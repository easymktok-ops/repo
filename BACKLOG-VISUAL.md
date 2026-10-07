# Backlog visual — Aerodiverti

Cambios de diseño/UI acumulados por el cliente. Se implementaron en bloque el
**2026-08-25** ("dale, ataquemos el backlog visual").

Estado: `PEND` = pendiente · `LISTO` = aplicado · `DUDA` = requiere decisión/asset.

---

## Resumen de implementación (2026-08-25)

| Item | Qué | Estado |
| --- | --- | --- |
| VIS-00 | Acento de marca aqua -> fucsia | **LISTO** |
| VIS-01 | CTAs en fucsia | **LISTO** |
| VIS-02 | Favicon fucsia bold | **LISTO** |
| VIS-03 | Logo en el menú | **LISTO** (wordmark tratado para oscuro) |
| VIS-04 | Favoritos en fucsia | **LISTO** |
| VIS-05 | Outlines de reservas en fucsia | **LISTO** |
| VIS-06 | Padding de "Elegir fecha" | **LISTO** |
| VIS-07 | Parallax del hero | **LISTO** (se implementó de cero) |
| VIS-08 | Quitar marca de agua "Teotihuacán" | **LISTO** |
| VIS-09 | Probar fondo claro | **RESUELTO**: se mantiene oscuro (decisión del cliente) |
| VIS-10 | Globopuerto: globos en tierra | **LISTO** (fotos en tierra del Drive) |
| VIS-11 | Feedback al elegir vuelo (auto-scroll) | **LISTO** |
| VIS-12 | Parallax doble exposición (foto fija) | **LISTO** |
| VIS-13 | Homologar el nuevo logo en el flujo de reservas | **LISTO** (código; falta subir logo en Stripe) |
| VIS-14 | Paneles backend responsive (sin scroll horizontal) | **LISTO** |
| VIS-15 | Favicon administrativo (azul/gris) para paneles backend | **LISTO** |
| VIS-16 | Auto-scroll al botón Continuar tras elegir fecha (reservas) | PEND |
| BO-01 | Cupo por día (lugares, vendidos, libres) | PEND |
| BO-02 | Abordaje del día (lista, QR, presentes, CSV) | PEND |
| BO-03 | Laboratorio: embudo de abandono y ventas reales | PEND |
| BO-04 | Editor de paquetes más digerible en /admin | PEND |
| BO-05 | Experimentos A/B con lectura honesta | PEND |
| CON-01 | Brisa como WhatsApp principal y primera asesora | PEND |
| SEO-01 | Search Console: páginas "No se ha encontrado (404)" | PEND (falta lista de URLs) |

Todo el cambio de color es sistémico: sale de un solo token `--accent` en
`src/styles/tokens.css`. No hay fucsia hardcodeado en componentes.

---

## Dirección de marca (transversal) — RESUELTO (VIS-00)

**Decisión aplicada:** el acento único pasa **por completo** de aqua a **fucsia
de marca**. Se conserva la regla "un solo acento" de DESIGN.md/CLAUDE.md; solo
cambia el matiz. El aqua "cielo alto" queda retirado.

El fucsia de marca puro del logo es `#B4438C` = `oklch(0.56 0.167 344)`. Ese tono
es demasiado oscuro para leer como **texto** sobre el fondo Obsidiana (L 0.17).
El token quedó afinado a `oklch(0.74 0.145 344)` (mismo matiz, más claro) para
pasar AA en sus dos papeles:

- como texto/icono/borde sobre `--bg`: **~7.7:1** (AA cuerpo, AAA large)
- como fondo de botón con `--accent-ink` (tinta oscura) encima: **~7.7:1**

## Paleta de pinceladas (acuarela del globo)

Añadida a `tokens.css` como `--brush-*` (OKLCH), documentada **solo para
micro-detalle** (bullets/puntos), nunca como color de UI. Aún **no** se cablea a
ningún componente: se hará en un punto concreto y con criterio para no romper la
disciplina de un solo acento. Si quieres, dime en qué lista/bloque la aplico.

- lima `oklch(0.852 0.173 128)` · verde `oklch(0.862 0.121 139)` ·
  amarillo `oklch(0.866 0.152 113)` · naranja `oklch(0.777 0.117 66)` ·
  coral `oklch(0.793 0.09 24)` · rosa `oklch(0.799 0.109 344)` ·
  cian `oklch(0.898 0.081 193)`

