# Imágenes pendientes (carpeta public/assets/img/)

El sitio muestra un recuadro "Falta: assets/img/…" hasta que el archivo exista. Subir con **estos nombres exactos**. Si además se sube la versión `.webp` o `.avif` con el mismo nombre base, se sirve automáticamente.

| Archivo | Tamaño mínimo | Sección |
|---|---|---|
| `logo.svg` (o `logo.png` blanco transparente) | 560×200 | Cabecera, hero y footer |
| `hero-poster.jpg` (+ `hero-poster.webp`) | 1920×1080 | Fondo del hero; también es la imagen al compartir en redes |
| `show.jpg` | 1600×900 | Marco sobre la frase principal |
| `experiencia.jpg` | 1200×1000 | Panel "Dinner & Show Joyas Colombianas®" |
| `banda.jpg` | 1920×900 | Banda con la bandera |
| `que-es.jpg` | 1200×1000 | Panel "¿Qué es un Dinner Show?" |
| `escenario.jpg` | 1920×900 | Banda del escenario |
| `contacto.jpg` | 1000×1250 | Junto al formulario |
| `favicon.png` | 512×512 | Pestaña del navegador (opcional, hay un SVG provisional) |

Peso objetivo: menos de 250 KB por foto en WebP. Al subir cada foto, revisar que su texto alternativo en `app/views/home.php` describa lo que realmente se ve.

Video del hero: MP4 H.264 sin audio, 1920×1080, 8–15 s, menos de 6 MB. Se configura en `hero.video_src` (contenido).
