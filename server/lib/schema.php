<?php
/**
 * Esquema para SQLite, autocreado en el primer arranque (idempotente).
 * Equivalente a server/sql/schema.sql (MySQL). Nombres de tabla unicos, sin
 * colision con nada existente.
 */

declare(strict_types=1);

function ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS bookings (
            id                    INTEGER PRIMARY KEY AUTOINCREMENT,
            reference             TEXT    NOT NULL UNIQUE,
            status                TEXT    NOT NULL DEFAULT 'pending',
            locale                TEXT    NOT NULL DEFAULT 'es',
            package_slug          TEXT    NOT NULL,
            package_title         TEXT    NOT NULL,
            passengers            INTEGER NOT NULL,
            flight_date           TEXT,
            mode                  TEXT    NOT NULL,
            currency              TEXT    NOT NULL DEFAULT 'MXN',
            price_per_person      INTEGER NOT NULL,
            amount_now_cents      INTEGER NOT NULL,
            total_full_cents      INTEGER NOT NULL,
            balance_cents         INTEGER NOT NULL,
            customer_name         TEXT    NOT NULL,
            customer_email        TEXT    NOT NULL,
            customer_phone        TEXT    NOT NULL,
            notes                 TEXT,
            stripe_session_id     TEXT,
            stripe_payment_intent TEXT,
            created_at            TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
            paid_at               TEXT
        )"
    );
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_bookings_session ON bookings (stripe_session_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings (status)");

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS notifications_outbox (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            booking_id      INTEGER NOT NULL,
            channel         TEXT    NOT NULL,
            kind            TEXT    NOT NULL,
            recipient       TEXT    NOT NULL,
            status          TEXT    NOT NULL DEFAULT 'pending',
            attempts        INTEGER NOT NULL DEFAULT 0,
            provider        TEXT,
            last_error      TEXT,
            next_attempt_at TEXT,
            sent_at         TEXT,
            created_at      TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
            updated_at      TEXT,
            FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
        )"
    );
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_outbox_pending ON notifications_outbox (status, next_attempt_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_outbox_booking ON notifications_outbox (booking_id)");

    // --- Columnas administrativas (panel de ventas) -----------------------
    // SQLite no tiene "ADD COLUMN IF NOT EXISTS": consultamos las columnas
    // actuales y agregamos solo las que falten. Idempotente y seguro en cada
    // arranque; no toca reservas existentes.
    ensure_admin_columns($pdo);
}

/**
 * Tablas de tarifas por fecha. NO se llama desde ensure_schema: solo desde el
 * panel, /api/prices.php y el checkout con la bandera encendida, para que con
 * pricing.rules_enabled=false nada nuevo se ejecute. Nunca lanza: si falla,
 * deja traza y devuelve false, y el llamador sigue con el flujo anterior.
 * En MySQL las tablas se crean con server/sql/schema.sql.
 */
function ensure_pricing_schema(PDO $pdo): bool
{
    try {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS pricing_rules (
                    id              TEXT PRIMARY KEY,
                    label           TEXT NOT NULL,
                    type            TEXT NOT NULL,
                    package_ids     TEXT NOT NULL,
                    start_date      TEXT NOT NULL,
                    end_date        TEXT NOT NULL,
                    weekdays        TEXT,
                    price_cents     INTEGER,
                    deposit_percent INTEGER,
                    active          INTEGER NOT NULL DEFAULT 1,
                    updated_at      TEXT NOT NULL,
                    updated_by      TEXT NOT NULL
                )"
            );
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rules_range ON pricing_rules (active, start_date, end_date)');
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS package_pricing (
                    package_slug            TEXT PRIMARY KEY,
                    default_deposit_percent INTEGER NOT NULL,
                    updated_at              TEXT NOT NULL,
                    updated_by              TEXT NOT NULL
                )"
            );
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS pricing_audit (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    at          TEXT NOT NULL,
                    user        TEXT NOT NULL,
                    action      TEXT NOT NULL,
                    entity      TEXT NOT NULL,
                    entity_id   TEXT NOT NULL,
                    before_json TEXT,
                    after_json  TEXT
                )"
            );
            $pdo->exec('CREATE TABLE IF NOT EXISTS pricing_meta (k TEXT PRIMARY KEY, v TEXT NOT NULL)');

            $have = [];
            foreach ($pdo->query('PRAGMA table_info(bookings)')->fetchAll() as $col) {
                $have[$col['name']] = true;
            }
            $wanted = [
                'unit_price_cents' => 'ALTER TABLE bookings ADD COLUMN unit_price_cents INTEGER',
                'deposit_percent'  => 'ALTER TABLE bookings ADD COLUMN deposit_percent INTEGER',
                'rule_id'          => 'ALTER TABLE bookings ADD COLUMN rule_id TEXT',
                'pricing_version'  => 'ALTER TABLE bookings ADD COLUMN pricing_version TEXT',
            ];
            foreach ($wanted as $name => $sql) {
                if (!isset($have[$name])) {
                    $pdo->exec($sql);
                }
            }
        }
        return true;
    } catch (Throwable $e) {
        log_line('pricing', 'no se pudo preparar el esquema de tarifas', ['msg' => $e->getMessage()]);
        return false;
    }
}

/** Agrega (si faltan) las columnas que usa el panel para gestionar reservas. */
function ensure_admin_columns(PDO $pdo): void
{
    $have = [];
    foreach ($pdo->query('PRAGMA table_info(bookings)')->fetchAll() as $col) {
        $have[$col['name']] = true;
    }
    $wanted = [
        'admin_notes'     => 'ALTER TABLE bookings ADD COLUMN admin_notes TEXT',
        'balance_paid_at' => 'ALTER TABLE bookings ADD COLUMN balance_paid_at TEXT',
        'completed_at'    => 'ALTER TABLE bookings ADD COLUMN completed_at TEXT',
        'cancelled_at'    => 'ALTER TABLE bookings ADD COLUMN cancelled_at TEXT',
        'updated_at'      => 'ALTER TABLE bookings ADD COLUMN updated_at TEXT',
    ];
    foreach ($wanted as $name => $sql) {
        if (!isset($have[$name])) {
            $pdo->exec($sql);
        }
    }
}
