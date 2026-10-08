<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/pricing_rules.php';

function t_rule(array $o): array
{
    return pricing_normalize_rule(array_merge([
        'id'              => 'r_' . substr(md5(json_encode($o)), 0, 8),
        'label'           => 'Regla',
        'type'            => 'season',
        'package_ids'     => 'all',
        'start_date'      => '2026-01-01',
        'end_date'        => '2026-01-01',
        'weekdays'        => null,
        'price_cents'     => 300000,
        'deposit_percent' => null,
        'active'          => 1,
        'updated_at'      => '2026-09-01 10:00:00',
        'updated_by'      => 'test',
    ], $o));
}

function t_pkg(array $o = []): array
{
    return array_merge([
        'slug'                    => 'vuelo-compartido',
        'base_price_cents'        => 220000,
        'default_deposit_percent' => 45,
    ], $o);
}

// --- Casos obligatorios -----------------------------------------------------

function test_temporada_que_cruza_el_anio(): void
{
    $rules = [t_rule(['type' => 'season', 'start_date' => '2026-12-15', 'end_date' => '2027-01-06', 'price_cents' => 290000])];
    foreach (['2026-12-31', '2027-01-01'] as $d) {
        $r = resolve_price($d, t_pkg(), $rules);
        assert_same('available', $r['status'], $d);
        assert_same(290000, $r['price'], $d);
    }
    assert_same(220000, resolve_price('2027-01-07', t_pkg(), $rules)['price']);
}

function test_bordes_inclusivos(): void
{
    $rules = [t_rule(['start_date' => '2026-10-10', 'end_date' => '2026-10-12', 'price_cents' => 250000])];
    assert_same(220000, resolve_price('2026-10-09', t_pkg(), $rules)['price'], 'dia anterior');
    assert_same(250000, resolve_price('2026-10-10', t_pkg(), $rules)['price'], 'primer dia');
    assert_same(250000, resolve_price('2026-10-12', t_pkg(), $rules)['price'], 'ultimo dia');
    assert_same(220000, resolve_price('2026-10-13', t_pkg(), $rules)['price'], 'dia posterior');
}

function test_29_de_febrero(): void
{
    $especial = [t_rule(['type' => 'date', 'start_date' => '2028-02-29', 'end_date' => '2028-02-29', 'price_cents' => 310000])];
    assert_same(310000, resolve_price('2028-02-29', t_pkg(), $especial)['price']);
    assert_same(220000, resolve_price('2028-03-01', t_pkg(), $especial)['price']);

    $temporada = [t_rule(['start_date' => '2028-02-28', 'end_date' => '2028-03-01', 'price_cents' => 260000])];
    assert_same(260000, resolve_price('2028-02-29', t_pkg(), $temporada)['price']);
}

function test_fecha_especial_gana_a_fin_de_semana_y_temporada(): void
{
    // 2026-12-19 es sabado.
    $temporada = t_rule(['id' => 'r_temp', 'type' => 'season', 'start_date' => '2026-12-01', 'end_date' => '2026-12-31', 'price_cents' => 250000]);
    $finde = t_rule(['id' => 'r_finde', 'type' => 'weekday', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'weekdays' => [0, 6], 'price_cents' => 270000]);
    $especial = t_rule(['id' => 'r_esp', 'type' => 'date', 'start_date' => '2026-12-19', 'end_date' => '2026-12-19', 'price_cents' => 330000]);
    $d = '2026-12-19';
    assert_same(6, pricing_weekday($d));

    $r = resolve_price($d, t_pkg(), [$temporada, $finde, $especial]);
    assert_same(330000, $r['price']);
    assert_same('r_esp', $r['rule_id']);
    assert_same('r_finde', resolve_price($d, t_pkg(), [$temporada, $finde])['rule_id']);
    assert_same('r_temp', resolve_price($d, t_pkg(), [$temporada])['rule_id']);
}

function test_dia_sin_vuelo_dentro_de_temporada(): void
{
    $rules = [
        t_rule(['id' => 'r_temp', 'start_date' => '2026-12-01', 'end_date' => '2026-12-31']),
        t_rule(['id' => 'r_navidad', 'type' => 'blocked', 'start_date' => '2026-12-25', 'end_date' => '2026-12-25', 'price_cents' => null]),
    ];
    assert_same(['status' => 'blocked', 'rule_id' => 'r_navidad'], resolve_price('2026-12-25', t_pkg(), $rules));
    assert_same('available', resolve_price('2026-12-24', t_pkg(), $rules)['status']);
}