---

## 1. Color y marca

- **VIS-01 · CTAs en fucsia** `LISTO`
  Todos los botones de acción (hero, rail, "Elegir fecha", "Ir a pagar",
  "Continuar", etc.) usan `--accent`, ahora fucsia. Cambio de un solo token.

- **VIS-02 · Favicon** `LISTO`
  Rediseñado en `public/favicon.svg`: silueta de globo **rellena en fucsia**
  (con gajos y canasta), legible y con carácter a 16px. Sustituye al globo de
  trazo fino aqua que se perdía en pequeño.

- **VIS-03 · Logo en el menú** `LISTO`
  El logo original es line-art negro + wordmark fucsia sobre **blanco** (para
  fondo claro). Como mantenemos el tema oscuro (VIS-09), extraje el **wordmark
  "Aerodiverti"** del logo, lo aislé del fondo (transparente) y lo normalicé al
  **fucsia de marca** del sitio. Reemplaza el texto en el rail (desktop) y en la
  barra superior (móvil). Assets: `src/assets/brand/aerodiverti-wordmark-dark.png`
  (usado en el sitio vía Astro Image) y `public/aerodiverti-wordmark.svg` (SVG
  drop-in, envuelve el PNG). Nota honesta: un SVG **vectorial puro** requiere el
  archivo fuente del diseñador (.ai/.eps/PDF vectorial); el actual es la obra
  real tratada a alta resolución, nítida al tamaño del menú y bastante más.

- **VIS-04 · Favoritos en fucsia** `LISTO`
  El corazón "guardado" usa `--accent`: catálogo, `/favoritos` y pin, todos en
  fucsia automáticamente.

- **VIS-05 · Outlines de Reservas en fucsia** `LISTO`
  Bordes de inputs, tarjetas de paquete/modo de pago, anillos de foco y el
  stepper: todo deriva de `--accent`. Ya en fucsia.

## 2. Botones / espaciado

- **VIS-06 · Padding de "Elegir fecha"** `LISTO`
  `.card-cta` pasa de `0.6rem 1rem` a `0.72rem 1.25rem` (+ gap). Respira y queda
  consistente con los demás botones pill.

## 3. Hero (home)

- **VIS-07 · Parallax** `LISTO`
  No había parallax real. Se añadió uno sutil: la imagen deriva más lento que el
  scroll (rAF + IntersectionObserver, solo mientras el hero se ve). Escala base
  1.12 para que el desplazamiento no muestre bordes. Respeta
  `prefers-reduced-motion` (sin movimiento, queda estático).

- **VIS-08 · Quitar marca de agua** `LISTO`
  Eliminada la palabra gigante y tenue **"TEOTIHUACÁN"** (`.de-word`,
  opacity 0.05) de la sección de doble exposición, justo bajo el hero. Era el
  único elemento tipo watermark del sitio. Si te referías a otra cosa concreta,
  dime y la ajusto.

- **VIS-09 · Probar fondo claro (blanco)** `RESUELTO: se mantiene oscuro`
  Decisión del cliente: **el sitio se queda en oscuro (Obsidiana)**. Coincide con
  la regla dura de CLAUDE.md y con que la foto/video del amanecer y el fucsia de
  marca rinden mejor sobre negro. El logo se resolvió con tratamiento sobre
  oscuro (VIS-03), sin necesidad de volcar el tema.

## 4. Fotos / contenido

- **VIS-10 · Globopuerto: globos en tierra** `LISTO`
  Vía el conector de Google Drive tomé fotos de la subcarpeta **"FOTOS EN
  TIERRA"** y armé la galería de "Las instalaciones" (antes eran tomas en vuelo)
  con **11 fotos** del globo en tierra, con personas: inflado con el globo de
  marca + equipo, grupos junto a la envolvente, familias, parejas en la
  canastilla al amanecer y selfies antes de despegar. Optimizadas (orientación,
  lado largo ≤1800 px, sin EXIF), alt text es/en.
  Assets: `src/assets/uploads/globopuerto-tierra-01..11.jpg`.
  Quedan más fotos buenas en el Drive (incluye alta resolución en las DSC); si
  quieres, las llevo también a la Galería.

