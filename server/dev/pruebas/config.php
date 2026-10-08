<?php
/**
 * Configuracion del ENTORNO DE PRUEBAS (pruebas.aerodiverti.com.mx).
 * Solo hay que cambiar las 3 lineas marcadas con  <<< CAMBIA AQUI.
 * Stripe va en MODO PRUEBA: aqui nunca se cobra dinero real.
 */
$c = require __DIR__ . '/config.example.php';

$c['site_url'] = 'https://pruebas.aerodiverti.com.mx';

// Llave de Stripe en MODO PRUEBA (empieza con rk_test_ o sk_test_).
$c['stripe_secret_key'] = 'PEGA_AQUI_LA_LLAVE_DE_PRUEBA';                // <<< CAMBIA AQUI

// Secreto del webhook de PRUEBA (empieza con whsec_).
$c['stripe_webhook_secret'] = 'PEGA_AQUI_EL_WHSEC_DEL_WEBHOOK_DE_PRUEBA'; // <<< CAMBIA AQUI

// Contrasena del panel de pruebas (/api/panel.php, usuario "aerodiverti").
$c['panel']['user'] = 'aerodiverti';
$c['panel']['password'] = 'PON_AQUI_UNA_CONTRASENA';                     // <<< CAMBIA AQUI

// Rutas propias del entorno de pruebas (no toca la base de produccion).
$c['catalog_path'] = dirname(__DIR__) . '/data/catalog.json';
$c['db']['path'] = __DIR__ . '/data/pruebas.sqlite';

// Precios por fecha ENCENDIDOS en pruebas.
$c['pricing'] = ['rules_enabled' => true, 'max_advance_days' => 365, 'cache_seconds' => 0];

// Sin correos ni WhatsApp reales desde pruebas.
$c['notifications']['email_provider'] = 'log';
$c['notifications']['admin_email'] = '';
$c['notifications']['whatsapp_provider'] = 'log';
$c['notifications']['admin_whatsapp'] = '';
$c['deploy_token'] = '';
$c['oauth'] = ['github_client_id' => '', 'github_client_secret' => ''];

return $c;
