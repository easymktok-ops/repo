<?php
declare(strict_types=1);

// Pruebas del motor de compras. Uso: php tests/run.php
// Usan una base SQLite temporal; no tocan datos reales.
$dbFile = sys_get_temp_dir() . '/jcd-test-' . getmypid() . '.sqlite';
@unlink($dbFile);
putenv('DB_DSN=sqlite:' . $dbFile);
putenv('APP_ENV=development');
putenv('PAYMENT_PROVIDER=simulated');

define('PUBLIC_DIR', dirname(__DIR__) . '/public');
require dirname(__DIR__) . '/app/bootstrap.php';

$passed = 0;
$failed = [];
function check(string $name, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo "  ok  $name\n";
    } else {
        $failed[] = $name;
        echo "  FALLA  $name" . ($detail !== '' ? " ($detail)" : '') . "\n";
    }
}
function throwsOrder(callable $fn): ?OrderException
{
    try {
        $fn();
    } catch (OrderException $e) {
        return $e;
    }
    return null;
}

passthru('php ' . escapeshellarg(dirname(__DIR__) . '/tools/migrate.php'), $rc);
check('migración aplicada', $rc === 0);

$event = Orders::buyableEvent();
$date = Orders::functionOptions($event)[0]['value'];
$buyer = ['buyer_name' => 'Ana Pérez', 'doc_type' => 'CC', 'doc_number' => '1234567890', 'email' => 'ana@example.com', 'phone' => '+57 300 111 2233', 'city' => 'Medellín'];
$gw = new SimulatedGateway('dev-secret-no-usar-en-produccion');

echo "Pedidos\n";
$order = Orders::create('medellin', $date, $buyer, ['menu' => 2, 'tapeo' => 1, 'vegetariano' => 1], 'simulated');
check('total = 3 x 195.000 + 1 x 180.000 (765000)', $order['total_amount'] === 765000, (string) $order['total_amount']);
check('orden nueva en estado created', $order['status'] === 'created');
check('id público de 32 caracteres', strlen($order['public_id']) === 32);
check('cantidad 4', $order['quantity'] === 4);

$e = throwsOrder(fn() => Orders::create('medellin', $date, $buyer, ['menu' => 0, 'tapeo' => 0], 'simulated'));
check('rechaza compra sin entradas', $e !== null && isset($e->fieldErrors['cantidad']));
$e = throwsOrder(fn() => Orders::create('medellin', $date, $buyer, ['menu' => 11], 'simulated'));
check('rechaza más de 10 entradas', $e !== null && isset($e->fieldErrors['cantidad']));
$e = throwsOrder(fn() => Orders::create('medellin', $date, $buyer, ['vip' => 1], 'simulated'));
check('rechaza producto que no existe', $e !== null);
$e = throwsOrder(fn() => Orders::create('medellin', '2020-01-04', $buyer, ['menu' => 1], 'simulated'));
check('rechaza fecha no ofrecida', $e !== null && isset($e->fieldErrors['funcion']));
$e = throwsOrder(fn() => Orders::create('bogota', $date, $buyer, ['menu' => 1], 'simulated'));
check('rechaza función que no está a la venta', $e !== null);
$bad = $buyer;
$bad['email'] = 'no-es-correo';
$bad['doc_type'] = 'XX';
$e = throwsOrder(fn() => Orders::create('medellin', $date, $bad, ['menu' => 1], 'simulated'));
check('valida correo y tipo de documento', $e !== null && isset($e->fieldErrors['email'], $e->fieldErrors['doc_type']));

echo "Tickets\n";
$t1 = Tickets::issue((int) $order['id']);
check('primer ticket es #JCD-1001', $t1['code'] === '#JCD-1001', $t1['code']);
$t1b = Tickets::issue((int) $order['id']);
check('emitir otra vez devuelve el mismo ticket', $t1b['code'] === '#JCD-1001' && $t1b['created'] === false);
$order2 = Orders::create('medellin', $date, $buyer, ['tapeo' => 1], 'simulated');
$t2 = Tickets::issue((int) $order2['id']);
check('segundo ticket es #JCD-1002', $t2['code'] === '#JCD-1002', $t2['code']);

