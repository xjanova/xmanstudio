{{--
    Boot screen: covers the page while three.js loads and the shaders compile,
    then becomes the gate. Entering is a click (or a key), which is also the
    user gesture browsers require before a page may play sound. A returning
    visitor skips the gate (main.js remembers the first choice). Scrolling on
    the gate enters without sound.
--}}
<div id="xu-loader" class="xu-loader" role="dialog" aria-modal="true" aria-labelledby="xu-loader-title" aria-describedby="xu-loader-sub">
    <div class="xu-loader__stars" aria-hidden="true"></div>

    {{-- The guide says hello (partials/universe/guide.blade.php is her in-flight self). --}}
    <figure class="xu-loader__guide" aria-hidden="true">
        <img src="{{ asset('artwork/universe/guide/welcome.webp') }}" alt="" decoding="async" draggable="false" onerror="this.closest('figure').remove()">
        <figcaption class="xu-loader__say">
            <b>สวัสดีค่ะ! พร้อมบินเข้าสู่จักรวาล XMAN ไปกับหนูไหม?</b>
            <small>Hi! Ready to fly into the XMAN universe with me?</small>
        </figcaption>
    </figure>

    @if($logoUrl)
        {{-- The logo itself, big and glinting; it is what grows into the portal on the way in. --}}
        <div class="xu-loader__emblem xu-loader__emblem--logo" aria-hidden="true">
            <span class="xu-loader__halo"></span>
            @include('partials.universe.logo', ['src' => $logoUrl, 'class' => 'xu-logo--gate', 'alt' => ''])
        </div>
        <p id="xu-loader-title" class="xu-loader__title"><span class="xu-sr">XMAN </span><span>UNIVERSE</span></p>
    @else
        <div class="xu-loader__emblem" aria-hidden="true">
            <span class="xu-loader__orbit xu-loader__orbit--a"></span>
            <span class="xu-loader__orbit xu-loader__orbit--b"></span>
            <span class="xu-loader__halo"></span>
            <span class="xu-loader__core"><span class="xu-loader__mark">X</span></span>
        </div>
        <p id="xu-loader-title" class="xu-loader__title">XMAN <span>UNIVERSE</span></p>
    @endif
    <p id="xu-loader-sub" class="xu-loader__sub">กำลังเปิดประตูสู่จักรวาลของ XMAN Studio<small>Opening the gate to the XMAN Studio universe</small></p>

    <div class="xu-loader__boot" id="xu-loader-boot">
        <div class="xu-loader__meter" aria-hidden="true"><span id="xu-loader-bar"></span></div>
        <div class="xu-loader__readout">
            <span id="xu-loader-step">กำลังเชื่อมต่อ / Linking</span>
            <span id="xu-loader-pct" class="xu-loader__pct">0%</span>
        </div>
        <ol class="xu-loader__log" id="xu-loader-log" aria-hidden="true"></ol>
    </div>

    <div class="xu-loader__gate" id="xu-loader-gate" hidden>
        <button type="button" class="xu-enter" data-xu-enter="sound">
            <span class="xu-enter__glow" aria-hidden="true"></span>
            <span class="xu-enter__label">เข้าสู่จักรวาล</span>
            <span class="xu-enter__sub">ENTER · เปิดเสียง / sound on</span>
        </button>
        <button type="button" class="xu-loader__quiet" data-xu-enter="mute">เข้าแบบไม่มีเสียง / Enter without sound</button>
        <p class="xu-loader__tip">
            <span aria-hidden="true">🎧</span> ใส่หูฟังจะได้อรรถรสที่สุด · เลื่อนเมาส์เพื่อเดินทาง
            <small>Best with headphones · scroll to travel</small>
        </p>
    </div>

    <a href="{{ $classicUrl }}" class="xu-loader__classic" data-xu-classic>ใช้หน้าเว็บแบบปกติ / Classic view</a>
</div>
