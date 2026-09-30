# PLAN — Joyas Colombianas® Dinner & Show

Fases y tareas. Detalle de avance en `docs/PROGRESS.md`; stack en `docs/STACK.md`.

## Fase 1 — 28 sept a 2 oct: accesos, auditoría, tokens, estructura base y home
- [ ] Acceso a Hostinger (credenciales, staging, versión de PHP)
- [x] Pantallazo del sitio actual (Norman)
- [x] Tokens de diseño y diagnóstico → `docs/DESIGN-TOKENS.md` (pendiente revisión de Norman)
- [x] Plataforma: sitio a medida sin WordPress, PHP + panel propio
- [x] Estructura base PHP (rutas, contenido editable, SEO, formulario)
- [x] Home full SEO con parallax por capas, contenido migrado
- [ ] Logo, fotos y video reales en `public/assets/img/` (`docs/IMAGENES.md`)
- [ ] Panel de administración mínimo (contenido, programación, precios, WhatsApp)

## Fase 2 — 5 a 9 oct: landings de campaña + contenido de John
- [ ] Landing Cartagena (marca, "Tesoros del Pacífico", sin checkout, noindex)
- [ ] Landings Medellín y Bogotá: conversión 100% UX/UI, noindex, fuera del sitemap, visibles en el sitio
- [ ] Conversión de .docx de John → `content/`, inyección en el home (SEO) y landings (6 oct)
- [ ] Revisión cruzada de contenido y enlaces internos

## Fase 3 — 12 a 16 oct: pagos, ticket, correo, door list, QA, producción
- [ ] Esquema MySQL: funciones, órdenes, tickets, door list
- [ ] Mercado Pago detrás de un adaptador (prep 12 oct, validación de cobertura internacional 13 oct, integración 14 oct)
- [ ] Webhook idempotente con firma verificada → ticket `#JCD-1001…` atómico
- [ ] Correo transaccional con ticket
- [ ] Door List exportable Excel/CSV
- [ ] Eventos `begin_checkout` y `purchase` (solo pago aprobado, en URL propia)
- [ ] Google My Business (15 oct)
- [ ] QA de punta a punta en staging y pase a producción (16 oct, con aprobación de Norman)

## Preguntas abiertas
- ¿Versión en inglés para turistas?
- ¿Se mantiene el formulario de contacto además de WhatsApp? (hoy está construido, igual que en el sitio actual)
- ¿Correo transaccional por SMTP de Hostinger o servicio externo?
- ¿Hay video horizontal del show?
