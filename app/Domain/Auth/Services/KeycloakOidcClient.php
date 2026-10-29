<?php

namespace App\Domain\Auth\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class KeycloakOidcClient
{
    public function isConfigured(): bool
    {
        return filled(config('keycloak.base_url'))
            && filled(config('keycloak.realm'))
            && filled(config('keycloak.client_id'));
    }

    public function authorizationUrl(string $state, ?string $redirectUri = null, string $prompt = 'login'): string
    {
        return $this->endpoint('auth').'?'.http_build_query([
            'client_id' => config('keycloak.client_id'),
            'redirect_uri' => $redirectUri ?? $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'prompt' => $prompt,
        ]);
    }

    public function registrationUrl(string $state, ?string $redirectUri = null): string
    {
        return $this->endpoint('registrations').'?'.http_build_query([
            'client_id' => config('keycloak.client_id'),
            'redirect_uri' => $redirectUri ?? $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
        ]);
    }

    public function logoutUrl(?string $idTokenHint = null, ?string $postLogoutRedirectUri = null): string
    {
        $params = [
            'client_id' => config('keycloak.client_id'),
            'post_logout_redirect_uri' => $postLogoutRedirectUri
                ?? config('keycloak.post_logout_redirect_uri')
                ?? url('/api/auth/after-logout'),
        ];
        if ($idTokenHint !== null && $idTokenHint !== '') {
            $params['id_token_hint'] = $idTokenHint;
        }

        return $this->endpoint('logout').'?'.http_build_query($params);
    }

    public function accountUrl(): string
    {
        return rtrim((string) config('keycloak.base_url'), '/').'/realms/'.config('keycloak.realm').'/account';
    }

    /**
     * @return array{access_token:string, refresh_token?:string, id_token?:string, expires_in?:int, token_type?:string}
     */
    public function exchangeAuthorizationCode(string $code, ?string $redirectUri = null): array
    {
        if (! $this->isConfigured() || blank(config('keycloak.client_secret'))) {
            throw new RuntimeException('Keycloak OIDC client is not fully configured for code exchange.');
        }

        $response = Http::asForm()->post($this->endpoint('token'), [
            'grant_type' => 'authorization_code',
            'client_id' => config('keycloak.client_id'),
            'client_secret' => config('keycloak.client_secret'),
            'code' => $code,
            'redirect_uri' => $redirectUri ?? $this->redirectUri(),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Keycloak token exchange failed: '.$response->body());
        }

        /** @var array{access_token:string, refresh_token?:string, id_token?:string, expires_in?:int, token_type?:string} $payload */
        $payload = $response->json();

        return $payload;
    }

    /**
     * @return array{access_token:string, refresh_token?:string, id_token?:string, expires_in?:int, token_type?:string}
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $response = Http::asForm()->post($this->endpoint('token'), [
            'grant_type' => 'refresh_token',
            'client_id' => config('keycloak.client_id'),
            'client_secret' => config('keycloak.client_secret'),
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Keycloak refresh failed: '.$response->body());
        }

        /** @var array{access_token:string, refresh_token?:string, id_token?:string, expires_in?:int, token_type?:string} $payload */
        $payload = $response->json();

        return $payload;
    }

    public function newState(): string
    {
        return Str::random(40);
    }

    public function redirectUri(): string
    {
        return (string) (config('keycloak.redirect_uri') ?: url('/api/auth/callback'));
    }

    private function endpoint(string $action): string
    {
        return rtrim((string) config('keycloak.base_url'), '/')
            .'/realms/'.config('keycloak.realm')
            .'/protocol/openid-connect/'.$action;
    }
}
