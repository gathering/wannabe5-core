<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function withAccessTokenUser(User $user): self
    {
        $token = $user->accessTokens->first()->token;

        return $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode("{$user->id}:{$token}"),
        ]);
    }

    public function withAccessToken(string $user, string $token): self
    {
        return $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode("{$user}:{$token}"),
        ]);
    }
}
