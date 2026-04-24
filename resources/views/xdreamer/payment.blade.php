@extends('layouts.xdreamer')
@section('title', 'ชำระเงิน · คำสั่งซื้อ #'.$order->order_number.' · X-DREAMER')

@php
    $hueShift = 70;
    $h = 220 + $hueShift;
    $metadata = is_array($order->metadata) ? $order->metadata : (json_decode($order->metadata ?? '{}', true) ?: []);
@endphp

@section('content')
<div style="padding:110px 24px 80px;max-width:900px;margin:0 auto;">
    <nav style="font-size:13px;color:#94a3b8;margin-bottom:32px;">
        <a href="{{ route('xdreamer.home') }}" style="color:inherit;text-decoration:none;">X-DREAMER</a>
        <span style="margin:0 8px;color:#475569;">/</span>
        <span style="color:#fff;">ชำระเงิน · #{{ $order->order_number }}</span>
    </nav>

    <div style="padding:32px;border-radius:22px;background:rgba(15,23,42,0.6);border:1px solid rgba(255,255,255,0.08);backdrop-filter:blur(18px);">

        @if(session('error'))
        <div style="padding:12px 14px;border-radius:10px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;font-size:13px;margin-bottom:16px;">
            {{ session('error') }}
        </div>
        @endif

        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
            <div>
                <div style="font-size:11px;color:#a5f3fc;letter-spacing:0.16em;text-transform:uppercase;margin-bottom:8px;">· คำสั่งซื้อ</div>
                <div style="font-size:24px;color:#fff;font-weight:300;">{{ $metadata['package_name'] ?? 'AI Credits' }}</div>
                <div style="font-size:13px;color:#94a3b8;margin-top:4px;">{{ number_format((int)($metadata['credits'] ?? 0)) }} credits @if(($metadata['bonus_credits'] ?? 0) > 0)+ {{ number_format($metadata['bonus_credits']) }} bonus@endif</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11px;color:#94a3b8;">ยอดชำระ</div>
                <div style="font-size:32px;font-weight:300;color:hsl({{ $h }},80%,75%);">฿{{ number_format($order->total) }}</div>
            </div>
        </div>

        <div style="height:1px;background:rgba(255,255,255,0.06);margin:24px 0;"></div>

        @if($order->payment_method === 'promptpay' && $paymentInfo)
            <div style="text-align:center;">
                <div style="font-size:13px;color:#94a3b8;margin-bottom:14px;">สแกน QR Code ด้วยแอป PromptPay / Mobile Banking</div>
                @if(! empty($paymentInfo['qr_url']))
                    <img src="{{ $paymentInfo['qr_url'] }}" alt="PromptPay QR" style="display:block;margin:0 auto;width:280px;height:280px;border-radius:16px;background:#fff;padding:14px;">
                @elseif(! empty($paymentInfo['qr_payload']))
                    <div style="display:inline-block;padding:14px;background:#fff;border-radius:16px;font-family:ui-monospace,monospace;font-size:11px;color:#000;word-break:break-all;max-width:300px;">{{ $paymentInfo['qr_payload'] }}</div>
                @endif
                <div style="margin-top:18px;font-size:12px;color:#64748b;">รหัสอ้างอิง: {{ $order->order_number }}</div>
            </div>
        @elseif($order->payment_method === 'bank_transfer')
            <div style="font-size:13px;color:#94a3b8;margin-bottom:14px;">โอนเงินไปยังบัญชีด้านล่าง แล้วอัปโหลดสลิปยืนยัน</div>
            @if($bankAccounts && $bankAccounts->count())
                <div style="display:flex;flex-direction:column;gap:10px;">
                    @foreach($bankAccounts as $bank)
                    <div style="padding:14px 18px;border-radius:12px;background:rgba(2,6,23,0.4);border:1px solid rgba(255,255,255,0.06);display:flex;justify-content:space-between;align-items:center;gap:14px;">
                        <div>
                            <div style="font-size:14px;color:#fff;font-weight:500;">{{ $bank->bank_name }}</div>
                            <div style="font-size:12px;color:#94a3b8;">{{ $bank->account_holder }}</div>
                        </div>
                        <div style="font-family:ui-monospace,monospace;font-size:14px;color:#a5f3fc;letter-spacing:0.04em;">{{ $bank->account_number }}</div>
                    </div>
                    @endforeach
                </div>
            @endif
        @endif

        <div style="height:1px;background:rgba(255,255,255,0.06);margin:32px 0;"></div>

        <form method="POST" action="{{ route('xdreamer.checkout.confirm', $order->id) }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:14px;">
            @csrf
            <div style="font-size:14px;color:#fff;font-weight:500;">แนบสลิปยืนยันการโอน</div>
            <input type="file" name="payment_slip" accept="image/jpeg,image/png" required
                   style="padding:10px;border-radius:10px;background:rgba(2,6,23,0.5);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:13px;font-family:inherit;">
            <textarea name="notes" rows="2" placeholder="หมายเหตุ (ไม่บังคับ)"
                      style="padding:12px 14px;border-radius:10px;background:rgba(2,6,23,0.5);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:13px;font-family:inherit;outline:none;resize:vertical;"></textarea>

            <button type="submit" style="margin-top:8px;padding:14px;border-radius:12px;
                background:linear-gradient(135deg, hsl({{ $h }},70%,50%), hsl({{ $h+40 }},70%,60%));
                color:#fff;border:none;font-size:14px;font-weight:600;cursor:pointer;">
                ยืนยันการชำระเงิน
            </button>
        </form>
    </div>
</div>
@endsection
