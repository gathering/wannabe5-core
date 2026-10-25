<?php

namespace App\Http\Controllers;

use App\Models\CrewEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CrewEventController extends Controller
{
    public function index(Request $request)
    {
        // Guests may list visible+active events (frontend /home SSR has no JWT forward).
        if (Auth::check()) {
            Gate::authorize('viewAny', CrewEvent::class);
        }

        $query = CrewEvent::query()->orderBy('start_date');

        $wantsHidden = $request->boolean('hidden') || $request->boolean('all');
        $wantsInactive = $request->boolean('inactive') || $request->boolean('all');
        $canSeeHidden = Auth::check() && Gate::allows('viewAnyHidden', CrewEvent::class);
        $canSeeInactive = Auth::check() && Gate::allows('viewAnyInactive', CrewEvent::class);

        if (! $wantsHidden || ! $canSeeHidden) {
            $query->where('visible', true);
        }
        if (! $wantsInactive || ! $canSeeInactive) {
            $query->where('active', true);
        }

        // Legacy EventCollection shape expected by wannabe5-frontend.
        return response()->json(['data' => $query->get()]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', CrewEvent::class);

        $validated = $request->validate([
            'name' => ['required', 'string'],
            'slug' => ['required', 'string', 'unique:crew_events,slug'],
            'location' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'visible' => ['boolean'],
            'active' => ['boolean'],
            'applications_open' => ['boolean'],
        ]);

        $event = CrewEvent::create($validated);

        return response()->json(['data' => $event], 201);
    }

    public function show(int|string $event_id)
    {
        $event = $this->findEvent($event_id);

        if (! $event->visible) {
            Gate::authorize('view', $event);
        }

        return response()->json(['data' => $event]);
    }

    public function update(Request $request, int|string $event_id)
    {
        $event = $this->findEvent($event_id);
        Gate::authorize('update', $event);
        $event->update($request->only([
            'name', 'slug', 'location', 'start_date', 'end_date', 'visible', 'active', 'applications_open',
        ]));

        return response()->json(['data' => $event->refresh()]);
    }

    public function delete(int|string $event_id)
    {
        $event = $this->findEvent($event_id);
        Gate::authorize('delete', $event);
        $event->delete();

        return response()->json(true);
    }

    private function findEvent(int|string $event_id): CrewEvent
    {
        if (is_numeric($event_id)) {
            return CrewEvent::query()->findOrFail((int) $event_id);
        }

        return CrewEvent::query()->where('slug', $event_id)->firstOrFail();
    }
}
