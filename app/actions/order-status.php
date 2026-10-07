<?php
declare(strict_types=1);

/** @var string $publicId */
$order = Orders::byPublicId($publicId);

if (!isset($statusOnly)) {
    if ($order === null) {
        http_response_code(404);
        view('404');
        return;
    }
    view('order', ['order' => $order]);
    return;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($order === null) {
    http_response_code(404);
    echo json_encode(['final' => false]);
    return;
}
$final = $order['status'] === 'approved' ? $order['ticket'] !== null : in_array($order['status'], ['rejected', 'cancelled', 'refunded'], true);
echo json_encode(['status' => $order['status'], 'final' => $final]);
