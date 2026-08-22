<?php
/**
 * Worker del outbox. Procesa las notificaciones pendientes con reintentos.
 * -----------------------------------------------------------------------------
 * Programalo en Webempresa (Herramientas -> Tareas de Cron), por ejemplo cada
 * minuto:
 *
 *   * * * * *  /usr/bin/php /home/USUARIO/ruta/server/cron/process-outbox.php
 *
 * Si tu Cron solo permite URLs (wget/curl), colocalo bajo la web root y
 * protegelo con un token:
 *
 *   wget -q -O /dev/null "https://tu-dominio/cron/process-outbox.php?token=SECRETO"
 *
 * define CRON_TOKEN en config.php (o variable de entorno) para el modo URL.
 */

declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/outbox.php';

$config = load_config();

// Autorizacion: CLI siempre permitido; via web exige token.
$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    $token = getenv('CRON_TOKEN') ?: ($config['cron_token'] ?? '');
    $given = $_GET['token'] ?? '';
    if (!$token || !hash_equals((string) $token, (string) $given)) {
        http_response_code(403);
        echo 'forbidden';
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
}

try {
    $pdo = db($config);
    $summary = process_outbox($pdo, $config);
    log_line('cron', 'outbox procesado', $summary);
    if ($isCli) {
        fwrite(STDOUT, json_encode($summary) . "\n");
    } else {
        echo json_encode($summary, JSON_UNESCAPED_UNICODE);
    }
    exit(0);
} catch (Throwable $e) {
    log_line('cron', 'error', ['msg' => $e->getMessage()]);
    if (!$isCli) {
        http_response_code(500);
    }
    fwrite($isCli ? STDERR : STDOUT, 'error: ' . $e->getMessage() . "\n");
    exit(1);
}
