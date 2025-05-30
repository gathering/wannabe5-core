<?php

use App\Models\User;
use KeycloakGuard\ActingAsKeycloakUser;

uses(ActingAsKeycloakUser::class);

test('no access token deny access', function () {
    $this->getJson('/api/test')->assertUnauthorized();
});

test('that access token work', function () {
    $user = User::factory()->hasAccessTokens(1)->create();

    $this->withAccessTokenUser($user)
        ->getJson('/api/test')
        ->assertOk();
});

test('broken access token deny access', function () {
    $user = User::factory()->hasAccessTokens(1)->create();

    $this->withAccessToken('test', 'test')
        ->getJson('/api/test')
        ->assertUnauthorized();

    $this->withAccessToken($user->id, str()->random(64))
        ->getJson('/api/test')
        ->assertUnauthorized();
});

test('that actingAsKeycloakUser works', function () {
    $this->actingAsKeycloakUser()
        ->getJson('/api/test')
        ->assertOk();
});

test('that actingAsKeycloakUser works with existing user', function () {
    // Fill the database with a few dummy users
    $this->seed(UserSeeder::class);
    $user = User::factory()->create();

    $this->actingAsKeycloakUser($user, [
        'preferred_username' => 'test121@example.com',
    ])
        ->getJson('/api/test')
        ->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
    ]);

    // Check that username was updated to preferred_username
    expect($user->refresh()->username)->toBeString()->toBe('test121@example.com');
});

test('that actingAsKeycloakUser works with non-existing user', function () {
    $this->assertDatabaseEmpty('users');

    $this->actingAsKeycloakUser(null, [
        'sub' => 'a6a7c6a3-fa75-4791-b4db-f3a5e427f6f5',
        'preferred_username' => 'test131@example.com',
    ])
        ->getJson('/api/test')
        ->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => 'a6a7c6a3-fa75-4791-b4db-f3a5e427f6f5',
        'username' => 'test131@example.com',
    ]);
});
