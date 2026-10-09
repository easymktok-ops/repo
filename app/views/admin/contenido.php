<?php
/** @var array $keys */
/** @var bool $saved */
partial('admin-top', ['title' => 'Contenido']);
?>
<main id="contenido" class="adm adm--narrow">
  <h1 class="display display--sm">Contenido básico</h1>
  <?php if ($saved): ?><p class="form-status form-status--ok" role="status">Guardado.</p><?php endif; ?>
  <form class="contact-form" method="post" action="/admin/contenido/">
    <?= csrf_field() ?>
    <?php foreach ($keys as $k => $label): ?>
    <div class="field field--full"><label for="c-<?= e($k) ?>"><?= e($label) ?></label><input id="c-<?= e($k) ?>" name="<?= e($k) ?>" value="<?= e((string) content('site.' . $k, '')) ?>"></div>
    <?php endforeach; ?>
    <div class="field field--full"><label for="c-rq">Reseña destacada del pie de página (cita)</label><input id="c-rq" name="review_quote" value="<?= e(is_placeholder(content('footer.review_quote')) ? '' : (string) content('footer.review_quote')) ?>"></div>
    <div class="field field--full"><label for="c-ra">Autor de la reseña</label><input id="c-ra" name="review_author" value="<?= e(is_placeholder(content('footer.review_author')) ? '' : (string) content('footer.review_author')) ?>"></div>
    <div class="field field--full"><button class="btn btn--primary" type="submit">Guardar</button></div>
  </form>
  <p class="checkout__hint">Precios, cupos y textos largos se cambian por ahora en el código; los agrego al panel si los necesitan.</p>
</main>
</body></html>
