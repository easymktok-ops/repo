const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

function initHeader() {
  const header = document.querySelector('[data-header]');
  if (!header) return;
  const hero = document.querySelector('.hero');

  if (!hero) {
    header.classList.add('is-scrolled');
    return;
  }

  const sentinel = document.createElement('div');
  sentinel.style.cssText = 'position:absolute;top:0;left:0;width:1px;height:80px;pointer-events:none;';
  hero.prepend(sentinel);

  new IntersectionObserver(([entry]) => {
    header.classList.toggle('is-scrolled', !entry.isIntersecting);
  }).observe(sentinel);
}

function initMenu() {
  const dialog = document.querySelector('[data-menu]');
  const openBtn = document.querySelector('[data-menu-open]');
  if (!dialog || !openBtn || typeof dialog.showModal !== 'function') return;

  const close = () => {
    dialog.close();
  };

  openBtn.addEventListener('click', () => {
    dialog.showModal();
    openBtn.setAttribute('aria-expanded', 'true');
  });

  dialog.addEventListener('close', () => {
    openBtn.setAttribute('aria-expanded', 'false');
    document.documentElement.style.overflow = '';
  });
  dialog.addEventListener('toggle', () => {
    document.documentElement.style.overflow = dialog.open ? 'hidden' : '';
  });

  dialog.querySelector('[data-menu-close]')?.addEventListener('click', close);
  dialog.querySelectorAll('[data-menu-link]').forEach((link) => link.addEventListener('click', close));
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) close();
  });
}

// El poster es el LCP; el video solo se pide cuando la página ya pintó y si la
// conexión y las preferencias lo permiten.
function initHeroVideo() {
  const video = document.querySelector('[data-hero-video]');
  if (!video || reduceMotion.matches) return;

  const conn = navigator.connection;
  if (conn && (conn.saveData || /(^|-)2g$/.test(conn.effectiveType || ''))) return;

  const load = () => {
    const source = video.querySelector('source[data-src]');
    const src = source?.getAttribute('data-src');
    if (!src) return;
    source.src = src;
    video.load();
    video.addEventListener('canplay', () => {
      video.setAttribute('data-loaded', 'true');
      video.play().catch(() => {});
    }, { once: true });
  };

  if ('requestIdleCallback' in window) {
    requestIdleCallback(load, { timeout: 2500 });
  } else {
    window.addEventListener('load', load, { once: true });
  }
}

// Profundidad por puntero en el hero: cada capa se desplaza según su factor,
// interpolada para que tenga inercia en lugar de seguir el mouse al píxel.
function initPointerDepth() {
  const hero = document.querySelector('.hero');
  if (!hero || reduceMotion.matches || !finePointer.matches) return;

  const layers = [
    { el: hero.querySelector('.hero__ribbons svg'), x: 28, y: 18 },
    { el: hero.querySelector('.hero__poster'), x: -12, y: -8 },
    { el: hero.querySelector('.hero__content'), x: 6, y: 4 },
  ].filter((layer) => layer.el);

  let targetX = 0;
  let targetY = 0;
  let currentX = 0;
  let currentY = 0;
  let frame = 0;
  let visible = true;

  const tick = () => {
    currentX += (targetX - currentX) * 0.08;
    currentY += (targetY - currentY) * 0.08;
    for (const layer of layers) {
      layer.el.style.transform = `translate3d(${(currentX * layer.x).toFixed(2)}px, ${(currentY * layer.y).toFixed(2)}px, 0)`;
    }
    const settled = Math.abs(targetX - currentX) < 0.001 && Math.abs(targetY - currentY) < 0.001;
    frame = settled || !visible ? 0 : requestAnimationFrame(tick);
  };

  const start = () => {
    if (!frame) frame = requestAnimationFrame(tick);
  };

  hero.addEventListener('pointermove', (event) => {
    const rect = hero.getBoundingClientRect();
    targetX = (event.clientX - rect.left) / rect.width - 0.5;
    targetY = (event.clientY - rect.top) / rect.height - 0.5;
    start();
  });

  hero.addEventListener('pointerleave', () => {
    targetX = 0;
    targetY = 0;
    start();
  });

  new IntersectionObserver(([entry]) => {
    visible = entry.isIntersecting;
    if (visible) start();
  }).observe(hero);
}

// El botón flotante se aparta cuando el pie de página ya muestra WhatsApp.
function initFloatingWhatsApp() {
  const button = document.querySelector('[data-wa-float]');
  const footer = document.querySelector('.site-footer');
  if (!button || !footer) return;

  new IntersectionObserver(([entry]) => {
    button.setAttribute('data-hidden', entry.isIntersecting ? 'true' : 'false');
  }, { rootMargin: '0px 0px -40% 0px' }).observe(footer);
}

initHeader();
initMenu();
initHeroVideo();
initPointerDepth();
initFloatingWhatsApp();
