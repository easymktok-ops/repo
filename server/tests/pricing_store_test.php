<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/schema.php';
require_once __DIR__ . '/../lib/pricing_store.php';

function t_catalog(): array
{
    return ['packages' => [
        ['slug' => 'vuelo-compartido', 'pricePerPerson' => 2200, 'currency' => 'MXN', 'bookable' => true, 'title' => ['es' => 'Vuelo compartido']],
        ['slug' => 'vuelo-privado', 'pricePerPerson' => 3900, 'currency' => 'MXN', 'bookable' => true, 'title' => ['es' => 'Vuelo privado']],
        ['slug' => 'consultar', 'pricePerPerson' => null, 'currency' => 'MXN', 'bookable' => false],
    ]];
}

const T_KNOWN = ['vuelo-compartido', 'vuelo-privado'];

function test_store_esquema_idempotente_y_columnas_en_bookings(): void
{
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    ensure_schema($pdo);
    $before = array_column($pdo->query('PRAGMA table_info(bookings)')->fetchAll(), 'name');
    assert_true(!in_array('rule_id', $before, true), 'ensure_schema no debe crear columnas de tarifas');
    assert_same(false, (bool) $pdo->query("SELECT name FROM sqlite_master WHERE name = 'pricing_rules'")->fetchColumn());

    assert_same(true, ensure_pricing_schema($pdo));
    assert_same(true, ensure_pricing_schema($pdo));
    $cols = array_column($pdo->query('PRAGMA table_info(bookings)')->fetchAll(), 'name');
    foreach (['unit_price_cents', 'deposit_percent', 'rule_id', 'pricing_version'] as $c) {
        assert_true(in_array($c, $cols, true), "falta columna {$c}");
    }
    foreach (['pricing_rules', 'package_pricing', 'pricing_audit', 'pricing_meta'] as $t) {
        assert_same($t, $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = '{$t}'")->fetchColumn());
    }
}

function t_store(): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    ensure_schema($pdo);
    assert_same(true, ensure_pricing_schema($pdo));
    return $pdo;
}

function t_navidad(): array
{
    return [
        'label' => 'Navidad', 'type' => 'season', 'package_ids' => 'all',
        'start_date' => '2026-12-15', 'end_date' => '2027-01-06', 'price_cents' => 260000,
    ];
}

function test_store_crear_editar_y_auditar(): void
{
    $pdo = t_store();
    $cat = t_catalog();
    assert_same('0-', substr(pricing_version($pdo, $cat), 0, 2));

    $id = pricing_save_rule($pdo, t_navidad(), 'berenice', T_KNOWN);
    assert_true((bool) preg_match('/^r_[0-9a-f]{8}$/', $id), 'id con formato r_ + 8 hex');
    $r = pricing_find_rule($pdo, $id);
    assert_same('all', $r['package_ids']);
    assert_same(true, $r['active']);
    assert_same(260000, $r['price_cents']);
    assert_same('berenice', $r['updated_by']);
    assert_same('1-', substr(pricing_version($pdo, $cat), 0, 2));

    pricing_save_rule($pdo, ['id' => $id, 'price_cents' => 290000, 'package_ids' => ['vuelo-privado']] + t_navidad(), 'norman', T_KNOWN);
    $r = pricing_find_rule($pdo, $id);
    assert_same(290000, $r['price_cents']);
    assert_same(['vuelo-privado'], $r['package_ids']);
    assert_same('2-', substr(pricing_version($pdo, $cat), 0, 2));

    $audit = pricing_audit_list($pdo, 1);
    assert_same(2, $audit['total']);
    assert_same('update', $audit['rows'][0]['action']);
    assert_same('norman', $audit['rows'][0]['user']);
    assert_same(260000, $audit['rows'][0]['before']['price_cents']);
    assert_same(290000, $audit['rows'][0]['after']['price_cents']);
    assert_same('create', $audit['rows'][1]['action']);
    assert_same(null, $audit['rows'][1]['before']);
}

function test_store_rechaza_datos_invalidos_sin_escribir(): void
{
    $pdo = t_store();
    $e = assert_throws(InvalidArgumentException::class, function () use ($pdo) {
        pricing_save_rule($pdo, ['price_cents' => 0] + t_navidad(), 'x', T_KNOWN);
    });
    assert_same(['price_cents' => 'El precio debe ser mayor a 0.'], json_decode($e->getMessage(), true));
    assert_same(0, (int) $pdo->query('SELECT COUNT(*) FROM pricing_rules')->fetchColumn());
    assert_same(0, pricing_audit_list($pdo, 1)['total']);
    assert_throws(InvalidArgumentException::class, function () use ($pdo) {
        pricing_save_rule($pdo, ['id' => 'r_noexiste'] + t_navidad(), 'x', T_KNOWN);
    });
}

