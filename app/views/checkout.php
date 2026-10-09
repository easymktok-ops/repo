<?php
/** @var array $event */
/** @var bool $ready */
/** @var array<string,string> $errors */
/** @var array $old */
/** @var array $dates */
$wa = whatsapp_link((string) content('site.whatsapp_number'), 'Hola, quiero comprar entradas para Joyas Colombianas® Dinner & Show');
$max = (int) ($GLOBALS['app_config']['checkout']['max_tickets_per_order'] ?? 10);
$selectedDate = (string) ($old['funcion'] ?? '');
if ($selectedDate === '') {
    $wanted = (string) ($_GET['fecha'] ?? '');
    $selectedDate = in_array($wanted, array_column($dates, 'value'), true) ? $wanted : (string) ($dates[0]['value'] ?? '');
}
$err = static fn(string $k): ?string => $errors[$k] ?? null;

partial('head', ['page' => [
    'title'      => 'Compra de entradas | ' . content('site.name'),
    'path'       => '/comprar/',
    'indexable'  => false,
    'body_class' => 'page-checkout',
]]);
partial('header');
?>
<main id="contenido" class="checkout">
  <div class="container">
    <header class="checkout__head">
      <h1 class="display display--sm">Compra de entradas</h1>
      <ul class="checkout__facts">
        <li><?= icon('pin') ?><span><?= e($event['venue']) ?>, <?= e($event['city']) ?></span></li>
        <li><?= icon('clock') ?><span>Función <?= e(format_time_12h($event['time'])) ?></span></li>
        <li><?= icon('door') ?><span>Puertas <?= e(format_time_12h($event['doors'])) ?></span></li>
        <li><?= icon('hanger') ?><span>Dress code <?= e(mb_strtolower($event['dress_code'])) ?></span></li>
      </ul>
    </header>

    <?php if (!$ready): ?>
    <div class="form-status form-status--error" role="status">
      <p>La compra en línea todavía no está disponible. Mientras tanto, escríbenos y te ayudamos con tu reserva.</p>
      <p><a class="btn btn--primary" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?><?= e(content('hero.whatsapp_label')) ?></a></p>
    </div>
    <?php else: ?>

    <?php if ($err('_form')): ?>
    <div class="form-status form-status--error" role="alert"><p><?= e($err('_form')) ?></p></div>
    <?php elseif ($errors): ?>
    <div class="form-status form-status--error" role="alert"><p>Revisa los campos marcados en rojo.</p></div>
    <?php endif; ?>

    <form class="checkout__form" method="post" action="/comprar/" novalidate data-checkout data-currency="COP">
      <?= csrf_field() ?>
      <div class="contact-form__trap" aria-hidden="true">
        <label for="website">No llenar</label>
        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
      </div>

      <div class="checkout__main">
        <fieldset class="checkout__step">
          <legend>1. Elige el sábado</legend>
          <?php
          $months = [];
          foreach ($dates as $d) { $months[substr($d['value'], 0, 7)][] = $d; }
          $monthNames = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
          ?>
          <div class="cal" role="radiogroup" aria-describedby="<?= $err('funcion') ? 'err-funcion' : '' ?>">
            <?php foreach ($months as $ym => $list): ?>
            <div class="cal__month">
              <h3 class="cal__title"><?= e($monthNames[(int) substr($ym, 5, 2) - 1]) ?> <?= e(substr($ym, 0, 4)) ?></h3>
              <div class="cal__days">
                <?php foreach ($list as $d): ?>
                <label class="cal__day">
                  <input type="radio" name="funcion" value="<?= e($d['value']) ?>" data-label="<?= e($d['label']) ?>"<?= $d['value'] === $selectedDate ? ' checked' : '' ?>>
                  <span class="cal__num"><?= (int) substr($d['value'], 8, 2) ?></span>
                  <span class="cal__dow">sáb</span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php if ($err('funcion')): ?><p class="field__error" id="err-funcion"><?= e($err('funcion')) ?></p><?php endif; ?>
        </fieldset>

        <fieldset class="checkout__step">
          <legend>2. Elige tu menú y cantidad</legend>
          <ul class="qty-list">
            <?php foreach ($event['prices'] as $price):
                $q = (int) ($old['qty'][$price['sku']] ?? 0); ?>
            <li class="qty-row" data-sku="<?= e($price['sku']) ?>" data-price="<?= (int) $price['amount'] ?>" data-label="<?= e($price['label']) ?>">
              <div class="qty-row__top">
                <div class="qty-row__info">
                  <label for="qty-<?= e($price['sku']) ?>"><?= e($price['label']) ?></label>
                  <span class="qty-row__price"><?= e(format_cop((int) $price['amount'])) ?></span>
                </div>
                <div class="stepper">
                  <button class="stepper__btn" type="button" data-step="-1" aria-label="Quitar una entrada de <?= e($price['label']) ?>">&minus;</button>
                  <input id="qty-<?= e($price['sku']) ?>" name="qty[<?= e($price['sku']) ?>]" type="number" inputmode="numeric" min="0" max="<?= $max ?>" value="<?= $q ?>">
                  <button class="stepper__btn" type="button" data-step="1" aria-label="Agregar una entrada de <?= e($price['label']) ?>">+</button>
                </div>
              </div>
              <?php if (!empty($price['courses'])): ?>
              <details class="qty-row__menu">
                <summary>Ver menú</summary>
                <dl>
                  <?php foreach ($price['courses'] as $course => $text): ?>
                  <dt><?= e($course) ?></dt><dd><?= e($text) ?></dd>
                  <?php endforeach; ?>
                </dl>
              </details>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php if ($err('cantidad')): ?><p class="field__error"><?= e($err('cantidad')) ?></p><?php endif; ?>
          <p class="checkout__hint">Máximo <?= $max ?> entradas por compra. Para grupos, <a href="<?= e($wa) ?>" target="_blank" rel="noopener">escríbenos por WhatsApp</a>.</p>
        </fieldset>

        <fieldset class="checkout__step">
          <legend>3. Datos para tu factura y tu ticket</legend>
          <div class="contact-form">
            <?php
            $fields = [
                ['buyer_name', 'Nombre completo o razón social', 'text', 'name', true],
                ['doc_type', 'Tipo de documento', 'select', 'off', true],
                ['doc_number', 'Número de documento', 'text', 'off', true],
                ['email', 'Correo electrónico', 'email', 'email', true],
                ['phone', 'Teléfono de contacto', 'tel', 'tel', true],
                ['city', 'Ciudad', 'text', 'address-level2', true],
            ];
            foreach ($fields as [$name, $label, $type, $auto]):
                $has = $err($name) !== null;
                $aria = $has ? ' aria-invalid="true" aria-describedby="err-' . e($name) . '"' : '';
            ?>
            <div class="field<?= $name === 'buyer_name' ? ' field--full' : '' ?>">
              <label for="f-<?= e($name) ?>"><?= e($label) ?></label>
              <?php if ($type === 'select'): ?>
              <select id="f-<?= e($name) ?>" name="<?= e($name) ?>" required<?= $aria ?>>
                <?php foreach (Orders::DOC_TYPES as $code => $text): ?>
                <option value="<?= e($code) ?>"<?= ($old[$name] ?? 'CC') === $code ? ' selected' : '' ?>><?= e($text) ?></option>
                <?php endforeach; ?>
              </select>
              <?php else: ?>
              <input id="f-<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" autocomplete="<?= e($auto) ?>" value="<?= e((string) ($old[$name] ?? '')) ?>" required<?= $aria ?>>
              <?php endif; ?>
              <?php if ($has): ?><p class="field__error" id="err-<?= e($name) ?>"><?= e($err($name)) ?></p><?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <div class="field field--check checkout__terms">
          <input id="f-acepta" name="acepta" type="checkbox" value="1" required<?= $err('acepta') ? ' aria-invalid="true" aria-describedby="err-acepta"' : '' ?>>
          <label for="f-acepta">Acepto la <a href="<?= e((string) content('contacto.privacy_url')) ?>">política de tratamiento de datos</a>.</label>
          <?php if ($err('acepta')): ?><p class="field__error" id="err-acepta"><?= e($err('acepta')) ?></p><?php endif; ?>
        </div>
      </div>

      <aside class="checkout__summary" aria-labelledby="resumen-title">
        <picture class="checkout__summary-img"><img src="/assets/img/hero-poster.jpg" alt="" width="640" height="360" loading="lazy" decoding="async"></picture>
        <h2 id="resumen-title" class="checkout__summary-title">Joyas Colombianas® Dinner &amp; Show</h2>
        <p class="checkout__summary-venue"><?= e($event['venue']) ?>, <?= e($event['city']) ?></p>
        <dl class="summary">
          <div><dt>Función</dt><dd data-summary-date><?= e(Orders::dateLabel($selectedDate)) ?></dd></div>
          <div><dt>Entradas</dt><dd data-summary-qty>0</dd></div>
        </dl>
        <ul class="summary__lines" data-summary-lines></ul>
        <p class="summary__total"><span>Total</span><strong data-summary-total>$0 COP</strong></p>
        <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('ticket') ?>Continuar al pago</button>
        <p class="checkout__hint">Pagas de forma segura en la pasarela de pagos. Tu ticket numerado aparece apenas se confirma el pago.</p>
      </aside>
    </form>
    <?php endif; ?>
  </div>
</main>
<?php partial('footer'); ?>
