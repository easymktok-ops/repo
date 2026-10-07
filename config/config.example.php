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
        // 'simulated' solo funciona fuera de producción; en producción usar 'mercadopago'.
        'provider'         => getenv('PAYMENT_PROVIDER') ?: 'simulated',
        'access_token'     => getenv('MP_ACCESS_TOKEN') ?: '',
        'webhook_secret'   => getenv('MP_WEBHOOK_SECRET') ?: '',
        'simulated_secret' => getenv('SIM_WEBHOOK_SECRET') ?: 'dev-secret-no-usar-en-produccion',
    ],

    'checkout' => [
        'max_tickets_per_order' => 10,
        'dates_offered'         => 8,
    ],

    'analytics' => [
        'ga4_id'        => getenv('GA4_ID') ?: '',
        'meta_pixel_id' => getenv('META_PIXEL_ID') ?: '',
    ],
];
