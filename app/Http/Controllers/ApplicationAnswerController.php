<?php

namespace App\Http\Controllers;

use App\Models\ApplicationAnswer;
use App\Models\ApplicationRecord;
use Illuminate\Http\Request;

class ApplicationAnswerController extends Controller
{
    public function index(Request $request, ?int $question = null)
    {
        $questionId = $question ?? (int) $request->integer('question_id');

        return response()->json(
            ApplicationAnswer::query()->where('question_id', $questionId)->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:application_questions,id'],
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'content' => ['nullable', 'string'],
        ]);

        $application = ApplicationRecord::query()->findOrFail($validated['application_id']);
        $answer = ApplicationAnswer::query()->firstOrNew([
            'application_id' => $validated['application_id'],
            'question_id' => $validated['question_id'],
        ]);
        $answer->user_id = $application->user_id;
        $answer->event_id = $application->event_id;
        $answer->content = $validated['content'] ?? null;
        $answer->save();

        return response()->json($answer, $answer->wasRecentlyCreated ? 201 : 200);
    }

    public function update(Request $request, int $answer)
    {
        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:application_questions,id'],
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'content' => ['nullable', 'string'],
        ]);

        $record = ApplicationAnswer::query()->findOrFail($answer);
        $application = ApplicationRecord::query()->findOrFail($validated['application_id']);
        $record->update([
            'question_id' => $validated['question_id'],
            'application_id' => $validated['application_id'],
            'user_id' => $application->user_id,
            'event_id' => $application->event_id,
            'content' => $validated['content'] ?? null,
        ]);

        return response()->json($record->refresh());
    }

    public function delete(int $answer)
    {
        ApplicationAnswer::query()->findOrFail($answer)->delete();

        return response()->json(true);
    }

    public function updateEventQuestion(Request $request, int $question)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $applications = ApplicationRecord::query()->where('user_id', $user->id)->get();
        foreach ($applications as $application) {
            $answer = ApplicationAnswer::query()->firstOrNew([
                'application_id' => $application->id,
                'question_id' => $question,
            ]);
            $answer->user_id = $application->user_id;
            $answer->event_id = $application->event_id;
            $answer->content = $request->input('content');
            $answer->save();
        }

        return response()->noContent();
    }
}
