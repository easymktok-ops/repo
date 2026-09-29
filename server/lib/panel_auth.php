<?php
/**
 * Sesion del panel de ventas (cookie aero_panel). La usan panel.php y la vista
 * previa de /api/prices.php, para que ambos reconozcan el mismo login.
 */

declare(strict_types=1);

function panel_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    session_name('aero_panel');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $https,
    ]);
    session_start();
}

/** Usuario con sesion valida en el panel, o null. */
function panel_session_user(): ?string
{
    panel_session_start();
    $u = $_SESSION['panel_user'] ?? '';
    return is_string($u) && $u !== '' ? $u : null;
}
