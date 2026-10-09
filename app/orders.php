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

    /** Reservas: el voucher debe aclarar si el documento es pasaporte o cédula. */
    public const RESERVATION_DOC_TYPES = [
        'PA' => 'Pasaporte',
        'CC' => 'Cédula de ciudadanía',
        'CE' => 'Cédula de extranjería',
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

    /** Busca en la programación y en las reservas (Cartagena). */
    public static function eventById(string $id): ?array
    {
        foreach ([...content('programacion.events', []), ...content('reservas', [])] as $event) {
            if (!empty($event['buyable']) && $event['id'] === $id) {
                return $event;
            }
        }
        return null;
    }

    public static function isReservation(array $event): bool
    {
        return ($event['kind'] ?? 'ticket') === 'reservation';
    }

    /** Precio unitario en COP. Los productos en USD se cobran en COP con la TRM de referencia de la configuración. */
    public static function unitAmount(array $price): int
    {
        if (isset($price['usd'])) {
            return (int) (ceil($price['usd'] * self::fxRate() / 100) * 100);
        }
        return (int) $price['amount'];
    }

    public static function fxRate(): int
    {
        return max(1, (int) ($GLOBALS['app_config']['checkout']['usd_cop_rate'] ?? 4000));
    }

    /** Personas ya reservadas o en pago (30 min) para una fecha. */
    public static function seatsTaken(string $eventId, string $date): int
    {
        $row = Db::one(
            "SELECT COALESCE(SUM(i.qty), 0) AS n FROM orders o JOIN order_items i ON i.order_id = o.id
             WHERE o.event_id = ? AND o.function_date = ?
               AND (o.status IN ('approved', 'in_process') OR (o.status IN ('created', 'pending') AND o.created_at > ?))",
            [$eventId, $date, date('Y-m-d H:i:s', time() - 1800)]
        );
        return (int) ($row['n'] ?? 0);
    }

    public static function seatsLeft(array $event, string $date): ?int
    {
        return isset($event['capacity']) ? max(0, (int) $event['capacity'] - self::seatsTaken($event['id'], $date)) : null;
    }

    public static function dateLabel(string $isoDate): string
    {
        $d = new DateTimeImmutable($isoDate);
        return ucfirst(self::DAYS[(int) $d->format('N')]) . ' ' . $d->format('j') . ' de ' . self::MONTHS[(int) $d->format('n')];
    }

    /** Próximas funciones semanales ofrecidas: [['value' => '2026-10-10', 'label' => 'Sábado 10 de octubre'], ...] */
    public static function functionOptions(array $event): array
    {
        $options = [];
        if (self::isReservation($event)) {
            $count = (int) ($event['dates_offered'] ?? 12);
            $day = (new DateTimeImmutable('today'))->modify('+' . (int) ($event['lead_days'] ?? 1) . ' days');
            while (count($options) < $count) {
                if (in_array((int) $day->format('N'), $event['weekdays'], true)) {
                    $value = $day->format('Y-m-d');
                    $options[] = ['value' => $value, 'label' => self::dateLabel($value)];
                }
                $day = $day->modify('+1 day');
            }
            return $options;
        }
        $count = (int) ($GLOBALS['app_config']['checkout']['dates_offered'] ?? 8);
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
        $event = self::eventById($eventId);
        $errors = [];
        $reservation = $event !== null && self::isReservation($event);
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
        $max = $reservation ? (int) $event['max_party'] : (int) ($GLOBALS['app_config']['checkout']['max_tickets_per_order'] ?? 10);
        $lines = [];
        $total = 0;
        $usd = 0;
        $count = 0;
        foreach ($qty as $sku => $n) {
            $n = (int) $n;
            if ($n === 0) {
                continue;
            }
            if (!isset($prices[$sku]) || $n < 0) {
                throw new OrderException('Producto no válido.');
            }
            $unit = self::unitAmount($prices[$sku]);
            $lines[] = ['sku' => $sku, 'label' => $prices[$sku]['label'], 'unit_price' => $unit, 'qty' => $n];
            $total += $unit * $n;
            $usd += (int) ($prices[$sku]['usd'] ?? 0) * $n;
            $count += $n;
        }
        if ($count === 0) {
            $errors['cantidad'] = $reservation ? 'Elige cuántas personas van a asistir.' : 'Elige al menos una entrada.';
        } elseif ($count > $max) {
            $errors['cantidad'] = $reservation
                ? "Cada reserva es una mesa de máximo $max personas."
                : "Máximo $max entradas por compra. Para grupos, escríbenos por WhatsApp.";
        } elseif (isset($errors['funcion']) === false && ($left = self::seatsLeft($event, $functionDate)) !== null && $count > $left) {
            $errors['cantidad'] = $left === 0 ? 'Esa fecha ya no tiene cupos. Elige otra.' : "Solo quedan $left cupos para esa fecha.";
        }

        $buyer = self::cleanBuyer($buyer, $errors, $reservation);
        if ($errors) {
            throw new OrderException('Revisa los campos marcados.', $errors);
        }
        if ($dryRun) {
            return [];
        }

        $now = Db::now();
        $publicId = bin2hex(random_bytes(16));
        $kind = $reservation ? 'reservation' : 'ticket';

        return Db::transaction(function (PDO $pdo) use ($publicId, $eventId, $functionDate, $buyer, $lines, $total, $usd, $kind, $event, $count, $gateway, $now) {
            // Un solo turno para crear órdenes: así dos compras a la vez no se pasan del cupo del día.
            Db::one("SELECT value FROM counters WHERE name = 'ticket'" . (Db::isMysql() ? ' FOR UPDATE' : ''));
            $left = self::seatsLeft($event, $functionDate);
            if ($left !== null && $count > $left) {
                throw new OrderException('Esa fecha se acaba de llenar.', ['cantidad' => $left === 0 ? 'Esa fecha ya no tiene cupos. Elige otra.' : "Solo quedan $left cupos para esa fecha."]);
            }
            Db::run(
                'INSERT INTO orders (public_id, status, event_id, function_date, kind, buyer_name, first_name, last_name, doc_type, doc_number, email, phone, city,
                                     total_amount, currency, usd_total, fx_rate, gateway, created_at, updated_at)
                 VALUES (?, \'created\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'COP\', ?, ?, ?, ?, ?)',
                [$publicId, $eventId, $functionDate, $kind, $buyer['buyer_name'], $buyer['first_name'], $buyer['last_name'],
                 $buyer['doc_type'], $buyer['doc_number'], $buyer['email'], $buyer['phone'], $buyer['city'], $total,
                 $usd > 0 ? $usd : null, $usd > 0 ? self::fxRate() : null, $gateway, $now, $now]
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
    private static function cleanBuyer(array $in, array &$errors, bool $reservation = false): array
    {
        $clean = static fn(string $k, int $max): string => mb_substr(trim(strip_tags((string) ($in[$k] ?? ''))), 0, $max);

        $buyer = [
            'buyer_name' => $clean('buyer_name', 160),
            'first_name' => $clean('first_name', 80),
            'last_name'  => $clean('last_name', 80),
            'doc_type'   => strtoupper($clean('doc_type', 8)),
            'doc_number' => preg_replace('/[^A-Za-z0-9\-]/', '', $clean('doc_number', 32)) ?? '',
            'email'      => mb_strtolower($clean('email', 160)),
            'phone'      => $clean('phone', 32),
            'city'       => $clean('city', 80),
        ];

        if ($reservation) {
            if (mb_strlen($buyer['first_name']) < 2) {
                $errors['first_name'] = 'Escribe tu nombre.';
            }
            if (mb_strlen($buyer['last_name']) < 2) {
                $errors['last_name'] = 'Escribe tu apellido.';
            }
            $buyer['buyer_name'] = trim($buyer['first_name'] . ' ' . $buyer['last_name']);
        } elseif (mb_strlen($buyer['buyer_name']) < 3) {
            $errors['buyer_name'] = 'Escribe tu nombre completo o la razón social.';
        }
        $docTypes = $reservation ? self::RESERVATION_DOC_TYPES : self::DOC_TYPES;
        if (!isset($docTypes[$buyer['doc_type']])) {
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
        if (!$reservation && $buyer['city'] === '') {
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
        $order['ticket'] = $ticket ? Tickets::code((int) $ticket['number'], (string) $order['kind']) : null;
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
