import { BUSINESS, CATEGORIES, PRODUCTS, VASO } from "./data.js";
import { mountDinos, heroLane, scrollLanes, chomp, reducedMotion, pelletSVG } from "./dino.js";
import { createGame } from "./game.js";

/* ---------- utilidades ---------- */
const $ = (s, el = document) => el.querySelector(s);
const $$ = (s, el = document) => [...el.querySelectorAll(s)];
const money = (n) => `$${n.toLocaleString("es-MX")}`;
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
const imgSrc = (k) => `img/${k}.webp`;
const EASE = "cubic-bezier(0.23, 1, 0.32, 1)";
const EASE_IO = "cubic-bezier(0.77, 0, 0.175, 1)";

const store = {
  get(k, d) { try { const v = localStorage.getItem(`dino:${k}`); return v ? JSON.parse(v) : d; } catch { return d; } },
  set(k, v) { try { localStorage.setItem(`dino:${k}`, JSON.stringify(v)); } catch { /* sin almacenamiento */ } }
};

const S = {
  cart: store.get("cart", []),
  points: store.get("points", 0),
  best: store.get("best", 0),
  order: store.get("order", null),
  usePoints: false,
  slot: null,
  name: store.get("name", ""),
  cat: "all",
  q: ""
};
const save = () => { store.set("cart", S.cart); store.set("points", S.points); store.set("best", S.best); store.set("order", S.order); store.set("name", S.name); };

function toast(msg) {
  const host = $("#toasts");
  const el = document.createElement("div");
  el.className = "toast";
  el.innerHTML = msg;
  host.append(el);
  setTimeout(() => { el.classList.add("out"); setTimeout(() => el.remove(), 220); }, 2600);
}

/* ---------- horario (hora de Ciudad de México) ---------- */
function nowMx() {
  const p = Object.fromEntries(new Intl.DateTimeFormat("en-US", { timeZone: BUSINESS.tz, hour: "numeric", minute: "numeric", hourCycle: "h23" })
    .formatToParts(new Date()).map((x) => [x.type, x.value]));
  return +p.hour * 60 + +p.minute;
}
const fmtTime = (m) => { const h = Math.floor(m / 60), mm = m % 60; const h12 = ((h + 11) % 12) + 1; return `${h12}:${String(mm).padStart(2, "0")} ${h < 12 ? "am" : "pm"}`; };
const isOpen = () => { const m = nowMx(); return m >= BUSINESS.openHour * 60 && m < BUSINESS.closeHour * 60; };

function renderStatus() {
  const el = $("#openStatus");
  const open = isOpen();
  el.classList.toggle("is-closed", !open);
  el.lastElementChild.textContent = open ? "Abierto ahora · cerramos a las 10:00 pm" : "Cerrado ahorita · abrimos a las 2:00 pm, ya puedes apartar tu hora";
}

function pickupSlots() {
  const now = nowMx(), open = BUSINESS.openHour * 60, close = BUSINESS.closeHour * 60;
  let start = Math.ceil((now + 15) / 15) * 15, day = "Hoy";
  if (start < open) start = open;
  if (start > close - 15) { start = open; day = "Mañana"; }
  const out = [];
  for (let m = start; m <= close - 15 && out.length < 14; m += 15) {
    const h = (m * 7 + (day === "Hoy" ? 3 : 11)) % 10;
    const load = h < 4 ? 0 : h < 8 ? 1 : h < 9 ? 2 : 3; // 0 tranquilo · 1 normal · 2 se llena · 3 lleno
    out.push({ id: `${day}-${m}`, day, m, label: fmtTime(m), load: out.length === 0 ? Math.min(load, 1) : load });
  }
  return out;
}
const LOAD = ["Tranquilo", "Normal", "Se llena", "Lleno"];

/* ---------- menú ---------- */
function productCard(p, i) {
  const quick = !p.options?.length;
  const from = p.options?.some((o) => o.choices.some((c) => c.extra));
  return `<article class="card" style="--i:${i}">
    <button type="button" class="card-hit" data-open="${p.id}" aria-label="${esc(p.name)}, ${money(p.price)}. Ver opciones">
      <span class="card-img"><img src="${imgSrc(p.img)}" alt="" loading="lazy" width="640" height="640"></span>
      ${p.tag ? `<span class="card-tag">${esc(p.tag)}</span>` : ""}
      <span class="card-info">
        <span class="card-name">${esc(p.name)}</span>
        <span class="card-desc">${esc(p.desc || "")}</span>
        <span class="card-price">${from ? "<small>desde</small> " : ""}${money(p.price)}</span>
      </span>
    </button>
    <button type="button" class="card-add" ${quick ? `data-add="${p.id}"` : `data-open="${p.id}"`} aria-label="Agregar ${esc(p.name)} al pedido">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
    </button>
  </article>`;
}

