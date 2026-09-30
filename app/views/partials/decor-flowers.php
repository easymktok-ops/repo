<?php
/** @var string $id */
$id = $id ?? 'f';
// Flores y hojas en trazo fino. Cada <use> se posiciona libre; se reutiliza un solo símbolo.
$flowers = $flowers ?? [
    ['x' => 90, 'y' => 140, 's' => 1.1, 'r' => -12],
    ['x' => 1290, 'y' => 220, 's' => .8, 'r' => 20],
    ['x' => 1180, 'y' => 720, 's' => 1.3, 'r' => 8],
    ['x' => 220, 'y' => 760, 's' => .7, 'r' => 35],
];
?>
<svg class="decor decor--flowers" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
  <defs>
    <g id="flor-<?= e($id) ?>">
      <g fill="none" stroke="currentColor" stroke-width="1.2">
        <ellipse cx="0" cy="-34" rx="13" ry="30"/>
        <ellipse cx="0" cy="-34" rx="13" ry="30" transform="rotate(72)"/>
        <ellipse cx="0" cy="-34" rx="13" ry="30" transform="rotate(144)"/>
        <ellipse cx="0" cy="-34" rx="13" ry="30" transform="rotate(216)"/>
        <ellipse cx="0" cy="-34" rx="13" ry="30" transform="rotate(288)"/>
        <circle r="8"/>
        <path d="M30 40c30 10 60 40 70 80M34 46c24-22 52-20 70-4-22 16-48 18-70 4Z"/>
        <path d="M-26 44c-26 18-42 50-44 86M-30 50c-28-14-54-8-66 12 24 10 48 6 66-12Z"/>
      </g>
    </g>
  </defs>
  <?php foreach ($flowers as $f): ?>
  <use href="#flor-<?= e($id) ?>" transform="translate(<?= (int) $f['x'] ?> <?= (int) $f['y'] ?>) rotate(<?= (int) $f['r'] ?>) scale(<?= (float) $f['s'] ?>)"/>
  <?php endforeach; ?>
</svg>
