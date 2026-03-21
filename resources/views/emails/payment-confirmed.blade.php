@extends('emails.partials.email-base')

@section('title', 'ชำระเงินสำเร็จ - #' . $order->order_number)

@section('header')
    <div style="font-size: 48px; margin-bottom: 8px;">&#10003;</div>
    <div class="email-header-badge badge-success">ชำระเงินสำเร็จ</div>
    <h1>การชำระเงินได้รับการยืนยัน</h1>
    <p>#{{ $order->order_number }}</p>
@endsection

@section('body')
    <p class="greeting">สวัสดีคุณ <strong>{{ $order->user->name ?? $order->customer_name ?? 'ลูกค้า' }}</strong></p>
    <p style="color: #4b5563; font-size: 14px; margin-bottom: 20px;">
        เรายืนยันการรับชำระเงินเรียบร้อยแล้ว ขอบคุณที่ไว้วางใจใช้บริการของเรา
    </p>

    {{-- Payment Info --}}
    <div class="card">
        <div class="card-title">รายละเอียดการชำระเงิน</div>
        <div class="info-row">
            <span class="info-label">หมายเลขคำสั่งซื้อ</span>
            <span class="info-value">#{{ $order->order_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">วันที่ชำระ</span>
            <span class="info-value">{{ ($order->paid_at ?? $order->updated_at)->timezone('Asia/Bangkok')->format('d/m/Y H:i') }} น.</span>
        </div>
        <div class="info-row">
            <span class="info-label">จำนวนเงิน</span>
            <span class="info-value" style="color: #10b981; font-size: 18px;">฿{{ number_format($order->total, 2) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">วิธีการชำระ</span>
            <span class="info-value">
                @switch($order->payment_method)
                    @case('promptpay') พร้อมเพย์ @break
                    @case('bank_transfer') โอนเงินผ่านธนาคาร @break
                    @case('credit_card') บัตรเครดิต @break
                    @case('stripe') Stripe @break
                    @case('wallet') กระเป๋าเงิน @break
                    @default {{ $order->payment_method }}
                @endswitch
            </span>
        </div>
    </div>

    {{-- Items --}}
    <table class="order-table">
        <thead>
            <tr>
                <th>สินค้า</th>
                <th style="text-align: center;">จำนวน</th>
                <th style="text-align: right;">ราคา</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name ?? $item->product_name }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">฿{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- License Keys --}}
    @php
        $licenseKeys = \App\Models\LicenseKey::where('order_id', $order->id)
            ->where('status', 'active')
            ->with('product')
            ->get();
    @endphp

    @if($licenseKeys->count() > 0)
    <div class="license-box">
        <h3>License Key ของคุณ</h3>
        <p style="font-size: 13px; color: #4b5563; margin-bottom: 16px;">กรุณาเก็บรักษา License Key เหล่านี้ไว้อย่างดี</p>

        @foreach($licenseKeys as $license)
        <div style="margin: 12px 0;">
            <div style="font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                {{ $license->product->name ?? 'ผลิตภัณฑ์' }}
            </div>
            <div class="license-key-display">{{ $license->license_key }}</div>
            <div class="license-meta">
                ประเภท: {{ ucfirst($license->license_type) }}
                @if($license->expires_at)
                    &bull; หมดอายุ: {{ $license->expires_at->timezone('Asia/Bangkok')->format('d/m/Y') }}
                @else
                    &bull; ตลอดชีพ
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Action Buttons --}}
    <div class="text-center mt-6" style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
        <a href="{{ config('app.url') }}/my-account/orders/{{ $order->id }}" class="btn btn-primary">
            ดูรายละเอียดคำสั่งซื้อ
        </a>
        @if($licenseKeys->count() > 0)
        <a href="{{ config('app.url') }}/my-account/licenses" class="btn btn-secondary">
            จัดการ License Keys
        </a>
        @endif
    </div>

    @if($licenseKeys->count() > 0)
    <div style="background: #f3f4f6; padding: 16px; border-radius: 8px; font-size: 13px; color: #6b7280; margin-top: 24px; text-align: center;">
        ใบเสร็จรับเงินแนบมาพร้อมอีเมลนี้ หรือดาวน์โหลดได้ที่หน้าบัญชีของคุณ
    </div>
    @endif
@endsection
