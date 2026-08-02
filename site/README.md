# Easy Marketing — Sitio (posicionamiento v2.0: Growth Systems)

Sitio estático de dos páginas para el posicionamiento **v2.0** de Easy Marketing:
una **consultora de sistemas de crecimiento comercial (Growth Systems)** para
empresas **B2B consolidadas**. El producto ancla es el método propietario
**Easy Growth System™** (Diagnosis → Blueprint → Activation → Optimization →
Scale). La IA, la automatización, los Ads y el CRM se comunican como
**componentes del sistema**, nunca como servicios sueltos.

> Cambio respecto a v1: v1 vendía "un sistema de IA que responde, califica y
> agenda". v2.0 vende **el sistema de crecimiento**; la herramienta baja a ser un
> componente. El léxico sube a ejecutivo (Growth, pipeline, CAC, MQL/SQL, ROI) y
> el ICP sube a empresa B2B con equipo comercial.

## Arquitectura (home, 11 secciones)

1. Hero — tesis: "El crecimiento predecible no es suerte. Es un sistema."
2. El verdadero problema — más publicidad no arregla un sistema roto.
3. Por qué las empresas no crecen — los 6 cuellos de botella.
4. Easy Growth System™ — el método en 5 fases.
5. Cómo funciona — un solo sistema para marketing, ventas y operaciones.
6. Capacidades del sistema — resultados de negocio (no servicios).
7. Casos de negocio — reto → sistema → resultado (con marcadores, sin cifras inventadas).
8. Tecnologías — el stack como componentes del sistema.
9. FAQ — objeciones de un director (¿por qué ustedes?, ¿por qué ahora?, ¿cómo miden?…).
10. Diagnóstico Ejecutivo — la oferta + calificación (para quién es / no es).
11. CTA final — Sesión Estratégica.

La landing `/diagnostico/` es la página del **Diagnóstico Ejecutivo** para tráfico
de campaña (hero + calificación + proceso en 3 pasos + FAQ de conversión + CTA).

## Identidad de marca (sin cambios)

Se conserva íntegro el design system real importado del **"easy MKT Design
System"**: colores (electric indigo `#2E2BE6` + spectrum), **Century Gothic**
auto-alojada, logo, isotipo/favicons y la animación del circuito del hero. El
reposicionamiento es de copy, orden, jerarquía y CTAs —no un rediseño.

## Estructura de archivos

```
site/
├── index.html                     # Home (11 secciones + card del autodiagnóstico)
├── diagnostico/index.html         # Diagnóstico Ejecutivo (landing de campaña)
├── diagnostico-negocio/index.html # Diagnosticador "7 Puntos" (autodiagnóstico / lead magnet)
├── gracias.html                   # Thank-you page (Diagnóstico Ejecutivo)
├── assets/
│   ├── css/styles.css             # Design system + componentes (incl. .dx- del diagnosticador)
│   ├── js/main.js                 # Menú móvil + reveal + tracking + formulario ejecutivo
│   ├── js/diagnostico-negocio.js  # Wizard + puntaje + reporte + PDF del diagnosticador 7 Puntos
│   ├── fonts/                     # Century Gothic (4 variantes)
│   └── img/                       # TODAS las imágenes: logo, isotipo, favicons y fotos (dashboard.webp, consultoria.webp)
├── site.webmanifest
└── README.md
```

## CTAs (links reales)

- **Principal — Diagnóstico Ejecutivo / Sesión Estratégica (Calendly):**
  `https://calendly.com/easymktok/asesoria`
- **Secundario — Habla con un consultor (WhatsApp):**
  `https://api.whatsapp.com/send/?phone=5215539787305&text=Hola%2C+solicito+informaci%C3%B3n&type=phone_number&app_absent=0`

Todos los CTA llevan a una sesión estratégica; se eliminaron los genéricos
("asesoría gratis", "agenda una llamada"). Sin formularios que compitan.

## Reglas de contenido aplicadas (v2.0)

- Se vende el **sistema**, no la herramienta. Ads/CRM/IA/automatización = componentes.
- Léxico ejecutivo permitido y usado: Growth, pipeline, generación de demanda, CAC,
  CPL, MQL/SQL, LTV, ROI, framework, optimización, escalabilidad.
