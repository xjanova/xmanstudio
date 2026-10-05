/* น้อง Nova — the hub's guide. The same Nova who flies the 3D home page on
 * xman4289.com: her stills and transparent VP9 clips are copies of
 * public_html/artwork/universe/guide (recipe: docs/UNIVERSE_GUIDE_CLIPS.md in
 * the xmanstudio repo). Frame 0 of every clip is its still, so a clip takes
 * over from the picture without a jump; the stills stay the fallback (Safari
 * draws VP9 alpha black, Save-Data, motion off, phones).
 *
 * window.XPH.prefs  — the Motion switch (remembered on this device)
 * window.XPH.guide  — "say this" / "do that": any part of the page can make her speak
 */
(function () {
  'use strict';

  var XPH = (window.XPH = window.XPH || {});
  var root = document.documentElement;
  var NOVA_V = '202610051200'; // bump when a clip/still is re-encoded under the same name
  var BASE = '/assets/nova/';

  /* ------------------------------------------------------------ prefs */

  var motion = null;
  var prefs = {
    motion: function () {
      if (motion !== null) return motion;
      var stored = null;
      try { stored = localStorage.getItem('xph.motion'); } catch (e) {}
      var reduced = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
      motion = stored ? stored === 'on' : !reduced;
      return motion;
    },
    setMotion: function (on) {
      motion = !!on;
      try { localStorage.setItem('xph.motion', on ? 'on' : 'off'); } catch (e) {}
      root.dataset.motion = on ? 'on' : 'off';
      window.dispatchEvent(new Event('xph:motion'));
    },
    init: function () { root.dataset.motion = prefs.motion() ? 'on' : 'off'; },
  };
  XPH.prefs = prefs;
  prefs.init();

  /** Video with alpha only where it is decoded with alpha and the visitor can afford it. */
  function canPlayAlphaVideo() {
    var ua = navigator.userAgent;
    var webkitOnly = /AppleWebKit/.test(ua) && !/Chrome|Chromium|Edg|Firefox|FxiOS|CriOS/.test(ua);
    var ios = /iPhone|iPad|iPod/.test(ua);
    var conn = navigator.connection;
    if (webkitOnly || ios || (conn && conn.saveData)) return false;
    if (!prefs.motion()) return false;
    return window.matchMedia('(min-width: 720px)').matches;
  }

  /* ------------------------------------------------------------ the store */

  var listeners = [];
  var actions = {};
  var current = null;
  var seq = 0;
  var protectedUntil = 0;
  var currentPriority = 0;
  var minimized = false;
  try { minimized = localStorage.getItem('xph.nova.min') === '1'; } catch (e) {}

  var guide = {
    /** line: {text, pose, after, react, chips:[{label, action, primary}], priority, hold} */
    say: function (line) {
      var now = performance.now();
      var p = line.priority == null ? 1 : line.priority;
      if (now < protectedUntil && p < currentPriority) return false;
      currentPriority = p;
      // rough reading time: Thai has no spaces, count characters
      protectedUntil = now + 900 + line.text.length * 38 + (line.hold == null ? 2200 : line.hold);
      current = Object.assign({}, line, { id: ++seq });
      listeners.forEach(function (fn) { fn(current); });
      return true;
    },
    release: function () { protectedUntil = 0; currentPriority = 0; },
    register: function (map) { Object.keys(map).forEach(function (k) { actions[k] = map[k]; }); },
    run: function (action) {
      var i = action.indexOf(':');
      var name = i < 0 ? action : action.slice(0, i);
      var arg = i < 0 ? undefined : action.slice(i + 1);
      if (actions[name]) actions[name](arg);
    },
    setMinimized: function (on) {
      minimized = !!on;
      try { localStorage.setItem('xph.nova.min', on ? '1' : '0'); } catch (e) {}
      if (ui) ui.sync();
    },
    onLine: function (fn) { listeners.push(fn); },
  };
  XPH.guide = guide;

  XPH.pick = function (list) { return list[Math.floor(Math.random() * list.length)]; };

  function graphemes(text) {
    try {
      var seg = new Intl.Segmenter('th', { granularity: 'grapheme' });
      return Array.from(seg.segment(text), function (s) { return s.segment; });
    } catch (e) {
      return Array.from(text);
    }
  }

  /* ------------------------------------------------------------ her clips */

  var POSES = ['welcome', 'present', 'cheer', 'bye'];
  // still: the picture that is frame 0 · loop: forward-and-back baked · pad: rendered with 8% room around her
  var CLIPS = {
    idle: { still: 'welcome', loop: true, pad: false },
    talk: { still: 'welcome', loop: true, pad: false },
    think: { still: 'welcome', loop: true, pad: true },
    present: { still: 'present', loop: true, pad: false },
    cheer: { still: 'cheer', loop: true, pad: true },
    wave: { still: 'bye', loop: true, pad: false },
    surprise: { still: 'welcome', loop: false, pad: true },
    heart: { still: 'welcome', loop: false, pad: true },
    kiss: { still: 'welcome', loop: false, pad: true },
    shy: { still: 'welcome', loop: false, pad: true },
  };
  var POSE_LOOP = { welcome: 'idle', present: 'present', cheer: 'cheer', bye: 'wave' };
  var FACE = BASE + 'stills/face.webp?v=' + NOVA_V;
  XPH.NOVA_FACE = FACE;

  var POKES = [
    { text: 'ว้าย! จิ้มโนวาทำไมเนี่ย~ ตกใจหมดเลย', react: 'surprise', pose: 'welcome' },
    { text: 'แหะ ๆ ชอบโนวาเหรอคะ? งั้นไปดูโปรแกรมด้วยกันนะ ✦', react: 'heart', pose: 'welcome' },
    { text: 'จุ๊บ~ ขอบคุณที่แวะมาสตูดิโอของเรานะคะ', react: 'kiss', pose: 'welcome' },
    { text: 'เขินนะ… โนวากำลังตั้งใจเป็นไกด์อยู่เลย', react: 'shy', pose: 'welcome' },
    { text: 'อยากให้โนวาสุ่มโปรแกรมให้มั้ยคะ?', react: 'heart', pose: 'welcome', chips: [{ label: 'สุ่มเลย!', action: 'random', primary: true }] },
  ];
  var FIDGETS = ['heart', 'kiss', 'shy'];
  var TIPS = [
    'ลากที่หน้าจอโฮโลแกรมเพื่อหมุนดูได้นะ หรือกดลูกศรซ้าย–ขวาก็ได้',
    'กด Ctrl + K (หรือ /) แล้วพิมพ์ชื่อโปรแกรม เช่น “AI” “VPN” หรือ “Android” ได้เลย',
    'การ์ดที่มีป้าย LIVE CAPTURE คือมีภาพหน้าจอจริงให้ดูนะคะ',
    'ถ้าเครื่องเริ่มหน่วง กดปุ่ม Motion ด้านบนเพื่อพักเอฟเฟกต์ได้',
    'อยากให้โนวาเลือกให้? กด “สุ่มโปรแกรม” ได้ทุกเมื่อเลย',
  ];

  /* ------------------------------------------------------------ the figure */

  var ui = null;

  function el(tag, cls, attrs) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (attrs) Object.keys(attrs).forEach(function (k) { e.setAttribute(k, attrs[k]); });
    return e;
  }

  function buildUI() {
    var wrap = el('div', 'nova', { 'data-mode': 'dock', 'data-min': '0', 'data-bubble': '0' });
    var body = el('div', 'nova-body');
    var aura = el('div', 'nova-aura', { 'aria-hidden': 'true' });
    var figure = el('div', 'nova-figure');
    var stills = {};
    POSES.forEach(function (p) {
      var im = el('img', 'nova-still' + (p === 'welcome' ? ' on' : ''), { alt: '', draggable: 'false', decoding: 'async' });
      im.loading = p === 'welcome' ? 'eager' : 'lazy';
      im.src = BASE + 'stills/' + p + '.webp?v=' + NOVA_V;
      im.onerror = function () {
        if (p === 'welcome') wrap.classList.add('is-missing');
        brokenStill[p] = true;
        if (im.classList.contains('on')) { im.classList.remove('on'); stills.welcome.classList.add('on'); }
      };
      stills[p] = im;
      figure.appendChild(im);
    });
    var videos = {};
    Object.keys(CLIPS).forEach(function (m) {
      var v = el('video', 'nova-clip' + (CLIPS[m].pad ? ' pad' : ''), { 'aria-hidden': 'true', tabindex: '-1' });
      v.muted = true;
      v.playsInline = true;
      v.preload = 'none';
      v.disablePictureInPicture = true;
      v.setAttribute('muted', '');
      v.setAttribute('playsinline', '');
      v.dataset.move = m;
      v.dataset.src = BASE + 'clips/' + m + '.webm?v=' + NOVA_V;
      videos[m] = v;
      figure.appendChild(v);
    });
    var hit = el('button', 'nova-hit', { type: 'button', 'aria-label': 'จิ้มน้อง Nova' });
    body.appendChild(aura);
    body.appendChild(figure);
    body.appendChild(hit);

    var bubble = el('section', 'nova-bubble', { 'aria-label': 'น้อง Nova ไกด์ของ XMAN Studio' });
    bubble.innerHTML =
      '<header><span class="nova-name"><i></i> NOVA · DEV GUIDE</span>' +
      '<button type="button" class="nova-x" aria-label="ปิดคำพูด">×</button></header>' +
      '<p class="nova-text" aria-hidden="true"><span class="nova-typed"></span><span class="caret"></span></p>' +
      '<p class="sr-only" role="status" aria-live="polite"></p>' +
      '<div class="nova-chips"></div>';
    var ctl = el('button', 'nova-ctl', { type: 'button', 'aria-label': 'ย่อน้อง Nova' });
    ctl.textContent = '–';
    var face = el('button', 'nova-face', { type: 'button', 'aria-label': 'คุยกับน้อง Nova' });
    face.innerHTML = '<img src="' + FACE + '" alt="" draggable="false"><span class="nova-dot"></span>';

    wrap.appendChild(body);
    wrap.appendChild(bubble);
    wrap.appendChild(ctl);
    wrap.appendChild(face);
    document.body.appendChild(wrap);

    return {
      wrap: wrap, body: body, stills: stills, videos: videos, hit: hit, bubble: bubble, ctl: ctl, face: face,
      typed: bubble.querySelector('.nova-typed'), live: bubble.querySelector('[role=status]'),
      chips: bubble.querySelector('.nova-chips'), x: bubble.querySelector('.nova-x'),
    };
  }

  var brokenStill = {};
  var brokenClip = {};

  function Figure(dom) {
    var active = null;
    var wanted = 'idle';
    var z = 2;
    var videoOK = canPlayAlphaVideo();

    function refresh() {
      videoOK = canPlayAlphaVideo();
      if (!videoOK && active) {
        active.classList.remove('on');
        active.pause();
        active = null;
      }
    }
    window.addEventListener('resize', refresh);
    window.addEventListener('xph:motion', refresh);

    // trickle-load the one-off moves after the page settles
    setTimeout(function () {
      if (!videoOK) return;
      var queue = ['talk', 'present', 'surprise', 'heart', 'kiss', 'shy'];
      (function next() {
        var m = queue.shift();
        var v = m && dom.videos[m];
        if (!v) return;
        if (!v.src) {
          v.preload = 'auto';
          v.src = v.dataset.src;
          v.load();
        }
        v.addEventListener('canplaythrough', next, { once: true });
        v.addEventListener('error', next, { once: true });
      })();
    }, 6000);

    function sameStill(v, clip) {
      return CLIPS[v.dataset.move || 'idle'].still === clip.still;
    }

    /** Show a clip (or its still where clips cannot play). onEnd fires for one-off moves. */
    this.show = function (move, onEnd) {
      var clip = CLIPS[move];
      wanted = move;
      var still = brokenStill[clip.still] ? 'welcome' : clip.still;
      POSES.forEach(function (p) { dom.stills[p].classList.toggle('on', p === still); });

      var v = dom.videos[move];
      if (!videoOK || !v || brokenClip[move]) {
        if (active && !sameStill(active, clip)) {
          active.classList.remove('on');
          active.pause();
          active = null;
        }
        if (onEnd) setTimeout(onEnd, 1600);
        return;
      }

      function start() {
        if (wanted !== move) return;
        var prev = active;
        v.loop = clip.loop;
        v.onended = clip.loop ? null : function () { if (onEnd) onEnd(); };
        try { v.currentTime = 0; } catch (e) {}
        v.style.zIndex = String(++z);
        v.classList.add('on');
        var pr = v.play();
        if (pr && pr.catch) {
          pr.catch(function (e) {
            // AbortError = paused right after play(); not a broken clip
            if (!e || e.name !== 'AbortError') {
              brokenClip[move] = true;
              v.classList.remove('on');
              if (onEnd) onEnd();
            }
          });
        }
        active = v;
        // the new clip fades in OVER the old one, which stays opaque until the fade is done
        if (prev && prev !== v) {
          setTimeout(function () {
            if (active !== prev) { prev.classList.remove('on'); prev.pause(); }
          }, 260);
        }
      }

      if (!v.src) { v.src = v.dataset.src; v.load(); }
      if (v.readyState >= 3) start();
      else {
        if (active && !sameStill(active, clip)) {
          active.classList.remove('on');
          active.pause();
          active = null;
        }
        v.addEventListener('canplay', start, { once: true });
        v.addEventListener('error', function () {
          brokenClip[move] = true;
          if (wanted === move && onEnd) onEnd();
        }, { once: true });
      }
    };
  }

  /* ------------------------------------------------------------ the guide UI */

  function Guide() {
    var dom = buildUI();
    var fig = new Figure(dom);
    var loop = 'idle';
    var pose = 'welcome';
    var busy = false;
    var typing = false;
    var lastLineAt = 0;
    var tipCount = 0;
    var mode = 'dock';
    var bubbleOn = false;
    var typeTimer = 0;
    var hideTimer = 0;
    var flyTimer = 0;
    var DOCK_H = 340;

    function setBubble(on) {
      bubbleOn = on;
      sync();
    }

    function sync() {
      dom.wrap.dataset.mode = mode;
      dom.wrap.dataset.min = minimized ? '1' : '0';
      dom.wrap.dataset.bubble = bubbleOn && (!minimized || mode === 'mini') ? '1' : '0';
      dom.ctl.hidden = minimized || mode === 'mini';
      dom.hit.tabIndex = minimized ? -1 : 0;
      dom.face.setAttribute('aria-label', minimized ? 'เรียกน้อง Nova กลับมา' : 'คุยกับน้อง Nova');
    }

    function playOnce(m, then) {
      busy = true;
      fig.show(m, function () {
        busy = false;
        if (then) then();
        else fig.show(loop);
      });
    }

    function renderChips(chips) {
      dom.chips.innerHTML = '';
      (chips || []).forEach(function (c) {
        var b = el('button', c.primary ? 'primary' : '', { type: 'button' });
        b.textContent = c.label;
        b.addEventListener('click', function () {
          guide.release();
          guide.run(c.action);
        });
        dom.chips.appendChild(b);
      });
    }

    /* speak: type the line out, talk while typing, then settle into a pose */
    guide.onLine(function (line) {
      clearInterval(typeTimer);
      clearTimeout(hideTimer);
      lastLineAt = performance.now();
      pose = line.pose || 'welcome';
      var talk = pose === 'welcome' ? 'talk' : POSE_LOOP[pose];
      var settle = line.after || POSE_LOOP[pose];
      var parts = graphemes(line.text);
      var i = 0;
      dom.typed.textContent = '';
      dom.live.textContent = line.text;
      renderChips(line.chips);
      setBubble(true);

      function finish() {
        typing = false;
        loop = settle;
        if (!busy) fig.show(settle);
        // her words tidy themselves away once read; a line with buttons waits longer,
        // and on the stage only plain lines go (the buttons are the point there)
        var chips = !!(line.chips && line.chips.length);
        if (!(mode === 'stage' && chips)) {
          hideTimer = setTimeout(function () { setBubble(false); }, (mode === 'stage' ? 7000 : 9000) + (chips ? 9000 : 0) + line.text.length * 40);
        }
      }
      function type() {
        typing = true;
        loop = talk;
        if (!busy) fig.show(talk);
        if (!prefs.motion()) {
          dom.typed.textContent = line.text;
          finish();
          return;
        }
        typeTimer = setInterval(function () {
          i = Math.min(parts.length, i + 1);
          dom.typed.textContent = parts.slice(0, i).join('');
          if (i >= parts.length) {
            clearInterval(typeTimer);
            finish();
          }
        }, 32);
      }
      if (line.react) playOnce(line.react, type);
      else type();
    });

    /* where she stands: beside the spotlight on wide screens, else the corner */
    var raf = 0;
    function place() {
      raf = 0;
      var vw = window.innerWidth;
      var vh = window.innerHeight;
      var spot = document.getElementById('spotlight');
      var r = spot ? spot.getBoundingClientRect() : null;
      var small = vw < 720;
      var H = Math.max(DOCK_H, Math.min((r ? r.height : 560) * 1.04, vh * 0.78));
      var W = H * (2 / 3);
      // she stands on the panel only while her head clears the top bar; scroll further and she docks
      var stageOK = !small && vw >= 1280 && !!r && r.top < vh * 0.55 && r.bottom - H * 0.96 >= 40;
      var m = small ? 'mini' : stageOK ? 'stage' : 'dock';
      if (m !== mode) {
        dom.wrap.classList.add('flying');
        clearTimeout(flyTimer);
        flyTimer = setTimeout(function () { dom.wrap.classList.remove('flying'); }, 750);
        mode = m;
        sync();
      }
      // the box is laid out at stage size and scaled DOWN for the dock (stays sharp)
      var x, y, s;
      if (m === 'stage' && r) {
        s = 1;
        x = r.right - W * 0.8;
        y = r.bottom - H * 0.97;
      } else {
        s = Math.min(DOCK_H, vh * 0.42) / H;
        x = vw - W * s - 8;
        y = vh - H * s + 4;
      }
      var st = dom.wrap.style;
      st.setProperty('--nh', H + 'px');
      st.setProperty('--nw', W + 'px');
      st.setProperty('--nx', x + 'px');
      st.setProperty('--ny', y + 'px');
      st.setProperty('--ns', String(s));
      if (m === 'stage') {
        // beside her head, high: the product's screen sits low in the stage below
        st.setProperty('--bx', Math.max(16, x + W * 0.2) + 'px');
        st.setProperty('--by', Math.max(84, y + H * 0.03) + 'px');
      } else {
        st.setProperty('--bx', Math.min(vw - 10, x + W * s * 0.94) + 'px');
        st.setProperty('--by', y + H * s * 0.1 + 'px');
      }
    }
    function queue() { if (!raf) raf = requestAnimationFrame(place); }
    place();
    window.addEventListener('scroll', queue, { passive: true });
    window.addEventListener('resize', queue);
    // the page reflows once fonts and cards arrive
    setTimeout(queue, 400);
    setTimeout(queue, 1500);

    /* idle life: little fidgets, and an occasional tip */
    var nextFidget = performance.now() + 9000 + Math.random() * 9000;
    setInterval(function () {
      var now = performance.now();
      if (document.hidden || typing || busy) return;
      if (now - lastLineAt > 38000 && tipCount < 3) {
        tipCount++;
        guide.say({ text: XPH.pick(TIPS), pose: 'welcome', priority: 0, react: 'heart' });
        return;
      }
      if (now > nextFidget && pose === 'welcome' && prefs.motion() && !minimized) {
        nextFidget = now + 9000 + Math.random() * 9000;
        playOnce(XPH.pick(FIDGETS));
      }
    }, 1000);

    function poke() {
      guide.release();
      guide.say(Object.assign({}, XPH.pick(POKES), { priority: 2 }));
    }
    dom.hit.addEventListener('click', poke);
    dom.x.addEventListener('click', function () { setBubble(false); });
    dom.ctl.addEventListener('click', function () { guide.setMinimized(true); });
    dom.face.addEventListener('click', function () {
      if (minimized) guide.setMinimized(false);
      if (mode === 'mini') setBubble(!bubbleOn);
      else poke();
    });

    /* thinking while the visitor types in the search box */
    this.think = function (on) {
      if (busy) return;
      if (on && pose === 'welcome' && !typing) fig.show('think');
      else if (!on && !typing) fig.show(loop);
    };

    this.sync = sync;
    fig.show('idle');
    sync();
  }

  function start() {
    ui = new Guide();
    XPH.nova = ui;
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();
