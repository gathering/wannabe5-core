<?php

namespace App\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Throwable;

/**
 * Authenticate API requests from wannabe5-frontend via the `wannabe_jwt` cookie.
 */
class WannabeJwtCookieGuard implements Guard
{
    use GuardHelpers;

    public function __construct(
        protected Request $request,
        protected string $publicKey,
    ) {}

    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->request->cookies->get('wannabe_jwt');
        if (! is_string($token) || $token === '') {
            return null;
        }

        try {
            $pem = $this->normalizePem($this->publicKey);
            $claims = JWT::decode($token, new Key($pem, 'RS256'));
            $sub = (string) ($claims->sub ?? '');
            if ($sub === '') {
                return null;
            }

            $user = User::query()->firstOrCreate(
                ['id' => $sub],
                [
                    'username' => (string) ($claims->email ?? $claims->username ?? $sub),
                    'type' => 'user',
                ]
            );

            return $this->user = $user;
        } catch (Throwable) {
            return null;
        }
    }

    public function validate(array $credentials = [])
    {
        return $this->user() !== null;
    }

    private function normalizePem(string $key): string
    {
        $key = trim($key);
        if (str_contains($key, 'BEGIN PUBLIC KEY')) {
            return $key;
        }

        $wrapped = trim(chunk_split(preg_replace('/\s+/', '', $key) ?? '', 64, "\n"));

        return "-----BEGIN PUBLIC KEY-----\n{$wrapped}\n-----END PUBLIC KEY-----";
    }
}