function renderTabs() {
  $("#tabs").innerHTML = CATEGORIES.map((c) =>
    `<button type="button" role="tab" class="tab" aria-selected="${c.id === S.cat}" data-cat="${c.id}">${c.name}</button>`).join("");
}

function renderGrid() {
  const q = S.q.trim().toLowerCase();
  const grid = $("#grid");
  let list = PRODUCTS.filter((p) => S.cat === "all" || p.cat === S.cat);
  if (q) list = list.filter((p) => `${p.name} ${p.desc} ${(p.options || []).flatMap((o) => o.choices.map((c) => c.label)).join(" ")}`.toLowerCase().includes(q));
  if (!list.length) {
    grid.innerHTML = `<div class="empty"><span class="empty-dino" data-dino="mini"></span><p>El dino ya se comió todo lo que se parece a "<strong>${esc(S.q)}</strong>". Prueba con chamoy, fresas o frappé.</p></div>`;
    mountDinos();
    return;
  }
  if (S.cat === "all" && !q) {
    grid.innerHTML = CATEGORIES.slice(1).map((c) => {
      const items = list.filter((p) => p.cat === c.id);
      return `<h3 class="grid-head">${c.name} <span>${items.length}</span></h3><div class="cards">${items.map(productCard).join("")}</div>`;
    }).join("");
  } else {
    grid.innerHTML = `<div class="cards">${list.map(productCard).join("")}</div>`;
  }
}

/* ---------- diálogo de producto ---------- */
let current = null;
function openProduct(id) {
  const p = PRODUCTS.find((x) => x.id === id);
  if (!p) return;
  current = { p, qty: 1 };
  $("#pdImg").src = imgSrc(p.img);
  $("#pdImg").alt = p.name;
  $("#pdName").textContent = p.name;
  $("#pdDesc").textContent = p.desc || "";
  $("#pdNote").value = "";
  $("#pdQty").textContent = "1";
  $("#pdOptions").innerHTML = (p.options || []).map((o) => `<fieldset class="opt">
      <legend>${esc(o.label)} ${o.required ? "<small>Elige uno</small>" : ""}</legend>
      <div class="chips">${o.choices.map((c, i) => `<label class="chip"><input type="radio" name="${o.id}" value="${i}" ${i === 0 ? "checked" : ""}><span>${esc(c.label)}${c.extra ? ` <em>+${money(c.extra)}</em>` : ""}</span></label>`).join("")}</div>
    </fieldset>`).join("");
  updatePdPrice();
  $("#productDialog").showModal();
}
function pdUnitPrice() {
  const { p } = current;
  let price = p.price;
  for (const o of p.options || []) {
    const i = +($(`#pdOptions input[name="${o.id}"]:checked`)?.value ?? 0);
    price += o.choices[i]?.extra || 0;
  }
  return price;
}
function updatePdPrice() { $("#pdAdd").textContent = `Agregar · ${money(pdUnitPrice() * current.qty)}`; }

function addToCart(item, fromEl) {
  const key = `${item.id}|${JSON.stringify(item.opts)}|${item.note || ""}`;
  const found = S.cart.find((c) => c.key === key);
  if (found) found.qty += item.qty; else S.cart.push({ ...item, key });
  save();
  flyToCart(fromEl, item.img);
  renderCartBadge(true);
  toast(`<strong>${esc(item.name)}</strong> va en tu pedido`);
}

function flyToCart(fromEl, img) {
  const target = $("#cartBtn");
  if (!fromEl || reducedMotion()) return;
  const a = fromEl.getBoundingClientRect(), b = target.getBoundingClientRect();
  const dot = document.createElement("div");
  dot.className = "fly";
  dot.style.backgroundImage = `url(${imgSrc(img)})`;
  const size = 56;
  dot.style.left = `${a.left + a.width / 2 - size / 2}px`;
  dot.style.top = `${a.top + a.height / 2 - size / 2}px`;
  document.body.append(dot);
  const dx = b.left + 22 - (a.left + a.width / 2), dy = b.top + b.height / 2 - (a.top + a.height / 2);
  dot.animate([
    { transform: "translate(0,0) scale(1)", opacity: 1 },
    { transform: `translate(${dx * 0.5}px, ${dy * 0.5 - 60}px) scale(.8)`, opacity: 1, offset: 0.55 },
    { transform: `translate(${dx}px, ${dy}px) scale(.25)`, opacity: 0.4 }
  ], { duration: 620, easing: EASE_IO }).finished.then(() => { dot.remove(); chomp($(".cart-dino")); });
}

