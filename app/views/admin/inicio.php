<?php
/** @var array $summary */
partial('admin-top', ['title' => 'Resumen']);
?>
<main id="contenido" class="adm">
  <h1 class="display display--sm">Resumen</h1>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Evento</th><th>Fecha</th><th>Pedidos pagados</th><th>Personas (incl. en pago)</th><th>Cupos</th><th>Recaudo</th><th>Descargas</th></tr></thead>
      <tbody>
      <?php foreach ($summary as $r): $ev = $r['event']; $res = Orders::isReservation($ev); ?>
        <tr>
          <td><?= e($ev['city']) ?></td>
          <td><?= e($r['date']['label']) ?></td>
          <td><?= $r['orders'] ?></td>
          <td><?= $r['people'] ?></td>
          <td><?= isset($ev['capacity']) ? (int) Orders::seatsLeft($ev, $r['date']['value']) . ' libres de ' . (int) $ev['capacity'] : '—' ?></td>
          <td><?= e(format_cop($r['revenue'])) ?></td>
          <td><a href="/admin/exportar/<?= $res ? 'reservas' : 'puerta' ?>/?fecha=<?= e($r['date']['value']) ?>"><?= $res ? 'Lista de reservas' : 'Lista de la puerta' ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <h2 class="adm-h2">Reporte para la contadora</h2>
  <form class="adm-filters" method="get" action="/admin/exportar/contador/">
    <label>Desde (fecha de pago)<input type="date" name="desde"></label>
    <label>Hasta<input type="date" name="hasta"></label>
    <label>Evento<select name="evento"><option value="">Todos</option><option value="medellin">Medellín</option><option value="cartagena">Cartagena</option></select></label>
    <button class="btn btn--primary btn--sm" type="submit">Descargar CSV</button>
  </form>
  <p class="checkout__hint">Cada fila es una venta pagada, con comisión y neto cuando la pasarela los informa. Se abre directo en Excel.</p>
</main>
</body></html>
