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
├── index.html              # Home (11 secciones)
├── diagnostico/index.html  # Diagnóstico Ejecutivo (landing de campaña)
├── assets/
│   ├── css/styles.css      # Design system + componentes v2.0 (fases, stack, casos, oferta)
│   ├── js/main.js          # Menú móvil
│   ├── fonts/              # Century Gothic (4 variantes)
│   └── img/                # Logo, isotipo.svg y set de favicons
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
  - `assets/img/dashboard.jpg` → sección "Cómo funciona" (analítica / marketing basado en datos).
  - `assets/img/consultoria.jpg` → sección "Diagnóstico Ejecutivo" (estrategia / acompañamiento).
  - **Ambas son placeholders on-brand.** Para usar tus fotos reales, reemplaza esos dos
    archivos conservando el mismo nombre y ruta; el estilo, el hover y el fade-up ya
    están aplicados y funcionan solos.
- **Microinteracciones** (globales, sutiles): hover con elevación + sombra en tarjetas,
  lift suave en botones y entrada *fade-up* al hacer scroll (IntersectionObserver, mejora
  progresiva). Todo respeta `prefers-reduced-motion`. No se modificó estructura ni copy.