## 5. UX / interacción

- **VIS-11 · Feedback al elegir vuelo (auto-scroll)** `LISTO`
  En el paso 1 de reservas, al elegir un vuelo ahora: (1) se hace **scroll suave**
  hacia el bloque Pasajeros/Fecha si está fuera de vista, y (2) ese bloque recibe
  un **halo fucsia breve** como confirmación. Respeta `prefers-reduced-motion`
  (scroll instantáneo, sin halo).

- **VIS-12 · Parallax de la doble exposición (foto fija)** `LISTO`
  En "Lo que se ve allá arriba", la foto del cielo ahora va **fija de fondo**
  (`background-attachment: fixed`) recortada con máscara en forma del globo: al
  hacer scroll, la ventana-globo revela distinto el cielo (parallax real). iOS
  degrada a normal (sin parallax, no roto); `prefers-reduced-motion` la deja
  contenida sin movimiento.

## 6. Marca / consistencia

- **VIS-13 · Homologar el nuevo logo en el flujo de reservas** `LISTO` (código)
  El wordmark ya vivía en el menú (rail + barra móvil) de todo el sitio. Se
  homologó en el resto del flujo:
  - **Página de confirmación** `/reserva-confirmada` (es + en): wordmark en el
    encabezado del "boleto", arriba del check de éxito. `LISTO`
  - **Correos de notificación** (`server/lib/templates.php`): encabezado de marca
    con el logo por **URL absoluta** (`{site_url}/aerodiverti-wordmark.png`, no
    adjunto, dominio-agnóstico) sobre banda oscura para que el wordmark tratado
    para oscuro se vea nítido; cuerpo claro legible. Aplica a la confirmación al
    cliente y a la alerta al admin. Se añadió `public/aerodiverti-wordmark.png`
    (PNG estable para correo; los clientes de correo no renderizan SVG). `LISTO`
  - **Stripe Checkout**: subir el logo en el panel de Stripe (Configuración →
    Marca). `PEND` — paso manual del negocio en el dashboard de Stripe.
  - (Opcional, no hecho) Reforzar el motivo "boarding pass" del `BookingWidget`.

---

## 7. Backend / paneles administrativos (antes de producción)

Registrados el **2026-08-31**, tras validar el panel de ventas en el sitio de
prueba. Son requisitos **antes de salir a producción**.

- **VIS-14 · Paneles backend responsive** `LISTO`
  La barra de scroll horizontal salía en el **panel de ventas**: la tabla tenía
  `min-width:820px` y los correos largos no cortaban, empujando el ancho.
  Solución en `server/public/api/panel.php`: se quitó el ancho mínimo forzado,
  los correos ahora cortan (`overflow-wrap`) y las columnas tienen **prioridad**:
  en tablet (≤960px) se ocultan Vuelo, Fecha de vuelo y Saldo en sitio; en móvil
  (≤640px) además Creada, Pax y Modo, dejando Folio · Cliente · Pagado · Estado.
  Así cabe sin scroll horizontal a cualquier ancho. El admin de contenido
  (`/admin`, Decap) ya es responsive por sí mismo.

- **VIS-15 · Favicon administrativo (diferenciar backend del front)** `LISTO`
  Mismo globo del favicon principal pero en **azul** (tono administrativo) para
  señalar que es backend. `public/favicon-admin.svg` (enlazado en
  `public/admin/index.html`) para el CMS de contenido, y una variante embebida
  (data-URI base64, para no romper el "un solo archivo") en el `<head>` del
  panel de ventas `panel.php`. El front sigue en fucsia.

---

## 8. Flujo de reservas (después de tarifas por temporada)

Registrado el **2026-10-02**, tras probar el entorno de pruebas con el calendario de precios.

