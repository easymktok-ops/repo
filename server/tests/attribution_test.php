<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/attribution.php';

function test_origen_google_ads_con_campana(): void
{
    assert_same('Google Ads · prueba', attribution_origin([
        'utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'prueba', 'gclid' => 'TEST123',
    ]));
    assert_same('Google Ads', attribution_origin(['gbraid' => 'GB1', 'landing_page' => '/']));
    assert_same('Google Ads · otoño', attribution_origin(['utm_source' => 'Google', 'utm_medium' => 'CPC', 'utm_campaign' => 'otoño']));
}

function test_origen_organico_directo_otro(): void
{
    assert_same('Orgánico', attribution_origin(['referrer' => 'https://www.google.com.mx/', 'landing_page' => '/']));
    assert_same('Orgánico', attribution_origin(['referrer' => 'https://duckduckgo.com/', 'landing_page' => '/']));
    assert_same('Directo', attribution_origin(['landing_page' => '/vuelos', 'first_touch_at' => '2026-10-14T09:00:00-06:00']));
    assert_same('Otro · m.facebook.com', attribution_origin(['referrer' => 'https://m.facebook.com/x', 'landing_page' => '/']));
    assert_same('Otro · newsletter', attribution_origin(['utm_source' => 'newsletter', 'landing_page' => '/']));
}

function test_origen_reserva_anterior_sin_datos(): void
{
    assert_same('—', attribution_origin([]));
    assert_same('—', attribution_origin(['utm_source' => null, 'referrer' => '', 'gclid' => null]));
}

function test_limpieza_recorta_y_valida(): void
{
    $c = attribution_clean([
        'utm_source' => "  google\n ",
        'utm_campaign' => str_repeat('a', 400),
        'referrer' => 'javascript:alert(1)',
        'landing_page' => 'https://otro.com/',
        'idioma' => 'fr',
        'first_touch_at' => 'ayer',
        'extra' => 'ignorado',
    ]);
    assert_same('google', $c['utm_source']);
    assert_same(255, mb_strlen((string) $c['utm_campaign']));
    assert_same(null, $c['referrer']);
    assert_same(null, $c['landing_page']);
    assert_same(null, $c['idioma']);
    assert_same(null, $c['first_touch_at']);
    assert_same(ATTRIBUTION_FIELDS, array_keys($c));

    $ok = attribution_clean([
        'referrer' => 'https://www.google.com/',
        'landing_page' => '/en/vuelos?utm_source=google',
        'idioma' => 'en',
        'first_touch_at' => '2026-10-14T09:30:00-06:00',
    ]);
    assert_same('https://www.google.com/', $ok['referrer']);
    assert_same('/en/vuelos?utm_source=google', $ok['landing_page']);
    assert_same('en', $ok['idioma']);
    assert_same('2026-10-14T09:30:00-06:00', $ok['first_touch_at']);
}

function test_limpieza_sin_datos_no_falla(): void
{
    $c = attribution_clean([]);
    assert_same(array_fill_keys(ATTRIBUTION_FIELDS, null), $c);
    $c = attribution_clean(['utm_source' => ['array'], 'referrer' => 123]);
    assert_same(null, $c['utm_source']);
    assert_same(null, $c['referrer']);
}
