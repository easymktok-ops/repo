<?php
declare(strict_types=1);

/** Cliente SMTP mínimo (sin dependencias, para hosting compartido) y correo de confirmación. */
final class Mail
{
    private static function cfg(): array
    {
        return $GLOBALS['app_config']['mail'] ?? [];
    }

    /** Quita saltos de línea: evita inyectar cabeceras desde datos del público. */
    private static function line(string $v): string
    {
        return trim(str_replace(["\r", "\n", "\0"], ' ', $v));
    }

    private static function encodeHeader(string $v): string
    {
        $v = self::line($v);
        return preg_match('/^[\x20-\x7E]*$/', $v) ? $v : '=?UTF-8?B?' . base64_encode($v) . '?=';
    }

    /**
     * @return string el mensaje MIME completo (útil para pruebas)
     */
    public static function build(string $to, string $subject, string $text, string $html): string
    {
        $c = self::cfg();
        $from = self::line((string) ($c['from_email'] ?? ''));
        $fromName = self::encodeHeader((string) ($c['from_name'] ?? ''));
        $boundary = 'jcd_' . bin2hex(random_bytes(12));
        $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
        $h = [
            'From: ' . ($fromName !== '' ? "$fromName <$from>" : $from),
            'To: ' . self::line($to),
            'Subject: ' . self::encodeHeader($subject),
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        if (!empty($c['reply_to'])) {
            $h[] = 'Reply-To: ' . self::line((string) $c['reply_to']);
        }
        $part = static fn(string $type, string $body): string =>
            "--$boundary\r\nContent-Type: $type; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
        return implode("\r\n", $h) . "\r\n\r\n" . $part('text/plain', $text) . $part('text/html', $html) . "--$boundary--\r\n";
    }

    /** @param string[] $bcc */
    public static function send(string $to, string $subject, string $text, string $html, array $bcc = []): void
    {
        $c = self::cfg();
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Correo de destino no válido.');
        }
        $from = (string) ($c['from_email'] ?? '');
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Falta MAIL_FROM en la configuración.');
        }
        $bcc = array_values(array_filter($bcc, static fn($a) => filter_var($a, FILTER_VALIDATE_EMAIL)));
        $message = self::build($to, $subject, $text, $html);

        if (($c['driver'] ?? 'log') !== 'smtp') {
            $dir = STORAGE_DIR . '/mail';
            is_dir($dir) || mkdir($dir, 0775, true);
            file_put_contents($dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.eml', $message);
            return;
        }
        self::smtp($c, $from, array_merge([$to], $bcc), $message);
    }

