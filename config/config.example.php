<?php
// Copiar como config/config.php en el servidor (fuera de public_html) y completar.
// config/config.php está en .gitignore: nunca subir credenciales al repo.
return [
    'env' => getenv('APP_ENV') ?: 'development',
    'url' => getenv('APP_URL') ?: 'http://localhost:8000',

    'db' => [
        'dsn'  => getenv('DB_DSN') ?: '',
        'user' => getenv('DB_USER') ?: '',
        'pass' => getenv('DB_PASS') ?: '',
    ],

    'payments' => [
        'provider'     => 'mercadopago',
        'access_token' => getenv('MP_ACCESS_TOKEN') ?: '',
        'webhook_secret' => getenv('MP_WEBHOOK_SECRET') ?: '',
    ],

    'analytics' => [
        'ga4_id'        => getenv('GA4_ID') ?: '',
        'meta_pixel_id' => getenv('META_PIXEL_ID') ?: '',
    ],
];
