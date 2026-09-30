<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_DIR', APP_ROOT . '/app');
// index.php lo define con su propio __DIR__: en Hostinger la carpeta pública es public_html.
if (!defined('PUBLIC_DIR')) {
    define('PUBLIC_DIR', APP_ROOT . '/public');
}
define('STORAGE_DIR', APP_ROOT . '/storage');

$configFile = APP_ROOT . '/config/config.php';
$config = is_file($configFile)
    ? require $configFile
    : require APP_ROOT . '/config/config.example.php';

define('APP_ENV', $config['env'] ?? 'production');
define('APP_URL', rtrim($config['url'] ?? '', '/'));

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

date_default_timezone_set('America/Bogota');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => APP_ENV === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('jcd_session');
    session_start();
}

require APP_DIR . '/helpers.php';
require APP_DIR . '/content.php';
require APP_DIR . '/seo.php';

$GLOBALS['app_config'] = $config;
