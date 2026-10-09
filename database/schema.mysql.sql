CREATE TABLE IF NOT EXISTS counters (
  name  VARCHAR(32) NOT NULL PRIMARY KEY,
  value BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO counters (name, value) VALUES ('ticket', 1000);
INSERT IGNORE INTO counters (name, value) VALUES ('reservation', 5000);

CREATE TABLE IF NOT EXISTS orders (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  public_id      VARCHAR(48)  NOT NULL,
  status         VARCHAR(16)  NOT NULL DEFAULT 'created',
  event_id       VARCHAR(32)  NOT NULL,
  function_date  DATE         NOT NULL,
  kind           VARCHAR(16)  NOT NULL DEFAULT 'ticket',
  buyer_name     VARCHAR(160) NOT NULL,
  first_name     VARCHAR(80)  NOT NULL DEFAULT '',
  last_name      VARCHAR(80)  NOT NULL DEFAULT '',
  doc_type       VARCHAR(8)   NOT NULL,
  doc_number     VARCHAR(32)  NOT NULL,
  email          VARCHAR(160) NOT NULL,
  phone          VARCHAR(32)  NOT NULL,
  city           VARCHAR(80)  NOT NULL DEFAULT '',
  total_amount   INT          NOT NULL,
  currency       CHAR(3)      NOT NULL DEFAULT 'COP',
  usd_total      INT          NULL,
  fx_rate        INT          NULL,
  gateway        VARCHAR(24)  NOT NULL,
  gateway_ref    VARCHAR(96)  NULL,
  payment_id     VARCHAR(96)  NULL,
  payment_method VARCHAR(48)  NULL,
  fee_amount     INT          NULL,
  net_amount     INT          NULL,
  created_at     DATETIME     NOT NULL,
  paid_at        DATETIME     NULL,
  updated_at     DATETIME     NOT NULL,
  UNIQUE KEY uq_orders_public (public_id),
  KEY idx_orders_status (status),
  KEY idx_orders_function (event_id, function_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id   INT UNSIGNED NOT NULL,
  sku        VARCHAR(24)  NOT NULL,
  label      VARCHAR(80)  NOT NULL,
  unit_price INT          NOT NULL,
  qty        INT          NOT NULL,
  KEY idx_items_order (order_id),
  CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tickets (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  number     INT UNSIGNED NOT NULL,
  order_id   INT UNSIGNED NOT NULL,
  created_at DATETIME     NOT NULL,
  UNIQUE KEY uq_tickets_number (number),
  UNIQUE KEY uq_tickets_order (order_id),
  CONSTRAINT fk_tickets_order FOREIGN KEY (order_id) REFERENCES orders (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS webhook_events (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  provider     VARCHAR(24)  NOT NULL,
  event_id     VARCHAR(128) NOT NULL,
  payload      MEDIUMTEXT   NOT NULL,
  received_at  DATETIME     NOT NULL,
  processed_at DATETIME     NULL,
  result       VARCHAR(64)  NULL,
  UNIQUE KEY uq_webhook_event (provider, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
