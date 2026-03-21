@php
    $siteLogo = \App\Models\Setting::getValue('site_logo');
    $appName = config('app.name', 'XMANStudio');
    // Use production URL for email assets (localhost won't work in email clients)
    $appUrl = \App\Models\PaymentSetting::get('email_site_url', config('app.url'));
    $fromEmail = config('mail.from.address', 'noreply@xman4289.com');
    $primaryColor = '#6366f1';
    $primaryDark = '#4f46e5';
    // Build logo URL that's accessible from email clients
    $logoUrl = $siteLogo ? ($appUrl . '/storage/' . $siteLogo) : null;
@endphp
<!DOCTYPE html>
<html lang="th" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
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

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Noto Sans Thai', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.7;
            color: #1f2937;
            background-color: #f3f4f6;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, {{ $primaryColor }} 0%, #7c3aed 50%, #a855f7 100%);
            padding: 32px 40px;
            text-align: center;
        }
        .email-logo {
            margin-bottom: 16px;
        }
        .email-logo img {
            max-height: 48px;
            max-width: 200px;
        }
        .email-logo-text {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .email-header-badge {
            display: inline-block;
            padding: 6px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 12px;
        }
        .badge-order {
            background: rgba(255,255,255,0.2);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .badge-success {
            background: #10b981;
            color: #ffffff;
        }
        .badge-test {
            background: #f59e0b;
            color: #ffffff;
        }
        .email-header h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            margin: 12px 0 4px;
        }
        .email-header p {
            color: rgba(255,255,255,0.85);
            font-size: 14px;
        }
        .email-body {
            padding: 32px 40px;
        }
        .greeting {
            font-size: 16px;
            color: #374151;
            margin-bottom: 16px;
        }
        .greeting strong {
            color: {{ $primaryColor }};
        }
        .card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin: 20px 0;
        }
        .card-title {
            font-size: 15px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6b7280; }
        .info-value { color: #1f2937; font-weight: 500; }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 14px;
        }
        .order-table th {
            background: #f3f4f6;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .order-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
        }
        .order-table .total-row td {
            font-weight: 700;
            color: #1f2937;
            font-size: 16px;
            background: #f9fafb;
            border-top: 2px solid {{ $primaryColor }};
        }
        .license-box {
            background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
            border: 1px solid #93c5fd;
            border-radius: 12px;
            padding: 24px;
            margin: 20px 0;
        }
        .license-box h3 {
            color: #1e40af;
            font-size: 15px;
            margin-bottom: 12px;
        }
        .license-key-display {
            background: #ffffff;
            padding: 14px;
            border-radius: 8px;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 15px;
            text-align: center;
            color: #1e40af;
            border: 2px dashed #93c5fd;
            letter-spacing: 1px;
            margin: 8px 0;
            word-break: break-all;
        }
        .license-meta {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
        }
        .warning-box {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 20px 0;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }
        .btn-primary {
            background: {{ $primaryColor }};
            color: #ffffff !important;
        }
        .btn-success {
            background: #10b981;
            color: #ffffff !important;
        }
        .btn-secondary {
            background: #6b7280;
            color: #ffffff !important;
        }
        .btn-block {
            display: block;
            width: 100%;
        }
        .text-center { text-align: center; }
        .mt-4 { margin-top: 16px; }
        .mt-6 { margin-top: 24px; }
        .mb-4 { margin-bottom: 16px; }

        .email-footer {
            background: #1f2937;
            padding: 32px 40px;
            text-align: center;
        }
        .footer-logo {
            margin-bottom: 16px;
        }
        .footer-logo img {
            max-height: 32px;
            filter: brightness(10);
        }
        .footer-text {
            color: #9ca3af;
            font-size: 13px;
            line-height: 1.8;
        }
        .footer-text a {
            color: #a5b4fc;
            text-decoration: none;
        }
        .footer-divider {
            border: none;
            border-top: 1px solid #374151;
            margin: 20px 0;
        }
        .footer-copyright {
            color: #6b7280;
            font-size: 11px;
        }

        @media only screen and (max-width: 640px) {
            .email-wrapper { width: 100% !important; }
            .email-header, .email-body, .email-footer { padding: 24px 20px !important; }
            .card { padding: 16px !important; }
        }
    </style>
</head>
<body style="background-color: #f3f4f6; margin: 0; padding: 20px 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center">
                <div class="email-wrapper" style="max-width: 640px; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">

                    {{-- HEADER --}}
                    <div class="email-header">
                        <div class="email-logo">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $appName }}" style="max-height: 48px; max-width: 200px;">
                            @else
                                <div class="email-logo-text">{{ $appName }}</div>
                            @endif
                        </div>
                        @yield('header')
                    </div>

                    {{-- BODY --}}
                    <div class="email-body">
                        @yield('body')
                    </div>

                    {{-- FOOTER --}}
                    <div class="email-footer">
                        <div class="footer-logo">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $appName }}" style="max-height: 32px; filter: brightness(10);">
                            @else
                                <div style="color: #ffffff; font-size: 18px; font-weight: 700;">{{ $appName }}</div>
                            @endif
                        </div>
                        <div class="footer-text">
                            <a href="{{ $appUrl }}">{{ $appUrl }}</a>
                        </div>
                        <hr class="footer-divider">
                        <p class="footer-copyright">
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
