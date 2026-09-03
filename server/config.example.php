<?php
/**
 * Aerodiverti - configuracion del backend de reservas (Fase 3).
 * -----------------------------------------------------------------------------
 * COPIA este archivo a `config.php` (mismo directorio) y rellena los valores.
 * `config.php` NUNCA se sube al repo (esta en .gitignore). Aqui no hay secretos.
 *
 * DOMINIO-AGNOSTICO: `site_url` sale de una sola variable. Cambiar de staging a
 * produccion = editar este valor, sin tocar codigo.
 *
 * En Webempresa puedes definir estos valores como variables de entorno del
 * hosting y leerlas con getenv(), o pegarlas directo aqui (el archivo vive
 * fuera de la web root recomendada). Preferir getenv() para las llaves.
 */

return [
    // --- Sitio -------------------------------------------------------------
    // URL base absoluta del sitio, SIN barra final. Se usa para success_url /
    // cancel_url de Stripe. Dominio-agnostico: aqui va el dominio del cliente.
    'site_url' => getenv('PUBLIC_SITE_URL') ?: 'https://tu-dominio.example',

    // --- Stripe ------------------------------------------------------------
    // Llaves de la cuenta "Aerodiverti Activa". Empezar en modo PRUEBA
    // (sk_test_/whsec_ de prueba). Para produccion cambiar a sk_live_ y al
    // whsec del endpoint en vivo. NUNCA hardcodear la secreta en cliente.
    'stripe_secret_key'     => getenv('STRIPE_SECRET_KEY') ?: 'sk_test_xxx',
    'stripe_webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_xxx',

    // --- Autoridad de precios ---------------------------------------------
    // Ruta ABSOLUTA en el filesystem al catalog.json que genera el build de
    // Astro (/data/catalog.json dentro de la web root). Ej. en Webempresa:
    // /home/USUARIO/www/tu-dominio/data/catalog.json
    'catalog_path' => __DIR__ . '/data/catalog.json',

    // Anticipo por pasajero (MXN) para el modo "apartar". Debe coincidir con
    // src/config/booking.ts. Fuente de verdad de negocio.
    'deposit_per_passenger' => 1000,

    // --- Base de datos -----------------------------------------------------
    // Por defecto SQLite: un archivo, sin servidor MySQL ni credenciales. El
    // esquema se crea SOLO en el primer arranque. Ideal cuando el plan tiene
    // limite de bases MySQL. El archivo vive fuera de la web (carpeta data,
    // protegida por .htaccess).
    'db' => [
        'driver' => 'sqlite',
        'path'   => __DIR__ . '/data/aerodiverti.sqlite',

        // Alternativa MySQL (solo si el hosting NO trae SQLite). Cambia
        // 'driver' a 'mysql' y rellena estos campos; luego corre
        // server/sql/schema.sql en esa base.
        // 'host'    => 'localhost',
        // 'name'    => '',
        // 'user'    => '',
        // 'pass'    => '',
        // 'charset' => 'utf8mb4',
    ],

    // --- Notificaciones ----------------------------------------------------
    // provider "log" = no envia, solo deja traza en la BD (default seguro,
    // anti bug "sin correo enviado"). "mail" usa la funcion mail() de PHP.
    // "smtp" reservado para Fase 3.1 si se configura un SMTP real.
    'notifications' => [
        'email_provider' => getenv('NOTIF_EMAIL_PROVIDER') ?: 'mail', // log | mail | smtp
        'email_from'     => getenv('NOTIF_EMAIL_FROM') ?: 'reservas@tu-dominio.example',
        'email_from_name' => 'Aerodiverti',
        // A donde llega la alerta interna de nueva reserva.
        'admin_email'    => getenv('NOTIF_ADMIN_EMAIL') ?: 'ventas@aerodiverti.com',

        // WhatsApp: "log" hasta cablear Meta Cloud API en Fase 3.1.
        'whatsapp_provider' => getenv('NOTIF_WA_PROVIDER') ?: 'log', // log | meta_cloud
        'whatsapp_token'    => getenv('NOTIF_WA_TOKEN') ?: '',
        'whatsapp_phone_id' => getenv('NOTIF_WA_PHONE_ID') ?: '',
        'admin_whatsapp'    => getenv('NOTIF_ADMIN_WHATSAPP') ?: '',
    ],

    // --- Reintentos del outbox --------------------------------------------
    'outbox' => [
        'max_attempts'   => 5,
        'base_delay_sec' => 60, // backoff: 60s, 120s, 240s... con tope
        'max_delay_sec'  => 3600,
    ],

    // --- Panel de ventas (server/public/api/panel.php) --------------------
    // Pantalla protegida para que el negocio vea y gestione las reservas.
    // La contrasena vive SOLO en el servidor (este archivo, fuera de la web).
    'panel' => [
        'enabled' => true,
        'user'    => getenv('PANEL_USER') ?: 'aerodiverti',
        // CAMBIA esto por una contrasena tuya (texto). Si prefieres, deja
        // 'password' vacio y pon un hash bcrypt en 'password_hash'
        // (generalo con: php -r "echo password_hash('tu-clave', PASSWORD_DEFAULT);").
        'password'      => getenv('PANEL_PASSWORD') ?: 'cambia-esta-clave',
        'password_hash' => getenv('PANEL_PASSWORD_HASH') ?: '',
    ],

    // --- OAuth para el panel de contenido (/admin, Decap CMS) --------------
    // Login del CMS via GitHub, usando el proxy en server/public/api/oauth/.
    // Crea una GitHub OAuth App (Settings -> Developer settings -> OAuth Apps):
    //   - Authorization callback URL: https://TU-DOMINIO/api/oauth/callback.php
    // y pega aqui el Client ID y el Client Secret. Viven SOLO en el servidor.
    'oauth' => [
        // FASE 2: pon 'enabled' => true SOLO cuando el panel de contenido este
        // liberado (y restaura el index.html de Decap). Mientras este en false,
        // /admin muestra "en preparacion" y los endpoints OAuth quedan inertes.
        'enabled'              => false,
        'github_client_id'     => getenv('OAUTH_GITHUB_CLIENT_ID') ?: '',
        'github_client_secret' => getenv('OAUTH_GITHUB_CLIENT_SECRET') ?: '',
    ],

    // Orden permitido para el redirect de Stripe. Solo se aceptan slugs que
    // existan en catalog.json; esto es una capa extra de allowlist de origenes
    // para CORS si el front vive en otro host (por defecto: mismo origen).
    'allowed_origins' => [
        // getenv('PUBLIC_SITE_URL'),  // se agrega site_url automaticamente
    ],
];
