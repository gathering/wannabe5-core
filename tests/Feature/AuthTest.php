<?php

use App\Models\User;
use KeycloakGuard\ActingAsKeycloakUser;

uses(ActingAsKeycloakUser::class);

test('no access token deny access', function () {
    $this->get('/api/test')->assertUnauthorized();
});

test('that access token work', function () {
    $user = User::factory()->hasAccessTokens(1)->create();

    $this->withAccessTokenUser($user)
        ->get('/api/test')
        ->assertOk();
});

test('broken access token deny access', function () {
    $user = User::factory()->hasAccessTokens(1)->create();

    $this->withAccessToken('test', 'test')
        ->get('/api/test')
        ->assertUnauthorized();

    $this->withAccessToken($user->id, str()->random(64))
        ->get('/api/test')
        ->assertUnauthorized();
});

test('that actingAsKeycloakUser works', function () {
    $this->actingAsKeycloakUser()
        ->getJson('/api/test')
        ->assertOk();
});
