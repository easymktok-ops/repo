<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/pricing.php';
require_once __DIR__ . '/../lib/pricing_rules.php';

function t_cc_pkg(): array
{
    return [
        'slug' => 'vuelo-compartido', 'title' => ['es' => 'Vuelo compartido'], 'pricePerPerson' => 2200,
        'currency' => 'MXN', 'capacity' => ['min' => 1, 'max' => 8], 'bookable' => true,
    ];
}

function test_charge_sin_resolved_es_el_flujo_de_hoy(): void
{
    $c = compute_charge(['deposit_per_passenger' => 1000], t_cc_pkg(), 'deposit', 3);
    assert_same(100000, $c['unit_amount_cents']);
    assert_same(3, $c['quantity']);
    assert_same(300000, $c['amount_now_cents']);
    assert_same(660000, $c['total_full_cents']);
    assert_same(360000, $c['balance_cents']);
    assert_same(2200, $c['price_per_person']);
    assert_true(!isset($c['rule_id']), 'sin campos de tarifa');

    $f = compute_charge(['deposit_per_passenger' => 1000], t_cc_pkg(), 'full', 2);
    assert_same(220000, $f['unit_amount_cents']);
    assert_same(440000, $f['amount_now_cents']);
    assert_same(0, $f['balance_cents']);
}

function test_charge_con_resolved_en_modo_anticipo(): void
{
    $r = resolve_price('2026-12-20', ['slug' => 'vuelo-compartido', 'base_price_cents' => 220000, 'default_deposit_percent' => 45], [
        pricing_normalize_rule([
            'id' => 'r_nav', 'label' => 'Navidad', 'type' => 'season', 'package_ids' => '"all"', 'start_date' => '2026-12-15',
            'end_date' => '2027-01-06', 'price_cents' => 265000, 'active' => 1, 'updated_at' => '2026-09-01 00:00:00',
        ]),
    ]);
    $c = compute_charge(['deposit_per_passenger' => 1000], t_cc_pkg(), 'deposit', 2, $r);
    assert_same(119250, $c['unit_amount_cents'], '2650 x 45 % = 1192.50');
    assert_same(2, $c['quantity']);
    assert_same(238500, $c['amount_now_cents']);
    assert_same(530000, $c['total_full_cents']);
    assert_same(291500, $c['balance_cents']);
    assert_same(2650, $c['price_per_person']);
    assert_same(265000, $c['unit_price_cents']);
    assert_same(238500, $c['deposit_total_cents']);
    assert_same(45, $c['deposit_percent']);
    assert_same('r_nav', $c['rule_id']);
}

function test_charge_con_resolved_en_modo_total(): void
{
    $r = resolve_price('2026-11-03', ['slug' => 'vuelo-compartido', 'base_price_cents' => 220000, 'default_deposit_percent' => 45], []);
    $c = compute_charge([], t_cc_pkg(), 'full', 3, $r);
    assert_same(220000, $c['unit_amount_cents']);
    assert_same(660000, $c['amount_now_cents']);
    assert_same(660000, $c['total_full_cents']);
    assert_same(0, $c['balance_cents']);
    assert_same(null, $c['rule_id']);
}

function test_charge_con_resolved_valida_capacidad_y_estado(): void
{
    $ok = ['status' => 'available', 'price' => 220000, 'deposit' => 99000, 'balance' => 121000, 'rule_id' => null, 'deposit_percent' => 45];
    assert_throws(InvalidArgumentException::class, function () use ($ok) {
        compute_charge([], t_cc_pkg(), 'deposit', 9, $ok);
    }, 'capacidad maxima');
    assert_throws(InvalidArgumentException::class, function () {
        compute_charge([], t_cc_pkg(), 'deposit', 2, ['status' => 'blocked', 'rule_id' => 'r_x']);
    }, 'fecha bloqueada');
    assert_throws(InvalidArgumentException::class, function () use ($ok) {
        compute_charge([], t_cc_pkg(), 'otro', 2, $ok);
    }, 'modo invalido');
}
