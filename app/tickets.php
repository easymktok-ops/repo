<?php
declare(strict_types=1);

final class Tickets
{
    public static function code(int $number, string $kind = 'ticket'): string
    {
        return ($kind === 'reservation' ? '#CTG-' : '#JCD-') . $number;
    }

    /**
     * Emite el ticket de una orden pagada. Es idempotente: llamarla varias veces (reintentos del aviso de pago,
     * dos avisos a la vez) devuelve siempre el mismo ticket. El número sale de un contador que se incrementa
     * dentro de la misma transacción, así no hay saltos ni repetidos.
     *
     * @return array{number:int,code:string,created:bool}
     */
    public static function issue(int $orderId): array
    {
        try {
            return Db::transaction(static function (PDO $pdo) use ($orderId): array {
                // Bloquea la orden para que dos avisos simultáneos de la misma compra se turnen.
                $kind = (string) (Db::one('SELECT kind FROM orders WHERE id = ?' . (Db::isMysql() ? ' FOR UPDATE' : ''), [$orderId])['kind'] ?? 'ticket');
                $counter = $kind === 'reservation' ? 'reservation' : 'ticket';

                $existing = Db::one('SELECT number FROM tickets WHERE order_id = ?', [$orderId]);
                if ($existing !== null) {
                    return ['number' => (int) $existing['number'], 'code' => self::code((int) $existing['number'], $kind), 'created' => false];
                }

                Db::run('UPDATE counters SET value = value + 1 WHERE name = ?', [$counter]);
                $number = (int) Db::one('SELECT value FROM counters WHERE name = ?', [$counter])['value'];
                Db::run('INSERT INTO tickets (number, order_id, created_at) VALUES (?, ?, ?)', [$number, $orderId, Db::now()]);

                return ['number' => $number, 'code' => self::code($number, $kind), 'created' => true];
            });
        } catch (PDOException $e) {
            // Carrera improbable: otro proceso emitió el ticket de esta orden justo antes. Se devuelve ese.
            if (Db::isUniqueViolation($e)) {
                $existing = Db::one('SELECT number FROM tickets WHERE order_id = ?', [$orderId]);
                if ($existing !== null) {
                    return ['number' => (int) $existing['number'], 'code' => self::code((int) $existing['number'], (string) (Db::one('SELECT kind FROM orders WHERE id = ?', [$orderId])['kind'] ?? 'ticket')), 'created' => false];
                }
            }
            throw $e;
        }
    }
}
