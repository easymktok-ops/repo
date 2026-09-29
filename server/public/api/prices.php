<?php
/**
 * GET /api/prices.php?packageId=slug&from=YYYY-MM-DD&to=YYYY-MM-DD[&preview=1]
 * -----------------------------------------------------------------------------
 * Precio por persona de cada dia (centavos MXN) para el calendario publico y
 * la vista previa del panel. No calcula nada propio: usa resolve_price(), la
 * misma funcion que el checkout.
 *
 * - Bandera pricing.rules_enabled en false (y sin preview): {"enabled":false}
 *   y el sitio sigue con el flujo anterior.
 * - preview=1 exige sesion del panel, ignora la bandera y agrega la regla que
 *   aplica a cada dia.
 */

declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/pricing.php';

$config = load_config();
$pricingCfg = $config['pricing'] ?? [];
$rulesOn = !empty($pricingCfg['rules_enabled']);
$preview = ($_GET['preview'] ?? '') === '1';

// --- CORS (mismo patron que el checkout; solo GET) ---------------------------
$siteOrigin = rtrim((string) $config['site_url'], '/');
$allowed = array_filter(array_merge([$siteOrigin], $config['allowed_origins'] ?? []));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

/** Solo las respuestas 200 publicas se pueden cachear. */
function prices_ok(array $payload, bool $preview, array $pricingCfg): void
{
    $s = max(0, (int) ($pricingCfg['cache_seconds'] ?? 60));
    if (!$preview && $s > 0) {
        header('Cache-Control: public, max-age=' . $s);
    }
    json_response(200, $payload);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    json_response(405, ['error' => 'Metodo no permitido']);
}

if ($preview) {
    require __DIR__ . '/../../lib/panel_auth.php';
    if (empty($config['panel']['enabled']) || panel_session_user() === null) {
        json_response(401, ['error' => 'Inicia sesion en el panel.', 'code' => 'unauthorized']);
    }
    session_write_close();
} elseif (!$rulesOn) {
    prices_ok(['enabled' => false], false, $pricingCfg);
}

require __DIR__ . '/../../lib/pricing_store.php';

// --- Parametros --------------------------------------------------------------
$slug = (string) ($_GET['packageId'] ?? '');
$from = (string) ($_GET['from'] ?? '');
$to = (string) ($_GET['to'] ?? '');
if (!preg_match('/^[a-z0-9-]{1,80}\z/', $slug) || !pricing_valid_ymd($from) || !pricing_valid_ymd($to)
    || $to < $from || pricing_days_between($from, $to) + 1 > 93) {
    json_response(400, ['error' => 'Parametros invalidos.', 'code' => 'bad_params']);
}

try {
    $catalog = load_catalog((string) $config['catalog_path']);
    if (!find_bookable_package($catalog, $slug)) {
        json_response(404, ['error' => 'Paquete no disponible.', 'code' => 'package_not_found']);
    }
    $pdo = db($config);
    if (!ensure_pricing_schema($pdo)) {
        prices_ok(['enabled' => false], $preview, $pricingCfg);
    }
    $pkg = pricing_load_package_pricing($pdo, $catalog)[$slug];
    if ($pkg['default_deposit_percent'] === null) {
        // Paquete sin % de anticipo: se cobra con el flujo anterior.
        prices_ok(['enabled' => false, 'reason' => 'unconfigured'], $preview, $pricingCfg);
    }
    $rules = pricing_load_rules($pdo);
    $version = pricing_version($pdo, $catalog);
} catch (Throwable $e) {
    log_line('prices', 'error al resolver precios', ['slug' => $slug, 'msg' => $e->getMessage()]);
    json_response(500, ['error' => 'No se pudieron cargar los precios.', 'code' => 'server_error']);
}

$today = pricing_today_mx();
$minDate = pricing_add_days($today, 1);
$maxDate = pricing_add_days($today, max(1, (int) ($pricingCfg['max_advance_days'] ?? 365)));

$byId = [];
foreach ($rules as $r) {
    $byId[$r['id']] = $r;
}

$days = [];
foreach (resolve_range($from, $to, $pkg, $rules, $minDate, $maxDate) as $d => $res) {
    $day = ['status' => $res['status']];
    if ($res['status'] === 'available') {
        $day['price'] = $res['price'];
        $day['deposit'] = $res['deposit'];
        $day['balance'] = $res['balance'];
    }
    if ($preview && $res['status'] !== 'unavailable') {
        $rule = isset($res['rule_id']) ? ($byId[$res['rule_id']] ?? null) : null;
        $day['ruleType'] = $rule !== null ? $rule['type'] : 'base';
        $day['ruleLabel'] = $rule !== null ? $rule['label'] : null;
        if ($res['status'] === 'available') {
            $day['depositPercent'] = $res['deposit_percent'];
        }
    }
    $days[$d] = $day;
}

prices_ok([
    'enabled'        => true,
    'currency'       => $pkg['currency'],
    'pricingVersion' => $version,
    'minDate'        => $minDate,
    'maxDate'        => $maxDate,
    'days'           => $days,
], $preview, $pricingCfg);
