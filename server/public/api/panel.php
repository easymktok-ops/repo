<?php
/**
 * Panel de ventas (Fase 4) — Aerodiverti
 * -----------------------------------------------------------------------------
 * Pantalla protegida para que el negocio VEA y GESTIONE las reservas guardadas
 * por el checkout (Fase 3). Un solo archivo, sin dependencias: se sube junto a
 * los demas endpoints (server/public/api/) y se abre en /api/panel.php.
 *
 * Hace:
 *  - Login con usuario+contrasena (config.php -> 'panel').
 *  - Tablero: KPIs + lista de reservas con filtros, buscador y paginacion.
 *  - Detalle de reserva + acciones: marcar saldo pagado en sitio, vuelo
 *    completado, cancelar/reactivar, y notas internas.
 *  - Exportar a CSV (Excel) respetando los filtros.
 *
 * Seguridad: sesion propia (cookie httponly), token CSRF en cada accion,
 * escape de toda salida, cabeceras anti-embebido y sin cache.
 */

declare(strict_types=1);

require __DIR__ . '/../../lib/bootstrap.php';
require __DIR__ . '/../../lib/db.php';

$config = load_config();
$panel = $config['panel'] ?? ['enabled' => false];

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, max-age=0');

if (empty($panel['enabled'])) {
    http_response_code(404);
    exit('No disponible.');
}

// --- Sesion -----------------------------------------------------------------
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443);
session_name('aero_panel');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $https,
]);
session_start();

