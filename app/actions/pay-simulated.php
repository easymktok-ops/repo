<?php
declare(strict_types=1);

$gateway = Payments::provider() === 'simulated' ? Payments::gateway('simulated') : null;
if (!($gateway instanceof SimulatedGateway) || !$gateway->isReady()) {
    http_response_code(404);
    view('404');
    return;
}

$order = Orders::byPublicId((string) ($_REQUEST['orden'] ?? ''));
if ($order === null || $order['gateway'] !== 'simulated') {
    http_response_code(404);
    view('404');
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = (string) ($_POST['resultado'] ?? '');
    if (csrf_valid($_POST['_csrf'] ?? null) && in_array($result, ['approved', 'rejected', 'pending'], true)) {
        $n = $gateway->buildNotification($order['public_id'], $result, (int) $order['total_amount']);
        Webhooks::handle('simulated', $n['headers'], $n['body']);
    }
    header('Location: /pago/' . $order['public_id'] . '/', true, 303);
    return;
}

view('pay-simulated', ['order' => $order]);
