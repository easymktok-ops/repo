# Cómo trabajar en este repo

## Ritmo de trabajo

**Cuando un mensaje trae "Backlog", son ítems para acumular.** No tocar código,
no generar el exportable, no commitear: se anotan, se confirma qué se entendió y
qué falta para poder hacerlos, y se espera la orden explícita para hacer todo
junto en una sola pasada.

Sin esa orden, la respuesta correcta a un backlog es una lista de lo anotado.

## Qué hay acá

- `kosecha/` — el sitio de Kosecha, comida sana en Libres, Puebla (React + Vite).
  Es el proyecto activo. Su README, PRODUCT.md, DESIGN.md y ASSETS-TODO.md
  explican el resto.
- En la raíz también viven un CLI de markitdown y fotos de otro proyecto
  (`hero-loop.mp4`, `galeria-*.jpg`, `ocasion-*.jpg`). No se tocan.

## Reglas del sitio

**No inventar datos.** Precios, ingredientes, horarios, dirección y macros salen
del menú de la marca o de su ficha de Google. Lo que no está confirmado no se
muestra ni se declara en los schemas: un horario inventado manda gente al local
cuando está cerrado. Lo que falta se anota en `kosecha/ASSETS-TODO.md`.

**El copy aprobado es verbatim.** "Quiénes somos" y "Trazabilidad" se transcriben
tal cual del manual de marca, en `src/data/contenido.js`. Todo el copy de
interfaz habla de tú mexicano, no de vos.

**Una sola fuente por dato.** El menú vive en `src/data/platillos.js`, el número
de WhatsApp en `src/data/negocio.js`, y los schemas de SEO se generan desde ahí
con `npm run seo`. Nunca duplicar un precio ni un teléfono en otro archivo.

**Nada se da por terminado sin medirlo.** Desde `kosecha/`, con el dev server en
el puerto 5179:

```bash
npm run verificar   # rueda, CTAs, teclado, reduced-motion en un navegador real
npm run auditar     # contraste, blancos de click de 44px, desborde de 320 a 1920
```

Las tres métricas de `auditar` tienen que dar cero. Los acentos de marca no
llegan a 4.5:1 sobre crema: para texto y botones van `--kos-naranja-hondo` y
`--kos-lima-hondo`.

## Entrega

`npm run exportar` arma el ZIP que el cliente sube a mano al hosting. Solo
cuando lo pide.
