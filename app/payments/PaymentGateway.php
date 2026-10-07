<?php
declare(strict_types=1);

class InvalidSignature extends RuntimeException {}
class GatewayNotReady extends RuntimeException {}

/**
 * Contrato que debe cumplir cualquier pasarela. El resto del sitio (compra, avisos, tickets)
 * solo conoce esta interfaz, así que cambiar de pasarela no obliga a reescribirlo.
 */
interface PaymentGateway
{
    /** Nombre guardado en orders.gateway. */
    public function name(): string;

    /** ¿Tiene todo configurado para cobrar? Si no, /comprar muestra un aviso en lugar de crear órdenes. */
    public function isReady(): bool;

    /**
     * Crea el cobro en la pasarela para esta orden.
     * @return array{redirect_url:string,gateway_ref:string}
     */
    public function createCheckout(array $order): array;

    /**
     * Verifica la firma del aviso y lo interpreta. Debe lanzar InvalidSignature si no es auténtico.
     * @param array<string,string> $headers cabeceras en minúsculas
     * @return array{event_id:string,order_ref:string,payload:array}
     */
    public function verifyAndParse(array $headers, string $rawBody): array;

    /**
     * Estado real del pago asociado al aviso. Las pasarelas reales deben consultarlo a su API
     * y no fiarse del cuerpo recibido.
     * @return array{status:string,amount:int,currency:string,payment_id:string,method:string,fee:?int,net:?int}
     */
    public function fetchPayment(array $event): array;
}
