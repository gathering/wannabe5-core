<?php

namespace App\Http\Controllers;

use App\Domain\Crew\Services\CrewMembershipPresenter;
use App\Models\Crew;
use App\Models\CrewEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CrewServiceController extends Controller
{
    public function __construct(private CrewMembershipPresenter $memberships) {}

    public function index(int $event_id)
    {
        CrewEvent::query()->findOrFail($event_id);

        return response()->json([
            'data' => Crew::query()->where('event_id', $event_id)->orderBy('sorted_weight')->get(),
        ]);
    }

    public function create(Request $request, int $event_id)
    {
        Gate::authorize('delete', new Crew(['event_id' => $event_id]));
        CrewEvent::query()->findOrFail($event_id);
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'parent_crew' => ['nullable', 'integer', Rule::exists('crews', 'id')->where('event_id', $event_id)],
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

    public function show(Request $request, int $event_id, int $crew_id)
    {
        Gate::authorize('view', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        $pretty = $request->boolean('pretty');
        $with = $pretty
            ? ['teams', 'roles', 'users.role', 'users.team', 'users.crew']
            : ['users.role', 'users.team'];

        $crew = Crew::query()
            ->where('event_id', $event_id)
            ->with($with)
            ->findOrFail($crew_id);

        $payload = $crew->toArray();
        // Always expose users for show so member counts / lists work; pretty also hydrates profiles.
        $payload['users'] = $this->memberships->presentMany($crew->users);
        if ($pretty) {
            $payload['teams'] = $crew->teams;
            $payload['roles'] = $crew->roles;
        }

        return response()->json(['data' => $payload]);
    }

    public function update(Request $request, int $event_id, int $crew_id)
    {
        Gate::authorize('update', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        $crew = Crew::query()->where('event_id', $event_id)->findOrFail($crew_id);
        if ($request->has('parent_crew')) {
            $request->validate(['parent_crew' => ['nullable', 'integer', Rule::exists('crews', 'id')->where('event_id', $event_id)]]);
            abort_if((int) $request->input('parent_crew') === $crew_id || in_array((int) $request->input('parent_crew'), $crew->descendantIds(), true), 422);
        }
        $crew->update($request->only([
            'name', 'parent_crew', 'hidden', 'can_apply', 'short_description', 'long_description', 'sorted_weight',
        ]));

        return response()->json(['data' => $crew->refresh()]);
    }

    public function delete(int $event_id, int $crew_id)
    {
        Gate::authorize('delete', Crew::query()->where('event_id', $event_id)->findOrFail($crew_id));

        Crew::query()->where('event_id', $event_id)->findOrFail($crew_id)->delete();

        return response()->json(true);
    }
}
