<?php

namespace App\Http\Controllers;

use App\Domain\Crew\Services\CrewMembershipPresenter;
use App\Models\Crew;
use App\Models\CrewRole;
use App\Models\CrewUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CrewUserServiceController extends Controller
{
    /** @var list<string> */
    private const MEMBERSHIP_WITH = ['crew:id,name,short_description,event_id,parent_crew', 'role:id,name,title,event_id', 'team:id,name,crew_id'];

    public function __construct(private CrewMembershipPresenter $memberships) {}

    public function getUsersByEvent(int $event_id)
    {
        abort_unless(auth()->user()->hasAccessAttribute('event:owner', (string) $event_id) || auth()->user()->hasAccessAttribute('event:manager', (string) $event_id) || auth()->user()->hasAccessAttribute('event:reader', (string) $event_id) || auth()->user()->hasAccessAttribute('event:member', (string) $event_id), 403);
        $crewIds = Crew::query()->where('event_id', $event_id)->pluck('id');

        $users = CrewUser::query()
            ->whereIn('crew_id', $crewIds)
            ->with(self::MEMBERSHIP_WITH)
            ->get();

        // Legacy CrewUserCollection shape: { data: [...] } — member search reads `.data`.
        return response()->json([
            'data' => $this->memberships->presentMany($users),
        ]);
    }

    public function showUserCrew(int $event_id, string $user_id)
    {
        abort_unless(auth()->id() === $user_id || auth()->user()->hasAccessAttribute('event:owner', (string) $event_id) || auth()->user()->hasAccessAttribute('event:manager', (string) $event_id) || auth()->user()->hasAccessAttribute('event:reader', (string) $event_id), 403);
        $crewIds = Crew::query()->where('event_id', $event_id)->pluck('id');

        $crewUsers = CrewUser::query()
            ->whereIn('crew_id', $crewIds)
            ->where('user_id', $user_id)
            ->with(self::MEMBERSHIP_WITH)
            ->get();

        // Legacy parity: organizers also "see" descendant crews in the sidenav.
        $organizerRole = CrewRole::query()
            ->where('event_id', $event_id)
            ->where('name', 'organizer')
            ->first();

        if ($organizerRole === null) {
            return response()->json($this->memberships->presentMany($crewUsers));
        }

        $entries = [];
        foreach ($crewUsers as $crewUser) {
            $entries[] = $crewUser;

            if ((int) $crewUser->role_id !== (int) $organizerRole->id || $crewUser->crew === null) {
                continue;
            }

            foreach ($crewUser->crew->children()->get() as $childCrew) {
                $synthetic = new CrewUser([
                    'user_id' => $user_id,
                    'crew_id' => $childCrew->id,
                    'role_id' => $organizerRole->id,
                    'team_id' => null,
                    'custom_title' => $crewUser->custom_title,
                ]);
                $synthetic->setRelation('crew', $childCrew);
                $synthetic->setRelation('role', $organizerRole);
                $synthetic->setRelation('team', null);
                $synthetic->setAttribute('synthetic', true);
                $entries[] = $synthetic;
            }
        }

        return response()->json($this->memberships->presentMany($entries));
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

        $crewUser = CrewUser::create([
            ...$validated,
            'crew_id' => $crew_id,
        ])->load(self::MEMBERSHIP_WITH);

        // Frontend form handlers only treat HTTP 200 as success.
        return response()->json($this->memberships->presentOne($crewUser));
    }

    public function showCrewUser(int $event_id, int $crew_id, string $user_id)
    {
        Gate::authorize('view', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);

        $crewUser = CrewUser::query()
            ->where('crew_id', $crew_id)
            ->where('user_id', $user_id)
            ->with(self::MEMBERSHIP_WITH)
            ->firstOrFail();

        return response()->json($this->memberships->presentOne($crewUser));
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

        return response()->json($this->memberships->presentOne($crewUser->refresh()->load(self::MEMBERSHIP_WITH)));
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

        $users = CrewUser::query()
            ->where('crew_id', $crew_id)
            ->whereIn('role_id', $roleIds)
            ->with(self::MEMBERSHIP_WITH)
            ->get();

        return response()->json($this->memberships->presentMany($users));
    }
}
