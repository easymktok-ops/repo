# PLAN — Joyas Colombianas® Dinner & Show

Ver reglas duras, alcance y exclusiones en el brief del proyecto (no se repiten aquí para evitar desincronización). Este archivo solo trackea fases/tareas.

## Fase 1 — 28 sept a 2 oct: accesos, auditoría, tokens, diagnóstico, estructura base y home
- [ ] Acceso a Hostinger (credenciales, staging) — sin WordPress
- [ ] Pantallazos + URLs de referencia del sitio actual (Norman)
- [ ] Auditoría `/impeccable` → `docs/DESIGN-TOKENS.md`
- [ ] Diagnóstico corto (se conserva / se mejora / se descarta) → aprobación Norman
- [x] Plataforma confirmada: sitio a medida sin WordPress (Norman)
- [ ] Aprobación del stack concreto (PHP a medida + admin propio vs. Grav CMS) — ver propuesta en `docs/PROGRESS.md`
- [ ] Confirmar si `hero-loop.mp4` es válido como placeholder o se descarta junto con los demás assets genéricos del repo
- [x] Prototipo aislado del hero (`prototipo/hero.html`) — motion, carga diferida de video, accesibilidad
- [ ] Estructura base del home (secciones, sin copy final — placeholders `{{...}}`)

## Fase 2 — 5 a 9 oct: landings acordadas + contenido de John
- [ ] Confirmar con Norman qué landings entran en este alcance
- [ ] Conversión de .docx de John → `content/` (Markdown/JSON)
- [ ] Inyección de contenido en componentes de home
- [ ] Landings: Cartagena (solo marca), Bogotá, Medellín
- [ ] Landings de pauta (noindex, fuera de sitemap)
- [ ] Páginas SEO largas (H1-H3, schema Event/Offer)
- [ ] Botón WhatsApp click-to-chat en todas las páginas

## Fase 3 — 12 a 16 oct: checkout, ticket, correo, door list, QA, producción
- [ ] Integración de pasarela de pago (adaptador de pagos) — pasarela por definir
- [ ] Variables de entorno para API keys (fuera del repo)
- [ ] Webhook idempotente + firma verificada → ticket numerado secuencial
- [ ] Correo transaccional de confirmación con ticket
- [ ] Door List exportable Excel/CSV
- [ ] Eventos GA4/Meta: `begin_checkout`, `purchase` (solo pago aprobado)
- [ ] QA punta a punta en staging
- [ ] Pase a producción (con aprobación de Norman)

## Preguntas abiertas (antes de seguir de fondo)
- ¿Sitio en inglés para turistas?
- ¿Formulario de contacto además de WhatsApp?
- ¿Correo transaccional por SMTP de Hostinger o servicio externo?
- ¿El cliente tiene video horizontal del show, o solo el material vertical visto en Drive?
- ¿Qué pasarela de pago se usará finalmente?
