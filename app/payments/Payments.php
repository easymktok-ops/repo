<?php
declare(strict_types=1);

final class Payments
{
    public static function provider(): string
    {
        return (string) ($GLOBALS['app_config']['payments']['provider'] ?? 'simulated');
    }

    public static function gateway(?string $provider = null): PaymentGateway
    {
        $provider ??= self::provider();
        $cfg = $GLOBALS['app_config']['payments'] ?? [];

        return match ($provider) {
            'simulated'   => new SimulatedGateway((string) ($cfg['simulated_secret'] ?? '')),
            'mercadopago' => new MercadoPagoGateway((string) ($cfg['access_token'] ?? ''), (string) ($cfg['webhook_secret'] ?? '')),
            default       => throw new GatewayNotReady("Pasarela desconocida: $provider"),
        };
    }
}
