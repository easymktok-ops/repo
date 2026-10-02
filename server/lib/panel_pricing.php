<?php
/**
 * Panel de Precios (pestana "Precios" de panel.php): anticipo por paquete,
 * temporadas / fechas especiales / dias de la semana y dias sin vuelo.
 * Usa pricing_store.php para escribir (transaccion + historial + version) y los
 * helpers de panel.php (h, csrf_token, csrf_ok, self_url, flash_set).
 *
 * Texto para el negocio en espanol llano: sin jerga tecnica.
 */

declare(strict_types=1);

require_once __DIR__ . '/pricing_store.php';

const PANEL_PRICING_TABS = [
    'anticipos'  => 'Anticipo por paquete',
    'temporadas' => 'Temporadas y fechas especiales',
    'sin-vuelo'  => 'Días sin vuelo',
    'historial'  => 'Historial',
    'vista-previa' => 'Vista previa',
];
const PANEL_MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
const PANEL_DIAS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 0 => 'Dom'];
const PANEL_TIPOS = ['season' => 'Temporada', 'date' => 'Fecha especial', 'weekday' => 'Días de la semana'];

/** "15 dic 2026". */
function panel_fecha_humana(string $ymd): string
{
    if (!pricing_valid_ymd($ymd)) {
        return $ymd;
    }
    [$y, $m, $d] = array_map('intval', explode('-', $ymd));
    return $d . ' ' . PANEL_MESES[$m - 1] . ' ' . $y;
}

function panel_fecha_rango(string $from, string $to): string
{
    return $from === $to ? panel_fecha_humana($from) : panel_fecha_humana($from) . ' al ' . panel_fecha_humana($to);
}

/** Centavos a "$2,650" (o "$1,192.50" si no es entero). */
function panel_pesos(int $cents): string
{
    $whole = $cents % 100 === 0;
    return '$' . number_format($cents / 100, $whole ? 0 : 2, '.', ',');
}

/** Acepta "2,650", "2650", "$2,650" o "2650.00". Devuelve centavos o null. */
function panel_parse_pesos(string $s): ?int
{
    $s = str_replace([',', '$', ' '], '', trim($s));
    if (!preg_match('/^(\d{1,7})(\.00?)?\z/', $s, $m)) {
        return null;
    }
    return (int) $m[1] * 100;
}

/** Texto de la tarifa con la que se cobro una reserva (columnas de bookings). */
function panel_tariff_text(array $b): string
{
    if (empty($b['rule_id'])) {
        $name = 'Precio base';
    } else {
        $name = !empty($b['rule_label']) ? (string) $b['rule_label'] : 'Tarifa eliminada';
    }
    $out = $name . ': ' . panel_pesos((int) ($b['unit_price_cents'] ?? 0)) . ' por persona';
    if (!empty($b['deposit_percent'])) {
        $out .= ', anticipo ' . (int) $b['deposit_percent'] . ' %';
    }
    return $out;
}

const PANEL_NOMBRE_TIPO = [
    'season' => 'esta temporada', 'date' => 'esta fecha especial',
    'weekday' => 'esta tarifa de días de la semana', 'blocked' => 'este cierre',
];
const PANEL_TIPO_DE = [
    'season' => 'la temporada', 'date' => 'la fecha especial', 'weekday' => 'los días de la semana', 'blocked' => 'el día sin vuelo',
];

/** "Del 20 dic 2026 al 24 dic 2026" / "El 25 dic 2026". */
function panel_tramo(string $from, string $to): string
{
    return $from === $to ? 'El ' . panel_fecha_humana($from) : 'Del ' . panel_fecha_humana($from) . ' al ' . panel_fecha_humana($to);
}

/**
 * Avisos (sin bloquear) de las fechas en que $rule coincide con otras.
 * @return string[] frases en espanol llano
 */
function panel_overlap_notes(array $rule, array $rules): array
{
    $byId = [];
    foreach ($rules as $r) {
        $byId[$r['id']] = $r;
    }
    $notes = [];
    foreach (pricing_overlaps($rule, $rules) as $o) {
        $other = $byId[$o['other_id']] ?? null;
        if ($other === null) {
            continue;
        }
        $plural = $o['from'] !== $o['to'];
        $tramo = panel_tramo($o['from'], $o['to']);
        $name = PANEL_NOMBRE_TIPO[$rule['type']] ?? 'esta tarifa';
        if ($rule['type'] === 'blocked') {
            $notes[] = $tramo . ' coincide con \'' . $other['label'] . '\'. ' . ($plural ? 'Esos días no habrá vuelos.' : 'Ese día no habrá vuelos.');
        } elseif ($o['outcome'] === 'no_flight') {
            $notes[] = $tramo . ' coincide con \'Sin vuelo: ' . $other['label'] . '\'. ' . ($plural ? 'Esos días no habrá vuelos.' : 'Ese día no habrá vuelos.');
        } elseif ($rule['type'] === $other['type']) {
            $a = pricing_rule_span($rule);
            $b = pricing_rule_span($other);
            if ($o['outcome'] === 'other_wins') {
                $why = $b < $a ? 'que abarca menos días' : 'que se editó más recientemente';
                $notes[] = $tramo . ' ' . $name . ' coincide con \'' . $other['label'] . '\'. Se aplicará el precio de \'' . $other['label'] . '\', ' . $why . '.';
            } else {
                $why = $a < $b ? 'que abarca menos días' : 'que se editó más recientemente';
                $notes[] = $tramo . ' ' . $name . ' coincide con \'' . $other['label'] . '\'. Se aplicará el precio de esta, ' . $why . '.';
            }
        } else {
            $winner = $o['outcome'] === 'other_wins' ? $other['type'] : $rule['type'];
            $notes[] = $tramo . ' ' . $name . ' coincide con \'' . $other['label'] . '\'. Se aplicará el precio de ' . PANEL_TIPO_DE[$winner] . '.';
        }
    }
    return $notes;
}

function panel_notes_html(array $notes): string
{
    if (!$notes) {
        return '';
    }
    $out = '<ul class="overlap" aria-label="Fechas que coinciden con otras tarifas">';
    foreach ($notes as $n) {
        $out .= '<li>' . h($n) . '</li>';
    }
    return $out . '</ul>';
}

function panel_pricing_tab(string $t): string
{
    return isset(PANEL_PRICING_TABS[$t]) ? $t : 'anticipos';
}

function panel_pkg_name(array $pkg): string
{
    return (string) ($pkg['title']['es'] ?? $pkg['slug']);
}

/**
 * Datos comunes de la pestana. ok=false si no se pudo preparar (mensaje en error).
 * @return array{ok:bool,error:string,packages:array,rules:array,known:array,enabled:bool,version:string,pdo:PDO}
 */
function panel_pricing_context(array $config, PDO $pdo): array
{
    $ctx = [
        'ok' => false, 'error' => '', 'packages' => [], 'rules' => [], 'known' => [], 'pdo' => $pdo,
        'enabled' => !empty($config['pricing']['rules_enabled']), 'version' => '',
    ];
    if (!ensure_pricing_schema($pdo)) {
        $ctx['error'] = 'No se pudo preparar la base de precios. Avisa a soporte.';
        return $ctx;
    }
    try {
        $catalog = load_catalog((string) $config['catalog_path']);
    } catch (Throwable $e) {
        log_line('panel', 'catalogo no disponible', ['msg' => $e->getMessage()]);
        $ctx['error'] = 'No se pudo leer la lista de paquetes. Avisa a soporte.';
        return $ctx;
    }
    $ctx['packages'] = pricing_load_package_pricing($pdo, $catalog);
    $ctx['known'] = array_keys($ctx['packages']);
    $ctx['rules'] = pricing_load_rules($pdo);
    $ctx['version'] = pricing_version($pdo, $catalog);
    $ctx['ok'] = true;
    return $ctx;
}

