<?php
declare(strict_types=1);

/** @var string $adminPath */
$method = $_SERVER['REQUEST_METHOD'];
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

if ($adminPath === '/admin/login') {
    $error = null;
    if ($method === 'POST') {
        if (!csrf_valid($_POST['_csrf'] ?? null)) {
            $error = 'La sesión expiró. Inténtalo de nuevo.';
        } elseif (Admin::locked()) {
            $error = 'Demasiados intentos. Espera 15 minutos.';
        } elseif (Admin::login((string) ($_POST['user'] ?? ''), (string) ($_POST['pass'] ?? ''))) {
            header('Location: /admin/', true, 303);
            return;
        } else {
            $error = 'Usuario o clave incorrectos.';
        }
    }
    view('admin/login', ['error' => $error, 'enabled' => Admin::enabled()]);
    return;
}

Admin::require();

if ($adminPath === '/admin/salir' && $method === 'POST' && csrf_valid($_POST['_csrf'] ?? null)) {
    Admin::logout();
    header('Location: /admin/login/', true, 303);
    return;
}

$ymd = static fn(?string $v): ?string => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
$eventId = isset($_GET['evento']) && preg_match('/^[a-z]{3,20}$/', (string) $_GET['evento']) ? (string) $_GET['evento'] : null;

if (preg_match('#^/admin/exportar/(puerta|contador|reservas)$#', $adminPath, $m)) {
    $kind = $m[1];
    if ($kind === 'puerta') {
        $orders = Admin::paidOrders($eventId ?? 'medellin', $ymd($_GET['fecha'] ?? null));
        $rows = array_map(static fn($o) => [
            $o['function_date'], $o['code'], $o['buyer_name'], $o['doc_type'] . ' ' . $o['doc_number'], $o['qty'], $o['concept'], $o['phone'], $o['email'], '',
        ], $orders);
        Admin::csv('lista-puerta-' . ($ymd($_GET['fecha'] ?? null) ?? 'todas') . '.csv',
            ['Fecha', 'Ticket', 'Nombre', 'Documento', 'Entradas', 'Detalle', 'Teléfono', 'Correo', 'Ingresó'], $rows);
    }
    if ($kind === 'reservas') {
        $orders = Admin::paidOrders('cartagena', $ymd($_GET['fecha'] ?? null));
        $rows = array_map(static fn($o) => [
            $o['code'], $o['first_name'], $o['last_name'], (Orders::RESERVATION_DOC_TYPES[$o['doc_type']] ?? $o['doc_type']) . ' ' . $o['doc_number'],
            $o['email'], $o['phone'], $o['function_date'], $o['qty'], $o['total_amount'],
        ], $orders);
        Admin::csv('reservas-cartagena-' . ($ymd($_GET['fecha'] ?? null) ?? 'todas') . '.csv',
            ['Voucher', 'Nombre', 'Apellido', 'Identificación', 'Email', 'Teléfono', 'Fecha de la reserva', 'Personas', 'Precio total recibido (COP)'], $rows);
    }
    $orders = Admin::paidOrders($eventId, null, $ymd($_GET['desde'] ?? null), $ymd($_GET['hasta'] ?? null), 'paid');
    $rows = array_map(static fn($o) => [
        substr((string) $o['paid_at'], 0, 10), $o['code'], $o['event_id'], $o['function_date'], $o['buyer_name'], $o['doc_type'], $o['doc_number'],
        $o['email'], $o['concept'], $o['qty'], $o['total_amount'], $o['fee_amount'] ?? '', $o['net_amount'] ?? '',
        $o['usd_total'] ?? '', $o['fx_rate'] ?? '', $o['payment_method'] ?? '', $o['payment_id'] ?? '',
    ], $orders);
    Admin::csv('reporte-contador-' . date('Ymd') . '.csv',
        ['Fecha de pago', 'Ticket/voucher', 'Evento', 'Fecha de la función', 'Nombre o razón social', 'Tipo doc.', 'Número doc.', 'Correo', 'Concepto', 'Cantidad',
         'Total cobrado (COP)', 'Comisión pasarela', 'Neto recibido', 'USD', 'TRM de referencia', 'Medio de pago', 'ID de pago'], $rows);
}

