<?php
/** @var array $event */
/** @var bool $ready */
/** @var array<string,string> $errors */
/** @var array $old */
/** @var array $dates */
$wa = whatsapp_link((string) content('site.whatsapp_number'), 'Hola, quiero reservar una mesa en ' . $event['venue'] . ', ' . $event['city']);
$cover = $event['prices'][0];
$unitCop = Orders::unitAmount($cover);
$maxParty = (int) $event['max_party'];
$selectedDate = (string) ($old['funcion'] ?? '');
if ($selectedDate === '') {
    $wanted = (string) ($_GET['fecha'] ?? '');
    $selectedDate = in_array($wanted, array_column($dates, 'value'), true) ? $wanted : '';
}
$personas = (int) ($old['personas'] ?? 2);
$err = static fn(string $k): ?string => $errors[$k] ?? null;
$weekdayNames = [4 => 'jueves', 5 => 'viernes', 6 => 'sábados'];

partial('head', ['page' => [
    'title'      => 'Reserva en ' . $event['venue'] . ', ' . $event['city'] . ' | ' . content('site.name'),
    'path'       => '/reservar/cartagena/',
    'indexable'  => false,
    'body_class' => 'page-checkout',
]]);
partial('header');
?>
<main id="contenido" class="checkout">
  <div class="container">
    <header class="checkout__head">
      <h1 class="display display--sm">Reserva tu mesa en <?= e($event['venue']) ?></h1>
      <ul class="checkout__facts">
        <li><?= icon('pin') ?><span><?= e($event['venue']) ?>, <?= e($event['city']) ?></span></li>
        <li><?= icon('calendar') ?><span>Jueves, viernes y sábados</span></li>
        <li><?= icon('ticket') ?><span>Cover de USD <?= (int) $cover['usd'] ?> por persona</span></li>
      </ul>
    </header>

    <?php if (!$ready): ?>
    <div class="form-status form-status--error" role="status">
      <p>La reserva en línea todavía no está disponible. Mientras tanto, escríbenos y te ayudamos.</p>
      <p><a class="btn btn--primary" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?><?= e(content('hero.whatsapp_label')) ?></a></p>
    </div>
    <?php else: ?>

    <?php if ($err('_form')): ?>
    <div class="form-status form-status--error" role="alert"><p><?= e($err('_form')) ?></p></div>
    <?php elseif ($errors): ?>
    <div class="form-status form-status--error" role="alert"><p>Revisa los campos marcados en rojo.</p></div>
    <?php endif; ?>

    <form class="checkout__form" method="post" action="/reservar/cartagena/" novalidate data-reserva data-unit-cop="<?= $unitCop ?>" data-unit-usd="<?= (int) $cover['usd'] ?>">
      <?= csrf_field() ?>
      <div class="contact-form__trap" aria-hidden="true">
        <label for="website">No llenar</label>
        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
      </div>

      <div class="checkout__main">
        <fieldset class="checkout__step">
          <legend>1. Elige el día</legend>
          <?php
          $months = [];
          foreach ($dates as $d) { $months[substr($d['value'], 0, 7)][] = $d; }
          $monthNames = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
          $dow = [4 => 'jue', 5 => 'vie', 6 => 'sáb'];
          ?>
          <div class="cal" role="radiogroup">
            <?php foreach ($months as $ym => $list): ?>
            <div class="cal__month">
              <h3 class="cal__title"><?= e($monthNames[(int) substr($ym, 5, 2) - 1]) ?> <?= e(substr($ym, 0, 4)) ?></h3>
              <div class="cal__days">
                <?php foreach ($list as $d):
                    $left = Orders::seatsLeft($event, $d['value']);
                    $full = $left === 0; ?>
                <label class="cal__day<?= $full ? ' cal__day--full' : '' ?>">
                  <input type="radio" name="funcion" value="<?= e($d['value']) ?>" data-label="<?= e($d['label']) ?>" data-left="<?= (int) $left ?>"<?= $d['value'] === $selectedDate ? ' checked' : '' ?><?= $full ? ' disabled' : '' ?>>
                  <span class="cal__num"><?= (int) substr($d['value'], 8, 2) ?></span>
                  <span class="cal__dow"><?= e($dow[(int) (new DateTimeImmutable($d['value']))->format('N')] ?? '') ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php if ($err('funcion')): ?><p class="field__error"><?= e($err('funcion')) ?></p><?php endif; ?>
        </fieldset>

        <fieldset class="checkout__step">
          <legend>2. ¿Cuántas personas van?</legend>
          <div class="qty-row">
            <div class="qty-row__top">
              <div class="qty-row__info">
                <label for="personas">Personas en la mesa</label>
                <span class="qty-row__price">USD <?= (int) $cover['usd'] ?> por persona</span>
              </div>
              <div class="stepper">
                <button class="stepper__btn" type="button" data-step="-1" aria-label="Quitar una persona">&minus;</button>
                <input id="personas" name="personas" type="number" inputmode="numeric" min="1" max="<?= $maxParty ?>" value="<?= $personas ?>">
                <button class="stepper__btn" type="button" data-step="1" aria-label="Agregar una persona">+</button>
              </div>
            </div>
          </div>
          <?php if ($err('cantidad')): ?><p class="field__error"><?= e($err('cantidad')) ?></p><?php endif; ?>
          <p class="checkout__hint">Mesas de máximo <?= $maxParty ?> personas. Reserva solo las personas que de verdad van a asistir: así se organiza la mesa.</p>
        </fieldset>

        <fieldset class="checkout__step">
          <legend>3. Datos de la reserva</legend>
          <div class="contact-form">
            <?php
            $fields = [
                ['first_name', 'Nombre', 'text', 'given-name'],
                ['last_name', 'Apellido', 'text', 'family-name'],
                ['doc_type', 'Tipo de documento', 'select', 'off'],
                ['doc_number', 'Número de identificación', 'text', 'off'],
                ['email', 'Correo electrónico', 'email', 'email'],
                ['phone', 'Teléfono de contacto', 'tel', 'tel'],
            ];
            foreach ($fields as [$name, $label, $type, $auto]):
                $has = $err($name) !== null;
                $aria = $has ? ' aria-invalid="true" aria-describedby="err-' . e($name) . '"' : '';
            ?>
            <div class="field">
              <label for="f-<?= e($name) ?>"><?= e($label) ?></label>
              <?php if ($type === 'select'): ?>
              <select id="f-<?= e($name) ?>" name="<?= e($name) ?>" required<?= $aria ?>>
                <?php foreach (Orders::RESERVATION_DOC_TYPES as $code => $text): ?>
                <option value="<?= e($code) ?>"<?= ($old[$name] ?? 'PA') === $code ? ' selected' : '' ?>><?= e($text) ?></option>
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
          <input id="f-acepta-cover" name="acepta_cover" type="checkbox" value="1" required<?= $err('acepta_cover') ? ' aria-invalid="true" aria-describedby="err-acepta_cover"' : '' ?>>
          <label for="f-acepta-cover">Entiendo que, si cancelo, <strong>el cover no se devuelve</strong>: al reservar se deja de vender una mesa para darme prioridad. Acepto los <a href="/terminos-reservas/" target="_blank" rel="noopener">términos de la reserva</a>.</label>
          <?php if ($err('acepta_cover')): ?><p class="field__error" id="err-acepta_cover"><?= e($err('acepta_cover')) ?></p><?php endif; ?>
        </div>
        <div class="field field--check checkout__terms">
          <input id="f-acepta" name="acepta" type="checkbox" value="1" required<?= $err('acepta') ? ' aria-invalid="true" aria-describedby="err-acepta"' : '' ?>>
          <label for="f-acepta">Acepto la <a href="<?= e((string) content('contacto.privacy_url')) ?>">política de tratamiento de datos</a>.</label>
          <?php if ($err('acepta')): ?><p class="field__error" id="err-acepta"><?= e($err('acepta')) ?></p><?php endif; ?>
        </div>
      </div>

      <aside class="checkout__summary" aria-labelledby="resumen-title">
        <h2 id="resumen-title" class="checkout__summary-title">Tu reserva</h2>
        <p class="checkout__summary-venue"><?= e($event['venue']) ?>, <?= e($event['city']) ?></p>
        <dl class="summary">
          <div><dt>Día</dt><dd data-summary-date>Elige un día</dd></div>
          <div><dt>Personas</dt><dd data-summary-people>0</dd></div>
          <div><dt>Cover</dt><dd data-summary-usd>USD 0</dd></div>
        </dl>
        <p class="summary__total"><span>Total a pagar</span><strong data-summary-total>$0 COP</strong></p>
        <p class="checkout__hint">El cobro se hace en pesos colombianos, a una tasa de referencia de $<?= e(number_format(Orders::fxRate(), 0, ',', '.')) ?> por dólar. El consumo en el restaurante se paga allí.</p>
        <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('ticket') ?>Continuar al pago</button>
        <p class="checkout__hint">Al pagar recibes el voucher de tu reserva.</p>
      </aside>
    </form>
    <?php endif; ?>
  </div>
</main>
<?php partial('footer'); ?>
