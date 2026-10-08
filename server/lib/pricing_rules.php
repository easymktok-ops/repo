<?php
/**
 * Tarifas por fecha: logica PURA (sin BD ni red). Es la UNICA implementacion de
 * la resolucion de precio; el calendario y la vista previa solo muestran lo que
 * devuelve /api/prices.php, que llama a estas funciones.
 *
 * Unidades: dinero en centavos MXN (enteros), fechas 'YYYY-MM-DD' en hora de
 * Mexico. Compatible con PHP 7.4.
 */

declare(strict_types=1);

// Hora de la Ciudad de Mexico como desfase FIJO: Mexico elimino el horario de
// verano en 2022 y la base de zonas que trae PHP 7.4 (2022.1) aun aplica UTC-5
// de abril a octubre, lo que adelantaria "hoy" una hora. Con '-06:00' el
// resultado no depende de la base de zonas del hosting.
const PRICING_TZ = '-06:00';
const PRICING_TYPES = ['season', 'date', 'weekday', 'blocked'];
// Precedencia entre tipos: menor gana.
const PRICING_TYPE_RANK = ['blocked' => 0, 'date' => 1, 'weekday' => 2, 'season' => 3];

function pricing_valid_ymd(string $d): bool
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})\z/', $d, $m)) {
        return false;
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

function pricing_date(string $ymd): DateTimeImmutable
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd, new DateTimeZone(PRICING_TZ));
    if ($d === false) {
        throw new InvalidArgumentException('Fecha invalida: ' . $ymd);
    }
    return $d;
}

/** Dias de $a a $b (negativo si $b es anterior). */
function pricing_days_between(string $a, string $b): int
{
    $diff = pricing_date($a)->diff(pricing_date($b));
    return (int) $diff->days * ($diff->invert ? -1 : 1);
}

function pricing_add_days(string $ymd, int $days): string
{
    return pricing_date($ymd)->modify(($days >= 0 ? '+' : '') . $days . ' days')->format('Y-m-d');
}

/** 0 = domingo ... 6 = sabado. */
function pricing_weekday(string $ymd): int
{
    return (int) pricing_date($ymd)->format('w');
}

function pricing_today_mx(?DateTimeImmutable $now = null): string
{
    $now = $now ?: new DateTimeImmutable('now');
    return $now->setTimezone(new DateTimeZone(PRICING_TZ))->format('Y-m-d');
}

/** Anticipo en centavos: redondeo a la mitad hacia arriba, solo enteros. */
function pricing_deposit_cents(int $priceCents, int $percent): int
{
    return intdiv($priceCents * $percent + 50, 100);
}

/**
 * Normaliza una fila de pricing_rules (package_ids y weekdays vienen como JSON
 * desde la BD) al formato que usan las funciones puras.
 */
function pricing_normalize_rule(array $row): array
{
    $pk = $row['package_ids'] ?? 'all';
    if (is_string($pk)) {
        $decoded = json_decode($pk, true);
        $pk = $decoded === null ? $pk : $decoded;
    }
    if ($pk !== 'all') {
        $pk = array_values(array_map('strval', (array) $pk));
    }
    $wd = $row['weekdays'] ?? null;
    if (is_string($wd)) {
        $wd = json_decode($wd, true);
    }
    $wd = is_array($wd) ? array_values(array_map('intval', $wd)) : null;

    return [
        'id'              => (string) $row['id'],
        'label'           => (string) ($row['label'] ?? ''),
        'type'            => (string) $row['type'],
        'package_ids'     => $pk,
        'start_date'      => (string) $row['start_date'],
        'end_date'        => (string) $row['end_date'],
        'weekdays'        => $wd,
        'price_cents'     => isset($row['price_cents']) ? (int) $row['price_cents'] : null,
        'deposit_percent' => isset($row['deposit_percent']) ? (int) $row['deposit_percent'] : null,
        'active'          => !empty($row['active']),
        'updated_at'      => (string) ($row['updated_at'] ?? ''),
        'updated_by'      => (string) ($row['updated_by'] ?? ''),
    ];
}

function pricing_rule_covers_package(array $rule, string $slug): bool
{
    return $rule['package_ids'] === 'all' || in_array($slug, (array) $rule['package_ids'], true);
}

/** La regla (activa) aplica ese dia, sin mirar el paquete. */
function pricing_rule_covers_date(array $rule, string $date): bool
{
    if (empty($rule['active'])) {
        return false;
    }
    if ($date < $rule['start_date'] || $date > $rule['end_date']) {
        return false;
    }
    if ($rule['type'] === 'weekday') {
        return in_array(pricing_weekday($date), (array) $rule['weekdays'], true);
    }
    return true;
}

function pricing_rule_span(array $rule): int
{
    return pricing_days_between($rule['start_date'], $rule['end_date']) + 1;
}

