<?php
/**
 * Tarifas por fecha: persistencia. Cada escritura va en UNA transaccion con su
 * registro en pricing_audit y el incremento de pricing_meta.version.
 * Requiere pricing_rules.php. Compatible con SQLite y MySQL.
 */

declare(strict_types=1);

require_once __DIR__ . '/pricing_rules.php';

function pricing_now(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone(PRICING_TZ)))->format('Y-m-d H:i:s');
}

function pricing_load_rules(PDO $pdo): array
{
    $rows = $pdo->query('SELECT * FROM pricing_rules ORDER BY start_date, id')->fetchAll(PDO::FETCH_ASSOC);
    return array_map('pricing_normalize_rule', $rows);
}

function pricing_find_rule(PDO $pdo, string $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM pricing_rules WHERE id = :id');
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? pricing_normalize_rule($row) : null;
}

/**
 * Paquetes reservables del catalogo con su precio base (centavos), su % de
 * anticipo (null = sin %, se usa el anticipo fijo) y el anticipo fijo por persona.
 * @return array<string,array> slug => ['slug','title','currency','base_price_cents','default_deposit_percent',
 *                                      'deposit_fixed_cents']
 */
function pricing_load_package_pricing(PDO $pdo, array $catalog, int $fixedDepositCents = 0): array
{
    $pcts = [];
    foreach ($pdo->query('SELECT package_slug, default_deposit_percent FROM package_pricing')->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $pcts[(string) $r['package_slug']] = (int) $r['default_deposit_percent'];
    }
    $out = [];
    foreach ($catalog['packages'] ?? [] as $p) {
        if (empty($p['bookable']) || !isset($p['slug'])) {
            continue;
        }
        $slug = (string) $p['slug'];
        $out[$slug] = [
            'slug'                    => $slug,
            'title'                   => $p['title'] ?? ['es' => $slug],
            'currency'                => strtoupper((string) ($p['currency'] ?? 'MXN')),
            'base_price_cents'        => (int) $p['pricePerPerson'] * 100,
            'default_deposit_percent' => $pcts[$slug] ?? null,
            'deposit_fixed_cents'     => $fixedDepositCents > 0 ? $fixedDepositCents : null,
        ];
    }
    return $out;
}

/** Anticipo fijo por persona en centavos (config deposit_per_passenger, en pesos); 0 = sin anticipo fijo. */
function pricing_fixed_deposit_cents(array $config): int
{
    return max(0, (int) ($config['deposit_per_passenger'] ?? 0)) * 100;
}

/** "{version}-{sha1 de los precios base, 8 caracteres}". */
function pricing_version(PDO $pdo, array $catalog): string
{
    $st = $pdo->prepare('SELECT v FROM pricing_meta WHERE k = :k');
    $st->execute([':k' => 'version']);
    $v = (int) ($st->fetchColumn() ?: 0);
    $base = [];
    foreach ($catalog['packages'] ?? [] as $p) {
        if (isset($p['slug'])) {
            $base[(string) $p['slug']] = $p['pricePerPerson'] ?? null;
        }
    }
    ksort($base);
    return $v . '-' . substr(sha1((string) json_encode($base)), 0, 8);
}

function pricing_rule_to_row(array $r): array
{
    return [
        ':id'              => $r['id'],
        ':label'           => trim((string) $r['label']),
        ':type'            => $r['type'],
        ':package_ids'     => json_encode($r['package_ids'] === 'all' ? 'all' : array_values($r['package_ids'])),
        ':start_date'      => $r['start_date'],
        ':end_date'        => $r['end_date'],
        ':weekdays'        => $r['type'] === 'weekday' ? json_encode(array_values(array_unique($r['weekdays']))) : null,
        ':price_cents'     => $r['type'] === 'blocked' ? null : $r['price_cents'],
        ':deposit_percent' => $r['type'] === 'blocked' ? null : ($r['deposit_percent'] ?? null),
        ':active'          => !empty($r['active']) ? 1 : 0,
        ':updated_at'      => $r['updated_at'],
        ':updated_by'      => $r['updated_by'],
    ];
}

