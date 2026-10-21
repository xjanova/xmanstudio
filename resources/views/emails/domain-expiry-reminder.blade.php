{{--
    The reminder for a domain that will NOT renew itself.

    Deliberately different from domain-renewal-notice: that one says "we are
    about to take your money", this one says "nobody is going to do this for
    you". The whole point is that the customer has to act, so the action is
    the loudest thing on the page and the deadline is a date, not a feeling.

    Vars: $domain, $price, $walletBalance, $daysLeft
--}}
@extends('emails.partials.email-base')

@section('title', 'โดเมน ' . $domain->domain . ' ใกล้หมดอายุ')

@php
    $urgent = $daysLeft <= 7;
    $covers = $price > 0 && $walletBalance >= $price;
@endphp

@section('header')
    <div class="email-header-badge {{ $urgent ? 'badge-test' : 'badge-order' }}">
        {{ $urgent ? 'ใกล้หมดอายุมาก' : 'แจ้งเตือนล่วงหน้า' }}
    </div>
    <h1>{{ $domain->domain }}</h1>
    <p>หมดอายุ {{ $domain->expires_at?->format('d/m/Y') }} · เหลืออีก {{ $daysLeft }} วัน</p>
@endsection

@section('body')
    <p class="greeting">เรียนคุณ <strong>{{ $domain->user?->name ?? 'ลูกค้า' }}</strong></p>

    <p style="color: #a8b4d4; font-size: 14px; margin-bottom: 20px;">
        โดเมน <strong style="color:#60a5fa;">{{ $domain->domain }}</strong> จะหมดอายุในอีก
        <strong style="color: {{ $urgent ? '#f87171' : '#fbbf24' }};">{{ $daysLeft }} วัน</strong>
        และโดเมนนี้<strong>ไม่ได้เปิดต่ออายุอัตโนมัติไว้</strong> —
        หมายความว่าถ้าคุณไม่ต่ออายุเอง โดเมนจะหยุดทำงานเมื่อถึงวันหมดอายุ
        เว็บไซต์และอีเมลที่ใช้ชื่อนี้จะใช้งานไม่ได้ทั้งหมด
    </p>

    <div class="card">
        <div class="card-title">สิ่งที่ต้องทำ</div>
        <div class="info-row">
            <span class="info-label">โดเมนหมดอายุ</span>
            <span class="info-value"><strong>{{ $domain->expires_at?->format('d/m/Y') }}</strong></span>
        </div>
        @if($price > 0)
            <div class="info-row">
                <span class="info-label">ค่าต่ออายุ 1 ปี</span>
                <span class="info-value" style="color: #60a5fa; font-size: 18px;">
                    <strong>{{ number_format($price, 0) }} บาท</strong>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">ยอดในกระเป๋าเงิน</span>
                <span class="info-value" style="color: {{ $covers ? '#34d399' : '#f87171' }};">
                    {{ number_format($walletBalance, 0) }} บาท
                    {{ $covers ? '· พอต่ออายุได้ทันที' : '' }}
                </span>
            </div>
        @endif
    </div>

    @if($price > 0 && ! $covers)
        <div class="warning-box mt-4">
            <p style="margin: 0; font-size: 13px; line-height: 1.7;">
                <strong>เงินในกระเป๋ายังไม่พอ</strong> — ขาดอีก {{ number_format($price - $walletBalance, 0) }} บาท
                เติมเงินก่อนแล้วค่อยกดต่ออายุ หรือจ่ายด้วยวิธีอื่นในหน้าโดเมนก็ได้ครับ
            </p>
        </div>
    @endif

    @if($urgent)
        <div class="warning-box mt-4">
            <p style="margin: 0; font-size: 13px; line-height: 1.7;">
                <strong>โดเมนที่หลุดไปแล้วอาจไม่ได้คืน</strong> — เมื่อพ้นกำหนด ชื่อนี้จะเข้าสู่ช่วงไถ่ถอน
                ซึ่งมีค่าธรรมเนียมสูงกว่าค่าต่ออายุปกติมาก และถ้าพ้นช่วงนั้นไปอีก
                ใครก็สามารถจดชื่อนี้ต่อจากคุณได้
            </p>
        </div>
    @endif

    <div class="text-center mt-6">
        <a href="{{ route('customer.domains.show', $domain->id) }}" class="btn btn-primary btn-block">
            ต่ออายุโดเมนนี้
        </a>
    </div>

    <p style="color: #7c8aa8; font-size: 13px; line-height: 1.7; margin-top: 16px;">
        อยากให้ระบบต่ออายุให้เองทุกปี? เปิด <strong>ต่ออายุอัตโนมัติ</strong> ในหน้าโดเมน
        แล้วเราจะตัดจากกระเป๋าเงินให้ก่อนหมดอายุ พร้อมแจ้งล่วงหน้าทุกครั้ง
        คุณจะได้ไม่ต้องคอยจำวันเอง
    </p>
@endsection