/* ============================================================================
   ACCIONES (POST do=pricing)
   Devuelve ['redirect'=>url] o ['errors'=>[campo=>texto], 'values'=>[...], 'tab'=>..., 'edit'=>?id]
============================================================================ */

function panel_pricing_post(PDO $pdo, array $ctx, string $user): array
{
    $tab = panel_pricing_tab((string) ($_POST['tab'] ?? ''));
    $back = self_url(['view' => 'precios', 'tab' => $tab]);
    if (!csrf_ok()) {
        flash_set('err', 'Sesion expirada, recarga e intenta de nuevo.');
        return ['redirect' => $back];
    }
    if (!$ctx['ok']) {
        flash_set('err', $ctx['error']);
        return ['redirect' => $back];
    }
    $op = (string) ($_POST['op'] ?? '');
    $known = $ctx['known'];
    $id = (string) ($_POST['id'] ?? '');

    try {
        switch ($op) {
            case 'deposit_save':
                return panel_pricing_post_deposits($pdo, $ctx, $user, $back);

            case 'rule_save':
            case 'block_save':
                $blocked = $op === 'block_save';
                $values = panel_pricing_read_form($blocked);
                $rule = panel_pricing_form_to_rule($values, $blocked);
                $errors = [];
                try {
                    pricing_save_rule($pdo, $rule, $user, $known);
                } catch (InvalidArgumentException $e) {
                    $errors = json_decode($e->getMessage(), true) ?: ['label' => $e->getMessage()];
                }
                if ($errors) {
                    if ($blocked && isset($errors['label'])) {
                        $errors['label'] = 'Escribe el motivo.';
                    }
                    foreach (['start_date', 'end_date'] as $f) {
                        if (isset($errors[$f]) && ($values[$f] ?? '') === '') {
                            $errors[$f] = 'Elige la fecha.';
                        }
                    }
                    return ['errors' => $errors, 'values' => $values, 'tab' => $tab, 'edit' => $values['id'] !== '' ? $values['id'] : null];
                }
                flash_set('ok', 'Precios actualizados.');
                return ['redirect' => $back];

            case 'rule_duplicate':
                pricing_duplicate_rule($pdo, $id, $user, $known);
                flash_set('ok', 'Precios actualizados. La copia quedó pausada.');
                return ['redirect' => $back];

            case 'rule_toggle':
                $ok = pricing_set_rule_active($pdo, $id, ($_POST['to'] ?? '') === 'on', $user);
                flash_set($ok ? 'ok' : 'err', $ok ? 'Precios actualizados.' : 'No se encontró lo que intentas cambiar.');
                return ['redirect' => $back];

            case 'rule_delete':
                $ok = pricing_delete_rule($pdo, $id, $user);
                flash_set($ok ? 'ok' : 'err', $ok ? 'Precios actualizados.' : 'No se encontró lo que intentas cambiar.');
                return ['redirect' => $back];
        }
    } catch (InvalidArgumentException $e) {
        $msgs = json_decode($e->getMessage(), true);
        flash_set('err', is_array($msgs) ? implode(' ', $msgs) : $e->getMessage());
        return ['redirect' => $back];
    } catch (Throwable $e) {
        log_line('panel', 'error al guardar precios', ['op' => $op, 'msg' => $e->getMessage()]);
        flash_set('err', 'No se pudo guardar. Intenta de nuevo.');
        return ['redirect' => $back];
    }
    flash_set('err', 'Acción no reconocida.');
    return ['redirect' => $back];
}

function panel_pricing_post_deposits(PDO $pdo, array $ctx, string $user, string $back): array
{
    $posted = is_array($_POST['deposit'] ?? null) ? $_POST['deposit'] : [];
    $errors = [];
    $changes = [];
    foreach ($ctx['packages'] as $slug => $pkg) {
        $raw = trim((string) ($posted[$slug] ?? ''));
        if ($raw === '') {
            continue;
        }
        if (!preg_match('/^\d{1,3}\z/', $raw) || (int) $raw < 1 || (int) $raw > 100) {
            $errors[$slug] = 'El anticipo debe estar entre 1 y 100 %.';
            continue;
        }
        if ($pkg['default_deposit_percent'] !== (int) $raw) {
            $changes[$slug] = (int) $raw;
        }
    }
    if ($errors) {
        return ['errors' => $errors, 'values' => array_map('strval', $posted), 'tab' => 'anticipos', 'edit' => null];
    }
    foreach ($changes as $slug => $pct) {
        pricing_set_deposit($pdo, $slug, $pct, $user, $ctx['known']);
    }
    flash_set('ok', $changes ? 'Precios actualizados.' : 'No hubo cambios.');
    return ['redirect' => $back];
}

/** Lee el formulario de una tarifa tal como lo escribio la persona. */
function panel_pricing_read_form(bool $blocked): array
{
    $g = function (string $k): string {
        return trim((string) ($_POST[$k] ?? ''));
    };
    $weekdays = [];
    foreach ((array) ($_POST['weekdays'] ?? []) as $d) {
        if (is_string($d) && preg_match('/^[0-6]\z/', $d)) {
            $weekdays[] = (int) $d;
        }
    }
    $pk = [];
    foreach ((array) ($_POST['packages'] ?? []) as $s) {
        if (is_string($s)) {
            $pk[] = $s;
        }
    }
    return [
        'id'              => $g('id'),
        'label'           => $g('label'),
        'type'            => $blocked ? 'blocked' : $g('type'),
        'start_date'      => $g('start_date'),
        'end_date'        => $g('end_date'),
        'weekdays'        => $weekdays,
        'packages_mode'   => $g('packages_mode') === 'some' ? 'some' : 'all',
        'packages'        => $pk,
        'price'           => $g('price'),
        'deposit_percent' => $g('deposit_percent'),
    ];
}

function panel_pricing_form_to_rule(array $v, bool $blocked): array
{
    $end = $v['end_date'];
    if ($end === '' && ($blocked || $v['type'] === 'date')) {
        $end = $v['start_date'];
    }
    $price = null;
    if (!$blocked) {
        $price = panel_parse_pesos($v['price']) ?? 0;
    }
    $pct = null;
    if (!$blocked && $v['deposit_percent'] !== '') {
        $pct = preg_match('/^\d{1,3}\z/', $v['deposit_percent']) ? (int) $v['deposit_percent'] : 0;
    }
    $rule = [
        'label'           => $v['label'],
        'type'            => $v['type'],
        'package_ids'     => $v['packages_mode'] === 'all' ? 'all' : $v['packages'],
        'start_date'      => $v['start_date'],
        'end_date'        => $end,
        'weekdays'        => $v['type'] === 'weekday' ? $v['weekdays'] : null,
        'price_cents'     => $price,
        'deposit_percent' => $pct,
    ];
    if ($v['id'] !== '') {
        $rule['id'] = $v['id'];
    }
    return $rule;
}

function panel_pricing_rule_to_form(array $r): array
{
    $cents = $r['price_cents'];
    return [
        'id'              => $r['id'],
        'label'           => $r['label'],
        'type'            => $r['type'],
        'start_date'      => $r['start_date'],
        'end_date'        => $r['end_date'],
        'weekdays'        => (array) $r['weekdays'],
        'packages_mode'   => $r['package_ids'] === 'all' ? 'all' : 'some',
        'packages'        => $r['package_ids'] === 'all' ? [] : (array) $r['package_ids'],
        'price'           => $cents === null ? '' : (string) ($cents % 100 === 0 ? intdiv($cents, 100) : $cents / 100),
        'deposit_percent' => $r['deposit_percent'] === null ? '' : (string) $r['deposit_percent'],
    ];
}

/* ============================================================================
   RENDER
============================================================================ */

