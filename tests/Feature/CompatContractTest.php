<?php

use Illuminate\Support\Facades\Http;

function fixtureJson(string $relativePath): array
{
    return json_decode(file_get_contents(base_path("tests/Fixtures/{$relativePath}")), true, flags: JSON_THROW_ON_ERROR);
}

test('compat proxy preserves response shape for access current user payload', function () {
    config()->set('backend_compat.services.access', 'https://backend.local/api/access');

    $fixture = fixtureJson('contracts/ms_access_users_current.json');
    Http::fake([
        'https://backend.local/api/access/users/current' => Http::response($fixture, 200),
    ]);

    $response = $this->getJson('/api/ms/access/users/current');
    $response->assertOk();
    expect($response->json())->toEqual($fixture);
});

test('compat proxy forwards auth and cookie headers to upstream', function () {
    config()->set('backend_compat.services.auth', 'https://backend.local/api/auth');

    Http::fake([
        'https://backend.local/api/auth/refresh-session' => Http::response('renewed', 200, [
            'Content-Type' => 'text/plain',
        ]),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer jwt-token',
        'Cookie' => 'wannabe_jwt=abc123',
    ])->post('/api/ms/auth/refresh-session');

    $response->assertOk();
    expect($response->getContent())->toBe('renewed');

    Http::assertSentCount(1);
});

test('compat proxy forwards upstream error response unchanged', function () {
    config()->set('backend_compat.services.application', 'https://backend.local/api/application');

    Http::fake([
        'https://backend.local/api/application/questions' => Http::response([
            'status' => 0,
            'messages' => ['question is required'],
        ], 422),
    ]);

    $response = $this->postJson('/api/ms/application/questions', []);
    $response->assertStatus(422)->assertExactJson([
        'status' => 0,
        'messages' => ['question is required'],
    ]);
});
