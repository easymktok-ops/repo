<?php
/** @var string $title */
partial('head', ['page' => ['title' => $title . ' | Panel', 'path' => '/admin/', 'indexable' => false, 'body_class' => 'page-admin']]);
?>
<header class="adm-bar">
  <a class="adm-bar__brand" href="/admin/">Panel · Joyas Colombianas®</a>
  <nav class="adm-bar__nav" aria-label="Panel">
    <a href="/admin/">Resumen</a>
    <a href="/admin/pedidos/">Pedidos</a>
    <a href="/admin/contenido/">Contenido</a>
    <form method="post" action="/admin/salir/"><?= csrf_field() ?><button class="adm-link" type="submit">Salir</button></form>
  </nav>
</header>
