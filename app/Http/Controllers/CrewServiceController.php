<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use App\Models\CrewEvent;
use Illuminate\Http\Request;

class CrewServiceController extends Controller
{
    public function index(int $event_id)
    {
        CrewEvent::query()->findOrFail($event_id);

        return response()->json([
            'data' => Crew::query()->where('event_id', $event_id)->orderBy('sorted_weight')->get(),
        ]);
    }

    public function create(Request $request, int $event_id)
    {
        CrewEvent::query()->findOrFail($event_id);
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'parent_crew' => ['nullable', 'integer', 'exists:crews,id'],
            'hidden' => ['boolean'],
            'can_apply' => ['boolean'],
            'short_description' => ['nullable', 'string'],
            'long_description' => ['nullable', 'string'],
            'sorted_weight' => ['integer'],
        ]);

        $crew = Crew::create([
            ...$validated,
            'event_id' => $event_id,
        ]);

        return response()->json(['data' => $crew], 201);
    }

    public function show(int $event_id, int $crew_id)
    {
        return response()->json([
            'data' => Crew::query()->where('event_id', $event_id)->findOrFail($crew_id),
        ]);
    }

    public function update(Request $request, int $event_id, int $crew_id)
    {
        $crew = Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);
        $crew->update($request->only([
            'name', 'parent_crew', 'hidden', 'can_apply', 'short_description', 'long_description', 'sorted_weight',
        ]));

        return response()->json(['data' => $crew->refresh()]);
    }

    public function delete(int $event_id, int $crew_id)
    {
        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id)->delete();

        return response()->json(true);
    }
}
