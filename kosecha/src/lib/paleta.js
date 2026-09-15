/**
 * Color aproximado de cada ingrediente del menú.
 *
 * Sirve para dibujar el platillo mientras no está la foto: en vez de una
 * silueta genérica, cada bowl se arma con los colores de lo que lleva adentro,
 * así la rueda ya distingue una Kosecha de otra. Cuando lleguen los PNG
 * recortados, esto deja de usarse solo.
 */
const COLORES = [
  [/kale|espinaca|arúgula|lechuga|germinado|verde/i, '#4e7d2f'],
  [/col morada|cebolla morada/i, '#8d4a83'],
  [/col |coles/i, '#a9c46a'],
  [/jitomate|tomate/i, '#c8372c'],
  [/fresa/i, '#d63a4a'],
  [/arándano/i, '#4a3b7a'],
  [/toronja/i, '#e2735c'],
  [/melón/i, '#f0a45c'],
  [/uva/i, '#6b4a86'],
  [/manzana/i, '#c0392b'],
  [/pera/i, '#bcc76a'],
  [/zanahoria/i, '#e07b28'],
  [/jícama|pepino/i, '#dfe6c8'],
  [/aceituna/i, '#5c6b3a'],
  [/queso de cabra|queso panela|queso manchego|panela|mozzarella|feta|brie/i, '#f2ece0'],
  [/pollo|pavo/i, '#d9b478'],
  [/salmón/i, '#e2765a'],
  [/jamón serrano|salami|chorizo|carnes frías|lomo/i, '#b5544f'],
  [/atún/i, '#a8705c'],
  [/huevo/i, '#f4d98a'],
  [/almendra|nuez|cacahuate|semilla|ajonjolí/i, '#b98e58'],
  [/crutón|pan |tortilla/i, '#d7a55e'],
  [/pasta/i, '#e8d9a8'],
  [/aderezo de jamaica/i, '#9c2f4a'],
  [/aderezo de cilantro/i, '#6f9b3f'],
  [/aderezo de mango|mango habanero/i, '#efa42b'],
  [/tahini|miel|mostaza/i, '#d9a441'],
  [/aderezo|mayonesa|balsámico|aceite/i, '#e6d7b2'],
  [/vegetales|frutas/i, '#6f9b3f'],
  [/proteína/i, '#d9b478'],
  [/base/i, '#4e7d2f'],
];

const POR_DEFECTO = ['#4e7d2f', '#a9c46a', '#e8d9a8', '#d9b478'];

/** Colores de los ingredientes de un platillo, sin repetir. */
export function paletaDe(platillo) {
  const colores = [];
  for (const ingrediente of platillo.ingredientes ?? []) {
    const encontrado = COLORES.find(([patron]) => patron.test(ingrediente));
    if (encontrado && !colores.includes(encontrado[1])) colores.push(encontrado[1]);
  }
  return colores.length >= 3 ? colores.slice(0, 7) : [...colores, ...POR_DEFECTO].slice(0, 7);
}

/** Ruido determinístico: el mismo platillo se dibuja siempre igual. */
export function semilla(texto) {
  let h = 2166136261;
  for (let i = 0; i < texto.length; i += 1) {
    h ^= texto.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return () => {
    h += 0x6d2b79f5;
    let t = h;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}
