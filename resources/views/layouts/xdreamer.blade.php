<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO --}}
    <x-seo-meta
        :title="View::yieldContent('title', 'X-DREAMER · ทอความฝันจากเส้นใยแห่งความคิด')"
        :description="View::yieldContent('meta_description', 'แพลตฟอร์ม AI generate สำหรับศิลปินและนักฝัน — สร้างภาพ วิดีโอ เสียง และฉาก 3D จากประโยคเดียว')"
        :image="View::yieldContent('og_image', '')"
    />

    {{-- Favicon --}}
    @php $siteFavicon = \App\Models\Setting::getValue('site_favicon'); @endphp
    @if($siteFavicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('images/xdreamer/logo.png') }}">
    @endif

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@200;300;400;500;600;700&family=Inter:wght@200;300;400;500;600;700;900&display=swap" rel="stylesheet">

    {{-- Custom head --}}
    @php $customHeadCode = \App\Models\Setting::getValue('custom_code_head', ''); @endphp
    @if($customHeadCode){!! $customHeadCode !!}@endif

    <style>
        html, body {
            margin:0;padding:0;background:#030612;color:#f1f5f9;
            font-family:'Noto Sans Thai','Inter',system-ui,sans-serif;
            -webkit-font-smoothing:antialiased;
        }
        body { overflow-x:hidden; }
        * { box-sizing:border-box; }

        @keyframes pulse { 0%,100% {opacity:1;} 50% {opacity:0.5;} }
        @keyframes blink { 0%,50% {opacity:1;} 50.01%,100% {opacity:0;} }
        @keyframes spin { to {transform:rotate(360deg);} }
        @keyframes floatY { 0%,100% {transform:translateY(0);} 50% {transform:translateY(-12px);} }
        @keyframes pageIn { from {opacity:0;transform:translateY(8px);} to {opacity:1;transform:translateY(0);} }
        @keyframes bannerIn { from {opacity:0;transform:translateX(20px);} to {opacity:1;transform:translateX(0);} }

        button { transition:all 250ms cubic-bezier(0.4,0,0.2,1);font-family:inherit; }
        button:hover { filter:brightness(1.1); }
        button:hover:not(:disabled) { transform:translateY(-1px); }
        a { transition:color 200ms; }
        a:hover { color:#fff !important; }
        ::selection { background:rgba(139,92,246,0.4); }
        ::-webkit-scrollbar { width:8px;height:8px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.08);border-radius:4px; }
        ::-webkit-scrollbar-thumb:hover { background:rgba(255,255,255,0.16); }

        /* Thai italic clipping fix */
        h1 span[style*="italic"], h2 span[style*="italic"],
        .xdr-italic-th { padding-bottom:0.15em;padding-right:0.08em;display:inline-block; }

        /* User menu items */
        .xdr-menu-item {
            display:flex;align-items:center;gap:10px;
            padding:8px 12px;border-radius:8px;
            font-size:13px;color:#e2e8f0;text-decoration:none;
            background:transparent;border:none;cursor:pointer;
            transition:background 150ms;
        }
        .xdr-menu-item:hover { background:rgba(255,255,255,0.05);color:#fff; }

        /* Noise overlay */
        .noise {
            position:fixed;inset:0;pointer-events:none;z-index:100;
            opacity:0.03;mix-blend-mode:overlay;
            background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='200' height='200'><filter id='n'><feTurbulence baseFrequency='0.9' numOctaves='3' seed='2'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>");
        }

        .page-enter { animation:pageIn 400ms cubic-bezier(0.4,0,0.2,1); }

        /* RESPONSIVE */
        @media (max-width:1024px) {
            .rp-nav { padding:14px 20px !important; }
            .rp-nav-links { gap:16px !important;font-size:12px !important; }
            .rp-nav-badge { display:none !important; }
            .rp-nav-brand { font-size:12px !important;letter-spacing:0.18em !important; }
            .rp-nav-cta-primary { padding:8px 14px !important;font-size:12px !important;white-space:nowrap !important; }
            .rp-nav-cta-ghost { padding:7px 12px !important;font-size:12px !important;white-space:nowrap !important; }
            .rp-hero-logo-wrap { position:static !important;flex-direction:row !important;justify-content:flex-end !important;margin-bottom:24px !important; }
            .rp-hero-logo { width:80px !important;height:80px !important;border-radius:18px !important; }
            .rp-container { padding:0 24px !important; }
            .rp-studio { grid-template-columns:280px 1fr !important; }
            .rp-studio-right { display:none !important; }
            .rp-studio-center { min-width:0 !important;width:100% !important;height:auto !important; }
            .rp-docs { grid-template-columns:220px 1fr !important; }
            .rp-docs-right { display:none !important; }
            .rp-docs-center { min-width:0 !important; }
            .rp-grid-4 { grid-template-columns:repeat(2,1fr) !important; }
            .rp-grid-3 { grid-template-columns:repeat(2,1fr) !important; }
            .rp-gallery-mason { column-count:3 !important; }
            .rp-dash-main { grid-template-columns:1fr !important; }
            .rp-stat-4 { grid-template-columns:repeat(2,1fr) !important; }
        }
        @media (max-width:720px) {
            .rp-nav { padding:14px 18px !important; }
            .rp-nav-links { display:none !important; }
            .rp-hero-logo-wrap { position:static !important;margin:0 auto 24px !important; }
            .rp-hero-logo { width:100px !important;height:100px !important; }
            .rp-container { padding:0 18px !important; }
            .rp-studio { grid-template-columns:1fr !important; }
            .rp-studio-left { height:auto !important;border-right:none !important;border-bottom:1px solid rgba(255,255,255,0.06) !important; }
            .rp-studio-center { height:auto !important; }
            .rp-docs { grid-template-columns:1fr !important; }
            .rp-docs-left { display:none !important; }
            .rp-grid-4 { grid-template-columns:1fr !important; }
            .rp-grid-3 { grid-template-columns:1fr !important; }
            .rp-grid-2 { grid-template-columns:1fr !important; }
            .rp-gallery-mason { column-count:2 !important; }
            .rp-stat-4 { grid-template-columns:repeat(2,1fr) !important;gap:12px !important; }
            .rp-prompt-row { flex-wrap:wrap !important; }
            .rp-gen-tray { grid-template-columns:repeat(2,1fr) !important; }
            .rp-hero-h1 { font-size:48px !important; }
            .rp-h2 { font-size:36px !important; }
            .rp-history { grid-template-columns:repeat(4,1fr) !important; }
            .rp-mode-tabs { flex-wrap:wrap !important; }
            .rp-filter-row { flex-direction:column !important;align-items:flex-start !important; }
            .rp-section { padding-left:18px !important;padding-right:18px !important;padding-top:64px !important;padding-bottom:64px !important; }
            .rp-banner { min-height:560px;border-radius:20px !important; }
            .rp-banner-content { padding:32px 22px !important;gap:20px !important;grid-template-columns:1fr !important; }
            .rp-banner-stats { padding:18px !important; }
        }
        @media (max-width:900px) {
            .rp-banner { height:auto !important;min-height:520px; }
            .rp-banner-content { grid-template-columns:1fr !important;padding:40px 28px !important; }
        }
    </style>

    @stack('styles')
</head>
<body>

    {{-- Global fixed background canvas --}}
    <div id="xdr-bg" style="position:fixed;inset:0;z-index:0;pointer-events:none;opacity:0.45;transition:opacity 120ms linear;">
        @include('xdreamer.partials.fiber-threads', [
            'id' => 'xdr-bg-canvas',
            'density' => 70,
            'speed' => 1,
            'hueShift' => 70,
            'opacity' => 1,
            'interactive' => true,
        ])
    </div>

    {{-- Frosted gradient overlay --}}
    <div id="xdr-overlay" style="
        position:fixed;inset:0;z-index:0;pointer-events:none;
        background:radial-gradient(ellipse at 50% 30%, rgba(3,6,18,0.35) 0%, rgba(3,6,18,0.7) 55%, rgba(3,6,18,0.92) 100%);
    "></div>

    <div style="position:relative;z-index:1;">
        {{-- Nav --}}
        @include('xdreamer.partials.nav', ['current' => $page ?? ''])

        {{-- Page content --}}
        <div class="page-enter">
            @yield('content')
        </div>
    </div>

    {{-- Noise overlay --}}
    <div class="noise"></div>

    {{-- Custom body --}}
    @php $customBodyEnd = \App\Models\Setting::getValue('custom_code_body_end', ''); @endphp
    @if($customBodyEnd){!! $customBodyEnd !!}@endif

    {{-- Alpine.js --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Fiber threads canvas (vanilla JS, capped at 30fps) --}}
    <script>
    (function(){
        const canvases = document.querySelectorAll('canvas[data-fiber-threads]');
        canvases.forEach(initCanvas);

        function initCanvas(canvas){
            const ctx = canvas.getContext('2d', { alpha:true });
            if (!ctx) return;
            const density = parseInt(canvas.dataset.density || 70, 10);
            const speed = parseFloat(canvas.dataset.speed || 1);
            const hueShift = parseFloat(canvas.dataset.hueShift || 70);
            const baseOpacity = parseFloat(canvas.dataset.opacity || 0.55);
            const interactive = canvas.dataset.interactive === '1';

            const palettes = [
                [160,85,55],[180,80,60],[210,90,65],[250,75,68],[275,70,65],[295,65,68]
            ].map(p => [(p[0] + hueShift) % 360, p[1], p[2]]);

            let w=0, h=0, dpr = Math.min(window.devicePixelRatio || 1, 1.5);
            let threads = [];
            const mouse = { x:-9999, y:-9999, active:false };
            let visible = true;
            let raf, t0 = performance.now(), lastFrame = 0;
            const frameMs = 1000 / 30;

            function resize(){
                const rect = canvas.getBoundingClientRect();
                w = rect.width; h = rect.height;
                if (w === 0 || h === 0) return;
                canvas.width = w * dpr; canvas.height = h * dpr;
                ctx.setTransform(dpr,0,0,dpr,0,0);
                spawn();
            }
            function spawn(){
                threads = [];
                for (let i = 0; i < density; i++){
                    const p = palettes[Math.floor(Math.random() * palettes.length)];
                    threads.push({
                        x0: Math.random()*w, y0: Math.random()*h,
                        cx1: Math.random()*w, cy1: Math.random()*h,
                        cx2: Math.random()*w, cy2: Math.random()*h,
                        x1: Math.random()*w, y1: Math.random()*h,
                        f1: 0.0004 + Math.random()*0.0008,
                        f2: 0.0003 + Math.random()*0.0007,
                        f3: 0.0002 + Math.random()*0.0006,
                        phase: Math.random()*Math.PI*2,
                        thick: 0.3 + Math.random()*1.4,
                        hue: p[0], sat: p[1], lit: p[2],
                        alpha: 0.25 + Math.random()*0.55,
                    });
                }
            }
            function onMove(e){
                const rect = canvas.getBoundingClientRect();
                mouse.x = e.clientX - rect.left;
                mouse.y = e.clientY - rect.top;
                mouse.active = true;
            }
            function onLeave(){ mouse.active = false; }

            resize();
            window.addEventListener('resize', resize);
            if (interactive){
                window.addEventListener('mousemove', onMove, { passive:true });
                window.addEventListener('mouseleave', onLeave);
            }
            const io = new IntersectionObserver(es => { visible = es[0].isIntersecting; }, { threshold:0 });
            io.observe(canvas);

            function draw(now){
                raf = requestAnimationFrame(draw);
                if (!visible) return;
                if (now - lastFrame < frameMs) return;
                lastFrame = now;
                const t = (now - t0) * speed;
                ctx.globalCompositeOperation = 'source-over';
                ctx.fillStyle = 'rgba(3,6,18,0.08)';
                ctx.fillRect(0,0,w,h);
                ctx.globalCompositeOperation = 'lighter';

                for (const th of threads){
                    const a = Math.sin(t * th.f1 + th.phase);
                    const b = Math.cos(t * th.f2 + th.phase * 1.3);
                    const c = Math.sin(t * th.f3 + th.phase * 0.7);
                    const d = Math.cos(t * th.f1 + th.phase * 2.1);

                    let x0 = th.x0 + a*120, y0 = th.y0 + b*120;
                    let cx1 = th.cx1 + c*200, cy1 = th.cy1 + d*200;
                    let cx2 = th.cx2 + b*200, cy2 = th.cy2 + a*200;
                    let x1 = th.x1 + d*120, y1 = th.y1 + c*120;

                    if (mouse.active && interactive){
                        const dx = mouse.x - (cx1 + cx2)/2;
                        const dy = mouse.y - (cy1 + cy2)/2;
                        const dist = Math.sqrt(dx*dx + dy*dy) + 1;
                        const pull = Math.max(0, 180 - dist) / 180;
                        cx1 += dx * pull * 0.35; cy1 += dy * pull * 0.35;
                        cx2 += dx * pull * 0.25; cy2 += dy * pull * 0.25;
                    }

                    const g = ctx.createLinearGradient(x0, y0, x1, y1);
                    const h1 = th.hue, h2 = (th.hue + 40) % 360;
                    g.addColorStop(0, `hsla(${h1},${th.sat}%,${th.lit}%,0)`);
                    g.addColorStop(0.15, `hsla(${h1},${th.sat}%,${th.lit}%,${th.alpha * baseOpacity})`);
                    g.addColorStop(0.5, `hsla(${(h1+20)%360},${th.sat}%,${th.lit+5}%,${th.alpha * baseOpacity * 1.1})`);
                    g.addColorStop(0.85, `hsla(${h2},${th.sat}%,${th.lit}%,${th.alpha * baseOpacity})`);
                    g.addColorStop(1, `hsla(${h2},${th.sat}%,${th.lit}%,0)`);

                    ctx.strokeStyle = g;
                    ctx.lineWidth = th.thick;
                    ctx.beginPath();
                    ctx.moveTo(x0, y0);
                    ctx.bezierCurveTo(cx1, cy1, cx2, cy2, x1, y1);
                    ctx.stroke();
                }
            }
            raf = requestAnimationFrame(draw);
        }

        // Fade hero canvas as user scrolls past 600px
        const bg = document.getElementById('xdr-bg');
        if (bg){
            const baseO = 0.28;
            const heroBoost = 0.55;
            const isHome = document.body.dataset.xdrPage === 'home';
            if (!isHome){
                bg.style.opacity = baseO;
                return;
            }
            let raf2;
            const onScroll = () => {
                if (raf2) return;
                raf2 = requestAnimationFrame(() => {
                    const heroAmount = Math.max(0, 1 - window.scrollY / 600);
                    bg.style.opacity = (baseO + heroAmount * heroBoost);
                    raf2 = null;
                });
            };
            window.addEventListener('scroll', onScroll, { passive:true });
            onScroll();
        }
    })();
    </script>

    @stack('scripts')
</body>
</html>