function panel_pricing_nav(string $section): string
{
    $tabs = [
        'bookings' => ['Reservas', self_url()],
        'pricing'  => ['Precios', self_url(['view' => 'precios'])],
    ];
    $out = '<nav class="tabs" aria-label="Secciones">';
    foreach ($tabs as $key => [$label, $url]) {
        $out .= '<a href="' . h($url) . '"' . ($key === $section ? ' aria-current="page"' : '') . '>' . h($label) . '</a>';
    }
    return $out . '</nav>';
}

function panel_field_err(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<p class="ferr" role="alert">' . h($errors[$key]) . '</p>' : '';
}

function panel_pricing_hidden(string $op, string $tab, string $extra = ''): string
{
    return '<input type="hidden" name="do" value="pricing" /><input type="hidden" name="op" value="' . h($op) . '" />'
        . '<input type="hidden" name="tab" value="' . h($tab) . '" /><input type="hidden" name="csrf" value="' . h(csrf_token()) . '" />'
        . $extra;
}

function panel_pricing_btn(string $op, string $tab, string $id, string $label, string $style = '', ?string $confirm = null, string $extra = ''): string
{
    $c = $confirm !== null ? ' onsubmit="return confirm(' . h(json_encode($confirm, JSON_UNESCAPED_UNICODE)) . ')"' : '';
    return '<form method="post" action="panel.php" class="inl"' . $c . '>'
        . panel_pricing_hidden($op, $tab, '<input type="hidden" name="id" value="' . h($id) . '" />' . $extra)
        . '<button class="btn sm ' . h($style) . '" type="submit">' . h($label) . '</button></form>';
}

/** Cuerpo completo de la seccion Precios. $state puede traer errors/values/edit tras un intento fallido. */
function panel_pricing_render(string $tab, array $ctx, array $state = []): string
{
    $tab = panel_pricing_tab($tab);
    $out = panel_pricing_nav('pricing')
        . '<div class="phead"><h1 class="ptitle">Precios</h1>'
        . '</div>';

    if (!$ctx['enabled']) {
        $out .= '<p class="note"><strong>Las tarifas por fecha están apagadas.</strong> El sitio cobra como antes '
            . '(anticipo fijo). Lo que captures aquí se guarda y se puede revisar, pero no llega a los clientes hasta que se encienda.</p>';
    }
    if (!$ctx['ok']) {
        return $out . '<p class="flash err">' . h($ctx['error']) . '</p>';
    }

    $out .= '<nav class="subtabs" aria-label="Precios">';
    foreach (PANEL_PRICING_TABS as $key => $label) {
        $out .= '<a href="' . h(self_url(['view' => 'precios', 'tab' => $key])) . '"'
            . ($key === $tab ? ' aria-current="page"' : '') . '>' . h($label) . '</a>';
    }
    $out .= '</nav>';

    if ($tab === 'temporadas') {
        return $out . panel_pricing_render_seasons($ctx, $state);
    }
    if ($tab === 'sin-vuelo') {
        return $out . panel_pricing_render_blocked($ctx, $state);
    }
    if ($tab === 'historial') {
        return $out . panel_pricing_render_history($ctx);
    }
    if ($tab === 'vista-previa') {
        return $out . panel_pricing_render_preview($ctx);
    }
    return $out . panel_pricing_render_deposits($ctx, $state);
}

function panel_pricing_render_deposits(array $ctx, array $state): string
{
    $errors = $state['errors'] ?? [];
    $values = $state['values'] ?? [];
    ob_start();
    ?>
    <p class="muted">Cuánto se cobra ahora para apartar el lugar, como porcentaje del precio de esa fecha. El resto se paga en sitio.</p>
    <form method="post" action="panel.php">
      <?= panel_pricing_hidden('deposit_save', 'anticipos') ?>
      <div class="tablewrap"><table class="ptable">
        <thead><tr>
          <th>Paquete</th><th class="num">Precio base por persona</th><th>Anticipo %</th><th class="hide-sm">Equivale a</th>
        </tr></thead>
        <tbody>
        <?php foreach ($ctx['packages'] as $slug => $p):
            $cur = $p['default_deposit_percent'];
            $val = array_key_exists($slug, $values) ? (string) $values[$slug] : ($cur === null ? '' : (string) $cur);
            ?>
          <tr>
            <td>
              <span class="strong"><?= h(panel_pkg_name($p)) ?></span>
              <?php if ($cur === null): ?><span class="badge warn">Falta el anticipo</span><?php endif; ?>
            </td>
            <td class="num"><?= h(panel_pesos($p['base_price_cents'])) ?></td>
            <td>
              <label class="sr" for="dep-<?= h($slug) ?>">Anticipo de <?= h(panel_pkg_name($p)) ?></label>
              <input id="dep-<?= h($slug) ?>" class="pct" type="number" inputmode="numeric" min="1" max="100" step="1"
                     name="deposit[<?= h($slug) ?>]" value="<?= h($val) ?>" data-price="<?= (int) $p['base_price_cents'] ?>"
                     data-eq="eq-<?= h($slug) ?>" /> %
              <?= panel_field_err($errors, $slug) ?>
            </td>
            <td class="hide-sm muted" id="eq-<?= h($slug) ?>" aria-live="polite"></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <p class="muted sm">El precio base se cambia en <a href="/admin/">Contenido › Paquetes</a>. Mientras a un paquete le falte el anticipo,
        se cobra como hoy.</p>
      <p><button class="btn primary" type="submit">Guardar anticipos</button></p>
    </form>
    <script>
      (function () {
        function fmt(c) { var w = c % 100 === 0; return '$' + (c / 100).toLocaleString('en-US', { minimumFractionDigits: w ? 0 : 2, maximumFractionDigits: w ? 0 : 2 }); }
        document.querySelectorAll('input.pct').forEach(function (i) {
          var out = document.getElementById(i.getAttribute('data-eq'));
          function upd() {
            var p = parseInt(i.value, 10);
            out.textContent = p >= 1 && p <= 100 ? fmt(Math.floor((parseInt(i.getAttribute('data-price'), 10) * p + 50) / 100)) + ' por persona' : '';
          }
          i.addEventListener('input', upd); upd();
        });
      })();
    </script>
    <?php
    return (string) ob_get_clean();
}

function panel_pricing_pkg_label(array $rule, array $ctx): string
{
    if ($rule['package_ids'] === 'all') {
        return 'Todos';
    }
    $names = [];
    foreach ((array) $rule['package_ids'] as $slug) {
        $names[] = isset($ctx['packages'][$slug]) ? panel_pkg_name($ctx['packages'][$slug]) : $slug;
    }
    return implode(', ', $names);
}

