<?php
/** @var string $id  sufijo único para los gradientes */
$id = $id ?? 'r';
?>
<svg class="decor decor--ribbons" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="rib-a-<?= e($id) ?>" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="#d81b60" stop-opacity="0"/>
      <stop offset=".35" stop-color="#f06292" stop-opacity=".75"/>
      <stop offset=".7" stop-color="#d81b60" stop-opacity=".5"/>
      <stop offset="1" stop-color="#d81b60" stop-opacity="0"/>
    </linearGradient>
    <linearGradient id="rib-b-<?= e($id) ?>" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="#f2c14e" stop-opacity="0"/>
      <stop offset=".5" stop-color="#f2c14e" stop-opacity=".55"/>
      <stop offset="1" stop-color="#f2c14e" stop-opacity="0"/>
    </linearGradient>
  </defs>
  <g fill="none" stroke-linecap="round">
    <path d="M-40 640C180 520 320 760 560 640s360-380 620-300 260 200 300 160" stroke="url(#rib-a-<?= e($id) ?>)" stroke-width="1.6"/>
    <path d="M-40 660C190 548 330 782 566 664s356-372 612-296 262 206 302 168" stroke="url(#rib-a-<?= e($id) ?>)" stroke-width=".8"/>
    <path d="M-60 300c220 60 300-120 520-60s240 260 480 220 280-200 540-140" stroke="url(#rib-b-<?= e($id) ?>)" stroke-width="1.2"/>
    <path d="M200 900c60-160 220-220 360-180s180 180 340 160 200-220 380-260" stroke="url(#rib-a-<?= e($id) ?>)" stroke-width="1.1"/>
    <path d="M-20 120c160 40 260-40 380 0s160 140 300 120" stroke="url(#rib-b-<?= e($id) ?>)" stroke-width=".7"/>
  </g>
</svg>
