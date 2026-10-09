<?php
declare(strict_types=1);

// Servidor de desarrollo de PHP: servir archivos estáticos tal cual. En Hostinger lo hace .htaccess.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file)) {
        return false;
    }
}

define('PUBLIC_DIR', __DIR__);
require dirname(__DIR__) . '/app/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$stubs = [
    '/cartagena'         => 'Cartagena',
    '/politica-de-datos' => 'Política de tratamiento de datos',
];

switch (true) {
    case $path === '/':
        view('home');
        break;

    case $path === '/contacto' && $method === 'POST':
        require APP_DIR . '/actions/contacto.php';
        break;

    case $path === '/medellin' || $path === '/bogota':
        view('landing', ['landingKey' => ltrim($path, '/')]);
        break;

    case $path === '/comprar':
        require APP_DIR . '/actions/checkout.php';
        break;

    case $path === '/pago-simulado':
        require APP_DIR . '/actions/pay-simulated.php';
        break;

    case preg_match('#^/pago/([a-f0-9]{32})$#', $path, $m) === 1:
        $publicId = $m[1];
        require APP_DIR . '/actions/order-status.php';
        break;

    case preg_match('#^/pago/([a-f0-9]{32})/estado$#', $path, $m) === 1:
        $publicId = $m[1];
        $statusOnly = true;
        require APP_DIR . '/actions/order-status.php';
        break;

    case $method === 'POST' && preg_match('#^/webhook/([a-z]{3,24})$#', $path, $m) === 1:
        $provider = $m[1];
        require APP_DIR . '/actions/webhook.php';
        break;

    case $path === '/sitemap.xml':
        header('Content-Type: application/xml; charset=utf-8');
        view('sitemap');
        break;

    case isset($stubs[$path]):
        view('stub', ['stubTitle' => $stubs[$path], 'stubPath' => $path]);
        break;

    default:
        http_response_code(404);
        view('404');
}