function panel_pricing_render_seasons(array $ctx, array $state): string
{
    $editing = $state['edit'] ?? null;
    if (isset($state['values'])) {
        $form = $state['values'];
    } elseif (($_GET['new'] ?? '') === '1') {
        $form = ['id' => ''];
    } elseif (isset($_GET['edit'])) {
        $editing = (string) $_GET['edit'];
        $found = null;
        foreach ($ctx['rules'] as $r) {
            if ($r['id'] === $editing && $r['type'] !== 'blocked') {
                $found = $r;
            }
        }
        $form = $found ? panel_pricing_rule_to_form($found) : null;
    } else {
        $form = null;
    }
    $out = '';
    if ($form !== null) {
        $out .= panel_pricing_rule_form($form, $state['errors'] ?? [], $ctx, false);
    } else {
        $out .= '<p><a class="btn primary" href="' . h(self_url(['view' => 'precios', 'tab' => 'temporadas', 'new' => '1'])) . '">Nueva temporada o fecha</a></p>';
    }

    $rules = array_values(array_filter($ctx['rules'], function (array $r): bool {
        return $r['type'] !== 'blocked';
    }));
    usort($rules, function (array $a, array $b): int {
        return strcmp($a['start_date'], $b['start_date']) ?: strcmp($a['id'], $b['id']);
    });

    $out .= '<div class="tablewrap"><table class="ptable"><thead><tr>'
        . '<th>Nombre</th><th class="hide-sm">Tipo</th><th>Fechas</th><th class="hide-md">Paquetes</th><th class="num">Precio</th>'
        . '<th class="hide-sm">Anticipo</th><th>Estado</th><th></th></tr></thead><tbody>';
    if (!$rules) {
        $out .= '<tr><td class="empty" colspan="8">Todavía no hay temporadas ni fechas especiales.</td></tr>';
    }
    foreach ($rules as $r) {
        $dates = panel_fecha_rango($r['start_date'], $r['end_date']);
        if ($r['type'] === 'weekday') {
            $days = [];
            foreach (PANEL_DIAS as $n => $name) {
                if (in_array($n, (array) $r['weekdays'], true)) {
                    $days[] = $name;
                }
            }
            $dates .= '<br><span class="muted sm">Solo ' . h(implode(', ', $days)) . '</span>';
        } else {
            $dates = h($dates);
        }
        $active = !empty($r['active']);
        $out .= '<tr>'
            . '<td class="strong">' . h($r['label']) . '<br><span class="muted sm show-sm">' . h(PANEL_TIPOS[$r['type']] ?? $r['type']) . '</span></td>'
            . '<td class="hide-sm">' . h(PANEL_TIPOS[$r['type']] ?? $r['type']) . '</td>'
            . '<td class="dates">' . $dates . '</td>'
            . '<td class="hide-md">' . h(panel_pricing_pkg_label($r, $ctx)) . '</td>'
            . '<td class="num">' . h(panel_pesos((int) $r['price_cents'])) . ' por persona</td>'
            . '<td class="hide-sm">' . ($r['deposit_percent'] === null ? 'El del paquete' : (int) $r['deposit_percent'] . ' %') . '</td>'
            . '<td><span class="badge ' . ($active ? 'ok' : 'warn') . '">' . ($active ? 'Activa' : 'Pausada') . '</span></td>'
            . '<td><div class="actions">'
            . '<a class="btn sm ghost" href="' . h(self_url(['view' => 'precios', 'tab' => 'temporadas', 'edit' => $r['id']])) . '">Editar</a>'
            . panel_pricing_btn('rule_duplicate', 'temporadas', $r['id'], 'Duplicar', 'ghost')
            . panel_pricing_btn('rule_toggle', 'temporadas', $r['id'], $active ? 'Pausar' : 'Activar', 'ghost', null,
                '<input type="hidden" name="to" value="' . ($active ? 'off' : 'on') . '" />')
            . panel_pricing_btn('rule_delete', 'temporadas', $r['id'], 'Eliminar', 'danger',
                "¿Eliminar '" . $r['label'] . "'? Esta acción no se puede deshacer")
            . '</div></td></tr>';
        $notes = panel_overlap_notes($r, $ctx['rules']);
        if ($notes) {
            $out .= '<tr class="notes"><td colspan="8">' . panel_notes_html($notes) . '</td></tr>';
        }
    }
    return $out . '</tbody></table></div>';
}

function panel_pricing_rule_form(array $v, array $errors, array $ctx, bool $blocked): string
{
    $v += [
        'id' => '', 'label' => '', 'type' => 'season', 'start_date' => '', 'end_date' => '', 'weekdays' => [],
        'packages_mode' => 'all', 'packages' => [], 'price' => '', 'deposit_percent' => '',
    ];
    $tab = $blocked ? 'sin-vuelo' : 'temporadas';
    $isEdit = $v['id'] !== '';
    ob_start();
    ?>
    <form method="post" action="panel.php" class="card pform" id="rule-form" novalidate>
      <?= panel_pricing_hidden($blocked ? 'block_save' : 'rule_save', $tab, '<input type="hidden" name="id" value="' . h($v['id']) . '" />') ?>
      <h3><?= $blocked ? 'Marcar días sin vuelo' : ($isEdit ? 'Editar' : 'Nueva temporada o fecha') ?></h3>

      <label class="fld"><span><?= $blocked ? 'Motivo' : 'Nombre' ?></span>
        <input type="text" name="label" maxlength="80" value="<?= h($v['label']) ?>"
               placeholder="<?= $blocked ? 'Ej. Mantenimiento' : 'Ej. Temporada navideña' ?>" required />
        <?= panel_field_err($errors, 'label') ?>
      </label>

      <?php if (!$blocked): ?>
      <fieldset class="fld"><legend>Tipo</legend>
        <?php foreach (PANEL_TIPOS as $key => $name): ?>
          <label class="opt"><input type="radio" name="type" value="<?= h($key) ?>" <?= $v['type'] === $key ? 'checked' : '' ?> /> <?= h($name) ?></label>
        <?php endforeach; ?>
        <?= panel_field_err($errors, 'type') ?>
      </fieldset>
      <?php endif; ?>

      <div class="row2">
        <label class="fld"><span>Desde</span>
          <input type="date" name="start_date" value="<?= h($v['start_date']) ?>" required />
          <?= panel_field_err($errors, 'start_date') ?>
        </label>
        <label class="fld"><span><?= $blocked ? 'Hasta (opcional)' : 'Hasta' ?></span>
          <input type="date" name="end_date" value="<?= h($v['end_date']) ?>" />
          <?= panel_field_err($errors, 'end_date') ?>
        </label>
      </div>

      <?php if (!$blocked): ?>
      <fieldset class="fld" id="weekdays-box"><legend>Días</legend>
        <?php foreach (PANEL_DIAS as $n => $name): ?>
          <label class="opt"><input type="checkbox" name="weekdays[]" value="<?= $n ?>" <?= in_array($n, $v['weekdays'], true) ? 'checked' : '' ?> /> <?= h($name) ?></label>
        <?php endforeach; ?>
        <?= panel_field_err($errors, 'weekdays') ?>
      </fieldset>
      <?php endif; ?>

      <fieldset class="fld"><legend>Paquetes</legend>
        <label class="opt"><input type="radio" name="packages_mode" value="all" <?= $v['packages_mode'] === 'all' ? 'checked' : '' ?> /> Todos</label>
        <label class="opt"><input type="radio" name="packages_mode" value="some" <?= $v['packages_mode'] === 'some' ? 'checked' : '' ?> /> Solo algunos</label>
        <div class="pkgs" id="pkgs-box">
          <?php foreach ($ctx['packages'] as $slug => $p): ?>
            <label class="opt"><input type="checkbox" name="packages[]" value="<?= h($slug) ?>" <?= in_array($slug, $v['packages'], true) ? 'checked' : '' ?> /> <?= h(panel_pkg_name($p)) ?></label>
          <?php endforeach; ?>
        </div>
        <?= panel_field_err($errors, 'package_ids') ?>
      </fieldset>

      <?php if (!$blocked): ?>
      <div class="row2">
        <label class="fld"><span>Precio por persona (pesos)</span>
          <input type="text" inputmode="numeric" name="price" value="<?= h($v['price']) ?>" placeholder="Ej. 2,650" />
          <?= panel_field_err($errors, 'price_cents') ?>
        </label>
        <label class="fld"><span>Anticipo % (opcional)</span>
          <input type="number" inputmode="numeric" min="1" max="100" name="deposit_percent" value="<?= h($v['deposit_percent']) ?>" placeholder="El del paquete" />
          <?= panel_field_err($errors, 'deposit_percent') ?>
        </label>
      </div>
      <?php endif; ?>

      <?php
        if ($isEdit) {
            foreach ($ctx['rules'] as $existing) {
                if ($existing['id'] === $v['id']) {
                    echo panel_notes_html(panel_overlap_notes($existing, $ctx['rules']));
                }
            }
        }
        ?>
      <div class="actions">
        <button class="btn primary" type="submit"><?= $blocked ? 'Marcar sin vuelo' : 'Guardar' ?></button>
        <?php if (!$blocked): ?><a class="btn ghost" href="<?= h(self_url(['view' => 'precios', 'tab' => 'temporadas'])) ?>">Cancelar</a><?php endif; ?>
      </div>
    </form>
    <script>
      (function () {
        var f = document.getElementById('rule-form');
        var wd = document.getElementById('weekdays-box');
        var pk = document.getElementById('pkgs-box');
        var start = f.elements['start_date'], end = f.elements['end_date'];
        function type() { var t = f.querySelector('input[name=type]:checked'); return t ? t.value : ''; }
        function sync() {
          if (wd) { wd.hidden = type() !== 'weekday'; }
          var some = f.querySelector('input[name=packages_mode]:checked').value === 'some';
          pk.hidden = !some;
        }
        f.addEventListener('change', sync);
        var lastStart = start.value;
        start.addEventListener('change', function () {
          var t = type();
          if ((t === 'date' || t === '') && (end.value === '' || end.value === lastStart)) { end.value = start.value; }
          lastStart = start.value;
        });
        sync();
      })();
    </script>
    <?php
    return (string) ob_get_clean();
}