// --- Helpers ----------------------------------------------------------------
function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
function money_cents(?int $cents, string $cur = 'MXN'): string
{
    $pesos = (int) round(((int) $cents) / 100);
    return '$' . number_format($pesos, 0, '.', ',') . ' ' . $cur;
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_ok(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}
function self_url(array $params = []): string
{
    return 'panel.php' . ($params ? ('?' . http_build_query($params)) : '');
}
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}
function panel_check(array $panel, string $user, string $pass): bool
{
    $uOk = hash_equals((string) ($panel['user'] ?? ''), $user);
    $hash = trim((string) ($panel['password_hash'] ?? ''));
    if ($hash !== '') {
        return $uOk && password_verify($pass, $hash);
    }
    $plain = (string) ($panel['password'] ?? '');
    return $uOk && $plain !== '' && hash_equals($plain, $pass);
}
function flash_set(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function flash_take(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

$do = $_GET['do'] ?? $_POST['do'] ?? '';

// --- Login / logout ---------------------------------------------------------
if ($do === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect(self_url());
}

$authed = !empty($_SESSION['panel_user']);

if (!$authed && $do === 'login') {
    if (!csrf_ok()) {
        flash_set('err', 'Sesion expirada, intenta de nuevo.');
        redirect(self_url());
    }
    $u = trim((string) ($_POST['user'] ?? ''));
    $p = (string) ($_POST['password'] ?? '');
    if (panel_check($panel, $u, $p)) {
        session_regenerate_id(true);
        $_SESSION['panel_user'] = $u;
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        redirect(self_url());
    }
    usleep(700000); // pequeno freno anti fuerza bruta
    flash_set('err', 'Usuario o contrasena incorrectos.');
    redirect(self_url());
}

// --- Puerta de acceso -------------------------------------------------------
if (!$authed) {
    render_login();
    exit;
}

$pdo = db($config);

// --- Acciones (mutaciones) --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $do === 'action') {
    if (!csrf_ok()) {
        flash_set('err', 'Sesion expirada, recarga e intenta de nuevo.');
        redirect(self_url());
    }
    $id = (int) ($_POST['id'] ?? 0);
    $act = (string) ($_POST['act'] ?? '');
    $now = date('Y-m-d H:i:s');
    $set = null;
    $params = [':id' => $id, ':now' => $now];
    switch ($act) {
        case 'balance_paid':
            $set = 'balance_paid_at = :now';
            break;
        case 'balance_undo':
            $set = 'balance_paid_at = NULL';
            break;
        case 'completed':
            $set = 'completed_at = :now';
            break;
        case 'completed_undo':
            $set = 'completed_at = NULL';
            break;
        case 'cancel':
            $set = 'cancelled_at = :now';
            break;
        case 'reactivate':
            $set = 'cancelled_at = NULL';
            break;
        case 'notes':
            $set = 'admin_notes = :notes';
            $params[':notes'] = mb_substr(trim((string) ($_POST['admin_notes'] ?? '')), 0, 2000);
            break;
    }
    if ($set !== null && $id > 0) {
        $pdo->prepare("UPDATE bookings SET $set, updated_at = :now WHERE id = :id")->execute($params);
        flash_set('ok', 'Cambios guardados.');
    }
    redirect(self_url(['view' => 'booking', 'id' => $id]));
}

// --- Construccion de filtros (compartido por lista y export) ----------------
$q = trim((string) ($_GET['q'] ?? ''));
$fStatus = (string) ($_GET['status'] ?? '');
$fFrom = trim((string) ($_GET['from'] ?? ''));
$fTo = trim((string) ($_GET['to'] ?? ''));

$where = [];
$args = [];
if ($q !== '') {
    $where[] = '(customer_name LIKE :q OR customer_email LIKE :q OR customer_phone LIKE :q OR reference LIKE :q OR package_title LIKE :q)';
    $args[':q'] = '%' . $q . '%';
}
if ($fStatus === 'paid' || $fStatus === 'pending') {
    $where[] = 'status = :st';
    $args[':st'] = $fStatus;
} elseif ($fStatus === 'cancelled') {
    $where[] = 'cancelled_at IS NOT NULL';
} elseif ($fStatus === 'balance_due') {
    $where[] = "status = 'paid' AND mode = 'deposit' AND balance_paid_at IS NULL AND cancelled_at IS NULL";
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fFrom)) {
    $where[] = 'date(created_at) >= :from';
    $args[':from'] = $fFrom;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fTo)) {
    $where[] = 'date(created_at) <= :to';
    $args[':to'] = $fTo;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// --- Exportar CSV -----------------------------------------------------------
if ($do === 'export') {
    $stmt = $pdo->prepare("SELECT * FROM bookings $whereSql ORDER BY created_at DESC");
    $stmt->execute($args);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reservas-aerodiverti-' . date('Ymd-His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM para que Excel lea acentos
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Folio', 'Creada', 'Estado', 'Cliente', 'Correo', 'Telefono', 'Vuelo',
        'Pasajeros', 'Fecha vuelo', 'Modo', 'Pagado ahora', 'Total vuelo',
        'Saldo en sitio', 'Saldo pagado', 'Completado', 'Cancelada', 'Notas',
    ]);
    while ($r = $stmt->fetch()) {
        fputcsv($out, [
            $r['reference'],
            $r['created_at'],
            $r['status'],
            $r['customer_name'],
            $r['customer_email'],
            $r['customer_phone'],
            $r['package_title'],
            $r['passengers'],
            $r['flight_date'],
            $r['mode'] === 'deposit' ? 'Anticipo' : 'Pago total',
            (int) $r['amount_now_cents'] / 100,
            (int) $r['total_full_cents'] / 100,
            (int) $r['balance_cents'] / 100,
            $r['balance_paid_at'] ?? '',
            $r['completed_at'] ?? '',
            $r['cancelled_at'] ?? '',
            str_replace(["\r", "\n"], ' ', (string) ($r['admin_notes'] ?? '')),
        ]);
    }
    fclose($out);
    exit;
}

