@extends('emails.partials.email-base')

@section('title', $license->product?->name ?? 'License')

@section('header')
    <div class="email-header-badge badge-order">
        @if($kind === 'notice') ต่ออายุอัตโนมัติ @elseif($kind === 'short') ยอดเงินไม่พอต่ออายุ @else ใกล้หมดอายุ @endif
    </div>
    <h1>{{ $license->product?->name ?? 'License' }}</h1>
    <p>หมดอายุ {{ $license->expires_at?->format('d/m/Y') }}</p>
@endsection

@section('body')
    <p class="greeting">เรียนคุณ <strong>{{ $license->user?->name ?? 'ลูกค้า' }}</strong></p>

    <p style="color: #a8b4d4; font-size: 14px; margin-bottom: 20px; line-height: 1.7;">
        @if($kind === 'notice')
            คุณเปิดต่ออายุอัตโนมัติไว้ เราจะตัดเงินจาก Wallet ประมาณวันที่ <strong>{{ $chargeDate }}</strong>
            และต่ออายุ<strong>คีย์เดิม</strong>ให้ทันที ไม่ต้องกรอกคีย์ใหม่ในโปรแกรม
            @if($walletBalance < $price)
                — <strong>ตอนนี้ยอดใน Wallet ยังไม่พอ</strong> กรุณาเติมเงินก่อนวันตัดเงิน
            @endif
        @elseif($kind === 'short')
            ถึงกำหนดต่ออายุอัตโนมัติแล้ว แต่ยอดใน Wallet ยังไม่พอ
            เราจะลองตัดเงินใหม่ทุกวัน กรุณาเติมเงินหรือกดต่ออายุเองเพื่อไม่ให้โปรแกรมหยุดทำงาน
        @else
            ไลเซนส์ของคุณจะหมดอายุในอีก <strong>{{ $daysLeft }} วัน</strong>
            กดต่ออายุได้จากหน้าไลเซนส์ — ใช้คีย์เดิม เวลาบวกต่อจากวันหมดอายุ ไม่เสียวันที่เหลือ
        @endif
    </p>

    <div class="card">
        <div class="card-title">รายละเอียด</div>
        <div class="info-row">
            <span class="info-label">License Key</span>
            <span class="info-value" style="font-family: monospace;">{{ $license->license_key }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">ค่าต่ออายุ (รวม VAT)</span>
            <span class="info-value" style="color: #60a5fa; font-size: 18px;"><strong>{{ number_format($price, 2) }} บาท</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">ยอดใน Wallet</span>
            <span class="info-value" style="color: {{ $walletBalance < $price ? '#f87171' : '#34d399' }};">{{ number_format($walletBalance, 2) }} บาท</span>
        </div>
    </div>

    <div class="text-center mt-6">
        <a href="{{ route('customer.licenses.show', $license) }}#renew" class="btn btn-primary btn-block">
            @if($kind === 'notice') ดู / ปิดต่ออายุอัตโนมัติ @else ต่ออายุคีย์นี้ @endif
        </a>
    </div>

    @if($walletBalance < $price)
        <div class="text-center mt-4">
            <a href="{{ route('user.wallet.topup') }}" class="btn btn-block">เติมเงินเข้า Wallet</a>
        </div>
    @endif
@endsection
