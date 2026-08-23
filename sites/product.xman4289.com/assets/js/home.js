/* XMAN Studio home — the product constellation.
   Same trick as the rest of this site: real 3D projected onto a 2D canvas, no
   library. Each product is a body on its own orbit around the XMAN star; the
   one that is shipping and flagship sits closest and burns brightest. */
(() => {
'use strict';
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const REDUCED = matchMedia('(prefers-reduced-motion: reduce)').matches;
const P = window.PRODUCTS || [];

/* ── language ─────────────────────────────────────────────── */
function language() {
  const root = document.documentElement, KEY = 'brainx.lang';
  const apply = (l) => {
    root.setAttribute('data-lang', l);
    root.setAttribute('lang', l === 'th' ? 'th' : 'en');
    $$('[data-th]').forEach(el => {
      const v = el.getAttribute(l === 'th' ? 'data-th' : 'data-en');
      if (v != null) el.textContent = v;
    });
    try { localStorage.setItem(KEY, l); } catch {}
  };
  let start = null;
  try { start = localStorage.getItem(KEY); } catch {}
  if (start !== 'th' && start !== 'en')
    start = (navigator.language || '').toLowerCase().startsWith('th') ? 'th' : 'en';
  apply(start);
  $('#lang-toggle')?.addEventListener('click', () => {
    apply(root.getAttribute('data-lang') === 'th' ? 'en' : 'th');
    drawGrid(currentFilter);
  });
  return () => root.getAttribute('data-lang') === 'th';
}
let isTH = () => true;

/* ── the sky: a Milky Way and drifting stars ───────────────
   Same four features that make a painted galaxy stop looking painted: a band
   that falls off with latitude, dust lanes cutting along it, an off-centre
   bulge so it is not a uniform stripe, and stars crowding the plane. The band
   is generated ONCE into an offscreen canvas and then blitted with a slow
   parallax offset — regenerating it per frame would cost 6-8ms every frame for
   a picture that never changes. */
function sky() {
  const cv = $('#sky');
  if (!cv) return;
  const ctx = cv.getContext('2d');
  let W = 0, H = 0, DPR = 1, band = null, stars = [];

  let seed = 20260803;
  const rnd = () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296);
  const range = (a, b) => a + rnd() * (b - a);

  function buildBand(w, h) {
    const c = document.createElement('canvas');
    c.width = w; c.height = h;
    const g = c.getContext('2d');
    g.translate(w / 2, h / 2);
    g.rotate(-0.42);                       // a diagonal band reads better than a level one
    g.translate(-w / 2, -h / 2);
    const midY = h * 0.5, coreX = w * 0.34;
    const fromCore = x => Math.min(Math.abs(x - coreX), w - Math.abs(x - coreX)) / (w / 2);

    const blob = (x, y, rx, ry, col, blur) => {
      g.save(); g.filter = `blur(${blur}px)`;
      g.translate(x, y); g.scale(1, ry / rx);
      const gr = g.createRadialGradient(0, 0, 0, 0, 0, rx);
      gr.addColorStop(0, col); gr.addColorStop(1, 'rgba(0,0,0,0)');
      g.fillStyle = gr; g.beginPath(); g.arc(0, 0, rx, 0, 6.283); g.fill(); g.restore();
    };

    g.globalCompositeOperation = 'lighter';
    blob(coreX, midY, w * 0.60, h * 0.20, 'rgba(72,90,150,0.40)', 70);
    for (let i = 0; i < 130; i++) {
      const x = rnd() * w, t = fromCore(x), bulge = Math.exp(-t * t * 4.2);
      const y = midY + range(-1, 1) * h * (0.020 + 0.035 * bulge);
      const rx = range(w * 0.02, w * 0.07) * (0.65 + 0.7 * bulge);
      const warm = Math.max(0, bulge - 0.15);
      blob(x, y, rx, rx * range(0.14, 0.30),
           `rgba(${Math.round(196 + 54 * warm)},${Math.round(196 + 26 * warm)},${Math.round(208 - 36 * warm)},${((0.072 + 0.15 * bulge) * range(.6, 1.25)).toFixed(3)})`,
           range(12, 34));
    }
    blob(coreX, midY, w * 0.10, h * 0.085, 'rgba(255,238,210,0.38)', 52);

    // dust: light removed, not paint added — and blur well under the thickness
    g.globalCompositeOperation = 'destination-out';
    let ry2 = midY + h * 0.008;
    for (let x = -w * 0.1; x < w * 1.1; x += w * 0.008) {
      ry2 = Math.max(midY - h * 0.035, Math.min(midY + h * 0.045, ry2 + range(-1, 1) * h * 0.004));
      const t = fromCore(x);
      blob(x, ry2, range(w * 0.022, w * 0.05), range(h * 0.009, h * 0.021),
           `rgba(0,0,0,${(0.74 * Math.exp(-t * t * 2.6) + 0.12).toFixed(3)})`, range(5, 12));
    }
    for (let i = 0; i < 120; i++) {
      const x = rnd() * w, t = fromCore(x);
      blob(x, midY + range(-1, 1) * h * 0.045, range(w * 0.008, w * 0.028), range(h * 0.006, h * 0.015),
           `rgba(0,0,0,${(0.40 * Math.exp(-t * t * 2.2) + 0.07).toFixed(3)})`, range(4, 10));
    }
    return c;
  }

  function build() {
    DPR = Math.min(devicePixelRatio || 1, 1.6);
    W = innerWidth; H = innerHeight;
    cv.width = (W * DPR) | 0; cv.height = (H * DPR) | 0;
    ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
    seed = 20260803;
    band = buildBand(Math.round(W * 1.25), Math.round(H * 1.25));
    stars = [];
    for (let i = 0; i < 260; i++) {
      // depth drives size, brightness AND parallax together, so the far ones
      // genuinely read as far rather than merely small
      const d = Math.pow(rnd(), 1.7);
      stars.push({ x: rnd(), y: rnd(), d, s: 0.4 + d * 1.7, a: 0.16 + d * 0.5, tw: rnd() * 6.28,
                   warm: rnd() < 0.22 });
    }
  }

  let t = 0, px = 0, py = 0;
  addEventListener('pointermove', e => {
    px = (e.clientX / innerWidth - .5); py = (e.clientY / innerHeight - .5);
  }, { passive: true });

  function frame() {
    if (!REDUCED) t += 0.0016;
    ctx.clearRect(0, 0, W, H);
    if (band) {
      ctx.globalAlpha = 1;
      ctx.drawImage(band, -W * 0.125 + Math.sin(t * .5) * 12 - px * 26,
                          -H * 0.125 + Math.cos(t * .4) * 8 - py * 18, W * 1.25, H * 1.25);
    }
    ctx.globalCompositeOperation = 'lighter';
    for (const s of stars) {
      const tw = 0.72 + 0.28 * Math.sin(t * 30 + s.tw);
      ctx.globalAlpha = s.a * tw;
      ctx.fillStyle = s.warm ? '#ffd9b8' : '#dbe9ff';
      const x = (s.x * W - px * (12 + s.d * 46) + W) % W;
      const y = (s.y * H - py * (8 + s.d * 30) + H) % H;
      ctx.beginPath(); ctx.arc(x, y, s.s, 0, 6.283); ctx.fill();
    }
    ctx.globalAlpha = 1; ctx.globalCompositeOperation = 'source-over';
    requestAnimationFrame(frame);
  }

  let rt = null;
  addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(build, 220); }, { passive: true });
  build(); frame();
}

