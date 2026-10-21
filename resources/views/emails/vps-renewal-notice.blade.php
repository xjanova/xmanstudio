@extends('emails.partials.email-base')

@section('title', 'เซิร์ฟเวอร์ ' . $server->hostname . ' ใกล้ต่ออายุ')

@section('header')
    <div class="email-header-badge badge-order">ต่ออายุอัตโนมัติ</div>
    <h1>{{ $server->hostname }}</h1>
    <p>{{ $server->plan_name }} · หมดอายุ {{ $server->expires_at?->format('d/m/Y') }}</p>
@endsection

@section('body')
    <p class="greeting">เรียนคุณ <strong>{{ $server->user?->name ?? 'ลูกค้า' }}</strong></p>
    <p style="color: #a8b4d4; font-size: 14px; margin-bottom: 20px;">
        เซิร์ฟเวอร์ของคุณเปิดต่ออายุอัตโนมัติไว้ เราจะตัดเงินจากกระเป๋าเงินในเว็บ
        <strong>ก่อนหมดอายุ {{ \App\Support\VpsSettings::chargeDays() }} วัน</strong> (ประมาณวันที่ {{ $server->expires_at?->copy()->subDays(\App\Support\VpsSettings::chargeDays())->format('d/m/Y') ?? '-' }})
        อีเมลฉบับนี้คือการแจ้งล่วงหน้าก่อนตัดเงินครับ
    </p>

    <div class="card">
        <div class="card-title">รายละเอียดการต่ออายุ</div>
        <div class="info-row">
            <span class="info-label">ค่าต่ออายุ ({{ $server->periodLabel() }})</span>
            <span class="info-value" style="color: #60a5fa; font-size: 18px;"><strong>{{ number_format($price, 0) }} บาท</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">ยอดในกระเป๋าเงินตอนนี้</span>
            <span class="info-value" style="color: {{ $walletBalance < $price ? '#f87171' : '#34d399' }};">{{ number_format($walletBalance, 0) }} บาท</span>
        </div>
        <div class="info-row">
            <span class="info-label">หมดอายุ</span>
            <span class="info-value">{{ $server->expires_at?->format('d/m/Y') }}</span>
        </div>
    </div>

    @if ($walletBalance < $price)
        <div class="warning-box mt-4">
            <p style="margin: 0; font-size: 13px; line-height: 1.7;">
                <strong>ยอดเงินยังไม่พอ</strong> — ขาดอีก {{ number_format($price - $walletBalance, 0) }} บาท
                ถ้าถึงวันตัดเงินแล้วยังไม่พอ เซิร์ฟเวอร์จะหมดอายุและถูกระงับ และข้อมูลในเครื่องจะถูกลบในที่สุด
                กรุณาเติมเงินก่อนครับ
            </p>
        </div>
    @endif

    <div class="text-center mt-6">
        <a href="{{ route('customer.vps.show', $server->id) }}" class="btn btn-primary btn-block">เปิดหน้าเซิร์ฟเวอร์ของฉัน</a>
    </div>

    <p style="color: #7c8aa8; font-size: 13px; line-height: 1.7; margin-top: 16px;">
        ไม่ต้องการต่ออายุ? เข้าหน้าเซิร์ฟเวอร์แล้วกด <strong>ปิดต่ออายุอัตโนมัติ</strong> — อย่าลืมสำรองข้อมูลก่อนหมดอายุ
    </p>
@endsection
