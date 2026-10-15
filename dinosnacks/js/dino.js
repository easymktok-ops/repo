// Dino Pac-Man: SVG del dino de lentes, chiles fantasma, botanas y pistas animadas.

export const reducedMotion = () => matchMedia("(prefers-reduced-motion: reduce)").matches;
/** Con movimiento reducido, el dino se queda quieto (pausa SMIL). */
export const freeze = (root) => { if (reducedMotion()) root.querySelectorAll("svg.dino-svg").forEach((s) => s.pauseAnimations?.()); };

const R = 34, CX = 50, CY = 52;
function mouth(deg) {
  const a = (deg * Math.PI) / 180;
  const x = (CX + R * Math.cos(a)).toFixed(1);
  const yU = (CY - R * Math.sin(a)).toFixed(1);
  const yD = (CY + R * Math.sin(a)).toFixed(1);
  return `M${CX} ${CY} L${x} ${yU} A${R} ${R} 0 1 0 ${x} ${yD} Z`;
}
const OPEN = mouth(38), MID = mouth(18), SHUT = mouth(2);

let uid = 0;
/** mode: "loop" mastica sin parar · "idle" boca cerrada, mastica con chomp(el) */
export function dinoSVG({ mode = "loop", speed = 0.32, label = "" } = {}) {
  const id = `chomp${++uid}`;
  const anim = mode === "loop"
    ? `<animate id="${id}" attributeName="d" dur="${speed}s" repeatCount="indefinite" values="${OPEN};${SHUT};${OPEN}" keyTimes="0;.5;1" calcMode="spline" keySplines=".4 0 .2 1;.4 0 .2 1"/>`
    : `<animate class="chomp-anim" attributeName="d" dur="${speed}s" begin="indefinite" repeatCount="3" values="${MID};${OPEN};${SHUT}" keyTimes="0;.45;1"/>`;
  return `<svg class="dino-svg" viewBox="-8 -8 116 112" ${label ? `role="img" aria-label="${label}"` : 'aria-hidden="true" focusable="false"'}>
    <path class="d-tail" d="M24 64 L-4 84 L30 80 Z"/>
    <rect class="d-foot" x="34" y="78" width="13" height="14" rx="5"/>
    <rect class="d-foot" x="56" y="78" width="13" height="14" rx="5"/>
    <path class="d-hair" d="M26 30 L28 8 L38 22 L44 0 L52 18 L62 4 L64 24 Z"/>
    <circle class="d-mouth" cx="${CX}" cy="${CY}" r="${R - 4}"/>
    <path class="d-tongue" d="M58 58 Q72 50 82 56 Q74 64 58 60 Z"/>
    <path class="d-body" d="${mode === "loop" ? OPEN : SHUT}">${anim}</path>
    <path class="d-belly" d="M30 70 Q44 84 62 80" />
    <path class="d-shades" d="M38 27 H74 Q77 27 76 30 L73 37 Q72 40 69 40 H62 Q59 40 58 37 L57 34 H55 L54 37 Q53 40 50 40 H44 Q41 40 40 37 Z"/>
    <path class="d-glint" d="M63 31 L67 31"/>
  </svg>`;
}

/** Hace masticar a un dino "idle" (cuando agregas algo al pedido). */
export function chomp(host) {
  if (!host || reducedMotion()) return;
  const a = host.querySelector(".chomp-anim");
  try { a?.beginElement(); } catch { /* SMIL no disponible */ }
  host.animate(
    [{ transform: "scale(1)" }, { transform: "scale(1.18) rotate(-6deg)" }, { transform: "scale(1)" }],
    { duration: 420, easing: "cubic-bezier(0.23, 1, 0.32, 1)" }
  );
}

export function chileSVG(scared = false) {
  return `<svg class="chile-svg ${scared ? "is-scared" : ""}" viewBox="0 0 60 72" aria-hidden="true" focusable="false">
    <path class="c-stem" d="M30 14 Q29 4 39 1"/>
    <path class="c-body" d="M7 38 Q7 12 30 12 Q53 12 53 38 V66 L46 60 L38 66 L30 60 L22 66 L14 60 L7 66 Z"/>
    <circle class="c-eye" cx="22" cy="34" r="6"/><circle class="c-eye" cx="40" cy="34" r="6"/>
    <circle class="c-pupil" cx="25" cy="35" r="3"/><circle class="c-pupil" cx="43" cy="35" r="3"/>
  </svg>`;
}

