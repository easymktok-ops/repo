<?php
/**
 * POST /api/create-checkout-session.php
 * -----------------------------------------------------------------------------
 * Recibe la seleccion del pasajero (slug, pasajeros, modo, contacto), RECALCULA
 * el monto del lado del servidor (autoridad = catalog.json), crea una reserva
 * 'pending' y una Stripe Checkout Session, y devuelve la URL de pago.
 *
 * El cliente NUNCA envia precios. La reserva pasa a 'paid' solo por webhook.
 *
 * Tarifas por fecha (config pricing.rules_enabled): el precio y el anticipo
 * salen de resolve_price() para flightDate, leyendo las reglas en este momento.
 * expectedUnitPrice / expectedDeposit (centavos por persona) son solo un aviso:
 * si no coinciden se responde 409 price_changed y no se cobra. Con la bandera
 * apagada, o con el paquete sin % de anticipo, el flujo es el de siempre.
 */

declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/pricing.php';
require __DIR__ . '/../../lib/stripe.php';

$config = load_config();

// --- CORS (por defecto mismo origen; refleja solo origenes permitidos) -------
$siteOrigin = rtrim((string) $config['site_url'], '/');
// Host de este sitio (dominio-agnostico: sale de site_url, nunca hardcodeado).
// Se etiqueta en la metadata de Stripe para que, cuando una cuenta de Stripe es
// compartida por varios sitios, cada webhook procese SOLO sus propias ventas.
$originHost = parse_url($siteOrigin, PHP_URL_HOST) ?: $siteOrigin;
$allowed = array_filter(array_merge([$siteOrigin], $config['allowed_origins'] ?? []));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(405, ['error' => 'Metodo no permitido']);
}

// --- Parseo y validacion de entrada -----------------------------------------
$body = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($body)) {
    json_response(400, ['error' => 'Cuerpo invalido']);
}

$slug = trim((string) ($body['packageSlug'] ?? ''));
$mode = (string) ($body['mode'] ?? '');
$passengers = (int) ($body['passengers'] ?? 0);
$locale = (($body['locale'] ?? 'es') === 'en') ? 'en' : 'es';
$name = trim((string) ($body['name'] ?? ''));
$email = trim((string) ($body['email'] ?? ''));
$phone = trim((string) ($body['phone'] ?? ''));
$flightDate = trim((string) ($body['flightDate'] ?? ''));
$notes = mb_substr(trim((string) ($body['notes'] ?? '')), 0, 500);
$expectedUnit = checkout_int_or_null($body['expectedUnitPrice'] ?? null);
$expectedDeposit = checkout_int_or_null($body['expectedDeposit'] ?? null);

$errors = [];
if ($slug === '') {
    $errors[] = 'Falta el paquete.';
}
if (!in_array($mode, ['full', 'deposit'], true)) {
    $errors[] = 'Modo de pago invalido.';
}
if ($name === '' || mb_strlen($name) < 2) {
    $errors[] = 'Nombre invalido.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Correo invalido.';
}
if (preg_replace('/\D+/', '', $phone) === '' || strlen(preg_replace('/\D+/', '', $phone)) < 8) {
    $errors[] = 'Telefono invalido.';
}
if ($flightDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $flightDate)) {
    $errors[] = 'Fecha invalida.';
}
if ($errors) {
    json_response(422, ['error' => implode(' ', $errors)]);
}

$resolved = null;
try {
    $catalog = load_catalog((string) $config['catalog_path']);
    $pkg = find_bookable_package($catalog, $slug);
    if (!$pkg) {
        json_response(404, ['error' => 'Paquete no disponible para reservar en linea.']);
    }
    if (!empty($config['pricing']['rules_enabled'])) {
        $resolved = checkout_price_for_date($config, $catalog, $slug, $flightDate, $expectedUnit, $expectedDeposit);
    }
    $charge = compute_charge($config, $pkg, $mode, $passengers, $resolved);
} catch (InvalidArgumentException $e) {
    json_response(422, ['error' => $e->getMessage()]);
} catch (Throwable $e) {
    log_line('checkout', 'error de catalogo/precio', ['msg' => $e->getMessage()]);
    json_response(500, ['error' => 'No se pudo calcular el precio. Intenta de nuevo.']);
}

