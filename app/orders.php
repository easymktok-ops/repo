<?php
declare(strict_types=1);

final class OrderException extends RuntimeException
{
    /** @param array<string,string> $fieldErrors */
    public function __construct(string $message, public readonly array $fieldErrors = [])
    {
        parent::__construct($message);
    }
}

final class Orders
{
    public const DOC_TYPES = [
        'CC' => 'Cédula de ciudadanía',
        'CE' => 'Cédula de extranjería',
        'NIT' => 'NIT',
        'PA' => 'Pasaporte',
    ];

    private const DAYS = ['', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
    private const MONTHS = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public static function buyableEvent(?string $id = null): ?array
    {
        foreach (content('programacion.events', []) as $event) {
            if (!empty($event['buyable']) && ($id === null || $event['id'] === $id)) {
                return $event;
            }
        }
        return null;
    }

    public static function dateLabel(string $isoDate): string
    {
        $d = new DateTimeImmutable($isoDate);
        return ucfirst(self::DAYS[(int) $d->format('N')]) . ' ' . $d->format('j') . ' de ' . self::MONTHS[(int) $d->format('n')];
    }

    /** Próximas funciones semanales ofrecidas: [['value' => '2026-10-10', 'label' => 'Sábado 10 de octubre'], ...] */
    public static function functionOptions(array $event): array
    {
        $count = (int) ($GLOBALS['app_config']['checkout']['dates_offered'] ?? 8);
        $options = [];
        $date = next_weekly_date((int) $event['weekday'], (string) $event['time']);
        for ($i = 0; $i < $count; $i++) {
            $value = $date->format('Y-m-d');
            $options[] = ['value' => $value, 'label' => self::dateLabel($value)];
            $date = $date->modify('+7 days');
        }
        return $options;
    }

    /**
     * @param array<string,mixed>  $buyer  buyer_name, doc_type, doc_number, email, phone, city
     * @param array<string,int>    $qty    sku => cantidad
     * @param bool $dryRun solo valida (lanza OrderException si algo falla) y no guarda nada
     * @return array<string,mixed> la orden creada (fila de orders + items)
     */
    public static function create(string $eventId, string $functionDate, array $buyer, array $qty, string $gateway, bool $dryRun = false): array
    {
        $event = self::buyableEvent($eventId);
        $errors = [];
        if ($event === null) {
            throw new OrderException('La función seleccionada no está a la venta.');
        }

        $validDates = array_column(self::functionOptions($event), 'value');
        if (!in_array($functionDate, $validDates, true)) {
            $errors['funcion'] = 'Elige una de las fechas disponibles.';
        }

        // Los precios salen siempre de la configuración del servidor, nunca del formulario.
        $prices = [];
        foreach ($event['prices'] as $price) {
            $prices[$price['sku']] = $price;
        }
        $max = (int) ($GLOBALS['app_config']['checkout']['max_tickets_per_order'] ?? 10);
        $lines = [];
        $total = 0;
        $count = 0;
        foreach ($qty as $sku => $n) {
            $n = (int) $n;
            if ($n === 0) {
                continue;
            }
            if (!isset($prices[$sku]) || $n < 0) {
                throw new OrderException('Producto no válido.');
            }
            $lines[] = ['sku' => $sku, 'label' => $prices[$sku]['label'], 'unit_price' => (int) $prices[$sku]['amount'], 'qty' => $n];
            $total += (int) $prices[$sku]['amount'] * $n;
            $count += $n;
        }
        if ($count === 0) {
            $errors['cantidad'] = 'Elige al menos una entrada.';
        } elseif ($count > $max) {
            $errors['cantidad'] = "Máximo $max entradas por compra. Para grupos, escríbenos por WhatsApp.";
        }

        $buyer = self::cleanBuyer($buyer, $errors);
        if ($errors) {
            throw new OrderException('Revisa los campos marcados.', $errors);
        }
        if ($dryRun) {
            return [];
        }

        $now = Db::now();
        $publicId = bin2hex(random_bytes(16));

        return Db::transaction(function (PDO $pdo) use ($publicId, $eventId, $functionDate, $buyer, $lines, $total, $gateway, $now) {
            Db::run(
                'INSERT INTO orders (public_id, status, event_id, function_date, buyer_name, doc_type, doc_number, email, phone, city,
                                     total_amount, currency, gateway, created_at, updated_at)
                 VALUES (?, \'created\', ?, ?, ?, ?, ?, ?, ?, ?, ?, \'COP\', ?, ?, ?)',
                [$publicId, $eventId, $functionDate, $buyer['buyer_name'], $buyer['doc_type'], $buyer['doc_number'],
                 $buyer['email'], $buyer['phone'], $buyer['city'], $total, $gateway, $now, $now]
            );
            $orderId = (int) $pdo->lastInsertId();
            foreach ($lines as $line) {
                Db::run(
                    'INSERT INTO order_items (order_id, sku, label, unit_price, qty) VALUES (?, ?, ?, ?, ?)',
                    [$orderId, $line['sku'], $line['label'], $line['unit_price'], $line['qty']]
                );
            }
            return self::byId($orderId);
        });
    }

    /** @param array<string,string> $errors se completa por referencia */
    private static function cleanBuyer(array $in, array &$errors): array
    {
        $clean = static fn(string $k, int $max): string => mb_substr(trim(strip_tags((string) ($in[$k] ?? ''))), 0, $max);

        $buyer = [
            'buyer_name' => $clean('buyer_name', 160),
            'doc_type'   => strtoupper($clean('doc_type', 8)),
            'doc_number' => preg_replace('/[^A-Za-z0-9\-]/', '', $clean('doc_number', 32)) ?? '',
            'email'      => mb_strtolower($clean('email', 160)),
            'phone'      => $clean('phone', 32),
            'city'       => $clean('city', 80),
        ];

        if (mb_strlen($buyer['buyer_name']) < 3) {
            $errors['buyer_name'] = 'Escribe tu nombre completo o la razón social.';
        }
        if (!isset(self::DOC_TYPES[$buyer['doc_type']])) {
            $errors['doc_type'] = 'Elige el tipo de documento.';
        }
        if (strlen($buyer['doc_number']) < 5) {
            $errors['doc_number'] = 'Escribe el número de documento, sin puntos ni espacios.';
        }
        if (!filter_var($buyer['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Escribe un correo válido, por ejemplo nombre@correo.com.';
        }
        if (!preg_match('/^\+?[\d\s\-()]{7,20}$/', $buyer['phone'])) {
            $errors['phone'] = 'Escribe un teléfono válido, con indicativo si estás fuera de Colombia.';
        }
        if ($buyer['city'] === '') {
            $errors['city'] = 'Escribe tu ciudad.';
        }
        return $buyer;
    }

    public static function byId(int $id): ?array
    {
        return self::withItems(Db::one('SELECT * FROM orders WHERE id = ?', [$id]));
    }

    public static function byPublicId(string $publicId): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $publicId)) {
            return null;
        }
        return self::withItems(Db::one('SELECT * FROM orders WHERE public_id = ?', [$publicId]));
    }

