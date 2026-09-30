<?php
/** @var array $page */
$bodyClass = $page['body_class'] ?? '';
$analytics = $GLOBALS['app_config']['analytics'] ?? [];
?>
<!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b0a0c">
<?= seo_head($page) ?>

<link rel="preload" href="/assets/fonts/cinzel-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/montserrat-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<?php if (!empty($page['preload_image'])): ?>
<link rel="preload" as="image" href="<?= e($page['preload_image']) ?>" fetchpriority="high">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
<link rel="icon" href="<?= e(asset_exists('img/favicon.png') ? asset('img/favicon.png') : asset('img/favicon.svg')) ?>">

<?php if (!empty($page['schema'])): ?>
<?= schema_script($page['schema']) ?>
<?php endif; ?>

<script>window.dataLayer = window.dataLayer || [];</script>
<?php if (!empty($analytics['ga4_id'])): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($analytics['ga4_id']) ?>"></script>
<script>function gtag(){dataLayer.push(arguments);}gtag('js', new Date());gtag('config', <?= json_encode($analytics['ga4_id']) ?>);</script>
<?php endif; ?>
<?php if (!empty($analytics['meta_pixel_id'])): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',<?= json_encode($analytics['meta_pixel_id']) ?>);fbq('track','PageView');</script>
<?php endif; ?>
<script type="module" src="<?= e(asset('js/main.js')) ?>"></script>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#contenido">Saltar al contenido</a>
