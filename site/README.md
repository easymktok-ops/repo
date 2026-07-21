# Easy Marketing — Sitio reconstruido (reposicionamiento IA)

Sitio estático de dos páginas para el nuevo posicionamiento de Easy Marketing:
**generación de demanda con automatización de IA** para negocios y organizaciones
de servicios profesionales (headhunting, staffing, consultoría B2B).

El producto ancla que se comunica: un sistema de IA que **responde en segundos,
califica al prospecto y agenda la cita** en el calendario del cliente,
automáticamente. La propuesta de valor es **ahorro de tiempo y dinero** (más citas,
menos seguimiento manual), enmarcado como *generación de demanda* — no como
servicio de agencia ni como "automatizaciones" a secas.

## Identidad de marca — importada del design system real

El look & feel proviene del **"easy MKT Design System"** de Claude Design
(proyecto `b38baca9-b81b-4f2e-8bee-76277ae56cac`), importado vía el MCP de Design.
Se implementaron sus tokens y assets reales:

- **Colores** (`tokens/colors.css`): electric indigo `#2E2BE6` como acento principal
  (CTA de Calendly), la paleta *spectrum* de la marca (cyan `#16DCDC`, green
  `#15C24D`, yellow `#FFC400`, orange `#FF6A1A`, magenta `#FF2D78`) para la
  animación del hero y detalles, y la rampa *ink* para textos/superficies.
- **Tipografías** (`tokens/typography.css`): **Century Gothic** (display + body),
  auto-alojada en `assets/fonts/` (Regular/Italic/Bold/BoldItalic). Los números de
  paso usan la familia pesada; si quieres **Archivo Black** (definida en el design
  system para números), agrégala en `assets/fonts/ArchivoBlack.otf` y ya está
  referenciada en `--font-heavy`.
- **Logo**: `assets/img/easy-mkt-logo.png` (wordmark oficial) en el header de ambas
  páginas.
- **Favicon / iconos**: generados a partir del isologo de marca sobre indigo
  (`favicon.ico`, `favicon-16/32/48`, `apple-touch-icon-180`,
  `android-chrome-192/512`) + `site.webmanifest`.

> Nota sobre el favicon: el archivo `favicon.png` original del design system venía
> truncado (PNG corrupto en origen), así que el set de iconos se generó con el
> lockup de marca en blanco sobre indigo (estilo app-icon). Si prefieres el isotipo
> aislado, reemplaza los PNG de `assets/img/` y regenera.

## Estructura

```
site/
├── index.html              # Home (/)
├── diagnostico/index.html  # Landing de campaña (/diagnostico/) — reemplaza a /free/
├── assets/
│   ├── css/styles.css      # Design system (tokens reales por variables CSS)
│   ├── js/main.js          # Menú móvil; el FAQ usa <details> nativo
│   ├── fonts/              # Century Gothic (4 variantes, auto-alojadas)
│   └── img/                # Logo + set de favicons
├── site.webmanifest
└── README.md
```

## Cómo verlo localmente

```bash
cd site
python3 -m http.server 8080
# http://localhost:8080/  y  http://localhost:8080/diagnostico/
```

## CTAs (links reales, ya integrados)

- **Principal (Calendly)** — el botón más prominente, en header, hero, tras cada
  sección de valor, CTA final y barra fija móvil:
  `https://calendly.com/easymktok/asesoria`
- **Secundario (WhatsApp)** — menor peso visual (ícono en header + repetido abajo
  y en el CTA final):
  `https://api.whatsapp.com/send/?phone=5215539787305&text=Hola%2C+solicito+informaci%C3%B3n&type=phone_number&app_absent=0`

No hay formularios de contacto que compitan con Calendly.

## Hero animado

Fondo claro (no oscuro) con líneas finas en los colores de acento de la marca que
dibujan un patrón tipo **circuito / red de nodos** (inspiración: deeddelivery.com +
animaciones SVG sutiles). Las líneas se **trazan al cargar** (stroke-dashoffset) y
los nodos **pulsan** en loop lento. Es SVG + CSS liviano, respeta
`prefers-reduced-motion` y no bloquea el headline ni el CTA.

## Reglas de contenido aplicadas

- **Lenguaje**: sin "agencia de marketing", "gestión de redes sociales", "campañas
  publicitarias", "SEO", "growth", "funnel", "ROAS". **Sin la palabra "despacho"**
  (se usa "negocio" / "organización" / "empresa de servicios profesionales"). El
  servicio se vende como **sistema/producto de generación de demanda con IA**,
  enfocado en el resultado.
- **Home** (audiencia fría, hooks Problema/Solución): dolor → sistema → ¿para quién?
  → cómo funciona (pasos) → FAQ B2B → CTA final.
- **Landing `/diagnostico/`** (audiencia que vio un anuncio, Producto/Decisión):
  oferta de diagnóstico gratuito de 30 min, **bloque de calificación explícita**
  que autofiltra por sector, tamaño (5–50) e iguala mensual, proceso en 3 pasos,
  FAQ de conversión, CTA repetido.
- **Sin sección de "Resultados de clientes"** en ninguna página (eliminada por
  completo, sin placeholders). No se inventaron clientes, cifras ni testimonios.
- **Mobile-first** y responsive, con barra de CTA fija inferior en celular.

## Banco de hooks (pendiente)

El documento `Easy_Marketing_Matriz_Hooks_Reposicionamiento.md` no estaba disponible
en el entorno; el copy es original, escrito según el brief y las reglas de lenguaje.
Si compartes el documento, ajusto headlines/subheads a esas frases exactas.