    private static function smtp(array $c, string $from, array $recipients, string $message): void
    {
        $host = (string) $c['host'];
        $port = (int) $c['port'];
        $mode = (string) ($c['encryption'] ?? 'ssl');
        $implicit = $mode === 'ssl';
        $errno = 0;
        $err = '';
        $fp = @stream_socket_client(($implicit ? 'ssl://' : 'tcp://') . "$host:$port", $errno, $err, 15);
        if (!$fp) {
            throw new RuntimeException("No se pudo conectar al servidor de correo ($err).");
        }
        stream_set_timeout($fp, 20);

        $read = static function () use ($fp): array {
            $code = 0;
            $text = '';
            while (($line = fgets($fp, 1024)) !== false) {
                $text .= $line;
                $code = (int) substr($line, 0, 3);
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            return [$code, $text];
        };
        $cmd = static function (string $line, array $ok) use ($fp, $read): string {
            fwrite($fp, $line . "\r\n");
            [$code, $text] = $read();
            if (!in_array($code, $ok, true)) {
                throw new RuntimeException('El servidor de correo respondió: ' . trim($text));
            }
            return $text;
        };

        try {
            [$code, $text] = $read();
            if ($code !== 220) {
                throw new RuntimeException('Saludo inesperado: ' . trim($text));
            }
            $name = parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost';
            $cmd("EHLO $name", [250]);
            if ($mode === 'tls') {
                $cmd('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('No se pudo activar el cifrado TLS.');
                }
                $cmd("EHLO $name", [250]);
            }
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode((string) $c['user']), [334]);
            $cmd(base64_encode((string) $c['pass']), [235]);
            $cmd("MAIL FROM:<$from>", [250]);
            foreach ($recipients as $rcpt) {
                $cmd("RCPT TO:<$rcpt>", [250, 251]);
            }
            $cmd('DATA', [354]);
            // Relleno de puntos: una línea que empieza con "." no debe cerrar el mensaje.
            $body = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $message));
            fwrite($fp, str_replace("\n", "\r\n", $body) . "\r\n.\r\n");
            [$code, $text] = $read();
            if ($code !== 250) {
                throw new RuntimeException('El servidor rechazó el mensaje: ' . trim($text));
            }
            @fwrite($fp, "QUIT\r\n");
        } finally {
            fclose($fp);
        }
    }

    /** Envía la confirmación una sola vez por orden pagada. Devuelve false si no había nada que enviar. */
    public static function sendConfirmation(int $orderId, bool $force = false): bool
    {
        $order = Orders::byId($orderId);
        if ($order === null || $order['status'] !== 'approved' || $order['ticket'] === null) {
            return false;
        }
        // Se "reserva" el envío antes de mandar: dos avisos simultáneos no duplican el correo.
        $claim = Db::run(
            'UPDATE orders SET email_sent_at = ? WHERE id = ?' . ($force ? '' : ' AND email_sent_at IS NULL'),
            [Db::now(), $orderId]
        );
        if ($claim === 0) {
            return false;
        }
        try {
            $msg = self::confirmation($order);
            $copy = array_filter(array_map('trim', explode(',', (string) (self::cfg()['copy_to'] ?? ''))));
            self::send($order['email'], $msg['subject'], $msg['text'], $msg['html'], $copy);
        } catch (Throwable $e) {
            Db::run('UPDATE orders SET email_sent_at = NULL WHERE id = ?', [$orderId]);
            throw $e;
        }
        return true;
    }

    /** @return array{subject:string,text:string,html:string} */
    public static function confirmation(array $order): array
    {
        $event = Orders::eventById($order['event_id']) ?? [];
        $res = $order['kind'] === 'reservation';
        $link = url('/pago/' . $order['public_id'] . '/');
        $date = Orders::dateLabel($order['function_date']);
        $place = trim((string) ($event['venue'] ?? '') . ', ' . (string) ($event['city'] ?? ''), ', ');

        $rows = [];
        if ($res) {
            $rows = [
                'Voucher' => $order['ticket'], 'Nombre' => $order['first_name'], 'Apellido' => $order['last_name'],
                '# Identificación' => (Orders::RESERVATION_DOC_TYPES[$order['doc_type']] ?? $order['doc_type']) . ' ' . $order['doc_number'],
                'Email' => $order['email'], 'Fecha de la reserva' => $date, 'Lugar' => $place,
                'Cantidad de personas' => (string) $order['quantity'],
                'Precio total recibido' => format_cop((int) $order['total_amount']) . ($order['usd_total'] ? ' (USD ' . (int) $order['usd_total'] . ')' : ''),
            ];
            $subject = 'Tu reserva ' . $order['ticket'] . ' en ' . ($event['venue'] ?? 'Cartagena');
            $note = 'Presenta este voucher con tu documento. El cover no es reembolsable.';
        } else {
            $time = format_time_12h((string) ($event['time'] ?? ''));
            $rows = [
                'Ticket' => $order['ticket'], 'A nombre de' => $order['buyer_name'], 'Función' => $date . ($time !== '' ? ', ' . $time : ''),
                'Lugar' => $place, 'Puertas' => format_time_12h((string) ($event['doors'] ?? '')),
                'Entradas' => implode(', ', array_map(static fn($i) => $i['qty'] . ' ' . $i['label'], $order['items'])),
                'Total pagado' => format_cop((int) $order['total_amount']),
                'Dress code' => mb_strtolower((string) ($event['dress_code'] ?? '')),
            ];
            $subject = 'Tu ticket ' . $order['ticket'] . ' · Joyas Colombianas® Dinner & Show';
            $note = 'Presenta este número en la puerta con tu documento.';
        }
        $rows = array_filter($rows, static fn($v) => trim((string) $v) !== '');

        $text = "Pago aprobado. Gracias por tu compra.\n\n";
        foreach ($rows as $k => $v) {
            $text .= "$k: $v\n";
        }
        $text .= "\n$note\nVer en línea: $link\n";

        $tr = '';
        foreach ($rows as $k => $v) {
            $tr .= '<tr><td style="padding:6px 12px 6px 0;color:#c9b9c0;font-size:14px">' . e((string) $k) . '</td><td style="padding:6px 0;color:#fff;font-size:15px;font-weight:600">' . e((string) $v) . '</td></tr>';
        }
        $html = '<!doctype html><html lang="es"><body style="margin:0;background:#0b0a0c;font-family:Arial,Helvetica,sans-serif">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px">'
            . '<table role="presentation" width="560" style="max-width:100%;background:#16121a;border:1px solid #d81b60;border-radius:16px">'
            . '<tr><td style="padding:28px 28px 8px"><p style="margin:0;color:#f48fb1;font-size:12px;letter-spacing:2px;text-transform:uppercase">Joyas Colombianas® Dinner &amp; Show</p>'
            . '<h1 style="margin:8px 0 0;color:#fff;font-size:26px">' . e((string) $order['ticket']) . '</h1>'
            . '<p style="margin:6px 0 0;color:#c9b9c0;font-size:14px">Pago aprobado. Gracias por tu compra.</p></td></tr>'
            . '<tr><td style="padding:16px 28px"><table role="presentation">' . $tr . '</table></td></tr>'
            . '<tr><td style="padding:0 28px 28px"><p style="color:#c9b9c0;font-size:14px;margin:0 0 16px">' . e($note) . '</p>'
            . '<a href="' . e($link) . '" style="display:inline-block;background:#d81b60;color:#fff;text-decoration:none;padding:12px 24px;border-radius:999px;font-size:14px;font-weight:700">Ver mi ' . ($res ? 'voucher' : 'ticket') . '</a></td></tr>'
            . '</table></td></tr></table></body></html>';

        return ['subject' => $subject, 'text' => $text, 'html' => $html];
    }
}