$title = (string) ($charge['title'][$locale] ?? ($charge['title']['es'] ?? $slug));
$reference = 'AERO-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 7));

// --- Persistir la reserva 'pending' -----------------------------------------
try {
    $pdo = db($config);
    $pricingCols = '';
    $pricingVals = '';
    $pricingParams = [];
    if ($resolved !== null) {
        $pricingCols = ', unit_price_cents, deposit_percent, rule_id, pricing_version';
        $pricingVals = ', :upc, :dpct, :rule, :pver';
        $pricingParams = [
            ':upc'  => $charge['unit_price_cents'],
            ':dpct' => $charge['deposit_percent'],
            ':rule' => $charge['rule_id'],
            ':pver' => $resolved['pricing_version'],
        ];
    }
    $stmt = $pdo->prepare(
        'INSERT INTO bookings
            (reference, status, locale, package_slug, package_title, passengers,
             flight_date, mode, currency, price_per_person, amount_now_cents,
             total_full_cents, balance_cents, customer_name, customer_email,
             customer_phone, notes, created_at' . $pricingCols . ')
         VALUES
            (:ref, \'pending\', :locale, :slug, :title, :pax, :fdate, :mode, :cur,
             :ppp, :now, :full, :bal, :name, :email, :phone, :notes, :created_at' . $pricingVals . ')'
    );
    $stmt->execute([
        ':ref' => $reference,
        ':locale' => $locale,
        ':slug' => $slug,
        ':title' => $title,
        ':pax' => $passengers,
        ':fdate' => $flightDate !== '' ? $flightDate : null,
        ':mode' => $mode,
        ':cur' => $charge['currency'],
        ':ppp' => $charge['price_per_person'],
        ':now' => $charge['amount_now_cents'],
        ':full' => $charge['total_full_cents'],
        ':bal' => $charge['balance_cents'],
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':notes' => $notes !== '' ? $notes : null,
        ':created_at' => date('Y-m-d H:i:s'),
    ] + $pricingParams);
    $bookingId = (int) $pdo->lastInsertId();
} catch (Throwable $e) {
    log_line('checkout', 'error al guardar reserva', ['msg' => $e->getMessage()]);
    json_response(500, ['error' => 'No se pudo iniciar la reserva. Intenta de nuevo.']);
}

// --- Crear la Stripe Checkout Session ---------------------------------------
$successPath = $locale === 'en' ? '/en/reserva-confirmada' : '/reserva-confirmada';
$cancelPath  = $locale === 'en' ? '/en/reservar' : '/reservar';

$productName = $mode === 'deposit'
    ? ($locale === 'en' ? "Deposit - {$title}" : "Anticipo - {$title}")
    : $title;

$params = [
    'mode' => 'payment',
    'locale' => $locale,
    'customer_email' => $email,
    'client_reference_id' => $reference,
    'success_url' => $siteOrigin . $successPath . '?ref=' . $reference
        . '&amt=' . ($charge['amount_now_cents'] / 100)
        . '&cur=' . $charge['currency']
        . '&session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $siteOrigin . $cancelPath . '?canceled=1&ref=' . $reference,
    'line_items' => [[
        'quantity' => $charge['quantity'],
        'price_data' => [
            'currency' => strtolower($charge['currency']),
            'unit_amount' => $charge['unit_amount_cents'],
            'product_data' => [
                'name' => $productName,
                'description' => $charge['unit_label'] . ' - ' . $passengers . ' pax',
            ],
        ],
    ]],
    'metadata' => [
        'reference' => $reference,
        'booking_id' => (string) $bookingId,
        'mode' => $mode,
        'passengers' => (string) $passengers,
        'origin_site' => $originHost,
    ],
    'payment_intent_data' => [
        'metadata' => [
            'reference' => $reference,
            'booking_id' => (string) $bookingId,
        ],
    ],
];
if ($resolved !== null) {
    $pricingMeta = [
        'date'           => $flightDate,
        'packageId'      => $slug,
        'ruleId'         => $charge['rule_id'] ?? 'base',
        'fullPrice'      => (string) $charge['total_full_cents'],
        'deposit'        => (string) $charge['deposit_total_cents'],
        'balance'        => (string) $charge['balance_cents'],
        'unitPrice'      => (string) $charge['unit_price_cents'],
        'depositPercent' => (string) $charge['deposit_percent'],
        'pricingVersion' => (string) $resolved['pricing_version'],
    ];
    $params['metadata'] += $pricingMeta;
    $params['payment_intent_data']['metadata'] += $pricingMeta;
}

