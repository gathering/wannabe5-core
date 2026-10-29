<?php

use App\Domain\Auth\Services\KeycloakOidcClient;
use App\Models\User;

test('auth oidc callback rejects invalid state and missing code', function () {
    $this->getJson('/api/auth/callback?state=forged&code=abc')
        ->assertUnauthorized()
        ->assertJsonPath('error', 'Invalid OIDC state');

    $this->withSession(['authflow.oidc_state' => 'expected-state'])
        ->getJson('/api/auth/callback?state=expected-state')
        ->assertStatus(400)
        ->assertJsonPath('error', 'Missing authorization code');
});

test('auth oidc login stores state in session and sets session cookie', function () {
    config()->set('keycloak.base_url', 'https://sso.example.test');
    config()->set('keycloak.realm', 'wannabe');
    config()->set('keycloak.client_id', 'wannabe-core');
    config()->set('keycloak.client_secret', 'secret');
    config()->set('keycloak.redirect_uri', 'https://app.example.test/api/auth/callback');

    $login = $this->get('/api/auth/login?redirect_url=https://localhost/welcome');
    $login->assertRedirect();
    $login->assertCookie(config('session.cookie'));
    $login->assertSessionHas('authflow.oidc_state');

    $location = (string) $login->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
    expect((string) ($query['state'] ?? ''))->toBe((string) session('authflow.oidc_state'));
});

test('auth oidc callback creates user from token claims', function () {
    $sub = (string) fake()->uuid();
    $encode = static fn (array $payload): string => rtrim(strtr(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    $header = $encode(['alg' => 'none']);
    $access = $encode(['sub' => $sub, 'preferred_username' => 'oidc.user']);
    $id = $encode(['sub' => $sub, 'email' => 'oidc.user@example.test']);

    $oidc = Mockery::mock(KeycloakOidcClient::class);
    $oidc->shouldReceive('exchangeAuthorizationCode')
        ->once()
        ->with('auth-code')
        ->andReturn([
            'access_token' => $header.'.'.$access.'.',
            'id_token' => $header.'.'.$id.'.',
            'refresh_token' => 'refresh',
        ]);
    app()->instance(KeycloakOidcClient::class, $oidc);

    $this->withSession([
        'authflow.oidc_state' => 'good-state',
        'authflow.intended' => 'https://localhost/home',
    ])->get('/api/auth/callback?state=good-state&code=auth-code')
        ->assertRedirect('https://localhost/home');

    $user = User::query()->findOrFail($sub);
    expect($user->username)->toBe('oidc.user@example.test');
    expect($user->type)->toBe('user');
});

test('json login and registration persist the state returned in their authorization URL', function (string $endpoint) {
    config()->set('keycloak.base_url', 'https://sso.example.test');
    config()->set('keycloak.realm', 'wannabe');
    config()->set('keycloak.client_id', 'wannabe-core');
    config()->set('keycloak.client_secret', 'secret');
    config()->set('keycloak.redirect_uri', 'https://app.example.test/api/auth/callback');

    $response = $this->getJson('/api/auth/'.$endpoint)->assertOk();
    parse_str((string) parse_url($response->json('redirect_to'), PHP_URL_QUERY), $query);
    expect($query['state'])->not->toBeEmpty()->toBe(session('authflow.oidc_state'));
})->with(['login', 'register']);

test('revoked upstream refresh clears server session and rejects renewal', function () {
    $user = User::factory()->create();
    $oidc = Mockery::mock(KeycloakOidcClient::class);
    $oidc->shouldReceive('isConfigured')->andReturn(true);
    $oidc->shouldReceive('refreshAccessToken')->once()->andThrow(new RuntimeException('revoked'));
    app()->instance(KeycloakOidcClient::class, $oidc);
    $this->withSession(['authflow.user_id' => $user->id, 'authflow.tokens' => ['refresh_token' => 'revoked']]);
    $this->asUser($user)->post('/api/auth/refresh-session')->assertUnauthorized()
        ->assertSessionMissing('authflow.tokens')->assertSessionMissing('authflow.user_id');
});
