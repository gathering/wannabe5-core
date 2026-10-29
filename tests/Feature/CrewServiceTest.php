<?php

use App\Models\AccessAttribute;
use App\Models\CrewRole;
use App\Models\User;

test('crew service supports events crews users teams and pages', function () {
    $user = User::factory()->create();
    AccessAttribute::query()->create([
        'source' => 'test',
        'user_id' => $user->id,
        'key' => 'event:owner',
        'value' => ['*'],
    ]);

    $event = $this->asUser($user)->postJson('/api/crew/events', [
        'name' => 'The Gathering',
        'slug' => 'tg-test',
        'location' => 'Vikingskipet',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-05',
        'visible' => true,
        'active' => true,
        'applications_open' => true,
    ])->assertStatus(201);
    $eventId = $event->json('data.id');

    $role = CrewRole::create([
        'event_id' => $eventId,
        'name' => 'chief',
        'title' => 'Chief',
        'description' => 'Crew chief',
    ]);

    $crew = $this->asUser($user)->postJson("/api/crew/events/{$eventId}/crews", [
        'name' => 'Infra',
        'can_apply' => true,
        'short_description' => 'Infra crew',
    ])->assertStatus(201);
    $crewId = $crew->json('data.id');

    $team = $this->asUser($user)->postJson("/api/crew/events/{$eventId}/crews/{$crewId}/teams", [
        'name' => 'Network',
    ])->assertStatus(201);
    $teamId = $team->json('id');

    $this->asUser($user)->postJson("/api/crew/events/{$eventId}/crews/{$crewId}/users", [
        'user_id' => $user->id,
        'role_id' => $role->id,
        'team_id' => $teamId,
    ])->assertOk()
        ->assertJsonPath('user_id', $user->id)
        ->assertJsonPath('profile.user_id', $user->id);

    $this->asUser($user)->getJson("/api/crew/events/{$eventId}/crews")
        ->assertOk()
        ->assertJsonCount(1, 'data');
    $this->asUser($user)->getJson("/api/crew/events/{$eventId}/users")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.crew.id', $crewId)
        ->assertJsonPath('data.0.crew.name', 'Infra')
        ->assertJsonPath('data.0.role.id', $role->id)
        ->assertJsonPath('data.0.profile.user_id', $user->id);
    $this->asUser($user)->getJson("/api/crew/events/{$eventId}/crews/{$crewId}?pretty=1")
        ->assertOk()
        ->assertJsonCount(1, 'data.users')
        ->assertJsonPath('data.users.0.profile.user_id', $user->id)
        ->assertJsonPath('data.teams.0.id', $teamId);
    $this->asUser($user)->getJson("/api/crew/events/{$eventId}/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('0.crew.name', 'Infra');
    $this->asUser($user)->getJson("/api/crew/events/{$eventId}/crews/{$crewId}/chief")->assertOk()->assertJsonCount(1);

    $page = \App\Models\CrewPage::create([
        'event_id' => $eventId,
        'type' => 'event_frontpage',
        'content' => ['blocks' => []],
    ]);
    $this->asUser($user)->getJson("/api/crew/events/{$eventId}/pages/{$page->id}")->assertOk();
    $this->asUser($user)->getJson("/api/crew/events/{$eventId}/pages/event_frontpage")->assertOk();
    $this->asUser($user)->get('/api/crew/liveness')->assertOk()->assertSee('ok');
});

test('user crew membership includes nested crew and expands organizer children', function () {
    $user = User::factory()->create();
    AccessAttribute::query()->create([
        'source' => 'test',
        'user_id' => $user->id,
        'key' => 'event:owner',
        'value' => ['*'],
    ]);

    $event = $this->asUser($user)->postJson('/api/crew/events', [
        'name' => 'Organizer Expand',
        'slug' => 'tg-org-expand',
        'location' => 'Vikingskipet',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-05',
        'visible' => true,
        'active' => true,
    ])->assertStatus(201);
    $eventId = $event->json('data.id');

    $organizer = CrewRole::create([
        'event_id' => $eventId,
        'name' => 'organizer',
        'title' => 'Organizer',
        'description' => 'Organizer',
    ]);

    $parent = $this->asUser($user)->postJson("/api/crew/events/{$eventId}/crews", [
        'name' => 'Parent',
        'can_apply' => true,
    ])->assertStatus(201)->json('data');

    $child = $this->asUser($user)->postJson("/api/crew/events/{$eventId}/crews", [
        'name' => 'Child',
        'parent_crew' => $parent['id'],
        'can_apply' => true,
    ])->assertStatus(201)->json('data');

    $this->asUser($user)->postJson("/api/crew/events/{$eventId}/crews/{$parent['id']}/users", [
        'user_id' => $user->id,
        'role_id' => $organizer->id,
    ])->assertOk();

    $this->asUser($user)->getJson("/api/crew/events/{$eventId}/users/{$user->id}")
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.crew.name', 'Parent')
        ->assertJsonPath('1.crew.name', 'Child')
        ->assertJsonPath('1.crew.id', $child['id'])
        ->assertJsonPath('1.synthetic', true);
});
