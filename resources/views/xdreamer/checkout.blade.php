@extends('layouts.xdreamer')
@section('title', 'ชำระเงิน · '.$package['name'].' · X-DREAMER')
@section('meta_description', 'ซื้อ AI Credits แพ็กเกจ '.$package['name'])

@php $hueShift = 70; $h = ($package['hue'] ?? 220) + $hueShift; @endphp

@section('content')
<div style="padding:110px 24px 80px;max-width:1100px;margin:0 auto;">
    <nav style="font-size:13px;color:#94a3b8;margin-bottom:32px;">
        <a href="{{ route('xdreamer.home') }}" style="color:inherit;text-decoration:none;">X-DREAMER</a>
        <span style="margin:0 8px;color:#475569;">/</span>
        <span style="color:#fff;">ชำระเงิน · {{ $package['name'] }}</span>
    </nav>

    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:32px;">
        {{-- Order summary --}}
        <div style="padding:32px;border-radius:22px;
            background:linear-gradient(160deg, hsla({{ $h }},60%,20%,0.65), hsla({{ $h+40 }},60%,12%,0.65));
            border:1px solid hsla({{ $h }},70%,55%,0.5);backdrop-filter:blur(18px);
            box-shadow:0 30px 60px -20px hsla({{ $h }},70%,50%,0.35);
            height:fit-content;">
            <div style="font-size:11px;letter-spacing:0.16em;color:#a5f3fc;text-transform:uppercase;margin-bottom:14px;">· แพ็กเกจของคุณ</div>
            <div style="font-size:24px;font-weight:300;color:#fff;letter-spacing:-0.01em;">{{ $package['name'] }}</div>
            <div style="display:flex;align-items:baseline;gap:6px;margin-top:18px;">
                <div style="font-size:42px;font-weight:300;color:#fff;letter-spacing:-0.02em;">฿{{ number_format($package['price_thb']) }}</div>
                <div style="font-size:14px;color:#94a3b8;">{{ $package['note'] }}</div>
            </div>

            <div style="margin-top:24px;padding:14px;border-radius:12px;background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.06);">
                <div style="display:flex;justify-content:space-between;font-size:13px;color:rgba(226,232,240,0.85);margin-bottom:8px;">
                    <span>Credits</span>
                    <span style="font-weight:600;">{{ number_format($package['credits']) }}</span>
                </div>
                @if(($package['bonus_credits'] ?? 0) > 0)
                <div style="display:flex;justify-content:space-between;font-size:13px;color:hsl({{ $h }},80%,75%);">
                    <span>โบนัส</span>
                    <span style="font-weight:600;">+ {{ number_format($package['bonus_credits']) }}</span>
                </div>
                @endif
            </div>

            <ul style="list-style:none;padding:0;margin:24px 0 0;">
                @foreach($package['features'] as $f)
                <li style="font-size:14px;color:rgba(226,232,240,0.8);margin-bottom:10px;display:flex;gap:10px;font-weight:300;">
                    <span style="color:hsl({{ $h }},80%,70%);flex-shrink:0;">✦</span> {{ $f }}
                </li>
                @endforeach
            </ul>
        </div>

        {{-- Form --}}
        <div style="padding:32px;border-radius:22px;background:rgba(15,23,42,0.6);border:1px solid rgba(255,255,255,0.08);backdrop-filter:blur(18px);">
            <div style="font-size:18px;color:#fff;font-weight:500;margin-bottom:24px;">ข้อมูลผู้สั่งซื้อ</div>

            @if($errors->any())
            <div style="padding:12px 14px;border-radius:10px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;font-size:13px;margin-bottom:16px;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('xdreamer.checkout.process', $package['slug']) }}" style="display:flex;flex-direction:column;gap:14px;">
                @csrf

                <div>
                    <div style="font-size:11px;color:#94a3b8;margin-bottom:6px;letter-spacing:0.04em;">ชื่อ-นามสกุล</div>
                    <input name="customer_name" type="text" required
                           value="{{ old('customer_name', auth()->user()->name ?? '') }}"
                           style="width:100%;padding:12px 14px;border-radius:10px;background:rgba(2,6,23,0.5);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:14px;font-family:inherit;outline:none;">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <div style="font-size:11px;color:#94a3b8;margin-bottom:6px;letter-spacing:0.04em;">อีเมล</div>
                        <input name="customer_email" type="email" required
                               value="{{ old('customer_email', auth()->user()->email ?? '') }}"
                               style="width:100%;padding:12px 14px;border-radius:10px;background:rgba(2,6,23,0.5);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:14px;font-family:inherit;outline:none;">
                    </div>
                    <div>
                        <div style="font-size:11px;color:#94a3b8;margin-bottom:6px;letter-spacing:0.04em;">เบอร์โทร</div>
                        <input name="customer_phone" type="tel" required
                               value="{{ old('customer_phone') }}"
                               style="width:100%;padding:12px 14px;border-radius:10px;background:rgba(2,6,23,0.5);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:14px;font-family:inherit;outline:none;">
                    </div>
                </div>

                <div style="margin-top:8px;">
                    <div style="font-size:11px;color:#94a3b8;margin-bottom:10px;letter-spacing:0.04em;text-transform:uppercase;">วิธีชำระเงิน</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <label style="cursor:pointer;padding:14px;border-radius:12px;background:rgba(2,6,23,0.4);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:10px;">
                            <input type="radio" name="payment_method" value="promptpay" checked style="accent-color:hsl({{ $h }},70%,60%);">
                            <div>
                                <div style="font-size:13px;color:#fff;font-weight:500;">PromptPay</div>
                                <div style="font-size:11px;color:#94a3b8;">QR Code · ทันที</div>
                            </div>
                        </label>
                        <label style="cursor:pointer;padding:14px;border-radius:12px;background:rgba(2,6,23,0.4);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:10px;">
                            <input type="radio" name="payment_method" value="bank_transfer" style="accent-color:hsl({{ $h }},70%,60%);">
                            <div>
                                <div style="font-size:13px;color:#fff;font-weight:500;">โอนผ่านธนาคาร</div>
                                <div style="font-size:11px;color:#94a3b8;">แนบสลิปยืนยัน</div>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" style="
                    margin-top:18px;padding:16px;border-radius:12px;
                    background:linear-gradient(135deg, hsl({{ $h }},70%,50%), hsl({{ $h+40 }},70%,60%));
                    color:#fff;border:none;font-size:15px;font-weight:600;cursor:pointer;
                    box-shadow:0 12px 30px -10px hsla({{ $h }},80%,50%,0.7);">
                    ดำเนินการชำระเงิน · ฿{{ number_format($package['price_thb']) }} →
                </button>

                <div style="font-size:11px;color:#64748b;text-align:center;margin-top:8px;">
                    คลิกเพื่อยอมรับ <a href="{{ route('terms') }}" style="color:#a5f3fc;text-decoration:none;">เงื่อนไขการใช้งาน</a>
                    · เครดิตจะถูกเพิ่มในบัญชี AIXMAN ของคุณภายใน 5 นาทีหลังยืนยันการชำระเงิน
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
