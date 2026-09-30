<?php
// Solo páginas indexables. Las landings de pauta quedan fuera a propósito (noindex).
$pages = [
    ['path' => '/', 'priority' => '1.0'],
];
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $p): ?>
  <url>
    <loc><?= e(url($p['path'])) ?></loc>
    <changefreq>weekly</changefreq>
    <priority><?= e($p['priority']) ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
