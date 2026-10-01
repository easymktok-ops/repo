# Fotos, logo y video

**Cómo se suben:** los originales van en `assets-src/` (rama `claude/wonderful-allen-qhmfe5`). Nunca en `public/`: de ahí se publica todo y los originales pesan mucho. Yo los recorto a la proporción de cada espacio y los optimizo con `python3 tools/optimize-images.py`, que deja un JPG y un WebP en `public/assets/img/`.

## Estado de cada espacio

| Espacio | Proporción final | Estado | Origen |
|---|---|---|---|
| Logo | 869×170 (5:1) | Listo | `assets-src/logo.png` (el archivo era WebP con extensión .png; ya está convertido a PNG real) |
| Hero (fondo) | Cubre pantalla completa | **Provisional**: la foto mide 863 px de ancho y se nota borrosa en pantallas grandes. Hace falta la original, mínimo 1920×1080 | `assets-src/hero-poster.png` |
| Banda con foto grupal | 1920×900 | Listo (recortada de 6000×4000, 19 MB → 307 KB) | `assets-src/banda.jpg` |
| Junto al formulario | 1000×1250 (4:5, vertical) | Listo | `assets-src/escenario.jpeg` |
| Marco sobre la frase principal (`show.jpg`) | 1600×900 | Falta | |
| Panel "Dinner & Show" (`experiencia.jpg`) | 1200×1000 | Falta | |
| Panel "¿Qué es un Dinner Show?" (`que-es.jpg`) | 1200×1000 | Falta | |
| Banda del escenario (`escenario.jpg`) | 1920×900 | Falta | |
| Video del hero | MP4, 1920×1080, sin audio, 8 a 15 s, menos de 6 MB | **No ha llegado**. Subir como `public/assets/img/hero.mp4`; se detecta solo | |

## Para agregar más fotos
1. Subir el original a `assets-src/` con cualquier nombre.
2. Avisarme en qué espacio va (o editar `SLOTS` en `tools/optimize-images.py`).
3. Para el hero conviene una foto horizontal, con los bailarines al centro y espacio libre arriba.

## Pendientes de revisar con la clienta
- La foto grupal de la banda muestra invitados, entre ellos un menor. Confirmar que hay autorización para publicarla en el sitio.
- El hero actual del sitio de la clienta (bailarines con fondo rojo) no es la foto que llegó. Pedir esa original.