if ($adminPath === '/admin/pedidos') {
    $sql = 'SELECT o.id, o.public_id, o.status, o.event_id, o.function_date, o.buyer_name, o.email, o.total_amount, o.created_at, o.kind, t.number AS ticket_number
            FROM orders o LEFT JOIN tickets t ON t.order_id = o.id WHERE 1=1';
    $p = [];
    $status = (string) ($_GET['estado'] ?? '');
    if ($status !== '' && preg_match('/^[a-z_]{3,16}$/', $status)) { $sql .= ' AND o.status = ?'; $p[] = $status; }
    if ($eventId) { $sql .= ' AND o.event_id = ?'; $p[] = $eventId; }
    if ($d = $ymd($_GET['fecha'] ?? null)) { $sql .= ' AND o.function_date = ?'; $p[] = $d; }
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($q !== '') { $sql .= ' AND (o.buyer_name LIKE ? OR o.email LIKE ? OR o.doc_number LIKE ?)'; $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%'; array_push($p, $like, $like, $like); }
    $sql .= ' ORDER BY o.id DESC LIMIT 300';
    view('admin/pedidos', ['orders' => Db::all($sql, $p), 'filters' => ['estado' => $status, 'evento' => $eventId, 'fecha' => $ymd($_GET['fecha'] ?? null), 'q' => $q]]);
    return;
}

if ($adminPath === '/admin/contenido') {
    $saved = false;
    $keys = [
        'whatsapp_number' => 'WhatsApp (solo números, con indicativo, ej. 573001234567)',
        'whatsapp_message' => 'Mensaje inicial de WhatsApp',
        'instagram' => 'Instagram (URL)',
        'facebook' => 'Facebook (URL)',
        'tripadvisor' => 'Tripadvisor (URL)',
    ];
    if ($method === 'POST' && csrf_valid($_POST['_csrf'] ?? null)) {
        $site = [];
        foreach (array_keys($keys) as $k) {
            $v = trim(strip_tags((string) ($_POST[$k] ?? '')));
            if ($k === 'whatsapp_number') { $v = preg_replace('/\D/', '', $v) ?? ''; }
            if (in_array($k, ['instagram', 'facebook', 'tripadvisor'], true) && $v !== '' && !preg_match('#^https://#i', $v)) { continue; }
            $site[$k] = mb_substr($v, 0, 300);
        }
        Admin::saveContent([
            'site' => $site,
            'footer' => ['review_quote' => mb_substr(trim(strip_tags((string) ($_POST['review_quote'] ?? ''))), 0, 400), 'review_author' => mb_substr(trim(strip_tags((string) ($_POST['review_author'] ?? ''))), 0, 80)],
        ]);
        header('Location: /admin/contenido/?guardado=1', true, 303);
        return;
    }
    view('admin/contenido', ['keys' => $keys, 'saved' => isset($_GET['guardado'])]);
    return;
}

// Resumen: cupos y ventas por fecha próxima.
$summary = [];
foreach ([Orders::eventById('medellin'), Orders::eventById('cartagena')] as $ev) {
    if (!$ev) { continue; }
    foreach (array_slice(Orders::functionOptions($ev), 0, 6) as $d) {
        $row = Db::one("SELECT COUNT(*) AS orders, COALESCE(SUM(total_amount),0) AS revenue FROM orders WHERE event_id = ? AND function_date = ? AND status = 'approved'", [$ev['id'], $d['value']]);
        $summary[] = ['event' => $ev, 'date' => $d, 'orders' => (int) $row['orders'], 'revenue' => (int) $row['revenue'], 'people' => Orders::seatsTaken($ev['id'], $d['value'])];
    }
}
view('admin/inicio', ['summary' => $summary]);
