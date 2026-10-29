<?php

use App\Models\ApplicationRecord;
use App\Models\User;

test('application show and update deny non-owners', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $application = ApplicationRecord::query()->create([
        'user_id' => $owner->id,
        'event_id' => 1,
        'crew_id' => 10,
        'status' => 'received',
    ]);

    $this->asUser($other)->getJson("/api/application/application/{$application->id}")
        ->assertForbidden();

    $this->asUser($other)->putJson("/api/application/{$application->id}", [
        'message' => 'nope',
    ])->assertForbidden();

    expect($application->fresh()->message)->toBeNull();
});

test('applicants cannot approve themselves or submit applications for other users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $record = ApplicationRecord::create([
        'user_id' => $owner->id, 'event_id' => 1, 'crew_id' => 10, 'status' => 'received',
    ]);
    $this->asUser($owner)->putJson("/api/application/{$record->id}/approved")->assertForbidden();
    $this->asUser($owner)->putJson("/api/application/{$record->id}", ['status' => 'approved'])->assertForbidden();
    $this->asUser($other)->putJson("/api/application/{$record->id}/approved")->assertForbidden();
    $this->asUser($other)->getJson("/api/application/user/{$owner->id}/applications?event_id=1")->assertForbidden();
    $this->asUser($other)->getJson('/api/application/10/applications?event_id=1')->assertForbidden();
    $this->asUser($other)->postJson('/api/application', [
        'user_id' => $owner->id, 'event_id' => 2, 'crew_id' => 11,
    ])->assertForbidden();
    expect($record->fresh()->status)->toBe('received');
});
