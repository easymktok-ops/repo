<?php
declare(strict_types=1);

// Crea o actualiza las tablas (es seguro correrlo varias veces). Uso: php tools/migrate.php
define('PUBLIC_DIR', dirname(__DIR__) . '/public');
require dirname(__DIR__) . '/app/bootstrap.php';

$driver = Db::driver();
$file = dirname(__DIR__) . "/database/schema.$driver.sql";
if (!is_file($file)) {
    fwrite(STDERR, "No hay esquema para el motor '$driver'.\n");
    exit(1);
}

$pdo = Db::pdo();
$sql = (string) file_get_contents($file);
$count = 0;
foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    $pdo->exec($statement);
    $count++;
}
echo "Base de datos lista ($driver): $count sentencias aplicadas.\n";
