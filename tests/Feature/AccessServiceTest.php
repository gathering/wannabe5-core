<?php

use App\Models\AccessAttribute;
use App\Models\AccessAttributeType;
use App\Models\User;

test('access service supports attribute-type and attribute CRUD', function () {
    $user = User::factory()->create();
    AccessAttribute::create(['source' => 'access', 'user_id' => $user->id, 'key' => 'access:owner', 'value' => ['*']]);

    $createType = $this->asUser($user)->postJson('/api/access/attribute-types', [
        'key' => 'crew:member',
        'source' => 'crew',
        'summary' => 'Crew membership',
        'description' => 'Member of crew',
    ]);
    $createType->assertStatus(201);
    $typeId = $createType->json('id');

    $this->asUser($user)->getJson('/api/access/attribute-types')->assertOk();
    $this->asUser($user)->getJson("/api/access/attribute-types/{$typeId}")
        ->assertOk()
        ->assertJson(['key' => 'crew:member']);

    $targetUser = User::factory()->create();
    $createAttribute = $this->asUser($user)->postJson('/api/access/attributes', [
        'source' => 'crew',
        'user_id' => $targetUser->id,
        'key' => 'crew:member',
        'value' => ['crew-1', 'crew-2'],
    ]);
    $createAttribute->assertStatus(201);
    $attributeId = $createAttribute->json('id');

    $this->asUser($user)->getJson('/api/access/attributes?key=crew:member&value=crew-1')->assertOk();
    $this->asUser($user)->getJson("/api/access/attributes/{$attributeId}")
        ->assertOk()
        ->assertJson(['key' => 'crew:member']);

    $this->asUser($user)->putJson("/api/access/attributes/{$attributeId}", [
        'source' => 'crew',
        'user_id' => $targetUser->id,
        'key' => 'crew:member',
        'value' => ['crew-3'],
    ])->assertOk()->assertJson(['value' => ['crew-3']]);

    $this->asUser($user)->getJson("/api/access/attribute-types/{$typeId}/attributes")->assertOk();

    $this->asUser($user)->deleteJson("/api/access/attributes/{$attributeId}")->assertNoContent();
    $this->asUser($user)->deleteJson("/api/access/attribute-types/{$typeId}")->assertNoContent();
});

test('access user endpoints expose current user and has-attribute checks', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    AccessAttributeType::create([
        'key' => 'crew:member',
        'source' => 'crew',
        'summary' => 'Crew membership',
        'description' => null,
    ]);
    AccessAttribute::create([
        'source' => 'crew',
        'user_id' => $user->id,
        'key' => 'crew:member',
        'value' => ['crew-42'],
    ]);
    AccessAttribute::create([
        'source' => 'crew',
        'user_id' => $other->id,
        'key' => 'crew:member',
        'value' => ['*'],
    ]);

    $this->asUser($user)->getJson('/api/access/users/current')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('attributes.crew:member.0', 'crew-42');

    $this->asUser($user)->getJson('/api/access/users/current?pretty=true')
        ->assertOk()
        ->assertJsonPath('sub', $user->id)
        ->assertJsonPath('attr.crew:member.value.0', 'crew-42')
        ->assertJsonPath('attr.crew:member.description.summary', 'Crew membership')
        ->assertJsonPath('attr.crew:member.description.source', 'crew');

    $this->asUser($user)->getJson("/api/access/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('id', $user->id);

    $this->asUser($user)->getJson("/api/access/users/{$user->id}?pretty=true")
        ->assertOk()
        ->assertJsonPath('sub', $user->id)
        ->assertJsonStructure(['sub', 'attr', 'partials']);

    $this->asUser($user)->getJson("/api/access/users/{$user->id}/attributes")
        ->assertOk()
        ->assertJsonCount(1);

    $response = $this->asUser($user)->getJson('/api/access/users/current/has/crew:member/crew-42')->assertOk();
    expect($response->json())->toBeTrue();

    $response = $this->asUser($user)->getJson("/api/access/users/{$other->id}/has/crew:member/anything")->assertOk();
    expect($response->json())->toBeTrue();
});

test('ordinary users cannot grant or change access permissions', function () {
    $user = User::factory()->create();
    $attribute = AccessAttribute::create([
        'source' => 'crew', 'user_id' => $user->id,
        'key' => 'crew:member', 'value' => ['1'],
    ]);
    $payload = [
        'source' => 'access', 'user_id' => $user->id,
        'key' => 'event:owner', 'value' => ['*'],
    ];

    $this->asUser($user)->postJson('/api/access/attributes', $payload)->assertForbidden();
    $this->asUser($user)->putJson("/api/access/attributes/{$attribute->id}", $payload)->assertForbidden();
    $this->asUser($user)->deleteJson("/api/access/attributes/{$attribute->id}")->assertForbidden();
    expect($attribute->fresh()->key)->toBe('crew:member');
    expect(AccessAttribute::where('key', 'event:owner')->exists())->toBeFalse();
});

test('ordinary users cannot administer attribute types', function () {
    $user = User::factory()->create();
    $type = AccessAttributeType::create([
        'key' => 'crew:member', 'source' => 'crew', 'summary' => 'Crew membership',
    ]);
    $payload = ['key' => 'event:owner', 'source' => 'crew', 'summary' => 'Event owner'];
    $this->asUser($user)->postJson('/api/access/attribute-types', $payload)->assertForbidden();
    $this->asUser($user)->putJson('/api/access/attribute-types/'.$type->id, $payload)->assertForbidden();
    $this->asUser($user)->deleteJson('/api/access/attribute-types/'.$type->id)->assertForbidden();
    expect($type->fresh()->key)->toBe('crew:member');
});
