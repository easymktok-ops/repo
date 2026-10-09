<?php
/** @var array $orders */
/** @var array $filters */
/** @var string $mailMsg */
partial('admin-top', ['title' => 'Pedidos']);
$labels = ['approved' => 'Pagado', 'pending' => 'Pendiente', 'in_process' => 'En proceso', 'created' => 'Sin pagar', 'rejected' => 'Rechazado', 'cancelled' => 'Cancelado', 'refunded' => 'Reembolsado'];
?>
<main id="contenido" class="adm">
  <h1 class="display display--sm">Pedidos</h1>
  <?php if ($mailMsg === 'enviado'): ?><p class="form-status form-status--ok" role="status">Correo enviado.</p>
  <?php elseif ($mailMsg === 'error'): ?><p class="form-status form-status--error" role="alert">No se pudo enviar el correo. Revisa la configuración de correo.</p>
  <?php elseif ($mailMsg === 'sin-pago'): ?><p class="form-status form-status--error" role="alert">Ese pedido no tiene un pago aprobado.</p><?php endif; ?>
  <form class="adm-filters" method="get" action="/admin/pedidos/">
    <label>Buscar<input name="q" value="<?= e($filters['q']) ?>" placeholder="nombre, correo o documento"></label>
    <label>Estado<select name="estado"><option value="">Todos</option>
      <?php foreach ($labels as $k => $v): ?><option value="<?= e($k) ?>"<?= $filters['estado'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
    <label>Evento<select name="evento"><option value="">Todos</option>
      <?php foreach (['medellin' => 'Medellín', 'cartagena' => 'Cartagena'] as $k => $v): ?><option value="<?= e($k) ?>"<?= $filters['evento'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
    <label>Fecha<input type="date" name="fecha" value="<?= e((string) $filters['fecha']) ?>"></label>
    <button class="btn btn--primary btn--sm" type="submit">Filtrar</button>
  </form>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Código</th><th>Estado</th><th>Evento</th><th>Fecha</th><th>Nombre</th><th>Correo</th><th>Total</th><th>Creado</th><th>Correo</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= $o['ticket_number'] ? e(Tickets::code((int) $o['ticket_number'], (string) $o['kind'])) : '—' ?></td>
          <td><?= e($labels[$o['status']] ?? $o['status']) ?></td>
          <td><?= e($o['event_id']) ?></td>
          <td><?= e($o['function_date']) ?></td>
          <td><?= e($o['buyer_name']) ?></td>
          <td><?= e($o['email']) ?></td>
          <td><?= e(format_cop((int) $o['total_amount'])) ?></td>
          <td><?= e($o['created_at']) ?></td>
          <td><?php if ($o['status'] === 'approved'): ?>
            <form method="post" action="/admin/reenviar/" style="margin:0"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
              <?= $o['email_sent_at'] ? 'Enviado' : 'Pendiente' ?> · <button class="adm-link" type="submit"><?= $o['email_sent_at'] ? 'Reenviar' : 'Enviar' ?></button></form>
          <?php else: ?>—<?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?><tr><td colspan="9">No hay pedidos con esos filtros.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <p class="checkout__hint">Se muestran los últimos 300.</p>
</main>
</body></html>
