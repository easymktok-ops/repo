// ✏️ Menú real de Dino Snacks (tomado de dinosnacksmenu.netlify.app). Precios en MXN.

export const BUSINESS = {
  name: "Dino Snacks",
  whatsapp: "522761028871",
  tz: "America/Mexico_City",
  openHour: 14, // 2:00 pm
  closeHour: 22 // 10:00 pm
};

export const CATEGORIES = [
  { id: "all", name: "Todo" },
  { id: "alimentos", name: "Botanas y comida" },
  { id: "fresas", name: "Fresas especiales" },
  { id: "bebidas", name: "Bebidas" },
  { id: "antojos", name: "Antojos" }
];

const COFFEE = ["Capuchino", "Oreo", "Moka", "Taro", "Caramelo + capuchino", "Piña colada", "Cajeta", "Fresas con crema", "Pay de limón", "Mazapán", "Chai manzana", "Pumpkin spice"];
const SODA = ["Mora azul", "Fresa", "Sandía", "Maracuyá", "Mango Tootsie pop", "Manzana verde"];
const flavor = (list) => ({ id: "sabor", label: "Sabor", required: true, choices: list.map((l) => ({ label: l })) });

export const PRODUCTS = [
  { id: "vaso-botanero", cat: "alimentos", name: "Vaso botanero", price: 40, img: "vaso-botanero", tag: "Favorito",
    desc: "Papas o verdura, gomitas, cacahuate y salsas.",
    options: [
      { id: "tamano", label: "Tamaño", required: true, choices: [{ label: "Chico" }, { label: "Grande", extra: 15 }] },
      { id: "base", label: "Base", required: true, choices: [{ label: "Papas" }, { label: "Verdura" }] }
    ] },
  { id: "dorilocos", cat: "alimentos", name: "Dorilocos", price: 40, img: "dorilocos", tag: "Picosito",
    desc: "Doritos, jícama, zanahoria, cacahuate, gomitas y chamoy." },
  { id: "cono-papas", cat: "alimentos", name: "Cono de papas", price: 55, img: "cono-papas",
    desc: "Papas a la francesa sazonadas y bañadas en queso.",
    options: [{ id: "sazon", label: "Sazonador", required: true, choices: [{ label: "Limón con pimienta" }, { label: "Limón picosito" }, { label: "Sal" }] }] },
  { id: "dino-nuggets", cat: "alimentos", name: "Dino nuggets", price: 55, img: "dino-nuggets",
    desc: "7 nuggets de pollo con forma de dinosaurio." },
  { id: "papas-gajo", cat: "alimentos", name: "Papas gajo", price: 40, img: "papas-gajo",
    desc: "Papas de corte grueso, fritas y sazonadas." },
  { id: "maruchan", cat: "alimentos", name: "Maruchan preparada", price: 25, img: "maruchan",
    desc: "Con salsas y limón." },
  { id: "ramen-buldak", cat: "alimentos", name: "Ramen Buldak", price: 70, img: "buldak", tag: "Reto",
    desc: "Ramen coreano preparado.",
    options: [{ id: "sabor", label: "Sabor", required: true, choices: [{ label: "Queso" }, { label: "Carbonara" }, { label: "Picante" }] }] },
  { id: "banderilla-coreana", cat: "alimentos", name: "Banderilla coreana", price: 35, img: "banderilla-coreana",
    desc: "Salchicha de pavo con cubos de papa.",
    options: [{ id: "cubierta", label: "Cubierta", required: true, choices: [{ label: "Flamin' Hot" }, { label: "Queso en polvo" }] }] },
  { id: "banderilla-crujiente", cat: "alimentos", name: "Banderilla crujiente", price: 30, img: "banderilla-crujiente",
    desc: "Salchicha de pavo con cubierta crujiente." },
  { id: "banderilla-sencilla", cat: "alimentos", name: "Banderilla sencilla", price: 25, img: "banderilla-sencilla",
    desc: "Salchicha de pavo con cubierta frita." },
  { id: "manzanas", cat: "alimentos", name: "Manzanas cubiertas", price: 50, img: "manzanas",
    desc: "Con tamarindo, gomitas, cacahuate y chamoy." },
  { id: "fresas-crema", cat: "alimentos", name: "Fresas con crema", price: 75, img: "fresas-crema",
    desc: "Fresa natural con crema de la casa y crema batida." },

  { id: "fresas-dubai", cat: "fresas", name: "Fresas Dubai", price: 135, img: "fresas-dubai", tag: "El antojo del mes",
    desc: "Fresas con chocolate, crema de pistache y kataifi." },
  { id: "fresas-chamoy", cat: "fresas", name: "Fresas chamoy", price: 100, img: "fresas-chamoy", desc: "Bañadas en chamoy con chilito." },
  { id: "fresas-raffaello", cat: "fresas", name: "Fresas Raffaello", price: 100, img: "fresas-raffaello", desc: "Con coco y chocolate blanco." },
  { id: "fresas-oreo", cat: "fresas", name: "Fresas Oreo", price: 100, img: "fresas-oreo", desc: "Con galleta Oreo triturada." },
  { id: "fresas-lotus", cat: "fresas", name: "Fresas Lotus", price: 100, img: "fresas-lotus", desc: "Con crema y galleta Lotus." },
  { id: "fresas-gansito", cat: "fresas", name: "Fresas Gansito", price: 100, img: "fresas-gansito", desc: "Con trozos de Gansito y chocolate." },
  { id: "fresas-clasicas", cat: "fresas", name: "Fresas clásicas", price: 85, img: "fresas-clasicas", desc: "Fresas con crema, como siempre." },

  { id: "perlas", cat: "bebidas", name: "Perlas explosivas", price: 65, img: "perlas", tag: "Truenan", desc: "Soda con perlas que explotan al morderlas.", options: [flavor(SODA)] },
  { id: "limonada", cat: "bebidas", name: "Limonada arcoíris", price: 70, img: "limonada", desc: "Limonada con té azul y perlas explosivas." },
  { id: "frappe", cat: "bebidas", name: "Frappé", price: 70, img: "frappe", desc: "Escoge entre 12 sabores.", options: [flavor(COFFEE)] },
  { id: "bebida-fria", cat: "bebidas", name: "Bebida fría", price: 65, img: "bebida-fria", desc: "Escoge entre 12 sabores.", options: [flavor(COFFEE)] },
  { id: "cafe", cat: "bebidas", name: "Bebida caliente", price: 65, img: "cafe", desc: "Escoge entre 12 sabores.", options: [flavor(COFFEE)] },
  { id: "italiana", cat: "bebidas", name: "Soda italiana", price: 45, img: "italiana", desc: "Burbujeante y con crema.", options: [flavor(SODA)] },
  { id: "icepop", cat: "bebidas", name: "Ice pop", price: 65, img: "icepop", desc: "Bebida helada con paleta.", options: [flavor(["Mora azul", "Mango chamoy", "Limonada rosa"])] },

  { id: "vaquitas", cat: "antojos", name: "Vaquitas locas", price: 35, img: "vaquitas", desc: "Dulces de leche preparados." },
  { id: "gomiboing", cat: "antojos", name: "Gomi Boing", price: 40, img: "gomiboing", desc: "Gomitas enchiladas en vaso." }
];

