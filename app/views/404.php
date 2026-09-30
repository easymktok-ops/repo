<?php
partial('head', ['page' => [
    'title'      => 'Página no encontrada | ' . content('site.name'),
    'path'       => '/404',
    'indexable'  => false,
    'body_class' => 'page-stub',
]]);
partial('header');
?>
<main id="contenido" class="stub">
  <div class="container stub__inner">
    <h1 class="display display--sm">Página no encontrada</h1>
    <p>La dirección que buscas no existe o cambió.</p>
    <a class="btn btn--primary" href="/">Volver al inicio</a>
  </div>
</main>
<?php partial('footer'); ?>
