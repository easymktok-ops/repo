<?php
/**
 * OUTBOX con reintentos y backoff. Corazon anti "sin correo enviado":
 *  - enqueue_notification(): inserta la notificacion como 'pending'. El flujo
 *    de reserva SOLO encola; no envia en linea (el webhook debe responder 200
 *    rapido a Stripe). El envio real lo hace el worker por Cron.
 *  - process_outbox(): el Cron toma las pendientes cuyo next_attempt_at ya
 *    vencio, intenta enviarlas y persiste el resultado. Backoff exponencial.
 *
 * El estado SIEMPRE queda trazado en la tabla: pending -> sent | failed, con
 * attempts, last_error y timestamps. Nunca se pierde silenciosamente.
 */

declare(strict_types=1);

/** Encola una notificacion para una reserva. */
function enqueue_notification(PDO $pdo, int $bookingId, string $channel, string $kind, string $to): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO notifications_outbox
            (booking_id, channel, kind, recipient, status, attempts, next_attempt_at, created_at)
         VALUES (:bid, :ch, :kind, :to, "pending", 0, NOW(), NOW())'
    );
    $stmt->execute([
        ':bid' => $bookingId,
        ':ch' => $channel,
        ':kind' => $kind,
        ':to' => $to,
    ]);
}

/**
 * Procesa las notificaciones pendientes vencidas. Devuelve un resumen.
 * @return array{processed:int,sent:int,failed:int,retry:int}
 */
function process_outbox(PDO $pdo, array $config): array
{
    require_once __DIR__ . '/templates.php';
    require_once __DIR__ . '/channels.php';

    $maxAttempts = (int) ($config['outbox']['max_attempts'] ?? 5);
    $baseDelay = (int) ($config['outbox']['base_delay_sec'] ?? 60);
    $maxDelay = (int) ($config['outbox']['max_delay_sec'] ?? 3600);

    // Toma un lote de pendientes listas para intentar (join con la reserva).
    $rows = $pdo->query(
        'SELECT o.*, b.reference, b.locale, b.customer_name, b.customer_email,
                b.customer_phone, b.package_title, b.passengers, b.flight_date,
                b.mode, b.amount_now_cents, b.balance_cents, b.notes
           FROM notifications_outbox o
           JOIN bookings b ON b.id = o.booking_id
          WHERE o.status = "pending" AND o.next_attempt_at <= NOW()
          ORDER BY o.id ASC
          LIMIT 25'
    )->fetchAll();

    $summary = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'retry' => 0];

    $upd = $pdo->prepare(
        'UPDATE notifications_outbox
            SET status = :status, attempts = :attempts, last_error = :err,
                provider = :provider, next_attempt_at = :next, sent_at = :sent_at,
                updated_at = NOW()
          WHERE id = :id'
    );

    foreach ($rows as $row) {
        $summary['processed']++;
        $attempts = (int) $row['attempts'] + 1;

        $rendered = render_notification((string) $row['kind'], $row);
        $result = channel_send($config, (string) $row['channel'], (string) $row['recipient'], $rendered);

        if ($result['ok']) {
            $upd->execute([
                ':status' => 'sent',
                ':attempts' => $attempts,
                ':err' => null,
                ':provider' => $result['provider'],
                ':next' => null,
                ':sent_at' => date('Y-m-d H:i:s'),
                ':id' => $row['id'],
            ]);
            $summary['sent']++;
            log_line('outbox', 'sent', ['id' => $row['id'], 'ch' => $row['channel'], 'kind' => $row['kind'], 'attempt' => $attempts]);
            continue;
        }

        // Fallo: reintentar con backoff, o marcar failed si se agotaron.
        if ($attempts >= $maxAttempts) {
            $upd->execute([
                ':status' => 'failed',
                ':attempts' => $attempts,
                ':err' => $result['error'],
                ':provider' => $result['provider'],
                ':next' => null,
                ':sent_at' => null,
                ':id' => $row['id'],
            ]);
            $summary['failed']++;
            log_line('outbox', 'FAILED (agotado)', ['id' => $row['id'], 'ch' => $row['channel'], 'error' => $result['error']]);
        } else {
            $delay = min($baseDelay * (2 ** ($attempts - 1)), $maxDelay);
            $next = date('Y-m-d H:i:s', time() + $delay);
            $upd->execute([
                ':status' => 'pending',
                ':attempts' => $attempts,
                ':err' => $result['error'],
                ':provider' => $result['provider'],
                ':next' => $next,
                ':sent_at' => null,
                ':id' => $row['id'],
            ]);
            $summary['retry']++;
            log_line('outbox', 'retry', ['id' => $row['id'], 'ch' => $row['channel'], 'attempt' => $attempts, 'next' => $next, 'error' => $result['error']]);
        }
    }

    return $summary;
}