/* ── the constellation ────────────────────────────────────── */
function constellation() {
  const cv = $('#orbit');
  if (!cv || !P.length) return;
  const ctx = cv.getContext('2d');
  const tip = $('#tip'), tipN = $('#tip-n'), tipD = $('#tip-d');
  let W = 0, H = 0, DPR = 1;

  /* Orbit assignment: shipping products ride the inner, faster rings so the
     things you can actually buy today are the ones that catch the eye. */
  /* Radii are capped at 1.0 so the outermost orbit still lands inside the
     canvas once perspective widens it — the first pass let them reach 1.47 and
     four products were drawn off the right edge with their labels clipped.
     The flagship takes the innermost, fastest, largest orbit: it should be the
     body the eye goes to, not one of sixteen equals. */
  const bodies = P.map((p, i) => {
    const flag = !!p[8], shipping = !p[4];
    const k = i / Math.max(1, P.length - 1);              // 0..1
    return {
      p, name: p[0], colour: p[7], href: p[8], soon: p[4], flag,
      r: flag ? 0.30 : 0.46 + k * 0.54,                   // 0.30 .. 1.00
      speed: flag ? 0.62 : (0.42 - k * 0.20) * (shipping ? 1 : 0.72),
      phase: (i * 2.399963) % (Math.PI * 2),
      tilt: 0.13 + (i % 5) * 0.03,
      size: flag ? 16 : (p[3] === null || p[3] > 3000 ? 7.5 : 6),
      x: 0, y: 0, z: 0, sx: 0, sy: 0, ss: 1,
    };
  });

  const view = { yaw: 0.5, pitch: 0.5, dist: 1 };
  let dragging = false, lx = 0, ly = 0, t = 0, hover = null, moved = false;
  /* Separate clock for the orbits, eased. The first cut ran a full orbit in
     about nine seconds, which is far too fast to click a body that is also
     moving — pointing at one now brings the whole system almost to a stop, so
     the target stays under the cursor while you decide. */
  let ot = 0, ease = 1;

  function resize() {
    DPR = Math.min(devicePixelRatio || 1, 2);
    W = cv.clientWidth || 600; H = cv.clientHeight || 600;
    cv.width = (W * DPR) | 0; cv.height = (H * DPR) | 0;
    ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
  }

  const project = (x, y, z) => {
    const cy = Math.cos(view.yaw), sy = Math.sin(view.yaw);
    const cp = Math.cos(view.pitch), sp = Math.sin(view.pitch);
    const x1 = x * cy - z * sy, z1 = x * sy + z * cy;
    const y1 = y * cp - z1 * sp, z2 = y * sp + z1 * cp;
    const unit = Math.min(W, H) * 0.44 * view.dist;
    const d = 3.2, k = d / (d + z2);
    return { x: W / 2 + x1 * unit * k, y: H / 2 + y1 * unit * k, s: k, z: z2 };
  };

  function frame() {
    const want = (hover || dragging) ? 0.05 : 1;
    ease += (want - ease) * 0.10;
    if (!REDUCED) { t += 0.0042; ot += 0.0042 * ease; }
    ctx.clearRect(0, 0, W, H);

    // orbit rings, drawn behind everything
    ctx.globalCompositeOperation = 'lighter';
    for (const b of bodies) {
      ctx.beginPath();
      for (let a = 0; a <= 64; a++) {
        const ang = (a / 64) * Math.PI * 2;
        const q = project(Math.cos(ang) * b.r, Math.sin(ang) * b.r * b.tilt, Math.sin(ang) * b.r);
        a ? ctx.lineTo(q.x, q.y) : ctx.moveTo(q.x, q.y);
      }
      ctx.strokeStyle = b.colour;
      ctx.globalAlpha = hover === b ? 0.30 : 0.075;
      ctx.lineWidth = hover === b ? 1.3 : 0.8;
      ctx.stroke();
    }

    // the XMAN star
    const c = project(0, 0, 0);
    const pulse = 1 + Math.sin(t * 22) * 0.05;
    const g = ctx.createRadialGradient(c.x, c.y, 0, c.x, c.y, 78 * pulse * view.dist);
    g.addColorStop(0, 'rgba(255,255,255,.95)');
    g.addColorStop(.18, 'rgba(160,235,255,.75)');
    g.addColorStop(.55, 'rgba(108,160,255,.16)');
    g.addColorStop(1, 'rgba(80,120,255,0)');
    ctx.globalAlpha = 1; ctx.fillStyle = g;
    ctx.beginPath(); ctx.arc(c.x, c.y, 78 * pulse * view.dist, 0, 6.283); ctx.fill();

    // bodies, painted far-to-near so nearer ones overlap correctly
    for (const b of bodies) {
      // 0.9, not 6 — the flagship now takes ~45s to come round instead of ~9
      const a = b.phase + ot * b.speed * 0.9;
      b.x = Math.cos(a) * b.r; b.y = Math.sin(a) * b.r * b.tilt; b.z = Math.sin(a) * b.r;
      const q = project(b.x, b.y, b.z);
      b.sx = q.x; b.sy = q.y; b.ss = q.s; b.vis = true;
    }
    bodies.sort((m, n) => n.z - m.z);

    for (const b of bodies) {
      const R = b.size * b.ss * view.dist;
      const on = hover === b;
      const halo = ctx.createRadialGradient(b.sx, b.sy, 0, b.sx, b.sy, R * (on ? 5 : 3.2));
      halo.addColorStop(0, b.colour); halo.addColorStop(1, 'rgba(0,0,0,0)');
      ctx.globalAlpha = on ? .5 : .22; ctx.fillStyle = halo;
      ctx.beginPath(); ctx.arc(b.sx, b.sy, R * (on ? 5 : 3.2), 0, 6.283); ctx.fill();

      ctx.globalAlpha = b.soon ? .62 : 1; ctx.fillStyle = b.colour;
      ctx.beginPath(); ctx.arc(b.sx, b.sy, R, 0, 6.283); ctx.fill();

      /* Labels only for the near half of each orbit, the flagship, or whatever
         is under the pointer. Sixteen labels drawn at once collided into an
         unreadable pile — and a name floating over a body on the FAR side of
         the star reads as belonging to whatever is in front of it. */
      if (on || b.flag || b.z < 0.06) {
        ctx.globalCompositeOperation = 'source-over';
        ctx.globalAlpha = on ? 1 : (b.flag ? .95 : .55 + (0.06 - b.z) * .5);
        ctx.fillStyle = on || b.flag ? '#fff' : 'rgba(226,232,255,.9)';
        ctx.font = `${on || b.flag ? 600 : 400} ${Math.max(10, (b.flag ? 14 : 11.5) * b.ss)}px Sora, system-ui, sans-serif`;
        ctx.textAlign = 'center';
        ctx.shadowColor = 'rgba(0,0,0,.95)'; ctx.shadowBlur = 9;
        ctx.fillText(b.name, b.sx, b.sy - R - 10);
        ctx.shadowBlur = 0;
        ctx.globalCompositeOperation = 'lighter';
      }
    }
    ctx.globalAlpha = 1; ctx.globalCompositeOperation = 'source-over';
    requestAnimationFrame(frame);
  }

  /* `pad` widens the hit disc for blunt pointers. A fingertip covers ~9mm and
     lands where the user is not looking, so a target sized for a 1px cursor
     reads as "nothing happened" on a phone. */
  const pick = (mx, my, pad = 0) => {
    let best = null, bd = 26 + pad;
    for (const b of bodies) {
      const d = Math.hypot(b.sx - mx, b.sy - my);
      if (d < bd + b.size * b.ss * 2) { bd = d; best = b; }
    }
    return best;
  };

  /* Pulled out of pointermove so a tap can reach it too. `blunt` swaps the
     call to action, because on a touch screen the second tap is the one that
     opens the product. */
  const select = (b, mx, my, blunt) => {
    hover = b;
    cv.style.cursor = b ? 'pointer' : 'grab';
    if (!b) { tip.hidden = true; return; }
    tipN.textContent = b.name;
    tipD.textContent = isTH() ? b.p[5] : b.p[6];
    $('#tip-go').textContent = b.href
      ? (blunt ? (isTH() ? 'แตะอีกครั้งเพื่อเข้าไปดู' : 'tap again to enter')
               : (isTH() ? 'คลิกเพื่อเข้าไปดู' : 'click to enter'))
      : (isTH() ? 'เร็ว ๆ นี้' : 'coming soon');
    tip.hidden = false;
    tip.style.left = mx + 'px'; tip.style.top = my + 'px';
  };

  cv.addEventListener('pointerdown', e => { dragging = true; moved = false; lx = e.clientX; ly = e.clientY; cv.setPointerCapture?.(e.pointerId); });
  cv.addEventListener('pointermove', e => {
    const r = cv.getBoundingClientRect(), mx = e.clientX - r.left, my = e.clientY - r.top;
    if (dragging) {
      const dx = e.clientX - lx, dy = e.clientY - ly;
      /* A mouse held still is still; a finger held still drifts several px.
         Judging both at 3px turned most real taps into micro-drags, which set
         `moved` and swallowed the release. */
      if (Math.abs(dx) + Math.abs(dy) > (e.pointerType === 'mouse' ? 3 : 10)) moved = true;
      view.yaw += dx * 0.006;
      view.pitch = Math.max(0.08, Math.min(1.35, view.pitch + dy * 0.005));
      lx = e.clientX; ly = e.clientY;
      return;
    }
    const b = pick(mx, my);
    if (b !== hover) select(b, mx, my, false);
    else if (b) { tip.style.left = mx + 'px'; tip.style.top = my + 'px'; }
  });
  const end = () => { dragging = false; };
  cv.addEventListener('pointerup', e => {
    if (moved) { end(); return; }
    if (e.pointerType === 'mouse') {
      if (hover?.href) location.href = hover.href;
      end(); return;
    }
    /* Touch has no hover state to inherit. A finger only ever arrives with the
       button already down, and the pointermove handler above returns early
       while `dragging`, so `hover` was never once assigned on a phone — the
       old `if (!moved && hover?.href)` could not fire, and tapping a body did
       nothing at all. Worse, the tooltip carrying each product's description
       was unreachable on touch entirely. So resolve the body from where the
       finger actually lifted: first tap selects it and pins the tooltip (the
       orbit eases to a near-stop, keeping it under the thumb), second tap on
       the same body opens it. Tapping empty space clears the selection. */
    const r = cv.getBoundingClientRect(), mx = e.clientX - r.left, my = e.clientY - r.top;
    const b = pick(mx, my, 12);
    if (b && b === hover && b.href) location.href = b.href;
    else select(b, mx, my, true);
    end();
  });
  cv.addEventListener('pointercancel', end);
  cv.addEventListener('pointerleave', e => {
    /* A touch pointer stops existing the instant the finger lifts, so the
       browser fires pointerleave immediately behind every pointerup. Clearing
       unconditionally here would wipe the selection the tap just made and the
       second tap could never match it. */
    if (e.pointerType === 'mouse') { hover = null; tip.hidden = true; }
    end();
  });
  cv.addEventListener('wheel', e => {
    e.preventDefault();
    view.dist = Math.max(0.55, Math.min(2.2, view.dist * (e.deltaY > 0 ? 0.92 : 1.08)));
  }, { passive: false });

  addEventListener('resize', resize, { passive: true });
  resize(); frame();
}

