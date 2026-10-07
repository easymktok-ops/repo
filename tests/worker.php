<?php
declare(strict_types=1);

// Proceso auxiliar de la prueba de concurrencia: crea una orden, la aprueba y emite su ticket.
define('PUBLIC_DIR', dirname(__DIR__) . '/public');
require dirname(__DIR__) . '/app/bootstrap.php';

$buyer = ['buyer_name' => 'Prueba Concurrente', 'doc_type' => 'CC', 'doc_number' => '99999999', 'email' => 'c@example.com', 'phone' => '3001112233', 'city' => 'Medellín'];
$order = Orders::create('medellin', $argv[1], $buyer, ['menu' => 1], 'simulated');
$gw = new SimulatedGateway((string) $GLOBALS['app_config']['payments']['simulated_secret']);
$n = $gw->buildNotification($order['public_id'], 'approved', 195000);
$r = Webhooks::handle('simulated', $n['headers'], $n['body']);
echo (int) preg_replace('/\D/', '', $r['ticket'] ?? '0');
