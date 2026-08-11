{{--
    Shared shell for HTTP error pages.

    Deliberately standalone — it does NOT extend layouts.app.
    layouts.app reads Setting::getValue() and <x-seo-meta> reads
    SeoSetting::getInstance(); if the error being reported IS a database
    outage, rendering those would throw a second exception and the visitor
    would get a blank page instead of this one. Everything here is inline and
    dependency-free: no DB, no Vite bundle, no JS.

    Vars: $code, $titleTh, $titleEn, $bodyTh, $bodyEn, $art (optional)
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} · {{ $titleEn }} — XMAN Studio</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background: #05070f;
            color: #e8ecf8;
            font-family: 'Noto Sans Thai', 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            text-align: center;
            position: relative;
            overflow-x: hidden;
        }
        .art {
            position: fixed;
            inset: 0;
            background-image: url('{{ asset('artwork/' . ($art ?? 'hero-error') . '.webp') }}');
            background-size: cover;
            background-position: center;
            opacity: .45;
            pointer-events: none;
        }
        .scrim {
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(5,7,15,.30) 0%, rgba(5,7,15,.88) 100%);
            pointer-events: none;
        }
        .wrap { position: relative; max-width: 640px; }
        .code {
            font-size: clamp(72px, 18vw, 156px);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.04em;
            margin: 0 0 8px;
            background: linear-gradient(135deg, #22d3ee 0%, #8b5cf6 50%, #e879f9 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        h1 { font-size: clamp(20px, 4vw, 30px); font-weight: 700; margin: 0 0 6px; }
        .sub { font-size: 14px; letter-spacing: .18em; text-transform: uppercase; color: #7c89ad; margin: 0 0 22px; }
        p { font-size: 16px; line-height: 1.7; color: #aab4cf; margin: 0 0 8px; }
        p.en { font-size: 14px; color: #7c89ad; margin-bottom: 32px; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
        a.btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 26px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }
        a.primary {
            background: linear-gradient(135deg, #22d3ee, #8b5cf6);
            color: #05070f;
            box-shadow: 0 8px 30px rgba(139, 92, 246, .35);
        }
        a.ghost { border: 1px solid rgba(255,255,255,.22); color: #e8ecf8; }
        a.btn:hover { transform: translateY(-2px); }
        a.ghost:hover { background: rgba(255,255,255,.08); }
        @media (prefers-reduced-motion: reduce) { a.btn { transition: none; } a.btn:hover { transform: none; } }
    </style>
</head>
<body>
    <div class="art" aria-hidden="true"></div>
    <div class="scrim" aria-hidden="true"></div>

    <main class="wrap">
        <div class="code">{{ $code }}</div>
        <h1>{{ $titleTh }}</h1>
        <p class="sub">{{ $titleEn }}</p>
        <p>{{ $bodyTh }}</p>
        <p class="en">{{ $bodyEn }}</p>
        <div class="actions">
            <a class="btn primary" href="{{ url('/') }}">กลับหน้าแรก / Back home</a>
            <a class="btn ghost" href="{{ url('/support') }}">ติดต่อเรา / Contact us</a>
        </div>
    </main>
</body>
</html>
