<?php
/**
 * Canales de envio. Mismo contrato que src/lib/notifications (TS): cada intento
 * devuelve un resultado {ok, error} y NUNCA lanza hacia arriba. El outbox
 * persiste ese resultado y reintenta. Anti bug "sin correo enviado".
 *
 * Providers:
 *  - email: "log" (solo traza) | "mail" (funcion mail() de PHP)
 *  - whatsapp: "log" (solo traza) | "meta_cloud" (WhatsApp Cloud API)
 */

declare(strict_types=1);

/**
 * Envia un mensaje ya renderizado por su canal.
 *
 * @param array $rendered subject/text/html (de render_notification)
 * @return array{ok:bool,provider:string,error:?string,provider_message_id:?string}
 */
function channel_send(array $config, string $channel, string $to, array $rendered): array
{
    try {
        if ($channel === 'email') {
            return email_send($config, $to, $rendered);
        }
        if ($channel === 'whatsapp') {
            return whatsapp_send($config, $to, $rendered);
        }
        return ['ok' => false, 'provider' => 'none', 'error' => 'canal desconocido: ' . $channel, 'provider_message_id' => null];
    } catch (Throwable $e) {
        // Blindaje: cualquier excepcion se convierte en resultado, no se propaga.
        return ['ok' => false, 'provider' => $channel, 'error' => $e->getMessage(), 'provider_message_id' => null];
    }
}

function email_send(array $config, string $to, array $rendered): array
{
    $provider = $config['notifications']['email_provider'] ?? 'log';
    $from = $config['notifications']['email_from'] ?? 'reservas@example.com';
    $fromName = $config['notifications']['email_from_name'] ?? 'Aerodiverti';

    if ($provider === 'log') {
        log_line('notify', 'EMAIL(log) to=' . mask_destination($to), ['subject' => $rendered['subject']]);
        return ['ok' => true, 'provider' => 'log', 'error' => null, 'provider_message_id' => 'log-email'];
    }

    if ($provider === 'mail') {
        $headers = [];
        $headers[] = 'From: ' . mb_encode_mimeheader($fromName) . ' <' . $from . '>';
        $headers[] = 'Reply-To: ' . $from;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $subject = '=?UTF-8?B?' . base64_encode($rendered['subject']) . '?=';
        $body = '<div style="font-family:system-ui,Arial,sans-serif;font-size:15px;line-height:1.6;color:#111">'
            . $rendered['html'] . '</div>';
        $ok = @mail($to, $subject, $body, implode("\r\n", $headers));
        return [
            'ok' => (bool) $ok,
            'provider' => 'mail',
            'error' => $ok ? null : 'mail() devolvio false',
            'provider_message_id' => null,
        ];
    }

    return ['ok' => false, 'provider' => $provider, 'error' => 'provider de email no soportado', 'provider_message_id' => null];
}

function whatsapp_send(array $config, string $to, array $rendered): array
{
    $n = $config['notifications'];
    $provider = $n['whatsapp_provider'] ?? 'log';

    if ($provider === 'log') {
        log_line('notify', 'WA(log) to=' . mask_destination($to), ['subject' => $rendered['subject']]);
        return ['ok' => true, 'provider' => 'log', 'error' => null, 'provider_message_id' => 'log-wa'];
    }

    if ($provider === 'meta_cloud') {
        $token = $n['whatsapp_token'] ?? '';
        $phoneId = $n['whatsapp_phone_id'] ?? '';
        if (!$token || !$phoneId) {
            return ['ok' => false, 'provider' => 'meta_cloud', 'error' => 'faltan token/phone_id', 'provider_message_id' => null];
        }
        $url = 'https://graph.facebook.com/v20.0/' . $phoneId . '/messages';
        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'to' => preg_replace('/\D+/', '', $to),
            'type' => 'text',
            'text' => ['body' => $rendered['text']],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'provider' => 'meta_cloud', 'error' => 'cURL: ' . $curlErr, 'provider_message_id' => null];
        }
        $data = json_decode($raw, true);
        if ($code >= 400) {
            $msg = $data['error']['message'] ?? ('HTTP ' . $code);
            return ['ok' => false, 'provider' => 'meta_cloud', 'error' => $msg, 'provider_message_id' => null];
        }
        $mid = $data['messages'][0]['id'] ?? null;
        return ['ok' => true, 'provider' => 'meta_cloud', 'error' => null, 'provider_message_id' => $mid];
    }

    return ['ok' => false, 'provider' => $provider, 'error' => 'provider de whatsapp no soportado', 'provider_message_id' => null];
}
