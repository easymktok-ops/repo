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
