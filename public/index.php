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
    '/comprar'           => 'Compra de entradas',
    '/medellin'          => 'Medellín',
    '/bogota'            => 'Bogotá',
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
