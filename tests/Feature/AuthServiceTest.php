<?php

use App\Domain\Shared\Support\WannabeJwtCookie;
use App\Models\User;

test('wannabe jwt cookie is httpOnly with expected name and subject claim', function () {
    // Unsigned base64 fallback (no private key) is not a 3-segment JWT; sign like production.
    $keypair = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($keypair, $privateKey);
    config()->set('backend_compat.wannabe_jwt_private_key', $privateKey);

    $user = User::factory()->create();
    $cookie = WannabeJwtCookie::make($user, ['username' => 'crew']);

    expect($cookie->getName())->toBe('wannabe_jwt');
    expect($cookie->isHttpOnly())->toBeTrue();
    expect($cookie->getSameSite())->toBe('lax');

    $parts = explode('.', $cookie->getValue());
    expect($parts)->toHaveCount(3);
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    expect($payload['sub'])->toBe($user->id);
    expect($payload['username'])->toBe('crew');
    expect($payload['name'])->toBeString()->not->toBe('');
    expect($payload['attr'])->toBeArray();
});

test('auth service provides login/register health and liveness endpoints', function () {
    // JSON clients get redirect targets without a browser OIDC bounce.
    config()->set('keycloak.base_url', null);
    config()->set('keycloak.client_id', null);
    config()->set('keycloak.client_secret', null);

    $this->getJson('/api/auth/login?redirect_url=https://localhost/welcome')
        ->assertOk()
        ->assertJsonPath('status', 'login_started')
        ->assertJsonPath('redirect_to', 'https://localhost/welcome');
    $this->getJson('/api/auth/login?redirect_url=https://evil.example/phish')
        ->assertOk()
        ->assertJsonPath('redirect_to', '/');
    $this->getJson('/api/auth/register')->assertOk()->assertJsonPath('status', 'register_started');
    $this->getJson('/api/auth/health')->assertOk()->assertJsonPath('ok', true);
    $this->get('/api/auth/liveness')->assertOk()->assertSee('ok');
});

test('auth service redirects browser clients to keycloak when oidc is configured', function () {
    config()->set('keycloak.base_url', 'https://sso.example.test');
    config()->set('keycloak.realm', 'wannabe');
    config()->set('keycloak.client_id', 'wannabe-core');
    config()->set('keycloak.redirect_uri', 'https://app.example.test/api/auth/callback');

    $response = $this->get('/api/auth/login?redirect_url=https://localhost/welcome');
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('https://sso.example.test/realms/wannabe/protocol/openid-connect/auth');
});

test('auth service provides protected session/profile endpoints', function () {
    $user = User::factory()->create();

    $this->asUser($user)->getJson('/api/auth/profile')
        ->assertOk()
        ->assertJsonPath('id', $user->id);

    $this->asUser($user)->get('/api/auth/after-login')
        ->assertOk()
        ->assertJsonPath('status', 'login_completed');

    $this->asUser($user)->get('/api/auth/refresh-session')
        ->assertOk()
        ->assertSee('valid');

    $this->asUser($user)->getJson('/api/auth/logout')
        ->assertOk()
        ->assertJsonPath('status', 'logout_started');

    $this->get('/api/auth/after-logout')
        ->assertOk()
        ->assertJsonPath('status', 'logged_out');
});

test('auth service supports service login with client credentials payload', function () {
    $response = $this->postJson('/api/auth/services/login', [
        'client_id' => 'svc-app',
        'client_secret' => 'dummy',
        'scope' => 'external-wannabe-service-user',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['access_token', 'token_type', 'expires_in'])
        ->assertJsonPath('token_type', 'bearer');

    $this->postJson('/api/auth/services/login', [
        'client_id' => 'svc-app',
        'client_secret' => 'dummy',
        'scope' => 'wrong-scope',
    ])->assertStatus(401)->assertJsonPath('error', 'Invalid login client, missing required scope');
});
