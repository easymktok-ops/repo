<?php
declare(strict_types=1);

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $cfg = $GLOBALS['app_config']['db'] ?? [];
        $dsn = (string) ($cfg['dsn'] ?? '');
        if ($dsn === '') {
            if (APP_ENV === 'production') {
                throw new RuntimeException('DB_DSN no está configurado.');
            }
            $dsn = 'sqlite:' . STORAGE_DIR . '/dev.sqlite';
        }

        $pdo = new PDO($dsn, ($cfg['user'] ?? '') ?: null, ($cfg['pass'] ?? '') ?: null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 15000');
            $pdo->exec('PRAGMA journal_mode = WAL');
        }

        return self::$pdo = $pdo;
    }

    public static function driver(): string
    {
        return self::pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public static function isMysql(): bool
    {
        return self::driver() === 'mysql';
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Ejecuta $fn dentro de una transacción. En SQLite se toma el bloqueo de escritura desde el
     * inicio (BEGIN IMMEDIATE) para que dos procesos no se pisen; en MySQL se usa InnoDB.
     */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn($pdo);
        }

        $sqlite = self::driver() === 'sqlite';
        $sqlite ? $pdo->exec('BEGIN IMMEDIATE') : $pdo->beginTransaction();

        try {
            $result = $fn($pdo);
            $sqlite ? $pdo->exec('COMMIT') : $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $sqlite ? $pdo->exec('ROLLBACK') : $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function run(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function isUniqueViolation(PDOException $e): bool
    {
        $code = (string) $e->getCode();
        return $code === '23000' || $code === '23505' || str_contains($e->getMessage(), 'UNIQUE');
    }
}
