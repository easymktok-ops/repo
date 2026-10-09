<?php
/** @var array $prices */
/** @var string $id */
$prices = array_values(array_filter($prices ?? [], static fn($p) => !empty($p['courses'])));
if (!$prices) return;
$id = $id ?? 'menus';
?>
<section class="menus" aria-labelledby="<?= e($id) ?>-title">
  <h2 id="<?= e($id) ?>-title" class="display display--sm">Los menús</h2>
  <p class="menus__lead">Cada entrada incluye cena y una bebida: 1 copa de vino o 1 cerveza nacional.</p>
  <div class="menus__grid">
    <?php foreach ($prices as $price): ?>
    <article class="menu-card">
      <h3><?= e($price['label']) ?></h3>
      <p class="menu-card__price"><?= e(format_cop((int) $price['amount'])) ?></p>
      <dl>
        <?php foreach ($price['courses'] as $course => $text): ?>
        <dt><?= e($course) ?></dt><dd><?= e($text) ?></dd>
        <?php endforeach; ?>
      </dl>
    </article>
    <?php endforeach; ?>
  </div>
</section>
