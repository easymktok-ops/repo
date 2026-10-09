<?php
/** @var array $errors */
/** @var array $old */
/** @var bool $saved */
partial('admin-top', ['title' => 'Precios y cupos']);
$val = static fn(string $k, mixed $fallback): string => e((string) ($old[$k] ?? $fallback));
$field = static function (string $name, string $label, mixed $value, string $hint = '') use ($errors) {
    $err = $errors[$name] ?? null;
    echo '<div class="field"><label for="' . e($name) . '">' . e($label) . '</label>'
        . '<input id="' . e($name) . '" name="' . e($name) . '" inputmode="numeric" value="' . e((string) $value) . '"' . ($err ? ' aria-invalid="true"' : '') . '>'
        . ($hint !== '' ? '<p class="checkout__hint">' . e($hint) . '</p>' : '')
        . ($err ? '<p class="field__error">' . e($err) . '</p>' : '') . '</div>';
};
?>
<main id="contenido" class="adm adm--narrow">
  <h1 class="display display--sm">Precios y cupos</h1>
  <?php if ($saved): ?><p class="form-status form-status--ok" role="status">Guardado. Los cambios valen para las compras nuevas; las ya hechas conservan su precio.</p><?php endif; ?>
  <?php if ($errors): ?><p class="form-status form-status--error" role="alert">Revisa los campos marcados.</p><?php endif; ?>
  <form class="contact-form" method="post" action="/admin/precios/">
    <?= csrf_field() ?>
    <h2 class="adm-h2 field--full">Medellín: precio por persona (COP)</h2>
    <?php foreach (content('programacion.events', []) as $ev): foreach ($ev['prices'] as $price): if (!isset($price['amount'])) { continue; }
        $k = 'price_' . $ev['id'] . '_' . $price['sku'];
        $field($k, $price['label'], $old[$k] ?? $price['amount']);
    endforeach; endforeach; ?>

    <?php foreach (content('reservas', []) as $ev): ?>
    <h2 class="adm-h2 field--full">Reservas en <?= e($ev['venue']) ?>, <?= e($ev['city']) ?></h2>
    <?php
    $field('usd_' . $ev['id'], 'Cover por persona (USD)', $old['usd_' . $ev['id']] ?? $ev['prices'][0]['usd']);
    $field('capacity_' . $ev['id'], 'Cupos por día', $old['capacity_' . $ev['id']] ?? $ev['capacity']);
    $field('maxparty_' . $ev['id'], 'Personas por mesa (máximo)', $old['maxparty_' . $ev['id']] ?? $ev['max_party']);
    endforeach; ?>
    <?php $field('fx', 'Tasa USD→COP de cobro', $old['fx'] ?? Orders::fxRate(), 'El cover se publica en dólares y se cobra en pesos con esta tasa. Cambia el cobro de las reservas nuevas.'); ?>
    <div class="field field--full"><button class="btn btn--primary" type="submit">Guardar</button></div>
  </form>
</main>
</body></html>
