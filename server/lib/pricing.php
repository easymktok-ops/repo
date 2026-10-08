<?php
/**
 * AUTORIDAD DE PRECIOS. Lee catalog.json (generado por el build de Astro desde
 * las Content Collections) y calcula el monto del lado del SERVIDOR. El cliente
 * solo manda slug + modo + pasajeros; el precio jamas viaja desde el front.
 */

declare(strict_types=1);

/** Carga y cachea el catalogo desde disco. @throws RuntimeException */
function load_catalog(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('catalog.json no encontrado en: ' . $path);
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['packages'])) {
        throw new RuntimeException('catalog.json invalido');
    }
    return $data;
}

/** Busca un paquete por slug. Devuelve null si no existe o no es reservable. */
function find_bookable_package(array $catalog, string $slug): ?array
{
    foreach ($catalog['packages'] as $p) {
        if (($p['slug'] ?? null) === $slug) {
            return !empty($p['bookable']) ? $p : null;
        }
    }
    return null;
}

/**
 * Calcula el cobro de AHORA para una reserva, en el idioma del servidor.
 *
 * $resolved (opcional) es el resultado 'available' de resolve_price() para la
 * fecha elegida (tarifas por fecha, centavos por persona). Sin el, el calculo
 * es el de siempre: priceFrom del catalogo y anticipo fijo por pasajero.
 *
 * @return array{
 *   currency:string, mode:string, passengers:int, unit_label:string,
 *   unit_amount_cents:int, quantity:int, amount_now_cents:int,
 *   price_per_person:int, total_full_cents:int, balance_cents:int, title:array
 * }
 * @throws InvalidArgumentException si los datos no son validos.
 */
function compute_charge(array $config, array $pkg, string $mode, int $passengers, ?array $resolved = null): array
{
    if ($passengers < 1) {
        throw new InvalidArgumentException('Numero de pasajeros invalido.');
    }
    $cap = $pkg['capacity'] ?? ['max' => 16];
    $min = (int) ($cap['min'] ?? 1);
    $max = (int) ($cap['max'] ?? 16);
    if ($passengers < $min || $passengers > $max) {
        throw new InvalidArgumentException(
            "Este paquete admite de {$min} a {$max} pasajeros."
        );
    }

    if ($resolved !== null) {
        return compute_charge_resolved($pkg, $mode, $passengers, $resolved);
    }

    $pricePerPerson = (int) $pkg['pricePerPerson'];
    if ($pricePerPerson <= 0) {
        throw new InvalidArgumentException('Paquete sin precio para reservar en linea.');
    }

    $totalFull = $pricePerPerson * $passengers;              // pesos
    $deposit   = (int) $config['deposit_per_passenger'];     // pesos por pasajero

    if ($mode === 'deposit') {
        // Anticipo por pasajero; el saldo se paga en sitio.
        $unitAmount = $deposit;
        $quantity   = $passengers;
        $amountNow  = $deposit * $passengers;
        $unitLabel  = 'Anticipo por pasajero';
    } elseif ($mode === 'full') {
        $unitAmount = $pricePerPerson;
        $quantity   = $passengers;
        $amountNow  = $totalFull;
        $unitLabel  = 'Precio por pasajero';
    } else {
        throw new InvalidArgumentException('Modo de pago invalido.');
    }

    return [
        'currency'          => strtoupper($pkg['currency'] ?? 'MXN'),
        'mode'              => $mode,
        'passengers'        => $passengers,
        'unit_label'        => $unitLabel,
        'unit_amount_cents' => $unitAmount * 100,
        'quantity'          => $quantity,
        'amount_now_cents'  => $amountNow * 100,
        'price_per_person'  => $pricePerPerson,
        'total_full_cents'  => $totalFull * 100,
        'balance_cents'     => ($totalFull - $amountNow) * 100,
        'title'             => $pkg['title'] ?? ['es' => $pkg['slug']],
    ];
}

/** Cobro con el precio y anticipo de la fecha (todo en centavos por persona). */
function compute_charge_resolved(array $pkg, string $mode, int $passengers, array $resolved): array
{
    if (($resolved['status'] ?? '') !== 'available') {
        throw new InvalidArgumentException('Esta fecha no esta disponible.');
    }
    $price = (int) $resolved['price'];
    $deposit = (int) $resolved['deposit'];
    if ($price <= 0) {
        throw new InvalidArgumentException('Paquete sin precio para reservar en linea.');
    }

    if ($mode === 'deposit') {
        $unit = $deposit;
        $unitLabel = 'Anticipo por pasajero';
    } elseif ($mode === 'full') {
        $unit = $price;
        $unitLabel = 'Precio por pasajero';
    } else {
        throw new InvalidArgumentException('Modo de pago invalido.');
    }
    $totalFull = $price * $passengers;
    $amountNow = $unit * $passengers;

    return [
        'currency'             => strtoupper($pkg['currency'] ?? 'MXN'),
        'mode'                 => $mode,
        'passengers'           => $passengers,
        'unit_label'           => $unitLabel,
        'unit_amount_cents'    => $unit,
        'quantity'             => $passengers,
        'amount_now_cents'     => $amountNow,
        'price_per_person'     => intdiv($price, 100),
        'total_full_cents'     => $totalFull,
        'balance_cents'        => $totalFull - $amountNow,
        'title'                => $pkg['title'] ?? ['es' => $pkg['slug']],
        'unit_price_cents'     => $price,
        'deposit_unit_cents'   => $deposit,
        'deposit_total_cents'  => $deposit * $passengers,
        'deposit_percent'      => (int) $resolved['deposit_percent'],
        'rule_id'              => $resolved['rule_id'] ?? null,
    ];
}
