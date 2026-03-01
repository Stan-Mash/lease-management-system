<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Controls which cross-origin requests the API accepts.
    |
    | allowed_origins:      Only the production domain and localhost for dev.
    | allowed_methods:      Explicit allowlist — no wildcards.
    | allowed_headers:      Standard request headers + Authorization for API tokens.
    | exposed_headers:      Headers the browser is allowed to read from responses.
    | max_age:              How long (seconds) the browser caches preflight results.
    | supports_credentials: Must be false when allowed_origins contains '*'.
    |                       Set to true only if you need cookie-based auth cross-origin.
    |
    | SECURITY: Never use '*' for allowed_origins in production.
    | Paths follow the same pattern as Laravel's default (api/* covers all API routes).
    |
    */

    'paths' => ['api/*', 'api/v1/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter([
        env('APP_URL', 'http://127.0.0.1:8000'),
        env('CORS_ALLOWED_ORIGIN'), // Optional extra origin (e.g., a mobile app domain)
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'X-CSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
