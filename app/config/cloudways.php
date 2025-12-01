<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudways Hosting Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings specifically optimized for
    | Cloudways hosting environment to resolve common session and 
    | authentication issues.
    |
    */

    'session_fixes' => [
        'use_database_sessions' => env('CLOUDWAYS_USE_DATABASE_SESSIONS', false),
        'force_secure_cookies' => env('CLOUDWAYS_FORCE_SECURE_COOKIES', true),
        'extend_session_lifetime' => env('CLOUDWAYS_EXTEND_SESSION', true),
    ],

    'server_timezone' => env('CLOUDWAYS_TIMEZONE', 'UTC'),

    'cache_prefix' => env('CLOUDWAYS_CACHE_PREFIX', 'cloudways_'),

    'trusted_proxies' => env('CLOUDWAYS_TRUSTED_PROXIES', '*'),

    'force_https' => env('CLOUDWAYS_FORCE_HTTPS', true),
];
