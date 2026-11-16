<?php

/*
|--------------------------------------------------------------------------
| CORS
|--------------------------------------------------------------------------
|
| Разрешённые origin задаются через FRONTEND_URL (можно несколько через
| запятую). Cookie-сессии не используются: авторизация — Bearer-токен
| Sanctum, поэтому supports_credentials выключен.
|
*/

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('FRONTEND_URL', 'http://localhost:5173')),
)));

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => false,
];
