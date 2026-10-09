<?php
// Para el subdominio de pruebas (staging). Copiar como config/config.php en el servidor y completar.
// Hostinger compartido no maneja variables de entorno cómodamente: aquí los valores van escritos.
// config/config.php está en .gitignore: nunca se sube al repositorio.
return [
    'env' => 'staging',                                   // fuera de producción: no se indexa y permite la pasarela simulada
    'url' => 'https://pruebas.TUDOMINIO.com',

    'db' => [
        'dsn'  => 'mysql:host=localhost;dbname=NOMBRE_BD;charset=utf8mb4',
        'user' => 'USUARIO_BD',
        'pass' => 'CLAVE_BD',
    ],

    'payments' => [
        'provider'         => 'simulated',                // pasa a 'mercadopago' cuando haya credenciales de prueba
        'access_token'     => '',
        'webhook_secret'   => '',
        'simulated_secret' => 'CAMBIAR-por-un-texto-largo-al-azar',
    ],

    'checkout' => [
        'max_tickets_per_order' => 10,
        'dates_offered'         => 8,
        'usd_cop_rate'          => 4000,                  // PROVISIONAL
    ],

    'mail' => [
        'driver'     => 'smtp',                           // 'log' para no enviar nada mientras se prueba
        'host'       => 'smtp.hostinger.com',             // confirmar en hPanel > Correos
        'port'       => 465,
        'encryption' => 'ssl',                            // 465 = ssl; 587 = tls
        'user'       => 'tickets@TUDOMINIO.com',
        'pass'       => 'CLAVE_DEL_BUZON',
        'from_email' => 'tickets@TUDOMINIO.com',
        'from_name'  => 'Joyas Colombianas® Dinner & Show',
        'reply_to'   => '',
        'copy_to'    => '',                               // copia oculta para el equipo, separados por coma
    ],

    'admin' => [
        'user'          => 'admin',
        'password_hash' => 'PEGAR_HASH',                  // php -r "echo password_hash('clave-larga', PASSWORD_DEFAULT);"
    ],

    // Instalador de un solo uso (sin SSH): abrir /instalar.php?token=ESTE_TEXTO. Borrar el archivo y vaciar este valor al terminar.
    'install_token' => 'CAMBIAR-por-un-texto-largo-al-azar',

    'analytics' => ['ga4_id' => '', 'meta_pixel_id' => ''],
];
