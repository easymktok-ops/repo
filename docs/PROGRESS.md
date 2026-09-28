# PROGRESS — Joyas Colombianas® Dinner & Show

## Corrección de rumbo (2026-09-28)
Sesión previa confundió el alcance: se trató el repo como si tuviera que auditarse tal cual, y se le dio demasiado peso a assets sueltos (`hero-valle.jpg`, `galeria-01..07.jpg`, `ocasion-aniversario/cumpleanos/pedida.jpg`) que **Norman confirmó que NO son de este proyecto** — se ignoran. También se abrió una propuesta de arquitectura basada en "child theme / tema propio" (WordPress) que queda **obsoleta**: Norman prefiere evitar WordPress. Todo lo de abajo refleja el alcance correcto: el brief completo dado al inicio del chat (sitio transaccional Joyas Colombianas® Dinner & Show), sin WordPress.

## Hecho
- Auditoría inicial del repo: confirma que no trae stack de WordPress ni código de sitio (solo un CLI npm sin relación, `markitdown-cli`, y los assets genéricos ya descartados arriba).
- `hero-loop.mp4` **descartado** — Norman confirmó que no es de este proyecto, él provee el video real. Se eliminó del repo y del prototipo (`prototipo/hero.html` ahora solo muestra el poster de placeholder hasta que llegue el archivo).
- Prototipo aislado del hero (`prototipo/hero.html`): video diferido (poster primero para LCP), overlay de contraste, CTA con fade-in y feedback táctil, `prefers-reduced-motion` respetado, sin autoplay en conexión lenta/Save-Data. Copy en placeholders `{{...}}`. Construido para ser independiente de la plataforma final, así que sigue sirviendo aunque cambie el stack.
- Decisión de plataforma: **sin WordPress**, sitio a medida (confirmado por Norman).
- Decisión de stack: **PHP a medida + panel de administración propio** (confirmado por Norman, sobre Grav CMS). Ver detalle en la propuesta al final de este archivo — deja de ser propuesta, es la línea base.

## Bloqueado (esperando a Norman / cliente)
- **Video real del hero** — Norman lo va a proporcionar (pendiente de recibir el archivo).
- **Pasarela de pago** — por definir (el brief inicial mencionaba Mercado Pago como oficial y Wompi/Stripe descartadas, pero Norman lo dejó abierto de nuevo — no asumir hasta confirmación).
- **Pantallazos y URLs de referencia del sitio actual de la clienta** — necesarios para `docs/DESIGN-TOKENS.md` antes de tocar CSS definitivo.
- **Confirmación de video horizontal del cliente** — el material bruto de Drive revisado hasta ahora es casi todo vertical (MVI_0006..0050.MOV).
- **Contenido de John** (copy) — no entregado; todo placeholder `{{...}}`.
- **Páginas de llamada con la clienta** (Cartagena/Bogotá/Medellín) — cuáles entran en este alcance, pendiente de Norman.

## Siguiente
1. Definir estructura de datos y campos del panel de administración (qué edita la clienta exactamente) y arrancar el esqueleto PHP.
2. Recibir el video real del hero de Norman.
3. Recibir pantallazos del sitio actual → auditoría `/impeccable` → `docs/DESIGN-TOKENS.md` → diagnóstico → aprobación.
4. Definir pasarela de pago para diseñar el adaptador de pagos.
5. Confirmar acceso a hosting de Hostinger (versión de PHP disponible, credenciales, staging) para validar compatibilidad del stack.

## Fuera de alcance / cotizar aparte
_(vacío por ahora)_

---

## Stack técnico confirmado: PHP a medida + admin propio

Decisión tomada (2026-09-28) sobre las dos opciones evaluadas (admin propio vs. Grav CMS — investigación con fuentes en el chat). Se descartó Grav: no reduce el trabajo de la parte que importa (checkout/webhook/ticket/door list, que es 100% a medida de todas formas) y suma una segunda pieza a mantener/actualizar separada del motor transaccional.

- **Sitio a medida en PHP** (HTML/CSS/JS en el front, PHP en el back) — corre nativo en cualquier plan de Hostinger, sin paquetes ni servicios adicionales.
- **Base de datos**: SQLite o MySQL (lo que ya incluya el hosting) para tickets, reservas y door list.
- **Panel de administración propio y mínimo**: solo los campos que la clienta necesita tocar (títulos, fechas de función, precios, textos de landings, media), sin plugins ni bloat de un CMS genérico.
- **Capa de pagos detrás de un adaptador**, como ya pedía el brief, para poder cambiar de pasarela sin reescribir.

**Siguiente paso técnico:** definir el esquema de datos (tablas/campos) y los campos exactos del panel antes de escribir código de backend.
