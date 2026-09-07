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

    if ($provider === 'smtp') {
        return smtp_send($config, $to, $from, $fromName, $rendered);
    }

    return ['ok' => false, 'provider' => $provider, 'error' => 'provider de email no soportado', 'provider_message_id' => null];
}

/**
 * Envio por SMTP autenticado, sin dependencias (sockets nativos). Soporta SSL
 * directo (puerto 465) o STARTTLS (587). Mismo contrato {ok,error} que el resto;
 * nunca lanza hacia arriba y deja traza de cada paso (anti bug "sin correo").
 * Config en notifications.smtp: host, port, user, pass, secure (ssl|tls|none).
 */
function smtp_send(array $config, string $to, string $from, string $fromName, array $rendered): array
{
    $s = $config['notifications']['smtp'] ?? [];
    $host = (string) ($s['host'] ?? '');
    $port = (int) ($s['port'] ?? 587);
    $user = (string) ($s['user'] ?? '');
    $pass = (string) ($s['pass'] ?? '');
    $secure = strtolower((string) ($s['secure'] ?? 'tls')); // ssl | tls | none

    $fail = static function (string $msg, $fp = null): array {
        if (is_resource($fp)) {
            @fclose($fp);
        }
        log_line('notify', 'EMAIL(smtp) error', ['err' => $msg]);
        return ['ok' => false, 'provider' => 'smtp', 'error' => $msg, 'provider_message_id' => null];
    };

    if ($host === '' || $user === '' || $pass === '') {
        return $fail('faltan host/user/pass en notifications.smtp');
    }

    $transport = ($secure === 'ssl') ? 'ssl://' . $host : 'tcp://' . $host;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client($transport . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        return $fail('no se pudo conectar a ' . $host . ':' . $port . ' (' . $errstr . '). Puede que el hosting bloquee el puerto SMTP saliente.');
    }
    stream_set_timeout($fp, 20);

    $read = static function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break; // 4to char " " => ultima linea; "-" => continua
            }
        }
        return $data;
    };
    $cmd = static function (string $c) use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        return $read();
    };
    $code = static function (string $resp): int {
        return (int) substr(ltrim($resp), 0, 3);
    };

    if ($code($read()) !== 220) {
        return $fail('banner SMTP inesperado', $fp);
    }

    $ehloHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
    if ($code($cmd('EHLO ' . $ehloHost)) !== 250) {
        return $fail('EHLO rechazado', $fp);
    }

    if ($secure === 'tls') {
        if ($code($cmd('STARTTLS')) !== 220) {
            return $fail('STARTTLS rechazado', $fp);
        }
        if (@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
            return $fail('no se pudo negociar TLS', $fp);
        }
        if ($code($cmd('EHLO ' . $ehloHost)) !== 250) {
            return $fail('EHLO post-TLS rechazado', $fp);
        }
    }

    if ($code($cmd('AUTH LOGIN')) !== 334) {
        return $fail('AUTH LOGIN no aceptado por el servidor', $fp);
    }
    if ($code($cmd(base64_encode($user))) !== 334) {
        return $fail('usuario rechazado', $fp);
    }
    if ($code($cmd(base64_encode($pass))) !== 235) {
        return $fail('autenticacion fallo: revisa usuario/clave SMTP', $fp);
    }

    if ($code($cmd('MAIL FROM:<' . $from . '>')) !== 250) {
        return $fail('MAIL FROM rechazado (¿el remitente coincide con el buzon?)', $fp);
    }
    $rcpt = $code($cmd('RCPT TO:<' . $to . '>'));
    if ($rcpt !== 250 && $rcpt !== 251) {
        return $fail('RCPT TO rechazado para ' . mask_destination($to), $fp);
    }
    if ($code($cmd('DATA')) !== 354) {
        return $fail('DATA rechazado', $fp);
    }

    $subject = '=?UTF-8?B?' . base64_encode($rendered['subject']) . '?=';
    $bodyHtml = '<div style="font-family:system-ui,Arial,sans-serif;font-size:15px;line-height:1.6;color:#111">'
        . $rendered['html'] . '</div>';
    $headers = [
        'Date: ' . date('r'),
        'From: ' . mb_encode_mimeheader($fromName) . ' <' . $from . '>',
        'To: <' . $to . '>',
        'Reply-To: ' . $from,
        'Subject: ' . $subject,
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $ehloHost . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
    ];
    $message = implode("\r\n", $headers) . "\r\n\r\n" . $bodyHtml;
    $message = preg_replace('/\r\n|\r|\n/', "\r\n", $message); // normaliza a CRLF
    $message = preg_replace('/^\./m', '..', $message);         // dot-stuffing RFC 5321
    fwrite($fp, $message . "\r\n.\r\n");
    if ($code($read()) !== 250) {
        return $fail('el servidor no acepto el mensaje', $fp);
    }

    $cmd('QUIT');
    @fclose($fp);
    log_line('notify', 'EMAIL(smtp) enviado to=' . mask_destination($to), ['subject' => $rendered['subject']]);
    return ['ok' => true, 'provider' => 'smtp', 'error' => null, 'provider_message_id' => 'smtp'];
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
