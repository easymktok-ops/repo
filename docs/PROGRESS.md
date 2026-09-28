# PROGRESS — Joyas Colombianas® Dinner & Show

## Corrección de rumbo (2026-09-28)
Sesión previa confundió el alcance: se trató el repo como si tuviera que auditarse tal cual, y se le dio demasiado peso a assets sueltos (`hero-valle.jpg`, `galeria-01..07.jpg`, `ocasion-aniversario/cumpleanos/pedida.jpg`) que **Norman confirmó que NO son de este proyecto** — se ignoran. También se abrió una propuesta de arquitectura basada en "child theme / tema propio" (WordPress) que queda **obsoleta**: Norman prefiere evitar WordPress. Todo lo de abajo refleja el alcance correcto: el brief completo dado al inicio del chat (sitio transaccional Joyas Colombianas® Dinner & Show), sin WordPress.

## Hecho
- Auditoría inicial del repo: confirma que no trae stack de WordPress ni código de sitio (solo un CLI npm sin relación, `markitdown-cli`, y los assets genéricos ya descartados arriba).
- `hero-loop.mp4` se había tomado como placeholder de hero (1920×1080, 10.1s, 6.9MB) — **pendiente de confirmar con Norman** si es válido para este proyecto o si también hay que descartarlo junto con el resto de esos assets sueltos.
- Prototipo aislado del hero (`prototipo/hero.html`): video diferido (poster primero para LCP), overlay de contraste, CTA con fade-in y feedback táctil, `prefers-reduced-motion` respetado, sin autoplay en conexión lenta/Save-Data. Copy en placeholders `{{...}}`. Construido para ser independiente de la plataforma final, así que sigue sirviendo aunque cambie el stack.
- Decisión de plataforma: **sin WordPress**, sitio a medida (confirmado por Norman).

## Bloqueado (esperando a Norman / cliente)
- **¿`hero-loop.mp4` es válido para este proyecto?** — pendiente respuesta directa.
- **Pasarela de pago** — por definir (el brief inicial mencionaba Mercado Pago como oficial y Wompi/Stripe descartadas, pero Norman lo dejó abierto de nuevo — no asumir hasta confirmación).
- **Pantallazos y URLs de referencia del sitio actual de la clienta** — necesarios para `docs/DESIGN-TOKENS.md` antes de tocar CSS definitivo.
- **Stack técnico concreto sin WordPress** — falta decidir cómo se resuelve el checkout/webhook/ticket/door list sin WP (ver propuesta abajo, pendiente de aprobación).
- **Confirmación de video horizontal del cliente** — el material bruto de Drive revisado hasta ahora es casi todo vertical (MVI_0006..0050.MOV).
- **Contenido de John** (copy) — no entregado; todo placeholder `{{...}}`.
- **Páginas de llamada con la clienta** (Cartagena/Bogotá/Medellín) — cuáles entran en este alcance, pendiente de Norman.

## Siguiente
1. Confirmar con Norman si `hero-loop.mp4` sirve o se descarta.
2. Aprobar el stack técnico sin WordPress (propuesta abajo).
3. Recibir pantallazos del sitio actual → auditoría `/impeccable` → `docs/DESIGN-TOKENS.md` → diagnóstico → aprobación.
4. Definir pasarela de pago para diseñar el adaptador de pagos.

## Fuera de alcance / cotizar aparte
_(vacío por ahora)_

---

## Propuesta de stack técnico sin WordPress (pendiente de aprobación)

Dado que el checkout, webhook de pago, ticket numerado atómico y door list exigen lógica de servidor real (no solo contenido editable), y que el hosting es Hostinger básico (compartido, sin proceso Node persistente), la opción más simple y acorde al alcance es:

- **Sitio a medida en PHP** (HTML/CSS/JS en el front, PHP en el back) — corre nativo en cualquier plan de Hostinger, sin paquetes ni servicios adicionales.
- **Base de datos**: SQLite o MySQL (lo que ya incluya el hosting) para tickets, reservas y door list.
- **Panel de administración propio y mínimo**: solo los campos que la clienta necesita tocar (títulos, fechas de función, precios, textos de landings, media), sin plugins ni bloat de un CMS genérico.
- **Capa de pagos detrás de un adaptador**, como ya pedía el brief, para poder cambiar de pasarela sin reescribir.

Alternativa si prefieren no construir el panel de admin desde cero: **Grav CMS** (PHP, flat-file, sin base de datos, gratis, mucho más liviano que WordPress) — da un panel de edición ya armado a cambio de menos control fino.

**Pendiente de Norman:** aprobar uno de los dos caminos para empezar a construir sobre una base real.
