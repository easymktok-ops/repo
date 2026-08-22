<?php
/**
 * POST /api/create-checkout-session.php
 * -----------------------------------------------------------------------------
 * Recibe la seleccion del pasajero (slug, pasajeros, modo, contacto), RECALCULA
 * el monto del lado del servidor (autoridad = catalog.json), crea una reserva
 * 'pending' y una Stripe Checkout Session, y devuelve la URL de pago.
 *
 * El cliente NUNCA envia precios. La reserva pasa a 'paid' solo por webhook.
 */

declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/pricing.php';
require __DIR__ . '/../../lib/stripe.php';

$config = load_config();

// --- CORS (por defecto mismo origen; refleja solo origenes permitidos) -------
$siteOrigin = rtrim((string) $config['site_url'], '/');
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

try {
    $catalog = load_catalog((string) $config['catalog_path']);
    $pkg = find_bookable_package($catalog, $slug);
    if (!$pkg) {
        json_response(404, ['error' => 'Paquete no disponible para reservar en linea.']);
    }
    $charge = compute_charge($config, $pkg, $mode, $passengers);
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
    $stmt = $pdo->prepare(
        'INSERT INTO bookings
            (reference, status, locale, package_slug, package_title, passengers,
             flight_date, mode, currency, price_per_person, amount_now_cents,
             total_full_cents, balance_cents, customer_name, customer_email,
             customer_phone, notes, created_at)
         VALUES
            (:ref, \'pending\', :locale, :slug, :title, :pax, :fdate, :mode, :cur,
             :ppp, :now, :full, :bal, :name, :email, :phone, :notes, :created_at)'
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
    ]);
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
    ],
    'payment_intent_data' => [
        'metadata' => [
            'reference' => $reference,
            'booking_id' => (string) $bookingId,
        ],
    ],
];

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
