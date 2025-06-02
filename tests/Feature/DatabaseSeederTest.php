<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('db:seed works', function () {
    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseHas('users', [
        'id' => 'eaf9efc2-adbb-4b27-b5a9-f6c60197ab56', // testbruker
    ]);

    $this->assertDatabaseHas('access_tokens', [
        'user_id' => 'eaf9efc2-adbb-4b27-b5a9-f6c60197ab56', // testbruker
    ]);

    $this->assertDatabaseHas('user_profiles', [
        'user_id' => 'eaf9efc2-adbb-4b27-b5a9-f6c60197ab56', // testbruker
    ]);

    $user = User::findOrFail('eaf9efc2-adbb-4b27-b5a9-f6c60197ab56');
    $this->assertModelExists($user);
    $this->assertModelExists($user->profile);

    expect($user->profile->firstname)->toBeString()->toBe('Test');
    expect($user->profile->lastname)->toBeString()->toBe('Brukersen');
    expect($user->accessTokens->first()->token)->toBeString()->toBe('testbruker');
});
