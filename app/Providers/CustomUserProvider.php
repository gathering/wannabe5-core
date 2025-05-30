<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;

class CustomUserProvider extends EloquentUserProvider
{
    public function custom_retrieve(object $token, array $credentials)
    {
        $user = User::firstOrNew(['id' => $token->sub, 'type' => 'user']);
        $user->username = $token->preferred_username;

        // Will only run if there are any changes
        $user->save();

        return $user;
    }
}
