@extends('emails.partials.email-base')

@section('title', 'ใบเสนอราคา ' . $quotation->displayNumber())

@section('header')
    <div class="email-header-badge badge-order">ใบเสนอราคา</div>
    <h1>{{ $quotation->displayNumber() }}</h1>
    <p>{{ $doc['service']['name_th'] ?: $quotation->service_name }}</p>
@endsection

@section('body')
    <p class="greeting">เรียนคุณ <strong>{{ $quotation->customer_name }}</strong></p>
    <p style="color: #a8b4d4; font-size: 14px; margin-bottom: 20px;">
        ขอบคุณที่สนใจบริการของเรา ใบเสนอราคาแนบมาพร้อมอีเมลฉบับนี้แล้ว
        และเปิดดูบนเว็บได้จากปุ่มด้านล่าง
    </p>

    <div class="card">
        <div class="card-title">สรุปยอด</div>

        <div class="info-row">
            <span class="info-label">รวมเป็นเงิน</span>
            <span class="info-value">{{ number_format($doc['subtotal'], 2) }} บาท</span>
        </div>

        @if ($doc['discount'] > 0)
            <div class="info-row">
                <span class="info-label">ส่วนลด {{ $doc['discount_percent'] }}%</span>
                <span class="info-value" style="color: #10b981;">−{{ number_format($doc['discount'], 2) }} บาท</span>
            </div>
        @endif

        @if ($doc['rush_fee'] > 0)
            <div class="info-row">
                <span class="info-label">ค่าเร่งงาน</span>
                <span class="info-value">{{ number_format($doc['rush_fee'], 2) }} บาท</span>
            </div>
        @endif

        {{-- โหมด "ไม่คิด VAT" ต้องไม่มีบรรทัดภาษีเลย ไม่ใช่แสดงเป็นศูนย์ --}}
        @if ($doc['vat_mode'] !== 'none')
            @if ($doc['vat_mode'] === 'inclusive')
                <div class="info-row">
                    <span class="info-label">มูลค่าสินค้า/บริการ</span>
                    <span class="info-value">{{ number_format($doc['amount_before_vat'], 2) }} บาท</span>
                </div>
            @endif
            <div class="info-row">
                <span class="info-label">ภาษีมูลค่าเพิ่ม {{ rtrim(rtrim(number_format($doc['vat_rate'], 2), '0'), '.') }}%</span>
                <span class="info-value">{{ number_format($doc['vat'], 2) }} บาท</span>
            </div>
        @endif

        <div class="info-row">
            <span class="info-label"><strong>จำนวนเงินรวมทั้งสิ้น</strong></span>
            <span class="info-value" style="color: #60a5fa; font-size: 18px;"><strong>{{ number_format($doc['grand_total'], 2) }} บาท</strong></span>
        </div>

        <div class="info-row">
            <span class="info-label">ตัวอักษร</span>
            <span class="info-value">({{ $doc['grand_total_words'] }})</span>
        </div>

        @if ($doc['withholding_amount'] > 0)
            <div class="info-row">
                <span class="info-label">หัก ณ ที่จ่าย {{ rtrim(rtrim(number_format($doc['withholding_pct'], 2), '0'), '.') }}%</span>
                <span class="info-value">−{{ number_format($doc['withholding_amount'], 2) }} บาท</span>
            </div>
            <div class="info-row">
                <span class="info-label">ยอดโอนสุทธิ</span>
                <span class="info-value">{{ number_format($doc['net_payable'], 2) }} บาท</span>
            </div>
        @endif

        <div class="info-row">
            <span class="info-label">ยืนราคาถึง</span>
            <span class="info-value">{{ $doc['valid_until'] }}</span>
        </div>
    </div>

    @if ($doc['public_url'])
        <div class="text-center mt-6">
            <a href="{{ $doc['public_url'] }}" class="btn btn-primary btn-block">เปิดใบเสนอราคา</a>
        </div>
        <p style="color: #7c8aa8; font-size: 13px; line-height: 1.7; margin-top: 16px;">
            ในหน้านั้นกด <strong>ตอบรับ</strong> เพื่อให้เราเริ่มงานได้เลย
            หรือกด <strong>ขอต่อรอง</strong> แล้วพิมพ์บอกว่าอยากปรับตรงไหน
            ทีมงานจะเสนอกลับให้ตรงจุด
        </p>
    @endif

    <div class="warning-box mt-4">
        <p style="margin: 0; font-size: 13px; line-height: 1.7;">
            ลิงก์นี้เป็นของใบเสนอราคาฉบับนี้โดยเฉพาะ กรุณาอย่าส่งต่อให้คนนอกองค์กร
            เพราะเปิดแล้วเห็นราคาและข้อมูลติดต่อของคุณ
        </p>
    </div>
@endsection