// --- KPIs (globales) --------------------------------------------------------
$today = date('Y-m-d');
$kpi = [
    'paid' => (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='paid' AND cancelled_at IS NULL")->fetchColumn(),
    'revenue' => (int) $pdo->query("SELECT COALESCE(SUM(amount_now_cents),0) FROM bookings WHERE status='paid' AND cancelled_at IS NULL")->fetchColumn(),
];
$stmt = $pdo->prepare("SELECT COALESCE(SUM(balance_cents),0) FROM bookings WHERE status='paid' AND mode='deposit' AND balance_paid_at IS NULL AND cancelled_at IS NULL");
$stmt->execute();
$kpi['balance_due'] = (int) $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status='paid' AND cancelled_at IS NULL AND completed_at IS NULL AND flight_date >= :t");
$stmt->execute([':t' => $today]);
$kpi['upcoming'] = (int) $stmt->fetchColumn();

// --- Vista de detalle -------------------------------------------------------
if (($_GET['view'] ?? '') === 'booking') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $b = $stmt->fetch();
    if (!$b) {
        render_layout('Reserva no encontrada', '<p class="muted">No existe esa reserva. <a href="' . h(self_url()) . '">Volver</a></p>', $kpi, false);
        exit;
    }
    $ntf = $pdo->prepare('SELECT * FROM notifications_outbox WHERE booking_id = :id ORDER BY id');
    $ntf->execute([':id' => $id]);
    render_layout('Reserva ' . h($b['reference']), render_detail($b, $ntf->fetchAll()), $kpi, false);
    exit;
}

// --- Vista de lista (default) ----------------------------------------------
$per = 25;
$page = max(1, (int) ($_GET['page'] ?? 1));
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings $whereSql");
$countStmt->execute($args);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$offset = ($page - 1) * $per;

$listStmt = $pdo->prepare("SELECT * FROM bookings $whereSql ORDER BY created_at DESC LIMIT $per OFFSET $offset");
$listStmt->execute($args);
$rows = $listStmt->fetchAll();

render_layout('Reservas', render_list($rows, $total, $page, $pages, compact('q', 'fStatus', 'fFrom', 'fTo')), $kpi, true);
exit;

/* ============================================================================
   RENDER
============================================================================ */

/** Etiqueta + color de estado a partir de una reserva. */
function status_badge(array $b): array
{
    if (!empty($b['cancelled_at'])) {
        return ['Cancelada', 'bad'];
    }
    if (($b['status'] ?? '') !== 'paid') {
        return ['Pendiente de pago', 'warn'];
    }
    if (!empty($b['completed_at'])) {
        return ['Vuelo completado', 'done'];
    }
    if (($b['mode'] ?? '') === 'deposit' && empty($b['balance_paid_at'])) {
        return ['Anticipo pagado', 'accent'];
    }
    return ['Pagado', 'ok'];
}

