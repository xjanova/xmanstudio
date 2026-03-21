@extends('emails.partials.email-base')

@section('title', $action === 'paid' ? 'คอมมิชชันได้รับการอนุมัติ' : 'คอมมิชชันถูกปฏิเสธ')

@section('header')
    @if($action === 'paid')
        <div style="font-size: 48px; margin-bottom: 8px;">&#10003;</div>
        <div class="email-header-badge badge-success">อนุมัติแล้ว</div>
        <h1>คอมมิชชันได้รับการอนุมัติ</h1>
        <p>เงินได้ถูกโอนเข้ากระเป๋าเงินของคุณแล้ว</p>
    @else
        <div style="font-size: 48px; margin-bottom: 8px;">&#10007;</div>
        <div class="email-header-badge" style="background: #ef4444; color: #fff;">ถูกปฏิเสธ</div>
        <h1>คอมมิชชันถูกปฏิเสธ</h1>
        <p>กรุณาตรวจสอบรายละเอียด</p>
    @endif
@endsection

@section('body')
    <p class="greeting">สวัสดีคุณ <strong>{{ $commission->affiliate->user->name ?? 'Affiliate' }}</strong></p>

    @if($action === 'paid')
        <p style="color: #4b5563; font-size: 14px; margin-bottom: 20px;">
            ค่าคอมมิชชันของคุณจำนวน <strong style="color: #10b981;">&#3647;{{ number_format($commission->commission_amount, 2) }}</strong>
            ได้ถูกโอนเข้ากระเป๋าเงินเรียบร้อยแล้ว
        </p>
    @else
        <p style="color: #4b5563; font-size: 14px; margin-bottom: 20px;">
            ค่าคอมมิชชันของคุณจำนวน &#3647;{{ number_format($commission->commission_amount, 2) }}
            ถูกปฏิเสธ
        </p>
    @endif

    <div class="card">
        <div class="card-title">รายละเอียดคอมมิชชัน</div>
        <div class="info-row">
            <span class="info-label">ประเภท</span>
            <span class="info-value">{{ $commission->source_description ?? ucfirst($commission->source_type ?? 'order') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">ยอดสั่งซื้อ</span>
            <span class="info-value">&#3647;{{ number_format($commission->order_amount, 2) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">อัตราคอมมิชชัน</span>
            <span class="info-value">{{ $commission->commission_rate }}%</span>
        </div>
        <div class="info-row">
            <span class="info-label">จำนวนเงิน</span>
            <span class="info-value" style="color: {{ $action === 'paid' ? '#10b981' : '#ef4444' }}; font-size: 18px; font-weight: 700;">
                &#3647;{{ number_format($commission->commission_amount, 2) }}
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">สถานะ</span>
            <span class="info-value" style="color: {{ $action === 'paid' ? '#10b981' : '#ef4444' }};">
                {{ $action === 'paid' ? 'อนุมัติ — โอนเข้ากระเป๋าแล้ว' : 'ปฏิเสธ' }}
            </span>
        </div>
        @if($action === 'rejected' && $commission->admin_note)
        <div class="info-row">
            <span class="info-label">หมายเหตุ</span>
            <span class="info-value">{{ $commission->admin_note }}</span>
        </div>
        @endif
    </div>

    @if($action === 'paid')
    <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 16px; text-align: center; margin: 20px 0;">
        <p style="font-size: 14px; color: #166534; font-weight: 600;">
            ยอดเงินในกระเป๋า: &#3647;{{ number_format($commission->affiliate->user->wallet->balance ?? 0, 2) }}
        </p>
    </div>
    @endif

    <div class="text-center mt-6">
        <a href="{{ config('app.url') }}/customer/affiliate" class="btn {{ $action === 'paid' ? 'btn-primary' : 'btn-secondary' }}">
            ดูแดชบอร์ด Affiliate
        </a>
    </div>
@endsection