/* ---------- carrito ---------- */
const cartCount = () => S.cart.reduce((n, c) => n + c.qty, 0);
const subtotal = () => S.cart.reduce((n, c) => n + c.price * c.qty, 0);
const discount = () => (S.usePoints && S.points >= 100 ? 10 : 0);

function renderCartBadge(bump) {
  const n = cartCount();
  const el = $("#cartCount");
  el.textContent = n;
  $("#cartBtn").classList.toggle("has-items", n > 0);
  if (bump && !reducedMotion()) el.animate([{ transform: "scale(1)" }, { transform: "scale(1.35)" }, { transform: "scale(1)" }], { duration: 320, easing: EASE, delay: 520 });
  $("#dock").hidden = n === 0;
  $("#dockCount").textContent = `${n} ${n === 1 ? "cosa" : "cosas"}`;
  $("#dockTotal").textContent = money(subtotal());
}

function optsText(o) { return Object.entries(o || {}).map(([k, v]) => `${k}: ${v}`).join(" · "); }

function renderCart() {
  const body = $("#cartBody");
  if (!S.cart.length) {
    body.innerHTML = `<div class="cart-empty"><span class="empty-dino" data-dino="mini"></span><p>Tu pedido está vacío y el dino tiene hambre.</p><button type="button" class="btn btn-ink" data-close-go="#menu">Ir al menú</button></div>`;
    mountDinos();
    return;
  }
  const slots = pickupSlots();
  if (!S.slot || !slots.some((s) => s.id === S.slot && s.load < 3)) S.slot = slots.find((s) => s.load < 3)?.id || null;
  const total = Math.max(0, subtotal() - discount());
  body.innerHTML = `
    <ul class="lines">${S.cart.map((c, i) => `<li class="line">
      <img src="${imgSrc(c.img)}" alt="" width="64" height="64">
      <div class="line-info"><strong>${esc(c.name)}</strong>${c.opts && Object.keys(c.opts).length ? `<small>${esc(optsText(c.opts))}</small>` : ""}${c.note ? `<small>“${esc(c.note)}”</small>` : ""}<span>${money(c.price * c.qty)}</span></div>
      <div class="qty qty-sm" role="group" aria-label="Cantidad de ${esc(c.name)}">
        <button type="button" data-line="${i}" data-d="-1" aria-label="Quitar uno">−</button><output>${c.qty}</output><button type="button" data-line="${i}" data-d="1" aria-label="Agregar uno">+</button>
      </div></li>`).join("")}</ul>

    <fieldset class="slots">
      <legend>¿A qué hora pasas? <small>${slots[0]?.day === "Mañana" ? "Ya cerramos por hoy, aparta para mañana" : "Horarios de hoy"}</small></legend>
      <div class="slot-row">${slots.map((s) => `<label class="slot load-${s.load}">
        <input type="radio" name="slot" value="${s.id}" ${s.id === S.slot ? "checked" : ""} ${s.load === 3 ? "disabled" : ""}>
        <span class="slot-time">${s.label}</span><span class="slot-load"><i></i><i></i><i></i></span><span class="slot-tag">${LOAD[s.load]}</span></label>`).join("")}</div>
    </fieldset>

    <label class="field"><span>Nombre para el pedido</span><input id="cartName" value="${esc(S.name)}" autocomplete="given-name" placeholder="Como te dicen" maxlength="30" required></label>
    <label class="field"><span>Notas (opcional)</span><input id="cartNotes" maxlength="120" placeholder="Llego en bici, sin popote…"></label>

    <label class="points-toggle ${S.points < 100 ? "is-locked" : ""}">
      <input type="checkbox" id="usePoints" ${S.usePoints && S.points >= 100 ? "checked" : ""} ${S.points < 100 ? "disabled" : ""}>
      <span><strong>Usar 100 Dino-puntos</strong> <small>${S.points >= 100 ? `Tienes ${S.points}. Te descontamos $10.` : `Tienes ${S.points}. Juega Dino-Chomp para juntar 100.`}</small></span>
    </label>

    <dl class="totals">
      <div><dt>Subtotal</dt><dd>${money(subtotal())}</dd></div>
      ${discount() ? `<div class="totals-disc"><dt>Dino-puntos</dt><dd>−${money(discount())}</dd></div>` : ""}
      <div class="totals-total"><dt>Total a pagar al recoger</dt><dd>${money(total)}</dd></div>
    </dl>
    <p class="earn">Con este pedido ganas <strong>${Math.floor(total / 10)} Dino-puntos</strong>.</p>
    <p class="form-error" id="cartError" role="alert" hidden></p>
    <div class="cart-actions">
      <button type="button" class="btn btn-ink btn-wide" id="confirmOrder">Apartar mi pedido · ${money(total)}</button>
      <button type="button" class="btn btn-wa btn-wide" id="waOrder">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3Z"/></svg>
        Enviar por WhatsApp</button>
    </div>`;
}

