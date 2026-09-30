<?php
declare(strict_types=1);

// Genera una versión estática del home para el demo de revisión (enlace privado).
// Uso: php tools/build-demo.php <carpeta-destino>
// El sitio real sigue siendo el PHP de public/; esto solo es una foto navegable.

$out = rtrim($argv[1] ?? '', '/');
if ($out === '') {
    fwrite(STDERR, "Uso: php tools/build-demo.php <carpeta-destino>\n");
    exit(1);
}

$root = dirname(__DIR__);
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
require $root . '/public/index.php';
$html = (string) ob_get_clean();

// El visor de artifacts pone su propio doctype, head y body.
$html = preg_replace('~<!doctype html>\s*<html[^>]*>\s*<head>\s*~i', '', $html);
$html = preg_replace('~<meta charset="utf-8">\s*<meta name="viewport"[^>]*>\s*~i', '', $html);
$html = preg_replace('~</head>\s*<body[^>]*>~i', '', $html);
$html = preg_replace('~</body>\s*</html>\s*$~i', '', $html);

// Rutas de archivos relativas a la página publicada.
$html = preg_replace('~(href|src)="/assets/([^"?]+)(\?v=\d+)?"~', '$1="assets/$2"', $html);

// Enlaces internos: anclas del home se mantienen; páginas aún no construidas llevan a la programación.
$html = preg_replace('~href="/#([a-z-]+)"~', 'href="#$1"', $html);
$html = str_replace('href="/politica-de-datos/"', 'href="#contacto"', $html);
$html = preg_replace('~href="/(comprar|medellin|bogota|cartagena)/[^"]*"~', 'href="#programacion"', $html);
$html = str_replace('href="/"', 'href="#contenido"', $html);

// El formulario no puede enviar desde el demo.
$html = str_replace('action="/contacto"', 'action="#contacto"', $html);

$html .= <<<'HTML'
<p class="demo-note" role="note">Demo de revisión · fotos, video y textos SEO provisionales</p>
<style>
  .demo-note {
    position: fixed;
    left: 1rem;
    bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
    z-index: 150;
    max-width: calc(100% - 6rem);
    margin: 0;
    padding: 0.45rem 0.8rem;
    border: 1px solid var(--jc-line);
    border-radius: 999px;
    background: rgb(11 10 12 / 0.9);
    color: var(--jc-ink);
    font: 500 0.6875rem/1.3 var(--font-body);
    letter-spacing: 0.04em;
  }
  .demo-toast { margin: 0; }
</style>
<script>
  document.querySelectorAll('[data-contact-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      let note = form.querySelector('.demo-toast');
      if (!note) {
        note = document.createElement('p');
        note.className = 'form-status form-status--ok demo-toast';
        note.setAttribute('role', 'status');
        form.prepend(note);
      }
      note.textContent = 'En el demo el formulario no envía. En el sitio real guarda la solicitud y valida cada campo.';
    });
  });
</script>
HTML;

if (!is_dir($out) && !mkdir($out, 0775, true)) {
    fwrite(STDERR, "No se pudo crear $out\n");
    exit(1);
}
file_put_contents($out . '/index.html', ltrim($html));

$copy = static function (string $from, string $to) use (&$copy): void {
    if (is_dir($from)) {
        if (!is_dir($to)) {
            mkdir($to, 0775, true);
        }
        foreach (scandir($from) as $item) {
            if ($item !== '.' && $item !== '..') {
                $copy("$from/$item", "$to/$item");
            }
        }
        return;
    }
    copy($from, $to);
};
$copy($root . '/public/assets', $out . '/assets');

echo "Demo generado en $out\n";
