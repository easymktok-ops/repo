// Dino-Chomp: Pac-Man con el dino de Dino Snacks. # muro · . gomita · F fresa (poder) · P dino · C casa de chiles · - puerta
const MAP = [
  "###################",
  "#........#........#",
  "#F##.###.#.###.##F#",
  "#.................#",
  "#.##.#.#####.#.##.#",
  "#....#...#...#....#",
  "####.### # ###.####",
  "   #.#       #.#   ",
  "####.# ##-## #.####",
  "    .  #CCC#  .    ",
  "####.# ##### #.####",
  "   #.#       #.#   ",
  "####.# ##### #.####",
  "#........#........#",
  "#.##.###.#.###.##.#",
  "#F.#.....P.....#.F#",
  "##.#.#.#####.#.#.##",
  "#....#...#...#....#",
  "#.######.#.######.#",
  "#.................#",
  "###################"
];
const COLS = MAP[0].length, ROWS = MAP.length, T = 24;
const DIRS = { up: { dx: 0, dy: -1 }, down: { dx: 0, dy: 1 }, left: { dx: -1, dy: 0 }, right: { dx: 1, dy: 0 } };
const GUMMY = ["#ff5a5f", "#ffb02e", "#3dd6c6", "#a78bfa", "#7ee06a"];
const C = { bg: "#10291a", wall: "#b9e34c", wallIn: "#10291a", dino: "#f2e53a", ink: "#111", chile: "#e8333a", stem: "#3fb950", scared: "#4f7cff" };

const same = (a, b) => a && b && a.dx === b.dx && a.dy === b.dy;
const opposite = (a, b) => a && b && a.dx === -b.dx && a.dy === -b.dy;

