<?php

declare(strict_types=1);

$matrixPath = __DIR__.'/../docs/parity/matrix.md';
if (! file_exists($matrixPath)) {
    fwrite(STDERR, "Missing parity matrix at docs/parity/matrix.md\n");
    exit(1);
}

$contents = file_get_contents($matrixPath);
$requiredDomains = ['Auth', 'Access', 'Application', 'Communication', 'Crew', 'Profile', 'Tasking'];

foreach ($requiredDomains as $domain) {
    if (! str_contains($contents, "| {$domain} |")) {
        fwrite(STDERR, "Parity matrix is missing domain row: {$domain}\n");
        exit(1);
    }
}

$inventoryPath = __DIR__.'/../tests/Fixtures/parity/backend_route_inventory.php';
if (! file_exists($inventoryPath)) {
    fwrite(STDERR, "Missing backend route inventory fixture at tests/Fixtures/parity/backend_route_inventory.php\n");
    exit(1);
}

/** @var array<string, array<string, array<int, string>>> $inventory */
$inventory = require $inventoryPath;
$requiredServices = ['access', 'application', 'auth', 'communication', 'crew', 'profile', 'tasking'];

foreach ($requiredServices as $service) {
    if (! array_key_exists($service, $inventory)) {
        fwrite(STDERR, "Route inventory missing service: {$service}\n");
        exit(1);
    }

    if ($inventory[$service] === []) {
        fwrite(STDERR, "Route inventory has no paths for service: {$service}\n");
        exit(1);
    }
}

$operationCount = 0;
foreach ($inventory as $paths) {
    foreach ($paths as $methods) {
        $operationCount += count($methods);
    }
}

if ($operationCount < 120) {
    fwrite(STDERR, "Route inventory operation count unexpectedly low: {$operationCount}\n");
    exit(1);
}

fwrite(STDOUT, "Parity matrix and route inventory checks passed ({$operationCount} operations).\n");
