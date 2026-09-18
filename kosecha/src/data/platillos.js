/**
 * Menú de Kosecha.
 *
 * Fuente única: documento "MENÚ" (Drive › Kosecha). Descripciones textuales
 * del documento; nombres y precios sin alterar.
 *
 * Notas abiertas (ver ASSETS-TODO.md):
 *  - No hay macros publicadas: el campo `macros` queda en null y el panel no
 *    muestra el bloque. No inventar valores.
 *  - Los 5 sándwiches comparten una sola foto (`sandwich.png`); falta foto por
 *    variante.
 *  - Los smoothies y los paquetes salieron del menú: la marca todavía no los
 *    ofrece.
 */

export const categorias = [
  { id: 'ensaladas', nombre: 'Ensaladas', acento: 'var(--kos-lima)', precioDesde: 110 },
  { id: 'sandwiches', nombre: 'Sándwiches', acento: 'var(--kos-naranja)', precioDesde: 90 },
  { id: 'wraps', nombre: 'Wraps', acento: 'var(--kos-amarillo)', precioDesde: 80 },
  { id: 'personalizada', nombre: 'Arma la tuya', acento: 'var(--kos-verde)', precioDesde: 110 },
];

export const platillos = [
  {
    id: 'kosecha-mediterranea',
    nombre: 'Kosecha mediterránea',
    descripcion:
      'Espinacas, aderezo italiano, pasta, jitomate cherry, cebolla morada, aceitunas, queso de cabra y pollo a las finas hierbas.',
    ingredientes: [
      'Espinacas',
      'Aderezo italiano',
      'Pasta',
      'Jitomate cherry',
      'Cebolla morada',
      'Aceitunas',
      'Queso de cabra',
      'Pollo a las finas hierbas',
    ],
    macros: null,
    precio: 110,
    categoria: 'ensaladas',
    etiqueta: 'Especialidad del mes',
    imagen: '/assets/platillos/mediterranea.png',
    enRueda: true,
    destacado: true,
  },
  {
    id: 'kosecha-del-bosque',
    nombre: 'Kosecha del bosque',
    descripcion:
      'Mezcla de lechugas, aderezo de jamaica, fresas, arándanos, gajos de toronja, crutones, queso panela y jamón serrano.',
    ingredientes: [
      'Mezcla de lechugas',
      'Aderezo de jamaica',
      'Fresas',
      'Arándanos',
      'Gajos de toronja',
      'Crutones',
      'Queso panela',
      'Jamón serrano',
    ],
    macros: null,
    precio: 110,
    categoria: 'ensaladas',
    etiqueta: 'Especialidad del mes',
    imagen: '/assets/platillos/bosque.png',
    enRueda: true,
    destacado: false,
  },
  {
    id: 'kosecha-surena',
    nombre: 'Kosecha sureña',
    descripcion:
      'Mezcla de lechugas, aderezo de cilantro, pasta, jitomates, cebolla morada, semillas caramelizadas y pollo al limón.',
    ingredientes: [
      'Mezcla de lechugas',
      'Aderezo de cilantro',
      'Pasta',
      'Jitomates',
      'Cebolla morada',
      'Semillas caramelizadas',
      'Pollo al limón',
    ],
    macros: null,
    precio: 110,
    categoria: 'ensaladas',
    etiqueta: 'Especialidad del mes',
    imagen: '/assets/platillos/surena.png',
    enRueda: true,
    destacado: false,
  },
  {
    id: 'kosecha-asiatica',
    nombre: 'Kosecha asiática',
    descripcion:
      'Mezcla de lechugas, aderezo de mango picante, pasta, jícama, melón, uvas, fresas, almendras y pollo picante.',
    ingredientes: [
      'Mezcla de lechugas',
      'Aderezo de mango picante',
      'Pasta',
      'Jícama',
      'Melón',
      'Uvas',
      'Fresas',
      'Almendras',
      'Pollo picante',
    ],
    macros: null,
    precio: 110,
    categoria: 'ensaladas',
    etiqueta: 'Especialidad del mes',
    imagen: '/assets/platillos/asiatica.png',
    enRueda: true,
    destacado: false,
  },
  {
    id: 'kosecha-de-coles',
    nombre: 'Kosecha de coles',
    descripcion:
      'Col morada y blanca, arúgula, aderezo de mayonesa al ajo, pepino, jícama, zanahoria, ajonjolí negro y pollo a la plancha.',
    ingredientes: [
      'Col morada y blanca',
      'Arúgula',
      'Aderezo de mayonesa al ajo',
      'Pepino',
      'Jícama',
      'Zanahoria',
      'Ajonjolí negro',
      'Pollo a la plancha',
    ],
    macros: null,
    precio: 110,
    categoria: 'ensaladas',
    etiqueta: 'Especialidad del mes',
    imagen: '/assets/platillos/coles.png',
    enRueda: true,
    destacado: false,
  },
  {
    id: 'kosecha-de-kale',
    nombre: 'Kosecha de kale',
    descripcion:
      'Hojas de kale, aderezo de miel y oliva, pasta, pepino, cebolla, jícama, zanahoria, semillas caramelizadas y pollo a las hierbas con parmesano.',
    ingredientes: [
      'Hojas de kale',
      'Aderezo de miel y oliva',
      'Pasta',
      'Pepino',
      'Cebolla',
      'Jícama',
      'Zanahoria',
      'Semillas caramelizadas',
      'Pollo a las hierbas con parmesano',
    ],
    macros: null,
    precio: 110,
    categoria: 'ensaladas',
    etiqueta: 'Especialidad del mes',
    imagen: '/assets/platillos/kale.png',
    enRueda: true,
    destacado: false,
  },
  {
    id: 'crea-tu-ensalada',
    nombre: 'Crea tu ensalada',
    descripcion:
      'Elige y mezcla: 1 Base + 1 Aderezo + 3 Vegetales/Frutas + 1 Carbohidrato + 1 Semilla + 1 Proteína.',
    ingredientes: [
      'Base a elegir',
      'Aderezo a elegir',
      '3 vegetales o frutas',
      '1 carbohidrato',
      '1 semilla',
      '1 proteína',
    ],
    macros: null,
    precio: 110,
    categoria: 'personalizada',
    etiqueta: 'A tu gusto',
    imagen: '/assets/platillos/personalizada.png',
    enRueda: true,
    destacado: false,
  },
  {
    id: 'sandwich-de-pollo',
    nombre: 'Sándwich de pollo',
    descripcion:
      'Pan Oroweat, mayonesa chipotle, lechuga, germinado, jitomate, cebolla morada, aguacate, queso panela y pollo.',
    ingredientes: [
      'Pan Oroweat',
      'Mayonesa chipotle',
      'Lechuga',
      'Germinado',
      'Jitomate',
      'Cebolla morada',
      'Aguacate',
      'Queso panela',
      'Pollo',
    ],
    macros: null,
    precio: 90,
    categoria: 'sandwiches',
    etiqueta: null,
    imagen: '/assets/platillos/sandwich.png',
    enRueda: false,
    destacado: false,
  },
  {
    id: 'sandwich-de-salmon',
    nombre: 'Sándwich de salmón',
    descripcion:
      'Pan Oroweat, mayonesa habanero, lechuga, germinado, jitomate, cebolla morada, queso panela, aguacate y salmón.',
    ingredientes: [
      'Pan Oroweat',
      'Mayonesa habanero',
      'Lechuga',
      'Germinado',
      'Jitomate',
      'Cebolla morada',
      'Queso panela',
      'Aguacate',
      'Salmón',
    ],
    macros: null,
    precio: 90,
    categoria: 'sandwiches',
    etiqueta: null,
    imagen: '/assets/platillos/sandwich.png',
    enRueda: false,
    destacado: false,
  },
  {
    id: 'sandwich-de-jamon-de-pavo',
    nombre: 'Sándwich de jamón de pavo',
    descripcion:
      'Pan Oroweat, mayonesa, lechuga, germinado, jitomate, cebolla morada, queso panela, aguacate y jamón de pavo.',
    ingredientes: [
      'Pan Oroweat',
      'Mayonesa',
      'Lechuga',
      'Germinado',
      'Jitomate',
      'Cebolla morada',
      'Queso panela',
      'Aguacate',
      'Jamón de pavo',
    ],
    macros: null,
    precio: 90,
    categoria: 'sandwiches',
    etiqueta: null,
    imagen: '/assets/platillos/sandwich.png',
    enRueda: false,
    destacado: false,
  },
  {
    id: 'sandwich-de-jamon-serrano',
    nombre: 'Sándwich de jamón serrano',
    descripcion:
      'Pan Oroweat, mayonesa, espinacas, germinado, jitomate, queso manchego, aguacate y jamón serrano.',
    ingredientes: [
      'Pan Oroweat',
      'Mayonesa',
      'Espinacas',
      'Germinado',
      'Jitomate',
      'Queso manchego',
      'Aguacate',
      'Jamón serrano',
    ],
    macros: null,
    precio: 90,
    categoria: 'sandwiches',
    etiqueta: null,
    imagen: '/assets/platillos/sandwich.png',
    enRueda: false,
    destacado: false,
  },
  {
    id: 'sandwich-de-carnes-frias',
    nombre: 'Sándwich de carnes frías',
    descripcion:
      'Pan Oroweat, mayonesa, espinacas, germinado, jitomate, queso manchego, jamón serrano, chorizo Pamplona y salami.',
    ingredientes: [
      'Pan Oroweat',
      'Mayonesa',
      'Espinacas',
      'Germinado',
      'Jitomate',
      'Queso manchego',
      'Jamón serrano',
      'Chorizo Pamplona',
      'Salami',
    ],
    macros: null,
    precio: 90,
    categoria: 'sandwiches',
    etiqueta: null,
    imagen: '/assets/platillos/sandwich.png',
    enRueda: false,
    destacado: false,
  },
  {
    id: 'wrap-kosecha',
    nombre: 'Wrap Kosecha',
    descripcion:
      'Tortilla de harina, aderezo, lechugas, vegetales, proteína (no lleva semillas).',
    ingredientes: [
      'Tortilla de harina',
      'Aderezo a elegir',
      'Lechugas',
      'Vegetales',
      'Proteína a elegir',
    ],
    macros: null,
    precio: 80,
    categoria: 'wraps',
    etiqueta: 'Todas las ensaladas se pueden hacer wrap',
    imagen: '/assets/platillos/wrap.png',
    enRueda: false,
    destacado: false,
  },
];