function render_list(array $rows, int $total, int $page, int $pages, array $f): string
{
    ob_start();
    ?>
    <form class="filters" method="get" action="panel.php">
      <input type="search" name="q" value="<?= h($f['q']) ?>" placeholder="Buscar folio, nombre, correo o telefono" />
      <select name="status" aria-label="Estado">
        <option value="">Todos los estados</option>
        <option value="paid" <?= $f['fStatus'] === 'paid' ? 'selected' : '' ?>>Pagadas</option>
        <option value="balance_due" <?= $f['fStatus'] === 'balance_due' ? 'selected' : '' ?>>Con saldo por cobrar</option>
        <option value="pending" <?= $f['fStatus'] === 'pending' ? 'selected' : '' ?>>Pendientes de pago</option>
        <option value="cancelled" <?= $f['fStatus'] === 'cancelled' ? 'selected' : '' ?>>Canceladas</option>
      </select>
      <label class="date">Desde <input type="date" name="from" value="<?= h($f['fFrom']) ?>" /></label>
      <label class="date">Hasta <input type="date" name="to" value="<?= h($f['fTo']) ?>" /></label>
      <button class="btn" type="submit">Filtrar</button>
      <a class="btn ghost" href="panel.php">Limpiar</a>
      <a class="btn ghost" href="<?= h(self_url(['do' => 'export', 'q' => $f['q'], 'status' => $f['fStatus'], 'from' => $f['fFrom'], 'to' => $f['fTo']])) ?>">Exportar CSV</a>
    </form>

    <p class="count"><?= (int) $total ?> reserva<?= $total === 1 ? '' : 's' ?><?= $f['q'] !== '' || $f['fStatus'] !== '' || $f['fFrom'] !== '' || $f['fTo'] !== '' ? ' (con filtros)' : '' ?></p>

    <div class="tablewrap">
      <table>
        <thead>
          <tr>
            <th>Folio</th><th>Creada</th><th>Cliente</th><th>Vuelo</th>
            <th class="num">Pax</th><th>Fecha vuelo</th><th>Modo</th>
            <th class="num">Pagado</th><th class="num">Saldo en sitio</th><th>Estado</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="10" class="empty">Sin reservas todavia.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): [$label, $cls] = status_badge($r); ?>
          <tr onclick="location.href='<?= h(self_url(['view' => 'booking', 'id' => $r['id']])) ?>'">
            <td class="mono"><?= h($r['reference']) ?></td>
            <td class="muted"><?= h(substr((string) $r['created_at'], 0, 16)) ?></td>
            <td>
              <div class="strong"><?= h($r['customer_name']) ?></div>
              <div class="muted sm"><?= h($r['customer_email']) ?></div>
            </td>
            <td><?= h($r['package_title']) ?></td>
            <td class="num"><?= (int) $r['passengers'] ?></td>
            <td><?= h($r['flight_date'] ?: '—') ?></td>
            <td><?= $r['mode'] === 'deposit' ? 'Anticipo' : 'Pago total' ?></td>
            <td class="num"><?= money_cents((int) $r['amount_now_cents'], $r['currency']) ?></td>
            <td class="num"><?= ($r['mode'] === 'deposit' && empty($r['balance_paid_at']) && empty($r['cancelled_at']) && $r['status'] === 'paid') ? money_cents((int) $r['balance_cents'], $r['currency']) : '—' ?></td>
            <td><span class="badge <?= $cls ?>"><?= h($label) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <nav class="pager">
        <?php if ($page > 1): ?><a class="btn ghost" href="<?= h(self_url(['page' => $page - 1, 'q' => $f['q'], 'status' => $f['fStatus'], 'from' => $f['fFrom'], 'to' => $f['fTo']])) ?>">← Anteriores</a><?php endif; ?>
        <span class="muted">Pagina <?= $page ?> de <?= $pages ?></span>
        <?php if ($page < $pages): ?><a class="btn ghost" href="<?= h(self_url(['page' => $page + 1, 'q' => $f['q'], 'status' => $f['fStatus'], 'from' => $f['fFrom'], 'to' => $f['fTo']])) ?>">Siguientes →</a><?php endif; ?>
      </nav>
    <?php endif; ?>
    <?php
    return (string) ob_get_clean();
}