- **VIS-16 · Auto-scroll al botón "Continuar" después de una selección** `PEND`
  Hoy, al elegir la **fecha** en el calendario (paso 1 de `/reservar`), el botón **Continuar** queda fuera de
  vista y hay que hacer scroll a mano; si el cliente no ve el botón, hay fricción y puede abandonar. Aplica
  igual a otras selecciones que dejan el siguiente paso abajo (pasajeros, modo de pago).
  Propuesta (mismo patrón que VIS-11, `pickFlight` en `BookingWidget.tsx`):
  - Al elegir fecha (`PriceCalendar` → `onSelect`, y el input nativo en `onChange`), si el botón Continuar no
    está completamente visible, `scrollIntoView({ block: "nearest" })` suave hacia él (o hacia el resumen +
    botón); si ya se ve, no mover nada.
  - Sin robar el foco (el teclado sigue en el calendario); solo scroll. No hacerlo al navegar meses ni al
    cargar la página con fecha precargada.
  - `prefers-reduced-motion`: salto instantáneo. Opcional: el mismo halo breve de VIS-11 sobre el botón.
  - Probar en móvil 390 px (donde más pasa) y en escritorio; cuidar la barra/banner inferior (cookies) para
    que no tape el botón al llegar.

---

## 9. Back-office: ideas del sistema anterior (aerodiverti.mx)

Registrado el **2026-10-05**. Norman compartió capturas del panel del desarrollo anterior (Reservaciones,
Disponibilidad, Abordaje, Contenido, Laboratorio). **No se desarrolla todavía**: se retoma cuando salgamos de
pruebas y hagamos cambios en el proyecto principal. Idea general: replicar lo útil con versiones más sólidas y
con el look del panel actual (Obsidiana, sin emojis como iconos, Phosphor), no el estilo "hecho con IA" de
esas pantallas. La parte de contenido de ese sistema **no** se copia (nosotros editamos con GitHub/Decap), solo
su forma de presentar los campos (BO-04).

Orden sugerido por impacto: BO-01 y BO-02 (operación del día a día y evitar sobreventa), luego BO-03, BO-04, BO-05.

- **BO-01 · Cupo por día** `PEND`
  Lo que tenían: lista de días con "N reservados · M libres de 40", campo para cambiar el cupo de ese día y
  botón **Cerrar**. Nosotros ya tenemos días sin vuelo y precios por fecha, pero **no** límite de lugares: hoy
  se podría vender de más un día lleno.
  Versión mejorada: cupo por defecto + cupo por fecha en la pestaña Precios (o una nueva "Disponibilidad"),
  contado contra reservas pagadas y en proceso de pago; el servidor rechaza el cobro si ya no hay lugares
  (mismo patrón que `409 date_blocked`); el calendario público muestra "Pocos lugares" y "Agotado". Vista
  de mes (no una lista de 60 filas). El número de lugares lo define el negocio (no inventarlo).

- **BO-02 · Abordaje del día** `PEND`
  Lo que tenían: pestañas por fecha con conteo, "0 de 1 reservas abordadas / 0 de 3 personas presentes",
  **Escanear QR**, búsqueda por nombre/teléfono/folio, tocar un nombre para marcarlo presente, dirección de
  recogida (transporte CDMX), **CSV del día** y **de la semana**, confirmación antes de deshacer o de marcar un
  pase de otra fecha.
  Versión mejorada: pantalla pensada para celular en el globopuerto; QR en el correo de confirmación y en
  `/reserva-confirmada`; marcar presente queda en el historial (quién y a qué hora); lista de recogidas por
  ruta para el transporte; aviso claro si el pase es de otro día o ya se usó; funciona con mala señal
  (reintenta al volver la red).

- **BO-03 · Laboratorio: dónde abandonan y cuánto se vende** `PEND`
  Lo que tenían: embudo "abrieron la reserva → vieron el catálogo → fecha → datos y pago → eligieron método →
  pagaron" con la mayor fuga marcada; ventas reales (total, desde el rediseño, últimos 7 días); ventas por
  dispositivo, canal, paquete y proveedor; ventas recientes; embudo por paquete; alerta si la conversión cae.
  Versión mejorada: eventos propios y anónimos (sin datos personales, respetando el aviso de cookies) en la
  base del panel, más las ventas reales de `bookings`; gráficas sobrias del sistema del panel; definiciones
  claras de cada métrica. Ojo: en sus capturas aparece una conversión de **126.7%**, imposible: hay que
  validar que nada pase de 100% y no mezclar sesiones con clics.

