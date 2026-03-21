@extends('emails.partials.email-base')

@section('title', 'ยืนยันคำสั่งซื้อ #' . $order->order_number)

@section('header')
    <div class="email-header-badge badge-order">คำสั่งซื้อใหม่</div>
    <h1>ยืนยันคำสั่งซื้อ</h1>
    <p>#{{ $order->order_number }}</p>
@endsection

@section('body')
    <p class="greeting">สวัสดีคุณ <strong>{{ $order->user->name ?? $order->customer_name ?? 'ลูกค้า' }}</strong></p>
    <p style="color: #4b5563; font-size: 14px; margin-bottom: 20px;">
        ขอบคุณที่สั่งซื้อสินค้ากับเรา คำสั่งซื้อของคุณได้รับการบันทึกเรียบร้อยแล้ว
    </p>

    {{-- Order Info --}}
    <div class="card">
        <div class="card-title">รายละเอียดคำสั่งซื้อ</div>
        <div class="info-row">
            <span class="info-label">หมายเลข</span>
            <span class="info-value">#{{ $order->order_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">วันที่</span>
            <span class="info-value">{{ $order->created_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') }} น.</span>
        </div>
        <div class="info-row">
            <span class="info-label">สถานะ</span>
            <span class="info-value" style="color: {{ $order->payment_status === 'paid' ? '#10b981' : '#f59e0b' }};">
                {{ $order->payment_status === 'paid' ? 'ชำระเงินแล้ว' : 'รอชำระเงิน' }}
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">วิธีชำระเงิน</span>
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

    {{-- Items Table --}}
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
        <tfoot>
            @if($order->tax > 0)
            <tr>
                <td colspan="2" style="text-align: right; color: #6b7280;">ยอดรวมสินค้า</td>
                <td style="text-align: right; color: #6b7280;">฿{{ number_format($order->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: right; color: #6b7280;">ภาษี VAT 7%</td>
                <td style="text-align: right; color: #6b7280;">฿{{ number_format($order->tax, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="2" style="text-align: right;">ยอดรวมทั้งหมด</td>
                <td style="text-align: right;">฿{{ number_format($order->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($order->payment_status === 'pending')
    <div class="warning-box">
        <strong>รอดำเนินการชำระเงิน</strong><br>
        <span style="color: #92400e;">กรุณาชำระเงินจำนวน <strong>฿{{ number_format($order->total, 2) }}</strong> ตามวิธีการที่เลือกไว้</span>
    </div>
    @endif

    <div class="text-center mt-6">
        <a href="{{ config('app.url') }}/my-account/orders/{{ $order->id }}" class="btn btn-primary">
            ดูรายละเอียดคำสั่งซื้อ
        </a>
    </div>
@endsection