/**
 * Orden de precedencia: tipo (sin vuelo > fecha > dia de semana > temporada),
 * luego menos dias, luego la editada mas recientemente, luego id ascendente.
 * Negativo = $a gana.
 */
function pricing_rule_compare(array $a, array $b): int
{
    $c = PRICING_TYPE_RANK[$a['type']] <=> PRICING_TYPE_RANK[$b['type']];
    if ($c !== 0) {
        return $c;
    }
    $c = pricing_rule_span($a) <=> pricing_rule_span($b);
    if ($c !== 0) {
        return $c;
    }
    $c = strcmp($b['updated_at'], $a['updated_at']);
    if ($c !== 0) {
        return $c;
    }
    return strcmp($a['id'], $b['id']);
}

/** Regla ganadora para esa fecha y paquete, o null si ninguna aplica. */
function pricing_winning_rule(string $date, string $slug, array $rules): ?array
{
    $best = null;
    foreach ($rules as $r) {
        if (!pricing_rule_covers_package($r, $slug) || !pricing_rule_covers_date($r, $date)) {
            continue;
        }
        if ($best === null || pricing_rule_compare($r, $best) < 0) {
            $best = $r;
        }
    }
    return $best;
}

/**
 * @param array $pkg ['slug'=>string,'base_price_cents'=>int,'default_deposit_percent'=>?int,
 *                    'deposit_fixed_cents'=>?int]
 * @param array $rules filas normalizadas (pricing_normalize_rule)
 * @return array status 'available' (price, deposit, balance, rule_id, deposit_percent, deposit_fixed),
 *               'blocked' (rule_id) o 'unconfigured'
 *
 * Reglas de negocio:
 * - El precio de lista (base) es el PISO: una regla nunca cobra menos, asi el "Desde" del sitio
 *   siempre es cierto aunque el precio de lista suba despues de crear la regla.
 * - Sin % de anticipo (ni en la regla ni en el paquete) se usa el anticipo fijo por persona
 *   (deposit_fixed_cents, el de siempre); sin ninguno de los dos queda 'unconfigured'.
 */
function resolve_price(string $date, array $pkg, array $rules): array
{
    $rule = pricing_winning_rule($date, (string) $pkg['slug'], $rules);

    if ($rule !== null && $rule['type'] === 'blocked') {
        return ['status' => 'blocked', 'rule_id' => $rule['id']];
    }

    $base = (int) $pkg['base_price_cents'];
    $price = $rule !== null && $rule['price_cents'] !== null
        ? max((int) $rule['price_cents'], $base)
        : $base;

    $pct = $rule !== null && $rule['deposit_percent'] !== null
        ? (int) $rule['deposit_percent']
        : ($pkg['default_deposit_percent'] ?? null);
    $fixed = isset($pkg['deposit_fixed_cents']) ? (int) $pkg['deposit_fixed_cents'] : 0;
    if ($pct === null && $fixed <= 0) {
        return ['status' => 'unconfigured'];
    }

    $deposit = $pct !== null ? pricing_deposit_cents($price, (int) $pct) : min($fixed, $price);
    return [
        'status'          => 'available',
        'price'           => $price,
        'deposit'         => $deposit,
        'balance'         => $price - $deposit,
        'rule_id'         => $rule !== null ? $rule['id'] : null,
        'deposit_percent' => $pct !== null ? (int) $pct : null,
        'deposit_fixed'   => $pct === null,
    ];
}

/**
 * Mapa dia por dia de $from a $to (inclusivo). Fuera de [$minDate, $maxDate]
 * el dia queda 'unavailable'.
 */
function resolve_range(string $from, string $to, array $pkg, array $rules, string $minDate, string $maxDate): array
{
    $out = [];
    for ($d = $from; $d <= $to; $d = pricing_add_days($d, 1)) {
        if ($d < $minDate || $d > $maxDate) {
            $out[$d] = ['status' => 'unavailable'];
            continue;
        }
        $out[$d] = resolve_price($d, $pkg, $rules);
    }
    return $out;
}

/**
 * Coincidencias de $rule con las demas reglas activas que comparten paquete.
 * Devuelve tramos consecutivos:
 *   ['from','to','other_id','other_label','outcome' => this_wins|other_wins|no_flight]
 * El texto para el negocio lo arma el panel.
 */
