# Product

## Register

brand

## Users

Dos audiencias, un mismo momento de alta consideración (ticket alto, compra única
memorable):

- **Viajero internacional** (EE. UU., Europa) planeando una experiencia "una vez
  en la vida" cerca de Ciudad de México. Llega en inglés, compara contra
  experiencias premium globales, decide por percepción de marca y confianza.
- **Viajero nacional / regalo** (parejas, aniversarios, cumpleaños). Llega en
  español, sensible a precio y disponibilidad, quiere ver rápido qué hay y
  apartar sin fricción.

Contexto de uso: mayormente móvil, a menudo de noche planeando el amanecer
siguiente, decidiendo entre varias opciones abiertas en pestañas. "Lo que no se
ve rápido no se vende."

## Product Purpose

Plataforma de marca + reservas para Aerodiverti (vuelos en globo al amanecer
sobre el valle de Teotihuacán). Debe igualar y superar el nivel visual y el flujo
de venta de referencias internacionales de alta gama, con Core Web Vitals casi
perfectos, SEO agresivo (incluida citabilidad por LLMs) y autoadministración a
prueba de errores para el dueño. Éxito = más reservas directas, menor
dependencia de intermediarios, y una percepción de marca claramente por encima
de la competencia local.

## Brand Personality

Cinematográfica, serena, precisa. Tres palabras: **alta, tangible, confiable**.
No es "aventura extrema" ni "tour turístico genérico": es un amanecer silencioso
a gran altura, con la ingeniería y el rigor de la aviación detrás (pilotos AFAC,
reprogramación sin costo). La voz es concreta y calmada, nunca grandilocuente.
Emociones objetivo: anticipación, calma, confianza.

## Anti-references

- El reflejo turístico "globo + dorado / amanecer naranja saturado" generado por
  IA. La lectura de amanecer viene de la fotografía y el copy, no de un dorado de
  paleta.
- La estructura de "landing genérica generada rápido" del sitio de referencia:
  hero → trust bar → cards de producto → why-us de 4 iconos → pasos 01/02/03 →
  reviews → FAQ → CTA, todo con iconografía de stroke genérica y sin un solo
  momento visual sorpresivo.
- Estética SaaS / editorial-magazine por defecto (display serif italic + drop
  caps + grid de tres columnas iguales). Iconos Lucide por defecto.
- El bug de confianza del sistema anterior: reservas "sin correo enviado". La
  notificación transaccional debe ser observable y con reintentos, nunca fire and
  forget.

## Design Principles

1. **El amanecer se muestra, no se colorea.** La emoción viene de imagen, video y
   tipografía; la paleta es obsidiana + cal + un solo acento de cielo. Nada de
   dorado turístico de relleno.
2. **Rompe el ritmo predecible.** No todas las secciones pesan igual. La doble
   exposición del globo es un momento hero, no un adorno. Cada sección merece su
   propia dirección de arte si el relato lo pide.
3. **Lo que no se ve rápido no se vende.** El catálogo es escaneable sin scroll
   excesivo y el precio "Desde $X" siempre visible. Densidad mayor en catálogo,
   aire editorial en el resto.
4. **Confianza como material.** Pilotos AFAC, reprogramación sin costo, pago
   seguro y reseñas reales 4.9 no son adornos: son estructura. Y la confianza
   operativa (que el correo de confirmación llegue) se diseña y se monitorea.
5. **Citable por humanos y por LLMs.** Frases autocontenidas que respondan la
   pregunta completa ("¿cuánto cuesta volar en globo en Teotihuacán?") en una
   oración clara, no dispersas en fragmentos de UI.

## Accessibility & Inclusion

Objetivo WCAG 2.2 AA. Contraste de cuerpo ≥ 4.5:1 verificado sobre la base
obsidiana (los tokens ya se eligieron con ese margen). Todo movimiento por encima
del dial 3 tiene alternativa `prefers-reduced-motion`. Navegación por teclado con
foco visible (anillo de acento) y skip-link. Bilingüe es/en con `hreflang`
correcto. `alt` descriptivo y real como parte de la voz de marca, no relleno.
