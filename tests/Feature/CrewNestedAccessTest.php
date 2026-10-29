<?php

use App\Domain\Shared\Services\ServiceAccessAttributeRegistry;
use App\Models\Crew;
use App\Models\CrewEvent;
use App\Models\CrewRole;
use App\Models\CrewUser;
use App\Models\User;

test('crew internal access expands manager attributes to nested child crews', function () {
    $user = User::factory()->create();
    $event = CrewEvent::create([
        'name' => 'TG Nested',
        'slug' => 'tg-nested',
        'location' => 'Vikingskipet',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-05',
        'visible' => true,
        'active' => true,
    ]);
    $role = CrewRole::create([
        'event_id' => $event->id,
        'name' => 'chief',
        'title' => 'Chief',
    ]);
    $parent = Crew::create([
        'event_id' => $event->id,
        'name' => 'Parent',
    ]);
    $child = Crew::create([
        'event_id' => $event->id,
        'parent_crew' => $parent->id,
        'name' => 'Child',
    ]);
    CrewUser::create([
        'user_id' => $user->id,
        'crew_id' => $parent->id,
        'role_id' => $role->id,
    ]);

    $payload = app(ServiceAccessAttributeRegistry::class)->forService('crew');
    $managerRows = collect($payload['users'])
        ->filter(fn ($row) => $row[0] === $user->id && $row[1] === 'crew:manager')
        ->values();

    expect($managerRows)->not->toBeEmpty();
    $values = collect($managerRows)->flatMap(fn ($row) => $row[2])->all();
    expect($values)->toContain($parent->id);
    expect($values)->toContain($child->id);
});
