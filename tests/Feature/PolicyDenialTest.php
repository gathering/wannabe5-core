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