- Sin "agencia de marketing / gestión de redes / campañas / SEO como servicio" ni la
  palabra "despacho". ICP = empresa B2B consolidada con equipo comercial.
- Casos de negocio con estructura reto/sistema/resultado y **marcadores claros**
  (`[Métrica … pendiente]`) — no se inventan cifras.
- Mobile-first, sin overflow horizontal, hero con copy y CTA antes que el visual.

## Cómo verlo localmente

```bash
cd site
python3 -m http.server 8080
# http://localhost:8080/  y  http://localhost:8080/diagnostico/
```

## Pendiente

Cargar métricas reales en "Casos de negocio" cuando estén disponibles (hoy con
marcadores). El documento ejecutivo de la auditoría v2.0 acompaña este cambio.

## Mejora visual v2.1 (imágenes de apoyo + microinteracciones)

- **Imágenes de apoyo** (bordes redondeados, sombra suave, animación de entrada):
  - `assets/img/dashboard.webp` → sección "Cómo funciona" (analítica / marketing basado en datos).
  - `assets/img/consultoria.webp` → sección "Diagnóstico Ejecutivo" (estrategia / acompañamiento).
  - **Ambas son placeholders on-brand.** Para usar tus fotos reales, reemplaza esos dos
    archivos conservando el mismo nombre y ruta; el estilo, el hover y el fade-up ya
    están aplicados y funcionan solos.
- **Microinteracciones** (globales, sutiles): hover con elevación + sombra en tarjetas,
  lift suave en botones y entrada *fade-up* al hacer scroll (IntersectionObserver, mejora
  progresiva). Todo respeta `prefers-reduced-motion`. No se modificó estructura ni copy.

## Iteración de conversión v2.2 (formulario + casos reales + tracking)

- **Formulario de calificación** en la sección `#diagnostico` (home): Nombre, Empresa,
  Email corporativo, WhatsApp/tel, Desafío (select), Tamaño de equipo comercial (radios).
  Botón "Solicitar Diagnóstico Ejecutivo" + microcopy. Email personal solo **advierte**
  (no bloquea). Todos los CTA principales ahora llevan al formulario (`#diagnostico`),
  no a WhatsApp.
- **Envío del lead → `info@easymarketing.mx`** vía FormSubmit (sin backend). Al enviar:
  valida → dispara evento → `fetch` a `https://formsubmit.co/ajax/info@easymarketing.mx`
  → redirige a **`gracias.html`** (thank-you page para medir conversión). El "siguiente
  paso" (agenda) usa el Calendly real desde la thank-you page.
  - ⚠️ **FormSubmit requiere activación única:** en el primer envío real llega un correo
    de confirmación a info@easymarketing.mx; hay que hacer clic una vez. El endpoint es
    una sola línea en `index.html` (`action=`) — fácil de cambiar por tu backend/CRM.
- **Casos de negocio reales** (sin nombres ni cifras inventadas): B2C internacional
  (resultado cualitativo), Headhunting B2B (**+30% MQLs**), Ecommerce mobiliario
  (**13X ROAS tras 6 meses**). Nota de transparencia reforzada.
- **Tracking (dataLayer, listo para GTM/GA4):** `diagnostico_ejecutivo_cta`,
  `diagnostico_ejecutivo_start`, `diagnostico_ejecutivo_submit`, `whatsapp_click`, y
  `diagnostico_ejecutivo_success` en la thank-you page. **Contenedor GTM `GTM-K73GRX5H`
  instalado** en las tres páginas (`<script>` en `<head>` + `<noscript>` tras `<body>`);
  en GTM basta con crear triggers de "Evento personalizado" con esos nombres y enviarlos a
  GA4 / conversiones.
- **Ajustes de copy:** "Demand Activation" como componentes del sistema; "Es para ti"
  → "capacidad comercial y operativa…"; capacidades refuerza "no son servicios sueltos".
- Sin rediseño: se conservó identidad visual, layout, colores, tipografías y animaciones.