function panel_pricing_render_blocked(array $ctx, array $state): string
{
    $out = '<p class="muted">Cierra fechas en las que no habrá vuelos. Esos días no se pueden reservar.</p>';
    $out .= panel_pricing_rule_form($state['values'] ?? ['id' => '', 'type' => 'blocked'], $state['errors'] ?? [], $ctx, true);

    $today = pricing_today_mx();
    $rules = array_values(array_filter($ctx['rules'], function (array $r) use ($today): bool {
        return $r['type'] === 'blocked' && $r['end_date'] >= $today;
    }));
    usort($rules, function (array $a, array $b): int {
        return strcmp($a['start_date'], $b['start_date']) ?: strcmp($a['id'], $b['id']);
    });

    $out .= '<h3 class="lh">Próximos días sin vuelo</h3><div class="tablewrap"><table class="ptable"><thead><tr>'
        . '<th>Fechas</th><th>Motivo</th><th>Paquetes</th><th></th></tr></thead><tbody>';
    if (!$rules) {
        $out .= '<tr><td class="empty" colspan="4">No hay días sin vuelo próximos.</td></tr>';
    }
    foreach ($rules as $r) {
        $out .= '<tr><td>' . h(panel_fecha_rango($r['start_date'], $r['end_date'])) . '</td>'
            . '<td class="strong">' . h($r['label']) . panel_notes_html(panel_overlap_notes($r, $ctx['rules'])) . '</td>'
            . '<td>' . h(panel_pricing_pkg_label($r, $ctx)) . '</td>'
            . '<td>' . panel_pricing_btn('rule_delete', 'sin-vuelo', $r['id'], 'Volver a abrir', 'ghost',
                "¿Volver a abrir '" . $r['label'] . "'? Esas fechas se podrán reservar de nuevo.") . '</td></tr>';
    }
    return $out . '</tbody></table></div>';
}

/* ---------------------------------------------------------------------------
   Historial
--------------------------------------------------------------------------- */

/** Resumen legible de una fila de pricing_audit. */
function panel_audit_summary(array $row, array $ctx): string
{
    $b = $row['before'];
    $a = $row['after'];
    $act = $row['action'];

    if ($row['entity'] === 'package') {
        $slug = (string) $row['entity_id'];
        $name = isset($ctx['packages'][$slug]) ? panel_pkg_name($ctx['packages'][$slug]) : $slug;
        $new = (int) ($a['default_deposit_percent'] ?? 0);
        if ($b === null) {
            return "Fijó el anticipo de {$name} en {$new} %";
        }
        return "Cambió el anticipo de {$name} de " . (int) $b['default_deposit_percent'] . " % a {$new} %";
    }

    $label = (string) (($a['label'] ?? null) ?? ($b['label'] ?? $row['entity_id']));
    $type = (string) (($a['type'] ?? null) ?? ($b['type'] ?? 'season'));
    $de = PANEL_TIPO_DE[$type] ?? 'la tarifa';

    switch ($act) {
        case 'create':
            return "Creó {$de} '{$label}'";
        case 'duplicate':
            return "Duplicó una tarifa: '{$label}'";
        case 'activate':
            return "Activó '{$label}'";
        case 'deactivate':
            return "Pausó '{$label}'";
        case 'delete':
            return $type === 'blocked' ? "Volvió a abrir '{$label}'" : "Eliminó {$de} '{$label}'";
    }

    $changes = [];
    if ($b !== null && $a !== null) {
        $oldLabel = (string) $b['label'];
        if ($oldLabel !== $label) {
            $changes[] = "Renombró '{$oldLabel}' a '{$label}'";
        }
        if ($b['price_cents'] !== $a['price_cents']) {
            $changes[] = "Cambió el precio de '{$label}' de " . panel_pesos((int) $b['price_cents']) . ' a ' . panel_pesos((int) $a['price_cents']);
        }
        if ($b['deposit_percent'] !== $a['deposit_percent']) {
            if ($b['deposit_percent'] === null) {
                $changes[] = "Fijó el anticipo de '{$label}' en " . (int) $a['deposit_percent'] . ' %';
            } elseif ($a['deposit_percent'] === null) {
                $changes[] = "Quitó el anticipo propio de '{$label}' (usa el del paquete)";
            } else {
                $changes[] = "Cambió el anticipo de '{$label}' de " . (int) $b['deposit_percent'] . ' % a ' . (int) $a['deposit_percent'] . ' %';
            }
        }
        if ($b['start_date'] !== $a['start_date'] || $b['end_date'] !== $a['end_date']) {
            $changes[] = "Cambió las fechas de '{$label}' a " . panel_fecha_rango($a['start_date'], $a['end_date']);
        }
        if ($b['package_ids'] !== $a['package_ids']) {
            $changes[] = "Cambió los paquetes de '{$label}'";
        }
        if ($b['weekdays'] !== $a['weekdays']) {
            $changes[] = "Cambió los días de '{$label}'";
        }
    }
    return $changes ? implode('. ', $changes) : "Editó '{$label}'";
}

function panel_pricing_render_history(array $ctx): string
{
    $page = max(1, (int) ($_GET['p'] ?? 1));
    $res = pricing_audit_list($ctx['pdo'], $page, 25);
    $pages = max(1, (int) ceil($res['total'] / $res['per_page']));

    $out = '<p class="muted">Todo cambio de precios queda registrado: quién lo hizo y qué cambió.</p>'
        . '<div class="tablewrap"><table class="ptable"><thead><tr><th>Fecha</th><th>Quién</th><th>Qué</th></tr></thead><tbody>';
    if (!$res['rows']) {
        $out .= '<tr><td class="empty" colspan="3">Todavía no hay cambios.</td></tr>';
    }
    foreach ($res['rows'] as $r) {
        $out .= '<tr><td class="nowrap">' . h(panel_fecha_humana(substr((string) $r['at'], 0, 10)) . ' · ' . substr((string) $r['at'], 11, 5)) . '</td>'
            . '<td>' . h($r['user']) . '</td>'
            . '<td>' . h(panel_audit_summary($r, $ctx)) . '</td></tr>';
    }
    $out .= '</tbody></table></div>';
    if ($pages > 1) {
        $out .= '<div class="pager">';
        if ($res['page'] > 1) {
            $out .= '<a class="btn sm ghost" href="' . h(self_url(['view' => 'precios', 'tab' => 'historial', 'p' => $res['page'] - 1])) . '">← Más recientes</a>';
        }
        $out .= '<span class="muted sm">Página ' . $res['page'] . ' de ' . $pages . '</span>';
        if ($res['page'] < $pages) {
            $out .= '<a class="btn sm ghost" href="' . h(self_url(['view' => 'precios', 'tab' => 'historial', 'p' => $res['page'] + 1])) . '">Anteriores →</a>';
        }
        $out .= '</div>';
    }
    return $out;
}

