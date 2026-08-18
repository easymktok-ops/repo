# CLAUDE.md

Contexto para agentes que trabajen en este repo.

## Qué es

Plataforma de marca + reservas para **Aerodiverti** (vuelos en globo sobre
Teotihuacán). Registro de diseño: **brand** (el diseño ES el producto).

## Antes de tocar UI

Este proyecto usa tres skills de diseño, en orden: `impeccable` (ver `PRODUCT.md`
y `DESIGN.md`), `design-taste-frontend` (dials + anti-slop preflight),
`emil-design-eng` (decisiones de animación). Regla de oro: si alguien pudiera
mirar el sitio y decir "esto lo hizo una IA" sin dudar, no está terminado.

Dials fijados: `DESIGN_VARIANCE 8`, `MOTION_INTENSITY 7`, `VISUAL_DENSITY 4`
(override a 5 en el catálogo).

## Reglas duras

- **Dominio-agnóstico**: nada de dominios hardcodeados. Todo absoluto sale de
  `PUBLIC_SITE_URL` / `src/config/site.ts`.
- **Tokens = fuente de verdad**: colores/tipo/espaciado/motion viven en
  `src/styles/tokens.css` (OKLCH). Tailwind los referencia con `@theme inline`.
- **Un solo acento** (aqua "cielo alto"). Ember es terciario raro, no color de UI.
- **Tema único** Obsidiana (oscuro). No hay light mode.
- **Motion**: nunca `transition: all`, nunca `ease-in` en UI; usar curvas de
  tokens; `prefers-reduced-motion` siempre; movimiento continuo con
  `useMotionValue`/`useTransform`, no `useState`.
- **Iconos**: Phosphor. Nunca Lucide por defecto, nunca SVG a mano (salvo favicon).
- **Contenido**: no inventar datos de negocio (precios, políticas, direcciones).
  Los carga el negocio vía `/admin`.
- **Sin em-dash** en texto visible.

## Contenido y CMS

`src/content.config.ts` define el schema; `public/admin/config.yml` es el panel
que lo edita. Ambos deben cambiar juntos.

## Notificaciones

`src/lib/notifications` implementa outbox con reintentos + logging: el estado de
envío SIEMPRE queda trazado (anti bug "sin correo enviado"). Nunca volver a
fire-and-forget.
