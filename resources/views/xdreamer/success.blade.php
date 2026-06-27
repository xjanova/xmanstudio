@extends('layouts.xdreamer')
@section('title', 'สำเร็จ · '.$order->order_number.' · X-DREAMER')

@php $hueShift = 70; $h = 220 + $hueShift; @endphp

@section('content')
<div style="padding:140px 24px 80px;max-width:720px;margin:0 auto;text-align:center;">
    <div style="width:88px;height:88px;border-radius:50%;margin:0 auto 24px;
        background:radial-gradient(circle at 30% 30%, hsl({{ $h }},80%,65%), hsl({{ $h+30 }},70%,45%));
        box-shadow:0 0 60px hsla({{ $h }},80%,60%,0.6), inset 0 0 12px rgba(255,255,255,0.3);
        display:grid;place-items:center;font-size:42px;color:#fff;">✓</div>

    <h1 style="font-size:clamp(36px, 5vw, 56px);font-weight:200;color:#fff;letter-spacing:-0.02em;margin:0;">
        @if($order->status === 'processing')
            ขอบคุณ — <span class="xdr-italic-th" style="font-style:italic;color:hsl({{ $h }},80%,75%);">รอการตรวจสอบ / Pending review</span>
        @else
            <span class="xdr-italic-th" style="font-style:italic;color:hsl({{ $h }},80%,75%);">เริ่มทอ</span>ความฝันได้เลย / Start weaving your dreams
        @endif
    </h1>

    <p style="margin-top:18px;color:rgba(203,213,225,0.75);font-size:16px;font-weight:300;">
        @if($order->status === 'processing')
            เราได้รับสลิปของคุณแล้ว — ทีมงานจะตรวจสอบภายใน 30 นาที
            จากนั้นเครดิตจะถูกเพิ่มให้กับบัญชี AIXMAN ของคุณโดยอัตโนมัติ
            <br><span style="opacity:0.7;">We have received your slip — our team will verify it within 30 minutes, then your credits will be added to your AIXMAN account automatically.</span>
        @else
            เครดิต / Credits {{ number_format((int)($metadata['credits'] ?? 0)) }}
            @if(($metadata['bonus_credits'] ?? 0) > 0)+ {{ number_format($metadata['bonus_credits']) }} โบนัส / bonus@endif
            ถูกเพิ่มในบัญชีของคุณแล้ว / have been added to your account
        @endif
    </p>

    <div style="margin-top:32px;padding:20px;border-radius:16px;background:rgba(15,23,42,0.5);border:1px solid rgba(255,255,255,0.06);text-align:left;">
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px 24px;font-size:13px;">
            <div>
                <div style="color:#64748b;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;"><x-bi k="common.order_number" /></div>
                <div style="color:#fff;font-family:ui-monospace,monospace;margin-top:2px;">{{ $order->order_number }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;"><x-bi th="ยอดชำระ" en="Amount paid" /></div>
                <div style="color:#fff;margin-top:2px;">฿{{ number_format($order->total) }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;"><x-bi th="แพ็กเกจ" en="Package" /></div>
                <div style="color:#fff;margin-top:2px;">{{ $metadata['package_name'] ?? '-' }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;"><x-bi k="common.status" /></div>
                <div style="color:hsl({{ $h }},80%,75%);margin-top:2px;text-transform:capitalize;">{{ $order->status }}</div>
            </div>
        </div>
    </div>

    <div style="margin-top:32px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="{{ config('services.aixman.api_base', 'https://ai.xman4289.com') }}" target="_blank" rel="noopener" style="
            padding:14px 28px;border-radius:12px;
            background:linear-gradient(135deg, hsl({{ $h }},70%,50%), hsl({{ $h+40 }},70%,60%));
            color:#fff;border:none;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;
            box-shadow:0 12px 30px -10px hsla({{ $h }},80%,50%,0.7);">
            ไปที่ AIXMAN / Go to AIXMAN →
        </a>
        <a href="{{ route('xdreamer.dashboard') }}" style="
            padding:14px 24px;border-radius:12px;background:rgba(255,255,255,0.05);color:#fff;
            border:1px solid rgba(255,255,255,0.15);font-size:14px;font-weight:500;cursor:pointer;text-decoration:none;">
            ดู Dashboard / View Dashboard
        </a>
    </div>
</div>
@endsection
