<?php
declare(strict_types=1);

// Envío de correo pendiente de decisión (SMTP de Hostinger vs. servicio externo).
// Mientras tanto las solicitudes quedan en storage/leads.csv, fuera de public_html.

$redirect = static function (string $query): never {
    header('Location: /?' . $query . '#contacto', true, 303);
    exit;
};

if (!csrf_valid($_POST['_csrf'] ?? null)) {
    $_SESSION['form_errors'] = ['_csrf' => 'La sesión expiró. Vuelve a enviar el formulario.'];
    $redirect('error=1');
}

if (trim((string) ($_POST['website'] ?? '')) !== '') {
    $redirect('enviado=1');
}

$clean = static fn(string $key, int $max): string => mb_substr(trim(strip_tags((string) ($_POST[$key] ?? ''))), 0, $max);

$data = [
    'nombre'   => $clean('nombre', 120),
    'empresa'  => $clean('empresa', 120),
    'correo'   => $clean('correo', 160),
    'telefono' => $clean('telefono', 30),
    'asunto'   => $clean('asunto', 160),
    'mensaje'  => $clean('mensaje', 3000),
];

$errors = [];
if ($data['nombre'] === '') {
    $errors['nombre'] = 'Escribe tu nombre completo.';
}
if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
    $errors['correo'] = 'Escribe un correo válido, por ejemplo nombre@correo.com.';
}
if (!preg_match('/^\+?[\d\s\-()]{7,20}$/', $data['telefono'])) {
    $errors['telefono'] = 'Escribe un número de contacto válido, con indicativo si estás fuera de Colombia.';
}
if ($data['asunto'] === '') {
    $errors['asunto'] = 'Cuéntanos el asunto de tu solicitud.';
}
if ($data['mensaje'] === '') {
    $errors['mensaje'] = 'Escribe tu mensaje.';
}
if (($_POST['acepta'] ?? '') !== '1') {
    $errors['acepta'] = 'Debes aceptar la política de tratamiento de datos para enviar la solicitud.';
}

if ($errors) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = $data;
    $redirect('error=1');
}

if (!is_dir(STORAGE_DIR)) {
    mkdir(STORAGE_DIR, 0750, true);
}

$file = STORAGE_DIR . '/leads.csv';
$isNew = !is_file($file);
$handle = fopen($file, 'ab');
if ($handle === false) {
    error_log('No se pudo abrir storage/leads.csv');
    $_SESSION['form_errors'] = ['_server' => 'No pudimos guardar tu solicitud. Escríbenos por WhatsApp.'];
    $redirect('error=1');
}

flock($handle, LOCK_EX);
if ($isNew) {
    fputcsv($handle, ['fecha', 'nombre', 'empresa', 'correo', 'telefono', 'asunto', 'mensaje'], ',', '"', '');
}
// Prefijo contra inyección de fórmulas al abrir el CSV en Excel.
$safe = array_map(static fn(string $v): string => preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $data);
fputcsv($handle, [date('c'), ...array_values($safe)], ',', '"', '');
flock($handle, LOCK_UN);
fclose($handle);

unset($_SESSION['csrf']);
$redirect('enviado=1');
