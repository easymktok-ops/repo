<?php
declare(strict_types=1);

/** @var string $provider */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$raw = (string) file_get_contents('php://input', false, null, 0, 262144);
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'HTTP_')) {
        $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
    }
}

try {
    $result = Webhooks::handle($provider, $headers, $raw);
    // 200 también para avisos repetidos o de órdenes desconocidas: así la pasarela deja de reintentarlos.
    http_response_code($result['ok'] ? 200 : 422);
    echo json_encode(['ok' => $result['ok'], 'result' => $result['result']]);
} catch (InvalidSignature) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'result' => 'invalid_signature']);
} catch (GatewayNotReady) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'result' => 'gateway_not_ready']);
} catch (Throwable $e) {
    error_log('Error procesando aviso de ' . $provider . ': ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'result' => 'error']);
}
