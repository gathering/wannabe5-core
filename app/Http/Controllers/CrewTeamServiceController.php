<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use App\Models\CrewTeam;
use Illuminate\Http\Request;

class CrewTeamServiceController extends Controller
{
    public function createCrewTeam(Request $request, int $event_id, int $crew_id)
    {
        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);
        $validated = $request->validate([
            'name' => ['required', 'string'],
        ]);

        return response()->json(CrewTeam::create([
            'crew_id' => $crew_id,
            'name' => $validated['name'],
        ]), 201);
    }

    public function updateCrewTeam(Request $request, int $event_id, int $crew_id, int $team_id)
    {
        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);
        $team = CrewTeam::query()->where('crew_id', $crew_id)->findOrFail($team_id);
        $team->update($request->only(['name']));

        return response()->json($team->refresh());
    }

    public function deleteCrewTeam(int $event_id, int $crew_id, int $team_id)
    {
        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);
        CrewTeam::query()->where('crew_id', $crew_id)->findOrFail($team_id)->delete();

        return response()->json(true);
    }
}
