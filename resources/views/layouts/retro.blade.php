<!DOCTYPE html>
<html lang="th" class="tron-body">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'XMAN Studio — Digital Craft Division')</title>
    <meta name="description" content="@yield('description', 'XMAN Studio — Blockchain, AI, Web & Mobile. Est. MMXVIII · Bangkok.')">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="tron-body" style="margin:0; background: var(--tron-void); color: var(--fg-1); font-family: var(--font-serif);">

    {{-- Sticky Nav --}}
    <nav style="position:sticky;top:0;z-index:50;background:linear-gradient(180deg,rgba(3,7,17,.95),rgba(10,22,40,.85));backdrop-filter:blur(12px);border-bottom:1px solid rgba(0,229,255,.25);">
        <div style="max-width:1280px;margin:0 auto;padding:0 24px;display:flex;align-items:center;height:64px;gap:16px;">
            <a href="{{ url('/') }}" style="display:flex;align-items:center;gap:12px;text-decoration:none;flex-shrink:0;">
                <span class="tron-seal-ring" style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-ui);font-weight:900;color:var(--tron-gold);font-size:14px;">X</span>
                <span style="font-family:var(--font-display);font-size:20px;letter-spacing:.08em;color:var(--fg-1);">XMAN <span class="tron-gold-foil">STUDIO</span></span>
            </a>
            <div style="display:none;margin-left:auto;gap:4px;" class="retro-nav-links">
                @php
                    $nav = [
                        ['home',      'HOME',     '#00e5ff', url('/')],
                        ['services',  'SERVICES', '#d4af37', url('/services')],
                        ['products',  'PROGRAMS', '#ff2d95', url('/products')],
                        ['rental',    'LEASE',    '#7c4dff', url('/rental')],
                        ['portfolio', 'ARCHIVES', '#4dd0e1', url('/portfolio')],
                        ['support',   'CONTACT',  '#4ade80', url('/support')],
                    ];
                    $active = $activeNav ?? 'home';
                @endphp
                @foreach($nav as [$id, $label, $color, $href])
                    @php $is = $id === $active; @endphp
                    <a href="{{ $href }}" style="padding:8px 14px;font-family:var(--font-ui);font-size:11px;letter-spacing:.2em;font-weight:600;text-decoration:none;
                       color: {{ $is ? $color : 'var(--fg-2)' }};
                       border-top: 1px solid {{ $is ? $color : 'transparent' }};
                       border-bottom: 1px solid {{ $is ? $color : 'transparent' }};
                       {{ $is ? 'text-shadow: 0 0 8px '.$color.'99;' : '' }}
                       transition: all .3s var(--ease-tron);">{{ $label }}</a>
                @endforeach
            </div>
            <div style="font-family:var(--font-mono);font-size:10px;color:var(--fg-3);letter-spacing:.1em;margin-left:auto;">EST · 2018</div>
        </div>
    </nav>

    @yield('content')

    @include('partials.retro-footer')

    <style>
        @media(min-width: 1024px) {
            .retro-nav-links { display: flex !important; }
        }
    </style>
</body>
</html>
