@extends('emails.partials.email-base')

@section('title', 'โดเมน ' . $domain->domain . ' ใกล้ต่ออายุ')

@section('header')
    <div class="email-header-badge badge-order">ต่ออายุอัตโนมัติ</div>
    <h1>{{ $domain->domain }}</h1>
    <p>หมดอายุ {{ $domain->expires_at?->format('d/m/Y') }}</p>
@endsection

@section('body')
    <p class="greeting">เรียนคุณ <strong>{{ $domain->user?->name ?? 'ลูกค้า' }}</strong></p>
    <p style="color: #a8b4d4; font-size: 14px; margin-bottom: 20px;">
        โดเมน <strong style="color:#60a5fa;">{{ $domain->domain }}</strong> ของคุณเปิดต่ออายุอัตโนมัติไว้
        {{-- Read from settings, not written out: an operator who moves the
             charge day must not leave this sentence promising the old one. --}}
        เราจะตัดเงินจากกระเป๋าเงินในเว็บ <strong>ก่อนหมดอายุ {{ \App\Support\DomainReminders::chargeDays() }} วัน</strong> ตามที่แจ้งไว้
        อีเมลฉบับนี้คือการแจ้งล่วงหน้าก่อนตัดเงินครับ
    </p>

    <div class="card">
        <div class="card-title">รายละเอียดการต่ออายุ</div>
        <div class="info-row">
            <span class="info-label">ค่าต่ออายุ 1 ปี</span>
            <span class="info-value" style="color: #60a5fa; font-size: 18px;"><strong>{{ number_format($price, 0) }} บาท</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">ยอดในกระเป๋าเงินตอนนี้</span>
            <span class="info-value" style="color: {{ $walletBalance < $price ? '#f87171' : '#34d399' }};">
                {{ number_format($walletBalance, 0) }} บาท
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">โดเมนหมดอายุ</span>
            <span class="info-value">{{ $domain->expires_at?->format('d/m/Y') }}</span>
        </div>
    </div>

    @if ($walletBalance < $price)
        <div class="warning-box mt-4">
            <p style="margin: 0; font-size: 13px; line-height: 1.7;">
                <strong>ยอดเงินยังไม่พอ</strong> — ขาดอีก {{ number_format($price - $walletBalance, 0) }} บาท
                ถ้าถึงกำหนดแล้วเงินยังไม่พอ เราจะยังไม่ตัดเงินและจะพยายามใหม่ทุกวันจนกว่าจะถึงวันหมดอายุ
                กรุณาเติมเงินก่อนเพื่อไม่ให้โดเมนหลุดครับ
            </p>
        </div>
    @endif

    <div class="text-center mt-6">
        <a href="{{ route('customer.domains.show', $domain->id) }}" class="btn btn-primary btn-block">เปิดหน้าโดเมนของฉัน</a>
    </div>

    <p style="color: #7c8aa8; font-size: 13px; line-height: 1.7; margin-top: 16px;">
        ไม่อยากต่ออายุ? เข้าหน้าโดเมนแล้วกด <strong>ปิดต่ออายุอัตโนมัติ</strong> ได้เลย เราจะไม่ตัดเงิน
        และโดเมนจะหมดอายุตามกำหนด · ต่ออายุเองตอนนี้ก็ได้จากปุ่มในหน้าเดียวกัน
    </p>
@endsection
