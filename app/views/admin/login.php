<?php
/** @var ?string $error */
/** @var bool $enabled */
partial('head', ['page' => ['title' => 'Ingreso | Panel', 'path' => '/admin/login/', 'indexable' => false, 'body_class' => 'page-admin']]);
?>
<main id="contenido" class="adm adm--narrow">
  <h1 class="display display--sm">Panel</h1>
  <?php if (!$enabled): ?>
  <p class="form-status form-status--error">El panel no está activado: falta configurar la clave de administrador en el servidor.</p>
  <?php else: ?>
  <?php if ($error): ?><p class="form-status form-status--error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <form class="contact-form" method="post" action="/admin/login/">
    <?= csrf_field() ?>
    <div class="field field--full"><label for="u">Usuario</label><input id="u" name="user" autocomplete="username" required></div>
    <div class="field field--full"><label for="p">Clave</label><input id="p" name="pass" type="password" autocomplete="current-password" required></div>
    <div class="field field--full"><button class="btn btn--primary" type="submit">Ingresar</button></div>
  </form>
  <?php endif; ?>
</main>
</body></html>
