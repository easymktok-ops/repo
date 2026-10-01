<?php
/** @var string $size  'sm' | 'lg' */
$size = $size ?? 'sm';
$name = content('site.name');
$file = asset_exists('img/logo.svg') ? 'img/logo.svg' : (asset_exists('img/logo.png') ? 'img/logo.png' : null);
// El logo entregado mide 869x170; el svg, de existir, debe conservar esa proporción.
[$w, $h] = $size === 'lg' ? [560, 110] : [164, 32];
?>
<?php if ($file): ?>
<span class="logo logo--<?= e($size) ?> logo--image">
  <img class="logo__img" src="<?= e(asset($file)) ?>" alt="<?= e($name) ?>" width="<?= $w ?>" height="<?= $h ?>"<?= $size === 'lg' ? ' fetchpriority="high"' : '' ?>>
  <?php if ($size === 'lg'): ?><span class="logo__cities"><?= e(content('site.cities_line')) ?></span><?php endif; ?>
</span>
<?php else: ?>
<span class="logo logo--<?= e($size) ?> logo--text">
  <span class="logo__script">Joyas Colombianas<sup>®</sup></span>
  <span class="logo__sub">Dinner &amp; Show</span>
  <?php if ($size === 'lg'): ?><span class="logo__cities"><?= e(content('site.cities_line')) ?></span><?php endif; ?>
</span>
<?php endif; ?>