function test_store_duplicar_pausar_eliminar(): void
{
    $pdo = t_store();
    $id = pricing_save_rule($pdo, t_navidad(), 'b', T_KNOWN);
    $copy = pricing_duplicate_rule($pdo, $id, 'b', T_KNOWN);
    $c = pricing_find_rule($pdo, $copy);
    assert_same('Navidad (copia)', $c['label']);
    assert_same(false, $c['active']);

    assert_same(true, pricing_set_rule_active($pdo, $id, false, 'b'));
    assert_same(false, pricing_find_rule($pdo, $id)['active']);
    assert_same(true, pricing_set_rule_active($pdo, $id, true, 'b'));

    assert_same(true, pricing_delete_rule($pdo, $copy, 'b'));
    assert_same(null, pricing_find_rule($pdo, $copy));
    assert_same(false, pricing_delete_rule($pdo, $copy, 'b'));

    $actions = array_column(pricing_audit_list($pdo, 1)['rows'], 'action');
    assert_same(['delete', 'activate', 'deactivate', 'duplicate', 'create'], $actions);
}

function test_store_sin_vuelo_y_dias_de_semana_normalizan_campos(): void
{
    $pdo = t_store();
    $id = pricing_save_rule($pdo, [
        'label' => 'Mantenimiento', 'type' => 'blocked', 'package_ids' => 'all',
        'start_date' => '2026-11-10', 'end_date' => '2026-11-10', 'price_cents' => 123, 'deposit_percent' => 40,
    ], 'b', T_KNOWN);
    $r = pricing_find_rule($pdo, $id);
    assert_same(null, $r['price_cents']);
    assert_same(null, $r['deposit_percent']);

    $id = pricing_save_rule($pdo, [
        'label' => 'Fines', 'type' => 'weekday', 'package_ids' => 'all', 'weekdays' => [6, 0, 6],
        'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'price_cents' => 250000,
    ], 'b', T_KNOWN);
    assert_same([6, 0], pricing_find_rule($pdo, $id)['weekdays']);
}

function test_store_anticipo_por_paquete_y_precio_base(): void
{
    $pdo = t_store();
    $cat = t_catalog();
    $pk = pricing_load_package_pricing($pdo, $cat);
    assert_same(['vuelo-compartido', 'vuelo-privado'], array_keys($pk), 'solo reservables');
    assert_same(220000, $pk['vuelo-compartido']['base_price_cents']);
    assert_same(null, $pk['vuelo-compartido']['default_deposit_percent']);

    pricing_set_deposit($pdo, 'vuelo-compartido', 45, 'b', T_KNOWN);
    pricing_set_deposit($pdo, 'vuelo-compartido', 50, 'b', T_KNOWN);
    $pk = pricing_load_package_pricing($pdo, $cat);
    assert_same(50, $pk['vuelo-compartido']['default_deposit_percent']);

    $row = pricing_audit_list($pdo, 1)['rows'][0];
    assert_same('deposit_update', $row['action']);
    assert_same(45, $row['before']['default_deposit_percent']);
    assert_same(50, $row['after']['default_deposit_percent']);

    assert_throws(InvalidArgumentException::class, function () use ($pdo) {
        pricing_set_deposit($pdo, 'vuelo-compartido', 0, 'b', T_KNOWN);
    });
    assert_throws(InvalidArgumentException::class, function () use ($pdo) {
        pricing_set_deposit($pdo, 'no-existe', 40, 'b', T_KNOWN);
    });
}

function test_store_version_refleja_cambios_de_precio_base(): void
{
    $pdo = t_store();
    $a = pricing_version($pdo, t_catalog());
    $cat = t_catalog();
    $cat['packages'][0]['pricePerPerson'] = 2500;
    $b = pricing_version($pdo, $cat);
    assert_same('0', explode('-', $b)[0]);
    assert_true($a !== $b, 'cambiar priceFrom en Decap cambia la version');
}

function test_store_audit_paginado(): void
{
    $pdo = t_store();
    for ($i = 0; $i < 5; $i++) {
        pricing_set_deposit($pdo, 'vuelo-privado', 20 + $i, 'b', T_KNOWN);
    }
    $p2 = pricing_audit_list($pdo, 2, 2);
    assert_same(5, $p2['total']);
    assert_same([22, 21], array_map(function ($r) {
        return $r['after']['default_deposit_percent'];
    }, $p2['rows']));
}
