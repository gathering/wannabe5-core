<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function withAccessToken(string $user, string $token): self
    {
        return $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode("{$user}:{$token}"),
        ]);
    }

    public function withAccessTokenUser(User $user): self
    {
        $token = $user->accessTokens->first()->token;

        return $this->withAccessToken($user->id, $token);
    }

    public function asUser(?User $user = null): self
    {
        $user = $user ?? User::factory()->create();

        return $this->actingAsKeycloakUser($user, [
            'email' => $user->username,
        ]);
    }
}
