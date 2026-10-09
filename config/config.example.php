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
        // Referencia para cobrar en COP lo que se publica en USD (reservas de Cartagena). PROVISIONAL: definir con la clienta.
        'usd_cop_rate'          => 4000,
    ],

    'mail' => [
        // 'log' guarda los correos en storage/mail/ (pruebas); 'smtp' los envía de verdad.
        'driver'     => getenv('MAIL_DRIVER') ?: 'log',
        // Hostinger: buzón creado en hPanel > Correos. Confirmar servidor y puerto en esa misma pantalla.
        'host'       => getenv('MAIL_HOST') ?: 'smtp.hostinger.com',
        'port'       => (int) (getenv('MAIL_PORT') ?: 465),
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'ssl', // ssl (465) o tls (587)
        'user'       => getenv('MAIL_USER') ?: '',
        'pass'       => getenv('MAIL_PASS') ?: '',
        'from_email' => getenv('MAIL_FROM') ?: '',
        'from_name'  => 'Joyas Colombianas® Dinner & Show',
        'reply_to'   => getenv('MAIL_REPLY_TO') ?: '',
        // Copia oculta de cada confirmación para el equipo (opcional, separados por coma).
        'copy_to'    => getenv('MAIL_COPY_TO') ?: '',
    ],

    'admin' => [
        'user'          => getenv('ADMIN_USER') ?: 'admin',
        // Generar con: php -r "echo password_hash('tu-clave', PASSWORD_DEFAULT);"
        // Sin hash, el panel solo funciona fuera de producción (clave de práctica: admin-dev).
        'password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: '',
    ],

    'analytics' => [
        'ga4_id'        => getenv('GA4_ID') ?: '',
        'meta_pixel_id' => getenv('META_PIXEL_ID') ?: '',
    ],
];
