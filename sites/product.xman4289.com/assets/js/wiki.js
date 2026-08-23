/* BrainX wiki — renders window.WIKI into a TOC + entries, and the comparison
   table. Every entry is What / Why ours wins / What you get, because a feature
   name on its own has never convinced anyone of anything. */
(() => {
'use strict';
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const W = window.WIKI || [];
const esc = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
let TH = true;

/* Rows are [featureTH, featureEN, brainx, obsidian, notion, chatgpt]
   Values: 1 = yes, 0 = no, or a short string. Deliberately honest — the
   places others win are left as wins, because a table where one column is
   all ticks is a table nobody believes. */
const CMP = [
 ['กราฟความรู้ 3 มิติที่บินเข้าไปได้','3D knowledge graph you can fly through', 1, '2D', 0, 0],
 ['ค้นด้วยความหมาย (embeddings) ในเครื่อง','Local semantic search (embeddings)', 1, 'ปลั๊กอิน/plugin', 1, 0],
 ['ค้นแบบ hybrid รวมความหมาย+คำสำคัญ','Hybrid semantic + keyword fusion', 1, 0, 0, 0],
 ['ภาษาไทยไม่ต้องเว้นวรรค','Thai retrieval without spaces', 1, 0, 0, 1],
 ['อ่าน .canvas เข้ากราฟ','Parses .canvas into the graph', 1, 'แสดงได้/views only', 0, 0],
 ['ดึงข้อความจาก PDF','PDF text extraction', 1, 'ปลั๊กอิน/plugin', 1, 1],
 ['ทำดัชนีไฟล์โค้ด + แยกชื่อฟังก์ชัน','Indexes source code + symbols', 1, 0, 0, 0],
 ['หาโน้ตที่ขัดแย้งกันเอง','Finds notes that contradict each other', 1, 0, 0, 0],
 ['แนะนำลิงก์ที่ควรมี','Suggests links you are missing', 1, 'ปลั๊กอิน/plugin', 0, 0],
 ['เป็น MCP server ให้ AI ต่อเข้ามาทำงาน','Is an MCP server agents can work through', 1, 0, 0, 0],
 ['เป็น MCP hub คุมโปรแกรมอื่น (Unity)','Is an MCP hub that drives other apps', 1, 0, 0, 0],
 ['ให้ AI ต่างค่ายคุยกันผ่านสมอง','Cross-vendor agent messaging', 1, 0, 0, 0],
 ['บันทึกทุกการเรียกพร้อมชื่อผู้เรียก','Journals every call with the real actor', 1, 0, 0, 0],
 ['ดูแลตัวเองตอนเครื่องว่าง','Maintains itself while idle', 1, 0, 0, 0],
 ['ใช้เป็นวอลเปเปอร์เดสก์ท็อปหลายจอ','Runs as multi-monitor live wallpaper', 1, 0, 0, 0],
 ['ข้อมูลอยู่ในเครื่องคุณ 100%','Your data never leaves your machine', 1, 1, 0, 0],
 ['ไฟล์เป็น Markdown ที่ย้ายออกได้ทุกเมื่อ','Plain Markdown you can walk away with', 1, 1, 0, 0],
 ['ใช้งานบนมือถือ','Mobile app', 0, 1, 1, 1],
 ['แก้ไขร่วมกันหลายคนแบบเรียลไทม์','Real-time multiplayer editing', 0, 0, 1, 0],
 ['ระบบปลั๊กอินจากชุมชน','Community plugin ecosystem', 0, 1, 0, 0],
];

function render() {
  // TOC
  $('#toc').innerHTML = '<div class="toc-in">' + W.map(g =>
    `<a class="toc-g" href="#${g.id}" data-th="${esc(g.icoTH)}" data-en="${esc(g.icoEN)}">${esc(g.icoTH)}</a>` +
    g.items.map(it => `<a class="toc-i" href="#${g.id}-${it[0]}" data-th="${esc(it[1])}" data-en="${esc(it[2])}">${esc(it[1])}</a>`).join('')
  ).join('') + `<a class="toc-g" href="#compare" data-th="เทียบกับเจ้าอื่น" data-en="Comparison">เทียบกับเจ้าอื่น</a></div>`;

  // entries
  $('#wiki-main').innerHTML = W.map(g => `
    <section class="wg" id="${g.id}">
      <h2 class="wg-h reveal" data-th="${esc(g.icoTH)}" data-en="${esc(g.icoEN)}">${esc(g.icoTH)}</h2>
      ${g.items.map(([id, tTh, tEn, wTh, wEn, eTh, eEn, gTh, gEn]) => `
      <article class="we reveal" id="${g.id}-${id}">
        <h3 data-th="${esc(tTh)}" data-en="${esc(tEn)}">${esc(tTh)}</h3>
        <div class="we-row">
          <span class="we-tag" data-th="คืออะไร" data-en="What it is">คืออะไร</span>
          <p data-th="${esc(wTh)}" data-en="${esc(wEn)}">${esc(wTh)}</p>
        </div>
        <div class="we-row we-edge">
          <span class="we-tag" data-th="ทำไมของเราดีกว่า" data-en="Why ours wins">ทำไมของเราดีกว่า</span>
          <p data-th="${esc(eTh)}" data-en="${esc(eEn)}">${esc(eTh)}</p>
        </div>
        <div class="we-row we-gain">
          <span class="we-tag" data-th="คุณได้อะไร" data-en="What you get">คุณได้อะไร</span>
          <p data-th="${esc(gTh)}" data-en="${esc(gEn)}">${esc(gTh)}</p>
        </div>
      </article>`).join('')}
    </section>`).join('');

  drawTable();
  sync();
}

function cell(v) {
  if (v === 1) return '<td class="y">✓</td>';
  if (v === 0) return '<td class="n">—</td>';
  const parts = String(v).split('/');
  return `<td class="p">${esc(TH ? parts[0] : (parts[1] || parts[0]))}</td>`;
}
function drawTable() {
  const h = TH
    ? ['ความสามารถ','BrainX','Obsidian','Notion','ChatGPT เปล่า ๆ']
    : ['Capability','BrainX','Obsidian','Notion','Plain ChatGPT'];
  $('#cmp').innerHTML =
    `<thead><tr>${h.map((x,i)=>`<th${i===1?' class="me"':''}>${esc(x)}</th>`).join('')}</tr></thead>` +
    `<tbody>${CMP.map(r => `<tr><td class="f">${esc(TH ? r[0] : r[1])}</td>` +
      [r[2],r[3],r[4],r[5]].map((v,i)=>cell(v).replace('<td','<td'+(i===0?' data-me="1"':''))).join('') +
      `</tr>`).join('')}</tbody>`;
}

function sync() {
  const k = TH ? 'data-th' : 'data-en';
  $$('[data-th]').forEach(el => { const v = el.getAttribute(k); if (v != null) el.textContent = v; });
  drawTable();
  window.__revealWatch?.();
}

function language() {
  const root = document.documentElement, KEY = 'brainx.lang';
  const apply = l => {
    root.setAttribute('data-lang', l); root.setAttribute('lang', l === 'th' ? 'th' : 'en');
    TH = l === 'th'; sync();
    try { localStorage.setItem(KEY, l); } catch {}
  };
  let s = null; try { s = localStorage.getItem(KEY); } catch {}
  if (s !== 'th' && s !== 'en') s = (navigator.language||'').toLowerCase().startsWith('th') ? 'th' : 'en';
  apply(s);
  $('#lang-toggle')?.addEventListener('click', () =>
    apply(root.getAttribute('data-lang') === 'th' ? 'en' : 'th'));
}

function motion() {
  const showAll = () => $$('.reveal').forEach(e => e.classList.add('in'));
  if (!('IntersectionObserver' in window)) { showAll(); window.__revealWatch = showAll; return; }
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
  }), { threshold: .08, rootMargin: '0px 0px -6% 0px' });
  const watch = () => $$('.reveal:not(.in)').forEach(e => io.observe(e));
  watch(); window.__revealWatch = watch;
  setTimeout(() => { if (!$('.reveal.in')) showAll(); }, 1200);

  // TOC highlight + progress rail
  const rail = $('#scroll-rail-fill'), nav = $('#nav');
  const spy = new IntersectionObserver(es => es.forEach(e => {
    if (!e.isIntersecting) return;
    $$('.toc a').forEach(a => a.classList.remove('on'));
    $(`.toc a[href="#${e.target.id}"]`)?.classList.add('on');
  }), { rootMargin: '-20% 0px -70% 0px' });
  $$('.we, .wg').forEach(el => spy.observe(el));
  addEventListener('scroll', () => {
    const h = document.body.scrollHeight - innerHeight;
    if (rail) rail.style.width = (h > 0 ? scrollY / h * 100 : 0) + '%';
    nav?.classList.toggle('stuck', scrollY > 40);
  }, { passive: true });
}

function boot() {
  document.documentElement.classList.add('js');
  render(); language(); motion();
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
