{{--
    NOVA LAYOUT — deep-space / 3D star-system theme.

    Scoped to the Nova home page, mirroring how layouts.retro is scoped to
    home-retro. ThemeService::getPublicLayout() intentionally falls through to
    layouts.app for every other page.

    Navigation contract: the orbital star menu is a PROGRESSIVE ENHANCEMENT.
    The markup below renders a plain, always-visible bar of real <a> links.
    Only once the inline head script proves JS runs (html.nova-js) does it
    become the 3D star menu. A blocked or broken script therefore degrades to
    an ordinary nav rather than to a site with no navigation at all.
--}}
@php
    $novaLogo = \App\Models\Setting::getValue('site_logo');
    $novaFavicon = \App\Models\Setting::getValue('site_favicon');

    // Orbital menu items. `accent` drives the per-item glow via --nv-accent.
    $novaMenu = [
        ['th' => 'หน้าหลัก',    'en' => 'Home',      'href' => url('/'),                  'accent' => '#22d3ee', 'icon' => 'home',  'art' => 'home'],
        ['th' => 'บริการ',      'en' => 'Services',  'href' => route('services.index'),   'accent' => '#8b5cf6', 'icon' => 'grid',  'art' => 'services'],
        ['th' => 'ผลิตภัณฑ์',   'en' => 'Products',  'href' => config('app.product_site_url'), 'accent' => '#e879f9', 'icon' => 'cube',  'art' => 'products'],
        ['th' => 'เช่าใช้งาน',  'en' => 'Rental',    'href' => route('rental.index'),     'accent' => '#34d399', 'icon' => 'clock', 'art' => 'rental'],
        ['th' => 'สร้างภาพ AI', 'en' => 'XDreamer',  'href' => route('xdreamer.home'),    'accent' => '#f472b6', 'icon' => 'spark', 'art' => 'xdreamer'],
        ['th' => 'เรียนโค้ด',   'en' => 'Academy',   'href' => route('code-academy'),     'accent' => '#38bdf8', 'icon' => 'book',  'art' => 'academy'],
        ['th' => 'เพลง',        'en' => 'Metal-X',   'href' => route('metal-x.index'),    'accent' => '#fb7185', 'icon' => 'play',  'art' => 'metalx'],
        ['th' => 'ติดต่อเรา',   'en' => 'Contact',   'href' => route('support.index'),    'accent' => '#ffd479', 'icon' => 'chat',  'art' => 'contact'],
    ];
@endphp
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#04050d">

    <x-seo-meta
        :title="View::yieldContent('title', 'XMAN Studio - IT Solutions & Software Development')"
        :description="View::yieldContent('meta_description', 'XMAN Studio ผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร ทำเว็บไซต์ แอพพลิเคชัน Blockchain IoT Network Security AI และอื่นๆ')"
        :image="View::yieldContent('og_image', '')"
    />

    @if($novaFavicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $novaFavicon) }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('storage/' . $novaFavicon) }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('public_html/favicon.ico') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    {{-- Mark JS as available before first paint so .nova-reveal can start
         hidden without ever risking a permanently blank page. --}}
    <script>document.documentElement.classList.add('nova-js');</script>

    @php
        $customHeadCode = \App\Models\Setting::getValue('custom_code_head', '');
    @endphp
    @if($customHeadCode)
        {!! $customHeadCode !!}
    @endif

    <x-adsense-head />