/* ---------------------------------------------------------------------------
   Vista previa (tabla de 60 dias con las mismas reglas que el checkout)
--------------------------------------------------------------------------- */

function panel_pricing_render_preview(array $ctx): string
{
    $slug = (string) ($_GET['pkg'] ?? '');
    if (!isset($ctx['packages'][$slug])) {
        $slug = (string) (array_keys($ctx['packages'])[0] ?? '');
    }
    $out = '<p class="muted">Así verá el precio cada día un cliente que reserve, con las tarifas que hay hoy '
        . '(aunque las tarifas por fecha sigan apagadas para el público).</p>'
        . '<form method="get" action="panel.php" class="filters"><input type="hidden" name="view" value="precios" />'
        . '<input type="hidden" name="tab" value="vista-previa" />'
        . '<label class="sr" for="pv-pkg">Paquete</label><select id="pv-pkg" name="pkg" onchange="this.form.submit()">';
    foreach ($ctx['packages'] as $s => $p) {
        $out .= '<option value="' . h($s) . '"' . ($s === $slug ? ' selected' : '') . '>' . h(panel_pkg_name($p)) . '</option>';
    }
    $out .= '</select><noscript><button class="btn sm" type="submit">Ver</button></noscript></form>';

    if ($slug === '') {
        return $out . '<p class="empty">No hay paquetes reservables.</p>';
    }
    $pkg = $ctx['packages'][$slug];
    if ($pkg['default_deposit_percent'] === null) {
        return $out . '<p class="note">A este paquete le falta el anticipo. Mientras tanto se cobra como hoy. Captúralo en \'Anticipo por paquete\'.</p>';
    }

    $out .= '<iframe class="pvframe" title="Calendario de precios de ' . h(panel_pkg_name($pkg)) . '" '
        . 'src="/panel-vista-previa/?pkg=' . h(rawurlencode($slug)) . '"></iframe>'
        . '<p class="muted sm">Pasa el cursor sobre un día para ver qué tarifa aplica. Es el mismo calendario que verá el cliente.</p>'
        . '<details class="pvlist"><summary>Ver la lista de los próximos 60 días</summary>';

    $byId = [];
    foreach ($ctx['rules'] as $r) {
        $byId[$r['id']] = $r;
    }
    $from = pricing_add_days(pricing_today_mx(), 1);
    $to = pricing_add_days($from, 59);
    $out .= '<div class="tablewrap"><table class="ptable"><thead><tr><th>Día</th><th class="num">Precio</th>'
        . '<th class="num hide-sm">Anticipo</th><th class="num hide-sm">Saldo</th><th>Tarifa</th></tr></thead><tbody>';
    foreach (resolve_range($from, $to, $pkg, $ctx['rules'], $from, $to) as $d => $res) {
        $dow = PANEL_DIAS[pricing_weekday($d)];
        $day = h($dow . ' ' . panel_fecha_humana($d));
        $rule = isset($res['rule_id']) ? ($byId[$res['rule_id']] ?? null) : null;
        if ($res['status'] === 'blocked') {
            $out .= '<tr class="blockedday"><td>' . $day . '</td><td class="num" colspan="3">Sin vuelo</td><td>' . h($rule['label'] ?? '') . '</td></tr>';
            continue;
        }
        $out .= '<tr><td>' . $day . '</td><td class="num">' . h(panel_pesos($res['price'])) . '</td>'
            . '<td class="num hide-sm">' . h(panel_pesos($res['deposit'])) . '</td>'
            . '<td class="num hide-sm">' . h(panel_pesos($res['balance'])) . '</td>'
            . '<td>' . ($rule !== null ? h($rule['label']) : '<span class="muted">Precio base</span>') . '</td></tr>';
    }
    return $out . '</tbody></table></div><p class="muted sm">Precios por persona. El saldo se paga en sitio.</p></details>';
}

function panel_pricing_css(): string
{
    return <<<CSS
    <style>
      .tabs{display:flex;gap:.3rem;margin:0 0 1.1rem;border-bottom:1px solid var(--line-soft)}
      .tabs a{padding:.55rem .95rem;font-weight:600;color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-1px}
      .tabs a:hover{color:var(--ink);text-decoration:none}
      .tabs a[aria-current=page]{color:var(--ink);border-bottom-color:var(--accent)}
      .subtabs{display:flex;flex-wrap:wrap;gap:.5rem;margin:.9rem 0 1.1rem}
      .subtabs a{padding:.35rem .85rem;border:1px solid var(--line);border-radius:999px;font-size:.86rem;color:var(--ink-soft)}
      .subtabs a:hover{border-color:var(--accent);text-decoration:none}
      .subtabs a[aria-current=page]{background:var(--surface2);border-color:var(--accent);color:var(--ink)}
      .ptitle{margin-bottom:.4rem}
      .note{padding:.65rem .85rem;border-radius:10px;margin:.6rem 0;font-size:.88rem;background:color-mix(in srgb,var(--warn) 12%,transparent);border:1px solid color-mix(in srgb,var(--warn) 32%,transparent)}
      .ptable tbody tr{cursor:default}
      .ptable tbody tr:hover{background:transparent}
      .ptable td{vertical-align:middle}
      .inl{display:inline;margin:0}
      .pct{width:5.2rem;font:inherit;background:var(--bg2);color:var(--ink);border:1px solid var(--line);border-radius:10px;padding:.45rem .6rem}
      .pform{display:grid;gap:.9rem;margin:0 0 1.1rem;max-width:720px}
      .fld{display:grid;gap:.3rem;border:0;padding:0;min-width:0;align-content:start}
      fieldset.fld{display:block}
      fieldset.fld>legend{margin-bottom:.3rem}
      .fld>span,.fld>legend{font-size:.85rem;color:var(--muted);padding:0}
      .fld input[type=text],.fld input[type=date],.fld input[type=number]{font:inherit;background:var(--bg2);color:var(--ink);
        border:1px solid var(--line);border-radius:10px;padding:.55rem .7rem;width:100%;min-height:2.75rem}
      .fld input:focus,.pct:focus{outline:2px solid var(--accent);outline-offset:1px;border-color:var(--accent)}
      .opt{display:inline-flex;align-items:center;gap:.4rem;margin:.15rem 1rem .15rem 0;color:var(--ink-soft)}
      .pkgs{margin-top:.3rem}
      .pkgs[hidden],#weekdays-box[hidden]{display:none}
      .row2{display:grid;gap:.9rem}
      @media(min-width:640px){.row2{grid-template-columns:1fr 1fr}}
      .ferr{color:var(--bad);font-size:.82rem}
      .lh{margin:1.4rem 0 .6rem}
      .sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
      .ptable thead th{white-space:normal}
      .show-sm{display:none}
      @media(max-width:640px){.show-sm{display:inline}}
      .ptable tr.notes td{padding-top:0;border-top:0}
      .overlap{list-style:none;padding:0;margin:.35rem 0 0;display:grid;gap:.25rem;font-size:.82rem;color:var(--warn)}
      .overlap li{padding-left:.6rem;border-left:2px solid var(--warn)}
      .nowrap{white-space:nowrap}
      .pvframe{display:block;width:100%;max-width:36rem;height:34rem;border:1px solid var(--line);border-radius:14px;background:var(--bg);margin:.4rem 0 .6rem}
      .pvlist{margin-top:1.1rem}
      .pvlist summary{cursor:pointer;color:var(--accent-strong);margin-bottom:.7rem}
      .blockedday td{color:var(--muted);background:color-mix(in srgb,var(--bad) 7%,transparent)}
      .ptable .actions{gap:.35rem;flex-wrap:wrap;max-width:190px}
      .phead{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.6rem 1rem}
      .ptable td.dates{min-width:9.5rem}
      .ptable td.num{white-space:normal}
      @media(max-width:640px){.pct{width:4.2rem}}
    </style>
    CSS;
}

