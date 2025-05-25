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
