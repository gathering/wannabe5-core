<?php

use App\Domain\Shared\Services\ServiceAccessAttributeRegistry;

test('service access registry returns owned and default attributes per service', function () {
    $registry = app(ServiceAccessAttributeRegistry::class);

    expect($registry->ownedAttributes('access'))->toHaveKey('access:owner');
    expect($registry->ownedAttributes('crew'))->toHaveKey('crew:manager');
    expect($registry->ownedAttributes('unknown-service'))->toBe([]);

    expect($registry->defaultServiceUserAttributes('communication'))->toHaveKey('com:client');
    expect($registry->defaultServiceUserAttributes('tasking'))->toBe([]);

    // tasking has no DB-backed user attribute source; safe for unit scope.
    $payload = $registry->forService('tasking');
    expect($payload)->toHaveKeys(['users', 'ownedAttributes', 'default-service-user', 'created']);
    expect($payload['ownedAttributes'])->toHaveKey('task:approver');
    expect($payload['users'])->toBe([]);
    expect($payload['created'])->toBeInt();
});
