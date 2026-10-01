<?php
$events = content('programacion.events', []);
$primary = null;
foreach ($events as $event) {
    if ($event['buyable']) {
        $primary = $event;
        break;
    }
}
$weekdays = [1 => 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábados', 'Domingos'];
$wa = whatsapp_link((string) content('site.whatsapp_number'), (string) content('site.whatsapp_message'));
$heroVideo = (string) content('hero.video_src');
if ($heroVideo === '' && asset_exists('img/hero.mp4')) {
    $heroVideo = asset('img/hero.mp4');
}
$sent = ($_GET['enviado'] ?? '') === '1';
$formErrors = $_SESSION['form_errors'] ?? [];
$formOld = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

partial('head', ['page' => [
    'title'         => content('seo.home.title'),
    'description'   => content('seo.home.description'),
    'path'          => '/',
    'indexable'     => true,
    'og_image'      => 'img/hero-poster.jpg',
    'body_class'    => 'page-home',
    'preload_image' => asset_exists('img/hero-poster.webp') ? asset('img/hero-poster.webp') : null,
    'schema'        => array_merge([schema_organization()], schema_events()),
]]);
partial('header');
?>
<main id="contenido">

  <section class="hero" aria-labelledby="hero-title" data-depth-scene>
    <div class="hero__media depth-layer" data-depth="0.25">
      <?= media('hero-poster.jpg', '', 1920, 1080, 'hero__poster', true) ?>
      <?php if ($heroVideo !== ''): ?>
      <video class="hero__video" muted loop playsinline preload="none" aria-hidden="true" data-hero-video>
        <source data-src="<?= e($heroVideo) ?>" type="video/mp4">
      </video>
      <?php endif; ?>
    </div>
    <div class="hero__scrim" aria-hidden="true"></div>
    <div class="hero__ribbons depth-layer" data-depth="0.6"><?php partial('decor-ribbons', ['id' => 'hero']); ?></div>

    <div class="hero__content">
      <h1 id="hero-title" class="hero__title"><?php partial('logo', ['size' => 'lg']); ?></h1>

      <?php if ($primary): ?>
      <p class="hero__meta">
        <span><?= icon('calendar') ?><?= e($weekdays[$primary['weekday']] ?? '') ?></span>
        <span><?= icon('clock') ?><?= e(format_time_12h($primary['time'])) ?></span>
        <span><?= icon('pin') ?><?= e($primary['venue']) ?></span>
      </p>
      <?php endif; ?>

      <div class="hero__actions">
        <a class="btn btn--primary btn--lg" href="#programacion"><?= icon('ticket') ?><?= e(content('hero.cta_label')) ?></a>
        <a class="btn btn--outline btn--lg" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?><?= e(content('hero.whatsapp_label')) ?></a>
      </div>
    </div>

    <a class="hero__scroll" href="#experiencia" aria-label="Bajar a la experiencia"><span></span></a>
  </section>

  <section class="statement" aria-labelledby="statement-title" data-depth-scene>
    <div class="statement__flowers depth-layer" data-depth="0.35"><?php partial('decor-flowers', ['id' => 'statement']); ?></div>
    <div class="statement__inner">
      <div class="statement__frame reveal">
        <?= media('show.jpg', 'Bailarines de Joyas Colombianas® en escena con luces del Pacífico', 1600, 900, 'statement__media') ?>
      </div>
      <p id="statement-title" class="statement__text display reveal"><?= e(content('statement')) ?></p>
    </div>
  </section>

  <section class="experiencia" id="experiencia" aria-labelledby="experiencia-title">
    <div class="experiencia__panel">
      <div class="experiencia__copy reveal">
        <h2 id="experiencia-title" class="display display--sm"><?= e(content('experiencia.title')) ?></h2>
        <?php foreach (content('experiencia.body', []) as $paragraph): ?>
        <p><?= e($paragraph) ?></p>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="experiencia__image">
      <?= media('experiencia.jpg', 'Bailarina con sombrero vueltiao y vestido típico en escena', 1200, 1000, 'cover') ?>
    </div>
  </section>

  <div class="band" aria-hidden="true">
    <div class="band__flowers"><?php partial('decor-flowers', ['id' => 'band', 'flowers' => [
        ['x' => 80, 'y' => 120, 's' => 1.4, 'r' => -20],
        ['x' => 1360, 'y' => 100, 's' => 1.2, 'r' => 30],
    ]]); ?></div>
    <div class="band__media depth-layer" data-depth="0.2"><?= media('banda.jpg', '', 1920, 900, 'cover') ?></div>
  </div>

  <section class="programacion" id="programacion" aria-labelledby="programacion-title" data-depth-scene>
    <div class="programacion__ribbons depth-layer" data-depth="0.4"><?php partial('decor-ribbons', ['id' => 'prog']); ?></div>
    <div class="container">
      <header class="programacion__cities">
        <h2 class="display display--sm reveal"><?= e(content('ciudades.title')) ?></h2>
        <ul class="city-links reveal">
          <?php foreach ($events as $event): ?>
          <li><a class="btn btn--primary btn--wide" href="#funcion-<?= e($event['id']) ?>"><?= e($event['city']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </header>

      <h2 id="programacion-title" class="display programacion__title reveal"><?= e(content('programacion.title')) ?></h2>

      <div class="event-grid">
        <?php foreach ($events as $event): ?>
        <article class="event-card reveal" id="funcion-<?= e($event['id']) ?>" aria-labelledby="evento-<?= e($event['id']) ?>">
          <header class="event-card__head">
            <h3 id="evento-<?= e($event['id']) ?>" class="event-card__city"><?= e($event['city']) ?></h3>
            <p class="event-card__summary"><?= e($event['summary']) ?></p>
          </header>

          <ul class="event-card__facts">
            <?php if (!empty($event['weekday'])): ?>
            <li><?= icon('calendar') ?><span><?= e($weekdays[$event['weekday']]) ?></span></li>
            <?php endif; ?>
            <?php if ($event['time'] !== ''): ?>
            <li><?= icon('clock') ?><span>Función <?= e(format_time_12h($event['time'])) ?></span></li>
            <?php endif; ?>
            <?php if ($event['doors'] !== ''): ?>
            <li><?= icon('door') ?><span>Puertas <?= e(format_time_12h($event['doors'])) ?></span></li>
            <?php endif; ?>
            <li><?= icon('pin') ?><span><?= e($event['venue']) ?></span></li>
            <li><?= icon('hanger') ?><span>Dress code <?= e(mb_strtolower($event['dress_code'])) ?></span></li>
          </ul>

          <?php if (!empty($event['prices'])): ?>
          <ul class="event-card__prices">
            <?php foreach ($event['prices'] as $price): ?>
            <li class="price-stub">
              <span class="price-stub__label"><?= e($price['label']) ?></span>
              <span class="price-stub__amount"><?= e(format_cop((int) $price['amount'])) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

          <a class="btn <?= $event['buyable'] ? 'btn--primary' : 'btn--outline' ?> btn--block event-card__cta" href="<?= e($event['cta_url']) ?>">
            <?= $event['buyable'] ? icon('ticket') : '' ?><?= e($event['cta_label']) ?>
          </a>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="que-es" id="que-es" aria-labelledby="que-es-title">
    <div class="que-es__image">
      <?= media('que-es.jpg', 'Bailarina con vestido amarillo y sombrero en plena coreografía', 1200, 1000, 'cover') ?>
    </div>
    <div class="que-es__panel">
      <div class="que-es__copy reveal">
        <h2 id="que-es-title" class="display display--sm"><?= e(content('que_es.title')) ?></h2>
        <?php foreach (content('que_es.body', []) as $paragraph): ?>
        <p><?= e($paragraph) ?></p>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <div class="band band--stage" aria-hidden="true">
    <div class="band__media depth-layer" data-depth="0.2"><?= media('escenario.jpg', '', 1920, 900, 'cover') ?></div>
  </div>

  <section class="contacto" id="contacto" aria-labelledby="contacto-title">
    <div class="contacto__image">
      <?= media('contacto.jpg', 'Elenco de Joyas Colombianas® con trajes del Pacífico y el Caribe', 1000, 1250, 'cover') ?>
    </div>
    <div class="contacto__body">
      <h2 id="contacto-title" class="display display--sm"><?= e(content('contacto.title')) ?></h2>

      <?php if ($sent): ?>
      <p class="form-status form-status--ok" role="status">Recibimos tu solicitud. Te responderemos pronto.</p>
      <?php endif; ?>
      <?php if ($formErrors): ?>
      <div class="form-status form-status--error" role="alert">
        <p>Revisa los campos marcados:</p>
        <ul><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>

      <form class="contact-form" method="post" action="/contacto" novalidate data-contact-form>
        <?= csrf_field() ?>
        <div class="contact-form__trap" aria-hidden="true">
          <label for="website">No llenar</label>
          <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>

        <?php
        $fields = [
            ['name' => 'nombre', 'label' => 'Nombre completo', 'type' => 'text', 'auto' => 'name', 'required' => true],
            ['name' => 'empresa', 'label' => 'NIT o Razón social', 'type' => 'text', 'auto' => 'organization', 'required' => false],
            ['name' => 'correo', 'label' => 'Correo electrónico', 'type' => 'email', 'auto' => 'email', 'required' => true],
            ['name' => 'telefono', 'label' => 'Número de contacto', 'type' => 'tel', 'auto' => 'tel', 'required' => true],
            ['name' => 'asunto', 'label' => 'Asunto', 'type' => 'text', 'auto' => 'off', 'required' => true],
        ];
        foreach ($fields as $field):
            $hasError = isset($formErrors[$field['name']]);
        ?>
        <div class="field<?= $field['name'] === 'asunto' ? ' field--full' : '' ?>">
          <label for="f-<?= e($field['name']) ?>"><?= e($field['label']) ?><?= $field['required'] ? '' : ' <span class="field__optional">(opcional)</span>' ?></label>
          <input id="f-<?= e($field['name']) ?>" name="<?= e($field['name']) ?>" type="<?= e($field['type']) ?>" autocomplete="<?= e($field['auto']) ?>"
            value="<?= e((string) ($formOld[$field['name']] ?? '')) ?>"<?= $field['required'] ? ' required' : '' ?><?= $hasError ? ' aria-invalid="true" aria-describedby="err-' . e($field['name']) . '"' : '' ?>>
          <?php if ($hasError): ?><p class="field__error" id="err-<?= e($field['name']) ?>"><?= e($formErrors[$field['name']]) ?></p><?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="field field--full">
          <label for="f-mensaje">Déjanos tu mensaje</label>
          <textarea id="f-mensaje" name="mensaje" rows="5" required<?= isset($formErrors['mensaje']) ? ' aria-invalid="true" aria-describedby="err-mensaje"' : '' ?>><?= e((string) ($formOld['mensaje'] ?? '')) ?></textarea>
          <?php if (isset($formErrors['mensaje'])): ?><p class="field__error" id="err-mensaje"><?= e($formErrors['mensaje']) ?></p><?php endif; ?>
        </div>

        <div class="field field--full field--check">
          <input id="f-acepta" name="acepta" type="checkbox" value="1" required<?= isset($formErrors['acepta']) ? ' aria-invalid="true" aria-describedby="err-acepta"' : '' ?>>
          <label for="f-acepta">Acepto la <a href="<?= e((string) content('contacto.privacy_url')) ?>">política de tratamiento de datos</a>.</label>
          <?php if (isset($formErrors['acepta'])): ?><p class="field__error" id="err-acepta"><?= e($formErrors['acepta']) ?></p><?php endif; ?>
        </div>

        <div class="contact-form__actions">
          <button class="btn btn--primary" type="submit"><?= e(content('contacto.submit_label')) ?></button>
          <a class="btn btn--ghost" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?><?= e(content('hero.whatsapp_label')) ?></a>
        </div>
      </form>
    </div>
  </section>

</main>
<?php partial('footer'); ?>
