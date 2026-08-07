# Home Easy Marketing — rediseño (from scratch)

Nueva home de `easymarketing.mx`, construida desde cero con el contenido real del
sitio actual y la disciplina visual de Noomo Agency (paleta restringida, motion con
propósito, revelado progresivo por scroll), pero en tecnología ligera: **HTML + CSS +
SVG**, sin Three.js/WebGL, sin frameworks, sin video.

## Archivos

| Archivo | Qué es |
|---|---|
| `index.html` | **La home.** Página autónoma: logos y foto incrustados en base64. Se sube tal cual y funciona sin la carpeta `images/`. |
| `Servicios.html` | **Servicios.** Misma familia visual y fondo neuronal, estructura de catálogo. Reemplaza la vieja que tiraba 404. |
| `Contact.html` | **Contacto.** Formulario + datos directos (WhatsApp, tel, email, redes). El form redirige a `gracias.html` al enviarse. |
| `gracias.html` | **Página de gracias (thank-you).** Marca la conversión: `noindex` + evento `dataLayer` `generate_lead`. Aquí deben disparar las campañas su conversión. |
| `*_src.html` | Plantillas fuente. La home es la fuente única del design system; las demás usan `__STYLE__` y `__NEURAL_JS__`, inyectados por el build. |
| `build.py` | Optimiza/incrusta imágenes, inyecta GTM + estilos compartidos y genera las 4 páginas. |

## Formularios, conversiones y analítica

- **GTM:** todas las páginas cargan tu contenedor `GTM-K73GRX5H` (el mismo del sitio original).
- **Flujo de conversión:** el formulario de `Contact.html` envía y **redirige a `gracias.html`**. Esa página es única (`noindex`) y hace `dataLayer.push({event:'generate_lead', conversion:true, ...})`. En Google Ads / Meta configura la conversión por **visita a `gracias.html`** o por el evento `generate_lead` en GTM.
- **⚠️ Un valor que debes poner:** en `Contact.html` el formulario usa [Web3Forms](https://web3forms.com) (gratis). Reemplaza `YOUR_WEB3FORMS_ACCESS_KEY` por tu *access key* (se saca en 1 minuto con tu correo) para recibir los mensajes. Si prefieres Formspree u otro proveedor, dilo y se cambia. El envío funciona por JS (fetch + redirect) con respaldo nativo si el visitante tiene JS desactivado.

## SEO / GEO

- `title`, `description`, `canonical` y OpenGraph únicos por página; HTML semántico; `lang="es-MX"`.
- **JSON-LD:** `Organization` + `FAQPage` (home), `OfferCatalog` (servicios), `ContactPage` + `Organization/contactPoint` + `BreadcrumbList` (contacto).
- La página de gracias va con `robots: noindex` (buena práctica: no debe rankear).
- Mensajes clave como **texto real** (no dentro de imágenes), rápido y mobile-first.

> Los enlaces internos usan `index.html` y `Servicios.html` (con S mayúscula, como el
> archivo original). Súbelos con esos nombres exactos para que el menú no rompa.

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
