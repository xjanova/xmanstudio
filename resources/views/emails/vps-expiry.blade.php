@extends('emails.partials.email-base')

@section('title', 'เซิร์ฟเวอร์ ' . $server->hostname)

@section('header')
    <div class="email-header-badge badge-order">
        @if($kind === 'expired') หมดอายุแล้ว @elseif($kind === 'short') ยอดเงินไม่พอต่ออายุ @else ใกล้หมดอายุ @endif
    </div>
    <h1>{{ $server->hostname }}</h1>
    <p>{{ $server->plan_name }} · หมดอายุ {{ $server->expires_at?->format('d/m/Y') }}</p>
@endsection

@section('body')
    <p class="greeting">เรียนคุณ <strong>{{ $server->user?->name ?? 'ลูกค้า' }}</strong></p>

    <p style="color: #a8b4d4; font-size: 14px; margin-bottom: 20px; line-height: 1.7;">
        @if($kind === 'expired')
            เซิร์ฟเวอร์ <strong>{{ $server->hostname }}</strong> หมดอายุแล้วและจะถูกระงับการใช้งาน
            <strong>ยังต่ออายุได้</strong> จากหน้าเซิร์ฟเวอร์ — แต่ถ้าปล่อยไว้นาน ข้อมูลในเครื่องจะถูกลบถาวร
        @elseif($kind === 'short')
            ถึงกำหนดต่ออายุเซิร์ฟเวอร์ <strong>{{ $server->hostname }}</strong> แล้ว แต่ยอดในกระเป๋าเงินยังไม่พอ
            เราจะลองตัดเงินใหม่ทุกวันจนถึงวันหมดอายุ กรุณาเติมเงินเพื่อไม่ให้เครื่องหยุดทำงาน
        @else
            เซิร์ฟเวอร์ <strong>{{ $server->hostname }}</strong> ไม่ได้เปิดต่ออายุอัตโนมัติ และจะหมดอายุในอีก
            <strong>{{ max(0, (int) $server->daysUntilExpiry()) }} วัน</strong> — เมื่อหมดอายุ เครื่องจะหยุดทำงานและข้อมูลจะถูกลบในที่สุด
        @endif
    </p>

    <div class="card">
        <div class="card-title">รายละเอียด</div>
        @if($price > 0)
            <div class="info-row">
                <span class="info-label">ค่าต่ออายุ ({{ $server->periodLabel() }})</span>
                <span class="info-value" style="color: #60a5fa; font-size: 18px;"><strong>{{ number_format($price, 0) }} บาท</strong></span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">ยอดในกระเป๋าเงิน</span>
            <span class="info-value" style="color: {{ $walletBalance < $price ? '#f87171' : '#34d399' }};">{{ number_format($walletBalance, 0) }} บาท</span>
        </div>
        <div class="info-row">
            <span class="info-label">IP</span>
            <span class="info-value" style="font-family: monospace;">{{ $server->ipv4 ?? '-' }}</span>
        </div>
    </div>

    <div class="text-center mt-6">
        <a href="{{ route('customer.vps.show', $server->id) }}" class="btn btn-primary btn-block">ต่ออายุ / เปิดหน้าเซิร์ฟเวอร์</a>
    </div>

    @if($walletBalance < $price)
        <div class="text-center mt-4">
            <a href="{{ route('user.wallet.index') }}" class="btn btn-block">เติมเงินเข้ากระเป๋า</a>
        </div>
    @endif
@endsection