// Constructor de Dino-vaso
export const VASO = {
  size: { type: "one", items: [
    { id: "chico", label: "Chico", price: 40, fill: 0.78 },
    { id: "grande", label: "Grande", price: 55, fill: 0.95 }
  ] },
  base: { type: "one", items: [
    { id: "papas", label: "Papas", color: "#f4c542" },
    { id: "verdura", label: "Verdura", color: "#9fd36a" },
    { id: "mixto", label: "Mitad y mitad", color: "#d9cf55" }
  ] },
  tops: { type: "many", included: 3, extra: 5, items: [
    { id: "gomitas", label: "Gomitas", color: "#e8333a", word: "Gomi" },
    { id: "cacahuate", label: "Cacahuate", color: "#b07a3c", word: "Crunch" },
    { id: "jicama", label: "Jícama", color: "#f2ecd8", word: "Fresh" },
    { id: "pepino", label: "Pepino", color: "#5fbf5a", word: "Verde" },
    { id: "mango", label: "Mango", color: "#ffb02e", word: "Tropi" },
    { id: "skwinkles", label: "Skwinkles", color: "#ff5fa2", word: "Skwink" },
    { id: "pulparindo", label: "Pulparindo", color: "#7a3b1d", word: "Tama" }
  ] },
  sauce: { type: "many", items: [
    { id: "chamoy", label: "Chamoy", color: "#b3122b", word: "Chamoy" },
    { id: "valentina", label: "Valentina", color: "#d9480f", word: "Fuego" },
    { id: "miguelito", label: "Miguelito", color: "#e03b6b", word: "Miguel" },
    { id: "tajin", label: "Tajín", color: "#c2410c", word: "Tajín" }
  ] }
};
