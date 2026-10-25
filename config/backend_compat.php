<?php

return [
    'timeout_seconds' => (int) env('BACKEND_COMPAT_TIMEOUT_SECONDS', 20),
    'wannabe_jwt_ttl_minutes' => (int) env('WANNABE_JWT_TTL_MINUTES', 15),
    // RS256 keypair used for wannabe_jwt (must match frontend AUTH_PUBLIC_KEY).
    'wannabe_jwt_private_key' => env('AUTH_USER_PRIVATE_KEY'),
    'wannabe_jwt_public_key' => env('AUTH_USER_PUBLIC_KEY'),
    'allowed_redirect_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('AUTH_ALLOWED_REDIRECT_HOSTS', 'localhost,wannabe5.gathering.org,127.0.0.1'))
    ))),

    // Service URLs should point to API roots without trailing slash.
    'services' => [
        'access' => env('BACKEND_COMPAT_ACCESS_URL'),
        'application' => env('BACKEND_COMPAT_APPLICATION_URL'),
        'auth' => env('BACKEND_COMPAT_AUTH_URL'),
        'communication' => env('BACKEND_COMPAT_COMMUNICATION_URL'),
        'crew' => env('BACKEND_COMPAT_CREW_URL'),
        'profile' => env('BACKEND_COMPAT_PROFILE_URL'),
        'tasking' => env('BACKEND_COMPAT_TASKING_URL'),
    ],
];