function waLink(order) {
  const lines = (order?.items || S.cart).map((c) => `• ${c.qty}x ${c.name}${c.opts && Object.keys(c.opts).length ? ` (${optsText(c.opts)})` : ""}${c.note ? ` “${c.note}”` : ""}: ${money(c.price * c.qty)}`);
  const slot = order?.slotLabel || slotLabel(S.slot);
  const total = order?.total ?? Math.max(0, subtotal() - discount());
  const name = order?.name || ($("#cartName")?.value || "").trim();
  const notes = order?.notes ?? ($("#cartNotes")?.value || "").trim();
  const text = [
    `Hola Dino Snacks 🦖 quiero pedir para recoger${order ? ` (código ${order.code})` : ""}:`,
    ...lines,
    `Total: ${money(total)}`,
    slot ? `Paso por él: ${slot}` : "",
    name ? `A nombre de: ${name}` : "",
    notes ? `Notas: ${notes}` : ""
  ].filter(Boolean).join("\n");
  return `https://wa.me/${BUSINESS.whatsapp}?text=${encodeURIComponent(text)}`;
}
function slotLabel(id) {
  const s = pickupSlots().find((x) => x.id === id);
  return s ? `${s.day} a las ${s.label}` : "";
}

function confirmOrder() {
  const name = $("#cartName").value.trim();
  const err = $("#cartError");
  if (!name) { err.hidden = false; err.textContent = "Escribe un nombre para entregarte el pedido correcto."; $("#cartName").focus(); return; }
  if (!S.slot) { err.hidden = false; err.textContent = "Escoge una hora para pasar por tu pedido."; return; }
  S.name = name;
  const total = Math.max(0, subtotal() - discount());
  if (discount()) S.points -= 100;
  const earned = Math.floor(total / 10);
  S.points += earned;
  S.order = {
    code: `DINO-${Math.floor(1000 + Math.random() * 9000)}`,
    locker: 1 + Math.floor(Math.random() * 12),
    slotLabel: slotLabel(S.slot),
    items: S.cart.map(({ key, ...c }) => c),
    total, name, earned,
    notes: $("#cartNotes").value.trim(),
    createdAt: Date.now(),
    onWay: false,
    pickedUp: false
  };
  S.cart = []; S.usePoints = false;
  save();
  renderCartBadge(); renderWallet();
  $("#cartDialog").close();
  openTicket();
  if (!reducedMotion()) confetti();
}

/* ---------- ticket y seguimiento ---------- */
const STAGES = ["Recibido", "Preparando", "En tu casillero", "Recogido"];
function orderStage(o) {
  if (!o) return -1;
  if (o.pickedUp) return 3;
  const s = (Date.now() - o.createdAt) / 1000;
  const k = o.onWay ? 0.6 : 1; // "voy en camino" acelera la cocina
  return s < 6 ? 0 : s < 20 * k ? 1 : 2;
}

let ticketTimer = 0;
function openTicket() {
  const o = S.order;
  if (!o) return;
  $("#ticketBody").innerHTML = `
    <p class="ticket-kicker">Pedido apartado · ${esc(o.slotLabel)}</p>
    <h2 id="ticketTitle" class="ticket-title">¡Listo, ${esc(o.name)}!</h2>
    <div class="ticket-card">
      <div class="ticket-qr" id="ticketQr" aria-label="Código QR para recoger"></div>
      <div class="ticket-meta">
        <p class="ticket-code">${o.code}</p>
        <p>Casillero <strong>#${o.locker}</strong></p>
        <p>Pagas <strong>${money(o.total)}</strong> al recoger</p>
      </div>
    </div>
    <div class="track" id="track" aria-label="Estado del pedido">
      <div class="track-rail"><div class="track-pellets">${Array.from({ length: 13 }, (_, i) => `<span class="pellet pellet-sm" style="left:${(i + 0.5) * (100 / 13)}%">${pelletSVG(i % 4 === 1 ? "gomita" : "dot", i)}</span>`).join("")}</div>
      <div class="track-dino" data-dino="mini"></div></div>
      <ol class="track-steps">${STAGES.map((s) => `<li>${s}</li>`).join("")}</ol>
    </div>
    <p class="track-msg" id="trackMsg" aria-live="polite"></p>
    <div class="ticket-actions">
      <button type="button" class="btn btn-yolk" id="onWay">Voy en camino</button>
      <button type="button" class="btn btn-ink" id="pickedUp">Escanear en el casillero</button>
      <a class="btn btn-wa" href="${waLink(o)}" target="_blank" rel="noopener">Mandar a WhatsApp</a>
    </div>
    <p class="ticket-note">Demo: el seguimiento va acelerado y el casillero es simulado.${o.earned ? ` Ganaste ${o.earned} Dino-puntos.` : ""}</p>`;
  mountDinos();
  const qr = $("#ticketQr");
  if (window.QRCode) new window.QRCode(qr, { text: `https://dinosnacks.mx/pickup/${o.code}`, width: 148, height: 148, colorDark: "#111111", colorLight: "#ffffff" });
  else qr.innerHTML = `<span class="qr-fallback">${o.code}</span>`;
  if (!$("#ticketDialog").open) $("#ticketDialog").showModal();
  tickTicket();
  clearInterval(ticketTimer);
  ticketTimer = setInterval(tickTicket, 1000);
}

