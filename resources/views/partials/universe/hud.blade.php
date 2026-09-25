{{--
    The heads-up display laid over the flight: brand, sale strip, sound and
    menu buttons, the journey rail (built by main.js from the sections), a
    live coordinate readout, the target reticle drawn around planets, and the
    way back to the classic page.
--}}
<header id="xu-hud" class="xu-hud">
    <a href="{{ url('/') }}" class="xu-brand" data-xu-top aria-label="XMAN Studio — กลับสู่จุดเริ่มต้น / Back to the start">
        <span class="xu-brand__badge" aria-hidden="true">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="" class="xu-brand__logo">
            @else
                <span class="xu-brand__mark">X</span>
            @endif
        </span>
        <span class="xu-brand__word"><b>XMAN</b><small>STUDIO</small></span>
    </a>

    <a href="{{ route('services.index') }}" class="xu-promo">
        <span class="xu-promo__tag" aria-hidden="true">%</span>
        <span class="xu-promo__text">มหกรรมลดราคา <small>Mega Sale</small></span>
        <b class="xu-promo__off">50-70% OFF</b>
        <span class="xu-promo__go">ดูบริการทั้งหมด <span aria-hidden="true">→</span></span>
    </a>

    <div class="xu-hud__actions">
        <button type="button" id="xu-sound" class="xu-sound" aria-pressed="false">
            <span class="xu-sound__bars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            <span class="xu-sound__label"><b data-on="เสียงเปิด" data-off="เสียงปิด">เสียงปิด</b><small data-on="SOUND ON" data-off="SOUND OFF">SOUND OFF</small></span>
        </button>
        <button type="button" id="xu-menu-btn" class="xu-menu-btn" aria-controls="xu-menu" aria-expanded="false" aria-haspopup="dialog">
            <span class="xu-menu-btn__orbit" aria-hidden="true"><i></i><i></i></span>
            <span class="xu-menu-btn__label"><b>เมนู</b><small>MENU</small></span>
        </button>
    </div>
</header>

<nav id="xu-rail" class="xu-rail" aria-label="จุดหมายในจักรวาล / Journey stops"></nav>

<div id="xu-coords" class="xu-coords" aria-hidden="true">
    <span class="xu-coords__sector" id="xu-coords-sector">SECTOR 01</span>
    <span class="xu-coords__xyz" id="xu-coords-xyz">0.0 · 0.0 · 0.0</span>
    <span class="xu-coords__v" id="xu-coords-v">v 0.00c</span>
</div>

<div id="xu-reticle" class="xu-reticle" aria-hidden="true">
    <svg class="xu-reticle__ring" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="48" pathLength="100"/>
        <circle class="xu-reticle__dash" cx="50" cy="50" r="44" pathLength="100"/>
    </svg>
    <span class="xu-reticle__tick xu-reticle__tick--n"></span>
    <span class="xu-reticle__tick xu-reticle__tick--e"></span>
    <span class="xu-reticle__tick xu-reticle__tick--s"></span>
    <span class="xu-reticle__tick xu-reticle__tick--w"></span>
    <span class="xu-reticle__label" id="xu-reticle-label"></span>
</div>

<div id="xu-scrollhint" class="xu-scrollhint" aria-hidden="true">
    <span class="xu-scrollhint__mouse"><i></i></span>
    <span>เลื่อนเพื่อเดินทาง <small>SCROLL TO TRAVEL</small></span>
</div>

<a href="{{ $classicUrl }}" class="xu-classic" data-xu-classic>โหมดปกติ <small>Classic view</small></a>

<div id="xu-flash" class="xu-flash" aria-hidden="true"></div>
