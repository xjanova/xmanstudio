{{--
    Shown to an address on the block list.

    Standalone like the rest of errors/ — no Setting, no SeoSetting, no Vite.
    It also does NOT reuse errors.layout, for one reason the other codes do not
    have: every link on that page goes back into the site, and for this visitor
    every one of them returns this same page. The only way out is off-site, so
    the only route offered here is an email address.

    Vars: $message, $ip, $until (Carbon|null)
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>403 · Access temporarily blocked — XMAN Studio</title>
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
        }
        .scrim {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 10%, rgba(239, 68, 68, .18) 0%, transparent 55%),
                radial-gradient(ellipse at 80% 90%, rgba(139, 92, 246, .14) 0%, transparent 50%);
            pointer-events: none;
        }
        .wrap { position: relative; max-width: 560px; }
        .shield { font-size: 56px; line-height: 1; margin-bottom: 8px; }
        .code {
            font-size: clamp(64px, 16vw, 132px);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.04em;
            margin: 0 0 12px;
            background: linear-gradient(135deg, #f87171 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        h1 { font-size: clamp(20px, 4.5vw, 27px); font-weight: 700; margin: 0 0 6px; }
        .sub { font-size: 14px; color: #8b93ad; margin: 0 0 20px; letter-spacing: .02em; }
        p { font-size: 15px; line-height: 1.75; color: #b9c0d4; margin: 0 0 18px; }
        .facts {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px 22px;
            justify-content: center;
            padding: 14px 20px;
            margin-bottom: 22px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 14px;
            background: rgba(255,255,255,.03);
            font-size: 13px;
        }
        .facts b { display: block; color: #6c7590; font-weight: 500; margin-bottom: 2px; font-size: 11px; text-transform: uppercase; letter-spacing: .08em; }
        .facts span { font-family: ui-monospace, 'SFMono-Regular', Menlo, monospace; color: #e8ecf8; }
        .btn {
            display: inline-block;
            padding: 12px 26px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            color: #05070f;
            background: linear-gradient(135deg, #22d3ee 0%, #8b5cf6 100%);
        }
        .note { margin-top: 20px; font-size: 12.5px; color: #6c7590; }
    </style>
</head>
<body>
    <div class="scrim"></div>
    <div class="wrap">
        <div class="shield">🛡️</div>
        <div class="code">403</div>
        <h1>การเข้าถึงถูกระงับชั่วคราว</h1>
        <p class="sub">Access temporarily blocked</p>

        <p>{{ $message }}</p>

        <div class="facts">
            <div>
                <b>IP address</b>
                <span>{{ $ip ?? '—' }}</span>
            </div>
            <div>
                <b>สถานะ / Status</b>
                <span>{{ $until ? $until->timezone(config('app.timezone'))->format('d/m/Y H:i') : 'Permanent' }}</span>
            </div>
        </div>

        <p>
            หากคุณเป็นเจ้าของบัญชีและถูกระงับโดยไม่ตั้งใจ กรุณาติดต่อทีมงานทางอีเมล
            พร้อมแจ้งหมายเลข IP ด้านบน<br>
            <span style="color:#8b93ad;font-size:13.5px;">If you believe this is a mistake, email us with the IP shown above.</span>
        </p>

        {{-- The only link that is not behind this same block. --}}
        <a class="btn" href="mailto:{{ config('mail.from.address') }}?subject=Blocked%20IP%20{{ urlencode((string) ($ip ?? '')) }}">
            ติดต่อทีมงาน / Email support
        </a>

        <p class="note">XMAN Studio · ระบบป้องกันการเข้าสู่ระบบอัตโนมัติ</p>
    </div>
</body>
</html>
