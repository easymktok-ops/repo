<?php
/** @var array $order */
$event = Orders::eventById($order['event_id']) ?? [];
$isRes = $order['kind'] === 'reservation';
$status = $order['status'];
$approved = $status === 'approved' && $order['ticket'] !== null;
$waiting = in_array($status, ['created', 'pending', 'in_process'], true) || ($status === 'approved' && $order['ticket'] === null);
$wa = whatsapp_link((string) content('site.whatsapp_number'), 'Hola, necesito ayuda con mi compra ' . ($order['ticket'] ?? ''));

partial('head', ['page' => [
    'title'      => ($approved ? ($isRes ? 'Tu reserva' : 'Tu ticket') : 'Estado de tu compra') . ' | ' . content('site.name'),
    'path'       => '/pago/' . $order['public_id'] . '/',
    'indexable'  => false,
    'body_class' => 'page-order',
]]);
partial('header');
?>
<main id="contenido" class="checkout order">
  <div class="container order__inner">

  <?php if ($approved): ?>
    <p class="form-status form-status--ok" role="status"><?= $isRes ? 'Pago aprobado. Esta es tu reserva.' : 'Pago aprobado. Esta es tu entrada.' ?></p>

    <?php if ($isRes): ?>
    <article class="ticket" aria-labelledby="ticket-code">
      <header class="ticket__head">
        <p class="ticket__brand">Voucher de reserva · <?= e((string) ($event['venue'] ?? '')) ?>, <?= e((string) ($event['city'] ?? '')) ?></p>
        <h1 id="ticket-code" class="ticket__code"><?= e($order['ticket']) ?></h1>
      </header>
      <dl class="ticket__data">
        <div><dt>Nombre</dt><dd><?= e($order['first_name']) ?></dd></div>
        <div><dt>Apellido</dt><dd><?= e($order['last_name']) ?></dd></div>
        <div><dt># Identificación</dt><dd><?= e(Orders::RESERVATION_DOC_TYPES[$order['doc_type']] ?? $order['doc_type']) ?>: <?= e($order['doc_number']) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($order['email']) ?></dd></div>
        <div><dt>Fecha de la reserva</dt><dd><?= e(Orders::dateLabel($order['function_date'])) ?></dd></div>
        <div><dt>Cantidad de personas</dt><dd><?= (int) $order['quantity'] ?></dd></div>
        <div><dt>Precio total recibido</dt><dd><?= e(format_cop((int) $order['total_amount'])) ?><?= $order['usd_total'] ? ' (USD ' . (int) $order['usd_total'] . ' a $' . number_format((int) $order['fx_rate'], 0, ',', '.') . ' por dólar)' : '' ?></dd></div>
      </dl>
      <p class="ticket__note">Presenta este voucher en el restaurante con tu documento. El cover no es reembolsable.</p>
    </article>
    <?php else: ?>
    <article class="ticket" aria-labelledby="ticket-code">
      <header class="ticket__head">
        <p class="ticket__brand"><?= e(content('site.name')) ?></p>
        <h1 id="ticket-code" class="ticket__code"><?= e($order['ticket']) ?></h1>
      </header>
      <dl class="ticket__data">
        <div><dt>A nombre de</dt><dd><?= e($order['buyer_name']) ?></dd></div>
        <div><dt>Función</dt><dd><?= e(Orders::dateLabel($order['function_date'])) ?>, <?= e(format_time_12h((string) ($event['time'] ?? ''))) ?></dd></div>
        <div><dt>Lugar</dt><dd><?= e((string) ($event['venue'] ?? '')) ?>, <?= e((string) ($event['city'] ?? '')) ?></dd></div>
        <div><dt>Puertas</dt><dd><?= e(format_time_12h((string) ($event['doors'] ?? ''))) ?></dd></div>
        <div><dt>Entradas</dt><dd><?= (int) $order['quantity'] ?> (<?= e(implode(', ', array_map(static fn(array $i): string => $i['qty'] . ' ' . $i['label'], $order['items']))) ?>)</dd></div>
        <div><dt>Total pagado</dt><dd><?= e(format_cop((int) $order['total_amount'])) ?></dd></div>
        <div><dt>Dress code</dt><dd><?= e(mb_strtolower((string) ($event['dress_code'] ?? ''))) ?></dd></div>
      </dl>
      <p class="ticket__note">Presenta este número en la puerta con tu documento.</p>
    </article>
    <?php endif; ?>

    <div class="order__actions">
      <button class="btn btn--primary" type="button" data-print><?= icon('ticket') ?>Guardar o imprimir <?= $isRes ? 'voucher' : 'ticket' ?></button>
      <a class="btn btn--ghost" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?>Necesito ayuda</a>
    </div>
    <p class="checkout__hint">Guarda esta dirección: es la única forma de volver a ver tu <?= $isRes ? 'voucher' : 'ticket' ?>.</p>

    <script>
      (function () {
        var id = <?= json_encode($order['ticket']) ?>;
        var key = 'purchase:' + id;
        try { if (sessionStorage.getItem(key)) return; sessionStorage.setItem(key, '1'); } catch (e) {}
        var items = <?= json_encode(array_map(static fn(array $i): array => ['item_id' => $i['sku'], 'item_name' => $i['label'], 'price' => (int) $i['unit_price'], 'quantity' => (int) $i['qty']], $order['items'])) ?>;
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ event: 'purchase', ecommerce: { transaction_id: id, value: <?= (int) $order['total_amount'] ?>, currency: 'COP', items: items } });
        if (window.gtag) { gtag('event', 'purchase', { transaction_id: id, value: <?= (int) $order['total_amount'] ?>, currency: 'COP', items: items }); }
        if (window.fbq) { fbq('track', 'Purchase', { value: <?= (int) $order['total_amount'] ?>, currency: 'COP' }); }
      })();
    </script>

  <?php elseif ($waiting): ?>
    <h1 class="display display--sm">Estamos confirmando tu pago</h1>
    <p>No cierres esta página. En cuanto el pago se confirme, aquí aparece tu <?= $isRes ? 'voucher' : 'ticket numerado' ?>.</p>
    <p class="checkout__hint" role="status" data-waiting>Esto puede tardar unos segundos.</p>
    <script>
      (function () {
        var tries = 0;
        var timer = setInterval(function () {
          tries++;
          fetch(<?= json_encode('/pago/' . $order['public_id'] . '/estado') ?>, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (s) { if (s.final) { clearInterval(timer); location.reload(); } })
            .catch(function () {});
          if (tries >= 40) { clearInterval(timer); var el = document.querySelector('[data-waiting]'); if (el) el.textContent = 'Sigue pendiente. Si ya pagaste, escríbenos por WhatsApp y lo revisamos.'; }
        }, 3000);
      })();
    </script>
    <p><a class="btn btn--ghost" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?>Escribir por WhatsApp</a></p>

  <?php else: ?>
    <h1 class="display display--sm">El pago no se aprobó</h1>
    <p>No se hizo ningún cobro por esta compra. Puedes intentarlo de nuevo con el mismo u otro medio de pago.</p>
    <p class="order__actions">
      <a class="btn btn--primary" href="<?= $isRes ? '/reservar/cartagena/' : '/comprar/' ?>"><?= icon('ticket') ?>Intentar de nuevo</a>
      <a class="btn btn--ghost" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?>Necesito ayuda</a>
    </p>
  <?php endif; ?>

  </div>
</main>
<?php partial('footer'); ?>
