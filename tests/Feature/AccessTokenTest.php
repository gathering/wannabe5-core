<?php

use App\Models\AccessToken;
use App\Models\User;

test('token can be instantiated', function () {
    $user = User::factory()->create();
    $token = new AccessToken;
    $token->name = 'test';
    $token->user_id = $user->id;
    $token->save();

    $this->assertDatabaseHas('access_tokens', [
        'user_id' => $user->id,
    ]);
});
