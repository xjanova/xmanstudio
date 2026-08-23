/* BrainX — product.xman4289.com
   No framework, no CDN: the 3D is hand-written on a 2D canvas with a
   perspective projection, so the page has zero dependencies and cannot be
   broken by a blocked script host. */
(() => {
'use strict';
const $  = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const REDUCED = matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ══ 1. Living background: a neural galaxy in real 3D ═══════════════
   Same idea as the product itself — subjects become clusters, notes become
   stars, links become filaments — drawn faintly so it reads as atmosphere
   behind the type rather than competing with it. */
function galaxy() {
  const cv = $('#bg-galaxy');
  if (!cv) return;
  const ctx = cv.getContext('2d', { alpha: true });
  let W = 0, H = 0, DPR = 1;

  // deterministic: the same sky on every visit
  let seed = 20260803;
  const rnd = () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296);

  const PAL = ['#6cf0ff', '#8b7cf6', '#e8825a', '#57e08a', '#7dd3fc'];
  const CLUSTERS = 7, PER = 46;
  const nodes = [], links = [];

  for (let c = 0; c < CLUSTERS; c++) {
    // cluster centres on a Fibonacci-ish sphere so they never bunch up
    const y = 1 - (c / (CLUSTERS - 1)) * 2;
    const r = Math.sqrt(Math.max(0, 1 - y * y));
    const th = c * 2.399963;
    const C = { x: Math.cos(th) * r * 300, y: y * 190, z: Math.sin(th) * r * 300 };
    const col = PAL[c % PAL.length];
    const start = nodes.length;
    for (let i = 0; i < PER; i++) {
      const a = rnd() * Math.PI * 2, b = Math.acos(2 * rnd() - 1), rr = 40 + Math.pow(rnd(), .6) * 92;
      nodes.push({
        x: C.x + rr * Math.sin(b) * Math.cos(a),
        y: C.y + rr * Math.sin(b) * Math.sin(a) * .65,
        z: C.z + rr * Math.cos(b),
        c: col, s: .5 + rnd() * 1.7, tw: rnd() * 6.28,
      });
    }
    // filaments inside the cluster
    for (let i = 0; i < PER * 1.25; i++) {
      const a = start + ((rnd() * PER) | 0), b2 = start + ((rnd() * PER) | 0);
      if (a !== b2) links.push([a, b2, col]);
    }
    // a few bridges to the previous cluster — the graph is one object
    if (c > 0) for (let i = 0; i < 4; i++)
      links.push([start + ((rnd() * PER) | 0), ((rnd() * start) | 0), col]);
  }

  // background stars, biased toward a band so the sky has a Milky Way in it
  const sky = [];
  for (let i = 0; i < 420; i++) {
    const a = rnd() * Math.PI * 2;
    const lat = ((rnd() + rnd() + rnd() - 1.5) / 1.5) * 0.34;
    const R = 900 + rnd() * 340, cl = Math.cos(lat);
    sky.push({ x: R * cl * Math.cos(a), y: R * Math.sin(lat), z: R * cl * Math.sin(a), s: rnd() * 1.25 + .25 });
  }

  const view = { rx: -0.34, ry: 0.2, tx: 0, ty: 0 };
  let mx = 0, my = 0, scrollN = 0, t = 0;

  function resize() {
    DPR = Math.min(devicePixelRatio || 1, 1.75);
    /* Fall back to the viewport. The element is fixed and inset:0, so the
       viewport IS its size — and asking the element can return 0 if the
       stylesheet has not been applied at the moment this first runs, which
       leaves a zero-size buffer and a permanently blank canvas. */
    W = cv.clientWidth || innerWidth; H = cv.clientHeight || innerHeight;
    cv.width = (W * DPR) | 0; cv.height = (H * DPR) | 0;
    ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
  }

  const P = { x: 0, y: 0, s: 0, v: false };
  function project(p, cy, sy, cx, sx, fx, fy, cz) {
    const x1 = p.x * cy - p.z * sy, z1 = p.x * sy + p.z * cy;
    const y1 = p.y * cx - z1 * sx, z2 = p.y * sx + z1 * cx + cz;
    if (z2 < 40) { P.v = false; return P; }
    const k = 620 / z2;
    P.x = fx + x1 * k; P.y = fy + y1 * k; P.s = k; P.v = true;
    return P;
  }

  function frame() {
    t += REDUCED ? 0 : 0.0022;
    ctx.clearRect(0, 0, W, H);

    const ry = view.ry + t + mx * 0.28;
    const rx = view.rx + my * 0.2 + scrollN * 0.36;
    const cy = Math.cos(ry), sy = Math.sin(ry), cx = Math.cos(rx), sx = Math.sin(rx);
    /* Focal point sits LOW. Centred, the cluster landed exactly on the hero
       headline and turned 80px type into confetti. */
    const fx = W * 0.5, fy = H * 0.78 - scrollN * H * 0.16, cz = 780;

    // sky band first
    ctx.globalCompositeOperation = 'lighter';
    for (const s of sky) {
      const p = project(s, cy, sy, cx, sx, fx, fy, cz);
      if (!p.v) continue;
      ctx.globalAlpha = 0.30 * p.s;
      ctx.fillStyle = '#cfe6ff';
      ctx.fillRect(p.x, p.y, s.s, s.s);
    }

    // filaments
    ctx.lineWidth = 0.7;
    for (const [a, b, col] of links) {
      const pa = project(nodes[a], cy, sy, cx, sx, fx, fy, cz);
      if (!pa.v) continue;
      const ax = pa.x, ay = pa.y, as = pa.s;
      const pb = project(nodes[b], cy, sy, cx, sx, fx, fy, cz);
      if (!pb.v) continue;
      ctx.globalAlpha = 0.055 * Math.min(as, pb.s) * 1.6;
      ctx.strokeStyle = col;
      ctx.beginPath(); ctx.moveTo(ax, ay); ctx.lineTo(pb.x, pb.y); ctx.stroke();
    }

    // stars
    for (const n of nodes) {
      const p = project(n, cy, sy, cx, sx, fx, fy, cz);
      if (!p.v) continue;
      const tw = 0.72 + 0.28 * Math.sin(t * 26 + n.tw);
      ctx.globalAlpha = Math.min(0.85, 0.30 * p.s * 2.1) * tw;
      ctx.fillStyle = n.c;
      const r = Math.max(0.5, n.s * p.s * 1.5);
      ctx.beginPath(); ctx.arc(p.x, p.y, r, 0, 6.283); ctx.fill();
    }
    ctx.globalAlpha = 1;
    ctx.globalCompositeOperation = 'source-over';
    requestAnimationFrame(frame);
  }

  addEventListener('resize', resize, { passive: true });
  addEventListener('pointermove', (e) => {
    mx = (e.clientX / innerWidth - .5) * 2; my = (e.clientY / innerHeight - .5) * 2;
  }, { passive: true });
  addEventListener('scroll', () => {
    scrollN = Math.min(1.6, scrollY / Math.max(1, innerHeight));
  }, { passive: true });
  resize(); frame();
}

/* ══ 2. Language ═══════════════════════════════════════════════════
   A real switch, not two stacked labels: the page is aimed at a global
   audience and doubling every line halves the reading speed for both. */
function language() {
  const root = document.documentElement;
  const KEY = 'brainx.lang';
  const apply = (l) => {
    root.setAttribute('data-lang', l);
    root.setAttribute('lang', l === 'th' ? 'th' : 'en');
    $$('[data-th]').forEach(el => {
      const v = el.getAttribute(l === 'th' ? 'data-th' : 'data-en');
      if (v != null) el.textContent = v;
    });
    try { localStorage.setItem(KEY, l); } catch {}
  };
  /* Saved choice wins; otherwise follow the browser. Written out rather than
     chained with || and ?: — `a || b ? 'th' : 'en'` reads like "saved, else
     browser" and actually means "if EITHER is truthy, Thai", which turned a
     stored 'en' back into Thai on every reload. */
  let start = null;
  try { start = localStorage.getItem(KEY); } catch {}
  if (start !== 'th' && start !== 'en') {
    start = (navigator.language || '').toLowerCase().startsWith('th') ? 'th' : 'en';
  }
  apply(start);
  $('#lang-toggle')?.addEventListener('click', () =>
    apply(root.getAttribute('data-lang') === 'th' ? 'en' : 'th'));
}

/* ══ 3. Reveal + counters + scroll rail ════════════════════════════ */
function motion() {
  const showAll = () => $$('.reveal').forEach(el => el.classList.add('in'));
  if (!('IntersectionObserver' in window)) { showAll(); window.__revealWatch = showAll; }
  else {
    const io = new IntersectionObserver((es) => {
      es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    const watch = () => $$('.reveal:not(.in)').forEach(el => io.observe(el));
    watch(); window.__revealWatch = watch;
    /* Backstop: if the observer has delivered nothing after a second — a
       browser that throttles it, a tab restored from bfcache, anything — stop
       trusting it and just show the page. Invisible content is never the
       better failure. */
    setTimeout(() => { if (!$('.reveal.in')) showAll(); }, 1200);
  }

  $$('[data-count]').forEach(el => {
    const target = parseFloat(el.dataset.count), suf = el.dataset.suffix || '';
    const dec = target % 1 !== 0 ? 1 : 0;
    const fmt = (v) => (dec ? v.toFixed(1) : Math.round(v).toLocaleString('en-US')) + suf;
    const o = new IntersectionObserver((es) => {
      if (!es[0].isIntersecting) return; o.disconnect();
      if (REDUCED) { el.textContent = fmt(target); return; }
      const T = 1500, t0 = performance.now();
      const tick = (now) => {
        const k = Math.min(1, (now - t0) / T);
        el.textContent = fmt(target * (1 - Math.pow(1 - k, 3)));
        if (k < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    }, { threshold: .5 });
    o.observe(el);
  });

  const rail = $('#scroll-rail-fill'), nav = $('#nav');
  addEventListener('scroll', () => {
    const h = document.body.scrollHeight - innerHeight;
    if (rail) rail.style.width = (h > 0 ? (scrollY / h) * 100 : 0) + '%';
    nav?.classList.toggle('stuck', scrollY > 40);
  }, { passive: true });
}

/* ══ 4. Content ════════════════════════════════════════════════════ */
const FEATURES = [
  ['GRAPH', 'จักรวาลความรู้ 3 มิติ', 'Live 3D universe',
   'ทุกโน้ตเป็นดาว ทุกลิงก์เป็นเส้นแรง จัดวางด้วยฟิสิกส์จริง หมุน ซูม คลิกเพื่อเปิดโน้ตได้ทันที',
   'Every note a star, every link a force, laid out by a real force simulation. Orbit, zoom, click straight into a note.'],
  ['SEARCH', 'ค้นแบบ Hybrid', 'Hybrid search',
   'รวมการค้นด้วยความหมาย (embeddings) เข้ากับคำสำคัญ ด้วย reciprocal-rank fusion — เจอทั้งคำที่พิมพ์ตรงและความคิดที่ใกล้เคียง',
   'Embedding similarity fused with keyword ranking by reciprocal-rank fusion — it finds both the exact term and the idea next door.'],
  ['THAI', 'เข้าใจภาษาไทยจริง', 'Real Thai support',
   'ค้นภาษาไทยแบบไม่ต้องเว้นวรรคด้วย n-gram matching และคิดโทเคนภาษาไทยถูกต้อง ไม่ใช่หารสี่',
   'Thai search works without spaces via n-gram matching, and Thai tokens are counted properly rather than estimated as chars/4.'],
  ['MCP', 'เป็น MCP Server', 'An MCP server',
   'เปิดเครื่องมือให้ Claude Code, CluadeX และไคลเอนต์ MCP อื่นเข้ามาค้น อ่าน เขียน และเชื่อมโน้ตได้โดยตรง',
   'Exposes tools so Claude Code, CluadeX and any MCP client can search, read, write and link notes directly.'],
  ['HUB', 'เป็น MCP Hub ด้วย', 'And an MCP hub',
   'ต่อออกไปคุมโปรแกรมอื่น เช่น Unity Editor — เอเจนต์ที่ต่อสมองอยู่แล้วได้เครื่องมือเพิ่มฟรี ๆ',
   'Bridges outward to other tools such as the Unity Editor — any agent already on the brain gains them with no extra config.'],
  ['BUS', 'Agent Bus ข้ามยี่ห้อ', 'Cross-vendor Agent Bus',
   'ส่งข้อความระหว่าง Claude, Codex, CluadeX ผ่านสมอง เห็นสถานะออนไลน์และกล่องข้อความค้างของแต่ละตัว',
   'Messages between Claude, Codex and CluadeX through the brain, with live presence and per-agent inboxes.'],
  ['HUD', 'HUD จัดวางเองได้', 'A HUD you arrange',
   'ทุกการ์ดลากย้าย ยืดขยาย ดับเบิลคลิกพอดีเนื้อหา ดูดขอบอัตโนมัติ จำตำแหน่งไว้ และรีเซ็ตได้ปุ่มเดียว',
   'Drag, resize, double-click to fit, magnetic snapping, remembered across launches, and one button to put it all back.'],
  ['GARDEN', 'ตัวสวนอัตโนมัติ', 'The self-gardener',
   'อบ embedding ที่ค้าง ตรวจลิงก์เสีย หาโน้ตขัดแย้ง แล้วเขียนรายงานสุขภาพสมอง ทำงานตอนเครื่องว่าง',
   'Re-bakes stale embeddings, audits links, finds contradictions and writes a health report — while the machine is idle.'],
  ['LOCAL', 'ทำงานบนเครื่องคุณ', 'Local-first',
   'ไฟล์ยังเป็น Markdown บนดิสก์ของคุณ ดัชนีอยู่ใน SQLite ในเครื่อง ไม่มีคลาวด์บังคับ',
   'Your files stay as Markdown on your disk, the index in a local SQLite database. No mandatory cloud.'],
  ['EXPERT', 'โปรไฟล์ความเชี่ยวชาญ', 'Expertise profile',
   'วัดจากสิ่งที่คุณเขียนจริง แยกตามหมวด บอกได้ว่าคุณแข็งตรงไหนและบางตรงไหน',
   'Scored from what you actually wrote, per subject — showing where you are deep and where you are thin.'],
  ['SCOPE', 'รู้ว่ากฎเป็นของโปรเจกต์ไหน', 'Scoped knowledge',
   'โน้ตมี kind และ scope ผลการค้นบอกได้ว่ากฎข้อนี้เป็นของโปรเจกต์ใด ไม่เอากฎข้ามโปรเจกต์มาปนกัน',
   'Notes carry kind and scope, so a result can say whose rules it is quoting instead of mixing projects together.'],
  ['STOP', 'งานยาวหยุดได้ทุกอัน', 'Every long job stoppable',
   'สแกนทั้งเครื่อง จัดสวน หรือ re-index — ปุ่มที่เริ่มคือปุ่มที่หยุด พร้อมบอกความคืบหน้า',
   'Whole-machine scans, gardening, re-indexing: the button that starts it is the button that stops it, with progress.'],
  ['UPDATE', 'อัปเดตเองตอนคุณไม่อยู่', 'Updates itself politely',
   'ตรวจรุ่นใหม่เป็นระยะ แล้วติดตั้งตอนเครื่องว่าง ไม่ขัดจังหวะกลางงาน',
   'Polls for releases and applies them once you have been idle — never in the middle of your work.'],
  ['WALL', 'ใช้เป็นวอลเปเปอร์ได้', 'Runs as wallpaper',
   'ส่งจักรวาลความรู้ไปเป็นพื้นหลังเดสก์ท็อป รองรับหลายจอ แต่ละจอมีฉากของตัวเอง',
   'Push the knowledge universe onto the desktop as live wallpaper, one scene per monitor.'],
  ['AUDIT', 'ตรวจสอบย้อนหลังได้', 'Fully auditable',
   'ทุกการเรียกเครื่องมือถูกบันทึกลงสมองพร้อมชื่อผู้เรียก ไม่ใช่แค่ชื่อช่องทาง',
   'Every tool call is journalled into the brain with the name of the actor — not just the transport it arrived on.'],
  ['I18N', 'ไทย / อังกฤษ', 'Thai / English',
   'อินเทอร์เฟซและการค้นหารองรับทั้งสองภาษาเต็มรูปแบบ',
   'Interface and retrieval both work fully in either language.'],
];

const CODEX = [
  ['กราฟความรู้ทำงานยังไง', 'How the knowledge graph works',
   `<p>BrainX อ่านไฟล์ Markdown ทุกไฟล์ในโฟลเดอร์ที่คุณชี้ให้ แยกหัวข้อ แท็ก และ <code>[[wiki-link]]</code> ออกมาเป็นโครงสร้าง จากนั้นให้แต่ละโน้ตเป็น <b>โหนด</b> และแต่ละลิงก์เป็น <b>เส้นเชื่อม</b></p>
    <p>การจัดวางไม่ได้ใช้ตำแหน่งที่กำหนดไว้ล่วงหน้า แต่ใช้การจำลองแรง (force simulation) โน้ตที่เชื่อมกันจะดึงเข้าหากัน โน้ตที่ไม่เกี่ยวกันจะผลักออก ผลลัพธ์คือกลุ่มก้อนที่เกิดขึ้นเองตามเนื้อหาจริง ไม่ใช่ตามที่ใครจัดไว้</p>
    <p>หมวดหลักของโน้ตกลายเป็น <b>กาแล็กซี</b> — วางบนทรงกลม Fibonacci เพื่อให้แยกจากกันชัดเจนแม้มีหลายสิบหมวด</p>`,
   `<p>BrainX reads every Markdown file in the folder you point it at, extracting headings, tags and <code>[[wiki-links]]</code>. Each note becomes a <b>node</b>; each link an <b>edge</b>.</p>
    <p>Nothing is placed by hand. A force simulation pulls linked notes together and pushes unrelated ones apart, so the clusters that appear are the ones actually present in your writing — not ones somebody arranged.</p>
    <p>A note's primary subject becomes a <b>galaxy</b>, positioned on a Fibonacci sphere so dozens of subjects stay visually separated.</p>`],
  ['ค้นหาแบบ Hybrid คืออะไร', 'What hybrid search means',
   `<p>การค้นด้วยคำสำคัญอย่างเดียวพลาดโน้ตที่พูดเรื่องเดียวกันด้วยคำอื่น การค้นด้วยความหมายอย่างเดียวก็พลาดรหัส ชื่อเฉพาะ และเลขเวอร์ชัน</p>
    <p>BrainX จึงทำทั้งสองอย่างแล้วรวมผลด้วย <b>reciprocal-rank fusion</b> — โน้ตที่ติดอันดับสูงจากทั้งสองวิธีจะขึ้นก่อน</p>
    <ul><li>ฝั่งความหมายใช้ embedding แบบหลายภาษา ทำงานได้ในเครื่อง</li>
    <li>ฝั่งคำสำคัญรองรับภาษาไทยแบบไม่ต้องเว้นวรรค ด้วย n-gram</li>
    <li>กรองด้วยหมวด แท็ก หรือ scope ได้ก่อนเข้าขั้นตอนเทียบความหมาย ทำให้เร็วกว่าการกรองทีหลัง</li></ul>`,
   `<p>Keyword search alone misses the note that says the same thing in different words. Semantic search alone misses identifiers, code names and version numbers.</p>
    <p>BrainX runs both and fuses them with <b>reciprocal-rank fusion</b>, so anything ranked highly by either method surfaces.</p>
    <ul><li>The semantic side uses multilingual embeddings, computed locally.</li>
    <li>The keyword side handles Thai without spaces through n-gram matching.</li>
    <li>Category, tag and scope filters apply <em>before</em> the similarity pass — far faster than filtering afterwards.</li></ul>`],
  ['MCP คืออะไร และทำไมถึงสำคัญ', 'What MCP is, and why it matters',
   `<p>Model Context Protocol เป็นมาตรฐานเปิดที่ให้ผู้ช่วย AI เรียกใช้เครื่องมือภายนอกได้ BrainX เปิดตัวเองเป็น MCP server ทำให้ผู้ช่วยที่รองรับสามารถ <b>ค้น อ่าน เขียน และเชื่อม</b> โน้ตของคุณได้โดยตรง</p>
    <p>ผลที่ตามมาสำคัญกว่าที่คิด: บทสนทนากับ AI ไม่ต้องเริ่มจากศูนย์อีกต่อไป สิ่งที่คุยกันเมื่อวานถูกเขียนกลับเข้าสมอง และพรุ่งนี้ AI ตัวอื่นก็อ่านต่อได้</p>
    <p>BrainX ยังเป็น <b>hub</b> ที่ต่อออกไปยัง MCP server ตัวอื่นได้ด้วย เครื่องมือของ Unity Editor จึงโผล่มาอยู่ข้าง ๆ เครื่องมือของสมองเอง โดยเอเจนต์ไม่ต้องตั้งค่าอะไรเพิ่ม และทุกการเรียกถูกบันทึกไว้ที่สมอง</p>`,
   `<p>The Model Context Protocol is an open standard that lets AI assistants call external tools. BrainX exposes itself as an MCP server, so a supporting assistant can <b>search, read, write and link</b> your notes directly.</p>
    <p>The consequence matters more than it sounds: conversations stop starting from zero. What you worked out yesterday is written back into the brain, and tomorrow a different assistant reads it.</p>
    <p>BrainX is also a <b>hub</b> that bridges outward to other MCP servers, so the Unity Editor's tools appear alongside the brain's own with no per-client configuration — and every call is journalled into the brain.</p>`],
  ['Agent Bus — ให้ AI คุยกันเอง', 'The Agent Bus — agents talking',
   `<p>Claude กับ Codex เป็นคนละผลิตภัณฑ์ คนละบริษัท ไม่มีช่องทางคุยกันโดยตรง แต่ถ้าทั้งคู่ต่ออยู่กับสมองก้อนเดียวกัน สมองก็เป็นคนกลางให้ได้</p>
    <p>ตัวตนของแต่ละเอเจนต์มาจากข้อมูลตอนเชื่อมต่อ (MCP <code>initialize</code>) <b>ไม่ใช่จากพารามิเตอร์ที่ส่งมา</b> — เอเจนต์จึงปลอมเป็นตัวอื่นไม่ได้</p>
    <p>หน้าจอแสดงผลเป็นระบบสุริยะ: สมองคือดาวฤกษ์ตรงกลาง เอเจนต์แต่ละตัวโคจรรอบ ๆ และทราฟฟิกจริงวิ่งเป็นเม็ดแสงเข้า-ออก พร้อมรายการข้อความด้านล่างที่บอกว่าใครทำอะไร</p>`,
   `<p>Claude and Codex are different products from different companies with no channel between them. If both are connected to the same brain, the brain can be the middleman.</p>
    <p>Each agent's identity comes from its connection handshake (MCP <code>initialize</code>), <b>never from a tool argument</b> — so no agent can impersonate another.</p>
    <p>The display is a solar system: the brain is the star, each agent a planet on its own orbit, and real traffic flies between them as motes of light — with a ticker underneath naming who did what.</p>`],
  ['ความเป็นส่วนตัวและการเก็บข้อมูล', 'Privacy and where data lives',
   `<p>BrainX เป็นโปรแกรมเดสก์ท็อป ไม่ใช่บริการคลาวด์ โน้ตของคุณยังเป็นไฟล์ Markdown อยู่บนดิสก์ของคุณ อ่านได้ด้วยโปรแกรมอะไรก็ได้ และยังใช้ Obsidian เปิดได้ตามปกติ</p>
    <p>ดัชนีและ embedding เก็บอยู่ในฐานข้อมูล SQLite ในเครื่อง การฝัง embedding ทำในเครื่องได้ผ่าน Ollama โดยไม่ต้องส่งเนื้อหาออกไปไหน</p>
    <p>สะพานที่ต่อออกไปหาโปรแกรมอื่นถูกจำกัดให้ทำงานเฉพาะในเครื่องเท่านั้น ผู้ที่เชื่อมต่อจากระยะไกลเข้าถึงไม่ได้เลย</p>`,
   `<p>BrainX is a desktop application, not a cloud service. Your notes remain Markdown files on your own disk, readable by anything, and still openable in Obsidian.</p>
    <p>The index and embeddings live in a local SQLite database. Embeddings can be computed on-machine through Ollama, so nothing needs to leave the computer.</p>
    <p>Outbound bridges to other software are restricted to local use only — remote callers can never reach them.</p>`],
  ['ต้องใช้เครื่องแรงแค่ไหน', 'What it needs to run',
   `<p>Windows 10 หรือ 11 พร้อม .NET runtime การแสดงผลสามมิติใช้ WebGL ผ่าน WebView2 ซึ่งการ์ดจอออนบอร์ดทั่วไปก็เอาอยู่</p>
    <p>ขนาดกราฟที่ทดสอบจริง: <b>1,204 โน้ต · 8,458 เส้นเชื่อม · 1.5 ล้านคำ</b> ทำงานลื่นบนเครื่องเดสก์ท็อปทั่วไป ฐานข้อมูลหลังปรับปรุงเหลือระดับไม่กี่เมกะไบต์</p>
    <p>ถ้าจะใช้ embedding ในเครื่อง แนะนำให้มี Ollama ติดตั้งไว้ แต่ไม่บังคับ — ระบบจะถอยไปใช้การค้นด้วยคำสำคัญได้เอง</p>`,
   `<p>Windows 10 or 11 with the .NET runtime. The 3D view is WebGL through WebView2, which ordinary integrated graphics handle comfortably.</p>
    <p>Measured on a real vault: <b>1,204 notes · 8,458 links · 1.5 million words</b>, running smoothly on a normal desktop, with the database trimmed to single-digit megabytes.</p>
    <p>Local embeddings want Ollama installed, but it is optional — the system falls back to keyword retrieval on its own.</p>`],
];

const PRODUCTS = [
  ['BrainX','Neural Knowledge Engine','AI & Automation',null,0,'สมองที่สองที่มองเห็นได้ กราฟความรู้ 3 มิติ + MCP hub สำหรับเอเจนต์ทุกตัว','A second brain you can see: a 3D knowledge graph and an MCP hub for every agent you use.','#6cf0ff',1],
  ['AutoTradeX','Crypto Arbitrage Bot','Software',19900,0,'บอทเทรด Crypto Arbitrage อัตโนมัติ รองรับ 6 Exchange ชั้นนำ พร้อมโหมดทดลองและระบบจัดการความเสี่ยง','Automated crypto arbitrage across 6 major exchanges, with simulation mode and risk controls.','#57e08a',0],
  ['PostXAgent','AI Brand Promotion','AI & Automation',7990,1,'โพสต์อัตโนมัติ 9 แพลตฟอร์ม พร้อม Web Automation ที่เรียนรู้เองและซ่อมตัวเองได้','Auto-posting across 9 platforms with self-learning, self-repairing web automation.','#8b7cf6',0],
  ['Live x Shop Pro','Live Commerce Platform','E-Commerce',5990,1,'รวมแชท Facebook/TikTok/LINE อ่านสลิปด้วย AI OCR ตรวจสลิปปลอม เชื่อมขนส่งครบ','Unified Facebook/TikTok/LINE chat, AI OCR slip reading, fraud detection and courier integration.','#e8825a',0],
  ['XcluadeAgent','GitHub Sync + AI Auto-Fix','AI & Automation',3490,1,'ซิงก์ release อัตโนมัติ พร้อม AI 6 โหมด ย้อนกลับเองเมื่อพัง แจ้งเตือน 5 ช่องทาง','Automated release sync with six AI modes, auto-rollback on failure and five notification channels.','#7dd3fc',0],
  ['SpiderX','P2P Mesh Network','Network & Security',2990,1,'เครือข่ายกระจายศูนย์ เข้ารหัส End-to-End ไม่มีเซิร์ฟเวอร์กลาง ไม่ต้องลงทะเบียน','Decentralised end-to-end encrypted mesh — no central server, no registration.','#57e08a',0],
  ['PhoneX Manager','Android Service Suite','Mobile Tools',1990,1,'Flash ROM, Backup, FRP Bypass, IMEI Tools รองรับ Qualcomm, MTK, Samsung, Xiaomi','Flashing, backup, FRP bypass and IMEI tools for Qualcomm, MediaTek, Samsung and Xiaomi.','#8b7cf6',0],
  ['WinXTools','Network & System Control','System Utilities',990,1,'ดู bandwidth แยกโปรเซส คุมระดับ kernel ด้วย WFP ถอนโปรแกรมลึกถึง registry','Per-process bandwidth, kernel-level WFP control and a deep uninstaller that reaches the registry.','#6cf0ff',0],
  ['SMS Payment Checker','WooCommerce Plugin','WordPress Plugins',990,0,'ตรวจเงินเข้าจาก SMS ธนาคาร 15+ แห่ง จับคู่ออเดอร์อัตโนมัติ เข้ารหัส AES-256-GCM','Reads bank SMS from 15+ Thai banks, matches orders automatically, AES-256-GCM encrypted.','#57e08a',0],
  ['SmsChecker','Bank SMS Automation','Mobile Tools',499,0,'ตรวจ SMS ธนาคาร 14+ แห่ง อนุมัติออเดอร์เรียลไทม์ ต่อหลายเซิร์ฟเวอร์พร้อมกัน','Detects bank SMS from 14+ banks, approves orders in real time, multi-server capable.','#e8825a',0],
  ['Tping','Android Auto-Typer','Mobile Tools',399,0,'บันทึกขั้นตอนแล้วเล่นซ้ำได้ถึง 999 รอบ มีโหมดเกมพร้อม crosshair overlay','Record a sequence and replay it up to 999 times, with a game mode and crosshair overlay.','#7dd3fc',0],
  ['Skidrow Killer','Malware Scanner','Security Software',299,1,'สแกนมัลแวร์เรียลไทม์ วิเคราะห์พฤติกรรมด้วย AI เฝ้า registry และบล็อกเซิร์ฟเวอร์ C2','Real-time malware scanning with AI behavioural analysis, registry watch and C2 blocking.','#e8825a',0],
  ['CluadeX','AI Coding Assistant','AI & Automation',199,0,'ผู้ช่วยเขียนโค้ดที่รันบนเครื่องคุณ 5 ผู้ให้บริการ AI, 28 เครื่องมือ, 22+ โมเดลในเครื่อง','A coding assistant that runs on your machine: 5 AI providers, 28 agent tools, 22+ local models.','#8b7cf6',0],
  ['GPUsharX','Decentralised GPU Sharing','Cloud & Computing',0,1,'แชร์การ์ดจอสร้างรายได้ ผู้แชร์ได้ 90% พร้อมระบบกันโกงหลายชั้น','Rent out your GPU and keep 90% of the revenue, with multi-layer anti-cheat.','#6cf0ff',0],
  ['LocalVPN','Virtual LAN over Internet','Network Tools',0,0,'สร้างวง LAN เสมือนข้ามอินเทอร์เน็ต เข้ารหัสด้วย WireGuard ทะลุ NAT ได้','A virtual LAN across the internet, WireGuard-encrypted, with NAT traversal.','#57e08a',0],
  ['Aipray','Buddhist Chanting Companion','Software',0,0,'บทสวด 20+ บท AI ฟังเสียงจับตำแหน่งอัตโนมัติ นับรอบให้ ใช้ออฟไลน์ได้ ฟรีตลอดไป','20+ chants with AI listening that tracks your place and counts repetitions. Offline. Free forever.','#e8825a',0],
];

function render() {
  const th = () => document.documentElement.getAttribute('data-lang') === 'th';

  // features
  const fg = $('#feat-grid');
  if (fg) fg.innerHTML = FEATURES.map(([tag, tTh, tEn, dTh, dEn], i) => `
    <article class="feat reveal" data-d="${(i % 4) + 1}">
      <span class="feat-tag">${tag}</span>
      <b data-th="${esc(tTh)}" data-en="${esc(tEn)}">${esc(tTh)}</b>
      <span data-th="${esc(dTh)}" data-en="${esc(dEn)}">${esc(dTh)}</span>
    </article>`).join('');

  // encyclopedia
  const cl = $('#codex-list');
  if (cl) cl.innerHTML = CODEX.map(([tTh, tEn, bTh, bEn], i) => `
    <div class="cdx reveal">
      <button class="cdx-btn" type="button" aria-expanded="false">
        <span class="cdx-n">${String(i + 1).padStart(2, '0')}</span>
        <span class="cdx-t" data-th="${esc(tTh)}" data-en="${esc(tEn)}">${esc(tTh)}</span>
        <span class="cdx-x" aria-hidden="true"></span>
      </button>
      <div class="cdx-body"><div class="cdx-in" data-html-th="${esc(bTh)}" data-html-en="${esc(bEn)}">${bTh}</div></div>
    </div>`).join('');

  $$('.cdx-btn').forEach(btn => btn.addEventListener('click', () => {
    const row = btn.closest('.cdx'), body = $('.cdx-body', row), open = row.classList.contains('open');
    $$('.cdx.open').forEach(o => { if (o !== row) { o.classList.remove('open'); $('.cdx-body', o).style.maxHeight = ''; $('.cdx-btn', o).setAttribute('aria-expanded', 'false'); } });
    row.classList.toggle('open', !open);
    btn.setAttribute('aria-expanded', String(!open));
    body.style.maxHeight = open ? '' : body.scrollHeight + 'px';
  }));

  // products
  const cats = [...new Set(PRODUCTS.map(p => p[2]))];
  const fl = $('#filters');
  if (fl) fl.innerHTML = `<button class="fchip on" data-f="*" data-th="ทั้งหมด" data-en="All">ทั้งหมด</button>`
    + cats.map(c => `<button class="fchip" data-f="${esc(c)}">${esc(c)}</button>`).join('');

  const pg = $('#prod-grid');
  const draw = (filter) => {
    if (!pg) return;
    pg.innerHTML = PRODUCTS.filter(p => filter === '*' || p[2] === filter).map(([n, sub, cat, price, soon, dTh, dEn, col, hero]) => `
      <article class="prod reveal${hero ? ' prod-hero' : ''}" style="--tint:${col}22;--tint2:${col}">
        <div class="prod-top">
          <div class="prod-ico">${esc(n.slice(0, 2).toUpperCase())}</div>
          ${soon ? `<span class="prod-soon" data-th="เร็ว ๆ นี้" data-en="Coming soon">เร็ว ๆ นี้</span>` : ''}
          ${hero ? `<span class="prod-soon" style="border-color:rgba(108,240,255,.5);color:#6cf0ff" data-th="เรือธง" data-en="Flagship">เรือธง</span>` : ''}
        </div>
        <h3>${esc(n)} <span style="color:var(--ink-3);font-weight:400;font-size:12px">${esc(sub)}</span></h3>
        <p data-th="${esc(dTh)}" data-en="${esc(dEn)}">${esc(dTh)}</p>
        <div class="prod-foot">
          <span class="prod-cat">${esc(cat)}</span>
          <span class="prod-price${!price ? ' free' : ''}" data-th="${price ? '฿' + price.toLocaleString() : (price === 0 ? 'ฟรี' : 'สอบถาม')}" data-en="${price ? '฿' + price.toLocaleString() : (price === 0 ? 'Free' : 'Enquire')}">${price ? '฿' + price.toLocaleString() : 'ฟรี'}</span>
        </div>
      </article>`).join('');
    syncLang(); window.__revealWatch?.();
  };
  draw('*');
  $$('.fchip').forEach(b => b.addEventListener('click', () => {
    $$('.fchip').forEach(x => x.classList.remove('on')); b.classList.add('on'); draw(b.dataset.f);
  }));

  function syncLang() {
    const l = th() ? 'data-th' : 'data-en';
    $$('[data-th]').forEach(el => { const v = el.getAttribute(l); if (v != null) el.textContent = v; });
    $$('[data-html-th]').forEach(el => {
      const v = el.getAttribute(th() ? 'data-html-th' : 'data-html-en');
      if (v != null) el.innerHTML = v;
      const row = el.closest('.cdx');
      if (row?.classList.contains('open')) $('.cdx-body', row).style.maxHeight = $('.cdx-body', row).scrollHeight + 'px';
    });
  }
  $('#lang-toggle')?.addEventListener('click', () => setTimeout(syncLang, 0));
  syncLang();
}
function esc(s) { return String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

/* ══ boot ══
   The `js` class is set FIRST and unconditionally: it is what arms the
   reveal-on-scroll styling, so it must never be set unless the code that
   undoes it is about to run. */
function boot() {
  document.documentElement.classList.add('js');
  language(); render(); motion(); galaxy();
  // Re-measure once everything (fonts, stylesheets, images) has settled.
  addEventListener('load', () => dispatchEvent(new Event('resize')), { once: true });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
