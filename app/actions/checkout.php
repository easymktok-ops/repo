<?php
declare(strict_types=1);

$event = Orders::eventById($checkoutEvent ?? 'medellin');
if ($event === null) {
    http_response_code(404);
    view('404');
    return;
}

$gateway = Payments::gateway();
$ready = $gateway->isReady();
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ready) {
    $old = $_POST;

    if (!csrf_valid($_POST['_csrf'] ?? null)) {
        $errors['_form'] = 'La sesión expiró. Vuelve a enviar el formulario.';
    } elseif (trim((string) ($_POST['website'] ?? '')) !== '') {
        // Honeypot: un bot rellenó el campo oculto. Se responde como si todo hubiera ido bien.
        header('Location: /', true, 303);
        return;
    } else {
        $reservation = Orders::isReservation($event);
        $accepted = ($_POST['acepta'] ?? '') === '1';
        $acceptedCover = !$reservation || ($_POST['acepta_cover'] ?? '') === '1';
        $qty = [];
        foreach ($event['prices'] as $price) {
            $qty[$price['sku']] = (int) ($_POST['qty'][$price['sku']] ?? 0);
        }
        if ($reservation) {
            $qty = ['cover' => (int) ($_POST['personas'] ?? 0)];
        }
        $buyer = [
            'buyer_name' => $_POST['buyer_name'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name'  => $_POST['last_name'] ?? '',
            'doc_type'   => $_POST['doc_type'] ?? '',
            'doc_number' => $_POST['doc_number'] ?? '',
            'email'      => $_POST['email'] ?? '',
            'phone'      => $_POST['phone'] ?? '',
            'city'       => $_POST['city'] ?? '',
        ];

        try {
            // Sin la casilla de términos solo se valida, para mostrar todos los errores de una vez.
            $order = Orders::create($event['id'], (string) ($_POST['funcion'] ?? ''), $buyer, $qty, $gateway->name(), !($accepted && $acceptedCover));
        } catch (OrderException $e) {
            $errors = $e->fieldErrors ?: ['_form' => $e->getMessage()];
            $order = null;
        }
        if (!$acceptedCover) {
            $errors['acepta_cover'] = 'Debes aceptar los términos de la reserva para continuar.';
        }
        if (!$accepted) {
            $errors['acepta'] = 'Debes aceptar la política de tratamiento de datos para continuar.';
        }

        if ($order && !$errors) {
            try {
                $checkout = $gateway->createCheckout($order);
                Orders::setGatewayRef((int) $order['id'], $checkout['gateway_ref']);
                unset($_SESSION['csrf']);
                header('Location: ' . $checkout['redirect_url'], true, 303);
                return;
            } catch (Throwable $e) {
                error_log('No se pudo crear el cobro de la orden ' . $order['public_id'] . ': ' . $e->getMessage());
                Db::run("UPDATE orders SET status = 'cancelled', updated_at = ? WHERE id = ?", [Db::now(), (int) $order['id']]);
                $errors['_form'] = 'No pudimos iniciar el pago. Inténtalo de nuevo en unos minutos o escríbenos por WhatsApp.';
            }
        }
    }
}

view(Orders::isReservation($event) ? 'reserva' : 'checkout', [
    'event'  => $event,
    'ready'  => $ready,
    'errors' => $errors,
    'old'    => $old,
    'dates'  => Orders::functionOptions($event),
]);