- **Formulario también en la landing de campaña** `/diagnostico/` (sección `#diagnostico` local, redirige a `../gracias.html`); sus CTAs apuntan al formulario de la propia página.

## Diagnosticador "7 Puntos" (`/diagnostico-negocio/`) — autodiagnóstico + lead magnet

Herramienta de autoevaluación (no es investigación de Claude: **el visitante responde**
sobre su propio negocio) basada en los 7 puntos de por qué un cliente compra o no:
**Público, Problema, Solución, Diferenciales, Testimonios, Objeciones, Garantía.**

- **Wizard, un punto a la vez** con barra de progreso "Punto X de 7". 13 preguntas
  (1–2 por punto), mayormente opción múltiple / escala 1–5 y 2 preguntas abiertas cortas
  (problema y diferenciador, ambas opcionales). ~3 min. Todo corre en el cliente.
- **Modelo híbrido de captura:**
  1. Al terminar muestra **resumen GRATIS sin pedir datos**: semáforo 🟢🟡🔴 por punto +
     el cuello de botella #1 destacado (gancho).
  2. Para el **reporte completo** pide Nombre, Email, WhatsApp, Negocio (opcional) y
     un checkbox de consentimiento.
  3. **Reporte** (post-captura): cada punto con puntaje, explicación y **1–2
     recomendaciones accionables personalizadas** según las respuestas (usa las frases
     abiertas del usuario cuando existen); prioridad #1 = punto más débil; **botón
     "Descargar PDF"** (imprime con `@media print` on-brand: logo, colores) y CTA a
     Calendly con copy dinámico ("…resolvemos juntos tu {punto más débil}") + WhatsApp
     secundario.
- **Puntaje**: cada respuesta aporta 0–100; el punto es el promedio ponderado de sus
  preguntas. Semáforo: ≥70 verde, 45–69 amarillo, <45 rojo. El más bajo = prioridad #1.
  **No inventa nada**: todo se deriva de lo respondido; abiertas vacías no bloquean.
- **Leads → mismo FormSubmit** (`info@easymarketing.mx`) con asunto propio
  *"Nuevo diagnóstico 7 Puntos"* para distinguirlos del Diagnóstico Ejecutivo. El correo
  del lead **incluye el resultado del diagnóstico** (puntaje por punto, punto más débil,
  índice general y las frases abiertas) como campos ocultos inyectados al enviar.
- **Tracking (dataLayer / GTM):** `dx_start`, `dx_complete`, `dx_lead_submit`,
  `dx_report_unlocked`, `dx_pdf_download`, `dx_calendly_click`.
- **Entrada desde el home**: card "3.5" entre "Por qué no crecen" y "El método", con
  copy de curiosidad + botón a `diagnostico-negocio/`.
- Reglas de lenguaje respetadas: "negocio/organización" (no "despacho"); se comunica como
  herramienta de **diagnóstico de negocio**, no como "análisis de marketing".

- **CTA primario del hero (home)**: cambiado de "Agenda un Diagnóstico Ejecutivo" a
  **"Hacer mi diagnóstico gratuito"**, que ahora abre el diagnosticador (`diagnostico-negocio/`)
  como entrada de menor fricción (top-of-funnel). Los demás CTA principales del sitio siguen
  llevando al Diagnóstico Ejecutivo (`#diagnostico`). Nuevo evento de tracking
  `diagnostico_negocio_cta` para clics hacia el diagnosticador.

- **Envío paralelo a Make (webhook):** al enviar el formulario, además del POST a
  FormSubmit, se dispara un segundo POST **fire-and-forget** en JSON al webhook
  `https://hook.us2.make.com/75gb150bmqtepgh5jypy29gu7fkpb8zk` con los mismos valores
  (nombre, negocio, email, whatsapp, consentimiento, `punto_1_publico`…`punto_7_garantia`
  como "NN/100", `punto_mas_debil`, `indice_general`, `problema_respuesta`,
  `diferenciador_respuesta`). No bloquea ni retrasa el flujo: el reporte y el PDF se
  muestran de inmediato; si el webhook falla o tarda, se ignora en silencio (solo
  `console.error`). No reemplaza a FormSubmit —es un envío adicional para automatización.
