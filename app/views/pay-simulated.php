<?php
/** @var array $order */
partial('head', ['page' => [
    'title'      => 'Pasarela de práctica | ' . content('site.name'),
    'path'       => '/pago-simulado/',
    'indexable'  => false,
    'body_class' => 'page-checkout',
]]);
partial('header');
?>
<main id="contenido" class="checkout order">
  <div class="container order__inner">
    <p class="form-status form-status--error" role="note">Pasarela de práctica. Solo existe en pruebas: no cobra nada ni existe en producción.</p>
    <h1 class="display display--sm">Pagar <?= e(format_cop((int) $order['total_amount'])) ?></h1>
    <p><?= (int) $order['quantity'] ?> entradas para el <?= e(Orders::dateLabel($order['function_date'])) ?>, a nombre de <?= e($order['buyer_name']) ?>.</p>
    <p>Elige qué respuesta debe dar la pasarela:</p>
    <form class="order__actions" method="post" action="/pago-simulado/">
      <?= csrf_field() ?>
      <input type="hidden" name="orden" value="<?= e($order['public_id']) ?>">
      <button class="btn btn--primary" type="submit" name="resultado" value="approved">Pago aprobado</button>
      <button class="btn btn--ghost" type="submit" name="resultado" value="rejected">Pago rechazado</button>
      <button class="btn btn--ghost" type="submit" name="resultado" value="pending">Pago pendiente</button>
    </form>
  </div>
</main>
<?php partial('footer'); ?>
