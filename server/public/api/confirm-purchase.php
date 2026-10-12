<?php
/**
 * GET /api/confirm-purchase.php?ref=AERO-XXXXXXX&session_id=cs_...
 * -----------------------------------------------------------------------------
 * Lo llama /reserva-confirmada (es/en) para saber si debe medir la conversion.
 * Responde paid:true SOLO si la reserva existe, el session_id es el suyo y el
 * pago esta confirmado: por el webhook (status 'paid') o, si el webhook aun no
 * llega (corre en paralelo a la redireccion), consultando la sesion en Stripe.
 *
 * El monto sale de la base, nunca de la URL. No devuelve datos personales.
 */

declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/stripe.php';

header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    json_response(405, ['paid' => false]);
}

$ref = (string) ($_GET['ref'] ?? '');
$sid = (string) ($_GET['session_id'] ?? '');
if (!preg_match('/^AERO-[A-Z0-9]{4,12}$/', $ref) || !preg_match('/^cs_(test|live)_[A-Za-z0-9]{8,255}$/', $sid)) {
    json_response(400, ['paid' => false]);
}

$config = load_config();

try {
    $pdo = db($config);
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE reference = :ref AND stripe_session_id = :sid LIMIT 1');
    $stmt->execute([':ref' => $ref, ':sid' => $sid]);
    $b = $stmt->fetch();
} catch (Throwable $e) {
    log_line('confirm', 'error de base', ['ref' => $ref, 'msg' => $e->getMessage()]);
    json_response(500, ['paid' => false]);
}
if (!$b) {
    json_response(404, ['paid' => false]);
}

$paid = $b['status'] === 'paid';
if (!$paid && $b['status'] === 'pending') {
    // El webhook puede tardar unos segundos: confirmamos directo con Stripe.
    try {
        $s = stripe_get_session((string) $config['stripe_secret_key'], $sid);
        $paid = ($s['payment_status'] ?? '') === 'paid' && ($s['client_reference_id'] ?? '') === $ref;
    } catch (Throwable $e) {
        log_line('confirm', 'no se pudo consultar la sesion', ['ref' => $ref, 'msg' => $e->getMessage()]);
    }
}
if (!$paid) {
    json_response(200, ['paid' => false]);
}

$value = (int) $b['amount_now_cents'] / 100;
$qty = max(1, (int) $b['passengers']);
json_response(200, [
    'paid' => true,
    'transaction_id' => $b['reference'],
    'value' => $value,
    'currency' => (string) $b['currency'],
    'payment_type' => $b['mode'] === 'deposit' ? 'deposit' : 'full',
    'items' => [[
        'item_id' => (string) $b['package_slug'],
        'item_name' => (string) $b['package_title'],
        'quantity' => $qty,
        'price' => round($value / $qty, 2),
    ]],
]);
