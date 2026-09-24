<?php
/**
 * Happy Puerto — receptor del formulario de cotización.
 * Envía el lead al correo del sitio y redirige a /gracias.
 *
 * Requisitos de hosting: PHP con mail() habilitado (cPanel lo trae).
 * No guarda datos ni usa base de datos. Sin dependencias externas.
 */

// --- Config ---
$DESTINO   = 'Ventas@happypuerto.com';                 // correo del cliente
$REMITENTE = 'no-reply@happypuerto.com';               // buzón del mismo dominio (mejor entregabilidad)
$GRACIAS   = '/gracias';                                // página de agradecimiento
$ERROR_URL = '/#contacto';                              // a dónde volver si algo falla

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . $ERROR_URL);
  exit;
}

// Anti-spam: honeypot (campo oculto que un humano no llena)
if (!empty($_POST['website'])) {
  header('Location: ' . $GRACIAS); // fingimos éxito para el bot
  exit;
}

// Helpers
function limpio($v) { return trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', (string)$v)); }
function campo($k) { return isset($_POST[$k]) ? limpio($_POST[$k]) : ''; }

$nombre   = campo('nombre');
$telefono = campo('telefono');
$email    = campo('email');
$personas = campo('personas');
$fecha    = campo('fecha');
$paquete  = campo('paquete');
$mensaje  = isset($_POST['mensaje']) ? trim((string)$_POST['mensaje']) : '';

// Validación mínima
$errores = array();
if ($nombre === '')                                   $errores[] = 'nombre';
if ($telefono === '')                                 $errores[] = 'telefono';
if (empty($_POST['consent']))                         $errores[] = 'consent';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';

if (count($errores) > 0) {
  header('Location: ' . $ERROR_URL . '?error=1');
  exit;
}

// Cuerpo del correo
$lineas = array(
  'Nueva solicitud de cotización desde happypuerto.com',
  '----------------------------------------------------',
  'Nombre:    ' . $nombre,
  'Teléfono:  ' . $telefono,
  'Email:     ' . ($email !== '' ? $email : '(no proporcionado)'),
  'Personas:  ' . ($personas !== '' ? $personas : '(sin especificar)'),
  'Fecha:     ' . ($fecha !== '' ? $fecha : '(sin especificar)'),
  'Paquete:   ' . ($paquete !== '' ? $paquete : '(sin especificar)'),
  '',
  'Mensaje:',
  ($mensaje !== '' ? $mensaje : '(sin mensaje)'),
  '',
  '----------------------------------------------------',
  'Enviado: ' . date('Y-m-d H:i:s'),
  'IP:      ' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''),
);
$cuerpo = implode("\r\n", $lineas);

$asunto = 'Cotización web' . ($paquete !== '' ? ' · ' . $paquete : '') . ' — ' . $nombre;

// Cabeceras (From del mismo dominio; Reply-To al visitante si dejó email)
$headers  = 'From: Happy Puerto Web <' . $REMITENTE . ">\r\n";
$headers .= 'Reply-To: ' . ($email !== '' ? $email : $REMITENTE) . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$ok = @mail($DESTINO, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $cuerpo, $headers, '-f' . $REMITENTE);

if ($ok) {
  header('Location: ' . $GRACIAS, true, 303);
} else {
  header('Location: ' . $ERROR_URL . '?error=envio');
}
exit;
