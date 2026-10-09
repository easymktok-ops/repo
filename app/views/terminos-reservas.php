<?php
partial('head', ['page' => [
    'title'      => 'Términos de las reservas en Cartagena | ' . content('site.name'),
    'path'       => '/terminos-reservas/',
    'indexable'  => false,
    'body_class' => 'page-stub',
]]);
partial('header');
?>
<main id="contenido" class="stub">
  <div class="container stub__inner">
    <h1 class="display display--sm">Términos de las reservas en Cartagena</h1>
    <ul>
      <li>La reserva se hace en La Oveja, Cartagena, para jueves, viernes y sábados.</li>
      <li>Reservar cuesta un cover de USD 5 por persona.</li>
      <li>Cada reserva es una mesa de máximo 6 personas.</li>
      <li>Reserva solo la cantidad de personas que realmente van a asistir, porque así se organiza la mesa.</li>
      <li>Si cancelas, el cover no se devuelve: al reservar se deja de vender una mesa en el evento para darte prioridad.</li>
      <li>El cobro se hace en pesos colombianos, a la tasa de referencia que se muestra antes de pagar.</li>
    </ul>
    <p class="checkout__hint">Texto provisional basado en las condiciones indicadas por la clienta. Pendiente de revisión legal.</p>
    <a class="btn btn--primary" href="/reservar/cartagena/">Reservar</a>
  </div>
</main>
<?php partial('footer'); ?>
