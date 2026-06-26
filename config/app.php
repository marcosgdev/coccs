<?php

return [
    'name' => env('APP_NAME', 'GestContratos'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim(env('APP_URL', 'http://localhost:8080'), '/'),
    'session_name' => env('SESSION_NAME', 'gestcontratos_session'),
    'upload_max_mb' => (int) env('UPLOAD_MAX_MB', 20),
    'timezone' => env('APP_TIMEZONE', 'America/Belem'),
];
