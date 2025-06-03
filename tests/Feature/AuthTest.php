<?php

use App\Models\User;
use Illuminate\Support\Str;
use KeycloakGuard\ActingAsKeycloakUser;

uses(ActingAsKeycloakUser::class);

test('no access token deny access', function () {
    $this->getJson('/api/test')->assertUnauthorized();
});

test('access token without expires_at work', function () {
    $user = User::factory()->hasAccessTokens(1)->create();
    expect($user->accessTokens[0]->last_used_at)->toBeNull();

    $this->withAccessTokenUser($user)
        ->getJson('/api/test')
        ->assertOk();

    // Check that last_used_at is updated
    expect($user->accessTokens[0]->fresh()->last_used_at)->not->toBeNull();
});

test('invalid access token deny access', function () {
    $user = User::factory()->hasAccessTokens(1)->create();

    // Invalid key and token
    $this->withAccessToken('test', 'test')
        ->getJson('/api/test')
        ->assertUnauthorized();

    // Invalid token
    $this->withAccessToken($user->id, str()->random(64))
        ->getJson('/api/test')
        ->assertUnauthorized();

    // Invalid key
    $this->withAccessToken(Str::uuid(), $user->accessTokens[0]->token)
        ->getJson('/api/test')
        ->assertUnauthorized();
});

test('active access token allow access', function () {
    $user = User::factory()->hasAccessTokens(1, ['expires_at' => now()->addDay()])->create();

    $this->withAccessTokenUser($user)
        ->getJson('/api/test')
        ->assertOk();
});

test('expired access token deny access', function () {
    $user = User::factory()->hasAccessTokens(1, ['expires_at' => now()->subDay()])->create();

    $this->withAccessTokenUser($user)
        ->getJson('/api/test')
        ->assertUnauthorized();

    // last_used_at is not updated
    expect($user->accessTokens[0]->fresh()->last_used_at)->toBeNull();
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
        'email' => 'test121@example.com',
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
        'email' => 'test131@example.com',
        'given_name' => 'test',
        'family_name' => 'test',
    ])
        ->getJson('/api/test')
        ->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => 'a6a7c6a3-fa75-4791-b4db-f3a5e427f6f5',
        'username' => 'test131@example.com',
    ]);
});

test('profile created for new users', function () {
    $this->assertDatabaseEmpty('users');
    $this->assertDatabaseEmpty('user_profiles');

    $this->actingAsKeycloakUser(null, [
        'sub' => Str::uuid(),
        'email' => fake()->unique()->safeEmail(),
        'given_name' => fake()->firstName(),
        'family_name' => fake()->lastName(),
    ])
        ->getJson('/api/test')
        ->assertOk();

    $user = User::first();
    expect($user->userProfile->email)->toBeString()->toBe($user->username);
    expect($user->userProfile->firstname)->toBeString();
    expect($user->userProfile->lastname)->toBeString();
});
