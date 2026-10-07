<?php
declare(strict_types=1);

/**
 * Mercado Pago (Checkout Pro con API de Orders).
 *
 * PENDIENTE: no se escribe ninguna llamada hasta tener la documentación oficial a la vista
 * (crear la orden, firma x-signature del aviso, consulta del pago, comisión y neto). Inventar
 * esos detalles podría dejar pagos sin validar o aceptar avisos falsos.
 * Mientras tanto isReady() es false y /comprar no crea órdenes.
 *
 * Al implementarla, solo cambia este archivo; el resto del sitio usa la interfaz PaymentGateway.
 */
final class MercadoPagoGateway implements PaymentGateway
{
    public function __construct(private string $accessToken, private string $webhookSecret) {}

    public function name(): string
    {
        return 'mercadopago';
    }

    public function isReady(): bool
    {
        return false;
    }

    public function createCheckout(array $order): array
    {
        throw new GatewayNotReady('Integración con Mercado Pago pendiente.');
    }

    public function verifyAndParse(array $headers, string $rawBody): array
    {
        throw new InvalidSignature('Integración con Mercado Pago pendiente: no se acepta ningún aviso.');
    }

    public function fetchPayment(array $event): array
    {
        throw new GatewayNotReady('Integración con Mercado Pago pendiente.');
    }
}