- **BO-04 · Editor de paquetes más digerible en /admin** `PEND`
  Lo que le gustó a Norman: cada paquete en una tarjeta con pocos campos claros (precio, precio tachado,
  duración, lugares, orden, "popular"), con una nota corta bajo cada uno, y una bitácora de cambios al final.
  El nuestro (Decap) se siente "enroscado": objetos anidados Español/English, slug, alt, etc.
  Versión mejorada (sin dejar Decap/GitHub): primero lo esencial (nombre, precio, precio tachado, disponible,
  foto) y lo demás plegado en "Avanzado" (slug, orden, alt, inglés); tal vez una vista "Precios de todos los
  paquetes" en una sola pantalla; historial legible de cambios a partir de los commits.
  Campos que ellos tenían y nosotros no (precio por menor, hora para presentarse, recogida en CDMX): solo si
  el negocio los confirma, y cambiando `content.config.ts` y `config.yml` juntos.

- **BO-05 · Experimentos A/B** `PEND`
  Lo que tenían: pruebas de orden del catálogo, tema claro vs oscuro, nuevo layout de escritorio y pago
  embebido vs redirección, con aviso de "no concluyente" cuando la muestra es chica.
  Versión mejorada: una prueba a la vez, con tamaño de muestra mínimo y fecha de corte definidos antes de
  empezar; resultado en lenguaje simple ("todavía no se sabe" / "B vende más"). Depende de BO-03.

---

## 10. Contacto y SEO

Registrado el **2026-10-07**.

- **CON-01 · Brisa como WhatsApp principal** `PEND`
  Pedido de Norman: el número de **Brisa** (55 9199 4645) pasa a ser el WhatsApp principal de la sección de
  contacto, y Brisa aparece **antes** que Rubí en todo el sitio.
  Dónde se cambia (todo en `src/config/site.ts`, nada hardcodeado en componentes):
  - `brand.whatsapp` → `5215591994645` (alimenta `whatsappUrl()`: Contacto, botón de chat, menú, paquetes
    "Consultar", reserva).
  - `brand.whatsappDisplay` → `+52 55 9199 4645`. **Ojo:** se puede sobrescribir con la variable
    `PUBLIC_WHATSAPP_DISPLAY_NUMBER`; si existe en el build de producción (GitHub Actions / hosting), cambiarla
    también o quitarla, o el sitio mostrará el número viejo con el enlace nuevo.
  - `brand.advisors`: Brisa primero, Rubí después (orden de los botones en `/reservar`). Actualizar el
    comentario "el primero es el número principal".
  - Revisar el JSON-LD (schema) y la página de contacto en es/en después del build.

- **SEO-01 · Search Console: "No se ha encontrado (404)"** `PEND (falta lista de URLs)`
  Alerta del 2026-10-07. Revisión hecha en el repo: el sitio nuevo **no tiene enlaces internos rotos** y
  todas las URLs del sitemap existen (verificado sobre `dist/`). Lo más probable es que Google esté
  visitando **direcciones del sitio anterior** en este dominio que ya no existen.
  Qué falta: la lista de ejemplos de Search Console (Indexación › Páginas › "No se ha encontrado (404)" ›
  exportar). Con ella:
  1. URLs viejas con equivalente nuevo (paquetes, contacto, reservas…) → **redirección 301** en
     `public_html/.htaccess` (plantilla `deploy-htaccess-public_html.txt`).
  2. URLs sin equivalente → se dejan en 404 (es normal y Google las va soltando) o 410 si se quiere acelerar.
  3. Agregar una **página 404 propia** (`src/pages/404.astro` + `ErrorDocument 404 /404.html`): hoy Apache
     muestra su página genérica; la nueva lleva a Vuelos, Reservar y WhatsApp, con `noindex`.
  4. Si alguna URL empieza con `pruebas.aerodiverti.com.mx`, es el entorno de pruebas (no indexable a
     propósito) y se ignora.
  5. Al terminar: "Validar corrección" en Search Console.

---

## Estado

Backlog visual **cerrado en código**: VIS-00..VIS-15 LISTO/RESUELTO. Único paso
manual pendiente: subir el **logo en Stripe** (parte de VIS-13, en el dashboard
de Stripe → Marca). Si el diseñador comparte el **archivo vectorial** del logo
(.ai/.eps/PDF vectorial), se puede reemplazar el wordmark tratado por un SVG
vectorial puro.
