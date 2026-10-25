<?php

namespace App\Http\Controllers;

use App\Models\ApplicationQuestion;
use Illuminate\Http\Request;

class ApplicationQuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = ApplicationQuestion::query();
        if ($request->filled('crew_id')) {
            $query->where('crew_id', (int) $request->integer('crew_id'));
        }
        if ($request->filled('event_id')) {
            $query->where('event_id', (int) $request->integer('event_id'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'crew_id' => ['nullable', 'integer'],
            'event_id' => ['nullable', 'integer'],
            'type' => ['required', 'string'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'required' => ['boolean'],
            'options' => ['array'],
        ]);

        return response()->json(ApplicationQuestion::create($validated), 201);
    }

    public function show(int $id)
    {
        return response()->json(ApplicationQuestion::query()->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $question = ApplicationQuestion::query()->findOrFail($id);
        $question->update($request->only(['crew_id', 'event_id', 'title', 'description', 'required', 'options']));

        return response()->json($question->refresh());
    }

    public function destroy(int $id)
    {
        ApplicationQuestion::query()->findOrFail($id)->delete();

        return response()->json(true);
    }
}
