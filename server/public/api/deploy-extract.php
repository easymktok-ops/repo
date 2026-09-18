<?php
/**
 * POST /api/deploy-extract.php
 * -----------------------------------------------------------------------------
 * Publicacion RAPIDA. GitHub Actions construye el sitio, lo comprime como UN
 * solo archivo (site-deploy.zip), lo sube por FTP a la raiz web y luego llama
 * a este endpoint. Aqui se descomprime sobre la raiz y se borra el zip. Asi se
 * evita subir cientos de archivos por FTPS (que este host corta a media sesion)
 * y el deploy pasa de ~20 min a ~1-2 min.
 *
 * SEGURIDAD
 *  - Token obligatorio en la cabecera X-Deploy-Token, comparado con hash_equals
 *    (tiempo constante). El token vive en config.php (server-side), nunca en el
 *    repo ni en el cliente.
 *  - Solo descomprime archivos ESTATICOS del sitio. RECHAZA de forma explicita:
 *      * rutas con "..", absolutas o con bytes nulos (path traversal),
 *      * cualquier entrada bajo server/ (backend, BD, config),
 *      * .htaccess / .htaccess.old / config.php (nunca se sobrescriben),
 *      * .well-known/ (validaciones de dominio/SSL).
 *  - Nunca escribe fuera de la raiz web.
 *
 * El zip solo contiene dist/ (el sitio publico), no hay secretos en el.
 */

declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';

$config = load_config();

// --- Metodo -----------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(405, ['ok' => false, 'error' => 'Metodo no permitido']);
}

// --- Token ------------------------------------------------------------------
$expected = trim((string) ($config['deploy_token'] ?? ''));
if ($expected === '' || $expected === 'CAMBIA_ESTE_TOKEN') {
    log_line('deploy', 'deploy_token no configurado en config.php');
    json_response(500, ['ok' => false, 'error' => 'deploy no configurado']);
}
$provided = (string) ($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');
if ($provided === '' || !hash_equals($expected, $provided)) {
    log_line('deploy', 'token invalido en intento de deploy', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '?',
    ]);
    json_response(403, ['ok' => false, 'error' => 'forbidden']);
}

// --- Raiz web y zip ---------------------------------------------------------
$webRoot = rtrim((string) ($config['web_root'] ?? ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
if ($webRoot === '' || !is_dir($webRoot)) {
    json_response(500, ['ok' => false, 'error' => 'raiz web invalida']);
}

$zipPath = $webRoot . '/site-deploy.zip';
if (!is_file($zipPath)) {
    json_response(404, ['ok' => false, 'error' => 'site-deploy.zip no encontrado']);
}

if (!class_exists('ZipArchive')) {
    json_response(500, ['ok' => false, 'error' => 'ZipArchive no disponible en este PHP']);
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    json_response(500, ['ok' => false, 'error' => 'no se pudo abrir el zip']);
}

// --- Filtro de entradas permitidas -----------------------------------------
/** Prefijos de ruta que NUNCA se tocan. */
$blockedPrefixes = ['server/', '.well-known/'];
/** Nombres base que NUNCA se sobrescriben (en cualquier nivel). */
$blockedBasenames = ['.htaccess', '.htaccess.old', 'config.php', 'site-deploy.zip'];

$allowed = [];
$skipped = 0;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (!is_string($name) || $name === '') {
        $skipped++;
        continue;
    }
    $norm = str_replace('\\', '/', $name);

    // Traversal / absolutas / byte nulo.
    if (
        strpos($norm, "\0") !== false ||
        $norm[0] === '/' ||
        strpos($norm, '../') !== false ||
        strncmp($norm, '..', 2) === 0
    ) {
        $skipped++;
        continue;
    }

    // Prefijos protegidos.
    $blocked = false;
    foreach ($blockedPrefixes as $p) {
        if (strncmp($norm, $p, strlen($p)) === 0) {
            $blocked = true;
            break;
        }
    }
    // Nombres base protegidos.
    if (!$blocked && in_array(basename($norm), $blockedBasenames, true)) {
        $blocked = true;
    }
    if ($blocked) {
        $skipped++;
        continue;
    }

    $allowed[] = $name;
}

// --- Extraccion -------------------------------------------------------------
$ok = true;
if ($allowed) {
    // extractTo con lista blanca: escribe solo lo permitido, crea carpetas y
    // sobrescribe archivos existentes. No usa memoria por archivo (streaming).
    $ok = $zip->extractTo($webRoot, $allowed);
}
$total = $zip->numFiles;
$zip->close();

// Borra el zip pase lo que pase (no debe quedar publico).
@unlink($zipPath);

if (!$ok) {
    log_line('deploy', 'fallo extractTo', ['permitidos' => count($allowed)]);
    json_response(500, ['ok' => false, 'error' => 'fallo al descomprimir']);
}

log_line('deploy', 'publicacion aplicada', [
    'escritos' => count($allowed),
    'omitidos' => $skipped,
    'total'    => $total,
]);

json_response(200, [
    'ok'       => true,
    'written'  => count($allowed),
    'skipped'  => $skipped,
    'total'    => $total,
]);
