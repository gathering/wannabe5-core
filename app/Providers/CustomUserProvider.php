<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;

class CustomUserProvider extends EloquentUserProvider
{
    public function custom_retrieve(object $token, array $credentials)
    {
        $user = User::firstOrNew(['id' => $token->sub, 'type' => 'user']);
        $user->username = $token->email;

        // Will only run if there are any changes
        $user->save();

        // Set firstname and lastname
        if (isset($token->given_name) && isset($token->family_name)) {
            $user->profile->firstname = $token->given_name ?? null;
            $user->profile->lastname = $token->family_name ?? null;

            // Will only run if there are any changes
            $user->profile->save();
        }

        return $user;
    }
}
