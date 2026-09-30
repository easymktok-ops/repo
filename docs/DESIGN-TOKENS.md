# DESIGN TOKENS — Joyas Colombianas® Dinner & Show

Fuente: pantallazo completo del sitio actual (joyascolombianasdinnershow.com, 2026-09-30). El dominio está bloqueado por la red de esta sesión, así que colores y tipografías son **aproximaciones visuales**: confirmarlos contra el CSS real cuando haya acceso (o que Norman los copie del tema actual). Los tokens viven en `public/assets/css/main.css` (`:root`).

## Identidad que NO cambia

| Token | Valor | Uso en el sitio actual |
|---|---|---|
| `--jc-black` | `#0b0a0c` + textura de estrellas | Fondo general de todas las secciones oscuras |
| `--jc-magenta` | `#d81b60` | Botones, paneles de texto, footer |
| `--jc-magenta-deep` | `#9c0f45` | Final de los degradados magenta |
| `--jc-rose` | `#f06292` | Bordes de tarjetas y campos, iconos |
| `--jc-gold` | `#f2c14e` | Acentos mínimos (flores de las bandas, foco) |
| Tipografía títulos | Cinzel, mayúsculas, tracking 0.2–0.22em | "Una explosión de ritmos…", títulos de sección |
| Tipografía texto | Montserrat 400/500/600 | Párrafos, botones, formulario |
| Logo | Script "Joyas Colombianas®" + "Dinner & Show" + ciudades | Hero y footer (hoy con fuente de respaldo Great Vibes hasta tener el archivo real) |
| Formas | Botones píldora, tarjetas y campos con borde rosa y radio suave | Programación, formulario |
| Imagen | Fotografía de escena, color saturado, bailarines con trajes típicos | Bandas y paneles |

Contraste verificado: blanco sobre `#d81b60` = 4.95:1 (AA texto normal). Blanco al 80% sobre negro ≈ 12:1.

## Diagnóstico

**Se conserva**
- Paleta negro estrellado + magenta, orden de secciones, títulos con tracking ancho, paneles magenta con foto al lado, tarjetas de programación con borde rosa, formulario, footer magenta.
- Textos actuales (tagline, títulos, programación, campos del formulario).

**Se mejora**
- Jerarquía: tagline y títulos más grandes y con interlineado; el texto de los paneles pasa de ~12px a 17px con buen contraste (hoy es difícil de leer sobre magenta).
- Formulario: etiquetas visibles encima de cada campo (hoy solo placeholder), errores claros por campo, foco visible.
- Tarjetas de programación: horario, puertas, lugar, dress code y precios visibles de un vistazo, con iconos propios y precios en formato boleta.
- Navegación: menú fijo con acceso directo a compra (hoy no hay menú visible) y menú móvil a pantalla completa.
- Movimiento: parallax por capas (estrellas, cintas, flores, fotos) ligado al scroll, profundidad con el puntero en el hero, sin afectar el flujo de compra y desactivado con `prefers-reduced-motion`.
- Rendimiento: fuentes locales con preload, imágenes con tamaño fijo y lazy loading, sin librerías.

**Se descarta**
- El segundo botón del hero "Compra entradas COP" (duplicaba al primero). Confirmar con la clienta si era para otra moneda.
- Crédito de agencia en el footer (regla del proyecto).
- Selector de idioma (bandera ES): vuelve cuando se decida si habrá versión en inglés.

## Landings de campaña (semana 2)
Mismos tokens. Enfoque 100% conversión (UX/UI directo: dónde, cuándo, cuánto, botón de compra), noindex, sin prioridad SEO. El home sí es full SEO.