/**
 * Detalles de uso en todo el panel: selector de fecha al hacer clic, controles nativos en oscuro y el globo que
 * celebra un guardado o se revienta en un error (sin animacion con prefers-reduced-motion).
 */
function panel_ux_head(): string
{
    return <<<CSS
    <style>
      :root{color-scheme:dark;--tour-bg:var(--surface);--tour-ink:var(--ink);--tour-soft:var(--ink-soft);
        --tour-muted:var(--muted);--tour-line:var(--line);--tour-accent:var(--accent);--tour-accent-ink:var(--accent-ink);
        --tour-font:var(--font)}
      .tour-btn{white-space:nowrap}
      @media(max-width:560px){.topright .muted.sm{display:none}}
      .fld input[type=date],.filters input[type=date]{cursor:pointer}
      .flash,.ferr{position:relative}
      .gl-fx{position:absolute;left:0;top:0;width:100%;height:100%;pointer-events:none;overflow:visible;z-index:6}
      .gl-b{position:absolute;bottom:40%;width:26px;height:33px;will-change:transform,opacity}
      .gl-b svg{display:block;width:100%;height:100%}
      .gl-p{position:absolute;width:5px;height:5px;border-radius:50%;background:var(--bad);will-change:transform,opacity}
    </style>
    CSS;
}

function panel_ux_foot(): string
{
    return <<<'HTML'
    <script>
    (function(){
      document.querySelectorAll('input[type=date]').forEach(function(i){
        i.addEventListener('click',function(){try{i.showPicker&&i.showPicker()}catch(e){}});
      });
      var f=document.querySelector('.flash.ok,.flash.err')||document.querySelector('.ferr');
      if(!f||!f.animate)return;
      if(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches)return;
      var ok=f.classList.contains('ok');
      var P='M16 3.4C10 3.4 5.8 7.9 5.8 13.1c0 4.6 3.3 7.8 7.3 9.3l1.1 1.8h3.6l1.1-1.8c4-1.5 7.3-4.7 7.3-9.3C26.2 7.9 22 3.4 16 3.4Z';
      function balloon(c){
        var b=document.createElement('span');b.className='gl-b';
        b.innerHTML='<svg viewBox="0 0 32 32" aria-hidden="true"><path d="'+P+'" fill="'+c+'"/>'
          +'<path d="M11.2 9.4c1-2.2 2.8-3.4 4.6-3.7" stroke="#fff" stroke-opacity=".45" stroke-width="1.6" fill="none" stroke-linecap="round"/>'
          +'<path d="M13.4 22.6 14.4 24.4M18.6 22.6 17.6 24.4" stroke="'+c+'" stroke-width="1.3" stroke-linecap="round"/>'
          +'<rect x="13.5" y="24.4" width="5" height="3.4" rx="1" fill="'+c+'"/></svg>';
        return b;
      }
      var fx=document.createElement('span');fx.className='gl-fx';fx.setAttribute('aria-hidden','true');f.appendChild(fx);
      var out='cubic-bezier(.23,1,.32,1)';
      if(ok){
        var cs=['#9cc2f5','#6f9de0','#ea83c1'];
        [[12,1,0],[38,.8,110],[62,.68,210]].forEach(function(a,n){
          var b=balloon(cs[n]);b.style.left=a[0]+'px';fx.appendChild(b);
          var s=a[1],dx=n%2?6:-5;
          b.animate([
            {transform:'translate(0,10px) scale('+s*.92+')',opacity:0},
            {transform:'translate('+dx*.3+'px,-18px) scale('+s+')',opacity:1,offset:.22},
            {transform:'translate('+dx+'px,-96px) scale('+s+')',opacity:0}
          ],{duration:1500,delay:a[2],easing:out,fill:'both'});
        });
        setTimeout(function(){fx.remove()},1900);
      }else{
        var c=getComputedStyle(f).color,b=balloon(c);b.style.left='14px';fx.appendChild(b);
        var rise=b.animate([
          {transform:'translateY(10px) scale(.92)',opacity:0},
          {transform:'translateY(-30px) scale(1)',opacity:1}
        ],{duration:560,easing:out,fill:'forwards'});
        rise.onfinish=function(){
          b.animate([
            {transform:'translateY(-30px) scale(1)',opacity:1},
            {transform:'translateY(-30px) scale(1.3)',opacity:0}
          ],{duration:120,easing:'ease-out',fill:'forwards'});
          for(var i=0;i<7;i++){
            var p=document.createElement('span');p.className='gl-p';p.style.background=c;
            p.style.left='25px';p.style.bottom='calc(40% + 48px)';fx.appendChild(p);
            var a=i/7*Math.PI*2,r=16+(i%2)*6;
            p.animate([
              {transform:'translate(0,0) scale(1)',opacity:1},
              {transform:'translate('+Math.cos(a)*r+'px,'+Math.sin(a)*r+'px) scale(.5)',opacity:0}
            ],{duration:320,easing:out,fill:'forwards'});
          }
          setTimeout(function(){fx.remove()},500);
        };
      }
    })();
    </script>
    HTML;
}

/** Boton "Ver recorrido": repite el tour de la pantalla actual (ver public/ui/tour.js). */
function panel_tour_button(): string
{
    return '<button type="button" class="btn ghost sm tour-btn" data-tour-start>Ver recorrido</button>';
}

/**
 * Recorridos guiados del panel. Cada pantalla tiene el suyo y se muestra solo la primera vez; "Ver recorrido"
 * (arriba) lo repite. El motor vive en el sitio publico: /ui/tour.js. Si no carga, el panel funciona igual.
 */
