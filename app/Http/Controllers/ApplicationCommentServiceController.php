<?php

namespace App\Http\Controllers;

use App\Models\ApplicationComment;
use App\Models\ApplicationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationCommentServiceController extends Controller
{
    public function index(int $application_id)
    {
        $application = ApplicationRecord::query()->findOrFail($application_id);
        Gate::authorize('comment', $application);

        return response()->json($application->comments()->get());
    }

    public function create(int $application_id, Request $request)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $validated = $request->validate([
            'comment' => ['required', 'string'],
        ]);

        $application = ApplicationRecord::query()->findOrFail($application_id);
        Gate::authorize('comment', $application);
        $comment = $application->comments()->create([
            'user_id' => $user->id,
            'comment' => $validated['comment'],
        ]);

        return response()->json($comment, 201);
    }

    public function update(int $application_id, int $comment_id, Request $request)
    {
        Gate::authorize('comment', ApplicationRecord::findOrFail($application_id));

        $validated = $request->validate([
            'comment' => ['required', 'string'],
        ]);

        $comment = ApplicationComment::query()
            ->where('application_id', $application_id)
            ->findOrFail($comment_id);
        $comment->update(['comment' => $validated['comment']]);

        return response()->json($comment->refresh());
    }

    public function delete(int $application_id, int $comment_id)
    {
        Gate::authorize('comment', ApplicationRecord::findOrFail($application_id));

        ApplicationComment::query()
            ->where('application_id', $application_id)
            ->findOrFail($comment_id)
            ->delete();

        return response()->json();
    }
}
