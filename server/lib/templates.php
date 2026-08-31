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
 * Envuelve el texto del correo en un HTML de marca: encabezado con el wordmark
 * sobre banda oscura (asi el logo tratado para oscuro se ve nitido) y cuerpo
 * claro legible. El logo va por URL ABSOLUTA (no adjunto) tomada de site_url:
 * dominio-agnostico. Si no hay site_url, cae al nombre en texto.
 */
function email_brand_html(string $bodyText): string
{
    $site = '';
    if (function_exists('load_config')) {
        $cfg = load_config();
        $site = rtrim((string) ($cfg['site_url'] ?? ''), '/');
    }
    $body = nl2br(htmlspecialchars($bodyText, ENT_QUOTES, 'UTF-8'));
    $logo = $site !== ''
        ? '<img src="' . htmlspecialchars($site . '/aerodiverti-wordmark.png', ENT_QUOTES, 'UTF-8')
            . '" alt="Aerodiverti" width="176" style="height:32px;width:auto;border:0;display:inline-block" />'
        : '<span style="color:#ea83c1;font-weight:700;font-size:18px">Aerodiverti</span>';

    return '<!doctype html><html><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;background:#f4f4f6">'
        . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;'
        . 'font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif">'
        . '<div style="background:#15171e;padding:22px 24px;text-align:center">' . $logo . '</div>'
        . '<div style="padding:24px;color:#23252b;font-size:15px;line-height:1.6">' . $body . '</div>'
        . '<div style="padding:14px 24px;color:#9aa0aa;font-size:12px;text-align:center;border-top:1px solid #eee">'
        . 'Aerodiverti · Vuelos en globo sobre Teotihuacan</div>'
        . '</div></body></html>';
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
    $html = email_brand_html($text);

    return ['subject' => $subject, 'text' => $text, 'html' => $html];
}
