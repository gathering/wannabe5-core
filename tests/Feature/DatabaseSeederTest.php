<?php

use App\Models\User;
use App\Models\UserProfile;
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
    $this->assertModelExists($user->userProfile);

    expect($user->userProfile->firstname)->toBeString()->toBe('Test');
    expect($user->userProfile->lastname)->toBeString()->toBe('Brukersen');
    expect($user->userProfile->nickname)->toBeString()->toBe('testbruker');
    expect($user->accessTokens->first()->token)->toBeString()->toBe('testbruker');

    expect(User::all())->toHaveCount(11);
    expect(UserProfile::all())->toHaveCount(11);
});