/** Ejecuta $fn dentro de una transaccion, registra el cambio y sube la version. */
function pricing_write(PDO $pdo, string $user, string $action, string $entity, string $entityId, callable $fn)
{
    $pdo->beginTransaction();
    try {
        $res = $fn();
        [$before, $after] = $res['audit'];
        $pdo->prepare(
            'INSERT INTO pricing_audit (at, user, action, entity, entity_id, before_json, after_json)
             VALUES (:at, :user, :action, :entity, :eid, :b, :a)'
        )->execute([
            ':at'     => pricing_now(),
            ':user'   => $user,
            ':action' => $action,
            ':entity' => $entity,
            ':eid'    => $entityId !== '' ? $entityId : (string) ($res['id'] ?? ''),
            ':b'      => $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            ':a'      => $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
        ]);
        $st = $pdo->prepare('SELECT v FROM pricing_meta WHERE k = :k');
        $st->execute([':k' => 'version']);
        $cur = $st->fetchColumn();
        if ($cur === false) {
            $pdo->prepare('INSERT INTO pricing_meta (k, v) VALUES (:k, :v)')->execute([':k' => 'version', ':v' => '1']);
        } else {
            $pdo->prepare('UPDATE pricing_meta SET v = :v WHERE k = :k')->execute([':k' => 'version', ':v' => (string) ((int) $cur + 1)]);
        }
        $pdo->commit();
        return $res['id'] ?? null;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Crea (sin id) o actualiza (con id) una regla.
 * @return string id de la regla
 * @throws InvalidArgumentException con los errores por campo en getMessage() (JSON)
 */
function pricing_save_rule(PDO $pdo, array $rule, string $user, array $knownSlugs, string $action = '', array $basePrices = []): string
{
    $errors = pricing_validate_rule($rule, $knownSlugs, $basePrices);
    if ($errors) {
        throw new InvalidArgumentException((string) json_encode($errors, JSON_UNESCAPED_UNICODE));
    }
    $id = (string) ($rule['id'] ?? '');
    $before = $id !== '' ? pricing_find_rule($pdo, $id) : null;
    if ($id !== '' && $before === null) {
        throw new InvalidArgumentException((string) json_encode(['id' => 'No se encontro lo que intentas editar.'], JSON_UNESCAPED_UNICODE));
    }
    if ($id === '') {
        $id = 'r_' . bin2hex(random_bytes(4));
    }
    $rule['id'] = $id;
    $rule['active'] = array_key_exists('active', $rule) ? !empty($rule['active']) : true;
    $rule['updated_at'] = pricing_now();
    $rule['updated_by'] = $user;
    $row = pricing_rule_to_row($rule);
    $action = $action !== '' ? $action : ($before === null ? 'create' : 'update');

    pricing_write($pdo, $user, $action, 'rule', $id, function () use ($pdo, $row, $before, $id) {
        if ($before === null) {
            $pdo->prepare(
                'INSERT INTO pricing_rules (id, label, type, package_ids, start_date, end_date, weekdays,
                    price_cents, deposit_percent, active, updated_at, updated_by)
                 VALUES (:id, :label, :type, :package_ids, :start_date, :end_date, :weekdays,
                    :price_cents, :deposit_percent, :active, :updated_at, :updated_by)'
            )->execute($row);
        } else {
            $pdo->prepare(
                'UPDATE pricing_rules SET label = :label, type = :type, package_ids = :package_ids,
                    start_date = :start_date, end_date = :end_date, weekdays = :weekdays,
                    price_cents = :price_cents, deposit_percent = :deposit_percent, active = :active,
                    updated_at = :updated_at, updated_by = :updated_by
                 WHERE id = :id'
            )->execute($row);
        }
        return ['id' => $id, 'audit' => [$before, pricing_find_rule($pdo, $id)]];
    });
    return $id;
}

/** Copia pausada con el nombre "... (copia)". @return string id nuevo */
function pricing_duplicate_rule(PDO $pdo, string $id, string $user, array $knownSlugs): string
{
    $src = pricing_find_rule($pdo, $id);
    if ($src === null) {
        throw new InvalidArgumentException((string) json_encode(['id' => 'No se encontro lo que intentas copiar.'], JSON_UNESCAPED_UNICODE));
    }
    $copy = $src;
    unset($copy['id']);
    $copy['label'] = mb_substr($src['label'], 0, 72) . ' (copia)';
    $copy['active'] = false;
    return pricing_save_rule($pdo, $copy, $user, $knownSlugs, 'duplicate');
}

function pricing_set_rule_active(PDO $pdo, string $id, bool $active, string $user): bool
{
    $before = pricing_find_rule($pdo, $id);
    if ($before === null) {
        return false;
    }
    pricing_write($pdo, $user, $active ? 'activate' : 'deactivate', 'rule', $id, function () use ($pdo, $id, $active, $user, $before) {
        $pdo->prepare('UPDATE pricing_rules SET active = :a, updated_at = :at, updated_by = :u WHERE id = :id')
            ->execute([':a' => $active ? 1 : 0, ':at' => pricing_now(), ':u' => $user, ':id' => $id]);
        return ['id' => $id, 'audit' => [$before, pricing_find_rule($pdo, $id)]];
    });
    return true;
}

function pricing_delete_rule(PDO $pdo, string $id, string $user): bool
{
    $before = pricing_find_rule($pdo, $id);
    if ($before === null) {
        return false;
    }
    pricing_write($pdo, $user, 'delete', 'rule', $id, function () use ($pdo, $id, $before) {
        $pdo->prepare('DELETE FROM pricing_rules WHERE id = :id')->execute([':id' => $id]);
        return ['id' => $id, 'audit' => [$before, null]];
    });
    return true;
}

/** Fija el % de anticipo por defecto de un paquete (1..100). */
function pricing_set_deposit(PDO $pdo, string $slug, int $percent, string $user, array $knownSlugs): void
{
    if (!in_array($slug, $knownSlugs, true)) {
        throw new InvalidArgumentException((string) json_encode(['package' => 'Ese paquete no existe.'], JSON_UNESCAPED_UNICODE));
    }
    if ($percent < 1 || $percent > 100) {
        throw new InvalidArgumentException((string) json_encode(['deposit_percent' => 'El anticipo debe estar entre 1 y 100 %.'], JSON_UNESCAPED_UNICODE));
    }
    pricing_write($pdo, $user, 'deposit_update', 'package', $slug, function () use ($pdo, $slug, $percent, $user) {
        $st = $pdo->prepare('SELECT default_deposit_percent FROM package_pricing WHERE package_slug = :s');
        $st->execute([':s' => $slug]);
        $prev = $st->fetchColumn();
        $params = [':s' => $slug, ':p' => $percent, ':at' => pricing_now(), ':u' => $user];
        if ($prev === false) {
            $pdo->prepare('INSERT INTO package_pricing (package_slug, default_deposit_percent, updated_at, updated_by)
                           VALUES (:s, :p, :at, :u)')->execute($params);
        } else {
            $pdo->prepare('UPDATE package_pricing SET default_deposit_percent = :p, updated_at = :at, updated_by = :u
                           WHERE package_slug = :s')->execute($params);
        }
        return [
            'id'    => $slug,
            'audit' => [
                $prev === false ? null : ['package_slug' => $slug, 'default_deposit_percent' => (int) $prev],
                ['package_slug' => $slug, 'default_deposit_percent' => $percent],
            ],
        ];
    });
}

/** @return array{rows:array,total:int,page:int,per_page:int} mas reciente primero */
function pricing_audit_list(PDO $pdo, int $page, int $perPage = 50): array
{
    $page = max(1, $page);
    $total = (int) $pdo->query('SELECT COUNT(*) FROM pricing_audit')->fetchColumn();
    $st = $pdo->prepare('SELECT * FROM pricing_audit ORDER BY id DESC LIMIT :lim OFFSET :off');
    $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $st->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
    $st->execute();
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $r['before'] = $r['before_json'] !== null ? json_decode((string) $r['before_json'], true) : null;
        $r['after'] = $r['after_json'] !== null ? json_decode((string) $r['after_json'], true) : null;
        $rows[] = $r;
    }
    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
}
