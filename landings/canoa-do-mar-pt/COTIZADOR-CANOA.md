# Canoa do Mar — Cotizador propio (PT + ES)

Aplica a `canoa-do-mar-pt/index-test-cotizador.html` y `canoa-do-mar-es/index-test-cotizador.html`
(los `index.html` originales no se tocaron).

## Por qué un cotizador propio (y no el widget de hBook)
El script de hBook de Canoa (`6875d90513003294de8d41ad.js`) **solo trae el chat** (HBot/Olark),
no el buscador de fechas — a la propiedad Canoa **no le está habilitado el módulo de motor
inline en hBook**. En vez de depender de eso, el cotizador es un **formulario nuestro**
(design system Caminué) que al enviar abre la página de reservas de hBook.

Ventajas: sin los bugs de posición del datepicker de jQuery UI, en móvil usa el **date picker
nativo del teléfono**, y no depende de que hBook active nada.

## Cómo funciona
Campos: fecha de entrada / salida (`<input type="date">`), adultos (1–5), niños (0–3).
Al enviar abre en pestaña nueva:

```
https://hbook.hsystem.com.br//Booking?companyId=6875d90513003294de8d41ad&language=<lang>
   &checkin=<fecha>&checkout=<fecha>&adults=<n>&children=<n>&<utms>
```

- **Formato de fecha por idioma** (igual que el datepicker de hBook): PT → `dd-mm-yyyy`,
  ES → `yyyy-mm-dd`.
- **UTMs**: reenvía los del anuncio (`utm_*`, `gclid`, `fbclid`, etc., persistidos en
  `sessionStorage`); defaults para tráfico directo (`utm_source=landing`,
  `utm_medium=cotizador_hero`, `utm_campaign=canoa-do-mar`).
- Emite `dataLayer.push({event:'cotizador_submit', ...})` para medición en GTM.

## Validado (arnés local)
- Render desktop (barra full-width) + mobile (apilado) en PT y ES.
- URL de reserva correcta: `companyId` de Canoa, fecha en el formato del idioma, `adults`/
  `children`, y UTMs (reenviados o defaults) — confirmado para PT y ES.

## ⚠️ Lo único a confirmar en tu server
No puedo abrir `hbook.hsystem.com.br` desde este entorno (red bloqueada). Hacé **una prueba
real**: enviá el cotizador y confirmá que la página de reservas de hBook abre con las **fechas
y huéspedes ya cargados**. Si el motor no toma las fechas en ese formato, avisame y ajusto
(`dd-mm-yyyy` ↔ `yyyy-mm-dd`).