function tickTicket() {
  const o = S.order;
  const st = orderStage(o);
  renderBanner();
  const track = $("#track");
  if (!track) return;
  const pct = [6, 36, 66, 94][st];
  const dino = $(".track-dino", track);
  dino.style.left = `${pct}%`;
  $$(".track-pellets .pellet", track).forEach((p) => p.classList.toggle("eaten", parseFloat(p.style.left) < pct));
  $$(".track-steps li", track).forEach((li, i) => { li.classList.toggle("done", i < st); li.classList.toggle("now", i === st); });
  const msg = [
    "La cocina ya vio tu pedido.",
    o.onWay ? "Le avisamos a la cocina que vienes. Tus cosas salen calientitas." : "Están preparando tu pedido.",
    `Tu pedido te espera en el casillero #${o.locker}. Escanea tu código para abrirlo.`,
    "¡Provecho! Gracias por pedir en Dino Snacks."
  ][st];
  const m = $("#trackMsg");
  if (m.textContent !== msg) m.textContent = msg;
  $("#onWay").disabled = o.onWay || st >= 2;
  $("#onWay").textContent = o.onWay ? "Ya saben que vienes" : "Voy en camino";
  $("#pickedUp").disabled = st < 2 || st === 3;
  $("#pickedUp").textContent = st === 3 ? "Recogido" : st < 2 ? "Se activa cuando esté listo" : "Escanear en el casillero";
  if (st === 3) clearInterval(ticketTimer);
}

function renderBanner() {
  const o = S.order, st = orderStage(o);
  const b = $("#orderBanner");
  b.hidden = !o || st === 3;
  if (!o || st === 3) return;
  $("#orderBannerText").textContent = st === 2 ? `${o.code} está listo en el casillero #${o.locker}` : `${o.code} · ${STAGES[st]} · ${o.slotLabel}`;
  b.classList.toggle("is-ready", st === 2);
}

function confetti() {
  const host = document.createElement("div");
  host.className = "confetti";
  host.innerHTML = Array.from({ length: 18 }, (_, i) => `<span>${pelletSVG(i % 3 === 0 ? "fresa" : i % 3 === 1 ? "gomita" : "papa", i)}</span>`).join("");
  document.body.append(host);
  $$("span", host).forEach((s, i) => {
    const x = (Math.random() - 0.5) * innerWidth * 0.9, y = innerHeight * (0.6 + Math.random() * 0.4);
    s.animate([
      { transform: "translate(0,0) rotate(0deg)", opacity: 1 },
      { transform: `translate(${x}px, ${-y * 0.5}px) rotate(${Math.random() * 360}deg)`, opacity: 1, offset: 0.4 },
      { transform: `translate(${x * 1.2}px, ${y * 0.3}px) rotate(${Math.random() * 720}deg)`, opacity: 0 }
    ], { duration: 1400 + i * 30, easing: "cubic-bezier(0.2, 0.6, 0.35, 1)" });
  });
  setTimeout(() => host.remove(), 2200);
}

/* ---------- Dino-vaso ---------- */
const V = { size: "chico", base: "papas", tops: new Set(["gomitas", "cacahuate", "jicama"]), sauce: new Set(["chamoy"]) };
function renderBuilderChips() {
  for (const [group, def] of Object.entries(VASO)) {
    const host = $(`#vasoForm [data-group="${group}"]`);
    host.innerHTML = def.items.map((it) => {
      const checked = def.type === "one" ? V[group] === it.id : V[group].has(it.id);
      return `<label class="chip chip-dark"><input type="${def.type === "one" ? "radio" : "checkbox"}" name="v-${group}" value="${it.id}" ${checked ? "checked" : ""}>
        <span>${it.color ? `<i style="background:${it.color}"></i>` : ""}${esc(it.label)}${it.price ? ` <em>${money(it.price)}</em>` : ""}</span></label>`;
    }).join("");
  }
}
const vasoPrice = () => VASO.size.items.find((s) => s.id === V.size).price + Math.max(0, V.tops.size - VASO.tops.included) * VASO.tops.extra;
function vasoName() {
  const s = [...V.sauce][0], t = [...V.tops];
  const w1 = s ? VASO.sauce.items.find((x) => x.id === s).word : t[0] ? VASO.tops.items.find((x) => x.id === t[0]).word : "Clásico";
  const w2 = ["Rex", "Raptor", "Saurio", "Bronto", "Tricera"][(t.length + (s ? s.length : 0) + V.base.length) % 5];
  return `${w1} ${w2}`;
}

