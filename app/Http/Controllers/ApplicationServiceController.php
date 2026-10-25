<?php

namespace App\Http\Controllers;

use App\Domain\Application\Jobs\HandleApplicationStatusTransition;
use App\Models\ApplicationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationServiceController extends Controller
{
    public function index(Request $request, string $crew)
    {
        Gate::authorize('viewAny', ApplicationRecord::class);

        return response()->json(
            ApplicationRecord::query()
                ->where('crew_id', (int) $crew)
                ->where('event_id', (int) $request->integer('event_id', 0))
                ->get()
        );
    }

    public function store(Request $request)
    {
        Gate::authorize('create', ApplicationRecord::class);

        $validated = $request->validate([
            'user_id' => ['required', 'uuid'],
            'event_id' => ['required', 'integer'],
            'crew_id' => ['required', 'integer'],
            'status' => ['nullable', 'string'],
        ]);

        if (ApplicationRecord::query()->where('user_id', $validated['user_id'])->where('event_id', $validated['event_id'])->count() >= 3) {
            abort(403, 'Only 3 applications is allowed per event!');
        }
        if (ApplicationRecord::query()->where('user_id', $validated['user_id'])->where('crew_id', $validated['crew_id'])->exists()) {
            abort(403, 'Only 1 application per crew is allowed!');
        }

        $application = ApplicationRecord::create([
            ...$validated,
            'status' => $validated['status'] ?? 'received',
        ]);

        return response()->json($application, 201);
    }

    public function show(int $application)
    {
        $record = ApplicationRecord::query()->with(['answers.question', 'comments'])->findOrFail($application);
        Gate::authorize('view', $record);

        return response()->json($record);
    }

    public function update(int $application, Request $request)
    {
        $record = ApplicationRecord::query()->findOrFail($application);
        Gate::authorize('update', $record);
        $record->update($request->only(['status', 'message']));

        return response()->json($record->refresh());
    }

    public function delete(int $application)
    {
        $record = ApplicationRecord::query()->findOrFail($application);
        Gate::authorize('delete', $record);
        $record->delete();

        return response()->json(true);
    }

    public function allByUserId(Request $request, string $user)
    {
        return response()->json(
            ApplicationRecord::query()
                ->where('user_id', $user)
                ->where('event_id', (int) $request->integer('event_id', 0))
                ->get()
        );
    }

    public function updateStatus(Request $request, int $applicationId, string $status)
    {
        if (! in_array($status, ['received', 'reviewable', 'approved', 'waiting_list', 'declined'], true)) {
            return response()->json(['messages' => ['status' => 'Invalid status provided']], 403);
        }

        $record = ApplicationRecord::query()->findOrFail($applicationId);
        Gate::authorize('updateStatus', $record);
        $record->status = $status;
        $record->message = $request->input('message');
        if ($status === 'reviewable') {
            $record->reviewable_at = now();
        }
        $record->save();

        HandleApplicationStatusTransition::dispatch("{$applicationId}/{$status}", $request->query());

        return response()->json($record);
    }
}
