/* XMAN Studio product hub — the page: spotlight, collection, release log,
 * studio, detail dialog, search, the tour, and what Nova says about each.
 * Reads window.XPH.catalog; makes Nova speak through window.XPH.guide; tells
 * the 3D layer (universe.js) what to show with window events. */
(function () {
  'use strict';

  var XPH = window.XPH;
  var C = XPH.catalog;
  var guide = XPH.guide;
  var prefs = XPH.prefs;
  var pick = XPH.pick;
  var root = document.documentElement;
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var pad2 = function (n) { return (n < 10 ? '0' : '') + n; };
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var TH_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
  var thaiDate = function (iso) {
    var p = iso.split('-');
    return parseInt(p[2], 10) + ' ' + TH_MONTHS[parseInt(p[1], 10) - 1] + ' ' + (parseInt(p[0], 10) + 543);
  };
  var smooth = function () { return prefs.motion() ? 'smooth' : 'auto'; };

  root.classList.add('js');

  var state = {
    feat: 0,
    filter: 'all',
    cat: null,
    query: '',
    detail: null,
    touched: 0,
    tourStep: -1,
    said: {},
    spotVisible: true,
  };
  XPH.state = { featured: C.FEATURED[0].id };

  var counts = C.counts;
  var FEATURED = C.FEATURED;

  /* ------------------------------------------------------------ counts & nav */

  $$('[data-count]').forEach(function (el) {
    var n = counts[el.getAttribute('data-count')];
    if (n != null) el.textContent = pad2(n);
  });
  $('#side-cats').innerHTML = C.CATS.map(function (c) {
    return '<a href="#products" class="side-link" data-cat="' + c.id + '" data-nav="cat:' + c.id + '" title="' + esc(c.th) + '">' +
      '<span class="nav-icon ic-cat">▹</span>' + esc(c.dir) + '<span class="nav-count">' + pad2(c.count) + '</span></a>';
  }).join('');

  // the phone menu is the sidebar's explorer + tour button
  var mobileMenu = $('#mobile-menu');
  [$('.explorer'), $('.side-tour')].forEach(function (node) {
    var copy = node.cloneNode(true);
    copy.removeAttribute('id');
    $$('[id]', copy).forEach(function (el) { el.removeAttribute('id'); });
    mobileMenu.appendChild(copy);
  });
  var menuButton = $('.menu-button');
  function setMenu(open) {
    mobileMenu.dataset.open = open ? '1' : '0';
    document.body.classList.toggle('menu-open', open);
    menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  menuButton.addEventListener('click', function () { setMenu(mobileMenu.dataset.open !== '1'); });

  function scrollToId(id, block) {
    var el = id === 'top' ? null : document.getElementById(id);
    if (!el) window.scrollTo({ top: 0, behavior: smooth() });
    else el.scrollIntoView({ behavior: smooth(), block: block || 'start' });
  }

  function navActive() {
    $$('.side-link').forEach(function (a) {
      var n = a.getAttribute('data-nav');
      var on =
        (n === 'discover' && section === 'discover') ||
        (section === 'products' && ((n === 'cat:' + state.cat) || (!state.cat && n === state.filter))) ||
        (n === 'releases' && section === 'releases') ||
        (n === 'studio' && section === 'studio');
      a.classList.toggle('active', !!on);
    });
  }

  // one listener for everything clickable that carries data-go / data-filter / data-cat / data-action
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-go],[data-filter],[data-cat],[data-action]');
    if (!a || a.closest('.nova')) return;
    if (a.hasAttribute('data-go')) {
      e.preventDefault();
      setMenu(false);
      var go = a.getAttribute('data-go');
      scrollToId(go, go === 'studio' ? 'center' : 'start');
    } else if (a.hasAttribute('data-filter')) {
      e.preventDefault();
      applyFilter(a.getAttribute('data-filter'), null, { scroll: !a.closest('#filters') });
    } else if (a.hasAttribute('data-cat')) {
      e.preventDefault();
      var c = a.getAttribute('data-cat');
      applyFilter('all', state.cat === c && a.closest('#cat-filters') ? null : c, { scroll: !a.closest('#cat-filters') });
    } else if (a.hasAttribute('data-action')) {
      e.preventDefault();
      setMenu(false);
      var act = a.getAttribute('data-action');
      if (act === 'reset') { searchInput.value = ''; state.query = ''; applyFilter('all', null, { speak: false }); }
      else guide.run(act);
    }
  });

  /* ------------------------------------------------------------ spotlight */

  var spot = $('#spotlight');
  var spotCopy = $('#spot-copy');
  var selector = $('#spot-selector');
  var shotTag = document.createElement('span');
  shotTag.className = 'spot-shot';
  shotTag.hidden = true;
  $('.spot-bottom').insertBefore(shotTag, $('#spot-status'));

  selector.style.setProperty('--n', FEATURED.length);
  selector.innerHTML = FEATURED.map(function (f, i) {
    var p = C.byId(f.id);
    return '<button type="button" class="spot-choice" data-i="' + i + '" aria-pressed="false" style="--accent:' + p.palette[2] + '">' +
      '<span class="choice-number">' + pad2(i + 1) + '</span>' +
      '<img src="' + p.art + '" alt="" loading="lazy" decoding="async">' +
      '<span class="choice-text"><b>' + esc(p.name) + '</b><small>' + esc(f.hook) + '</small></span><i></i></button>';
  }).join('');
  selector.addEventListener('click', function (e) {
    var b = e.target.closest('.spot-choice');
    if (b) chooseFeature(+b.getAttribute('data-i'), true);
  });
  $('#spot-prev').addEventListener('click', function () { chooseFeature(state.feat - 1, true); });
  $('#spot-next').addEventListener('click', function () { chooseFeature(state.feat + 1, true); });
  $('#spot-total').textContent = '/ ' + pad2(FEATURED.length);

  var newTab = ' target="_blank" rel="noopener"';

  /** A file streams in place; a download page opens in a tab. Every download comes from xman4289.com. */
  function downloadLink(p) {
    return '<a class="button primary" href="' + esc(p.download) + '"' + (p.dlPage ? newTab : ' rel="noopener"') + ' data-dl="' + p.id + '"><span aria-hidden="true">⤓</span> ' + esc(p.downloadLabel) + '</a>';
  }

  /** Download first when there is one, then the product page. */
  function actionsHtml(p) {
    var page = p.external ? newTab : '';
    if (p.download) {
      return downloadLink(p) + '<a class="button secondary" href="' + esc(p.href) + '"' + page + '>' + esc(p.hrefLabel) + ' <span aria-hidden="true">↗</span></a>';
    }
    return '<a class="button ' + (p.status === 'soon' ? 'gold' : 'primary') + '" href="' + esc(p.href) + '"' + page + '><span aria-hidden="true">' + (p.status === 'soon' ? '✦' : '⌘') + '</span> ' + esc(p.hrefLabel) + '</a>';
  }

  function renderSpot(byUser) {
    var f = FEATURED[state.feat];
    var p = C.byId(f.id);
    var c = C.cat(p.cat);
    spot.style.setProperty('--accent', p.palette[2]);
    spot.style.setProperty('--accent2', p.palette[0]);
    $('#spot-num').textContent = pad2(state.feat + 1);
    $('#spot-category').textContent = c.dir + ' · ' + c.label.toUpperCase();
    var title = $('#feature-title');
    title.textContent = p.name;
    fitTitle(title);
    $('#spot-subtitle').textContent = p.sub;
    $('#spot-pitch').innerHTML = esc(p.pitch[0]) + '<br>' + esc(p.pitch[1]);
    $('#spot-tags').innerHTML = p.platforms.slice(0, 2).concat(p.tags.slice(0, 3)).map(function (t) { return '<span>' + esc(t) + '</span>'; }).join('');
    var acts = $('#spot-actions');
    // one call to action + details; the product page rides along with the price
    acts.innerHTML = (p.download ? downloadLink(p) : actionsHtml(p)) + '<button type="button" class="button secondary" data-open="' + p.id + '">รายละเอียด <span aria-hidden="true">⊕</span></button>';
    var priceEl = $('.spot-price', spotCopy);
    if (!priceEl) {
      priceEl = document.createElement('span');
      priceEl.className = 'spot-price';
      spotCopy.insertBefore(priceEl, acts);
    }
    priceEl.innerHTML = esc(p.price) + (p.download
      ? ' <span class="sep">·</span> <a href="' + esc(p.href) + '"' + (p.external ? newTab : '') + '>' + esc(p.hrefLabel) + ' ↗</a>'
      : '');
    $('#spot-sector').textContent = f.sector;
    $('#spot-node').textContent = f.node;
    var st = $('#spot-status');
    st.textContent = p.status === 'live' ? (p.version ? 'LIVE · v' + p.version : 'LIVE · SHIPPING') : 'IN DEVELOPMENT';
    st.classList.toggle('soon', p.status !== 'live');
    $('#spotlight-stage').setAttribute('aria-label', 'หน้าจอของ ' + p.name + ' ลากเพื่อหมุน');
    $('#fb-title').textContent = p.name;
    $('#fb-img').src = (p.shots.length ? p.shots[0] : p.art);
    setShotTag(p, 0);
    // replay the copy's entrance
    spotCopy.classList.remove('swap');
    void spotCopy.offsetWidth;
    if (byUser !== null) spotCopy.classList.add('swap');
    $$('.spot-choice', selector).forEach(function (b, i) {
      var on = i === state.feat;
      b.classList.toggle('selected', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
      var bar = b.querySelector('i');
      bar.classList.remove('run');
      if (on && prefs.motion()) { void bar.offsetWidth; bar.classList.add('run'); }
    });
    XPH.state.featured = p.id;
    window.dispatchEvent(new CustomEvent('xph:feature', { detail: p.id }));
  }

  /** Shrink the spotlight name until its longest word fits the column (names never break mid-word). */
  function fitTitle(title) {
    var fit = 1;
    title.style.setProperty('--fit', '1');
    var words = title.textContent.split(' ');
    title.innerHTML = words.map(function (w) { return '<span style="display:inline-block;white-space:nowrap">' + esc(w) + '</span>'; }).join(' ');
    var max = title.clientWidth;
    var widest = function () {
      return Math.max.apply(null, $$('span', title).map(function (sp) { return sp.getBoundingClientRect().width; }));
    };
    while (max > 0 && widest() > max && fit > 0.45) {
      fit -= 0.05;
      title.style.setProperty('--fit', fit.toFixed(2));
    }
  }
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { fitTitle($('#feature-title')); });
  window.addEventListener('resize', function () { fitTitle($('#feature-title')); });

  function setShotTag(p, i) {
    if (!p.shots.length) { shotTag.hidden = true; return; }
    shotTag.hidden = false;
    shotTag.textContent = 'LIVE CAPTURE ' + pad2(i + 1) + ' / ' + pad2(p.shots.length);
  }
  window.addEventListener('xph:slide', function (e) {
    var p = C.byId(e.detail.id);
    if (p && p.id === XPH.state.featured) setShotTag(p, e.detail.i);
  });

  function chooseFeature(i, byUser) {
    state.feat = (i + FEATURED.length) % FEATURED.length;
    renderSpot(byUser);
    if (byUser) {
      state.touched = performance.now();
      var p = C.byId(FEATURED[state.feat].id);
      guide.say({ text: p.nova[0], pose: 'present', priority: 3 });
    }
  }

  spotCopy.addEventListener('click', function (e) {
    var b = e.target.closest('[data-open]');
    if (b) openProduct(C.byId(b.getAttribute('data-open')));
    var d = e.target.closest('[data-dl]');
    if (d) downloaded(C.byId(d.getAttribute('data-dl')));
  });

  // drag the stage to turn the screen (and nudge the planet behind it)
  var stage = $('#spotlight-stage');
  var drag = null;
  stage.addEventListener('pointerdown', function (e) {
    drag = { x: e.clientX, id: e.pointerId };
    stage.setPointerCapture(e.pointerId);
    state.touched = performance.now();
  });
  stage.addEventListener('pointermove', function (e) {
    if (!drag || drag.id !== e.pointerId) return;
    var dx = e.clientX - drag.x;
    drag.x = e.clientX;
    window.dispatchEvent(new CustomEvent('xph:spin', { detail: dx }));
  });
  var endDrag = function () { drag = null; };
  stage.addEventListener('pointerup', endDrag);
  stage.addEventListener('pointercancel', endDrag);
  stage.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
      e.preventDefault();
      state.touched = performance.now();
      window.dispatchEvent(new CustomEvent('xph:spin', { detail: e.key === 'ArrowLeft' ? -40 : 40 }));
    }
  });

  // auto-advance while nobody is using the spotlight
  setInterval(function () {
    if (!prefs.motion()) return;
    var idle = performance.now() - state.touched > 20000;
    if (idle && state.spotVisible && !state.detail && !document.hidden && state.tourStep < 0) chooseFeature(state.feat + 1, false);
  }, 10000);

  /* ------------------------------------------------------------ filters & cards */

  var FILTERS = [
    { id: 'all', label: 'ทั้งหมด' },
    { id: 'live', label: 'ใช้งานได้แล้ว' },
    { id: 'free', label: 'ใช้ฟรีได้' },
    { id: 'soon', label: 'กำลังพัฒนา' },
  ];
  var FILTER_LINES = {
    live: { text: 'โปรแกรมที่ใช้งานได้แล้วมี ' + counts.live + ' ตัว ดาวน์โหลดหรือซื้อได้ทันทีเลยค่ะ!', pose: 'cheer' },
    free: { text: 'สายฟรีต้องชอบ! ' + counts.free + ' ตัวนี้ใช้ฟรี หรือมีรุ่นฟรีให้ลองก่อนนะ ✦', pose: 'cheer' },
    soon: { text: 'นี่คือของที่ทีมกำลังสร้างอยู่ แอบดูก่อนใครได้เลย~ เปิดขายเมื่อไหร่โนวาจะบอกนะ', pose: 'present' },
  };

  var filtersEl = $('#filters');
  var catFiltersEl = $('#cat-filters');
  filtersEl.innerHTML = FILTERS.map(function (f) {
    return '<button type="button" data-filter="' + f.id + '" aria-pressed="false">' + esc(f.label) + ' <span>' + counts[f.id] + '</span></button>';
  }).join('');
  catFiltersEl.innerHTML = C.CATS.map(function (c) {
    return '<button type="button" data-cat="' + c.id + '" aria-pressed="false" title="' + esc(c.th) + '">' + esc(c.dir) + '<span>' + c.count + '</span></button>';
  }).join('');

  function matches(p) {
    if (state.filter === 'live' && p.status !== 'live') return false;
    if (state.filter === 'free' && !p.free) return false;
    if (state.filter === 'soon' && p.status !== 'soon') return false;
    if (state.cat && p.cat !== state.cat) return false;
    var q = state.query.trim().toLowerCase();
    if (!q) return true;
    var c = C.cat(p.cat);
    var hay = [p.name, p.sub, p.tagline, p.desc, p.price, c.label, c.th, c.dir].concat(p.tags, p.platforms, p.features).join(' ').toLowerCase();
    return q.split(/\s+/).every(function (w) { return hay.indexOf(w) >= 0; });
  }

  var cardsEl = $('#cards');
  function cardHtml(p) {
    var c = C.cat(p.cat);
    var badges = '<span class="badge ' + p.status + '">' + (p.status === 'live' ? '● LIVE' : '◌ IN DEV') + '</span>' +
      (p.free ? '<span class="badge free">FREE</span>' : '');
    return '<div class="rise card-wrap"><article class="card" data-id="' + p.id + '" style="--accent:' + p.palette[2] + ';--accent2:' + p.palette[0] + '">' +
      '<button type="button" class="card-hit" aria-label="ดูรายละเอียด ' + esc(p.name) + '"></button>' +
      '<div class="card-3d">' +
        '<div class="card-art"><img src="' + p.art + '" alt="" loading="lazy" decoding="async" draggable="false">' +
          (p.shots.length ? '<img class="card-shot" src="' + p.shots[0] + '" alt="" loading="lazy" decoding="async" draggable="false">' : '') +
          '<div class="card-art-fade"></div></div>' +
        '<div class="card-badges">' + badges + '</div>' +
        (p.shots.length ? '<span class="badge capture">◉ LIVE CAPTURE · ' + p.shots.length + '</span>' : '') +
        '<span class="card-num">' + esc(c.dir) + pad2(p.index + 1) + '</span>' +
        '<div class="card-body">' +
          '<h3>' + esc(p.name) + '</h3>' +
          '<div class="card-sub">' + esc(p.sub) + '</div>' +
          '<p>' + esc(p.tagline) + '</p>' +
          '<div class="card-plat">' + p.platforms.map(function (x) { return '<span>' + esc(x) + '</span>'; }).join('') + '</div>' +
          '<div class="card-foot">' +
            '<span class="card-price">' + (p.version ? '<em class="ver">v' + esc(p.version) + '</em>' : '') + '<em>' + esc(p.price) + '</em></span>' +
            '<span class="card-cta">รายละเอียด <span aria-hidden="true">⊕</span></span>' +
          '</div>' +
        '</div>' +
        '<div class="card-glare"></div>' +
      '</div></article></div>';
  }

  function renderCards() {
    var list = C.PRODUCTS.filter(matches);
    cardsEl.innerHTML = list.map(cardHtml).join('');
    $$('.rise', cardsEl).forEach(function (el) { el.classList.add('in'); });
    $('#list-count').textContent = list.length;
    $('#result-label').textContent = list.length + (list.length === 1 ? ' build' : ' builds') + ' to explore';
    $('#empty').hidden = list.length > 0;
    $$('button', filtersEl).forEach(function (b) {
      var on = b.getAttribute('data-filter') === state.filter && !state.cat;
      b.classList.toggle('on', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    $$('button', catFiltersEl).forEach(function (b) {
      var on = b.getAttribute('data-cat') === state.cat;
      b.classList.toggle('on', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    navActive();
    return list;
  }

  function applyFilter(f, cat, opts) {
    opts = opts || {};
    state.filter = f;
    state.cat = cat || null;
    renderCards();
    setMenu(false);
    if (opts.scroll) scrollToId('products');
    if (opts.speak === false) return;
    if (state.cat) {
      var c = C.cat(state.cat);
      guide.say({ text: 'หมวด ' + c.th + ' มี ' + c.count + ' ตัวค่ะ ' + C.PRODUCTS.filter(function (p) { return p.cat === c.id; }).map(function (p) { return p.name; }).join(', ') + ' ชี้ที่การ์ดแล้วโนวาจะเล่าให้ฟังนะ', pose: 'present', priority: 3 });
    } else if (FILTER_LINES[f]) {
      guide.say(Object.assign({}, FILTER_LINES[f], { priority: 3 }));
    }
  }

  // 3D tilt + hover lines
  var hoverTimer = 0;
  var lastHover = '';
  cardsEl.addEventListener('pointermove', function (e) {
    var card = e.target.closest('.card');
    if (!card || e.pointerType !== 'mouse' || root.dataset.motion === 'off') return;
    var r = card.getBoundingClientRect();
    var x = (e.clientX - r.left) / r.width;
    var y = (e.clientY - r.top) / r.height;
    card.style.setProperty('--rx', (0.5 - y) * 10 + 'deg');
    card.style.setProperty('--ry', (x - 0.5) * 14 + 'deg');
    card.style.setProperty('--gx', x * 100 + '%');
    card.style.setProperty('--gy', y * 100 + '%');
  });
  cardsEl.addEventListener('pointerover', function (e) {
    var card = e.target.closest('.card');
    if (!card || card.contains(e.relatedTarget)) return;
    hoverLine(C.byId(card.getAttribute('data-id')));
  });
  cardsEl.addEventListener('pointerout', function (e) {
    var card = e.target.closest('.card');
    if (!card || card.contains(e.relatedTarget)) return;
    card.style.setProperty('--rx', '0deg');
    card.style.setProperty('--ry', '0deg');
    clearTimeout(hoverTimer);
  });
  cardsEl.addEventListener('focusin', function (e) {
    var card = e.target.closest('.card');
    if (card) hoverLine(C.byId(card.getAttribute('data-id')));
  });
  cardsEl.addEventListener('click', function (e) {
    var card = e.target.closest('.card');
    if (card) openProduct(C.byId(card.getAttribute('data-id')));
  });
  function hoverLine(p) {
    clearTimeout(hoverTimer);
    if (!p || p.id === lastHover) return;
    hoverTimer = setTimeout(function () {
      // once per card in a row, so sweeping the pointer back and forth is not a chatterbox
      if (guide.say({ text: pick(p.nova), pose: 'present', priority: 1, hold: 1200 })) lastHover = p.id;
    }, 650);
  }

  /* ------------------------------------------------------------ search */

  var searchInput = $('#search');
  var searchTimer = 0;
  searchInput.addEventListener('input', function () {
    state.query = searchInput.value;
    if (state.query && (state.filter !== 'all' || state.cat)) { state.filter = 'all'; state.cat = null; }
    var list = renderCards();
    if (state.query.length === 1) scrollToId('products');
    if (XPH.nova) XPH.nova.think(!!state.query);
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
      if (XPH.nova) XPH.nova.think(false);
      if (!state.query) return;
      if (list.length === 0) {
        guide.say({ text: 'อุ๊ย หาไม่เจอเลยค่ะ ลองคำอื่นดูนะ เช่น “AI” “Windows” “VPN” หรือ “ฟรี”', pose: 'welcome', react: 'surprise', priority: 2 });
      } else if (list.length <= 3) {
        guide.say({ text: 'เจอแล้ว! ' + list.map(function (p) { return p.name; }).join(', ') + ' — กดการ์ดเพื่อดูรายละเอียดได้เลยค่ะ', pose: 'present', priority: 2 });
      }
    }, 900);
  });
  // a little command palette: Ctrl/⌘ + K, or "/" when not typing
  document.addEventListener('keydown', function (e) {
    var typing = /^(INPUT|TEXTAREA|SELECT)$/.test((e.target.tagName || '')) || e.target.isContentEditable;
    if (((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') || (!typing && e.key === '/')) {
      e.preventDefault();
      searchInput.focus();
      searchInput.select();
    }
  });

  /* ------------------------------------------------------------ motion switch */

  var motionBtn = $('#motion-toggle');
  function syncMotion() {
    var on = prefs.motion();
    motionBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
    motionBtn.title = on ? 'พักเอฟเฟกต์เคลื่อนไหว' : 'เปิดเอฟเฟกต์เคลื่อนไหว';
    $('.motion-glyph', motionBtn).textContent = on ? 'Ⅱ' : '▷';
  }
  motionBtn.addEventListener('click', function () { prefs.setMotion(!prefs.motion()); syncMotion(); });
  window.addEventListener('xph:motion', syncMotion);
  syncMotion();

  // the 3D layer says this machine stutters even at the lowest resolution: go still
  window.addEventListener('xph:lowperf', function () {
    if (!prefs.motion()) return;
    prefs.setMotion(false, true);
    guide.say({
      text: 'เครื่องนี้เริ่มกระตุกนิดหน่อย โนวาเลยพักเอฟเฟกต์เป็นภาพนิ่งให้นะคะ ดูทุกอย่างได้ครบเหมือนเดิม อยากเปิดกลับกดปุ่ม Motion ได้เลย',
      pose: 'welcome', priority: 3, hold: 6000,
      chips: [{ label: 'เปิดเอฟเฟกต์กลับ', action: 'motionOn' }],
    });
  });

  /* ------------------------------------------------------------ release log */

  var timelineEl = $('#timeline');
  timelineEl.innerHTML = C.RELEASES.map(function (r) {
    var p = C.byId(r.id);
    return '<li class="rise" style="--accent:' + p.palette[2] + '"><button type="button" class="tl-card" data-open="' + p.id + '">' +
      '<span class="tl-top"><time datetime="' + r.date + '">' + thaiDate(r.date) + '</time>' + (r.v ? '<span class="ver">v' + esc(r.v) + '</span>' : '') + '</span>' +
      '<span class="tl-game"><i></i> ' + esc(p.name) + '</span>' +
      '<b>' + esc(r.title) + '</b>' +
      '<ul>' + r.items.slice(0, 2).map(function (it) { return '<li>' + esc(it) + '</li>'; }).join('') + '</ul>' +
      '<span class="tl-more">ดูโปรแกรมนี้ <span aria-hidden="true">→</span></span></button></li>';
  }).join('');
  timelineEl.addEventListener('click', function (e) {
    var b = e.target.closest('[data-open]');
    if (b) openProduct(C.byId(b.getAttribute('data-open')), true, true);
  });

  /* ------------------------------------------------------------ detail dialog */

  var dialog = $('#detail');
  var inner = $('#detail-inner');
  var lastFocus = null;

  function verKey(api) { return 'xph.ver.' + api; }

  /** Bullet lines of a changelog, readable and with no repo traces. */
  function changelogItems(md) {
    return String(md || '').split(/\r?\n/)
      .filter(function (l) { return /^\s*[-*]\s+/.test(l); })
      .map(function (l) {
        return l.replace(/^\s*[-*]\s+/, '').replace(/\s*\([0-9a-f]{7,}\)\s*$/i, '').replace(/[*`_]/g, '').replace(/^\w[\w .-]*:\s/, function (m) { return m.charAt(0).toUpperCase() + m.slice(1); }).trim();
      })
      .filter(function (l) { return l && !/github|https?:|release:/i.test(l); })
      .slice(0, 5);
  }

  /** The live version for the open dialog: once per product per 10 minutes, so browsing never
      eats into the per-IP limit the desktop apps' update checks share. */
  function fetchVersion(p) {
    if (!p.api) return;
    var cached = null;
    try { cached = JSON.parse(sessionStorage.getItem(verKey(p.api)) || 'null'); } catch (e) {}
    var paint = function (v) {
      var box = $('#detail-ver');
      if (!v || state.detail !== p || !box) return;
      var items = changelogItems(v.changelog);
      var date = v.version === p.version && p.released ? ' · ' + thaiDate(p.released) : '';
      box.innerHTML = '<span class="term">$ xman version ' + esc(p.id) + ' → v' + esc(v.version) + '</span>' +
        'เวอร์ชันล่าสุด <b>v' + esc(v.version) + '</b>' + (v.file_size_formatted ? ' · ' + esc(v.file_size_formatted) : '') + esc(date) +
        (items.length ? '<ul>' + items.map(function (i) { return '<li>' + esc(i) + '</li>'; }).join('') + '</ul>' : '') +
        '<small>ข้อมูลสดจาก xman4289.com · ไฟล์ดาวน์โหลดส่งจาก xman4289.com เท่านั้น</small>';
    };
    var fallback = function () {
      var note = $('#detail-ver small');
      if (state.detail === p && note) note.textContent = 'ข้อมูล ณ วันที่ปล่อย · ดูเวอร์ชันล่าสุดได้ที่หน้าสินค้า';
    };
    if (cached && cached.v && Date.now() - cached.at < 10 * 60 * 1000) { paint(cached.v); return; }
    var ctl = window.AbortController ? new AbortController() : null;
    var timer = setTimeout(function () { if (ctl) ctl.abort(); fallback(); }, 7000);
    fetch(C.SHOP + '/api/v1/products/' + encodeURIComponent(p.api) + '/version', { headers: { Accept: 'application/json' }, signal: ctl ? ctl.signal : undefined })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) {
        clearTimeout(timer);
        if (!j || !j.success || !j.version) return fallback();
        try { sessionStorage.setItem(verKey(p.api), JSON.stringify({ at: Date.now(), v: j.version })); } catch (e) {}
        paint(j.version);
      })
      .catch(function () { clearTimeout(timer); fallback(); });
  }

  function openProduct(p, speak, fromLog) {
    if (!p) return;
    state.detail = p;
    lastFocus = document.activeElement;
    var c = C.cat(p.cat);
    var pics = p.shots.length ? p.shots : [p.art];
    var isShot = p.shots.length > 0;
    inner.style.setProperty('--accent', p.palette[2]);
    inner.style.setProperty('--accent2', p.palette[0]);
    var verLine;
    if (p.version) {
      verLine = '<span class="term">$ xman version ' + esc(p.id) + '</span>เวอร์ชันล่าสุด <b>v' + esc(p.version) + '</b>' + (p.released ? ' · ' + thaiDate(p.released) : '') + '<small>กำลังตรวจเวอร์ชันล่าสุดจาก xman4289.com…</small>';
    } else if (p.status === 'live') {
      verLine = '<span class="term">$ xman status ' + esc(p.id) + ' → live</span>ใช้งานได้แล้ว · ' + esc(p.price);
    } else {
      verLine = '<span class="term">$ xman status ' + esc(p.id) + ' → building…</span>กำลังพัฒนา — ติดตามและจองสิทธิ์ได้ที่หน้าสินค้า';
    }
    inner.innerHTML =
      '<button type="button" class="detail-close" aria-label="ปิด">×</button>' +
      '<div class="detail-visual">' +
        '<div class="dv-main' + (isShot ? ' is-shot' : '') + '"><img id="dv-img" src="' + pics[0] + '" alt="ภาพของ ' + esc(p.name) + '">' +
          '<div class="dv-fade"></div>' +
          '<div class="detail-state"><span class="badge ' + p.status + '">' + (p.status === 'live' ? '● LIVE' : '◌ IN DEV') + '</span>' + (p.free ? '<span class="badge free">FREE</span>' : '') + '</div>' +
          (isShot ? '<span class="dv-cap" id="dv-cap">◉ LIVE CAPTURE · ภาพหน้าจอจริง 01 / ' + pad2(pics.length) + '</span>' : '') +
        '</div>' +
        (pics.length > 1 ? '<div class="dv-thumbs" role="group" aria-label="ภาพหน้าจอ">' + pics.map(function (s, i) {
          return '<button type="button" data-shot="' + i + '" aria-current="' + (i === 0) + '" aria-label="ภาพที่ ' + (i + 1) + '"><img src="' + s + '" alt="" loading="lazy"></button>';
        }).join('') + '</div>' : '') +
      '</div>' +
      '<div class="detail-body">' +
        '<span class="eyebrow">' + esc(c.dir) + ' · ' + esc(c.label.toUpperCase()) + '</span>' +
        '<h2 id="detail-title">' + esc(p.name) + '</h2>' +
        '<div class="detail-sub">' + esc(p.sub) + '</div>' +
        '<div class="detail-meta"><span class="price">' + esc(p.price) + '</span>' + p.platforms.map(function (x) { return '<span class="badge">' + esc(x) + '</span>'; }).join('') + '</div>' +
        '<p class="detail-desc">' + esc(p.desc) + '</p>' +
        '<ul class="detail-features">' + p.features.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul>' +
        '<div class="detail-ver" id="detail-ver">' + verLine + '</div>' +
        '<div class="detail-actions">' + actionsHtml(p) + '</div>' +
      '</div>';
    var thumbs = $('.dv-thumbs', inner);
    if (thumbs) {
      thumbs.addEventListener('click', function (e) {
        var b = e.target.closest('[data-shot]');
        if (!b) return;
        var i = +b.getAttribute('data-shot');
        $('#dv-img').src = pics[i];
        var cap = $('#dv-cap');
        if (cap) cap.textContent = '◉ LIVE CAPTURE · ภาพหน้าจอจริง ' + pad2(i + 1) + ' / ' + pad2(pics.length);
        $$('[data-shot]', thumbs).forEach(function (t) { t.setAttribute('aria-current', t === b ? 'true' : 'false'); });
      });
    }
    $('.detail-close', inner).addEventListener('click', closeProduct);
    $$('[data-dl]', inner).forEach(function (a) { a.addEventListener('click', function () { downloaded(p); }); });
    if (typeof dialog.showModal === 'function') {
      if (!dialog.open) dialog.showModal();
    } else dialog.setAttribute('open', '');
    $('.detail-close', inner).focus();
    fetchVersion(p);
    if (speak !== false) {
      var line = fromLog && p.version ? 'เวอร์ชัน ' + p.version + ' ของ ' + p.name + ' เพิ่งปล่อยไปค่ะ ข้อมูลในหน้าต่างนี้ดึงสดจากร้านเลยนะ' : pick(p.nova);
      guide.say({ text: line, pose: 'present', priority: 3 });
    }
  }

  function closeProduct() {
    if (dialog.open) dialog.close();
  }
  dialog.addEventListener('close', function () {
    state.detail = null;
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  });
  // a click on the backdrop closes it
  dialog.addEventListener('click', function (e) {
    if (e.target === dialog) closeProduct();
  });

  function downloaded(p) {
    if (!p) return;
    guide.say({ text: 'กำลังส่งไฟล์ ' + p.name + ' จาก xman4289.com ให้นะคะ ใช้แล้วชอบ กลับมาเล่าให้โนวาฟังด้วย~', pose: 'cheer', priority: 3 });
  }

  /* ------------------------------------------------------------ Nova's actions */

  var TOUR = [
    { go: 'spotlight', line: { text: 'เริ่มที่ NOW RUNNING! โปรแกรมเด่นอยู่ตรงนี้ หน้าจอโฮโลแกรมโชว์ภาพของโปรแกรมนั้น ลากเพื่อหมุนดูได้เลยค่ะ', pose: 'present' } },
    { go: 'feature:winx-tools', line: { text: 'อย่าง WinXTools นี่เป็นภาพหน้าจอจริงทั้ง 9 หน้าเลยนะ มันสลับให้ดูเอง ป้าย LIVE CAPTURE บอกว่าภาพไหนของจริง', pose: 'present' } },
    { go: 'filter:free', line: { text: 'อยากลองก่อนจ่าย? กด free/ จะเหลือแต่โปรแกรมที่ใช้ฟรีหรือมีรุ่นฟรีให้ลอง ✦', pose: 'cheer' } },
    { go: 'releases', line: { text: 'ส่วน release.log คือเวอร์ชันที่ปล่อยจริงล่าสุด ทุกไฟล์ส่งจาก xman4289.com โดยตรงค่ะ', pose: 'present' } },
    { go: 'studio', line: { text: 'และนี่คือ XMAN Studio ผู้สร้างทุกโปรแกรมในฮับนี้ จบทัวร์แล้ว! ไปเลือกโปรแกรมกันเลยนะคะ~', pose: 'bye' } },
  ];

  function runStep(i) {
    state.tourStep = i;
    var step = TOUR[i];
    if (!step) { state.tourStep = -1; return; }
    var parts = step.go.split(':');
    var kind = parts[0];
    var arg = parts[1];
    if (kind === 'spotlight') scrollToId('spotlight', 'center');
    if (kind === 'feature') {
      scrollToId('spotlight', 'center');
      var idx = FEATURED.findIndex(function (f) { return f.id === arg; });
      if (idx >= 0) chooseFeature(idx, false);
      state.touched = performance.now();
    }
    if (kind === 'filter') {
      searchInput.value = '';
      state.query = '';
      applyFilter(arg, null, { scroll: true, speak: false });
    }
    if (kind === 'releases') scrollToId('releases');
    if (kind === 'studio') scrollToId('studio', 'center');
    var last = i === TOUR.length - 1;
    guide.say(Object.assign({}, step.line, {
      priority: 4,
      hold: 60000,
      chips: last
        ? [{ label: 'สุ่มโปรแกรมให้หน่อย', action: 'random', primary: true }, { label: 'ดู BrainX', action: 'open:brainx' }]
        : [{ label: 'ถัดไป › (' + (i + 1) + '/' + TOUR.length + ')', action: 'tourNext', primary: true }, { label: 'จบทัวร์', action: 'tourEnd' }],
    }));
    if (last) state.tourStep = -1;
  }

  function random() {
    searchInput.value = '';
    state.query = '';
    applyFilter('all', null, { speak: false });
    var p = pick(C.PRODUCTS);
    setTimeout(function () {
      var card = $('.card[data-id="' + p.id + '"]');
      if (!card) return;
      card.scrollIntoView({ behavior: smooth(), block: 'center' });
      card.classList.remove('spot');
      void card.offsetWidth;
      card.classList.add('spot');
    }, 60);
    var chips = [{ label: 'ดูรายละเอียด', action: 'open:' + p.id, primary: true }];
    if (p.download) chips.push({ label: p.downloadLabel, action: 'download:' + p.id });
    chips.push({ label: 'สุ่มอีก', action: 'random' });
    guide.say({ text: 'โนวาสุ่มได้… ' + p.name + '! ' + p.tagline, pose: 'cheer', react: 'heart', priority: 3, hold: 15000, chips: chips });
  }

  guide.register({
    tour: function () { runStep(0); },
    tourNext: function () { runStep(state.tourStep + 1); },
    tourEnd: function () {
      state.tourStep = -1;
      guide.say({ text: 'โอเคค่ะ! อยากให้ช่วยอะไรเมื่อไหร่ จิ้มโนวาได้เลยนะ', pose: 'welcome', priority: 3 });
    },
    random: random,
    motionOn: function () { prefs.setMotion(true); },
    free: function () { applyFilter('free', null, { scroll: true }); },
    open: function (id) { openProduct(C.byId(id), false); },
    download: function (id) {
      var p = C.byId(id);
      if (!p || !p.download) return;
      if (p.dlPage) window.open(p.download, '_blank', 'noopener');
      else window.location.href = p.download;
      downloaded(p);
    },
    dismiss: function () {
      guide.say({ text: 'ได้เลย~ ถ้าต้องการโนวา จิ้มที่ตัวโนวาได้ทุกเมื่อนะคะ', pose: 'welcome', priority: 3 });
    },
  });

  /* ------------------------------------------------------------ greeting */

  setTimeout(function () {
    var back = false;
    try {
      back = localStorage.getItem('xph.visited') === '1';
      localStorage.setItem('xph.visited', '1');
    } catch (e) {}
    guide.say(back
      ? {
          text: 'กลับมาแล้ว! วันนี้หาโปรแกรมแบบไหนอยู่คะ? ให้โนวาสุ่มให้ก็ได้นะ',
          pose: 'welcome', priority: 3, hold: 9000,
          chips: [{ label: 'สุ่มโปรแกรมให้หน่อย', action: 'random', primary: true }, { label: 'ของฟรีมีอะไรบ้าง', action: 'free' }, { label: 'พาทัวร์', action: 'tour' }],
        }
      : {
          text: 'สวัสดีค่า~ น้อง Nova เองค่ะ ไกด์ประจำ XMAN Studio! ที่นี่มีโปรแกรมที่ทีมเราเขียนเองทั้ง ' + counts.all + ' ตัว อยากให้โนวาพาทัวร์มั้ยคะ?',
          pose: 'welcome', priority: 3, hold: 14000,
          chips: [{ label: 'พาทัวร์หน่อย', action: 'tour', primary: true }, { label: 'สุ่มโปรแกรมให้หน่อย', action: 'random' }, { label: 'เดี๋ยวดูเอง', action: 'dismiss' }],
        });
  }, 1400);

  /* ------------------------------------------------------------ sections */

  var section = 'discover';
  var crumb = $('#crumb');
  var NAMES = { spotlight: 'discover', products: 'products', releases: 'release.log', studio: 'studio.md' };
  var FIRST = {
    products: { text: 'นี่คือโปรแกรมทั้ง ' + counts.all + ' ตัวของเราค่ะ ชี้ที่การ์ดใบไหน โนวาจะเล่าให้ฟังเอง~ การ์ดที่มีภาพจริง เอาเมาส์ไปชี้จะเห็นหน้าจอจริงนะ', pose: 'present', priority: 1 },
    releases: { text: 'ทุกเวอร์ชันที่ปล่อยจริงอยู่ตรงนี้นะ กดการ์ดเพื่อดูเวอร์ชันล่าสุดสด ๆ จากร้านได้เลย!', pose: 'present', priority: 1 },
    studio: { text: 'XMAN Studio สตูดิโอเล็ก ๆ ที่เขียนทุกบรรทัดเอง ขอบคุณที่แวะมานะคะ ✦', pose: 'bye', priority: 1 },
  };
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      var id = e.target.id;
      if (id === 'spotlight') state.spotVisible = e.isIntersecting;
      if (!e.isIntersecting) return;
      section = id === 'spotlight' ? 'discover' : id === 'releases' ? 'releases' : id;
      crumb.textContent = NAMES[id] || 'discover';
      navActive();
      if (FIRST[id] && !state.said[id] && state.tourStep < 0) {
        state.said[id] = true;
        guide.say(FIRST[id]);
      }
    });
  }, { threshold: 0.3 });
  ['spotlight', 'products', 'releases', 'studio'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) io.observe(el);
  });

  // 3D rise-in for blocks as they enter
  var rise = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) {
        e.target.classList.add('in');
        rise.unobserve(e.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
  $$('.rise').forEach(function (el) { rise.observe(el); });
  // never leave content hidden if the observer is late or missing
  setTimeout(function () { $$('.rise').forEach(function (el) { el.classList.add('in'); }); }, 3500);

  /* ------------------------------------------------------------ first paint */

  renderCards();
  renderSpot(null);
})();
