<?php
/**
 * Conexion a la base de datos. Por defecto SQLite (un archivo, sin servidor de
 * BD: ideal para hosting compartido con limite de bases MySQL). Soporta MySQL
 * como alternativa si el hosting no trae SQLite.
 *
 * Con SQLite, el esquema se crea SOLO la primera vez (ensure_schema): no hace
 * falta crear base, usuario ni correr SQL a mano.
 */

declare(strict_types=1);

require_once __DIR__ . '/schema.php';

function db(array $config): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $db = $config['db'];
    $driver = $db['driver'] ?? 'sqlite';

    if ($driver === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $db['host'] ?? 'localhost',
            $db['name'] ?? '',
            $db['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $db['user'] ?? '', $db['pass'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    }

    // --- SQLite (por defecto) ---
    $path = $db['path'] ?? (__DIR__ . '/../data/aerodiverti.sqlite');
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Durabilidad + espera si hay bloqueo momentaneo (webhook + cron).
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    ensure_schema($pdo);
    return $pdo;
}