</head>
<body class="nova-body">
    @php
        $customBodyStartCode = \App\Models\Setting::getValue('custom_code_body_start', '');
    @endphp
    @if($customBodyStartCode)
        {!! $customBodyStartCode !!}
    @endif

    <a href="#nova-main" class="nova-skip">ข้ามไปยังเนื้อหา / Skip to content</a>

    {{-- 3D backdrop. Hand-written perspective projection on a 2D canvas —
         no WebGL, no external library, so nothing here can be broken by a
         blocked script host. The canvas is decorative only. --}}
    <div class="nova-canvas-layer" aria-hidden="true">
        <canvas id="nova-canvas"></canvas>
    </div>
    <div class="nova-vignette" aria-hidden="true"></div>

    {{-- PRIMARY NAVIGATION.
         Baseline: a plain link bar (works with JS off, crawlable, keyboard
         reachable). Enhanced: an orbital ring launched from the XMAN star. --}}
    <nav id="nova-nav" class="nova-nav" data-open="false" aria-label="เมนูหลัก / Main menu">
        <div class="nova-nav__scrim" data-nova-close aria-hidden="true"></div>
        <div class="nova-nav__ring">
            @foreach($novaMenu as $i => $item)
                <a href="{{ $item['href'] }}"
                   class="nova-nav__link"
                   style="--nv-accent: {{ $item['accent'] }};"
                   data-nova-orbit="{{ $i }}">
                    {{-- Artwork tile, shown only in the orbital (JS) menu — it
                         replaces the glyph that used to sit there. The no-JS bar
                         stays a plain text list. width/height are set so the chip
                         measures the same before the image loads: layout() picks
                         the ring radius from offsetHeight the moment it opens. --}}
                    <span class="nova-nav__thumb" aria-hidden="true">
                        <img src="{{ asset('artwork/menu/' . $item['art'] . '.webp') }}"
                             alt="" width="208" height="116" decoding="async">
                    </span>
                    <span class="nova-nav__icon" aria-hidden="true">
                        @include('partials.nova-icon', ['name' => $item['icon']])
                    </span>
                    <span class="nova-nav__th">{{ $item['th'] }}</span>
                    <span class="nova-nav__en">{{ $item['en'] }}</span>
                </a>
            @endforeach
        </div>
        <button type="button" class="nova-nav__close" data-nova-close>ปิด / Close (Esc)</button>
    </nav>

    <main id="nova-main" class="nova-main">
        @yield('content')
    </main>

    @include('partials.nova-footer')

    {{-- The star trigger, mirrored into the corner once the hero scrolls away
         so navigation stays reachable from anywhere on the page. --}}
    <button type="button"
            id="nova-star-mini"
            class="nova-star-mini"
            aria-controls="nova-nav"
            aria-expanded="false"
            hidden>
        <span class="nova-sr">เปิดเมนู / Open menu</span>
        <span class="nova-star-mini__glow" aria-hidden="true"></span>
        @if($novaLogo)
            <img src="{{ asset('storage/' . $novaLogo) }}" alt="" class="nova-star-mini__logo">
        @else
            <span class="nova-star-mini__word" aria-hidden="true">X</span>
        @endif
    </button>

    <style>
        /* Corner star — layout-local because it only exists in this shell. */
        .nova-star-mini {
            position: fixed;
            right: 22px;
            bottom: 22px;
            z-index: 55;
            display: grid;
            place-items: center;
            width: 60px;
            height: 60px;
            padding: 0;
            border-radius: 50%;
            border: 1px solid var(--nv-line-hi);
            background: radial-gradient(ellipse 100% 100% at 50% 30%, rgba(40, 52, 96, .95), rgba(6, 9, 22, .98));
            box-shadow: 0 14px 44px rgba(0, 0, 0, .7);
            cursor: pointer;
            opacity: 0;
            transform: scale(.6);
            transition: opacity .4s var(--nv-ease), transform .4s var(--nv-ease), box-shadow .4s var(--nv-ease);
        }
        .nova-star-mini[hidden] { display: none; }
        .nova-star-mini.is-shown { opacity: 1; transform: scale(1); }
        .nova-star-mini:hover,
        .nova-star-mini:focus-visible {
            box-shadow: 0 0 0 1px var(--nv-core), 0 14px 54px rgba(0, 0, 0, .8), 0 0 34px -8px var(--nv-core);
        }
        .nova-star-mini__glow {
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, var(--nv-cyan), var(--nv-violet), var(--nv-magenta), var(--nv-core), var(--nv-cyan));
            filter: blur(10px);
            opacity: .5;
            animation: nova-spin 14s linear infinite;
            pointer-events: none;
        }
        .nova-star-mini__logo { position: relative; width: 62%; height: auto; pointer-events: none; }
        .nova-star-mini__word {
            position: relative;
            font-size: 22px;
            font-weight: 900;
            color: var(--nv-fg-1);
            pointer-events: none;
        }
        @media (prefers-reduced-motion: reduce) {
            .nova-star-mini__glow { animation: none; }
        }
    </style>

    @stack('scripts')

    <script>
    /* =====================================================================
       NOVA — canvas star-field + orbital menu.
       Zero dependencies. Everything degrades safely:
         • canvas failure   → page keeps its CSS background
         • observer failure → timeout backstop reveals all content
         • JS failure       → html.nova-js never set, nav stays a plain bar
       ===================================================================== */
    (function () {
        'use strict';

        var reduceMotion = window.matchMedia
            ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
            : false;

        /* ---------------------------------------------------------------
           1. 3D star field
           Perspective projection of points in a rotating cylinder of space:
           clusters of nodes, faint filaments between close neighbours, and
           a soft nebula band behind everything.
           --------------------------------------------------------------- */
        (function starfield() {
            var canvas = document.getElementById('nova-canvas');
            if (!canvas) return;

            var ctx;
            try {
                ctx = canvas.getContext('2d');
            } catch (e) {
                return;
            }
            if (!ctx) return;

            var W = 0, H = 0, dpr = 1;
            var FOCAL = 620;
            var DEPTH = 1400;
            var nodes = [];
            var nebula = [];
            var rafId = null;
            var angle = 0;
            var warp = 0;               // brief speed boost when the menu opens
            var pointerX = 0, pointerY = 0;
            var targetX = 0, targetY = 0;

            var PALETTE = [
                [34, 211, 238],
                [139, 92, 246],
                [232, 121, 249],
                [52, 211, 153],
                [255, 212, 121]
            ];

            function rand(min, max) { return min + Math.random() * (max - min); }

            function build() {
                // Scale the population to the viewport, with a hard ceiling so
                // a large desktop canvas cannot melt a weak GPU.
                var area = W * H;
                var count = Math.round(Math.min(340, Math.max(90, area / 7200)));
                nodes = [];

                for (var i = 0; i < count; i++) {
                    var colour = PALETTE[(Math.random() * PALETTE.length) | 0];
                    nodes.push({
                        // Cylindrical placement keeps density even under rotation.
                        r: rand(60, Math.max(W, H) * 0.85),
                        theta: rand(0, Math.PI * 2),
                        y: rand(-H * 0.6, H * 0.6),
                        z: rand(-DEPTH * 0.5, DEPTH * 0.5),
                        size: rand(0.7, 2.4),
                        twinkle: rand(0, Math.PI * 2),
                        speed: rand(0.15, 0.6),
                        colour: colour
                    });
                }

                nebula = [];
                for (var n = 0; n < 5; n++) {
                    nebula.push({
                        x: rand(0.1, 0.9),
                        y: rand(0.15, 0.85),
                        radius: rand(0.28, 0.62),
                        colour: PALETTE[(Math.random() * PALETTE.length) | 0],
                        alpha: rand(0.05, 0.13),
                        drift: rand(0, Math.PI * 2)
                    });
                }
            }

            function resize() {
                // Cap DPR — a 3x retina phone gains nothing here but pays for
                // every pixel.
                dpr = Math.min(window.devicePixelRatio || 1, 2);
                W = window.innerWidth;
                H = window.innerHeight;
                canvas.width = Math.round(W * dpr);
                canvas.height = Math.round(H * dpr);
                canvas.style.width = W + 'px';
                canvas.style.height = H + 'px';
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                build();
            }

            function project(node, a) {
                var th = node.theta + a * node.speed;
                var x = Math.cos(th) * node.r;
                var z = node.z + Math.sin(th) * node.r * 0.45;
                var denom = FOCAL + z + DEPTH * 0.5;
                if (denom <= 1) return null;
                var scale = FOCAL / denom;
                return {
                    x: W / 2 + (x + pointerX * 40) * scale,
                    y: H / 2 + (node.y + pointerY * 26) * scale,
                    scale: scale,
                    depth: z
                };
            }

            function drawNebula(t) {
                for (var i = 0; i < nebula.length; i++) {
                    var b = nebula[i];
                    var cx = (b.x + Math.sin(t * 0.00004 + b.drift) * 0.035) * W;
                    var cy = (b.y + Math.cos(t * 0.00003 + b.drift) * 0.03) * H;
                    var rad = b.radius * Math.max(W, H);
                    var g = ctx.createRadialGradient(cx, cy, 0, cx, cy, rad);
                    var c = b.colour;
                    g.addColorStop(0, 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + b.alpha + ')');
                    g.addColorStop(1, 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',0)');
                    ctx.fillStyle = g;
                    ctx.fillRect(0, 0, W, H);
                }
            }

            function frame(t) {
                ctx.clearRect(0, 0, W, H);

                // Ease the parallax toward the pointer instead of snapping.
                pointerX += (targetX - pointerX) * 0.045;
                pointerY += (targetY - pointerY) * 0.045;

                drawNebula(t);

                if (warp > 0) warp *= 0.94;
                angle += 0.00022 + warp;

                var projected = [];
                for (var i = 0; i < nodes.length; i++) {
                    var p = project(nodes[i], angle);
                    if (!p) continue;
                    if (p.x < -80 || p.x > W + 80 || p.y < -80 || p.y > H + 80) continue;
                    p.node = nodes[i];
                    projected.push(p);
                }

                // Filaments: only between near neighbours, and only for the
                // closer half of the field, so the line count stays bounded.
                ctx.lineWidth = 0.6;
                for (var a = 0; a < projected.length; a++) {
                    var pa = projected[a];
                    if (pa.scale < 0.55) continue;
                    for (var b2 = a + 1; b2 < projected.length; b2++) {
                        var pb = projected[b2];
                        if (pb.scale < 0.55) continue;
                        var dx = pa.x - pb.x;
                        var dy = pa.y - pb.y;
                        var d2 = dx * dx + dy * dy;
                        if (d2 > 16000) continue;
                        var alpha = (1 - d2 / 16000) * 0.16 * pa.scale;
                        var c = pa.node.colour;
                        ctx.strokeStyle = 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + alpha + ')';
                        ctx.beginPath();
                        ctx.moveTo(pa.x, pa.y);
                        ctx.lineTo(pb.x, pb.y);
                        ctx.stroke();
                    }
                }

                // Nodes, far to near.
                projected.sort(function (m, n) { return m.depth - n.depth; });
                for (var k = 0; k < projected.length; k++) {
                    var pp = projected[k];
                    var nd = pp.node;
                    var tw = 0.55 + 0.45 * Math.sin(t * 0.0013 + nd.twinkle);
                    var size = nd.size * pp.scale * 1.6;
                    var col = nd.colour;
                    var alpha = Math.min(1, tw * pp.scale * 1.1);

                    ctx.beginPath();
                    ctx.arc(pp.x, pp.y, Math.max(0.4, size), 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(' + col[0] + ',' + col[1] + ',' + col[2] + ',' + alpha + ')';
                    ctx.fill();

                    // Bloom on the brightest, nearest nodes only.
                    if (pp.scale > 0.85 && tw > 0.8) {
                        var g2 = ctx.createRadialGradient(pp.x, pp.y, 0, pp.x, pp.y, size * 6);
                        g2.addColorStop(0, 'rgba(' + col[0] + ',' + col[1] + ',' + col[2] + ',' + (alpha * 0.35) + ')');
                        g2.addColorStop(1, 'rgba(' + col[0] + ',' + col[1] + ',' + col[2] + ',0)');
                        ctx.fillStyle = g2;
                        ctx.beginPath();
                        ctx.arc(pp.x, pp.y, size * 6, 0, Math.PI * 2);
                        ctx.fill();
                    }
                }

                rafId = window.requestAnimationFrame(frame);
            }

            function start() {
                if (rafId === null) rafId = window.requestAnimationFrame(frame);
            }

            function stop() {
                if (rafId !== null) {
                    window.cancelAnimationFrame(rafId);
                    rafId = null;
                }
            }

            resize();

            if (reduceMotion) {
                // One settled frame, no loop.
                ctx.clearRect(0, 0, W, H);
                drawNebula(0);
                for (var i = 0; i < nodes.length; i++) {
                    var p = project(nodes[i], 0);
                    if (!p) continue;
                    var c = nodes[i].colour;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, Math.max(0.4, nodes[i].size * p.scale * 1.6), 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + Math.min(1, p.scale) + ')';
                    ctx.fill();
                }
            } else {
                start();
            }

            // Debounced resize — rebuilding the field is not cheap.
            var resizeTimer = null;
            window.addEventListener('resize', function () {
                if (resizeTimer) window.clearTimeout(resizeTimer);
                resizeTimer = window.setTimeout(function () {
                    resize();
                    if (reduceMotion) {
                        ctx.clearRect(0, 0, W, H);
                        drawNebula(0);
                    }
                }, 180);
            });

            if (!reduceMotion) {
                window.addEventListener('pointermove', function (e) {
                    targetX = (e.clientX / W - 0.5) * 2;
                    targetY = (e.clientY / H - 0.5) * 2;
                }, { passive: true });

                // Never burn frames on a tab nobody is looking at.
                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) stop(); else start();
                });
            }

            window.novaWarp = function () { if (!reduceMotion) warp = 0.012; };
        })();

        /* ---------------------------------------------------------------
           2. Orbital star menu
           --------------------------------------------------------------- */
        (function starMenu() {
            var nav = document.getElementById('nova-nav');
            if (!nav) return;

            var items = nav.querySelectorAll('[data-nova-orbit]');
            var closers = nav.querySelectorAll('[data-nova-close]');
            var mini = document.getElementById('nova-star-mini');
            var lastFocus = null;

            function layout() {
                var count = items.length;
                if (!count) return;

                // Stagger drives the bloom in BOTH layouts, so it is applied
                // before deciding whether a ring is even possible.
                for (var s = 0; s < count; s++) {
                    items[s].style.transitionDelay = reduceMotion ? '0s' : (s * 0.035) + 's';
                }

                // Measure a real chip instead of assuming 128px — the width
                // changes with the grid rules and with the user's font size.
                // offsetWidth/Height, NOT getBoundingClientRect: the closed
                // menu holds the chips at scale(0.2) and a client rect would
                // report them five times too small.
                // Drop .is-grid first so the measurement always describes the
                // ring layout; otherwise a previous grid decision feeds back
                // into the next one and the two can oscillate.
                nav.classList.remove('is-grid');
                var chipW = items[0].offsetWidth || 128;
                var chipH = items[0].offsetHeight || 92;
                var edge = 14;
                var closeRoom = 56;

                // Largest radius that keeps every chip fully on screen...
                var maxRx = window.innerWidth / 2 - chipW / 2 - edge;
                var maxRy = window.innerHeight / 2 - chipH / 2 - edge - closeRoom;
                // ...and the smallest that keeps neighbours from overlapping.
                // Arc between adjacent centres = 2 * R * sin(pi / count).
                var minR = (chipW + 10) / (2 * Math.sin(Math.PI / count));

                var radius = Math.min(
                    Math.min(window.innerWidth, window.innerHeight) * 0.38,
                    300,
                    maxRx,
                    maxRy
                );

                // No radius satisfies both constraints (narrow phone, or a
                // landscape phone that is wide enough but far too short) —
                // hand off to the CSS grid layout instead of drawing a ring
                // that clips or overlaps.
                if (radius < minR) {
                    nav.classList.add('is-grid');
                    return;
                }

                for (var i = 0; i < count; i++) {
                    // Start at the top and go clockwise.
                    var a = (Math.PI * 2 * i) / count - Math.PI / 2;
                    items[i].style.setProperty('--nv-tx', (Math.cos(a) * radius).toFixed(1) + 'px');
                    items[i].style.setProperty('--nv-ty', (Math.sin(a) * radius).toFixed(1) + 'px');
                }
            }

            function triggers() {
                return document.querySelectorAll('[data-nova-star]');
            }

            function setExpanded(open) {
                var all = triggers();
                for (var i = 0; i < all.length; i++) {
                    all[i].setAttribute('aria-expanded', open ? 'true' : 'false');
                }
                if (mini) mini.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            function open() {
                lastFocus = document.activeElement;
                layout();
                nav.setAttribute('data-open', 'true');
                setExpanded(true);
                document.body.style.overflow = 'hidden';
                if (window.novaWarp) window.novaWarp();
                if (items.length) {
                    // Let the fly-out start before stealing focus.
                    window.setTimeout(function () { items[0].focus(); }, reduceMotion ? 0 : 180);
                }
            }

            function close() {
                nav.setAttribute('data-open', 'false');
                setExpanded(false);
                document.body.style.overflow = '';
                if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
            }

            function isOpen() {
                return nav.getAttribute('data-open') === 'true';
            }

            document.addEventListener('click', function (e) {
                var trigger = e.target.closest ? e.target.closest('[data-nova-star]') : null;
                if (trigger) {
                    e.preventDefault();
                    if (isOpen()) close(); else open();
                }
            });

            if (mini) {
                mini.addEventListener('click', function () {
                    if (isOpen()) close(); else open();
                });
            }

            for (var c = 0; c < closers.length; c++) {
                closers[c].addEventListener('click', close);
            }

            document.addEventListener('keydown', function (e) {
                if (!isOpen()) return;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    close();
                    return;
                }
                // Keep Tab inside the open overlay.
                if (e.key === 'Tab') {
                    var focusables = nav.querySelectorAll('a[href], button:not([disabled])');
                    if (!focusables.length) return;
                    var first = focusables[0];
                    var last = focusables[focusables.length - 1];
                    if (e.shiftKey && document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    } else if (!e.shiftKey && document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            });

            window.addEventListener('resize', function () { if (isOpen()) layout(); });
            layout();

            // Reveal the corner star once the hero star has scrolled away.
            if (mini) {
                mini.hidden = false;
                var hero = document.getElementById('nova-hero');
                var sync = function () {
                    var past = hero
                        ? hero.getBoundingClientRect().bottom < 80
                        : window.scrollY > 400;
                    mini.classList.toggle('is-shown', past);
                };
                window.addEventListener('scroll', sync, { passive: true });
                sync();
            }
        })();

        /* ---------------------------------------------------------------
           3. Reveal on scroll — with a backstop.
           If IntersectionObserver is missing or never delivers, everything
           is force-revealed. Content must never depend on an observer.
           --------------------------------------------------------------- */
        (function reveal() {
            var els = document.querySelectorAll('.nova-reveal');
            if (!els.length) return;

            function showAll() {
                for (var i = 0; i < els.length; i++) els[i].classList.add('is-in');
            }

            if (reduceMotion || !('IntersectionObserver' in window)) {
                showAll();
                return;
            }

            var seen = false;
            var io = new IntersectionObserver(function (entries) {
                for (var i = 0; i < entries.length; i++) {
                    if (entries[i].isIntersecting) {
                        seen = true;
                        entries[i].target.classList.add('is-in');
                        io.unobserve(entries[i].target);
                    }
                }
            }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });

            for (var i = 0; i < els.length; i++) io.observe(els[i]);

            // Backstop: if nothing has been delivered, stop hiding the page.
            window.setTimeout(function () { if (!seen) showAll(); }, 2500);
        })();

        /* ---------------------------------------------------------------
           4. Count-up stats
           --------------------------------------------------------------- */
        (function counters() {
            var els = document.querySelectorAll('[data-nova-count]');
            if (!els.length) return;

            function run(el) {
                var target = parseInt(el.getAttribute('data-nova-count'), 10);
                if (isNaN(target)) return;
                var suffix = el.getAttribute('data-nova-suffix') || '';
                if (reduceMotion) {
                    el.textContent = target + suffix;
                    return;
                }
                var start = null;
                var dur = 1400;
                function step(ts) {
                    if (start === null) start = ts;
                    var p = Math.min(1, (ts - start) / dur);
                    // easeOutCubic
                    var eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = Math.round(target * eased) + suffix;
                    if (p < 1) window.requestAnimationFrame(step);
                }
                window.requestAnimationFrame(step);
            }

            if (!('IntersectionObserver' in window)) {
                for (var i = 0; i < els.length; i++) run(els[i]);
                return;
            }

            var io = new IntersectionObserver(function (entries) {
                for (var i = 0; i < entries.length; i++) {
                    if (entries[i].isIntersecting) {
                        run(entries[i].target);
                        io.unobserve(entries[i].target);
                    }
                }
            }, { threshold: 0.4 });

            for (var j = 0; j < els.length; j++) io.observe(els[j]);
        })();
    })();
    </script>

    @php
        $customBodyEndCode = \App\Models\Setting::getValue('custom_code_body_end', '');
    @endphp
    @if($customBodyEndCode)
        {!! $customBodyEndCode !!}
    @endif
</body>
</html>
