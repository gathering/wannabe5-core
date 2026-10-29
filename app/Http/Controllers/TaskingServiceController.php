<?php

namespace App\Http\Controllers;

use App\Models\CrewUser;
use App\Models\Task;
use App\Models\TaskRecipient;
use App\Models\TaskResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TaskingServiceController extends Controller
{
    public function index(int $event_id)
    {
        return response()->json([
            'data' => Task::query()->where('event_id', $event_id)->where('enabled', true)->get(),
        ]);
    }

    public function getTasksForUser(Request $request, int $event_id, string $user_id)
    {
        abort_unless(auth()->id() === $user_id, 403);
        // Frontend layout fetches ?required=true to gate on incomplete required tasks only.
        $onlyRequiredIncomplete = $request->boolean('required');

        $tasks = Task::query()
            ->where('event_id', $event_id)
            ->where('enabled', true)
            ->with('recipients')
            ->get();

        $payload = [];
        foreach ($tasks as $task) {
            $recipient = $this->matchingRecipient($task->recipients, $event_id, $user_id);
            if ($recipient === null) {
                continue;
            }

            $response = TaskResponse::query()
                ->where('task_id', $task->id)
                ->where('user_id', $user_id)
                ->first();

            $completed = $response !== null ? (bool) $response->completed : false;
            $required = (bool) $recipient->mandatory;

            if ($onlyRequiredIncomplete && ($completed || ! $required)) {
                continue;
            }

            $row = $task->toArray();
            unset($row['recipients']);
            $row['completed'] = $completed;
            $row['skipped'] = false;
            $row['required'] = $required;
            $row['approval_needed'] = (bool) $recipient->approval_needed;
            $row['approved'] = $response !== null ? (bool) $response->approved : false;
            $payload[] = $row;
        }

        return response()->json(['data' => $payload]);
    }

    public function showTaskForUser(int $event_id, string $user_id, int $task)
    {
        abort_unless(auth()->id() === $user_id || auth()->user()->hasAccessAttribute('task:reader', (string) $task) || auth()->user()->hasAccessAttribute('task:approver', (string) $task) || auth()->user()->hasAccessAttribute('event:owner', (string) $event_id), 403);
        $record = Task::query()
            ->where('event_id', $event_id)
            ->with(['questions', 'recipients'])
            ->findOrFail($task);

        $recipient = $this->matchingRecipient($record->recipients, $event_id, $user_id);
        if ($recipient === null) {
            abort(403);
        }

        $response = TaskResponse::query()->where('task_id', $task)->where('user_id', $user_id)->first();

        $payload = $record->toArray();
        unset($payload['recipients']);
        $payload['required'] = (bool) $recipient->mandatory;
        $payload['skippable'] = (bool) ($record->skippable ?? false);
        $payload['approval_needed'] = (bool) $recipient->approval_needed;
        $payload['completed'] = $response !== null ? (bool) $response->completed : false;
        $payload['approved'] = $response !== null ? (bool) $response->approved : false;
        $payload['skipped'] = false;
        $payload['values'] = $response?->value;

        return response()->json(['data' => $payload]);
    }

    public function storeTaskResponse(Request $request, int $event_id, string $user_id, int $task)
    {
        abort_unless(auth()->id() === $user_id, 403);
        Task::query()->where('event_id', $event_id)->findOrFail($task);
        $assigned = Task::query()->with('recipients')->findOrFail($task);
        abort_unless($this->matchingRecipient($assigned->recipients, $event_id, $user_id) !== null, 403);
        $validated = $request->validate([
            'value' => ['nullable', 'array'],
            'completed' => ['boolean'],
        ]);

        $response = TaskResponse::query()->updateOrCreate(
            ['task_id' => $task, 'user_id' => $user_id],
            [
                'value' => $validated['value'] ?? [],
                'completed' => $validated['completed'] ?? true,
                'approved' => false,
            ]
        );

        return response()->json(['data' => $response], $response->wasRecentlyCreated ? 201 : 200);
    }

    public function approveTaskResponse(int $event_id, string $user_id, int $task)
    {
        abort_unless(auth()->user()->hasAccessAttribute('event:owner', (string) $event_id) || auth()->user()->hasAccessAttribute('task:approver', (string) $task), 403);
        Task::query()->where('event_id', $event_id)->findOrFail($task);
        $response = TaskResponse::query()->where('task_id', $task)->where('user_id', $user_id)->firstOrFail();
        $response->update(['approved' => true]);

        return response()->json(['data' => $response->refresh()]);
    }

    public function getTask(int $event_id, int $task)
    {
        abort_unless(auth()->user()->hasAccessAttribute('event:owner', (string) $event_id) || auth()->user()->hasAccessAttribute('task:reader', (string) $task) || auth()->user()->hasAccessAttribute('task:approver', (string) $task), 403);

        return response()->json([
            'data' => Task::query()->where('event_id', $event_id)->with(['questions', 'recipients'])->findOrFail($task),
        ]);
    }

    public function getTaskResponses(int $event_id, int $task)
    {
        abort_unless(auth()->user()->hasAccessAttribute('event:owner', (string) $event_id) || auth()->user()->hasAccessAttribute('task:reader', (string) $task) || auth()->user()->hasAccessAttribute('task:approver', (string) $task), 403);
        Task::query()->where('event_id', $event_id)->findOrFail($task);

        return response()->json([
            'data' => TaskResponse::query()->where('task_id', $task)->get(),
        ]);
    }

    public function getTasksForCrew(int $event_id, int $crew_id)
    {
        abort_unless(auth()->user()->hasAccessAttribute('event:owner', (string) $event_id) || auth()->user()->hasAccessAttribute('crew:owner', (string) $crew_id) || auth()->user()->hasAccessAttribute('crew:manager', (string) $crew_id), 403);
        $taskIds = TaskRecipient::query()
            ->where('recipient_type', 'crew')
            ->where('recipient_id', (string) $crew_id)
            ->pluck('task_id');

        return response()->json([
            'data' => Task::query()->where('event_id', $event_id)->whereIn('id', $taskIds)->get(),
        ]);
    }

    public function getTaskForCrew(int $event_id, int $crew_id, int $task)
    {
        abort_unless(auth()->user()->hasAccessAttribute('event:owner', (string) $event_id) || auth()->user()->hasAccessAttribute('crew:owner', (string) $crew_id) || auth()->user()->hasAccessAttribute('crew:manager', (string) $crew_id), 403);
        TaskRecipient::query()
            ->where('task_id', $task)
            ->where('recipient_type', 'crew')
            ->where('recipient_id', (string) $crew_id)
            ->firstOrFail();

        return response()->json([
            'data' => Task::query()->where('event_id', $event_id)->findOrFail($task),
        ]);
    }

    public function getTaskResponsesForCrew(int $event_id, int $crew_id, int $task)
    {
        $this->getTaskForCrew($event_id, $crew_id, $task);

        return response()->json([
            'data' => TaskResponse::query()->where('task_id', $task)->whereIn('user_id', CrewUser::query()->where('crew_id', $crew_id)->select('user_id'))->get(),
        ]);
    }

    /**
     * @param  Collection<int, TaskRecipient>  $recipients
     */
    private function matchingRecipient(Collection $recipients, int $event_id, string $user_id): ?TaskRecipient
    {
        foreach ($recipients as $recipient) {
            if ($recipient->recipient_type === 'crew' && CrewUser::query()->where('crew_id', $recipient->recipient_id)->where('user_id', $user_id)->whereIn('crew_id', \App\Models\Crew::query()->where('event_id', $event_id)->select('id'))->exists()) {
                return $recipient;
            }
            if ($recipient->recipient_type === 'event' && (string) $recipient->recipient_id === (string) $event_id) {
                return $recipient;
            }
            if (in_array($recipient->recipient_type, ['user', 'profile'], true)
                && (string) $recipient->recipient_id === $user_id) {
                return $recipient;
            }
        }

        return null;
    }
}
