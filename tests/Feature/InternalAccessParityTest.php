<?php

use App\Models\AccessAttribute;
use App\Models\Crew;
use App\Models\CrewEvent;
use App\Models\CrewRole;
use App\Models\CrewUser;
use App\Models\User;

test('native services expose _internal/access payload shape and liveness', function () {
    $user = User::factory()->create();

    AccessAttribute::create([
        'source' => 'crew',
        'user_id' => $user->id,
        'key' => 'crew:member',
        'value' => ['99'],
    ]);

    $event = CrewEvent::create([
        'name' => 'TG',
        'slug' => 'tg-internal',
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
    $crew = Crew::create([
        'event_id' => $event->id,
        'name' => 'Infra',
    ]);
    CrewUser::create([
        'user_id' => $user->id,
        'crew_id' => $crew->id,
        'role_id' => $role->id,
    ]);

    foreach (['access', 'application', 'communication', 'crew', 'tasking'] as $service) {
        $prefix = $service === 'profile' ? 'profileservice' : $service;
        $response = $this->asUser($user)->getJson("/api/{$prefix}/_internal/access")->assertOk();
        $response->assertJsonStructure([
            'users',
            'ownedAttributes',
            'default-service-user',
            'created',
        ]);
        $this->asUser($user)->get("/api/{$prefix}/liveness")->assertOk()->assertSee('ok');
    }

    $profile = $this->asUser($user)->getJson('/api/profileservice/_internal/access')->assertOk();
    $profile->assertJsonStructure(['users', 'ownedAttributes', 'default-service-user', 'created']);
    expect(collect($profile->json('users'))->contains(fn ($row) => $row[1] === 'profile:owner'))->toBeTrue();

    $crewAccess = $this->asUser($user)->getJson('/api/crew/_internal/access')->assertOk();
    expect(collect($crewAccess->json('users'))->contains(fn ($row) => $row[1] === 'crew:manager'))->toBeTrue();

    $access = $this->asUser($user)->getJson('/api/access/_internal/access')->assertOk();
    expect(collect($access->json('users'))->contains(fn ($row) => $row[1] === 'crew:member'))->toBeTrue();
});