    private static function withItems(?array $order): ?array
    {
        if ($order === null) {
            return null;
        }
        $order['items'] = Db::all('SELECT sku, label, unit_price, qty FROM order_items WHERE order_id = ? ORDER BY id', [(int) $order['id']]);
        $order['quantity'] = array_sum(array_column($order['items'], 'qty'));
        $ticket = Db::one('SELECT number FROM tickets WHERE order_id = ?', [(int) $order['id']]);
        $order['ticket'] = $ticket ? Tickets::code((int) $ticket['number']) : null;
        return $order;
    }

    public static function setGatewayRef(int $orderId, string $ref): void
    {
        Db::run('UPDATE orders SET gateway_ref = ?, status = \'pending\', updated_at = ? WHERE id = ? AND status = \'created\'', [$ref, Db::now(), $orderId]);
    }

    /**
     * Aplica el resultado de un pago confirmado por la pasarela. Nunca retrocede una orden aprobada.
     * @param array{status:string,amount:int,currency?:string,payment_id?:string,method?:string,fee?:int|null,net?:int|null} $payment
     */
    public static function applyPayment(int $orderId, array $payment): void
    {
        $now = Db::now();
        $approved = $payment['status'] === 'approved';
        Db::run(
            'UPDATE orders SET status = ?, payment_id = ?, payment_method = ?, fee_amount = ?, net_amount = ?,
                    paid_at = CASE WHEN ? = 1 AND paid_at IS NULL THEN ? ELSE paid_at END, updated_at = ?
             WHERE id = ? AND status NOT IN (\'approved\', \'refunded\')',
            [$payment['status'], $payment['payment_id'] ?? null, $payment['method'] ?? null, $payment['fee'] ?? null,
             $payment['net'] ?? null, $approved ? 1 : 0, $now, $now, $orderId]
        );
    }
}