let prevLayers = new Set();
function renderCup(dropColor) {
  const g = $("#cupLayers");
  const top = 20, bottom = 236, H = bottom - top;
  const fill = VASO.size.items.find((s) => s.id === V.size).fill;
  const surface = bottom - H * fill;
  const baseCol = VASO.base.items.find((b) => b.id === V.base).color;
  const tops = [...V.tops].map((id) => VASO.tops.items.find((x) => x.id === id));
  const sauces = [...V.sauce].map((id) => VASO.sauce.items.find((x) => x.id === id));
  const baseH = (bottom - surface) * (tops.length ? 0.55 : 1);
  const bandH = tops.length ? (bottom - surface - baseH) / tops.length : 0;
  let y = bottom - baseH;
  const parts = [`<g data-k="base-${V.base}"><rect x="0" y="${y}" width="200" height="${baseH + 4}" fill="${baseCol}"/>${Array.from({ length: 14 }, (_, i) => `<rect x="${18 + (i * 37) % 160}" y="${y + 10 + (i * 23) % Math.max(10, baseH - 20)}" width="${V.base === "verdura" ? 8 : 22}" height="${V.base === "verdura" ? 22 : 7}" rx="3" fill="rgba(0,0,0,.12)" transform="rotate(${(i * 29) % 50 - 25} ${30 + (i * 37) % 160} ${y + 20})"/>`).join("")}</g>`];
  tops.forEach((t, i) => {
    y -= bandH;
    parts.push(`<g data-k="top-${t.id}"><rect x="0" y="${y}" width="200" height="${bandH + 2}" fill="${t.color}"/>${Array.from({ length: 7 }, (_, j) => `<circle cx="${16 + j * 28 + (i % 2) * 12}" cy="${y + bandH / 2 + ((j % 2) - 0.5) * bandH * 0.35}" r="${Math.min(7, bandH / 3)}" fill="rgba(0,0,0,.16)"/>`).join("")}</g>`);
  });
  sauces.forEach((s, i) => {
    const sy = surface + 6 + i * 9;
    parts.push(`<g data-k="sauce-${s.id}"><path d="M0 ${sy} Q 25 ${sy - 10} 50 ${sy} T 100 ${sy} T 150 ${sy} T 200 ${sy} V ${sy + 7} Q 175 ${sy + 2} 150 ${sy + 7} T 100 ${sy + 7} T 50 ${sy + 7} T 0 ${sy + 7} Z" fill="${s.color}"/>${[30 + i * 40, 110 + i * 25].map((dx) => `<path d="M${dx} ${sy + 5} q4 ${18 + i * 6} 0 ${24 + i * 6} q-4 -6 0 -${24 + i * 6}" fill="${s.color}"/>`).join("")}</g>`);
  });
  g.innerHTML = parts.join("");
  const now = new Set($$("g[data-k]", g).map((el) => el.dataset.k));
  if (!reducedMotion()) {
    $$("g[data-k]", g).forEach((el) => {
      if (!prevLayers.has(el.dataset.k) && prevLayers.size) {
        el.animate([{ transform: "translateY(-40px)", opacity: 0 }, { transform: "translateY(0)", opacity: 1 }], { duration: 420, easing: EASE });
      }
    });
  }
  prevLayers = now;
  $("#vasoPrice").textContent = money(vasoPrice());
  $("#vasoName").textContent = `Dino-vaso ${vasoName()}`;
  if (dropColor && !reducedMotion()) dropIntoCup(dropColor);
}
function dropIntoCup(color) {
  const stage = $(".builder-stage");
  const dot = document.createElement("span");
  dot.className = "drop";
  dot.style.background = color;
  stage.append(dot);
  dot.animate([
    { transform: "translate(-50%, 0) scale(.9)", opacity: 1 },
    { transform: "translate(-50%, 150px) scale(1)", opacity: 1, offset: 0.8 },
    { transform: "translate(-50%, 170px) scale(.6)", opacity: 0 }
  ], { duration: 560, easing: "cubic-bezier(0.55, 0, 0.7, 0.4)" }).finished.then(() => dot.remove());
  chomp($("#builderDino"));
}

