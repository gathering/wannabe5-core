<?php

use Illuminate\Support\Facades\Http;

function backendRouteInventory(): array
{
    /** @var array<string, array<string, array<int, string>>> $inventory */
    $inventory = require base_path('tests/Fixtures/parity/backend_route_inventory.php');

    return $inventory;
}

function fillRoutePathParams(string $path): string
{
    $replacements = [
        'id' => '1',
        'user_id' => 'user-1',
        'attribute_id' => '11',
        'attribute_type_id' => '12',
        'attribute' => 'role',
        'value' => 'member',
        'question' => '101',
        'answer' => '202',
        'application' => '303',
        'crew' => 'crew-1',
        'user' => 'user-2',
        'application_id' => '304',
        'applicationId' => '305',
        'comment_id' => '401',
        'status' => 'approved',
        'event_id' => '501',
        'crew_id' => '502',
        'team_id' => '503',
        'task' => '504',
        'page_id' => '601',
        'page_type' => 'about',
        'list_owner_id' => '701',
        'list_recipient_id' => '702',
        'list_id' => '703',
        'ids' => '801,802',
        'approval_state' => 'approved',
        'profileId' => '901',
        'size' => 'small',
    ];

    return preg_replace_callback('/\{([^}]+)\}/', function (array $matches) use ($replacements): string {
        $key = $matches[1];

        return $replacements[$key] ?? '1';
    }, $path) ?? $path;
}

test('compat proxy forwards all backend microservice route surfaces', function () {
    Http::preventStrayRequests();

    $requestCount = 0;
    foreach (backendRouteInventory() as $service => $paths) {
        $base = "https://backend.local/api/{$service}";
        config()->set("backend_compat.services.{$service}", $base);

        foreach ($paths as $inventoryPath => $methods) {
            $resolved = fillRoutePathParams($inventoryPath);
            $proxyPath = $resolved === '/' ? '' : ltrim($resolved, '/');
            $proxyUrl = $proxyPath === ''
                ? "/api/ms/{$service}"
                : "/api/ms/{$service}/{$proxyPath}";
            $upstreamUrl = rtrim($base, '/').'/'.ltrim($proxyPath, '/');
            Http::fake([
                $upstreamUrl => function (\Illuminate\Http\Client\Request $request) use ($service, $inventoryPath) {
                    return Http::response([
                        'ok' => true,
                        'service' => $service,
                        'method' => strtoupper($request->method()),
                        'path' => $inventoryPath,
                    ], 200);
                },
            ]);

            foreach ($methods as $method) {
                $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
                $payload = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)
                    ? ['probe' => true]
                    : [];

                $response = $this->call($method, $proxyUrl, $payload, [], [], $server);
                $response->assertOk()->assertJson([
                    'ok' => true,
                    'service' => $service,
                    'method' => $method,
                    'path' => $inventoryPath,
                ]);

                $requestCount++;
            }
        }
    }

    expect($requestCount)->toBeGreaterThan(120);
});
