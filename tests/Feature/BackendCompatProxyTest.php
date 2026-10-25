<?php

use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

test('compat proxy returns 503 for unknown service mapping', function () {
    config()->set('backend_compat.services.access', null);

    $response = $this->getJson('/api/ms/access/users/current');

    $response->assertStatus(503)->assertJson([
        'message' => "Backend compatibility service 'access' is not configured.",
    ]);
});

test('compat proxy forwards upstream response with status and body', function () {
    config()->set('backend_compat.services.access', 'https://backend.local/api/access');

    Http::fake([
        'https://backend.local/api/access/users/current?pretty=1' => Http::response([
            'id' => 'u-1',
            'roles' => ['crew'],
        ], 200, ['Content-Type' => 'application/json']),
    ]);

    $response = $this->getJson('/api/ms/access/users/current?pretty=1', [
        'Authorization' => 'Bearer token-1',
    ]);

    $response->assertOk()->assertJson([
        'id' => 'u-1',
        'roles' => ['crew'],
    ]);

    Http::assertSentCount(1);
});

test('compat proxy forwards non-json content for docs or previews', function () {
    config()->set('backend_compat.services.communication', 'https://backend.local/api/communication');

    Http::fake([
        'https://backend.local/api/communication/templates/12/preview' => Http::response(
            '<html>preview</html>',
            200,
            ['Content-Type' => 'text/html']
        ),
    ]);

    $response = $this->get('/api/ms/communication/templates/12/preview');

    $response->assertOk();
    expect($response->getContent())->toBe('<html>preview</html>');
});

test('compat proxy preserves multiple set-cookie headers from upstream auth responses', function () {
    config()->set('backend_compat.services.auth', 'https://backend.local/api/auth');

    Http::fake([
        'https://backend.local/api/auth/login' => Http::response('ok', 200, [
            'Set-Cookie' => [
                'wannabe_jwt=abc123; Path=/; HttpOnly',
                'wannabe_refresh=def456; Path=/; HttpOnly',
            ],
        ]),
    ]);

    $response = $this->get('/api/ms/auth/login');
    $response->assertOk();

    $cookies = $response->headers->getCookies();
    expect($cookies)->toHaveCount(2);
    expect($cookies[0]->getName())->toBe('wannabe_jwt');
    expect($cookies[1]->getName())->toBe('wannabe_refresh');
});

test('compat proxy forwards multipart payloads for profile image style endpoints', function () {
    config()->set('backend_compat.services.profile', 'https://backend.local/api/profile');

    Http::fake([
        'https://backend.local/api/profile/profile/77/profile-image' => Http::response(['uploaded' => true], 201),
    ]);

    $file = UploadedFile::fake()->image('avatar.jpg');
    $response = $this->post('/api/ms/profile/profile/77/profile-image', [
        'image' => $file,
    ]);

    $response->assertStatus(201)->assertExactJson(['uploaded' => true]);

    Http::assertSent(function (HttpClientRequest $request): bool {
        return str_contains($request->body(), 'avatar.jpg');
    });
});