try {
    $session = stripe_create_checkout_session((string) $config['stripe_secret_key'], $params);
} catch (Throwable $e) {
    log_line('checkout', 'error creando sesion Stripe', ['ref' => $reference, 'msg' => $e->getMessage()]);
    json_response(502, ['error' => 'No se pudo conectar con el proveedor de pago. Intenta de nuevo.']);
}

// Guarda el id de sesion para conciliar el webhook.
try {
    $pdo->prepare('UPDATE bookings SET stripe_session_id = :sid WHERE id = :id')
        ->execute([':sid' => $session['id'], ':id' => $bookingId]);
} catch (Throwable $e) {
    log_line('checkout', 'no se pudo guardar session_id', ['ref' => $reference, 'msg' => $e->getMessage()]);
}

json_response(200, [
    'url' => $session['url'],
    'reference' => $reference,
]);

/** Entero enviado por el cliente (solo informativo), o null si no es numero. */
function checkout_int_or_null($v): ?int
{
    if (is_int($v)) {
        return $v;
    }
    return is_string($v) && preg_match('/^\d{1,9}\z/', $v) ? (int) $v : null;
}

/**
 * Tarifas por fecha. Devuelve el resultado 'available' de resolve_price() mas
 * 'pricing_version', o null para seguir con el flujo anterior (esquema no
 * disponible o paquete sin % de anticipo). Responde directamente 422 si la
 * fecha no es vendible, 409 si no hay vuelo ese dia o si el precio cambio.
 */
function checkout_price_for_date(array $config, array $catalog, string $slug, string $flightDate, ?int $expectedUnit, ?int $expectedDeposit): ?array
{
    require_once __DIR__ . '/../../lib/pricing_store.php';

    $pdo = db($config);
    if (!ensure_pricing_schema($pdo)) {
        return null;
    }
    $pkg = pricing_load_package_pricing($pdo, $catalog)[$slug] ?? null;
    if ($pkg === null || $pkg['default_deposit_percent'] === null) {
        return null;
    }

    $today = pricing_today_mx();
    $minDate = pricing_add_days($today, 1);
    $maxDate = pricing_add_days($today, max(1, (int) ($config['pricing']['max_advance_days'] ?? 365)));
    if (!pricing_valid_ymd($flightDate) || $flightDate < $minDate || $flightDate > $maxDate) {
        json_response(422, ['error' => 'Elige una fecha de vuelo valida.', 'code' => 'date_invalid']);
    }

    $r = resolve_price($flightDate, $pkg, pricing_load_rules($pdo));
    if ($r['status'] === 'blocked') {
        json_response(409, ['error' => 'Esta fecha ya no está disponible. Elige otra.', 'code' => 'date_blocked']);
    }
    if ($r['status'] !== 'available') {
        return null;
    }
    if (($expectedUnit !== null && $expectedUnit !== $r['price'])
        || ($expectedDeposit !== null && $expectedDeposit !== $r['deposit'])) {
        json_response(409, [
            'error'   => 'El precio de esta fecha cambió. Revisa el nuevo total antes de pagar.',
            'code'    => 'price_changed',
            'current' => ['price' => $r['price'], 'deposit' => $r['deposit'], 'balance' => $r['balance']],
        ]);
    }
    $r['pricing_version'] = pricing_version($pdo, $catalog);
    return $r;
}
