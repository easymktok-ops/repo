<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/panel_pricing.php';

function test_panel_parse_pesos(): void
{
    assert_same(265000, panel_parse_pesos('2,650'));
    assert_same(265000, panel_parse_pesos('2650'));
    assert_same(265000, panel_parse_pesos('$ 2,650'));
    assert_same(265000, panel_parse_pesos('2650.00'));
    assert_same(0, panel_parse_pesos('0'));
    foreach (['', 'mucho', '26.50', '-5', '2650.5x', '99999999'] as $bad) {
        assert_same(null, panel_parse_pesos($bad), var_export($bad, true));
    }
}

function test_panel_pesos_y_fechas(): void
{
    assert_same('$2,650', panel_pesos(265000));
    assert_same('$1,192.50', panel_pesos(119250));
    assert_same('15 dic 2026 al 6 ene 2027', panel_fecha_rango('2026-12-15', '2027-01-06'));
    assert_same('14 feb 2027', panel_fecha_rango('2027-02-14', '2027-02-14'));
}

function test_panel_texto_de_tarifa_aplicada(): void
{
    $b = ['rule_id' => 'r_1', 'rule_label' => 'Navidad', 'unit_price_cents' => 265000, 'deposit_percent' => 45];
    assert_same('Navidad: $2,650 por persona, anticipo 45 %', panel_tariff_text($b));
    assert_same('Tarifa eliminada: $2,650 por persona, anticipo 45 %', panel_tariff_text(['rule_label' => null] + $b));
    assert_same('Precio base: $2,200 por persona', panel_tariff_text(['rule_id' => null, 'unit_price_cents' => 220000, 'deposit_percent' => null]));
}

function test_panel_formulario_a_tarifa(): void
{
    $v = [
        'id' => '', 'label' => 'Puente', 'type' => 'date', 'start_date' => '2027-02-14', 'end_date' => '', 'weekdays' => [3],
        'packages_mode' => 'some', 'packages' => ['vuelo-privado'], 'price' => '3,000', 'deposit_percent' => '',
    ];
    $r = panel_pricing_form_to_rule($v, false);
    assert_same('2027-02-14', $r['end_date'], 'fecha especial: hasta = desde');
    assert_same(null, $r['weekdays'], 'solo dias de la semana lleva dias');
    assert_same(300000, $r['price_cents']);
    assert_same(['vuelo-privado'], $r['package_ids']);
    assert_same(null, $r['deposit_percent']);
    assert_true(!isset($r['id']));

    $r = panel_pricing_form_to_rule(['price' => 'x', 'deposit_percent' => 'abc', 'type' => 'season', 'packages_mode' => 'all'] + $v, false);
    assert_same(0, $r['price_cents'], 'precio ilegible se rechaza en la validacion');
    assert_same(0, $r['deposit_percent']);
    assert_same('all', $r['package_ids']);

    $b = panel_pricing_form_to_rule(['type' => 'blocked', 'end_date' => ''] + $v, true);
    assert_same(null, $b['price_cents']);
    assert_same('2027-02-14', $b['end_date']);
}

function t_pp_rule(array $o): array
{
    return pricing_normalize_rule(array_merge([
        'id' => 'r_a', 'label' => 'Regla', 'type' => 'season', 'package_ids' => '"all"', 'start_date' => '2026-12-01',
        'end_date' => '2026-12-31', 'weekdays' => null, 'price_cents' => 260000, 'deposit_percent' => null,
        'active' => 1, 'updated_at' => '2026-09-01 10:00:00', 'updated_by' => 't',
    ], $o));
}