const GUMMY = ["#e8333a", "#ff9f1c", "#2ec4b6", "#7b61ff", "#3fb950"];
export function pelletSVG(kind, i = 0) {
  switch (kind) {
    case "gomita": {
      const c = GUMMY[i % GUMMY.length];
      return `<svg viewBox="0 0 20 24" class="pellet-svg"><g fill="${c}" stroke="#111" stroke-width="1.6"><circle cx="5" cy="4" r="3"/><circle cx="15" cy="4" r="3"/><circle cx="10" cy="8" r="6"/><ellipse cx="10" cy="17" rx="7" ry="6.5"/></g></svg>`;
    }
    case "papa":
      return `<svg viewBox="0 0 24 24" class="pellet-svg"><g fill="#f7c948" stroke="#111" stroke-width="1.5"><rect x="4" y="3" width="5" height="17" rx="1.5" transform="rotate(-18 6 12)"/><rect x="14" y="3" width="5" height="17" rx="1.5" transform="rotate(16 16 12)"/></g><path d="M3 15 H21 L19 23 H5 Z" fill="#e8333a" stroke="#111" stroke-width="1.5" stroke-linejoin="round"/></svg>`;
    case "fresa":
      return `<svg viewBox="0 0 24 26" class="pellet-svg pellet-fresa"><path d="M12 25 C2 18 1 10 4 7 C7 4 17 4 20 7 C23 10 22 18 12 25Z" fill="#e8333a" stroke="#111" stroke-width="1.6"/><path d="M6 6 L12 1 L18 6 L12 8Z" fill="#3fb950" stroke="#111" stroke-width="1.4"/><g fill="#ffe08a"><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="12" cy="17" r="1"/></g></svg>`;
    default:
      return `<svg viewBox="0 0 20 24" class="pellet-svg"><circle cx="10" cy="12" r="4" fill="#111"/></svg>`;
  }
}

/** Monta los dinos estáticos marcados con data-dino. */
export function mountDinos() {
  document.querySelectorAll("[data-dino]").forEach((el) => {
    const kind = el.dataset.dino;
    el.innerHTML = dinoSVG({ mode: kind === "builder" ? "idle" : kind === "mini" ? "loop" : "idle", speed: kind === "mini" ? 0.5 : 0.24 });
  });
  freeze(document);
}

