<?php
/**
 * Router SOLO para desarrollo local (no se despliega):
 *   npm run build
 *   php -S localhost:8000 server/dev/router.php
 * Replica el .htaccess de produccion: /api/* -> server/public/api/*, y el
 * resto se sirve desde dist/. Con `npm run dev`, Astro reenvia /api a este
 * servidor (ver vite.server.proxy en astro.config.mjs).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__, 2);
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

if (strpos($path, '..') !== false) {
    http_response_code(400);
    exit;
}

if (strpos($path, '/api/') === 0) {
    $file = $root . '/server/public' . $path;
    if (is_file($file) && substr($file, -4) === '.php') {
        $_SERVER['SCRIPT_NAME'] = $path;
        $_SERVER['SCRIPT_FILENAME'] = $file;
        chdir(dirname($file));
        require $file;
        return true;
    }
    http_response_code(404);
    echo 'No encontrado';
    return true;
}

$dist = $root . '/dist';
$file = $dist . $path;
if (is_dir($file)) {
    $file = rtrim($file, '/') . '/index.html';
}
if (!is_file($file) && is_file($file . '.html')) {
    $file .= '.html';
}
if (!is_file($file)) {
    http_response_code(404);
    $file = $dist . '/404.html';
    if (!is_file($file)) {
        echo 'No encontrado';
        return true;
    }
}
$types = [
    'html' => 'text/html; charset=utf-8', 'js' => 'text/javascript', 'css' => 'text/css',
    'json' => 'application/json', 'svg' => 'image/svg+xml', 'avif' => 'image/avif',
    'webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'mp4' => 'video/mp4',
    'woff2' => 'font/woff2', 'xml' => 'application/xml', 'txt' => 'text/plain; charset=utf-8',
];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
readfile($file);
return true;
