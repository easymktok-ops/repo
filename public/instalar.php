<?php
declare(strict_types=1);

// Instalador de un solo uso para hosting sin SSH: crea las tablas. Requiere 'install_token' en config/config.php.
// Cuando termine, BORRAR este archivo del servidor.
define('PUBLIC_DIR', __DIR__);
// app/ puede estar un nivel arriba de public/, o en la carpeta pruebas-app (hosting compartido).
$root = null;
foreach ([dirname(__DIR__), dirname(__DIR__) . '/pruebas-app', dirname(__DIR__, 2) . '/pruebas-app'] as $candidate) {
    if (is_file($candidate . '/app/bootstrap.php')) {
        $root = $candidate;
        break;
    }
}
if ($root === null) {
    http_response_code(500);
    exit('No se encontró la carpeta de la aplicación (pruebas-app).');
}
require $root . '/app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

$token = (string) ($GLOBALS['app_config']['install_token'] ?? '');
if ($token === '' || !hash_equals($token, (string) ($_GET['token'] ?? ''))) {
    http_response_code(404);
    exit("No disponible.\n");
}

try {
    $driver = Db::driver();
    $file = $root . "/database/schema.$driver.sql";
    $count = 0;
    foreach (array_filter(array_map('trim', explode(';', (string) file_get_contents($file)))) as $statement) {
        Db::pdo()->exec($statement);
        $count++;
    }
    echo "Base de datos lista ($driver): $count sentencias aplicadas.\n";
    echo 'Escritura en storage/: ' . (is_writable($root . '/storage') ? 'sí' : 'NO (dar permisos 775 a storage/)') . "\n";
    echo 'PHP ' . PHP_VERSION . ' | pdo_mysql: ' . (extension_loaded('pdo_mysql') ? 'sí' : 'NO') . ' | mbstring: ' . (extension_loaded('mbstring') ? 'sí' : 'NO') . "\n";
    echo "\nAhora BORRA public/instalar.php del servidor.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage() . "\n";
}
