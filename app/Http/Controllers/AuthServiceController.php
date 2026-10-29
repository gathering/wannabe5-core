<?php

namespace App\Http\Controllers;

use App\Domain\Auth\Services\KeycloakOidcClient;
use App\Domain\Profile\Jobs\HandleProfileSyncFromAuth;
use App\Domain\Shared\Support\TrustedRedirect;
use App\Domain\Shared\Support\WannabeJwtCookie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthServiceController extends Controller
{
    public function __construct(
        protected KeycloakOidcClient $oidc,
    ) {}

    public function login(Request $request)
    {
        $redirect = TrustedRedirect::sanitize($request->query('redirect_url'), '/');
        session()->put('authflow.intended', $redirect);
        session()->put('authflow.intended-source', 'login');

        $state = $this->oidc->newState();
        session()->put('authflow.oidc_state', $state);

        if ($this->shouldRedirectToOidc($request) && $this->oidc->isConfigured()) {

            return redirect()->away($this->oidc->authorizationUrl($state));
        }

        return response()->json([
            'redirect_to' => $this->oidc->isConfigured()
                ? $this->oidc->authorizationUrl($state)
                : $redirect,
            'status' => 'login_started',
            'oidc_configured' => $this->oidc->isConfigured(),
        ]);
    }

    public function register(Request $request)
    {
        $redirect = TrustedRedirect::sanitize($request->query('redirect_url'), '/');
        session()->put('authflow.intended', $redirect);
        session()->put('authflow.intended-source', 'register');

        $state = $this->oidc->newState();
        session()->put('authflow.oidc_state', $state);

        if ($this->shouldRedirectToOidc($request) && $this->oidc->isConfigured()) {

            return redirect()->away($this->oidc->registrationUrl($state));
        }

        return response()->json([
            'redirect_to' => $this->oidc->isConfigured()
                ? $this->oidc->registrationUrl($state)
                : $redirect,
            'status' => 'register_started',
            'oidc_configured' => $this->oidc->isConfigured(),
        ]);
    }

    public function callback(Request $request)
    {
        $state = (string) $request->query('state', '');
        $expected = (string) session()->pull('authflow.oidc_state', '');
        if ($state === '' || $expected === '' || ! hash_equals($expected, $state)) {
            return response()->json(['error' => 'Invalid OIDC state'], 401);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return response()->json(['error' => 'Missing authorization code'], 400);
        }

        try {
            $tokens = $this->oidc->exchangeAuthorizationCode($code);
            $accessClaims = $this->decodeJwtPayload($tokens['access_token'] ?? '');
            $idClaims = $this->decodeJwtPayload($tokens['id_token'] ?? '');
            $claims = array_merge($accessClaims, array_filter(
                $idClaims,
                static fn ($value) => $value !== null && $value !== ''
            ));
            $userId = (string) ($claims['sub'] ?? '');
            if ($userId === '') {
                return response()->json(['error' => 'Token missing subject'], 401);
            }

            $username = (string) (
                $claims['email']
                ?? $claims['preferred_username']
                ?? $claims['username']
                ?? $userId
            );

            $user = User::query()->firstOrCreate(
                ['id' => $userId],
                [
                    'username' => $username,
                    'type' => 'user',
                ]
            );

            session()->regenerate();
            session()->put('authflow.user_id', $user->id);
            session()->put('authflow.tokens', [
                'access_token' => $tokens['access_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'id_token' => $tokens['id_token'] ?? null,
            ]);

            $intended = TrustedRedirect::sanitize(
                session()->pull('authflow.intended'),
                '/welcome'
            );

            $response = redirect()->away($intended)->withCookie(WannabeJwtCookie::make($user, [
                'email' => $user->username,
            ]));
        } catch (Throwable $exception) {
            Log::warning('OIDC callback failed', ['error' => $exception->getMessage()]);

            return response()->json(['error' => 'OIDC callback failed'], 401);
        }

        // Side effects must not fail an otherwise successful login.
        try {
            HandleProfileSyncFromAuth::dispatch('after-login', $request->query());
        } catch (Throwable $exception) {
            Log::warning('OIDC after-login profile sync dispatch failed', [
                'error' => $exception->getMessage(),
            ]);
        }

        return $response;
    }

    public function afterLogout()
    {
        session()->forget(['authflow.tokens', 'authflow.user_id', 'authflow.intended', 'authflow.intended-source']);

        return response()->json(['status' => 'logged_out'])->withCookie(WannabeJwtCookie::forget());
    }

    public function refreshSession(Request $request)
    {
        $user = $request->user();
        if ($user === null) {
            return response('unauthenticated', 401)->withCookie(WannabeJwtCookie::forget());
        }

        $tokens = session('authflow.tokens', []);
        if (! $this->oidc->isConfigured() || ! filled($tokens['refresh_token'] ?? null)
            || session('authflow.user_id') !== $user->id) {
            session()->forget(['authflow.tokens', 'authflow.user_id']);

            return response('unauthenticated', 401)->withCookie(WannabeJwtCookie::forget());
        }
        $result = 'valid';
        if ($this->oidc->isConfigured() && filled($tokens['refresh_token'] ?? null)) {
            try {
                $refreshed = $this->oidc->refreshAccessToken((string) $tokens['refresh_token']);
                session()->put('authflow.tokens', [
                    'access_token' => $refreshed['access_token'] ?? null,
                    'refresh_token' => $refreshed['refresh_token'] ?? $tokens['refresh_token'],
                    'id_token' => $refreshed['id_token'] ?? ($tokens['id_token'] ?? null),
                ]);
                $result = 'renewed';
            } catch (Throwable $exception) {
                session()->forget(['authflow.tokens', 'authflow.user_id']);
                Log::info('OIDC refresh failed', ['error' => $exception->getMessage()]);

                return response('unauthenticated', 401)->withCookie(WannabeJwtCookie::forget());
            }
        }

        HandleProfileSyncFromAuth::dispatch('refresh-session', $request->query());

        return response($result)->withCookie(WannabeJwtCookie::make($user, [
            'uncached' => $request->boolean('uncached'),
        ]));
    }

    public function logout(Request $request)
    {
        $redirect = TrustedRedirect::sanitize($request->query('redirect_url'), '/');
        session()->put('authflow.intended', $redirect);
        session()->put('authflow.intended-source', 'logout');

        if ($this->shouldRedirectToOidc($request) && $this->oidc->isConfigured()) {
            $idToken = session('authflow.tokens.id_token');

            return redirect()->away($this->oidc->logoutUrl(is_string($idToken) ? $idToken : null));
        }

        return response()->json([
            'status' => 'logout_started',
            'redirect_to' => $this->oidc->isConfigured()
                ? $this->oidc->logoutUrl()
                : url('/api/auth/after-logout'),
            'oidc_configured' => $this->oidc->isConfigured(),
        ]);
    }

    public function afterLogin(Request $request)
    {
        $response = $this->refreshSession($request);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }
        $response->setContent(json_encode(['status' => 'login_completed'], JSON_THROW_ON_ERROR));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        if ($this->shouldRedirectToOidc($request) && $this->oidc->isConfigured()) {
            return redirect()->away($this->oidc->accountUrl());
        }

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'account_url' => $this->oidc->isConfigured() ? $this->oidc->accountUrl() : null,
        ]);
    }

    public function health()
    {
        return response()->json([
            'ok' => true,
            'oidc_configured' => $this->oidc->isConfigured(),
        ]);
    }

    public function serviceLogin(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['nullable', 'string'],
            'client_secret' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
        ]);

        $scope = $validated['scope'] ?? 'wannabe-service-user';
        $allowedScopes = ['wannabe-service-user', 'external-wannabe-service-user'];
        $scopeParts = preg_split('/\s+/', trim($scope)) ?: [];
        if (count(array_intersect($scopeParts, $allowedScopes)) === 0) {
            return response()->json([
                'error' => 'Invalid login client, missing required scope',
            ], 401);
        }

        if ($this->oidc->isConfigured()
            && filled($validated['client_id'] ?? null)
            && filled($validated['client_secret'] ?? null)
            && filled(config('keycloak.base_url'))
        ) {
            try {
                $tokenUrl = rtrim((string) config('keycloak.base_url'), '/')
                    .'/realms/'.config('keycloak.realm')
                    .'/protocol/openid-connect/token';
                $upstream = Http::asForm()->post($tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $validated['client_id'],
                    'client_secret' => $validated['client_secret'],
                    'scope' => $scope,
                ]);
                if ($upstream->successful()) {
                    return response()->json($upstream->json());
                }
            } catch (Throwable $exception) {
                Log::warning('Service login upstream failed; falling back to local token', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $subject = isset($validated['client_id']) && $validated['client_id'] !== ''
            ? 'service:'.$validated['client_id']
            : 'service:token';
        if ($request->bearerToken() !== null) {
            $subject = 'service:bearer';
        }

        return response()->json([
            'access_token' => rtrim(strtr(base64_encode(json_encode([
                'sub' => $subject,
                'scope' => $scope,
                'iat' => now()->timestamp,
                'exp' => now()->addMinutes((int) config('backend_compat.wannabe_jwt_ttl_minutes', 15))->timestamp,
            ], JSON_THROW_ON_ERROR)), '+/', '-_'), '='),
            'token_type' => 'bearer',
            'expires_in' => (int) config('backend_compat.wannabe_jwt_ttl_minutes', 15) * 60,
        ]);
    }

    public function liveness()
    {
        return response('ok', 200);
    }

    private function shouldRedirectToOidc(Request $request): bool
    {
        if ($request->boolean('format_json') || $request->query('format') === 'json') {
            return false;
        }

        $accept = strtolower((string) $request->header('Accept', ''));

        return ! str_contains($accept, 'application/json');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            return [];
        }

        $payload = strtr($parts[1], '-_', '+/');
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return [];
        }

        $json = json_decode($decoded, true);

        return is_array($json) ? $json : [];
    }
}
