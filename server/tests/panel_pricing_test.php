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
