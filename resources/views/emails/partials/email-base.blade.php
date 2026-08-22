@php
    $appName = config('app.name', 'XMANStudio');
    // Use production URL for email assets (localhost won't work in email clients)
    $appUrl = \App\Models\PaymentSetting::get('email_site_url', config('app.url'));
    $fromEmail = config('mail.from.address', 'noreply@xman4289.com');

    // The site logo is dark-on-light artwork, so it disappears on this dark
    // template. Only an explicitly-set email logo (which an admin can point at a
    // light-on-dark file) is used as an image — otherwise we draw the wordmark.
    $logoUrl = \App\Models\PaymentSetting::get('email_logo_url');
@endphp
<!DOCTYPE html>
<html lang="th" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    {{-- Tells Apple Mail / Gmail this design is already dark, so they stop auto-inverting it. --}}
    <meta name="color-scheme" content="dark">
    <meta name="supported-color-schemes" content="dark">
    <title>@yield('title', $appName)</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap');

        /*
            NOVA email theme — mirrors resources/css/nova-theme.css.
            Every colour is a literal hex: mail clients strip CSS custom properties,
            so --nv-* tokens cannot be used here.
              void #04050d · abyss #070a18 · panel #0e1428 · line #1c2545
              cyan #22d3ee · violet #8b5cf6 · magenta #e879f9 · mint #34d399 · core #ffd479
              fg-1 #eaf0ff · fg-2 #a8b4d4 · fg-3 #6b7799
        */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Noto Sans Thai', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.7;
            color: #eaf0ff;
            background-color: #04050d;
            color-scheme: dark;
            -webkit-font-smoothing: antialiased;
        }
        a { color: #22d3ee; }

        .email-wrapper {
            max-width: 640px;
            background-color: #070a18;
            border: 1px solid #1c2545;
            border-radius: 20px;
            overflow: hidden;
        }

        /* The nova spectrum, as a hairline across the top of the card. */
        .email-spectrum {
            height: 4px;
            line-height: 4px;
            font-size: 0;
            background-color: #8b5cf6;
            background-image: linear-gradient(90deg, #22d3ee 0%, #8b5cf6 38%, #e879f9 68%, #ffd479 100%);
        }

        .email-header {
            padding: 40px 40px 32px;
            text-align: center;
            background-color: #0a0f22;
            background-image: radial-gradient(ellipse 120% 140% at 50% 0%, #1a2247 0%, #0a0f22 62%);
            border-bottom: 1px solid #1c2545;
        }
        .email-logo { margin-bottom: 22px; }
        .email-logo img { max-height: 44px; max-width: 200px; }
        .email-logo-text {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 3px;
            color: #eaf0ff;
            text-transform: uppercase;
        }
        .email-logo-text .nv-x { color: #22d3ee; }
        .email-logo-sub {
            margin-top: 6px;
            font-size: 10px;
            letter-spacing: 4px;
            color: #6b7799;
            text-transform: uppercase;
        }

        .email-header-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.4px;
            margin-bottom: 16px;
        }
        .badge-order   { background-color: #17203f; color: #a5b4fc; border: 1px solid #2c3768; }
        .badge-success { background-color: #0f2f26; color: #34d399; border: 1px solid #1d5647; }
        .badge-test    { background-color: #2b2440; color: #e879f9; border: 1px solid #4a3a6b; }

        .email-header h1 {
            font-size: 27px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            line-height: 1.35;
        }
        .email-header p {
            font-size: 14px;
            color: #a8b4d4;
        }

        .email-body {
            padding: 36px 40px;
            background-color: #070a18;
        }
        .greeting {
            font-size: 16px;
            color: #eaf0ff;
            margin-bottom: 6px;
        }
        .greeting strong { color: #22d3ee; font-weight: 600; }

        .card {
            background-color: #0e1428;
            border: 1px solid #1c2545;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #8b5cf6;
            margin-bottom: 14px;
        }

        .info-row {
            display: block;
            font-size: 14px;
            padding: 9px 0;
            border-bottom: 1px solid #161d38;
            overflow: hidden;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #8f9cc0; float: left; }
        .info-value { color: #eaf0ff; font-weight: 500; float: right; text-align: right; }

        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 4px;
            font-size: 14px;
        }
        .order-table th {
            background-color: #131a33;
            color: #8f9cc0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 11px 14px;
            text-align: left;
            border-bottom: 1px solid #1c2545;
        }
        .order-table td {
            padding: 13px 14px;
            color: #eaf0ff;
            border-bottom: 1px solid #161d38;
        }
        .order-table .total-row td {
            background-color: #131a33;
            color: #ffd479;
            font-weight: 700;
            font-size: 16px;
            border-bottom: none;
        }

        /* License key: the one place the nova "core" gold is allowed to shine. */
        .license-box {
            background-color: #0f1226;
            background-image: radial-gradient(ellipse 100% 160% at 50% 0%, #221a3d 0%, #0f1226 70%);
            border: 1px solid #3b2f66;
            border-radius: 16px;
            padding: 26px;
            text-align: center;
            margin-bottom: 20px;
        }
        .license-box h3 {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #e879f9;
            margin-bottom: 14px;
        }
        .license-key-display {
            font-family: 'JetBrains Mono', ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #ffd479;
            background-color: #04050d;
            border: 1px dashed #4a3a6b;
            border-radius: 10px;
            padding: 15px 12px;
            word-break: break-all;
        }
        .license-meta {
            margin-top: 12px;
            font-size: 13px;
            color: #a8b4d4;
        }

        .warning-box {
            background-color: #2a1a12;
            border: 1px solid #5c3a1e;
            border-left: 3px solid #ffd479;
            border-radius: 12px;
            padding: 16px 18px;
            font-size: 14px;
            color: #f3d7ae;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 13px 30px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
        }
        .btn-primary {
            background-color: #7c5cf6;
            background-image: linear-gradient(135deg, #22d3ee 0%, #8b5cf6 55%, #a855f7 100%);
            color: #ffffff !important;
        }
        .btn-success {
            background-color: #10a37f;
            background-image: linear-gradient(135deg, #34d399 0%, #10a37f 100%);
            color: #04050d !important;
        }
        .btn-secondary {
            background-color: #131a33;
            border: 1px solid #2c3768;
            color: #a8b4d4 !important;
        }
        .btn-block { display: block; }

        .text-center { text-align: center; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .mb-4 { margin-bottom: 16px; }

        .email-footer {
            padding: 30px 40px 34px;
            text-align: center;
            background-color: #04050d;
            border-top: 1px solid #1c2545;
        }
        .footer-logo { margin-bottom: 12px; }
        .footer-logo img { max-height: 30px; }
        .footer-text { font-size: 13px; color: #a8b4d4; }
        .footer-text a { color: #22d3ee; text-decoration: none; }
        .footer-divider {
            border: none;
            border-top: 1px solid #1c2545;
            margin: 18px 0 14px;
        }
        .footer-copyright { font-size: 12px; color: #6b7799; line-height: 1.8; }

        @media only screen and (max-width: 640px) {
            .email-wrapper { width: 100% !important; border-radius: 0 !important; }
            .email-header, .email-body, .email-footer { padding: 26px 20px !important; }
            .card { padding: 17px !important; }
            .email-header h1 { font-size: 23px !important; }
            .info-label, .info-value { float: none !important; display: block !important; text-align: left !important; }
        }
    </style>
</head>
<body style="background-color: #04050d; margin: 0; padding: 24px 0; color-scheme: dark;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #04050d;">
        <tr>
            <td align="center">
                <div class="email-wrapper" style="max-width: 640px; background-color: #070a18; border: 1px solid #1c2545; border-radius: 20px; overflow: hidden;">

                    {{-- SPECTRUM --}}
                    <div class="email-spectrum" style="height: 4px; line-height: 4px; font-size: 0; background-color: #8b5cf6;">&nbsp;</div>

                    {{-- HEADER --}}
                    <div class="email-header" style="padding: 40px 40px 32px; text-align: center; background-color: #0a0f22; border-bottom: 1px solid #1c2545;">
                        <div class="email-logo" style="margin-bottom: 22px;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $appName }}" style="max-height: 44px; max-width: 200px;">
                            @else
                                <div class="email-logo-text" style="font-size: 22px; font-weight: 700; letter-spacing: 3px; color: #eaf0ff;">
                                    <span class="nv-x" style="color: #22d3ee;">X</span>MAN STUDIO
                                </div>
                                <div class="email-logo-sub" style="margin-top: 6px; font-size: 10px; letter-spacing: 4px; color: #6b7799;">IT &amp; AI SOLUTIONS</div>
                            @endif
                        </div>
                        @yield('header')
                    </div>

                    {{-- BODY --}}
                    <div class="email-body" style="padding: 36px 40px; background-color: #070a18;">
                        @yield('body')
                    </div>

                    {{-- FOOTER --}}
                    <div class="email-footer" style="padding: 30px 40px 34px; text-align: center; background-color: #04050d; border-top: 1px solid #1c2545;">
                        <div class="footer-logo" style="margin-bottom: 12px;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $appName }}" style="max-height: 30px;">
                            @else
                                <div style="color: #eaf0ff; font-size: 15px; font-weight: 700; letter-spacing: 2px;">
                                    <span style="color: #22d3ee;">X</span>MAN STUDIO
                                </div>
                            @endif
                        </div>
                        <div class="footer-text" style="font-size: 13px; color: #a8b4d4;">
                            <a href="{{ $appUrl }}" style="color: #22d3ee; text-decoration: none;">{{ $appUrl }}</a>
                        </div>
                        <hr class="footer-divider" style="border: none; border-top: 1px solid #1c2545; margin: 18px 0 14px;">
                        <p class="footer-copyright" style="font-size: 12px; color: #6b7799; line-height: 1.8;">
                            &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.<br>
                            อีเมลนี้ถูกส่งโดยอัตโนมัติ กรุณาอย่าตอบกลับ
                        </p>
                    </div>

                </div>
            </td>
        </tr>
    </table>
</body>
</html>
