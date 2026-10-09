<?php
declare(strict_types=1);

final class Webhooks
{
    /**
     * Procesa un aviso de pago: verifica la firma, ignora repetidos, comprueba que el monto coincida y,
     * si el pago está aprobado, emite el ticket. Es seguro llamarlo varias veces con el mismo aviso.
     *
     * @param array<string,string> $headers cabeceras en minúsculas
     * @return array{ok:bool,result:string,ticket?:string}
     * @throws InvalidSignature si el aviso no es auténtico (el endpoint responde 401)
     */
    public static function handle(string $provider, array $headers, string $rawBody): array
    {
        $gateway = Payments::gateway($provider);
        $event = $gateway->verifyAndParse($headers, $rawBody);

        // Un mismo aviso solo se procesa una vez, aunque la pasarela lo reenvíe.
        try {
            Db::run(
                'INSERT INTO webhook_events (provider, event_id, payload, received_at) VALUES (?, ?, ?, ?)',
                [$provider, $event['event_id'], $rawBody, Db::now()]
            );
        } catch (PDOException $e) {
            if (!Db::isUniqueViolation($e)) {
                throw $e;
            }
            // Ya llegó antes. Solo se ignora si se procesó completo; si falló a medias, se reintenta.
            $seen = Db::one('SELECT processed_at FROM webhook_events WHERE provider = ? AND event_id = ?', [$provider, $event['event_id']]);
            if ($seen !== null && $seen['processed_at'] !== null) {
                return ['ok' => true, 'result' => 'duplicate'];
            }
        }

        $result = self::apply($gateway, $event);
        Db::run(
            'UPDATE webhook_events SET processed_at = ?, result = ? WHERE provider = ? AND event_id = ?',
            [Db::now(), $result['result'], $provider, $event['event_id']]
        );
        return $result;
    }

    private static function apply(PaymentGateway $gateway, array $event): array
    {
        $order = Orders::byPublicId($event['order_ref']);
        if ($order === null) {
            return ['ok' => true, 'result' => 'unknown_order'];
        }
        if ($order['gateway'] !== $gateway->name()) {
            return ['ok' => true, 'result' => 'gateway_mismatch'];
        }

        $payment = $gateway->fetchPayment($event);

        if ($payment['status'] === 'approved'
            && ($payment['amount'] !== (int) $order['total_amount'] || $payment['currency'] !== $order['currency'])) {
            error_log("Aviso con monto distinto: orden {$order['public_id']} esperaba {$order['total_amount']} {$order['currency']}, llegó {$payment['amount']} {$payment['currency']}");
            return ['ok' => false, 'result' => 'amount_mismatch'];
        }

        Orders::applyPayment((int) $order['id'], $payment);

        if ($payment['status'] === 'approved') {
            $ticket = Tickets::issue((int) $order['id']);
            // El correo nunca puede tumbar la confirmación del pago: si falla, queda pendiente de reenvío.
            try {
                Mail::sendConfirmation((int) $order['id']);
            } catch (Throwable $e) {
                error_log('Correo de la orden ' . $order['public_id'] . ': ' . $e->getMessage());
            }
            return ['ok' => true, 'result' => 'approved', 'ticket' => $ticket['code']];
        }
        return ['ok' => true, 'result' => $payment['status']];
    }
}
