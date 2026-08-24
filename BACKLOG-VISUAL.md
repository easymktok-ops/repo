# Backlog visual — Aerodiverti

Cambios de diseño/UI acumulados por el cliente. **No se implementan hasta
autorización explícita** ("dale a los cambios"). Se aplican todos juntos.

Estado: `PEND` = pendiente · `LISTO` = aplicado · `DUDA` = requiere decisión/asset.

---

## Dirección de marca (transversal)

La marca de Aerodiverti es **fucsia/magenta** (del logo y el favicon). Hoy el
sitio usa un solo acento **aqua** ("cielo alto"). El cliente quiere mover el
acento de acción hacia el **fucsia de marca**.

> **DECISIÓN A CONFIRMAR (VIS-00):** ¿el acento cambia **por completo** a fucsia,
> o el fucsia es para **acciones/CTAs/outlines** y el aqua se conserva en algún
> rol secundario? Afecta a `tokens.css` y a la regla "un solo acento" de
> DESIGN.md/CLAUDE.md. Definir al implementar (yo propongo opciones con muestras).

---

## 1. Color y marca

- **VIS-01 · CTAs en fucsia** `PEND`
  Los botones de acción ("Reservar vuelo" del hero y del rail, "Elegir fecha",
  "Ir a pagar", etc.) deben ir en el **fucsia de marca**, no en el aqua actual.
  Ref: captura del home (recuadros rosas sobre los dos "Reservar vuelo").

- **VIS-02 · Favicon** `DUDA(asset)`
  Reemplazar el favicon actual por el **fucsia** que pasó el cliente (replicarlo).
  _Necesito el archivo_ (SVG o PNG a buena resolución) o lo tomo del Drive.

- **VIS-03 · Logo en el menú** `DUDA(asset)`
  Reemplazar el texto "Aerodiverti" del rail lateral por el **logotipo** (script
  "Aerodiverti / vuela y descubre"). _Necesito el archivo_ (SVG preferido).
  Contemplar variante legible sobre fondo oscuro.

- **VIS-04 · Favoritos en fucsia** `PEND`
  El corazón de favoritos (hoy aqua) al **fucsia de marca**. Aplica en: tarjetas
  del catálogo, botón guardar, página `/favoritos` y el icono del pin.

- **VIS-05 · Outlines de Reservas en fucsia** `PEND`
  En el flujo `/reservar`, **todos los outlines/bordes** en fucsia (bordes de
  inputs, tarjetas de paquete y de modo de pago, anillos de foco, etc.).

## 2. Botones / espaciado

- **VIS-06 · Padding de "Elegir fecha"** `PEND`
  El botón "Elegir fecha" (catálogo) está muy pegado al borde; ampliar el
  padding interno para que respire. Revisar también consistencia con los demás
  botones. Ref: captura del botón.

## 3. Hero (home) — "la idea está bien, la ejecución falló"

- **VIS-07 · Parallax roto** `PEND`
  El parallax del hero no funciona. Arreglarlo (o sustituir por un efecto que sí
  se sienta bien y respete `prefers-reduced-motion`).

- **VIS-08 · Quitar marca de agua** `PEND`
  Eliminar la marca de agua de "Teotihuacán" que se ve sobre el hero.

- **VIS-09 · Probar fondo claro (blanco)** `DUDA(UX)`
  Explorar un hero con **fondo blanco** en vez de negro. **Nota del cliente:**
  "si ves que rompe y va en contra de las reglas de UX, no lo hagas, cuestiona".
  → Al implementar: prototipar ambas y evaluar críticamente contraste/jerarquía y
  el impacto en la identidad (el oscuro da el aire cinematográfico y hace resaltar
  la foto del amanecer). Recomendar con evidencia, no aplicar a ciegas.

## 4. Fotos / contenido

- **VIS-10 · Globopuerto: globos en tierra** `DUDA(asset)`
  Hoy Globopuerto usa fotos del globo **en vuelo**; el cliente prefiere fotos del
  globo **en tierra** (mayormente). Hay más fotos en el **Drive**. _Buscar y
  seleccionar_ las de globo en tierra.
  Drive: https://drive.google.com/drive/folders/1jJKAUhgot46Uu0gC7oIrjn-v0kapxeKV

---

## Assets que necesito para implementar

- [ ] **Logo** (script "Aerodiverti / vuela y descubre") en **SVG** (o PNG grande).
- [ ] **Favicon** fucsia en **SVG/PNG**.
- [ ] **Fotos de globo en tierra** para Globopuerto (del Drive).
- [ ] **Hex/OKLCH exacto del fucsia** de marca (lo extraigo del logo/favicon).

_Con estos assets, VIS-02/03/10 quedan desbloqueados._