/* ── the grid ─────────────────────────────────────────────── */
let currentFilter = '*';
function esc(s){ return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function drawGrid(filter) {
  currentFilter = filter;
  const pg = $('#prod-grid'); if (!pg) return;
  const th = isTH();
  pg.innerHTML = P.filter(p => filter === '*' || p[2] === filter).map(p => {
    const [n, sub, cat, price, soon, dTh, dEn, col, href] = p;
    const priceTxt = price === null ? (th ? 'สอบถาม' : 'Enquire')
                   : price === 0   ? (th ? 'ฟรี' : 'Free')
                   : '฿' + price.toLocaleString();
    const tag = href ? `<span class="prod-soon" style="border-color:rgba(108,240,255,.5);color:#6cf0ff">${th?'เรือธง':'Flagship'}</span>`
              : soon ? `<span class="prod-soon">${th?'เร็ว ๆ นี้':'Coming soon'}</span>` : '';
    const open = href ? `<a class="prod-open" href="${href}">${th?'เข้าไปดู':'Enter'} →</a>` : '';
    return `<article class="prod reveal${href?' is-link':''}" style="--tint:${col}22;--tint2:${col}">
      ${href ? `<a class="prod-hit" href="${href}" aria-label="${esc(n)}"></a>` : ''}
      <div class="prod-top"><div class="prod-ico">${esc(n.slice(0,2).toUpperCase())}</div>${tag}</div>
      <h3>${esc(n)} <span style="color:var(--ink-3);font-weight:400;font-size:12px">${esc(sub)}</span></h3>
      <p>${esc(th ? dTh : dEn)}</p>
      <div class="prod-foot"><span class="prod-cat">${esc(cat)}</span>
        <span class="prod-price${price?'':' free'}">${priceTxt}</span></div>
      ${open}
    </article>`;
  }).join('');
  window.__revealWatch?.();
}

function grid() {
  const cats = [...new Set(P.map(p => p[2]))];
  const fl = $('#filters');
  if (fl) fl.innerHTML = `<button class="fchip on" data-f="*" data-th="ทั้งหมด" data-en="All">ทั้งหมด</button>`
    + cats.map(c => `<button class="fchip" data-f="${esc(c)}">${esc(c)}</button>`).join('');
  drawGrid('*');
  $$('.fchip').forEach(b => b.addEventListener('click', () => {
    $$('.fchip').forEach(x => x.classList.remove('on')); b.classList.add('on'); drawGrid(b.dataset.f);
  }));
}

function motion() {
  const showAll = () => $$('.reveal').forEach(el => el.classList.add('in'));
  if (!('IntersectionObserver' in window)) { showAll(); window.__revealWatch = showAll; return; }
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
  }), { threshold: .12, rootMargin: '0px 0px -8% 0px' });
  const watch = () => $$('.reveal:not(.in)').forEach(el => io.observe(el));
  watch(); window.__revealWatch = watch;
  setTimeout(() => { if (!$('.reveal.in')) showAll(); }, 1200);
  addEventListener('scroll', () => $('#nav')?.classList.toggle('stuck', scrollY > 40), { passive: true });
}

function boot() {
  document.documentElement.classList.add('js');
  /* Rewrite the hint before language() reads it out of these attributes: on a
     hover-less screen the old copy promised a scroll-to-zoom that only the
     wheel handler implements, and a single click that is now a two-tap. */
  if (matchMedia('(hover: none)').matches) {
    const hint = $('.hint-line');
    hint?.setAttribute('data-th', 'ลากเพื่อหมุน · แตะดวงเพื่อดู · แตะอีกครั้งเพื่อเข้าไป');
    hint?.setAttribute('data-en', 'Drag to turn · tap a body to preview · tap again to enter');
  }
  isTH = language();
  grid(); motion(); sky(); constellation();
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