/* ---------- juego y monedero ---------- */
function renderWallet() {
  $("#walletPts").textContent = S.points;
  $("#walletBest").textContent = S.best;
  const into = S.points % 100;
  $("#walletFill").style.transform = `scaleX(${S.points >= 100 ? 1 : into / 100})`;
  $("#walletHint").textContent = S.points >= 100
    ? `Tienes ${Math.floor(S.points / 100)} ${Math.floor(S.points / 100) === 1 ? "cupón" : "cupones"} de $10 listos para usar en tu pedido.`
    : `Te faltan ${100 - S.points} para tu primer cupón de $10.`;
}

function setupGame() {
  const overlay = $("#gameOverlay"), title = $("#overlayTitle"), sub = $("#overlaySub"), btn = $("#gameStart");
  let awarded = 0;
  const award = (score) => {
    const pts = Math.floor((score - awarded) / 10);
    awarded = score;
    if (pts > 0) { S.points += pts; renderWallet(); }
    if (score > S.best) S.best = score;
    save(); renderWallet();
    return pts;
  };
  const game = createGame({
    canvas: $("#gameCanvas"),
    onScore: (s) => { $("#hudScore").textContent = s; },
    onLives: (l) => { $("#hudLives").textContent = "♥".repeat(Math.max(0, l)) || "–"; $("#hudLives").setAttribute("aria-label", `${l} vidas`); },
    onState: (st, score) => {
      if (st === "play") { overlay.hidden = true; return; }
      overlay.hidden = false;
      if (st === "paused") { title.textContent = "Pausa"; sub.textContent = `Llevas ${score} puntos`; btn.textContent = "Seguir jugando"; }
      if (st === "won") { const p = award(score); title.textContent = "¡Te comiste todo!"; sub.textContent = `+${p} Dino-puntos. El siguiente nivel va más rápido.`; btn.textContent = "Siguiente nivel"; }
      if (st === "over") { const p = award(score); awarded = 0; title.textContent = "Te alcanzó el chile"; sub.textContent = `${score} puntos · ganaste ${p} Dino-puntos`; btn.textContent = "Jugar otra vez"; }
    }
  });
  btn.addEventListener("click", () => game.start());

  const KEYS = { ArrowUp: "up", ArrowDown: "down", ArrowLeft: "left", ArrowRight: "right", w: "up", s: "down", a: "left", d: "right", W: "up", S: "down", A: "left", D: "right" };
  addEventListener("keydown", (e) => {
    if (game.state !== "play" || !KEYS[e.key] || e.target.closest("input, textarea")) return;
    e.preventDefault();
    game.steer(KEYS[e.key]);
  });
  $$(".pad button").forEach((b) => b.addEventListener("pointerdown", (e) => { e.preventDefault(); game.steer(b.dataset.dir); }));
  const cv = $("#gameCanvas");
  let sx = 0, sy = 0;
  cv.addEventListener("pointerdown", (e) => { sx = e.clientX; sy = e.clientY; });
  cv.addEventListener("pointermove", (e) => {
    if (!e.buttons && e.pointerType === "mouse") return;
    const dx = e.clientX - sx, dy = e.clientY - sy;
    if (Math.hypot(dx, dy) < 18) return;
    game.steer(Math.abs(dx) > Math.abs(dy) ? (dx > 0 ? "right" : "left") : (dy > 0 ? "down" : "up"));
    sx = e.clientX; sy = e.clientY;
  });
  document.addEventListener("visibilitychange", () => { if (document.hidden) game.pause(); });
  new IntersectionObserver(([e]) => { if (!e.isIntersecting) game.pause(); }, { threshold: 0.15 }).observe(cv);
  new IntersectionObserver(([e]) => document.body.classList.toggle("in-game", e.isIntersecting), { threshold: 0.3 }).observe($(".game-box"));
}

