<?php
/** @var string $size  'sm' | 'lg' */
$size = $size ?? 'sm';
$name = content('site.name');
$file = asset_exists('img/logo.svg') ? 'img/logo.svg' : (asset_exists('img/logo.png') ? 'img/logo.png' : null);
?>
<?php if ($file): ?>
<img class="logo logo--<?= e($size) ?>" src="<?= e(asset($file)) ?>" alt="<?= e($name) ?>" width="<?= $size === 'lg' ? 560 : 180 ?>" height="<?= $size === 'lg' ? 200 : 64 ?>"<?= $size === 'lg' ? ' fetchpriority="high"' : '' ?>>
<?php else: ?>
<span class="logo logo--<?= e($size) ?> logo--text">
  <span class="logo__script">Joyas Colombianas<sup>®</sup></span>
  <span class="logo__sub">Dinner &amp; Show</span>
  <?php if ($size === 'lg'): ?><span class="logo__cities"><?= e(content('site.cities_line')) ?></span><?php endif; ?>
</span>
<?php endif; ?>
