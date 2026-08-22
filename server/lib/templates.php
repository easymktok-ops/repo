<?php
/**
 * Plantillas de notificaciones (email + WhatsApp), es/en. Sin HTML crudo del
 * usuario: los datos de la reserva se escapan al renderizar el correo.
 */

declare(strict_types=1);

/** Formatea centavos MXN como "$1,234". */
function money_mxn(int $cents): string
{
    return '$' . number_format($cents / 100, 0, '.', ',');
}

/**
 * Construye el contenido de una notificacion segun su tipo.
 * $b = fila de la reserva (bookings). Devuelve subject/text/html.
 *
 * @return array{subject:string,text:string,html:string}
 */
function render_notification(string $kind, array $b): array
{
    $locale = ($b['locale'] === 'en') ? 'en' : 'es';
    $name = trim((string) $b['customer_name']);
    $title = (string) $b['package_title'];
    $pax = (int) $b['passengers'];
    $date = (string) ($b['flight_date'] ?? '');
    $mode = (string) $b['mode'];
    $paid = money_mxn((int) $b['amount_now_cents']);
    $balance = money_mxn((int) $b['balance_cents']);
    $ref = (string) $b['reference'];

    $isDeposit = ($mode === 'deposit');

    if ($kind === 'booking_confirmation') {
        if ($locale === 'en') {
            $subject = "Your Aerodiverti booking is confirmed - {$ref}";
            $lines = [
                "Hi {$name},",
                "",
                "Your hot-air balloon flight is confirmed. Here are the details:",
                "",
                "Booking: {$ref}",
                "Experience: {$title}",
                "Passengers: {$pax}",
                $date ? "Preferred date: {$date}" : "",
                "Paid now: {$paid}",
                $isDeposit ? "Balance to pay on site the day of the flight: {$balance}" : "Fully paid.",
                "",
                "We'll contact you to confirm the exact date and meeting details.",
                "See you above Teotihuacan,",
                "Aerodiverti",
            ];
        } else {
            $subject = "Tu reserva en Aerodiverti esta confirmada - {$ref}";
            $lines = [
                "Hola {$name},",
                "",
                "Tu vuelo en globo esta confirmado. Estos son los detalles:",
                "",
                "Reserva: {$ref}",
                "Experiencia: {$title}",
                "Pasajeros: {$pax}",
                $date ? "Fecha tentativa: {$date}" : "",
                "Pagado ahora: {$paid}",
                $isDeposit ? "Saldo a liquidar en sitio el dia del vuelo: {$balance}" : "Pagado en su totalidad.",
                "",
                "Te contactaremos para confirmar la fecha exacta y el punto de encuentro.",
                "Nos vemos sobre Teotihuacan,",
                "Aerodiverti",
            ];
        }
    } elseif ($kind === 'booking_admin_alert') {
        $subject = "Nueva reserva {$ref} - {$title} ({$pax} pax) - {$paid}";
        $lines = [
            "Nueva reserva pagada.",
            "",
            "Referencia: {$ref}",
            "Paquete: {$title}",
            "Pasajeros: {$pax}",
            "Modo: " . ($isDeposit ? "Anticipo (saldo en sitio: {$balance})" : "Pago completo"),
            "Cobrado ahora: {$paid}",
            $date ? "Fecha tentativa: {$date}" : "",
            "",
            "Cliente: {$name}",
            "Email: " . (string) $b['customer_email'],
            "Telefono/WhatsApp: " . (string) $b['customer_phone'],
            $b['notes'] ? "Notas: " . (string) $b['notes'] : "",
        ];
    } else {
        $subject = "Aerodiverti - {$ref}";
        $lines = ["Actualizacion de la reserva {$ref}."];
    }

    $text = implode("\n", array_filter($lines, static fn($l) => $l !== ""));
    $html = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));

    return ['subject' => $subject, 'text' => $text, 'html' => $html];
}
