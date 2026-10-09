@php($hub = rtrim(config('game-support.hub_origin'), '/'))
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', 'ร่วมสร้างเกม') · XMAN GAMES HUB Community</title>
<meta name="description" content="สนับสนุนเกมของ XMAN Studio รับไอเท็มผู้สนับสนุน ดูยอดที่ตรวจสอบแล้ว รีวิว แสดงความคิดเห็น โหวต และให้ดาว">
<meta name="theme-color" content="#07080f">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="{{ asset('css/game-support.css') }}?v=4">
</head>
<body class="gs-public">
<div class="gs-nebula" aria-hidden="true"></div>
{{-- the same frame as xgameshub.xman4289.com: sidebar + top bar, links lead back into the hub --}}
<aside class="gs-side" id="gs-menu" data-open="0">
    <a class="gs-brand" href="{{ $hub }}/" aria-label="XMAN GAMES HUB หน้าแรก"><img src="{{ asset('images/gameshub/logo-v2.webp') }}" alt="XMAN GAMES HUB" width="760" height="314"></a>
    <div class="gs-caption">YOUR GATEWAY TO PLAY</div>
    <nav class="gs-nav" aria-label="เมนูหลัก">
        <a href="{{ $hub }}/"><i>◇</i>Discover</a>
        <a href="{{ $hub }}/?f=full#games"><i>★</i>Full games</a>
        <a href="{{ $hub }}/?f=play#games"><i>▷</i>Play now</a>
        <a href="{{ $hub }}/?f=dev#games"><i>▧</i>In development</a>
        <a href="{{ $hub }}/?f=concept#games"><i>✳</i>Concept lab</a>
        <a href="{{ $hub }}/?f=roblox#games"><i>⬢</i>Roblox</a>
        <span class="gs-group">ร่วมสร้างเกม</span>
        <a href="{{ $hub }}/fund/hive-breach/"><i>♦</i>HIVE // BREACH: COREWAR</a>
        <a href="{{ $hub }}/fund/breaker/"><i>♦</i>X-NOVA: BREAKER</a>
        <a href="{{ route('game-support.index') }}" @class(['on' => request()->routeIs('game-support.index', 'game-support.show')])><i>♥</i>Community</a>
        <a href="{{ route('game-support.my-items') }}" @class(['on' => request()->routeIs('game-support.my-items')])><i>✦</i>ไอเท็มของฉัน</a>
        <a href="{{ $hub }}/#redeem"><i>⌁</i>แลกโค้ดในฮับ</a>
        <a href="{{ $hub }}/#devlog"><i>✎</i>Dev log</a>
        <a href="{{ $hub }}/#studio"><i>⌘</i>Meet the studio</a>
    </nav>
    <div class="gs-note"><span>XMAN ORIGINALS</span><p>Small studio.<br><strong>Infinite worlds.</strong></p></div>
</aside>
<div class="gs-shell">
    <header class="gs-top">
        <button type="button" class="gs-menu" aria-controls="gs-menu" aria-expanded="false" data-menu><span></span><span></span><span></span><b class="gs-sr">เมนู</b></button>
        <a class="gs-mobile-brand" href="{{ $hub }}/" aria-label="XMAN GAMES HUB"><img src="{{ asset('images/gameshub/logo-v2.webp') }}" alt="XMAN GAMES HUB" width="760" height="314"></a>
        <div class="gs-crumb">XMAN UNIVERSE <span>/</span> COMMUNITY @hasSection('crumb')<span>/</span> <b>@yield('crumb')</b>@endif</div>
        @auth
            <a class="gs-pill" href="{{ route('game-support.my-items') }}"><i>✦</i><span>{{ auth()->user()->name }}</span></a>
        @else
            <a class="gs-pill" href="{{ route('login') }}"><i>→</i><span>เข้าสู่ระบบ XMAN ID</span></a>
        @endauth
        <a class="gs-pill" href="{{ $hub }}/"><i>←</i><span>หน้าหลัก</span></a>
    </header>
    <main class="gs-wrap">
        @if(session('success'))<p class="gs-success" role="status">{{ session('success') }}</p>@endif
        @if($errors->any())<div class="gs-error" role="alert"><strong>กรุณาตรวจข้อมูลอีกครั้ง</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
    <footer class="gs-foot"><span>© 2026 XMAN STUDIO</span><span>การสนับสนุนการพัฒนาโดยสมัครใจ · ยอดนับหลังตรวจเงินเข้า · สลิปไม่แสดงต่อสาธารณะ</span></footer>
</div>
<script src="{{ asset('js/game-support.js') }}?v=2" defer></script>
</body>
</html>
