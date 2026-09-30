<?php
$nav = [
    ['href' => '/#programacion', 'label' => 'Programación'],
    ['href' => '/#experiencia', 'label' => 'La experiencia'],
    ['href' => '/#que-es', 'label' => '¿Qué es un Dinner Show?'],
    ['href' => '/#contacto', 'label' => 'Contacto'],
];
?>
<header class="site-header" data-header>
  <div class="site-header__inner">
    <a class="site-header__brand" href="/" aria-label="<?= e(content('site.name')) ?>, inicio">
      <?php partial('logo', ['size' => 'sm']); ?>
    </a>

    <nav class="site-nav" aria-label="Principal">
      <ul class="site-nav__list">
        <?php foreach ($nav as $item): ?>
        <li><a class="site-nav__link" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <a class="btn btn--primary btn--sm site-header__cta" href="/#programacion"><?= e(content('hero.cta_label')) ?></a>

    <button class="site-header__toggle" type="button" aria-expanded="false" aria-controls="menu-movil" data-menu-open>
      <?= icon('menu') ?><span class="visually-hidden">Abrir menú</span>
    </button>
  </div>
</header>

<dialog class="mobile-menu" id="menu-movil" aria-label="Menú" data-menu>
  <div class="mobile-menu__top">
    <?php partial('logo', ['size' => 'sm']); ?>
    <button class="mobile-menu__close" type="button" data-menu-close>
      <?= icon('close') ?><span class="visually-hidden">Cerrar menú</span>
    </button>
  </div>
  <ul class="mobile-menu__list">
    <?php foreach ($nav as $item): ?>
    <li><a href="<?= e($item['href']) ?>" data-menu-link><?= e($item['label']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <a class="btn btn--primary btn--block" href="/#programacion" data-menu-link><?= e(content('hero.cta_label')) ?></a>
</dialog>
