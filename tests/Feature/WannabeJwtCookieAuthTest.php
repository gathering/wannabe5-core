<?php

use App\Domain\Shared\Support\WannabeJwtCookie;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

test('wannabe jwt cookie is rs256 signed when private key is configured', function () {
    $keypair = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($keypair, $privateKey);
    $publicKey = openssl_pkey_get_details($keypair)['key'];

    config()->set('backend_compat.wannabe_jwt_private_key', $privateKey);
    config()->set('backend_compat.wannabe_jwt_public_key', $publicKey);

    $user = User::factory()->create();
    $cookie = WannabeJwtCookie::make($user);
    $token = $cookie->getValue();

    expect(substr_count($token, '.'))->toBe(2);

    $claims = JWT::decode($token, new Key($publicKey, 'RS256'));
    expect($claims->sub)->toBe($user->id);
});

test('api accepts wannabe_jwt cookie when public key is configured', function () {
    $keypair = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($keypair, $privateKey);
    $publicKey = openssl_pkey_get_details($keypair)['key'];

    config()->set('backend_compat.wannabe_jwt_private_key', $privateKey);
    config()->set('backend_compat.wannabe_jwt_public_key', $publicKey);

    $user = User::factory()->create();
    $token = WannabeJwtCookie::make($user)->getValue();

    $this->disableCookieEncryption();

    $this->withCredentials()
        ->withUnencryptedCookie('wannabe_jwt', $token)
        ->getJson('/api/auth/profile')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});
