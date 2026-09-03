<?php
/**
 * OAuth GitHub para Decap CMS — paso 2 (callback).
 * -----------------------------------------------------------------------------
 * GitHub regresa aqui con ?code y ?state. Verifica el state, canjea el code por
 * un access_token y completa el handshake con Decap via postMessage (el formato
 * exacto que espera el CMS). Luego la ventana emergente se puede cerrar.
 */

declare(strict_types=1);

require __DIR__ . '/../../../lib/bootstrap.php';

$config = load_config();
$oauth = $config['oauth'] ?? [];

// FASE 2: interruptor (ver auth.php). Sin 'enabled' => true, aviso amable.
if (empty($oauth['enabled'])) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    header('Retry-After: 3600');
    exit('<!doctype html><meta charset="utf-8"><title>Fase 2</title>'
        . '<body style="font-family:system-ui;background:#0f1013;color:#ecedf1;'
        . 'display:grid;place-items:center;height:100vh;margin:0;text-align:center">'
        . '<p>Módulo de administración de contenido en preparación (Fase 2).</p></body>');
}

$clientId = (string) ($oauth['github_client_id'] ?? '');
$clientSecret = (string) ($oauth['github_client_secret'] ?? '');

function oauth_fail(string $msg): void
{
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit($msg);
}

if ($clientId === '' || $clientSecret === '') {
    oauth_fail('OAuth no configurado en config.php.');
}

$code = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');
$cookieState = (string) ($_COOKIE['decap_oauth_state'] ?? '');

if ($code === '' || $state === '' || $cookieState === '' || !hash_equals($cookieState, $state)) {
    oauth_fail('Estado invalido (posible CSRF o cookie expirada). Reintenta el login.');
}

// Canje code -> access_token.
$ch = curl_init('https://github.com/login/oauth/access_token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_POSTFIELDS     => http_build_query([
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'code'          => $code,
    ]),
    CURLOPT_TIMEOUT        => 15,
]);
$resp = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($resp === false) {
    log_line('oauth', 'error de red con GitHub', ['err' => $err]);
    oauth_fail('No se pudo contactar a GitHub. Reintenta.');
}

$data = json_decode((string) $resp, true);
$token = is_array($data) ? (string) ($data['access_token'] ?? '') : '';

if ($token === '') {
    log_line('oauth', 'sin access_token', ['resp' => is_array($data) ? ($data['error'] ?? 'desconocido') : 'no-json']);
    oauth_fail('GitHub no devolvio token. Revisa client_id/secret y el callback registrado.');
}

// Limpia la cookie de estado.
setcookie('decap_oauth_state', '', ['expires' => time() - 3600, 'path' => '/']);

// Handshake con Decap: el popup avisa "authorizing", el CMS responde y el popup
// devuelve el token con el formato exacto que Decap escucha.
$payload = json_encode(['token' => $token, 'provider' => 'github'], JSON_UNESCAPED_SLASHES);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Autenticando…</title></head>
<body style="font-family:system-ui;background:#0f0f12;color:#eee;display:grid;place-items:center;height:100vh;margin:0">
<p>Autenticado con GitHub. Puedes cerrar esta ventana.</p>
<script>
(function () {
  function receiveMessage(e) {
    window.opener && window.opener.postMessage(
      'authorization:github:success:<?= $payload ?>',
      e.origin
    );
    window.removeEventListener('message', receiveMessage, false);
  }
  window.addEventListener('message', receiveMessage, false);
  window.opener && window.opener.postMessage('authorizing:github', '*');
})();
</script>
</body></html>
