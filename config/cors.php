<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| Tuned for a mobile-first API. The mobile client (Expo / React Native) is
| NOT actually subject to CORS — native fetch isn't browser-policed — but
| our auxiliary web tooling (Swagger UI, an eventual admin dashboard) IS,
| and Expo Dev Tools talks to the API through a webview during development.
|
| `allowed_origins_patterns` covers the dynamic LAN IPs a phone uses to
| reach a dev machine (e.g. 192.168.x.x:8000). Tighten in production to
| explicit origins only.
|
*/

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))) ?: [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:8000',
        'http://localhost:8081',   // expo dev
        'http://127.0.0.1',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8081',
    ],

    'allowed_origins_patterns' => [
        // Any LAN IP for mobile devices talking to a dev machine.
        '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
        '#^https?://(10|192\.168|172\.(1[6-9]|2[0-9]|3[0-1]))\.\d+\.\d+(:\d+)?$#',
        // Expo tunnels / exp.host
        '#^https?://[a-z0-9-]+\.exp\.direct$#',
        '#^https?://[a-z0-9-]+\.tunnel\.exp\.direct$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 0,

    'supports_credentials' => false,
];