/* ---------- Pista del hero: el dino recorre la pantalla comiendo botanas y un chile lo persigue ---------- */
export function heroLane(lane) {
  const kinds = ["gomita", "papa", "gomita", "dot", "gomita", "papa"];
  lane.innerHTML = `<div class="lane-track"></div><div class="lane-pellets"></div>
    <div class="lane-actor lane-chile">${chileSVG()}</div>
    <div class="lane-actor lane-dino">${dinoSVG({ speed: 0.26 })}</div>`;
  const wrap = lane.querySelector(".lane-pellets");
  const dino = lane.querySelector(".lane-dino");
  const chile = lane.querySelector(".lane-chile");
  let pellets = [], W = 0;

  function layout() {
    W = lane.clientWidth;
    const gap = W < 560 ? 46 : 58;
    const n = Math.max(6, Math.floor(W / gap));
    const fresaAt = Math.floor(n * 0.55);
    wrap.innerHTML = Array.from({ length: n }, (_, i) => {
      const k = i === fresaAt ? "fresa" : kinds[i % kinds.length];
      return `<span class="pellet" style="left:${(i + 0.5) * (W / n)}px">${pelletSVG(k, i)}</span>`;
    }).join("");
    pellets = [...wrap.children].map((el) => ({ el, x: parseFloat(el.style.left), fresa: !!el.querySelector(".pellet-fresa") }));
  }
  layout();

  if (reducedMotion()) {
    freeze(lane);
    dino.style.transform = `translateX(${W * 0.42}px)`;
    chile.style.transform = `translateX(${W * 0.1}px)`;
    pellets.forEach((p) => p.x < W * 0.42 + 40 && p.el.classList.add("eaten"));
    return;
  }

  let x = -90, last = 0, running = false, raf = 0, scared = false;
  const speed = () => Math.max(110, W / 7.5);
  function frame(t) {
    const dt = last ? Math.min(0.05, (t - last) / 1000) : 0;
    last = t;
    x += speed() * dt;
    if (x > W + 200) {
      x = -90; scared = false;
      chile.firstElementChild.classList.remove("is-scared");
      pellets.forEach((p, i) => { p.el.classList.remove("eaten"); p.el.style.transitionDelay = `${i * 25}ms`; });
    }
    const mouthX = x + 52;
    for (const p of pellets) {
      if (!p.el.classList.contains("eaten") && p.x < mouthX) {
        p.el.style.transitionDelay = "0ms";
        p.el.classList.add("eaten");
        if (p.fresa && !scared) { scared = true; chile.firstElementChild.classList.add("is-scared"); }
      }
    }
    const bob = Math.sin(t / 90) * 2;
    dino.style.transform = `translate3d(${x}px, ${bob}px, 0)`;
    const cx = scared ? x - 150 - Math.sin(t / 160) * 8 : x - 120;
    chile.style.transform = `translate3d(${cx}px, ${Math.sin(t / 140) * 4}px, 0)`;
    raf = requestAnimationFrame(frame);
  }
  const io = new IntersectionObserver(([e]) => {
    if (e.isIntersecting && !running) { running = true; last = 0; raf = requestAnimationFrame(frame); }
    else if (!e.isIntersecting && running) { running = false; cancelAnimationFrame(raf); }
  });
  io.observe(lane);
  let rt; addEventListener("resize", () => { clearTimeout(rt); rt = setTimeout(layout, 150); });
}

/* ---------- Pistas ligadas al scroll: el dino avanza (o regresa) mientras bajas ---------- */
export function scrollLanes(lanes) {
  const items = [...lanes].map((lane, li) => {
    lane.innerHTML = `<div class="lane-track"></div><div class="lane-pellets"></div><div class="lane-actor lane-dino">${dinoSVG({ speed: 0.3 })}</div>`;
    return { lane, li, wrap: lane.querySelector(".lane-pellets"), dino: lane.querySelector(".lane-dino"), pellets: [], W: 0, prev: 0 };
  });
  function layout() {
    for (const it of items) {
      it.W = it.lane.clientWidth;
      const n = Math.max(8, Math.floor(it.W / 40));
      it.wrap.innerHTML = Array.from({ length: n }, (_, i) =>
        `<span class="pellet pellet-sm" style="left:${(i + 0.5) * (it.W / n)}px">${pelletSVG(i % 5 === 2 ? "gomita" : "dot", i + it.li)}</span>`).join("");
      it.pellets = [...it.wrap.children].map((el) => ({ el, x: parseFloat(el.style.left) }));
    }
    update();
  }
  function update() {
    const vh = innerHeight;
    for (const it of items) {
      const r = it.lane.getBoundingClientRect();
      if (r.bottom < -50 || r.top > vh + 50) continue;
      const p = Math.min(1, Math.max(0, (vh - r.top) / (vh * 0.85)));
      const x = p * (it.W + 40) - 70;
      const dir = x < it.prev - 0.5 ? -1 : x > it.prev + 0.5 ? 1 : it.dir || 1;
      it.dir = dir; it.prev = x;
      it.dino.style.transform = `translate3d(${x}px,0,0) scaleX(${dir})`;
      for (const pl of it.pellets) pl.el.classList.toggle("eaten", pl.x < x + 46);
    }
  }
  layout();
  if (reducedMotion()) { lanes.forEach(freeze); return; }
  let ticking = false;
  addEventListener("scroll", () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => { ticking = false; update(); });
  }, { passive: true });
  let rt; addEventListener("resize", () => { clearTimeout(rt); rt = setTimeout(layout, 150); });
}
