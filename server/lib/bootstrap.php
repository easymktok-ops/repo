<?php
/**
 * Arranque comun de todos los endpoints/cron: carga config, define helpers de
 * respuesta JSON y manejo de errores. Sin dependencias externas.
 */

declare(strict_types=1);

mb_internal_encoding('UTF-8');
// Zona horaria del negocio (timestamps consistentes en toda la app).
date_default_timezone_set('America/Mexico_City');

/** Carga config.php (secretos) o cae a config.example.php en su defecto. */
function load_config(): array
{
    $dir = dirname(__DIR__);
    $real = $dir . '/config.php';
    $example = $dir . '/config.example.php';
    if (is_file($real)) {
        return require $real;
    }
    // Sin config.php no operamos con secretos reales; el ejemplo permite que el
    // codigo cargue en dev, pero las llaves son placeholders.
    return require $example;
}

/** Respuesta JSON uniforme y fin de ejecucion. */
function json_response(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Log estructurado a error_log (siempre queda traza; nunca se pierde). */
function log_line(string $scope, string $msg, array $ctx = []): void
{
    $line = '[aero:' . $scope . '] ' . $msg;
    if ($ctx) {
        $line .= ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    error_log($line);
}

/** Enmascara un destino (email/telefono) para logs. */
function mask_destination(string $to): string
{
    if (strpos($to, '@') !== false) {
        [$user, $domain] = explode('@', $to, 2);
        return substr($user, 0, 2) . '***@' . $domain;
    }
    $len = strlen($to);
    if ($len <= 5) {
        return substr($to, 0, 1) . '***';
    }
    return substr($to, 0, 3) . '***' . substr($to, -2);
}
