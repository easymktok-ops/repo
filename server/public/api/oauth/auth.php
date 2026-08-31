<?php
/**
 * OAuth GitHub para Decap CMS — paso 1 (autorizar).
 * -----------------------------------------------------------------------------
 * Decap (panel /admin) abre este endpoint en una ventana emergente. Redirige a
 * GitHub para que el usuario autorice, guardando un `state` anti-CSRF en cookie.
 * GitHub regresa a callback.php con el `code`.
 *
 * DOMINIO-AGNOSTICO: el redirect_uri se arma con el host de la propia peticion,
 * no se hardcodea. El unico dato externo son las credenciales (config.php).
 */

declare(strict_types=1);

require __DIR__ . '/../../../lib/bootstrap.php';

$config = load_config();
$oauth = $config['oauth'] ?? [];
$clientId = (string) ($oauth['github_client_id'] ?? '');

if ($clientId === '') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('OAuth no configurado: falta oauth.github_client_id en config.php.');
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443);

$state = bin2hex(random_bytes(16));
setcookie('decap_oauth_state', $state, [
    'expires'  => time() + 600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $https,
]);

$scheme = $https ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$redirectUri = $scheme . '://' . $host . $dir . '/callback.php';

$params = http_build_query([
    'client_id'    => $clientId,
    'redirect_uri' => $redirectUri,
    'scope'        => 'repo',
    'state'        => $state,
    'allow_signup' => 'false',
]);

header('Location: https://github.com/login/oauth/authorize?' . $params);
exit;
