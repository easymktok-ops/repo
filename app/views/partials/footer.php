<?php
$wa = whatsapp_link((string) content('site.whatsapp_number'), (string) content('site.whatsapp_message'));
$socials = array_filter([
    'instagram'   => ['url' => content('site.instagram'), 'label' => 'Instagram'],
    'facebook'    => ['url' => content('site.facebook'), 'label' => 'Facebook'],
    'tripadvisor' => ['url' => content('site.tripadvisor'), 'label' => 'TripAdvisor'],
], static fn(array $s): bool => !empty($s['url']));
$quote = (string) content('footer.review_quote');
?>
<footer class="site-footer">
  <div class="site-footer__inner">
    <div class="site-footer__brand">
      <a href="/" aria-label="<?= e(content('site.name')) ?>, inicio"><?php partial('logo', ['size' => 'sm']); ?></a>
      <p class="site-footer__cities"><?= e(content('site.cities_line')) ?></p>
    </div>

    <nav class="site-footer__nav" aria-label="Pie de página">
      <ul>
        <li><a href="/#experiencia">Dinner &amp; Show Joyas Colombianas®</a></li>
        <li><a href="/bogota/">Eventos privados</a></li>
        <li><a href="/#que-es">¿Qué es un Dinner Show?</a></li>
      </ul>
      <ul>
        <li><a href="/medellin/">Medellín</a></li>
        <li><a href="/bogota/">Bogotá</a></li>
      </ul>
    </nav>

    <figure class="site-footer__review">
      <span class="site-footer__review-icon"><?= icon('tripadvisor') ?></span>
      <blockquote><p><?= e($quote) ?></p></blockquote>
      <?php if (!is_placeholder($quote)): ?>
      <figcaption><?= e((string) content('footer.review_author')) ?></figcaption>
      <?php endif; ?>
    </figure>
  </div>

  <div class="site-footer__bottom">
    <ul class="site-footer__social" aria-label="Redes sociales">
      <?php foreach ($socials as $key => $social): ?>
      <li><a href="<?= e($social['url']) ?>" target="_blank" rel="noopener"><?= icon($key) ?><span class="visually-hidden"><?= e($social['label']) ?></span></a></li>
      <?php endforeach; ?>
      <li><a href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?><span class="visually-hidden">WhatsApp</span></a></li>
    </ul>
    <p class="site-footer__legal">
      © <?= date('Y') ?> <?= e(content('site.name')) ?>. Todos los derechos reservados.
      <a href="<?= e((string) content('contacto.privacy_url')) ?>">Política de tratamiento de datos</a>
    </p>
  </div>
</footer>

<a class="wa-float" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-wa-float>
  <?= icon('whatsapp') ?><span class="visually-hidden"><?= e(content('hero.whatsapp_label')) ?></span>
</a>
</body>
</html>
