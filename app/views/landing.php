<?php
/** @var string $landingKey  medellin | bogota */
$lp = content('landings.' . $landingKey);
$event = null;
foreach (content('programacion.events', []) as $candidate) {
    if ($candidate['id'] === ($lp['event'] ?? '')) {
        $event = $candidate;
    }
}
if ($lp === null || $event === null) {
    http_response_code(404);
    view('404');
    return;
}

$buy = ($lp['mode'] ?? 'buy') === 'buy';
$weekdays = [1 => 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábados', 'Domingos'];
$waNumber = (string) content('site.whatsapp_number');
$wa = whatsapp_link($waNumber, (string) ($lp['wa_message'] ?? ''));
$ctaHref = $buy ? '/comprar/' : $wa;
$ctaExternal = !$buy;
$prices = $event['prices'] ?? [];
$minPrice = $prices ? min(array_column($prices, 'amount')) : null;
$dates = $buy ? array_slice(Orders::functionOptions($event), 0, 4) : [];

partial('head', ['page' => [
    'title'         => $lp['meta_title'],
    'path'          => '/' . $landingKey . '/',
    'indexable'     => false,
    'body_class'    => 'page-landing',
    'preload_image' => asset_exists('img/hero-poster.webp') ? asset('img/hero-poster.webp') : null,
]]);
?>
<header class="lp-bar">
  <a class="lp-bar__brand" href="/" aria-label="<?= e(content('site.name')) ?>, inicio"><?php partial('logo', ['size' => 'sm']); ?></a>
  <a class="btn btn--primary btn--sm" href="<?= e($ctaHref) ?>"<?= $ctaExternal ? ' target="_blank" rel="noopener"' : '' ?> data-lp-cta="barra"><?= e($lp['cta_label']) ?></a>
</header>

<main id="contenido" class="lp">
  <section class="lp-hero" aria-labelledby="lp-title">
    <div class="lp-hero__media" aria-hidden="true"><?= media('hero-poster.jpg', '', 1920, 1080, 'cover', true) ?></div>
    <div class="lp-hero__scrim" aria-hidden="true"></div>

    <div class="lp-hero__inner">
      <h1 id="lp-title" class="lp-hero__title"><?= e($lp['h1']) ?></h1>
      <p class="lp-hero__lead"><?= e($lp['lead']) ?></p>

      <dl class="lp-facts">
        <div class="lp-fact">
          <dt><?= icon('pin') ?>Dónde</dt>
          <dd><?= e($event['venue']) ?><br><?= e($event['city']) ?></dd>
        </div>
        <div class="lp-fact">
          <dt><?= icon('calendar') ?>Cuándo</dt>
          <?php if ($buy): ?>
          <dd><?= e($weekdays[$event['weekday']] ?? '') ?>, función <?= e(format_time_12h($event['time'])) ?><br>Puertas <?= e(format_time_12h($event['doors'])) ?></dd>
          <?php else: ?>
          <dd>Funciones privadas<br>Fecha a convenir</dd>
          <?php endif; ?>
        </div>
        <div class="lp-fact">
          <dt><?= icon('ticket') ?>Cuánto</dt>
          <?php if ($buy && $minPrice): ?>
          <dd>Desde <?= e(format_cop((int) $minPrice)) ?><br>por persona</dd>
          <?php else: ?>
          <dd>Cotización según<br>tu evento</dd>
          <?php endif; ?>
        </div>
        <div class="lp-fact">
          <dt><?= icon('hanger') ?>Cómo</dt>
          <dd>Dress code <?= e(mb_strtolower($event['dress_code'])) ?><br><?= $buy ? 'Entrada con ticket numerado' : 'Reserva previa' ?></dd>
        </div>
      </dl>

      <?php if ($buy && $prices): ?>
      <ul class="lp-prices">
        <?php foreach ($prices as $price): ?>
        <li class="price-stub">
          <span class="price-stub__label"><?= e($price['label']) ?></span>
          <span class="price-stub__amount"><?= e(format_cop((int) $price['amount'])) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>

      <div class="lp-hero__actions">
        <a class="btn btn--primary btn--lg" href="<?= e($ctaHref) ?>"<?= $ctaExternal ? ' target="_blank" rel="noopener"' : '' ?> data-lp-cta="hero">
          <?= icon($buy ? 'ticket' : 'whatsapp') ?><?= e($lp['cta_label']) ?>
        </a>
        <?php if ($buy): ?>
        <a class="btn btn--outline btn--lg" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?><?= e(content('hero.whatsapp_label')) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($buy) partial('menus', ['prices' => $prices, 'id' => 'lp-menus']); ?>

  <?php if ($buy && $dates): ?>
  <section class="lp-dates" aria-labelledby="lp-dates-title">
    <h2 id="lp-dates-title" class="display display--sm">Próximas funciones</h2>
    <ul class="lp-dates__list">
      <?php foreach ($dates as $d): ?>
      <li><a class="lp-date" href="/comprar/?fecha=<?= e($d['value']) ?>" data-lp-cta="fecha"><span><?= e($d['label']) ?></span><?= icon('arrow') ?></a></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <section class="lp-about" aria-labelledby="lp-about-title">
    <div class="lp-about__text">
      <h2 id="lp-about-title" class="display display--sm"><?= e(content('experiencia.title')) ?></h2>
      <p><?= e((string) (content('experiencia.body', [])[0] ?? '')) ?></p>
    </div>
    <div class="lp-about__media"><?= media('contacto.jpg', 'Escenario de Joyas Colombianas® con bailarines en traje típico', 1000, 1250, 'cover') ?></div>
  </section>

  <section class="lp-faq" aria-labelledby="lp-faq-title">
    <h2 id="lp-faq-title" class="display display--sm">Antes de ir</h2>
    <dl class="lp-faq__list">
      <div><dt>¿A qué hora abren las puertas?</dt><dd><?= $buy ? 'Las puertas abren a las ' . e(format_time_12h($event['doors'])) . ' y la función empieza a las ' . e(format_time_12h($event['time'])) . '.' : 'Se define con tu evento al cotizar.' ?></dd></div>
      <div><dt>¿Qué debo ponerme?</dt><dd>El dress code es <?= e(mb_strtolower($event['dress_code'])) ?>.</dd></div>
      <?php if ($buy): ?>
      <div><dt>¿Cómo recibo mi entrada?</dt><dd>Al confirmarse el pago, aparece tu ticket numerado en pantalla. Preséntalo en la puerta con tu documento.</dd></div>
      <div><dt>¿Dónde compro?</dt><dd>Solo en esta página oficial. Para grupos grandes, escríbenos por WhatsApp.</dd></div>
      <?php else: ?>
      <div><dt>¿Cómo cotizo?</dt><dd>Escríbenos por WhatsApp con la fecha, el número de invitados y el tipo de evento.</dd></div>
      <?php endif; ?>
    </dl>
  </section>
</main>

<div class="lp-sticky">
  <a class="btn btn--primary btn--block btn--lg" href="<?= e($ctaHref) ?>"<?= $ctaExternal ? ' target="_blank" rel="noopener"' : '' ?> data-lp-cta="sticky">
    <?= icon($buy ? 'ticket' : 'whatsapp') ?><?= e($lp['cta_label']) ?><?= $buy && $minPrice ? ' desde ' . e(format_cop((int) $minPrice)) : '' ?>
  </a>
</div>

<footer class="lp-foot">
  <p><?= e(content('site.name')) ?></p>
  <p><a href="/">Sitio oficial</a> · <a href="<?= e((string) content('contacto.privacy_url')) ?>">Política de tratamiento de datos</a></p>
</footer>
</body>
</html>
