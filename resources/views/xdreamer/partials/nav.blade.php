{{-- X-DREAMER navigation bar (fixed, blurred) --}}
@php
    $brand = $brand ?? 'X-DREAMER';
    $current = $current ?? '';
    $links = [
        ['id' => 'studio', 'label' => 'สตูดิโอ', 'route' => 'xdreamer.studio'],
        ['id' => 'gallery', 'label' => 'Gallery', 'route' => 'xdreamer.gallery'],
        ['id' => 'dashboard', 'label' => 'Dashboard', 'route' => 'xdreamer.dashboard'],
        ['id' => 'docs', 'label' => 'Docs', 'route' => 'xdreamer.docs'],
        ['id' => 'about', 'label' => 'About', 'route' => 'xdreamer.about'],
    ];
@endphp
<nav class="rp-nav" style="
    position:fixed;top:0;left:0;right:0;z-index:50;
    padding:20px 48px;
    display:flex;align-items:center;justify-content:space-between;
    backdrop-filter:blur(18px) saturate(1.3);
    -webkit-backdrop-filter:blur(18px) saturate(1.3);
    background:linear-gradient(180deg, rgba(3,6,18,0.65), rgba(3,6,18,0.25));
    border-bottom:1px solid rgba(255,255,255,0.06);
">
    <a href="{{ route('xdreamer.home') }}" class="rp-nav-brand-wrap" style="display:flex;align-items:center;gap:10px;text-decoration:none;cursor:pointer;">
        <img src="{{ asset('images/xdreamer/logo.png') }}" alt="{{ $brand }}" style="
            width:38px;height:38px;border-radius:10px;object-fit:cover;
            box-shadow:0 0 20px rgba(139,92,246,0.45);
        ">
        <div class="rp-nav-brand" style="font-family:Inter,sans-serif;font-weight:900;letter-spacing:0.22em;font-size:14px;color:#fff;">
            {{ $brand }}
        </div>
        <div class="rp-nav-badge" style="font-size:10px;letter-spacing:0.2em;color:#94a3b8;padding:3px 8px;border:1px solid rgba(255,255,255,0.1);border-radius:999px;margin-left:6px;">
            v4 · LIVE
        </div>
    </a>

    <div class="rp-nav-links" style="display:flex;gap:28px;font-size:14px;color:rgba(255,255,255,0.75);font-weight:500;">
        @foreach($links as $l)
            <a href="{{ route($l['route']) }}" style="
                color:{{ $current === $l['id'] ? '#fff' : 'inherit' }};
                text-decoration:none;cursor:pointer;position:relative;padding-bottom:2px;
                border-bottom:{{ $current === $l['id'] ? '1px solid rgba(165,243,252,0.6)' : '1px solid transparent' }};
            ">{{ $l['label'] }}</a>
        @endforeach
    </div>

    <div style="display:flex;align-items:center;gap:12px;">
        @auth
            <div x-data="{ open: false }" @click.outside="open = false" style="position:relative;">
                <button @click="open = !open" style="
                    display:flex;align-items:center;gap:10px;padding:6px 14px 6px 6px;
                    border-radius:999px;background:rgba(255,255,255,0.06);
                    border:1px solid rgba(255,255,255,0.1);color:#fff;cursor:pointer;
                ">
                    <div style="
                        width:28px;height:28px;border-radius:50%;
                        background:conic-gradient(from 180deg, #10b981, #06b6d4, #8b5cf6, #10b981);
                        display:grid;place-items:center;
                        font-size:13px;font-weight:700;color:#030612;
                    ">{{ mb_substr(auth()->user()->name ?? 'X', 0, 1) }}</div>
                    <span style="font-size:13px;font-weight:500;">{{ auth()->user()->name ?? 'User' }}</span>
                    <span style="font-size:9px;opacity:0.6;margin-left:2px;">▼</span>
                </button>
                <div x-show="open" x-transition style="
                    position:absolute;top:calc(100% + 8px);right:0;width:220px;
                    background:rgba(15,23,42,0.95);backdrop-filter:blur(20px);
                    border:1px solid rgba(255,255,255,0.1);border-radius:12px;
                    padding:6px;box-shadow:0 20px 40px -10px rgba(0,0,0,0.5);z-index:60;
                " style="display:none;">
                    <div style="padding:12px 14px 10px;border-bottom:1px solid rgba(255,255,255,0.06);">
                        <div style="font-size:13px;color:#fff;font-weight:500;">{{ auth()->user()->name }}</div>
                        <div style="font-size:11px;color:#94a3b8;">{{ auth()->user()->email }}</div>
                    </div>
                    <a href="{{ route('xdreamer.dashboard') }}" class="xdr-menu-item">
                        <span style="color:#a5f3fc;width:14px;display:inline-block;">◈</span>Dashboard
                    </a>
                    <a href="{{ route('xdreamer.studio') }}" class="xdr-menu-item">
                        <span style="color:#a5f3fc;width:14px;display:inline-block;">✦</span>สตูดิโอ
                    </a>
                    <a href="{{ route('xdreamer.gallery') }}" class="xdr-menu-item">
                        <span style="color:#a5f3fc;width:14px;display:inline-block;">▧</span>Gallery
                    </a>
                    <div style="height:1px;background:rgba(255,255,255,0.06);margin:6px 0;"></div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="xdr-menu-item" style="width:100%;text-align:left;background:transparent;border:none;color:#fca5a5;font-family:inherit;cursor:pointer;">
                            <span style="width:14px;display:inline-block;">⎋</span>ออกจากระบบ
                        </button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('xdreamer.login') }}" class="rp-nav-cta-ghost" style="
                background:transparent;color:#e2e8f0;border:1px solid rgba(255,255,255,0.15);
                padding:8px 16px;border-radius:10px;font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;
            ">เข้าสู่ระบบ</a>
            <a href="{{ route('xdreamer.signup') }}" class="rp-nav-cta-primary" style="
                background:linear-gradient(135deg, #10b981 0%, #06b6d4 50%, #8b5cf6 100%);
                color:#fff;border:none;padding:9px 18px;border-radius:10px;
                font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;
                box-shadow:0 8px 24px -8px rgba(139,92,246,0.6);
            ">เริ่มสร้างฟรี</a>
        @endauth
    </div>
</nav>
