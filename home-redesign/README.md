# Home Easy Marketing — rediseño (from scratch)

Nueva home de `easymarketing.mx`, construida desde cero con el contenido real del
sitio actual y la disciplina visual de Noomo Agency (paleta restringida, motion con
propósito, revelado progresivo por scroll), pero en tecnología ligera: **HTML + CSS +
SVG**, sin Three.js/WebGL, sin frameworks, sin video.

## Archivos

| Archivo | Qué es |
|---|---|
| `index.html` | **El entregable.** Página autónoma (~450 KB): todos los logos y la foto están incrustados en base64. Se puede subir tal cual y funciona sin la carpeta `images/`. |
| `home_src.html` | Plantilla fuente con tokens `__ASSET__` (código limpio, sin base64). |
| `build.py` | Reduce/optimiza las imágenes y las incrusta en la plantilla para generar `index.html`. |

## Regenerar `index.html`

Requiere Python 3 y Pillow (`pip install Pillow`). Ajusta la ruta `IMG` en `build.py`
hacia tu carpeta `images/` y ejecuta:

```bash
python3 build.py
```

## Decisiones de diseño

- **Mensaje central:** generación de demanda con IA (la IA es el núcleo del "motor";
  Ads, contenido y SEO son las piezas que lo alimentan).
- **Paleta disciplinada (lección Noomo):** un solo protagonista, índigo `#411ae7`,
  sobre near-black índigo-tintado y blanco. Sin acentos extra.
- **Tipografía:** Montserrat + Open Sans (identidad de marca ya existente), vía Google
  Fonts con fallback al sistema.
- **CTAs:** principal a Calendly (`calendly.com/easymktok/asesoria`), secundario a
  WhatsApp (`wa.me/525539787305`). Se enlaza a Calendly en vez de incrustar su widget,
  para no cargar scripts externos pesados (importa en conexiones móviles).
- **Rendimiento y accesibilidad:** una sola dependencia externa (la fuente), motion en
  CSS/SVG, `prefers-reduced-motion` respetado, revelados que realzan contenido ya
  visible (no lo ocultan si falla el JS), 0px de scroll horizontal en 390/768/1440.
- **SEO/GEO:** el mensaje va como texto real (no dentro de gráficos), con JSON-LD de
  `Organization` y `FAQPage`.

## Notas de integración

- Enlaces internos (`Servicios.html`, `Contact.html`) apuntan a las páginas actuales
  del sitio; ajústalos si cambian de ruta.
- La foto de Norman se reescaló a 360×360 para peso; el original está en `images/`.
