@extends('emails.partials.email-base')

@section('title', 'ใบเสนอราคา ' . $quotation->displayNumber() . ' ใกล้หมดอายุ')

@section('header')
    <div class="email-header-badge badge-order">ใกล้หมดอายุ</div>
    <h1>{{ $quotation->displayNumber() }}</h1>
    <p>{{ $quotation->service_name }}</p>
@endsection

@section('body')
    <p class="greeting">เรียนคุณ <strong>{{ $quotation->customer_name }}</strong></p>
    <p style="color: #a8b4d4; font-size: 14px; margin-bottom: 20px;">
        @if ($daysLeft <= 0)
            ใบเสนอราคาฉบับนี้ยืนราคาถึงวันนี้เป็นวันสุดท้ายครับ
        @else
            ใบเสนอราคาฉบับนี้ยืนราคาอีก <strong style="color:#fbbf24;">{{ $daysLeft }} วัน</strong>
            (ถึง {{ $quotation->valid_until->format('d/m/Y') }})
        @endif
        ถ้ายังสนใจอยู่แต่ติดตรงไหน บอกเรามาได้เลย ปรับให้ได้ครับ
    </p>

    <div class="card">
        <div class="card-title">สรุปยอด</div>
        <div class="info-row">
            <span class="info-label">งานที่เสนอ</span>
            <span class="info-value">{{ $quotation->service_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label"><strong>จำนวนเงินรวมทั้งสิ้น</strong></span>
            <span class="info-value" style="color: #60a5fa; font-size: 18px;"><strong>{{ number_format((float) $quotation->grand_total, 2) }} บาท</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">ยืนราคาถึง</span>
            <span class="info-value">{{ $quotation->valid_until->format('d/m/Y') }}</span>
        </div>
    </div>

    @if ($publicUrl)
        <div class="text-center mt-6">
            <a href="{{ $publicUrl }}" class="btn btn-primary btn-block">เปิดใบเสนอราคา</a>
        </div>
        <p style="color: #7c8aa8; font-size: 13px; line-height: 1.7; margin-top: 16px;">
            กด <strong>ตอบรับ</strong> เพื่อให้เราเริ่มงานได้เลย
            หรือกด <strong>ขอต่อรอง</strong> แล้วพิมพ์บอกว่าอยากปรับตรงไหน
            ทีมงานจะเสนอกลับให้ตรงจุด
        </p>
    @endif

    <p style="color: #7c8aa8; font-size: 13px; line-height: 1.7; margin-top: 18px;">
        เราจะเตือนเรื่องนี้แค่ครั้งนี้ครั้งเดียวครับ หลังจากนี้ไม่มีอีเมลตามอีก
    </p>
@endsection