function test_sin_reglas_usa_precio_base_y_porcentaje_del_paquete(): void
{
    $r = resolve_price('2026-11-03', t_pkg(), []);
    assert_same([
        'status'          => 'available',
        'price'           => 220000,
        'deposit'         => 99000,
        'balance'         => 121000,
        'rule_id'         => null,
        'deposit_percent' => 45,
    ], $r);
}

function test_redondeo_con_enteros(): void
{
    $casos = [
        [265000, 33, 87450],   // exacto
        [233333, 45, 105000],  // 104999.85
        [475000, 21, 99750],   // exacto
        [1, 50, 1],            // 0.5 sube
        [3, 50, 2],            // 1.5 sube
        [101, 1, 1],           // 1.01 baja
    ];
    foreach ($casos as [$price, $pct, $exp]) {
        $r = resolve_price('2026-11-03', t_pkg(['base_price_cents' => $price, 'default_deposit_percent' => $pct]), []);
        assert_same($exp, $r['deposit'], "{$price} x {$pct}%");
        assert_same($price - $exp, $r['balance'], "saldo {$price} x {$pct}%");
    }
}

function test_regla_inactiva_se_ignora(): void
{
    $rules = [t_rule(['start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'active' => 0, 'price_cents' => 999900])];
    assert_same(220000, resolve_price('2026-11-03', t_pkg(), $rules)['price']);
    $blocked = [t_rule(['type' => 'blocked', 'start_date' => '2026-11-03', 'end_date' => '2026-11-03', 'active' => 0])];
    assert_same('available', resolve_price('2026-11-03', t_pkg(), $blocked)['status']);
}

// --- Extras -----------------------------------------------------------------

function test_desempate_mismo_tipo(): void
{
    $larga = t_rule(['id' => 'r_a', 'start_date' => '2026-12-01', 'end_date' => '2026-12-31', 'price_cents' => 250000]);
    $corta = t_rule(['id' => 'r_b', 'start_date' => '2026-12-20', 'end_date' => '2026-12-26', 'price_cents' => 280000]);
    assert_same('r_b', resolve_price('2026-12-22', t_pkg(), [$larga, $corta])['rule_id'], 'rango mas corto');

    $vieja = t_rule(['id' => 'r_c', 'start_date' => '2026-12-20', 'end_date' => '2026-12-26', 'updated_at' => '2026-09-01 10:00:00']);
    $nueva = t_rule(['id' => 'r_d', 'start_date' => '2026-12-21', 'end_date' => '2026-12-27', 'updated_at' => '2026-09-02 10:00:00']);
    assert_same('r_d', resolve_price('2026-12-22', t_pkg(), [$vieja, $nueva])['rule_id'], 'mas reciente');

    $x = t_rule(['id' => 'r_y', 'start_date' => '2026-12-20', 'end_date' => '2026-12-26']);
    $y = t_rule(['id' => 'r_x', 'start_date' => '2026-12-20', 'end_date' => '2026-12-26']);
    assert_same('r_x', resolve_price('2026-12-22', t_pkg(), [$x, $y])['rule_id'], 'id ascendente');
    assert_same('r_x', resolve_price('2026-12-22', t_pkg(), [$y, $x])['rule_id'], 'no depende del orden');
}

function test_regla_de_un_paquete_no_aplica_a_otro(): void
{
    $rules = [t_rule(['package_ids' => '["vuelo-privado"]', 'start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'price_cents' => 450000])];
    assert_same(220000, resolve_price('2026-11-03', t_pkg(), $rules)['price']);
    assert_same(450000, resolve_price('2026-11-03', t_pkg(['slug' => 'vuelo-privado']), $rules)['price']);

    $todos = [t_rule(['package_ids' => '"all"', 'start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'price_cents' => 260000])];
    assert_same(260000, resolve_price('2026-11-03', t_pkg(), $todos)['price']);
    assert_same(260000, resolve_price('2026-11-03', t_pkg(['slug' => 'vuelo-privado']), $todos)['price']);
}

function test_porcentaje_de_la_regla_sobrescribe_el_del_paquete(): void
{
    $rules = [t_rule(['start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'price_cents' => 265000, 'deposit_percent' => 33])];
    $r = resolve_price('2026-11-03', t_pkg(), $rules);
    assert_same(33, $r['deposit_percent']);
    assert_same(87450, $r['deposit']);
}

function test_regla_sin_precio_usa_el_base(): void
{
    $rules = [t_rule(['start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'price_cents' => null, 'deposit_percent' => 30])];
    $r = resolve_price('2026-11-03', t_pkg(), $rules);
    assert_same(220000, $r['price']);
    assert_same(66000, $r['deposit']);
}

function test_paquete_sin_porcentaje_queda_sin_configurar(): void
{
    assert_same(['status' => 'unconfigured'], resolve_price('2026-11-03', t_pkg(['default_deposit_percent' => null]), []));
    // Un dia sin vuelo sigue bloqueado aunque falte el %.
    $rules = [t_rule(['id' => 'r_b', 'type' => 'blocked', 'start_date' => '2026-11-03', 'end_date' => '2026-11-03'])];
    assert_same('blocked', resolve_price('2026-11-03', t_pkg(['default_deposit_percent' => null]), $rules)['status']);
}

function test_dias_de_semana_fuera_de_sus_dias_no_aplica(): void
{
    // 2026-11-02 lunes, 2026-11-07 sabado.
    $rules = [t_rule(['type' => 'weekday', 'start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'weekdays' => '[6]', 'price_cents' => 270000])];
    assert_same(220000, resolve_price('2026-11-02', t_pkg(), $rules)['price']);
    assert_same(270000, resolve_price('2026-11-07', t_pkg(), $rules)['price']);
}

function test_fechas_validas(): void
{
    assert_true(pricing_valid_ymd('2028-02-29'));
    assert_true(!pricing_valid_ymd('2026-02-29'));
    assert_true(!pricing_valid_ymd('2026-02-30'));
    assert_true(!pricing_valid_ymd('2026-13-01'));
    assert_true(!pricing_valid_ymd('26-01-01'));
    assert_true(!pricing_valid_ymd('2026-1-1'));
    assert_true(!pricing_valid_ymd(''));
    assert_true(!pricing_valid_ymd("2026-01-01\n"));
}

function test_aritmetica_de_fechas(): void
{
    assert_same(1, pricing_days_between('2026-12-31', '2027-01-01'));
    assert_same(-1, pricing_days_between('2027-01-01', '2026-12-31'));
    assert_same(366, pricing_days_between('2028-01-01', '2029-01-01'));
    assert_same('2028-02-29', pricing_add_days('2028-02-28', 1));
    assert_same('2027-01-01', pricing_add_days('2026-12-31', 1));
    assert_same('2026-12-31', pricing_add_days('2027-01-01', -1));
    // Cambio de horario (Mexico ya no aplica, pero la zona debe ser estable).
    assert_same('2026-11-02', pricing_add_days('2026-11-01', 1));
}

function test_hoy_en_hora_de_mexico(): void
{
    // 05:30 UTC del 30 sep = 23:30 del 29 sep en CDMX.
    $now = new DateTimeImmutable('2026-09-30 05:30:00', new DateTimeZone('UTC'));
    assert_same('2026-09-29', pricing_today_mx($now));
}

function test_hoy_no_depende_de_la_base_de_zonas_del_hosting(): void
{
    // PHP 7.4 trae la base de zonas 2022.1, que aun aplica horario de verano en
    // Mexico: con 'America/Mexico_City' estas horas caerian en el dia siguiente.
    $casos = [
        '2026-07-15 05:30:00' => '2026-07-14', // 23:30 del 14 en CDMX (pleno verano)
        '2026-07-15 06:00:00' => '2026-07-15', // medianoche exacta en CDMX
        '2026-04-05 05:59:59' => '2026-04-04', // antiguo domingo de cambio de horario
        '2027-01-01 05:59:59' => '2026-12-31', // fin de anio
    ];
    foreach ($casos as $utc => $esperado) {
        assert_same($esperado, pricing_today_mx(new DateTimeImmutable($utc, new DateTimeZone('UTC'))), $utc);
    }
}

function test_resolve_range_respeta_la_ventana(): void
{
    $days = resolve_range('2026-09-28', '2026-10-02', t_pkg(), [], '2026-09-30', '2026-10-01');
    assert_same(['2026-09-28', '2026-09-29', '2026-09-30', '2026-10-01', '2026-10-02'], array_keys($days));
    assert_same('unavailable', $days['2026-09-29']['status']);
    assert_same('available', $days['2026-09-30']['status']);
    assert_same('available', $days['2026-10-01']['status']);
    assert_same('unavailable', $days['2026-10-02']['status']);
}

function test_coincidencias_entre_temporada_y_fecha_especial(): void
{
    $temporada = t_rule(['id' => 'r_t', 'label' => 'Invierno', 'start_date' => '2026-12-15', 'end_date' => '2026-12-31']);
    $fin = t_rule(['id' => 'r_f', 'label' => 'Fin de anio', 'type' => 'date', 'start_date' => '2026-12-20', 'end_date' => '2026-12-24']);
    $navidad = t_rule(['id' => 'r_n', 'label' => 'Navidad', 'type' => 'blocked', 'start_date' => '2026-12-25', 'end_date' => '2026-12-25']);
    $otroPaquete = t_rule(['id' => 'r_o', 'type' => 'date', 'package_ids' => '["vuelo-privado"]', 'start_date' => '2026-12-16', 'end_date' => '2026-12-16']);
    $temporada['package_ids'] = ['vuelo-compartido'];
    $all = [$temporada, $fin, $navidad, $otroPaquete];

    assert_same([
        ['from' => '2026-12-20', 'to' => '2026-12-24', 'other_id' => 'r_f', 'other_label' => 'Fin de anio', 'outcome' => 'other_wins'],
        ['from' => '2026-12-25', 'to' => '2026-12-25', 'other_id' => 'r_n', 'other_label' => 'Navidad', 'outcome' => 'no_flight'],
    ], pricing_overlaps($temporada, $all));

    assert_same([
        ['from' => '2026-12-20', 'to' => '2026-12-24', 'other_id' => 'r_t', 'other_label' => 'Invierno', 'outcome' => 'this_wins'],
    ], pricing_overlaps($fin, $all));
}

function test_coincidencias_con_dias_de_semana_y_mismo_tipo(): void
{
    // Sabados de diciembre dentro de una temporada: tramos de un dia.
    $temp = t_rule(['id' => 'r_t', 'start_date' => '2026-12-01', 'end_date' => '2026-12-13']);
    $sab = t_rule(['id' => 'r_s', 'label' => 'Sabados', 'type' => 'weekday', 'weekdays' => [6], 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
    $o = pricing_overlaps($temp, [$temp, $sab]);
    assert_same(['2026-12-05', '2026-12-12'], array_column($o, 'from'));
    assert_same(['other_wins', 'other_wins'], array_column($o, 'outcome'));

    $corta = t_rule(['id' => 'r_c', 'label' => 'Puente', 'start_date' => '2026-12-05', 'end_date' => '2026-12-07']);
    $o = pricing_overlaps($temp, [$temp, $corta]);
    assert_same([['from' => '2026-12-05', 'to' => '2026-12-07', 'other_id' => 'r_c', 'other_label' => 'Puente', 'outcome' => 'other_wins']], $o);

    $pausada = t_rule(['id' => 'r_p', 'start_date' => '2026-12-05', 'end_date' => '2026-12-07', 'active' => 0]);
    assert_same([], pricing_overlaps($temp, [$temp, $pausada]));
}

function test_validacion_de_reglas(): void
{
    $known = ['vuelo-compartido', 'vuelo-privado'];
    $ok = [
        'label' => 'Navidad', 'type' => 'season', 'package_ids' => 'all',
        'start_date' => '2026-12-15', 'end_date' => '2027-01-06', 'price_cents' => 265000,
    ];
    assert_same([], pricing_validate_rule($ok, $known));
    assert_same([], pricing_validate_rule(['type' => 'blocked', 'label' => 'Mantenimiento', 'price_cents' => null] + $ok, $known));

    $e = pricing_validate_rule(['label' => '', 'end_date' => '2026-12-01', 'price_cents' => 0, 'deposit_percent' => 101] + $ok, $known);
    assert_same(['label', 'end_date', 'price_cents', 'deposit_percent'], array_keys($e));
    assert_same('La fecha final no puede ser antes de la inicial.', $e['end_date']);

    $e = pricing_validate_rule(['type' => 'weekday', 'weekdays' => [], 'package_ids' => []] + $ok, $known);
    assert_same(['package_ids', 'weekdays'], array_keys($e));
    $e = pricing_validate_rule(['package_ids' => ['otro']] + $ok, $known);
    assert_same(['package_ids'], array_keys($e));
    $e = pricing_validate_rule(['start_date' => '2026-02-30', 'label' => str_repeat('a', 81)] + $ok, $known);
    assert_same(['label', 'start_date'], array_keys($e));
}
