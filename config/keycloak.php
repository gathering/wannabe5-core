<?php

return [
    'realm_public_key' => env('KEYCLOAK_REALM_PUBLIC_KEY', null),
    'token_encryption_algorithm' => env('KEYCLOAK_TOKEN_ENCRYPTION_ALGORITHM', 'RS256'),
    'load_user_from_database' => true,
    'user_provider_custom_retrieve_method' => 'custom_retrieve',
    'user_provider_credential' => 'id',
    'token_principal_attribute' => 'sub',
    'append_decoded_token' => false,
    'allowed_resources' => null,
    'ignore_resources_validation' => true,
    'leeway' => env('KEYCLOAK_LEEWAY', 0),
    'input_key' => null,

    // OIDC / Keycloak Web flow settings used by native auth service endpoints.
    'base_url' => env('KEYCLOAK_BASE_URL'),
    'realm' => env('KEYCLOAK_REALM', 'wannabe'),
    'client_id' => env('KEYCLOAK_CLIENT_ID'),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
    'redirect_uri' => env('KEYCLOAK_REDIRECT_URI'),
    'post_logout_redirect_uri' => env('KEYCLOAK_POST_LOGOUT_REDIRECT_URI'),
];
