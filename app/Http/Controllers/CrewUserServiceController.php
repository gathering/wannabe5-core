<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use App\Models\CrewRole;
use App\Models\CrewUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CrewUserServiceController extends Controller
{
    public function getUsersByEvent(int $event_id)
    {
        abort_unless(auth()->user()->hasAccessAttribute('event:owner', (string) $event_id) || auth()->user()->hasAccessAttribute('event:manager', (string) $event_id) || auth()->user()->hasAccessAttribute('event:reader', (string) $event_id) || auth()->user()->hasAccessAttribute('event:member', (string) $event_id), 403);
        $crewIds = Crew::query()->where('event_id', $event_id)->pluck('id');

        return response()->json(CrewUser::query()->whereIn('crew_id', $crewIds)->with('role')->get());
    }

    public function showUserCrew(int $event_id, string $user_id)
    {
        abort_unless(auth()->id() === $user_id || auth()->user()->hasAccessAttribute('event:owner', (string) $event_id) || auth()->user()->hasAccessAttribute('event:manager', (string) $event_id) || auth()->user()->hasAccessAttribute('event:reader', (string) $event_id), 403);
        $crewIds = Crew::query()->where('event_id', $event_id)->pluck('id');

        return response()->json(
            CrewUser::query()->whereIn('crew_id', $crewIds)->where('user_id', $user_id)->with('role')->get()
        );
    }

    public function createCrewUser(Request $request, int $event_id, int $crew_id)
    {
        Gate::authorize('update', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);
        $validated = $request->validate([
            'user_id' => ['required', 'uuid'],
            'role_id' => ['required', 'integer', Rule::exists('crew_roles', 'id')->where('event_id', $event_id)],
            'team_id' => ['nullable', 'integer', Rule::exists('crew_teams', 'id')->where('crew_id', $crew_id)],
            'custom_title' => ['nullable', 'string'],
        ]);

        return response()->json(CrewUser::create([
            ...$validated,
            'crew_id' => $crew_id,
        ]), 201);
    }

    public function showCrewUser(int $event_id, int $crew_id, string $user_id)
    {
        Gate::authorize('view', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);

        return response()->json(
            CrewUser::query()->where('crew_id', $crew_id)->where('user_id', $user_id)->with('role')->firstOrFail()
        );
    }

    public function updateCrewUser(Request $request, int $event_id, int $crew_id, string $user_id)
    {
        Gate::authorize('update', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);
        $crewUser = CrewUser::query()->where('crew_id', $crew_id)->where('user_id', $user_id)->firstOrFail();
        $crewUser->update($request->validate([
            'role_id' => ['sometimes', 'integer', Rule::exists('crew_roles', 'id')->where('event_id', $event_id)],
            'team_id' => ['sometimes', 'nullable', 'integer', Rule::exists('crew_teams', 'id')->where('crew_id', $crew_id)],
            'custom_title' => ['sometimes', 'nullable', 'string'],
        ]));

        return response()->json($crewUser->refresh()->load('role'));
    }

    public function deleteCrewUser(int $event_id, int $crew_id, string $user_id)
    {
        Gate::authorize('update', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);
        CrewUser::query()->where('crew_id', $crew_id)->where('user_id', $user_id)->firstOrFail()->delete();

        return response()->json(true);
    }

    public function showCrewChief(int $event_id, int $crew_id)
    {
        Gate::authorize('view', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        return $this->showByRoleName($event_id, $crew_id, 'chief');
    }

    public function showCrewOrganizer(int $event_id, int $crew_id)
    {
        Gate::authorize('view', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        return $this->showByRoleName($event_id, $crew_id, 'organizer');
    }

    private function showByRoleName(int $event_id, int $crew_id, string $roleName)
    {
        Gate::authorize('view', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);
        $roleIds = CrewRole::query()->where('event_id', $event_id)->where('name', $roleName)->pluck('id');

        return response()->json(
            CrewUser::query()->where('crew_id', $crew_id)->whereIn('role_id', $roleIds)->with('role')->get()
        );
    }
}