/* ---------- eventos ---------- */
function bind() {
  $("#tabs").addEventListener("click", (e) => {
    const b = e.target.closest("[data-cat]");
    if (!b) return;
    S.cat = b.dataset.cat;
    renderTabs(); renderGrid();
  });
  $("#search").addEventListener("input", (e) => { S.q = e.target.value; renderGrid(); });

  document.addEventListener("click", (e) => {
    const open = e.target.closest("[data-open]");
    if (open) return openProduct(open.dataset.open);
    const quick = e.target.closest("[data-quick]");
    if (quick) return openProduct(quick.dataset.quick);
    const add = e.target.closest("[data-add]");
    if (add) {
      const p = PRODUCTS.find((x) => x.id === add.dataset.add);
      addToCart({ id: p.id, name: p.name, img: p.img, price: p.price, qty: 1, opts: {} }, add.closest(".card")?.querySelector("img") || add);
    }
  });

  const pd = $("#productDialog");
  pd.addEventListener("change", updatePdPrice);
  pd.addEventListener("click", (e) => {
    const q = e.target.closest("[data-qty]");
    if (q) { current.qty = Math.min(20, Math.max(1, current.qty + +q.dataset.qty)); $("#pdQty").textContent = current.qty; updatePdPrice(); }
    if (e.target === pd) pd.close();
  });
  $("#productForm").addEventListener("submit", (e) => {
    if (e.submitter?.value !== "add") return;
    const { p, qty } = current;
    const opts = {};
    for (const o of p.options || []) {
      const i = +($(`#pdOptions input[name="${o.id}"]:checked`)?.value ?? 0);
      opts[o.label] = o.choices[i].label;
    }
    addToCart({ id: p.id, name: p.name, img: p.img, price: pdUnitPrice(), qty, opts, note: $("#pdNote").value.trim() }, $("#pdImg"));
  });

  const cd = $("#cartDialog");
  const openCart = () => { renderCart(); cd.showModal(); };
  $("#cartBtn").addEventListener("click", openCart);
  $("#dockBtn").addEventListener("click", openCart);
  cd.addEventListener("click", (e) => {
    if (e.target === cd || e.target.closest("[data-close]")) return cd.close();
    const go = e.target.closest("[data-close-go]");
    if (go) { cd.close(); $(go.dataset.closeGo).scrollIntoView({ behavior: reducedMotion() ? "auto" : "smooth" }); return; }
    const l = e.target.closest("[data-line]");
    if (l) {
      const it = S.cart[+l.dataset.line];
      it.qty += +l.dataset.d;
      if (it.qty <= 0) S.cart.splice(+l.dataset.line, 1);
      save(); renderCartBadge(); renderCart();
      return;
    }
    if (e.target.closest("#confirmOrder")) return confirmOrder();
    if (e.target.closest("#waOrder")) {
      const name = $("#cartName").value.trim();
      if (name) { S.name = name; save(); }
      window.open(waLink(), "_blank", "noopener");
    }
  });
  cd.addEventListener("change", (e) => {
    if (e.target.name === "slot") S.slot = e.target.value;
    if (e.target.id === "usePoints") { S.usePoints = e.target.checked; const n = $("#cartName").value; renderCart(); $("#cartName").value = n; }
  });

  const td = $("#ticketDialog");
  td.addEventListener("click", (e) => {
    if (e.target === td || e.target.closest("[data-close]")) return td.close();
    if (e.target.closest("#onWay")) { S.order.onWay = true; save(); tickTicket(); toast("Le avisamos a la cocina que vas en camino"); }
    if (e.target.closest("#pickedUp")) {
      S.order.pickedUp = true; save(); tickTicket();
      toast(`Casillero #${S.order.locker} abierto. ¡Provecho!`);
      if (!reducedMotion()) confetti();
    }
  });
  $("#orderBannerOpen").addEventListener("click", openTicket);

  $("#vasoForm").addEventListener("change", (e) => {
    const [, group] = e.target.name.split("-");
    const def = VASO[group];
    let color = null;
    if (def.type === "one") { V[group] = e.target.value; color = def.items.find((x) => x.id === e.target.value).color; }
    else if (e.target.checked) { V[group].add(e.target.value); color = def.items.find((x) => x.id === e.target.value).color; }
    else V[group].delete(e.target.value);
    renderCup(color || null);
  });
  $("#vasoForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const label = (g, ids) => [...ids].map((id) => VASO[g].items.find((x) => x.id === id).label).join(", ") || "Sin";
    addToCart({
      id: "dino-vaso", name: `Dino-vaso ${vasoName()}`, img: "vaso-botanero", price: vasoPrice(), qty: 1,
      opts: { Tamaño: VASO.size.items.find((s) => s.id === V.size).label, Base: VASO.base.items.find((b) => b.id === V.base).label, Toppings: label("tops", V.tops), Salsas: label("sauce", V.sauce) }
    }, $(".cup"));
  });
}

/* ---------- inicio ---------- */
mountDinos();
renderStatus(); setInterval(renderStatus, 60000);
renderTabs(); renderGrid();
renderCartBadge();
renderBuilderChips(); renderCup();
renderWallet();
setupGame();
bind();
heroLane($("#heroLane"));
scrollLanes($$("[data-scroll-lane]"));
if (S.order && orderStage(S.order) < 3) { renderBanner(); clearInterval(ticketTimer); ticketTimer = setInterval(renderBanner, 2000); }