function test_panel_avisos_de_coincidencia(): void
{
    $temp = t_pp_rule(['id' => 'r_t', 'label' => 'Invierno', 'start_date' => '2026-12-15', 'end_date' => '2026-12-31']);
    $esp = t_pp_rule(['id' => 'r_e', 'label' => 'Fin de año', 'type' => 'date', 'start_date' => '2026-12-20', 'end_date' => '2026-12-24']);
    $nav = t_pp_rule(['id' => 'r_n', 'label' => 'Navidad', 'type' => 'blocked', 'start_date' => '2026-12-25', 'end_date' => '2026-12-25', 'price_cents' => null]);
    $all = [$temp, $esp, $nav];

    assert_same([
        "Del 20 dic 2026 al 24 dic 2026 esta temporada coincide con 'Fin de año'. Se aplicará el precio de la fecha especial.",
        "El 25 dic 2026 coincide con 'Sin vuelo: Navidad'. Ese día no habrá vuelos.",
    ], panel_overlap_notes($temp, $all));
    assert_same([
        "Del 20 dic 2026 al 24 dic 2026 esta fecha especial coincide con 'Invierno'. Se aplicará el precio de la fecha especial.",
    ], panel_overlap_notes($esp, $all));
    assert_same([
        "El 25 dic 2026 coincide con 'Invierno'. Ese día no habrá vuelos.",
    ], panel_overlap_notes($nav, $all));

    $corta = t_pp_rule(['id' => 'r_c', 'label' => 'Puente', 'start_date' => '2026-12-05', 'end_date' => '2026-12-07']);
    $larga = t_pp_rule(['id' => 'r_l', 'label' => 'Diciembre', 'start_date' => '2026-12-01', 'end_date' => '2026-12-31']);
    assert_same([
        "Del 5 dic 2026 al 7 dic 2026 esta temporada coincide con 'Puente'. Se aplicará el precio de 'Puente', que abarca menos días.",
    ], panel_overlap_notes($larga, [$larga, $corta]));
    assert_same([], panel_overlap_notes($larga, [$larga]));
}

function test_panel_resumen_del_historial(): void
{
    $ctx = ['packages' => ['vuelo-compartido' => ['slug' => 'vuelo-compartido', 'title' => ['es' => 'Vuelo compartido']]]];
    $r = function (string $action, $b, $a, string $entity = 'rule', string $id = 'r_a') {
        return ['action' => $action, 'entity' => $entity, 'entity_id' => $id, 'before' => $b, 'after' => $a];
    };
    $nav = ['label' => 'Navidad', 'type' => 'season', 'price_cents' => 260000, 'deposit_percent' => null, 'start_date' => '2026-12-15',
        'end_date' => '2027-01-06', 'package_ids' => 'all', 'weekdays' => null];

    assert_same("Creó la temporada 'Navidad'", panel_audit_summary($r('create', null, $nav), $ctx));
    assert_same("Cambió el precio de 'Navidad' de \$2,600 a \$2,900", panel_audit_summary($r('update', $nav, ['price_cents' => 290000] + $nav), $ctx));
    $conPct = ['deposit_percent' => 40] + $nav;
    assert_same("Fijó el anticipo de 'Navidad' en 50 %", panel_audit_summary($r('update', $nav, ['deposit_percent' => 50] + $nav), $ctx));
    assert_same("Cambió el anticipo de 'Navidad' de 40 % a 50 %", panel_audit_summary($r('update', $conPct, ['deposit_percent' => 50] + $nav), $ctx));
    assert_same("Quitó el anticipo propio de 'Navidad' (usa el del paquete)", panel_audit_summary($r('update', $conPct, $nav), $ctx));
    assert_same("Cambió las fechas de 'Navidad' a 1 dic 2026 al 6 ene 2027", panel_audit_summary($r('update', $nav, ['start_date' => '2026-12-01'] + $nav), $ctx));
    assert_same("Editó 'Navidad'", panel_audit_summary($r('update', $nav, $nav), $ctx));
    assert_same("Pausó 'Navidad'", panel_audit_summary($r('deactivate', $nav, $nav), $ctx));
    assert_same("Activó 'Navidad'", panel_audit_summary($r('activate', $nav, $nav), $ctx));
    assert_same("Eliminó la temporada 'Navidad'", panel_audit_summary($r('delete', $nav, null), $ctx));
    assert_same("Volvió a abrir 'Mantenimiento'", panel_audit_summary($r('delete', ['label' => 'Mantenimiento', 'type' => 'blocked'], null), $ctx));
    $pk = function ($n) {
        return ['package_slug' => 'vuelo-compartido', 'default_deposit_percent' => $n];
    };
    assert_same('Cambió el anticipo de Vuelo compartido de 45 % a 50 %', panel_audit_summary($r('deposit_update', $pk(45), $pk(50), 'package', 'vuelo-compartido'), $ctx));
    assert_same('Fijó el anticipo de Vuelo compartido en 45 %', panel_audit_summary($r('deposit_update', null, $pk(45), 'package', 'vuelo-compartido'), $ctx));
}
