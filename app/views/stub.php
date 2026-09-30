<?php
/** @var string $stubTitle */
/** @var string $stubPath */
partial('head', ['page' => [
    'title'      => $stubTitle . ' | ' . content('site.name'),
    'path'       => $stubPath . '/',
    'indexable'  => false,
    'body_class' => 'page-stub',
]]);
partial('header');
?>
<main id="contenido" class="stub">
  <div class="container stub__inner">
    <h1 class="display display--sm"><?= e($stubTitle) ?></h1>
    <p>Esta página está en construcción.</p>
    <a class="btn btn--primary" href="/#programacion">Ver programación</a>
  </div>
</main>
<?php partial('footer'); ?>