function panel_tours_script(string $section): string
{
    $view = (string) ($_GET['view'] ?? '');
    if ($section === 'pricing') {
        $page = 'precios-' . panel_pricing_tab((string) ($_GET['tab'] ?? ($_POST['tab'] ?? '')));
    } elseif ($view === 'booking') {
        $page = 'reserva';
    } else {
        $page = 'reservas';
    }
    return '<script src="/ui/tour.js" defer></script>'
        . '<script>window.PANEL_TOUR_PAGE=' . json_encode($page) . ';</script>'
        . <<<'HTML'
    <script>
    window.addEventListener('DOMContentLoaded',function(){
      if(!window.AeroTour)return;
      var page=window.PANEL_TOUR_PAGE;
      var hasForm=!!document.getElementById('rule-form');
      var BTN={sel:'.tour-btn',title:'Repite el recorrido',body:'Si olvidas algo, aquí lo vuelves a ver. Cada pantalla del panel tiene su propio recorrido.'};
      var T={
        'reservas':[
          {title:'Bienvenida al panel de ventas',body:'Aquí llega cada reserva que se hace en el sitio, con su pago. Te muestro en un minuto para qué sirve cada parte.'},
          {sel:'nav.tabs',title:'Reservas y Precios',body:'Cambia entre la lista de reservas y la sección de <b>Precios</b>, donde manejas temporadas, anticipos y días sin vuelo.'},
          {sel:'.kpis',title:'Resumen del negocio',body:'Reservas pagadas, lo cobrado en línea, el <b>saldo que falta cobrar en sitio</b> y cuántos vuelos vienen.'},
          {sel:'.filters input[name=q]',title:'Buscar una reserva',body:'Escribe folio, nombre, correo o teléfono y presiona <b>Filtrar</b>.'},
          {sel:'.filters select[name=status]',title:'Filtrar por estado',body:'Pagadas, con saldo por cobrar, pendientes de pago o canceladas. Útil para ver a quién cobrarle el saldo el día del vuelo.'},
          {sel:'.filters label.date',title:'Rango de fechas',body:'<b>Desde</b> y <b>Hasta</b> filtran por el día en que se hizo la reserva. Haz clic en la fecha para abrir el calendario.'},
          {sel:'.filters a[href*="do=export"]',title:'Exportar CSV',body:'Descarga la lista tal como la tienes filtrada, para abrirla en Excel o Google Sheets.'},
          {sel:'.tablewrap',title:'Lista de reservas',body:'Haz clic en una fila para abrir la reserva: ahí cobras el saldo, marcas el vuelo como completado o la cancelas. El color del estado te dice en qué va.'},
          BTN
        ],
        'reserva':[
          {sel:'.dhead',title:'La reserva de un vistazo',body:'Folio, paquete y estado del pago. El folio es el mismo que recibe el cliente en su correo.'},
          {sel:'dl.kv',title:'Datos del cliente',body:'Haz clic en el correo o el teléfono para escribirle o llamarle. Aquí también ves la fecha, los pasajeros y la tarifa que se le cobró.'},
          {sel:'.ticket',title:'Cuentas',body:'Total del vuelo, lo pagado en línea y el <b>saldo a cobrar en sitio</b> si apartó con anticipo.'},
          {sel:'.actions',title:'Acciones',body:'<b>Marcar saldo pagado</b> cuando el cliente liquide en sitio, <b>Marcar vuelo completado</b> después del vuelo y <b>Cancelar</b> si no se hará. Todo se puede deshacer.'},
          {sel:'textarea[name=admin_notes]',title:'Notas internas',body:'Recordatorios o acuerdos con el cliente. Solo las ve el equipo, nunca el cliente.'},
          {sel:'.ntf||.dcol .card:nth-child(2)',title:'Notificaciones',body:'Los avisos que se mandaron por esta reserva y si salieron bien.'},
          {sel:'a.back',title:'Volver a la lista',body:'Regresa a todas las reservas.'}
        ],
        'precios-anticipos':[
          {title:'Precios por fecha',body:'Aquí decides cuánto cuesta cada día y cuánto se cobra para apartar. Todo lo que guardas se aplica al momento.'},
          {sel:'nav.subtabs',title:'Tus herramientas',body:'<b>Anticipo por paquete</b>: lo que se cobra al apartar. <b>Temporadas</b>: precios por rango, fecha o día de la semana. <b>Días sin vuelo</b>: cierra fechas. <b>Historial</b>: quién cambió qué. <b>Vista previa</b>: el calendario como lo ve el cliente.'},
          {sel:'.ptable input.pct',title:'Anticipo %',body:'Porcentaje del precio de esa fecha que se cobra al apartar. A la derecha ves cuánto equivale por persona. Mientras un paquete diga <b>Falta el anticipo</b>, se cobra como antes ($1,000 fijos por pasajero).'},
          {sel:'.ptable thead th:nth-child(2)',title:'Precio base',body:'Es el precio de los días sin temporada. No se cambia aquí sino en el administrador de contenido (<b>Paquetes de vuelo</b>).'},
          {sel:'form button.primary[type=submit]',title:'Guardar anticipos',body:'Guarda todos los porcentajes de una vez. Si algo está mal, te marca el campo en rojo.'},
          BTN
        ],
        'precios-temporadas':hasForm?[
          {sel:'#rule-form label.fld:has(input[name=label])||#rule-form input[name=label]',title:'Nombre',body:'Para reconocerla en la lista y el historial, por ejemplo "Temporada navideña". El cliente ve el precio, no el nombre.'},
          {sel:'#rule-form fieldset.fld:has(input[name=type])||#rule-form input[name=type]',title:'Tipo',body:'<b>Temporada</b>: un rango de fechas. <b>Fecha especial</b>: uno o pocos días, y le gana a las demás. <b>Días de la semana</b>: por ejemplo sábados y domingos dentro de un rango.'},
          {sel:'#rule-form .row2',title:'Desde y Hasta',body:'Haz clic en la fecha para abrir el calendario. Los dos días cuentan.'},
          {sel:'#weekdays-box',title:'Días',body:'Solo para el tipo <b>Días de la semana</b>: marca cuáles aplican.'},
          {sel:'#rule-form fieldset.fld:has(input[name=packages_mode])||#pkgs-box',title:'Paquetes',body:'<b>Todos</b> o <b>Solo algunos</b>, si el precio no aplica a todos los vuelos.'},
          {sel:'#rule-form .row2:has(input[name=price])||#rule-form input[name=price]',title:'Precio y anticipo',body:'Precio <b>por persona</b> en pesos (2650 o 2,650). El anticipo es opcional: vacío usa el del paquete.'},
          {sel:'#rule-form .actions||#rule-form button[type=submit]',title:'Guardar',body:'Si se cruza con otra regla, el panel te avisa en amarillo cuál precio se cobrará.'}
        ]:[
          {sel:'a.btn.primary[href*="new"]||.subtabs + * a.btn.primary',title:'Nueva temporada o fecha',body:'Crea un precio para un rango de fechas, una fecha especial (14 de febrero) o ciertos días de la semana.'},
          {sel:'.tablewrap',title:'Tus reglas de precio',body:'Cada fila dice fechas, paquetes y precio por persona. Si dos se cruzan el mismo día gana la fecha especial, luego días de la semana, luego temporada.'},
          {sel:'.ptable .actions',title:'Botones de cada regla',body:'<b>Editar</b> la cambia. <b>Duplicar</b> hace una copia pausada para reutilizarla. <b>Pausar</b> la apaga sin borrarla. <b>Eliminar</b> la borra para siempre.'},
          BTN
        ],
        'precios-sin-vuelo':[
          {sel:'#rule-form',title:'Cerrar fechas',body:'Para mantenimiento, clima o eventos: esos días no se pueden reservar y el cliente los ve como <b>Sin vuelo</b>.'},
          {sel:'#rule-form .row2',title:'Fechas',body:'<b>Desde</b> es el día a cerrar. <b>Hasta</b> es opcional, para cerrar varios días seguidos.'},
          {sel:'#rule-form button[type=submit]',title:'Marcar sin vuelo',body:'Se aplica al momento en el calendario de reservas.'},
          {sel:'.tablewrap',title:'Días cerrados',body:'Con <b>Volver a abrir</b> las fechas se pueden reservar de nuevo.'},
          BTN
        ],
        'precios-historial':[
          {sel:'.tablewrap',title:'Historial de cambios',body:'Cada cambio de precios queda aquí: quién lo hizo, cuándo y qué cambió. Sirve para revisar si algo no cuadra.'},
          BTN
        ],
        'precios-vista-previa':[
          {sel:'#pv-pkg',title:'Elige un paquete',body:'Cada paquete puede tener precios distintos por fecha.'},
          {sel:'.pvframe',title:'Lo que verá el cliente',body:'El mismo calendario del sitio, con el precio de cada día. Pasa el cursor sobre un día para ver qué regla se aplica.'},
          {sel:'.pvlist summary',title:'Lista de 60 días',body:'Ábrela para ver precio, anticipo y saldo de cada día en tabla.'},
          BTN
        ]
      };
      var key=page+(page==='precios-temporadas'&&hasForm?'-form':'');
      var steps=T[page];
      if(steps)AeroTour.define(key,steps,{version:1});
      var b=document.querySelector('.tour-btn');
      if(b){if(steps)b.setAttribute('data-tour-start',key);else b.hidden=true;}
    });
    </script>
    HTML;
}
