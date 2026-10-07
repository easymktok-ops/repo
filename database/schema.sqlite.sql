CREATE TABLE IF NOT EXISTS counters (
  name  TEXT PRIMARY KEY,
  value INTEGER NOT NULL
);

INSERT OR IGNORE INTO counters (name, value) VALUES ('ticket', 1000);

CREATE TABLE IF NOT EXISTS orders (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  public_id      TEXT NOT NULL UNIQUE,
  status         TEXT NOT NULL DEFAULT 'created',
  event_id       TEXT NOT NULL,
  function_date  TEXT NOT NULL,
  buyer_name     TEXT NOT NULL,
  doc_type       TEXT NOT NULL,
  doc_number     TEXT NOT NULL,
  email          TEXT NOT NULL,
  phone          TEXT NOT NULL,
  city           TEXT NOT NULL DEFAULT '',
  total_amount   INTEGER NOT NULL,
  currency       TEXT NOT NULL DEFAULT 'COP',
  gateway        TEXT NOT NULL,
  gateway_ref    TEXT NULL,
  payment_id     TEXT NULL,
  payment_method TEXT NULL,
  fee_amount     INTEGER NULL,
  net_amount     INTEGER NULL,
  created_at     TEXT NOT NULL,
  paid_at        TEXT NULL,
  updated_at     TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders (status);
CREATE INDEX IF NOT EXISTS idx_orders_function ON orders (event_id, function_date);

CREATE TABLE IF NOT EXISTS order_items (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id   INTEGER NOT NULL REFERENCES orders (id),
  sku        TEXT NOT NULL,
  label      TEXT NOT NULL,
  unit_price INTEGER NOT NULL,
  qty        INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_items_order ON order_items (order_id);

CREATE TABLE IF NOT EXISTS tickets (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  number     INTEGER NOT NULL UNIQUE,
  order_id   INTEGER NOT NULL UNIQUE REFERENCES orders (id),
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS webhook_events (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  provider     TEXT NOT NULL,
  event_id     TEXT NOT NULL,
  payload      TEXT NOT NULL,
  received_at  TEXT NOT NULL,
  processed_at TEXT NULL,
  result       TEXT NULL,
  UNIQUE (provider, event_id)
);
