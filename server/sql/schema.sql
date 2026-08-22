-- Aerodiverti - esquema de reservas (Fase 3). MySQL 5.7+/8.
-- Corre esto una vez en la BD que crees en el panel de Webempresa
-- (PhpMyAdmin -> Importar, o pegando el SQL).

SET NAMES utf8mb4;

-- --------------------------------------------------------------------------
-- Reservas. Se crea una fila 'pending' al iniciar el checkout y pasa a
-- 'paid' cuando el webhook de Stripe confirma el pago. amount_now = lo cobrado
-- ahora (total o anticipo); balance = saldo a liquidar en sitio.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference         VARCHAR(24)  NOT NULL,           -- ej. AERO-7F3K9Q
    status            ENUM('pending','paid','expired','cancelled') NOT NULL DEFAULT 'pending',
    locale            CHAR(2)      NOT NULL DEFAULT 'es',

    package_slug      VARCHAR(80)  NOT NULL,
    package_title     VARCHAR(160) NOT NULL,
    passengers        SMALLINT UNSIGNED NOT NULL,
    flight_date       DATE         NULL,
    mode              ENUM('full','deposit') NOT NULL,

    currency          CHAR(3)      NOT NULL DEFAULT 'MXN',
    price_per_person  INT UNSIGNED NOT NULL,           -- pesos
    amount_now_cents  INT UNSIGNED NOT NULL,           -- centavos cobrados ahora
    total_full_cents  INT UNSIGNED NOT NULL,           -- centavos del total
    balance_cents     INT UNSIGNED NOT NULL,           -- centavos por pagar en sitio

    customer_name     VARCHAR(120) NOT NULL,
    customer_email    VARCHAR(160) NOT NULL,
    customer_phone    VARCHAR(40)  NOT NULL,
    notes             VARCHAR(500) NULL,

    stripe_session_id VARCHAR(120) NULL,
    stripe_payment_intent VARCHAR(120) NULL,

    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at           DATETIME     NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uniq_reference (reference),
    UNIQUE KEY uniq_stripe_session (stripe_session_id),
    KEY idx_status (status),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Outbox de notificaciones. Una fila por notificacion (cliente + admin, email
-- y/o whatsapp). El Cron la procesa con reintentos. El estado SIEMPRE queda
-- trazado: pending -> sent | failed, con attempts y last_error.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications_outbox (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id      BIGINT UNSIGNED NOT NULL,
    channel         ENUM('email','whatsapp') NOT NULL,
    kind            VARCHAR(40)  NOT NULL,             -- booking_confirmation | booking_admin_alert
    recipient       VARCHAR(160) NOT NULL,
    status          ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    attempts        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    provider        VARCHAR(40)  NULL,
    last_error      VARCHAR(500) NULL,
    next_attempt_at DATETIME     NULL,
    sent_at         DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL,

    PRIMARY KEY (id),
    KEY idx_pending (status, next_attempt_at),
    KEY idx_booking (booking_id),
    CONSTRAINT fk_outbox_booking FOREIGN KEY (booking_id)
        REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
