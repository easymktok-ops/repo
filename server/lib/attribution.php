<?php
/**
 * Atribucion de primer contacto de cada reserva (de donde llego el cliente).
 *
 * El navegador captura UTM, referrer, pagina de entrada, idioma y fecha del primer contacto
 * (src/lib/attribution.ts + Analytics.astro) y los manda con create-checkout-session.php. Aqui:
 *  - attribution_clean(): limpia y recorta lo que llega del navegador. Nada es obligatorio.
 *  - attribution_origin(): deriva la etiqueta "Origen" que muestra el panel.
 *
 * Interruptor: $config['attribution']['enabled'] = false deja de guardar estos campos (la reserva
 * se crea igual). Funciones puras, sin dependencias: se prueban en tests/attribution_test.php.
 */

declare(strict_types=1);

/** Columnas de bookings que guarda la atribucion (todas NULL por defecto). */
const ATTRIBUTION_FIELDS = [
    'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
    'referrer', 'landing_page', 'idioma', 'first_touch_at',
];

/** Texto libre: sin caracteres de control, sin espacios sobrantes, maximo $max caracteres. */
function attribution_text($v, int $max = 255): ?string
{
    if (!is_string($v) && !is_numeric($v)) {
        return null;
    }
    $s = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $v));
    if ($s === '' || !preg_match('//u', $s)) {
        return null;
    }
    return mb_substr($s, 0, $max);
}

/**
 * Limpia los campos de atribucion del cuerpo de la peticion. Devuelve SIEMPRE las 9 llaves
 * (null si no vino o no es valido).
 *
 * @return array<string, ?string>
 */
function attribution_clean(array $body): array
{
    $out = array_fill_keys(ATTRIBUTION_FIELDS, null);
    foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $k) {
        $out[$k] = attribution_text($body[$k] ?? null);
    }

    $ref = attribution_text($body['referrer'] ?? null);
    $out['referrer'] = ($ref !== null && preg_match('#^https?://#i', $ref)) ? $ref : null;

    $landing = attribution_text($body['landing_page'] ?? null);
    $out['landing_page'] = ($landing !== null && $landing[0] === '/') ? $landing : null;

    $lang = $body['idioma'] ?? null;
    $out['idioma'] = in_array($lang, ['es', 'en'], true) ? $lang : null;

    // ISO 8601 con zona, p. ej. 2026-10-14T09:30:00-06:00.
    $ft = attribution_text($body['first_touch_at'] ?? null, 40);
    $out['first_touch_at'] = ($ft !== null && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?([+-]\d{2}:\d{2}|Z)$/', $ft)) ? $ft : null;

    return $out;
}

/** Dominio sin "www." de una URL, o null. */
function attribution_host(?string $url): ?string
{
    if ($url === null || $url === '') {
        return null;
    }
    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        return null;
    }
    return preg_replace('/^www\./i', '', strtolower($host));
}

function attribution_is_search_engine(?string $host): bool
{
    if ($host === null) {
        return false;
    }
    return (bool) preg_match(
        '/(^|\.)(google\.[a-z.]+|bing\.com|yahoo\.[a-z.]+|duckduckgo\.com|ecosia\.org|yandex\.[a-z.]+|baidu\.com|search\.brave\.com|startpage\.com|qwant\.com)$/',
        $host
    );
}

/**
 * Etiqueta "Origen" de una reserva, en este orden:
 *  a) gclid/gbraid/wbraid, o utm_source=google + utm_medium=cpc -> "Google Ads" (+ campana)
 *  b) referrer de buscador sin click ID                          -> "Orgánico"
 *  c) sin referrer ni UTM                                        -> "Directo"
 *  d) cualquier otro                                             -> "Otro" (+ dominio o utm_source)
 * Reservas anteriores a la captura (sin ningun dato) -> "—".
 */
function attribution_origin(array $r): string
{
    $v = static function (string $k) use ($r): string {
        return trim((string) ($r[$k] ?? ''));
    };

    $hasClick = $v('gclid') !== '' || $v('gbraid') !== '' || $v('wbraid') !== '';
    $hasUtm = $v('utm_source') !== '' || $v('utm_medium') !== '' || $v('utm_campaign') !== '';
    $captured = $hasClick || $hasUtm || $v('referrer') !== '' || $v('landing_page') !== '' || $v('first_touch_at') !== '';
    if (!$captured) {
        return '—';
    }

    if ($hasClick || (strtolower($v('utm_source')) === 'google' && strtolower($v('utm_medium')) === 'cpc')) {
        return 'Google Ads' . ($v('utm_campaign') !== '' ? ' · ' . $v('utm_campaign') : '');
    }

    $host = attribution_host($v('referrer'));
    if (attribution_is_search_engine($host)) {
        return 'Orgánico';
    }
    if ($host === null && !$hasUtm) {
        return 'Directo';
    }
    $detail = $host ?? $v('utm_source');
    return 'Otro' . ($detail !== '' ? ' · ' . $detail : '');
}

/** Todos los campos crudos, para el tooltip y el detalle del panel. */
function attribution_raw(array $r): array
{
    $out = [];
    foreach (array_merge(['gclid', 'gbraid', 'wbraid'], ATTRIBUTION_FIELDS) as $k) {
        $out[$k] = (string) ($r[$k] ?? '');
    }
    return $out;
}
