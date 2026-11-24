<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://127.0.0.1:5000,http://localhost:5000,http://127.0.0.1:5180,http://localhost:5180,http://127.0.0.1:5181,http://localhost:5181')),
    ))),
    'allowed_origins_patterns' => [
        '#^https?://(?:localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$#i',
    ],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'Origin', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 600,
    'supports_credentials' => false,
];