/** Los 7 platillos que giran en la rueda del hero, en orden. */
export const platillosRueda = platillos.filter((p) => p.enRueda);

export const acentoDe = (categoriaId) =>
  categorias.find((c) => c.id === categoriaId)?.acento ?? 'var(--kos-verde)';

/** El armador del menú, tal cual el documento MENÚ. */
export const armador = {
  titulo: 'Crea tu ensalada a tu gusto',
  precio: 110,
  instruccion:
    'Elige y mezcla: 1 Base + 1 Aderezo + 3 Vegetales/Frutas + 1 Carbohidrato + 1 Semilla + 1 Proteína',
  pasos: [
    { n: 1, nombre: 'Base', opciones: ['Arúgula', 'Coles mixtas', 'Germinado', 'Kale', 'Lechugas mix'] },
    {
      n: 2,
      nombre: 'Aderezo',
      opciones: ['Aceite de oliva', 'Balsámico', 'Cilantro', 'Italiano', 'Mango habanero', 'Miel mostaza', 'Mil islas', 'Ranch'],
    },
    {
      n: 3,
      nombre: 'Frutas y vegetales',
      opciones: ['Aceitunas', 'Fresa', 'Jitomate bola', 'Jitomate cherry', 'Manzana', 'Melón', 'Pepino', 'Pera', 'Pimientos', 'Toronja', 'Cebolla', 'Jícama', 'Zanahoria'],
    },
    { n: 4, nombre: 'Carbohidratos', opciones: ['Crutones', 'Pasta', 'Tiras de tortilla frita'] },
    {
      n: 5,
      nombre: 'Semillas',
      opciones: ['Ajonjolí', 'Almendra', 'Cacahuate', 'Nuez', 'Semillas de calabaza', 'Semillas caramelizadas', 'Semillas de girasol'],
    },
    {
      n: 6,
      nombre: 'Proteínas',
      opciones: ['Atún', 'Huevo', 'Pollo a la plancha', 'Pollo al limón', 'Pollo a las hierbas', 'Pollo picante', 'Queso panela'],
    },
  ],
  /** Cada extra suma $20 al platillo. */
  precioExtra: 20,
  extras: [
    {
      nombre: 'Quesos',
      opciones: ['Cabra (ceniza, arándano o clásico)', 'Azul', 'Feta', 'Brie', 'Parmesano', 'Mozzarella', 'Manchego', 'Monterrey Jack'],
    },
    { nombre: 'Vegetales', opciones: ['Palmito', 'Alcachofa', 'Elotitos'] },
    { nombre: 'Carnes', opciones: ['Jamón serrano', 'Lomo', 'Salami', 'Salmón'] },
  ],
};
