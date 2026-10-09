<?php
declare(strict_types=1);

final class Admin
{
    private const LOCK_FILE = STORAGE_DIR . '/admin-attempts.json';

    public static function enabled(): bool
    {
        return self::hash() !== '';
    }

    private static function hash(): string
    {
        $cfg = $GLOBALS['app_config']['admin'] ?? [];
        $hash = (string) ($cfg['password_hash'] ?? '');
        if ($hash === '' && APP_ENV !== 'production') {
            return password_hash('admin-dev', PASSWORD_DEFAULT);
        }
        return $hash;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['admin']);
    }

    /** Exige sesión de admin; si no hay, manda al ingreso. */
    public static function require(): void
    {
        if (!self::check()) {
            header('Location: /admin/login/', true, 303);
            exit;
        }
    }

    public static function locked(): bool
    {
        $attempts = self::attempts();
        return count($attempts) >= 5;
    }

    /** @return int[] marcas de tiempo de intentos fallidos en los últimos 15 minutos desde esta IP */
    private static function attempts(): array
    {
        $all = is_file(self::LOCK_FILE) ? (json_decode((string) file_get_contents(self::LOCK_FILE), true) ?: []) : [];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
        return array_values(array_filter($all[$ip] ?? [], static fn($t) => $t > time() - 900));
    }

    private static function record(bool $ok): void
    {
        $all = is_file(self::LOCK_FILE) ? (json_decode((string) file_get_contents(self::LOCK_FILE), true) ?: []) : [];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
        $list = self::attempts();
        if (!$ok) {
            $list[] = time();
        } else {
            $list = [];
        }
        $all[$ip] = $list;
        @file_put_contents(self::LOCK_FILE, json_encode($all), LOCK_EX);
    }

    public static function login(string $user, string $pass): bool
    {
        if (!self::enabled() || self::locked()) {
            return false;
        }
        $cfg = $GLOBALS['app_config']['admin'] ?? [];
        $ok = hash_equals((string) ($cfg['user'] ?? 'admin'), $user) && password_verify($pass, self::hash());
        self::record($ok);
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
        } else {
            usleep(400000);
        }
        return $ok;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin']);
        session_regenerate_id(true);
    }

    /** Celda segura para Excel: neutraliza fórmulas (=, +, -, @) en texto que escribió el público. */
    public static function cell(mixed $v): string
    {
        $v = (string) $v;
        return $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false && !is_numeric($v) ? "'" . $v : $v;
    }

    /** CSV con BOM y punto y coma: así lo abre Excel en español sin configurar nada. */
    public static function csv(string $filename, array $header, array $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $header, ';', '"', '');
        foreach ($rows as $row) {
            fputcsv($out, array_map([self::class, 'cell'], $row), ';', '"', '');
        }
        fclose($out);
        exit;
    }

    /** Órdenes pagadas con su detalle. $eventId y fechas opcionales. */
    public static function paidOrders(?string $eventId = null, ?string $date = null, ?string $from = null, ?string $to = null, string $by = 'function'): array
    {
        $sql = "SELECT o.*, t.number AS ticket_number FROM orders o LEFT JOIN tickets t ON t.order_id = o.id WHERE o.status = 'approved'";
        $p = [];
        if ($eventId) { $sql .= ' AND o.event_id = ?'; $p[] = $eventId; }
        if ($date) { $sql .= ' AND o.function_date = ?'; $p[] = $date; }
        $col = $by === 'paid' ? 'DATE(o.paid_at)' : 'o.function_date';
        if ($from) { $sql .= " AND $col >= ?"; $p[] = $from; }
        if ($to) { $sql .= " AND $col <= ?"; $p[] = $to; }
        $sql .= $by === 'paid' ? ' ORDER BY o.paid_at, o.id' : ' ORDER BY o.function_date, o.buyer_name';
        $rows = Db::all($sql, $p);
        foreach ($rows as &$r) {
            $r['items'] = Db::all('SELECT sku, label, unit_price, qty FROM order_items WHERE order_id = ? ORDER BY id', [(int) $r['id']]);
            $r['qty'] = array_sum(array_column($r['items'], 'qty'));
            $r['code'] = $r['ticket_number'] ? Tickets::code((int) $r['ticket_number'], (string) $r['kind']) : '';
            $r['concept'] = implode(' + ', array_map(static fn($i) => $i['qty'] . ' x ' . $i['label'], $r['items']));
        }
        return $rows;
    }

    public static function saveContent(array $patch): void
    {
        $file = STORAGE_DIR . '/content.json';
        $current = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        $merged = array_replace_recursive($current, $patch);
        file_put_contents($file, json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    /**
     * Valida el formulario de precios y arma el cambio a guardar en content.json.
     * Las posiciones salen de los valores por defecto, buscando cada producto por su sku.
     * @return array{patch:array,errors:array<string,string>}
     */
    public static function pricePatch(array $in): array
    {
        $errors = [];
        $int = static function (string $key, int $min, int $max, string $label) use ($in, &$errors): ?int {
            $raw = preg_replace('/[^\d]/', '', (string) ($in[$key] ?? '')) ?? '';
            $n = $raw === '' ? null : (int) $raw;
            if ($n === null || $n < $min || $n > $max) {
                $errors[$key] = "$label: escribe un número entre $min y $max.";
                return null;
            }
            return $n;
        };

        $defaults = require APP_DIR . '/content/defaults.php';
        $patch = [];

        foreach ($defaults['programacion']['events'] as $ei => $ev) {
            foreach ($ev['prices'] as $pi => $price) {
                if (!isset($price['amount'])) { continue; }
                $n = $int('price_' . $ev['id'] . '_' . $price['sku'], 10000, 2000000, $price['label']);
                if ($n !== null) { $patch['programacion']['events'][$ei]['prices'][$pi]['amount'] = $n; }
            }
        }
        foreach ($defaults['reservas'] as $ri => $ev) {
            $usd = $int('usd_' . $ev['id'], 1, 100, 'Cover en USD');
            $cap = $int('capacity_' . $ev['id'], 1, 500, 'Cupos por día');
            $max = $int('maxparty_' . $ev['id'], 1, 20, 'Personas por mesa');
            if ($usd !== null) { $patch['reservas'][$ri]['prices'][0]['usd'] = $usd; }
            if ($cap !== null) { $patch['reservas'][$ri]['capacity'] = $cap; }
            if ($max !== null) { $patch['reservas'][$ri]['max_party'] = $max; }
        }
        $fx = $int('fx', 1000, 10000, 'Tasa USD→COP');
        if ($fx !== null) { $patch['ajustes']['usd_cop_rate'] = $fx; }

        return ['patch' => $patch, 'errors' => $errors];
    }
}
