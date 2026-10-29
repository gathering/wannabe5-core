<?php

namespace App\Domain\Shared\Support;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class WannabeJwtCookie
{
    public static function make(User $user, array $extra = []): SymfonyCookie
    {
        $ttl = (int) config('backend_compat.wannabe_jwt_ttl_minutes', 15);
        $user->loadMissing(['userProfile', 'accessAttributes']);

        $payload = [
            'sub' => $user->id,
            'email' => $user->username,
            'username' => $user->username,
            // Frontend SSR (Next.js) cannot serialize undefined — always emit strings/objects.
            'name' => self::displayName($user),
            'attr' => self::attributeMap($user),
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes($ttl)->timestamp,
            'iss' => config('app.url', 'wannabe5-core'),
            'aud' => 'account',
            'typ' => 'Bearer',
            ...$extra,
        ];

        $token = self::encode($payload);

        return Cookie::make(
            name: 'wannabe_jwt',
            value: $token,
            minutes: $ttl,
            path: '/',
            domain: null,
            secure: (bool) config('session.secure', false),
            httpOnly: true,
            raw: false,
            sameSite: 'Lax',
        );
    }

    public static function forget(): SymfonyCookie
    {
        return Cookie::forget('wannabe_jwt');
    }

    private static function displayName(User $user): string
    {
        $profile = $user->userProfile;
        if ($profile !== null) {
            $full = trim(implode(' ', array_filter([
                $profile->firstname,
                $profile->lastname,
            ])));
            if ($full !== '') {
                return $full;
            }
            if (filled($profile->nickname)) {
                return (string) $profile->nickname;
            }
        }

        return (string) ($user->username ?: $user->id);
    }

    /**
     * @return array<string, list<mixed>>
     */
    private static function attributeMap(User $user): array
    {
        $map = [];
        foreach ($user->accessAttributes as $attribute) {
            $map[(string) $attribute->key] = array_values($attribute->value);
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function encode(array $payload): string
    {
        $privateKey = config('backend_compat.wannabe_jwt_private_key');
        if (is_string($privateKey) && $privateKey !== '') {
            return JWT::encode($payload, $privateKey, 'RS256');
        }

        // Test/dev fallback when signing keys are not configured.
        return rtrim(strtr(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }
}