echo "Avisos de pago\n";
$order3 = Orders::create('medellin', $date, $buyer, ['menu' => 1], 'simulated');
$n = $gw->buildNotification($order3['public_id'], 'approved', 195000);
try {
    Webhooks::handle('simulated', ['x-simulated-signature' => 'falsa'], $n['body']);
    check('rechaza firma falsa', false);
} catch (InvalidSignature $e) {
    check('rechaza firma falsa', true);
}
check('firma falsa no cambió la orden', Orders::byId((int) $order3['id'])['status'] === 'created');

$r = Webhooks::handle('simulated', $n['headers'], $n['body']);
check('aviso aprobado emite ticket #JCD-1003', ($r['ticket'] ?? '') === '#JCD-1003', json_encode($r));
$o3 = Orders::byId((int) $order3['id']);
check('orden queda approved con comisión y neto', $o3['status'] === 'approved' && $o3['fee_amount'] > 0 && $o3['net_amount'] === 195000 - $o3['fee_amount']);
$r = Webhooks::handle('simulated', $n['headers'], $n['body']);
check('el mismo aviso repetido se ignora', $r['result'] === 'duplicate');
$n2 = $gw->buildNotification($order3['public_id'], 'approved', 195000);
$r = Webhooks::handle('simulated', $n2['headers'], $n2['body']);
check('otro aviso de la misma compra no duplica el ticket', ($r['ticket'] ?? '') === '#JCD-1003');
check('hay 3 tickets en total', (int) Db::one('SELECT COUNT(*) c FROM tickets')['c'] === 3);

$order4 = Orders::create('medellin', $date, $buyer, ['menu' => 1], 'simulated');
$n = $gw->buildNotification($order4['public_id'], 'approved', 1000);
$r = Webhooks::handle('simulated', $n['headers'], $n['body']);
check('monto distinto al de la orden no emite ticket', $r['ok'] === false && Orders::byId((int) $order4['id'])['ticket'] === null);

$order5 = Orders::create('medellin', $date, $buyer, ['menu' => 1], 'simulated');
$n = $gw->buildNotification($order5['public_id'], 'rejected', 195000);
$r = Webhooks::handle('simulated', $n['headers'], $n['body']);
check('pago rechazado no emite ticket', Orders::byId((int) $order5['id'])['status'] === 'rejected' && Orders::byId((int) $order5['id'])['ticket'] === null);
$n = $gw->buildNotification($order5['public_id'], 'approved', 195000);
$r = Webhooks::handle('simulated', $n['headers'], $n['body']);
check('un reintento aprobado después de un rechazo sí emite ticket', ($r['ticket'] ?? '') !== '' && Orders::byId((int) $order5['id'])['status'] === 'approved');

$n = $gw->buildNotification($order3['public_id'], 'rejected', 195000);
Webhooks::handle('simulated', $n['headers'], $n['body']);
check('un rechazo tardío no revierte una orden aprobada', Orders::byId((int) $order3['id'])['status'] === 'approved');

$n = $gw->buildNotification(str_repeat('a', 32), 'approved', 195000);
$r = Webhooks::handle('simulated', $n['headers'], $n['body']);
check('aviso de una orden inexistente se ignora sin error', $r['result'] === 'unknown_order');

$mp = Payments::gateway('mercadopago');
try {
    $mp->verifyAndParse([], '{}');
    check('Mercado Pago sin implementar no acepta avisos', false);
} catch (InvalidSignature $e) {
    check('Mercado Pago sin implementar no acepta avisos', true);
}

echo "Concurrencia (14 procesos a la vez)\n";
$before = (int) Db::one("SELECT value FROM counters WHERE name = 'ticket'")['value'];
$procs = [];
for ($i = 0; $i < 14; $i++) {
    $p = proc_open(['php', __DIR__ . '/worker.php', $date], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['DB_DSN' => 'sqlite:' . $dbFile, 'APP_ENV' => 'development'] + getenv());
    $procs[] = [$p, $pipes];
}
$numbers = [];
$errors = '';
foreach ($procs as [$p, $pipes]) {
    $out = trim((string) stream_get_contents($pipes[1]));
    $errors .= (string) stream_get_contents($pipes[2]);
    proc_close($p);
    if (ctype_digit($out)) {
        $numbers[] = (int) $out;
    }
}
sort($numbers);
check('los 14 procesos emitieron ticket', count($numbers) === 14, $errors);
check('sin números repetidos', count(array_unique($numbers)) === 14);
check('numeración continua, sin saltos', $numbers === range($before + 1, $before + 14), implode(',', $numbers));

