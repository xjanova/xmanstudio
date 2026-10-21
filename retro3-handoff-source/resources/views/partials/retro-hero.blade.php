<div class="tron-scanlines" style="position:relative;min-height:640px;overflow:hidden;background:radial-gradient(ellipse at 50% 20%, #0a1628 0%, #030711 60%, #000 100%);">
    {{-- Parallax grid floor --}}
    <div id="retro-grid-wrap" style="position:absolute;inset:0;">
        <div class="tron-grid-perspective" style="height:60%;bottom:0;top:auto;"></div>
    </div>

    {{-- Horizon line --}}
    <div id="retro-horizon" style="position:absolute;left:0;right:0;top:55%;height:2px;background:linear-gradient(90deg,transparent,#00e5ff,transparent);box-shadow:0 0 20px #00e5ff, 0 0 40px rgba(0,229,255,.5);"></div>

    {{-- Vinyl disc --}}
    <div id="retro-vinyl" style="position:absolute;right:-120px;top:60px;width:360px;height:360px;opacity:.85;">
        <div class="tron-spin-slow" style="width:100%;height:100%;border-radius:50%;background:radial-gradient(circle,#030711 30%,#0a1628 32%,#030711 34%,#0a1628 36%,#030711 38%,#0a1628 40%,#030711 42%,#1a2842 50%,#030711 55%,#0a1628 60%,#030711 65%);border:1px solid rgba(0,229,255,.3);box-shadow:var(--glow-cyan);position:relative;">
            <div style="position:absolute;top:50%;left:50%;width:80px;height:80px;margin-top:-40px;margin-left:-40px;border-radius:50%;background:radial-gradient(circle,#d4af37,#8a7020);box-shadow:var(--glow-gold);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);color:#030711;font-weight:700;font-size:12px;letter-spacing:.1em;">X · MMXVIII</div>
        </div>
    </div>

    {{-- Sun glow --}}
    <div id="retro-sun" style="position:absolute;left:50%;top:48%;width:520px;height:520px;margin-left:-260px;margin-top:-260px;border-radius:50%;background:radial-gradient(circle, rgba(212,175,55,.22) 0%, rgba(0,229,255,.12) 40%, transparent 70%);"></div>

    {{-- Content --}}
    <div style="position:relative;z-index:3;max-width:1100px;margin:0 auto;padding:120px 32px 80px;text-align:center;">
        <div style="display:inline-flex;align-items:center;gap:12px;padding:8px 20px;border:1px solid rgba(212,175,55,.4);background:rgba(212,175,55,.05);border-radius:2px;font-family:var(--font-ui);font-size:10px;letter-spacing:.3em;color:var(--tron-gold);text-transform:uppercase;margin-bottom:32px;">
            <span class="tron-pulse" style="width:6px;height:6px;background:var(--tron-gold);border-radius:50%;box-shadow:var(--glow-gold);"></span>
            Broadcasting Since MMXVIII · Grid Online
        </div>
        <h1 class="tron-flicker" style="margin:0;font-family:var(--font-display);font-size:clamp(56px,11vw,160px);line-height:.95;color:var(--fg-1);letter-spacing:-.01em;">XMAN</h1>
        <h1 class="tron-gold-foil" style="margin:-12px 0 0;font-family:var(--font-display);font-size:clamp(56px,11vw,160px);line-height:.95;letter-spacing:-.01em;">STUDIO</h1>

        <div style="display:flex;align-items:center;justify-content:center;gap:16px;margin:36px auto 24px;max-width:540px;color:var(--fg-3);">
            <span style="flex:1;height:1px;background:linear-gradient(90deg,transparent,var(--tron-cyan),transparent);"></span>
            <span style="font-family:var(--font-ui);font-size:10px;letter-spacing:.4em;text-transform:uppercase;color:var(--tron-cyan);">Digital Craft Division</span>
            <span style="flex:1;height:1px;background:linear-gradient(90deg,transparent,var(--tron-cyan),transparent);"></span>
        </div>

        <p style="font-family:var(--font-serif);font-size:22px;color:var(--fg-2);line-height:1.5;max-width:640px;margin:0 auto;font-style:italic;">
            สร้างสรรค์นวัตกรรมดิจิทัลด้วยทีมงานมืออาชีพ<br>
            <span style="font-style:normal;font-family:var(--font-ui);font-size:12px;letter-spacing:.25em;color:var(--tron-cyan-soft);display:inline-block;margin-top:12px;">BLOCKCHAIN · AI · MOBILE · IOT</span>
        </p>

        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:40px;">
            <a href="{{ url('/support') }}" class="tron-btn tron-btn-gold" style="text-decoration:none;">Initiate Project →</a>
            <a href="{{ url('/portfolio') }}" class="tron-btn" style="text-decoration:none;">View Archives</a>
        </div>

        {{-- Stats rail --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;margin-top:64px;padding:24px 0;border-top:1px solid rgba(0,229,255,.2);border-bottom:1px solid rgba(0,229,255,.2);">
            @foreach([['150+','Projects Shipped'],['50+','Clients Served'],['VIII','Years Online'],['24/7','Uplink']] as $i => $s)
                <div style="{{ $i ? 'border-left:1px solid rgba(0,229,255,.2);' : '' }}padding:0 16px;">
                    <div class="tron-gold-foil" style="font-family:var(--font-display);font-size:36px;">{{ $s[0] }}</div>
                    <div style="font-family:var(--font-ui);font-size:10px;letter-spacing:.25em;color:var(--fg-3);text-transform:uppercase;margin-top:4px;">{{ $s[1] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    (function(){
        var gridWrap = document.getElementById('retro-grid-wrap');
        var horizon  = document.getElementById('retro-horizon');
        var vinyl    = document.getElementById('retro-vinyl');
        var sun      = document.getElementById('retro-sun');
        if (!gridWrap) return;
        window.addEventListener('scroll', function(){
            var y = window.scrollY;
            gridWrap.style.transform = 'translateY(' + (y*0.3) + 'px)';
            horizon.style.transform  = 'translateY(' + (y*0.15) + 'px)';
            vinyl.style.transform    = 'translateY(' + (y*-0.2) + 'px)';
            sun.style.transform      = 'translateY(' + (y*-0.1) + 'px)';
        }, {passive:true});
    })();
</script>
