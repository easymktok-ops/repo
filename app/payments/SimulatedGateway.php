<?php
declare(strict_types=1);

/**
 * Pasarela de práctica para desarrollo y pruebas de punta a punta. Funciona solo fuera de producción.
 * Firma sus avisos con HMAC-SHA256 igual que lo haría una pasarela real, para ejercitar el mismo camino.
 */
final class SimulatedGateway implements PaymentGateway
{
    public function __construct(private string $secret) {}

    public function name(): string
    {
        return 'simulated';
    }

    public function isReady(): bool
    {
        return APP_ENV !== 'production';
    }

    public function createCheckout(array $order): array
    {
        if (!$this->isReady()) {
            throw new GatewayNotReady('La pasarela simulada no se puede usar en producción.');
        }
        return [
            'redirect_url' => url('/pago-simulado/?orden=' . $order['public_id']),
            'gateway_ref'  => 'sim_' . $order['public_id'],
        ];
    }

    public function verifyAndParse(array $headers, string $rawBody): array
    {
        $given = $headers['x-simulated-signature'] ?? '';
        if (!hash_equals($this->sign($rawBody), $given)) {
            throw new InvalidSignature('Firma del aviso no válida.');
        }
        $payload = json_decode($rawBody, true);
        if (!is_array($payload) || empty($payload['event_id']) || empty($payload['order_ref'])) {
            throw new InvalidSignature('Aviso mal formado.');
        }
        return ['event_id' => (string) $payload['event_id'], 'order_ref' => (string) $payload['order_ref'], 'payload' => $payload];
    }

    public function fetchPayment(array $event): array
    {
        $p = $event['payload'];
        return [
            'status'     => (string) ($p['status'] ?? 'rejected'),
            'amount'     => (int) ($p['amount'] ?? 0),
            'currency'   => (string) ($p['currency'] ?? 'COP'),
            'payment_id' => (string) ($p['payment_id'] ?? ''),
            'method'     => (string) ($p['method'] ?? 'simulado'),
            'fee'        => isset($p['fee']) ? (int) $p['fee'] : null,
            'net'        => isset($p['net']) ? (int) $p['net'] : null,
        ];
    }

    public function sign(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, $this->secret);
    }

    /** Arma un aviso firmado, como lo enviaría la pasarela. @return array{headers:array<string,string>,body:string} */
    public function buildNotification(string $orderRef, string $status, int $amount): array
    {
        $fee = (int) round($amount * 0.028) + 800;
        $body = json_encode([
            'event_id'   => bin2hex(random_bytes(8)),
            'order_ref'  => $orderRef,
            'status'     => $status,
            'amount'     => $amount,
            'currency'   => 'COP',
            'payment_id' => 'simpay_' . bin2hex(random_bytes(4)),
            'method'     => 'tarjeta simulada',
            'fee'        => $status === 'approved' ? $fee : null,
            'net'        => $status === 'approved' ? $amount - $fee : null,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return ['headers' => ['x-simulated-signature' => $this->sign($body)], 'body' => $body];
    }
}