function render_detail(array $b, array $ntf): string
{
    [$label, $cls] = status_badge($b);
    $csrf = csrf_token();
    $isDepositDue = $b['mode'] === 'deposit' && empty($b['balance_paid_at']) && $b['status'] === 'paid';
    ob_start();
    ?>
    <p><a class="back" href="<?= h(self_url()) ?>">← Todas las reservas</a></p>
    <div class="detail">
      <div class="dcol">
        <div class="card">
          <div class="dhead">
            <div>
              <div class="mono ref"><?= h($b['reference']) ?></div>
              <h2><?= h($b['package_title']) ?></h2>
            </div>
            <span class="badge <?= $cls ?>"><?= h($label) ?></span>
          </div>
          <dl class="kv">
            <div><dt>Cliente</dt><dd><?= h($b['customer_name']) ?></dd></div>
            <div><dt>Correo</dt><dd><a href="mailto:<?= h($b['customer_email']) ?>"><?= h($b['customer_email']) ?></a></dd></div>
            <div><dt>Telefono / WhatsApp</dt><dd><a href="tel:<?= h($b['customer_phone']) ?>"><?= h($b['customer_phone']) ?></a></dd></div>
            <div><dt>Pasajeros</dt><dd><?= (int) $b['passengers'] ?></dd></div>
            <div><dt>Fecha tentativa de vuelo</dt><dd><?= h($b['flight_date'] ?: 'Sin fecha') ?></dd></div>
            <div><dt>Modo de pago</dt><dd><?= $b['mode'] === 'deposit' ? 'Apartar (anticipo)' : 'Pago total' ?></dd></div>
            <div><dt>Reserva creada</dt><dd><?= h($b['created_at']) ?></dd></div>
            <?php if (!empty($b['paid_at'])): ?><div><dt>Pago confirmado</dt><dd><?= h($b['paid_at']) ?></dd></div><?php endif; ?>
          </dl>
          <?php if (!empty($b['notes'])): ?>
            <div class="clientnote"><span class="muted sm">Nota del cliente</span><p><?= nl2br(h($b['notes'])) ?></p></div>
          <?php endif; ?>
        </div>

        <div class="card">
          <h3>Notificaciones</h3>
          <?php if (!$ntf): ?>
            <p class="muted sm">Sin notificaciones registradas.</p>
          <?php else: ?>
            <ul class="ntf">
              <?php foreach ($ntf as $n): ?>
                <li>
                  <span class="badge <?= $n['status'] === 'sent' ? 'ok' : ($n['status'] === 'failed' ? 'bad' : 'warn') ?>"><?= h($n['status']) ?></span>
                  <span><?= h($n['kind']) ?> · <?= h($n['channel']) ?></span>
                  <span class="muted sm"><?= h($n['provider'] ?? '') ?><?= $n['sent_at'] ? ' · ' . h($n['sent_at']) : '' ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
            <p class="muted sm">En esta demo el envio esta en modo registro: se traza pero no se manda correo.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="dcol">
        <div class="card ticket">
          <div class="tline"><span>Total del vuelo</span><b><?= money_cents((int) $b['total_full_cents'], $b['currency']) ?></b></div>
          <div class="tline"><span>Pagado en linea</span><b><?= money_cents((int) $b['amount_now_cents'], $b['currency']) ?></b></div>
          <?php if ($b['mode'] === 'deposit'): ?>
            <div class="tline big"><span>Saldo a cobrar en sitio</span><b class="<?= empty($b['balance_paid_at']) ? 'accent' : 'ok' ?>"><?= money_cents((int) $b['balance_cents'], $b['currency']) ?><?= !empty($b['balance_paid_at']) ? ' ✓' : '' ?></b></div>
          <?php endif; ?>
        </div>

        <div class="card">
          <h3>Acciones</h3>
          <?php if ($f = flash_take()): ?><p class="flash <?= $f['type'] === 'ok' ? 'ok' : 'err' ?>"><?= h($f['msg']) ?></p><?php endif; ?>
          <div class="actions">
            <?php if ($isDepositDue && empty($b['cancelled_at'])): ?>
              <?= action_btn($b['id'], 'balance_paid', $csrf, 'Marcar saldo pagado en sitio', 'primary') ?>
            <?php elseif (!empty($b['balance_paid_at'])): ?>
              <?= action_btn($b['id'], 'balance_undo', $csrf, 'Quitar “saldo pagado”', 'ghost') ?>
            <?php endif; ?>

            <?php if (empty($b['completed_at']) && empty($b['cancelled_at'])): ?>
              <?= action_btn($b['id'], 'completed', $csrf, 'Marcar vuelo completado', 'primary') ?>
            <?php elseif (!empty($b['completed_at'])): ?>
              <?= action_btn($b['id'], 'completed_undo', $csrf, 'Quitar “completado”', 'ghost') ?>
            <?php endif; ?>

            <?php if (empty($b['cancelled_at'])): ?>
              <?= action_btn($b['id'], 'cancel', $csrf, 'Cancelar reserva', 'danger', '¿Cancelar esta reserva?') ?>
            <?php else: ?>
              <?= action_btn($b['id'], 'reactivate', $csrf, 'Reactivar reserva', 'ghost') ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="card">
          <h3>Notas internas</h3>
          <form method="post" action="panel.php">
            <input type="hidden" name="do" value="action" />
            <input type="hidden" name="act" value="notes" />
            <input type="hidden" name="id" value="<?= (int) $b['id'] ?>" />
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
            <textarea name="admin_notes" rows="4" placeholder="Recordatorios, acuerdos, seguimiento…"><?= h($b['admin_notes'] ?? '') ?></textarea>
            <button class="btn" type="submit">Guardar notas</button>
          </form>
        </div>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function action_btn(int $id, string $act, string $csrf, string $label, string $style, ?string $confirm = null): string
{
    $c = $confirm ? ' onsubmit="return confirm(' . h(json_encode($confirm)) . ')"' : '';
    return '<form method="post" action="panel.php"' . $c . '>'
        . '<input type="hidden" name="do" value="action" />'
        . '<input type="hidden" name="act" value="' . h($act) . '" />'
        . '<input type="hidden" name="id" value="' . $id . '" />'
        . '<input type="hidden" name="csrf" value="' . h($csrf) . '" />'
        . '<button class="btn ' . h($style) . '" type="submit">' . h($label) . '</button>'
        . '</form>';
}

function render_login(): void
{
    $csrf = csrf_token();
    $flash = flash_take();
    ?><!doctype html>
    <html lang="es"><head><meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <title>Panel · Aerodiverti</title>
    <?= panel_css() ?>
    </head>
    <body class="loginbg">
      <main class="loginwrap">
        <form class="card login" method="post" action="panel.php">
          <div class="brand">🎈 Aerodiverti</div>
          <h1>Panel de ventas</h1>
          <p class="muted">Acceso solo para el equipo.</p>
          <?php if ($flash): ?><p class="flash err"><?= h($flash['msg']) ?></p><?php endif; ?>
          <input type="hidden" name="do" value="login" />
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
          <label>Usuario<input name="user" autocomplete="username" required autofocus /></label>
          <label>Contrasena<input name="password" type="password" autocomplete="current-password" required /></label>
          <button class="btn primary" type="submit">Entrar</button>
        </form>
      </main>
    </body></html>
    <?php
}

function render_layout(string $title, string $body, array $kpi, bool $showKpi): void
{
    $user = h($_SESSION['panel_user'] ?? '');
    ?><!doctype html>
    <html lang="es"><head><meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <title><?= h($title) ?> · Panel Aerodiverti</title>
    <?= panel_css() ?>
    </head>
    <body>
      <header class="topbar">
        <a class="brand" href="panel.php">🎈 Aerodiverti</a>
        <div class="topright">
          <span class="muted sm">Sesion: <?= $user ?></span>
          <a class="btn ghost sm" href="<?= h(self_url(['do' => 'logout'])) ?>">Salir</a>
        </div>
      </header>
      <main class="wrap">
        <?php if ($showKpi): ?>
          <div class="kpis">
            <div class="kpi"><span class="klabel">Reservas pagadas</span><span class="kval"><?= (int) $kpi['paid'] ?></span></div>
            <div class="kpi"><span class="klabel">Cobrado en linea</span><span class="kval"><?= money_cents((int) $kpi['revenue']) ?></span></div>
            <div class="kpi"><span class="klabel">Saldo por cobrar en sitio</span><span class="kval accent"><?= money_cents((int) $kpi['balance_due']) ?></span></div>
            <div class="kpi"><span class="klabel">Vuelos proximos</span><span class="kval"><?= (int) $kpi['upcoming'] ?></span></div>
          </div>
        <?php endif; ?>
        <?php if ($f = flash_take()): ?><p class="flash <?= $f['type'] === 'ok' ? 'ok' : 'err' ?>"><?= h($f['msg']) ?></p><?php endif; ?>
        <?= $body ?>
      </main>
    </body></html>
    <?php
}

function panel_css(): string
{
    return <<<CSS
    <style>
      :root{
        --bg:#14161b;--bg2:#0f1116;--surface:#1c1f27;--surface2:#242833;
        --ink:#ecedf1;--ink-soft:#d3d7df;--muted:#a6abb5;--faint:#878e9b;
        --line:#333844;--line-soft:#262b34;--accent:#ea83c1;--accent-strong:#f7a0d4;
        --accent-ink:#17131a;--ok:#57c98d;--warn:#e6b450;--bad:#ef6d6d;--done:#7bb8ef;
        --font:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
        --mono:ui-monospace,"SFMono-Regular","Cascadia Code",Menlo,monospace;
      }
      *{box-sizing:border-box;margin:0}
      body{background:var(--bg);color:var(--ink);font-family:var(--font);font-size:15px;line-height:1.55;-webkit-font-smoothing:antialiased}
      a{color:var(--accent-strong);text-decoration:none}
      a:hover{text-decoration:underline}
      .muted{color:var(--muted)} .faint{color:var(--faint)} .sm{font-size:.82rem}
      .strong{font-weight:600;color:var(--ink)} .mono{font-family:var(--mono)}
      .num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
      .accent{color:var(--accent)}
      .topbar{position:sticky;top:0;z-index:5;display:flex;align-items:center;justify-content:space-between;
        gap:1rem;padding:.8rem 1.1rem;background:color-mix(in srgb,var(--bg) 88%,transparent);
        backdrop-filter:blur(8px);border-bottom:1px solid var(--line-soft)}
      .brand{font-weight:700;color:var(--accent);font-size:1.05rem}
      .topright{display:flex;align-items:center;gap:.7rem}
      .wrap{max-width:1120px;margin:0 auto;padding:1.4rem 1.1rem 4rem}
      h1{font-size:1.5rem;letter-spacing:-.02em} h2{font-size:1.2rem;letter-spacing:-.01em}
      h3{font-size:1rem;margin-bottom:.7rem}
      .btn{display:inline-block;font:inherit;font-weight:600;font-size:.86rem;cursor:pointer;
        border:1px solid var(--line);border-radius:999px;padding:.5rem 1rem;background:var(--surface2);
        color:var(--ink);transition:border-color .15s,background-color .15s,transform .1s}
      .btn:hover{border-color:var(--accent);text-decoration:none}
      .btn:active{transform:scale(.98)}
      .btn.primary{background:var(--accent);color:var(--accent-ink);border-color:transparent}
      .btn.primary:hover{background:var(--accent-strong)}
      .btn.ghost{background:transparent}
      .btn.danger{background:transparent;color:var(--bad);border-color:color-mix(in srgb,var(--bad) 50%,var(--line))}
      .btn.danger:hover{border-color:var(--bad)}
      .btn.sm{padding:.35rem .75rem;font-size:.8rem}
      .kpis{display:grid;grid-template-columns:repeat(2,1fr);gap:.7rem;margin-bottom:1.3rem}
      @media(min-width:720px){.kpis{grid-template-columns:repeat(4,1fr)}}
      .kpi{background:linear-gradient(180deg,var(--surface2),var(--surface));border:1px solid var(--line-soft);
        border-radius:14px;padding:.9rem 1rem;display:flex;flex-direction:column;gap:.25rem}
      .klabel{font-size:.76rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
      .kval{font-size:1.5rem;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:-.02em}
      .filters{display:flex;flex-wrap:wrap;gap:.55rem;align-items:center;margin-bottom:1rem}
      .filters input,.filters select{font:inherit;background:var(--bg2);color:var(--ink);border:1px solid var(--line);
        border-radius:10px;padding:.5rem .7rem}
      .filters input[type=search]{min-width:min(340px,100%);flex:1}
      .filters .date{display:inline-flex;align-items:center;gap:.4rem;color:var(--muted);font-size:.85rem}
      .filters input:focus,.filters select:focus{outline:2px solid var(--accent);outline-offset:1px;border-color:var(--accent)}
      .count{color:var(--muted);font-size:.85rem;margin-bottom:.6rem}
      .tablewrap{overflow-x:auto;border:1px solid var(--line-soft);border-radius:14px;background:var(--surface)}
      table{width:100%;border-collapse:collapse;font-size:.9rem;min-width:820px}
      thead th{text-align:left;font-size:.74rem;text-transform:uppercase;letter-spacing:.05em;color:var(--faint);
        padding:.7rem .8rem;border-bottom:1px solid var(--line-soft);white-space:nowrap}
      thead th.num{text-align:right}
      tbody td{padding:.7rem .8rem;border-bottom:1px solid var(--line-soft);vertical-align:top}
      tbody tr{cursor:pointer;transition:background-color .12s}
      tbody tr:hover{background:var(--surface2)}
      tbody tr:last-child td{border-bottom:0}
      td.empty,.empty{color:var(--muted);text-align:center;padding:2rem}
      .badge{display:inline-block;font-size:.72rem;font-weight:600;padding:.2rem .55rem;border-radius:999px;white-space:nowrap;
        border:1px solid transparent}
      .badge.ok{background:color-mix(in srgb,var(--ok) 16%,transparent);color:var(--ok);border-color:color-mix(in srgb,var(--ok) 35%,transparent)}
      .badge.accent{background:color-mix(in srgb,var(--accent) 16%,transparent);color:var(--accent);border-color:color-mix(in srgb,var(--accent) 35%,transparent)}
      .badge.warn{background:color-mix(in srgb,var(--warn) 15%,transparent);color:var(--warn);border-color:color-mix(in srgb,var(--warn) 35%,transparent)}
      .badge.bad{background:color-mix(in srgb,var(--bad) 15%,transparent);color:var(--bad);border-color:color-mix(in srgb,var(--bad) 35%,transparent)}
      .badge.done{background:color-mix(in srgb,var(--done) 15%,transparent);color:var(--done);border-color:color-mix(in srgb,var(--done) 35%,transparent)}
      .pager{display:flex;align-items:center;justify-content:center;gap:1rem;margin-top:1.2rem}
      .detail{display:grid;gap:1rem}
      @media(min-width:840px){.detail{grid-template-columns:1.3fr 1fr}}
      .dcol{display:grid;gap:1rem;align-content:start}
      .card{background:var(--surface);border:1px solid var(--line-soft);border-radius:14px;padding:1.1rem}
      .dhead{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:.9rem}
      .ref{font-size:.82rem;color:var(--accent)}
      .kv{display:grid;gap:.55rem}
      .kv>div{display:grid;grid-template-columns:minmax(120px,40%) 1fr;gap:.6rem}
      .kv dt{color:var(--muted);font-size:.85rem} .kv dd{color:var(--ink)}
      .clientnote{margin-top:.9rem;padding-top:.9rem;border-top:1px solid var(--line-soft)}
      .ticket .tline{display:flex;justify-content:space-between;gap:1rem;padding:.5rem 0;font-variant-numeric:tabular-nums}
      .ticket .tline.big{border-top:1px dashed var(--line);margin-top:.3rem;padding-top:.7rem;font-size:1.05rem}
      .ntf{list-style:none;display:grid;gap:.5rem}
      .ntf li{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem}
      .actions{display:flex;flex-wrap:wrap;gap:.6rem}
      .actions form{margin:0}
      textarea{width:100%;font:inherit;background:var(--bg2);color:var(--ink);border:1px solid var(--line);
        border-radius:10px;padding:.6rem .7rem;resize:vertical;margin-bottom:.7rem}
      textarea:focus{outline:2px solid var(--accent);outline-offset:1px;border-color:var(--accent)}
      .back{font-size:.9rem}
      .flash{padding:.6rem .85rem;border-radius:10px;font-size:.88rem;margin-bottom:.9rem}
      .flash.ok{background:color-mix(in srgb,var(--ok) 14%,transparent);color:var(--ok);border:1px solid color-mix(in srgb,var(--ok) 30%,transparent)}
      .flash.err{background:color-mix(in srgb,var(--bad) 14%,transparent);color:var(--bad);border:1px solid color-mix(in srgb,var(--bad) 30%,transparent)}
      /* login */
      .loginbg{min-height:100dvh;display:grid;place-items:center;background:radial-gradient(120% 90% at 50% 0%,#1a1c24,var(--bg))}
      .loginwrap{width:100%;max-width:380px;padding:1.2rem}
      .login{display:grid;gap:.7rem}
      .login .brand{color:var(--accent);font-weight:700;font-size:1.1rem}
      .login h1{font-size:1.35rem;margin-top:.2rem}
      .login label{display:grid;gap:.3rem;font-size:.85rem;color:var(--muted)}
      .login input{font:inherit;background:var(--bg2);color:var(--ink);border:1px solid var(--line);border-radius:10px;padding:.6rem .75rem}
      .login input:focus{outline:2px solid var(--accent);outline-offset:1px;border-color:var(--accent)}
      .login .btn{margin-top:.4rem}
      @media(prefers-reduced-motion:reduce){*{transition:none!important}}
    </style>
    CSS;
}