echo "Reservas Cartagena\n";
$ctg = Orders::eventById('cartagena');
$ctgDates = Orders::functionOptions($ctg);
$ctgDate = $ctgDates[0]['value'];
check('fechas solo jueves, viernes y sábado', count(array_filter($ctgDates, fn($d) => in_array((int) (new DateTimeImmutable($d['value']))->format('N'), [4, 5, 6], true))) === count($ctgDates));
$guest = ['first_name' => 'John', 'last_name' => 'Smith', 'doc_type' => 'PA', 'doc_number' => 'X1234567', 'email' => 'john@example.com', 'phone' => '+1 555 123 4567'];
$r1 = Orders::create('cartagena', $ctgDate, $guest, ['cover' => 4], 'simulated');
check('cover USD 5 por persona cobrado en COP', $r1['total_amount'] === 4 * Orders::unitAmount($ctg['prices'][0]) && $r1['usd_total'] === 20 && $r1['kind'] === 'reservation');
check('nombre completo armado desde nombre y apellido', $r1['buyer_name'] === 'John Smith');
$e = throwsOrder(fn() => Orders::create('cartagena', $ctgDate, $guest, ['cover' => 7], 'simulated'));
check('rechaza mesa de más de 6', $e !== null && isset($e->fieldErrors['cantidad']));
$e = throwsOrder(fn() => Orders::create('cartagena', '2020-01-04', $guest, ['cover' => 2], 'simulated'));
check('rechaza fecha fuera de la oferta', $e !== null && isset($e->fieldErrors['funcion']));
$e = throwsOrder(fn() => Orders::create('cartagena', $ctgDate, ['first_name' => '', 'last_name' => ''] + $guest, ['cover' => 2], 'simulated'));
check('exige nombre y apellido', $e !== null && isset($e->fieldErrors['first_name']) && isset($e->fieldErrors['last_name']));
$e = throwsOrder(fn() => Orders::create('cartagena', $ctgDate, ['doc_type' => 'NIT'] + $guest, ['cover' => 2], 'simulated'));
check('el documento debe ser pasaporte, cédula o extranjería', $e !== null && isset($e->fieldErrors['doc_type']));
// Cupo de 50 por día: 1 reserva de 4 ya existe; se llena con mesas de 6 y la que sobra se rechaza.
for ($i = 0; $i < 7; $i++) { Orders::create('cartagena', $ctgDate, $guest, ['cover' => 6], 'simulated'); }
check('quedan 4 cupos (50 - 4 - 42)', Orders::seatsLeft($ctg, $ctgDate) === 4, (string) Orders::seatsLeft($ctg, $ctgDate));
$e = throwsOrder(fn() => Orders::create('cartagena', $ctgDate, $guest, ['cover' => 5], 'simulated'));
check('rechaza lo que pasa el cupo del día', $e !== null && isset($e->fieldErrors['cantidad']));
Orders::create('cartagena', $ctgDate, $guest, ['cover' => 4], 'simulated');
check('día lleno', Orders::seatsLeft($ctg, $ctgDate) === 0);
$n = $gw->buildNotification($r1['public_id'], 'approved', $r1['total_amount']);
Webhooks::handle('simulated', $n['headers'], $n['body']);
$rv = Orders::byPublicId($r1['public_id']);
check('voucher con código #CTG-', $rv['ticket'] !== null && str_starts_with($rv['ticket'], '#CTG-5'), (string) $rv['ticket']);
check('la numeración de entradas del show no se mezcla', (int) Db::one("SELECT value FROM counters WHERE name = 'ticket'")['value'] >= 1000 && (int) Db::one("SELECT value FROM counters WHERE name = 'ticket'")['value'] < 5000);

@unlink($dbFile);
@unlink($dbFile . '-wal');
@unlink($dbFile . '-shm');

echo "\n$passed pruebas correctas, " . count($failed) . " con fallas\n";
exit($failed ? 1 : 0);
