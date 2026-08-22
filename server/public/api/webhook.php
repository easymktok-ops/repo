<?php
/**
 * POST /api/webhook.php  (endpoint de webhooks de Stripe)
 * -----------------------------------------------------------------------------
 * Verifica la firma, marca la reserva como pagada (idempotente) y ENCOLA las
 * notificaciones en el outbox. No envia correos aqui: responde 200 rapido y el
 * Cron hace el envio con reintentos.
 *
 * Configura este endpoint en Stripe (Desarrolladores -> Webhooks) escuchando
 * el evento `checkout.session.completed`. Copia el signing secret (whsec_...) a
 * STRIPE_WEBHOOK_SECRET en config.php.
 */

declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/stripe.php';
require __DIR__ . '/../../lib/outbox.php';

$config = load_config();

$payload = file_get_contents('php://input') ?: '';
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!stripe_verify_webhook($payload, $sig, (string) $config['stripe_webhook_secret'])) {
    log_line('webhook', 'firma invalida');
    json_response(400, ['error' => 'firma invalida']);
}

$event = json_decode($payload, true);
$type = $event['type'] ?? '';

// Solo nos interesa el pago completado del Checkout.
if ($type !== 'checkout.session.completed') {
    // 200 para que Stripe no reintente eventos que ignoramos a proposito.
    json_response(200, ['ignored' => $type]);
}

$session = $event['data']['object'] ?? [];
$sessionId = (string) ($session['id'] ?? '');
$reference = (string) ($session['client_reference_id'] ?? ($session['metadata']['reference'] ?? ''));
$paymentIntent = (string) ($session['payment_intent'] ?? '');

if ($sessionId === '' && $reference === '') {
    json_response(400, ['error' => 'sesion sin identificador']);
}

try {
    $pdo = db($config);

    // Localiza la reserva por session_id (preferente) o por referencia.
    $stmt = $pdo->prepare(
        'SELECT * FROM bookings WHERE stripe_session_id = :sid OR reference = :ref LIMIT 1'
    );
    $stmt->execute([':sid' => $sessionId, ':ref' => $reference]);
    $booking = $stmt->fetch();

    if (!$booking) {
        // No la encontramos: registrar y devolver 200 (no sirve reintentar).
        log_line('webhook', 'reserva no encontrada', ['session' => $sessionId, 'ref' => $reference]);
        json_response(200, ['warning' => 'reserva no encontrada']);
    }

    // Idempotencia: si ya estaba pagada, no re-encolar notificaciones.
    if ($booking['status'] === 'paid') {
        json_response(200, ['ok' => true, 'already' => true]);
    }

    // Marca pagada.
    $pdo->prepare(
        'UPDATE bookings
            SET status = \'paid\', paid_at = :paid_at,
                stripe_session_id = COALESCE(stripe_session_id, :sid),
                stripe_payment_intent = :pi
          WHERE id = :id'
    )->execute([
        ':paid_at' => date('Y-m-d H:i:s'),
        ':sid' => $sessionId,
        ':pi' => $paymentIntent !== '' ? $paymentIntent : null,
        ':id' => $booking['id'],
    ]);

    $bookingId = (int) $booking['id'];

    // Encola notificaciones: confirmacion al cliente + alerta al admin.
    enqueue_notification($pdo, $bookingId, 'email', 'booking_confirmation', (string) $booking['customer_email']);

    $adminEmail = $config['notifications']['admin_email'] ?? '';
    if ($adminEmail) {
        enqueue_notification($pdo, $bookingId, 'email', 'booking_admin_alert', $adminEmail);
    }

    // WhatsApp interno opcional (si esta configurado meta_cloud).
    $adminWa = $config['notifications']['admin_whatsapp'] ?? '';
    if ($adminWa && ($config['notifications']['whatsapp_provider'] ?? 'log') !== 'log') {
        enqueue_notification($pdo, $bookingId, 'whatsapp', 'booking_admin_alert', $adminWa);
    }

    log_line('webhook', 'reserva pagada + notificaciones encoladas', ['ref' => $booking['reference']]);
    json_response(200, ['ok' => true]);
} catch (Throwable $e) {
    log_line('webhook', 'error procesando', ['msg' => $e->getMessage()]);
    // 500 para que Stripe reintente el webhook.
    json_response(500, ['error' => 'error interno']);
}