export function createGame({ canvas, onScore, onLives, onEnd, onState }) {
  const ctx = canvas.getContext("2d");
  const dpr = Math.min(2, devicePixelRatio || 1);
  canvas.width = COLS * T * dpr; canvas.height = ROWS * T * dpr;
  ctx.scale(dpr, dpr);

  let grid, pellets, player, ghosts, score, lives, level, state = "idle", fright = 0, pauseT = 0, raf = 0, last = 0, t = 0, eatenChain = 0;

  const tile = (x, y) => {
    if (y < 0 || y >= ROWS) return "#";
    x = ((x % COLS) + COLS) % COLS;
    return grid[y][x];
  };
  const passable = (x, y, ghost) => { const c = tile(x, y); return c !== "#" && (c !== "-" || ghost); };

  function reset(full) {
    if (full) {
      grid = MAP.map((r) => r.split(""));
      pellets = 0;
      grid.forEach((r) => r.forEach((c) => { if (c === "." || c === "F") pellets++; }));
    }
    const sp = 4.6 + level * 0.35;
    player = { x: 9, y: 15, dir: DIRS.left, want: DIRS.left, speed: sp, mouth: 0 };
    ghosts = [0, 1, 2].map((i) => ({ i, x: 8 + i, y: 9, dir: DIRS.up, speed: sp * 0.86, mode: "home", wait: 1 + i * 2.6 }));
    fright = 0;
  }

  function newGame() { score = 0; lives = 3; level = 0; reset(true); onScore?.(score); onLives?.(lives); }

  /* ---------- movimiento por casillas ---------- */
  function move(e, dt, choose, onTile) {
    let d = e.speed * dt, guard = 0;
    while (d > 1e-6 && guard++ < 6) {
      const rx = Math.round(e.x), ry = Math.round(e.y);
      if (Math.abs(e.x - rx) < 1e-4 && Math.abs(e.y - ry) < 1e-4) {
        e.x = rx; e.y = ry;
        if (e.x < 0) e.x += COLS; else if (e.x >= COLS) e.x -= COLS;
        onTile?.(e);
        const nd = choose(e);
        if (!nd) return;
        e.dir = nd;
        const s = Math.min(d, 1);
        e.x += nd.dx * s; e.y += nd.dy * s; d -= s;
      } else {
        if (e.want && opposite(e.want, e.dir)) e.dir = e.want; // media vuelta inmediata
        const tx = e.dir.dx > 0 ? Math.ceil(e.x) : e.dir.dx < 0 ? Math.floor(e.x) : e.x;
        const ty = e.dir.dy > 0 ? Math.ceil(e.y) : e.dir.dy < 0 ? Math.floor(e.y) : e.y;
        const rem = Math.abs(tx - e.x) + Math.abs(ty - e.y);
        if (rem <= d) { e.x = tx; e.y = ty; d -= rem; } else { e.x += e.dir.dx * d; e.y += e.dir.dy * d; d = 0; }
      }
    }
  }

  const choosePlayer = (e) => {
    if (e.want && passable(e.x + e.want.dx, e.y + e.want.dy)) return e.want;
    if (passable(e.x + e.dir.dx, e.y + e.dir.dy)) return e.dir;
    return null;
  };

  function eat(e) {
    const c = grid[e.y]?.[e.x];
    if (c === ".") { grid[e.y][e.x] = " "; score += 10; pellets--; onScore?.(score); }
    else if (c === "F") {
      grid[e.y][e.x] = " "; score += 50; pellets--; onScore?.(score);
      fright = Math.max(4, 7 - level); eatenChain = 0;
      ghosts.forEach((g) => { if (g.mode === "chase" || g.mode === "scatter") { g.mode = "fright"; g.dir = { dx: -g.dir.dx, dy: -g.dir.dy }; } });
    }
    if (pellets <= 0) { state = "won"; onState?.("won", score, level); }
  }

  function chooseGhost(g) {
    if (g.mode === "home") {
      if (g.wait > 0) return null;
      g.mode = "leave";
    }
    const opts = Object.values(DIRS).filter((d) => passable(g.x + d.dx, g.y + d.dy, g.mode === "leave" || g.mode === "eaten") && !opposite(d, g.dir));
    const pool = opts.length ? opts : Object.values(DIRS).filter((d) => passable(g.x + d.dx, g.y + d.dy, true));
    if (!pool.length) return null;
    let target;
    if (g.mode === "leave") { target = { x: 9, y: 7 }; if (g.x === 9 && g.y === 7) g.mode = fright > 0 ? "fright" : "chase"; }
    if (g.mode === "eaten") { target = { x: 9, y: 9 }; if (g.x === 9 && g.y === 9) { g.mode = "leave"; g.speed = player.speed * 0.86; } }
    if (g.mode === "fright") return pool[Math.floor(Math.random() * pool.length)];
    if (g.mode === "chase") {
      const p = player;
      target = g.i === 0 ? { x: p.x, y: p.y }
        : g.i === 1 ? { x: p.x + p.dir.dx * 4, y: p.y + p.dir.dy * 4 }
        : Math.hypot(g.x - p.x, g.y - p.y) > 7 ? { x: p.x, y: p.y } : { x: 1, y: 19 };
      if (Math.random() < 0.12) return pool[Math.floor(Math.random() * pool.length)];
    }
    pool.sort((a, b) => Math.hypot(g.x + a.dx - target.x, g.y + a.dy - target.y) - Math.hypot(g.x + b.dx - target.x, g.y + b.dy - target.y));
    return pool[0];
  }

  function update(dt) {
    t += dt;
    if (pauseT > 0) { pauseT -= dt; return; }
    if (fright > 0) { fright -= dt; if (fright <= 0) ghosts.forEach((g) => { if (g.mode === "fright") g.mode = "chase"; }); }
    player.mouth += dt * 14;
    move(player, dt, choosePlayer, eat);
    if (state !== "play") return;
    for (const g of ghosts) {
      if (g.mode === "home") g.wait -= dt;
      const base = player.speed * 0.86;
      g.speed = g.mode === "eaten" ? base * 2 : g.mode === "fright" ? base * 0.6 : g.y === 9 && (g.x < 4 || g.x > 14) ? base * 0.55 : base;
      move(g, dt, chooseGhost);
      const dx = Math.abs(((g.x - player.x + COLS / 2) % COLS + COLS) % COLS - COLS / 2);
      if (dx + Math.abs(g.y - player.y) < 0.7) {
        if (g.mode === "fright") { g.mode = "eaten"; eatenChain++; score += 200 * eatenChain; onScore?.(score); pauseT = 0.35; }
        else if (g.mode !== "eaten") { die(); return; }
      }
    }
  }

  function die() {
    lives--; onLives?.(lives);
    if (lives <= 0) { state = "over"; onState?.("over", score, level); onEnd?.(score); return; }
    pauseT = 0.9; reset(false);
  }

  /* ---------- dibujo ---------- */
  function drawWalls() {
    ctx.fillStyle = C.bg; ctx.fillRect(0, 0, COLS * T, ROWS * T);
    ctx.lineCap = "round"; ctx.lineJoin = "round";
    for (const [w, col] of [[T * 0.62, C.wall], [T * 0.2, C.wallIn]]) {
      ctx.strokeStyle = col; ctx.fillStyle = col; ctx.lineWidth = w;
      ctx.beginPath();
      for (let y = 0; y < ROWS; y++) for (let x = 0; x < COLS; x++) {
        if (MAP[y][x] !== "#") continue;
        const cx = x * T + T / 2, cy = y * T + T / 2;
        ctx.moveTo(cx, cy); ctx.lineTo(cx + 0.01, cy);
        if (MAP[y][x + 1] === "#") { ctx.moveTo(cx, cy); ctx.lineTo(cx + T, cy); }
        if (MAP[y + 1]?.[x] === "#") { ctx.moveTo(cx, cy); ctx.lineTo(cx, cy + T); }
      }
      ctx.stroke();
    }
    ctx.fillStyle = "#ff8fb1"; ctx.fillRect(9 * T + 3, 8 * T + T / 2 - 2, T - 6, 4); // puerta
  }

  function drawPellets() {
    for (let y = 0; y < ROWS; y++) for (let x = 0; x < COLS; x++) {
      const c = grid[y][x], cx = x * T + T / 2, cy = y * T + T / 2;
      if (c === ".") {
        ctx.fillStyle = GUMMY[(x + y) % GUMMY.length];
        ctx.beginPath(); ctx.arc(cx, cy, 3.2, 0, Math.PI * 2); ctx.fill();
      } else if (c === "F") {
        const s = 1 + Math.sin(t * 6) * 0.12;
        ctx.save(); ctx.translate(cx, cy); ctx.scale(s, s);
        ctx.fillStyle = C.chile; ctx.strokeStyle = C.ink; ctx.lineWidth = 1.5;
        ctx.beginPath(); ctx.moveTo(0, 8); ctx.bezierCurveTo(-9, 2, -8, -6, 0, -5); ctx.bezierCurveTo(8, -6, 9, 2, 0, 8); ctx.fill(); ctx.stroke();
        ctx.fillStyle = C.stem; ctx.beginPath(); ctx.moveTo(-5, -5); ctx.lineTo(0, -9); ctx.lineTo(5, -5); ctx.closePath(); ctx.fill();
        ctx.restore();
      }
    }
  }

  function drawDino(p) {
    const cx = p.x * T + T / 2, cy = p.y * T + T / 2, r = T * 0.62;
    const open = state === "play" && pauseT <= 0 ? (Math.sin(p.mouth) + 1) / 2 : 0.5;
    const a = 0.08 + open * 0.62;
    ctx.save(); ctx.translate(cx, cy);
    if (p.dir.dx < 0) ctx.scale(-1, 1);
    else if (p.dir.dy < 0) ctx.rotate(-Math.PI / 2);
    else if (p.dir.dy > 0) ctx.rotate(Math.PI / 2);
    ctx.lineWidth = 2; ctx.strokeStyle = C.ink;
    // cresta
    ctx.fillStyle = C.ink; ctx.beginPath();
    ctx.moveTo(-r * 0.75, -r * 0.45); ctx.lineTo(-r * 0.6, -r * 1.25); ctx.lineTo(-r * 0.25, -r * 0.8); ctx.lineTo(-r * 0.05, -r * 1.35); ctx.lineTo(r * 0.2, -r * 0.8); ctx.closePath(); ctx.fill();
    // cuerpo
    ctx.fillStyle = C.dino; ctx.beginPath(); ctx.moveTo(0, 0); ctx.arc(0, 0, r, a, Math.PI * 2 - a); ctx.closePath(); ctx.fill(); ctx.stroke();
    // lentes
    ctx.fillStyle = C.ink; ctx.beginPath();
    ctx.roundRect(-r * 0.3, -r * 0.62, r * 0.42, r * 0.3, 3); ctx.roundRect(r * 0.18, -r * 0.62, r * 0.36, r * 0.28, 3); ctx.fill();
    ctx.fillRect(-r * 0.4, -r * 0.6, r * 0.95, r * 0.08);
    ctx.restore();
  }

  function drawGhost(g) {
    const cx = g.x * T + T / 2, cy = g.y * T + T / 2, w = T * 0.62;
    ctx.save(); ctx.translate(cx, cy);
    if (g.mode !== "eaten") {
      const blink = g.mode === "fright" && fright < 1.6 && Math.floor(t * 8) % 2;
      ctx.fillStyle = g.mode === "fright" ? (blink ? "#f4f4f4" : C.scared) : C.chile;
      ctx.strokeStyle = C.ink; ctx.lineWidth = 1.8;
      ctx.beginPath(); ctx.moveTo(-w, w); ctx.lineTo(-w, 0); ctx.arc(0, 0, w, Math.PI, 0); ctx.lineTo(w, w);
      const wob = Math.sin(t * 18) * 2;
      for (let i = 0; i < 4; i++) { const x = w - (i + 0.5) * (w / 2); ctx.lineTo(x, w - 4 + (i % 2 ? wob : -wob)); ctx.lineTo(x - w / 4, w); }
      ctx.closePath(); ctx.fill(); ctx.stroke();
      ctx.strokeStyle = C.stem; ctx.lineWidth = 3; ctx.beginPath(); ctx.moveTo(0, -w); ctx.quadraticCurveTo(1, -w - 6, 7, -w - 7); ctx.stroke();
    }
    if (g.mode === "fright") {
      ctx.fillStyle = "#fff"; ctx.fillRect(-5, -3, 3, 3); ctx.fillRect(2, -3, 3, 3);
    } else {
      for (const ex of [-4.5, 4.5]) {
        ctx.fillStyle = "#fff"; ctx.beginPath(); ctx.arc(ex, -2, 3.6, 0, Math.PI * 2); ctx.fill();
        ctx.fillStyle = C.ink; ctx.beginPath(); ctx.arc(ex + g.dir.dx * 1.6, -2 + g.dir.dy * 1.6, 1.8, 0, Math.PI * 2); ctx.fill();
      }
    }
    ctx.restore();
  }

  function draw() {
    drawWalls(); drawPellets();
    ghosts.forEach(drawGhost);
    drawDino(player);
  }

  function loop(ts) {
    const dt = last ? Math.min(0.04, (ts - last) / 1000) : 0;
    last = ts;
    if (state === "play") update(dt); else t += dt;
    draw();
    if (state === "play") raf = requestAnimationFrame(loop);
  }

  function start() {
    if (state === "won") { level++; reset(true); }
    else if (state !== "paused") newGame();
    state = "play"; pauseT = 0.6; last = 0;
    cancelAnimationFrame(raf); raf = requestAnimationFrame(loop);
    onState?.("play");
  }
  function pause() { if (state === "play") { state = "paused"; cancelAnimationFrame(raf); onState?.("paused", score); } }
  function steer(name) { const d = DIRS[name]; if (d && player) player.want = d; }

  newGame(); draw();
  return { start, pause, steer, get state() { return state; } };
}
