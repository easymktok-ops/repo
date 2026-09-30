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
 * @return array{ok:bool,error:string,packages:array,rules:array,known:array,enabled:bool,version:string}
 */
function panel_pricing_context(array $config, PDO $pdo): array
{
    $ctx = [
        'ok' => false, 'error' => '', 'packages' => [], 'rules' => [], 'known' => [],
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
    $out = panel_pricing_nav('pricing') . '<h1 class="ptitle">Precios</h1>';

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
          <input type="text" inputmode="numeric" name="price" value="<?= h($v['price']) ?>" placeholder="2,650" />
          <?= panel_field_err($errors, 'price_cents') ?>
        </label>
        <label class="fld"><span>Anticipo % (opcional)</span>
          <input type="number" inputmode="numeric" min="1" max="100" name="deposit_percent" value="<?= h($v['deposit_percent']) ?>" />
          <small class="muted">Si lo dejas vacío se usa el del paquete.</small>
          <?= panel_field_err($errors, 'deposit_percent') ?>
        </label>
      </div>
      <?php endif; ?>

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
            . '<td class="strong">' . h($r['label']) . '</td>'
            . '<td>' . h(panel_pricing_pkg_label($r, $ctx)) . '</td>'
            . '<td>' . panel_pricing_btn('rule_delete', 'sin-vuelo', $r['id'], 'Volver a abrir', 'ghost',
                "¿Volver a abrir '" . $r['label'] . "'? Esas fechas se podrán reservar de nuevo.") . '</td></tr>';
    }
    return $out . '</tbody></table></div>';
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
      .ptable .actions{gap:.35rem;flex-wrap:wrap;max-width:190px}
      .ptable td.dates{min-width:9.5rem}
      .ptable td.num{white-space:normal}
      @media(max-width:640px){.pct{width:4.2rem}}
    </style>
    CSS;
}