function pricing_overlaps(array $rule, array $rules): array
{
    $out = [];
    if (empty($rule['active'])) {
        return $out;
    }
    foreach ($rules as $other) {
        if ($other['id'] === $rule['id'] || empty($other['active'])) {
            continue;
        }
        if (!pricing_rules_share_package($rule, $other)) {
            continue;
        }
        $from = max($rule['start_date'], $other['start_date']);
        $to = min($rule['end_date'], $other['end_date']);
        if ($from > $to) {
            continue;
        }
        $current = null;
        for ($d = $from; $d <= $to; $d = pricing_add_days($d, 1)) {
            if (!pricing_rule_covers_date($rule, $d) || !pricing_rule_covers_date($other, $d)) {
                if ($current !== null) {
                    $out[] = $current;
                    $current = null;
                }
                continue;
            }
            if (pricing_rule_compare($rule, $other) < 0) {
                $outcome = 'this_wins';
            } else {
                $outcome = $other['type'] === 'blocked' ? 'no_flight' : 'other_wins';
            }
            if ($current !== null && $current['outcome'] === $outcome && pricing_add_days($current['to'], 1) === $d) {
                $current['to'] = $d;
                continue;
            }
            if ($current !== null) {
                $out[] = $current;
            }
            $current = [
                'from'        => $d,
                'to'          => $d,
                'other_id'    => $other['id'],
                'other_label' => $other['label'],
                'outcome'     => $outcome,
            ];
        }
        if ($current !== null) {
            $out[] = $current;
        }
    }
    usort($out, function (array $a, array $b): int {
        return strcmp($a['from'], $b['from']) ?: strcmp($a['other_id'], $b['other_id']);
    });
    return $out;
}

function pricing_rules_share_package(array $a, array $b): bool
{
    if ($a['package_ids'] === 'all' || $b['package_ids'] === 'all') {
        return true;
    }
    return count(array_intersect((array) $a['package_ids'], (array) $b['package_ids'])) > 0;
}

/**
 * Valida una regla antes de guardarla. Devuelve errores por campo, en espanol
 * llano (vacio = valida). $known = slugs de paquetes existentes.
 */
/**
 * @param array $basePrices slug => precio de lista en centavos. Si se pasa, el precio de la
 *                          regla no puede quedar por debajo del precio de lista de ningun
 *                          paquete al que aplica (el "Desde" del sitio debe ser cierto).
 */
function pricing_validate_rule(array $r, array $known, array $basePrices = []): array
{
    $e = [];
    $label = trim((string) ($r['label'] ?? ''));
    if ($label === '') {
        $e['label'] = 'Escribe un nombre.';
    } elseif (mb_strlen($label) > 80) {
        $e['label'] = 'El nombre debe tener 80 caracteres o menos.';
    }
    $type = (string) ($r['type'] ?? '');
    if (!in_array($type, PRICING_TYPES, true)) {
        $e['type'] = 'Elige un tipo.';
    }
    $start = (string) ($r['start_date'] ?? '');
    $end = (string) ($r['end_date'] ?? '');
    if (!pricing_valid_ymd($start)) {
        $e['start_date'] = 'La fecha no es válida.';
    }
    if (!pricing_valid_ymd($end)) {
        $e['end_date'] = 'La fecha no es válida.';
    }
    if (!isset($e['start_date']) && !isset($e['end_date']) && $end < $start) {
        $e['end_date'] = 'La fecha final no puede ser antes de la inicial.';
    }
    $pk = $r['package_ids'] ?? null;
    if ($pk !== 'all') {
        $pk = is_array($pk) ? $pk : [];
        if (!$pk) {
            $e['package_ids'] = 'Elige al menos un paquete.';
        } elseif (array_diff($pk, $known)) {
            $e['package_ids'] = 'Hay un paquete que no existe.';
        }
    }
    if ($type === 'weekday') {
        $wd = is_array($r['weekdays'] ?? null) ? $r['weekdays'] : [];
        $valid = array_filter($wd, function ($d): bool {
            return is_int($d) && $d >= 0 && $d <= 6;
        });
        if (!$wd || count($valid) !== count($wd)) {
            $e['weekdays'] = 'Elige al menos un día.';
        }
    }
    if ($type !== 'blocked' && $type !== '') {
        $price = $r['price_cents'] ?? null;
        if (!is_int($price) || $price <= 0) {
            $e['price_cents'] = 'El precio debe ser mayor a 0.';
        } elseif ($basePrices && !isset($e['package_ids'])) {
            $slugs = $pk === 'all' ? array_keys($basePrices) : $pk;
            $floor = 0;
            foreach ($slugs as $s) {
                $floor = max($floor, (int) ($basePrices[$s] ?? 0));
            }
            if ($price < $floor) {
                $e['price_cents'] = 'No puede ser menor al precio de lista ($' . number_format(intdiv($floor, 100))
                    . ' por persona en los paquetes elegidos). Para una oferta, baja el precio en Contenido › Paquetes.';
            }
        }
    }
    $pct = $r['deposit_percent'] ?? null;
    if ($pct !== null && (!is_int($pct) || $pct < 1 || $pct > 100)) {
        $e['deposit_percent'] = 'El anticipo debe estar entre 1 y 100 %.';
    }
    return $e;
}
