{{--
    XMAN UNIVERSE — the full-3D home page.

    A scroll-driven flight through the XMAN universe, drawn with WebGL
    (resources/js/universe). Each <section class="xu-st"> below is one stop on
    the way: it sets how far the visitor scrolls there (data-len, in screen
    heights) and holds the content shown on arrival. The sections stay in the
    DOM in reading order and every link in them is a real <a>, so the page is
    readable as plain HTML too — that is the no-JavaScript layout, and what
    the flat CSS in resources/css/universe.css draws before html.xu-live.

    Who gets this page and who gets the theme's own home page is decided in
    App\Support\UniverseHome (server) and partials/universe/detect (browser).
    Content lists come from App\Support\HomeContent, shared with Nova's home.

    Receives from HomeController: $featuredProducts, $featuredReviews.
--}}
@php
    $xuLogo = \App\Models\Setting::getValue('site_logo');
    $xuLogoUrl = $xuLogo ? asset('storage/' . $xuLogo) : null;
    $xuClassicUrl = \App\Support\UniverseHome::classicUrl();
    $xuHasProducts = isset($featuredProducts) && $featuredProducts->isNotEmpty();
    $xuHasReviews = isset($featuredReviews) && $featuredReviews->isNotEmpty();
@endphp
<!DOCTYPE html>
<html lang="th" class="xu-doc">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    @include('partials.universe.detect', ['classicUrl' => $xuClassicUrl])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#03040b">
    <meta name="color-scheme" content="dark">

    <x-seo-meta
        title="XMAN Studio - IT Solutions & Software Development ครบวงจร"
        description="XMAN Studio ผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร — Blockchain, AI, Web & Mobile, IoT, Network Security และซอฟต์แวร์เฉพาะทาง"
    />

    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Not render-blocking: the loader paints with system fonts and main.js waits for these before the flight starts. --}}
    <link rel="stylesheet" media="print" onload="this.media='all'"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Kanit:wght@300;400;500;600&family=Noto+Sans+Thai:wght@400;500;600;700&family=Unbounded:wght@500;700;800&display=swap">

    @vite(['resources/css/universe.css', 'resources/js/universe/main.js'])

    @php
        $customHeadCode = \App\Models\Setting::getValue('custom_code_head', '');
        $adsenseClient = (string) \App\Models\Setting::getValue('adsense_client_id', '');
    @endphp
    @if($customHeadCode)
        {!! $customHeadCode !!}
    @endif
    {{-- Site verification only. Auto ads would drop ad units into a full-screen WebGL scene;
         the classic home page (?view=classic) keeps the full AdSense loader. --}}
    @if(\App\Models\Setting::getValue('adsense_enabled', false) && preg_match('/^ca-pub-\d{10,20}$/', $adsenseClient) === 1)
        <meta name="google-adsense-account" content="{{ $adsenseClient }}">
    @endif
</head>
<body class="xu-body">
    @php
        $customBodyStartCode = \App\Models\Setting::getValue('custom_code_body_start', '');
    @endphp
    @if($customBodyStartCode)
        {!! $customBodyStartCode !!}
    @endif

    <a href="{{ $xuClassicUrl }}" class="xu-skip" data-xu-classic>ข้ามไปหน้าเว็บแบบปกติ (อ่านง่ายกว่า) / Skip to the classic page</a>

    {{-- WebGL mounts here. Decorative: everything it shows is also said in the sections below. --}}
    <div id="xu-canvas" class="xu-canvas" aria-hidden="true"></div>

    @include('partials.universe.loader', ['logoUrl' => $xuLogoUrl, 'classicUrl' => $xuClassicUrl])
    @include('partials.universe.hud', ['logoUrl' => $xuLogoUrl, 'classicUrl' => $xuClassicUrl])
    @include('partials.universe.menu', ['classicUrl' => $xuClassicUrl])
    @include('partials.universe.guide')

    <main id="xu-main" class="xu-main">
        @include('partials.universe.st-core')
        @include('partials.universe.st-origin')
        @include('partials.universe.st-services')
        @if($xuHasProducts)
            @include('partials.universe.st-products')
        @endif
        @include('partials.universe.st-platforms')
        @include('partials.universe.st-stack')
        @if($xuHasReviews)
            @include('partials.universe.st-reviews')
        @endif
        @include('partials.universe.st-launch')
    </main>

    @include('partials.universe.st-control', ['classicUrl' => $xuClassicUrl])

    <div id="xu-toast" class="xu-toast" role="status" aria-live="polite"></div>

    <script type="application/json" id="xu-config">@json([
        'classicUrl' => $xuClassicUrl,
        'home' => url('/'),
    ])</script>

    @php
        $customBodyEndCode = \App\Models\Setting::getValue('custom_code_body_end', '');
    @endphp
    @if($customBodyEndCode)
        {!! $customBodyEndCode !!}
    @endif
</body>
</html>
