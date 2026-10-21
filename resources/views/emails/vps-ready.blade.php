@extends('emails.partials.email-base')

@section('title', 'เซิร์ฟเวอร์พร้อมใช้งาน')

@section('header')
    <div class="email-header-badge badge-order">VPS พร้อมใช้งาน</div>
    <h1>{{ $server->hostname }}</h1>
    <p>{{ $server->plan_name }} · {{ $server->data_center_name }}</p>
@endsection

@section('body')
    <p class="greeting">เรียนคุณ <strong>{{ $server->user?->name ?? 'ลูกค้า' }}</strong></p>
    <p style="color: #a8b4d4; font-size: 14px; margin-bottom: 20px;">
        เซิร์ฟเวอร์ของคุณติดตั้งเสร็จและเปิดใช้งานแล้ว เข้าใช้งานผ่าน SSH ได้ทันทีด้วยข้อมูลด้านล่าง
    </p>

    <div class="card">
        <div class="card-title">ข้อมูลเข้าใช้งาน</div>
        <div class="info-row">
            <span class="info-label">IP Address</span>
            <span class="info-value" style="color: #60a5fa; font-family: monospace;"><strong>{{ $server->ipv4 ?? 'กำลังกำหนด — ดูในหน้าเซิร์ฟเวอร์' }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">ผู้ใช้</span>
            <span class="info-value" style="font-family: monospace;">root</span>
        </div>
        <div class="info-row">
            <span class="info-label">รหัสผ่าน</span>
            <span class="info-value">
                @if($server->needs_password_reset)
                    <span style="color:#fbbf24;">กรุณาตั้งรหัสผ่านใหม่ในหน้าเซิร์ฟเวอร์ก่อนเข้าใช้งาน</span>
                @else
                    รหัสที่คุณตั้งตอนสั่งเช่า (เราไม่ส่งรหัสผ่านทางอีเมล)
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">ระบบปฏิบัติการ</span>
            <span class="info-value">{{ $server->template_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">ใช้งานได้ถึง</span>
            <span class="info-value">{{ $server->expires_at?->format('d/m/Y') }}</span>
        </div>
    </div>

    @if($server->ipv4)
        <div class="card" style="margin-top: 16px;">
            <div class="card-title">เชื่อมต่อด้วยคำสั่ง</div>
            <p style="font-family: monospace; font-size: 14px; color: #e2e8f0; margin: 0;">ssh root@{{ $server->ipv4 }}</p>
        </div>
    @endif

    <div class="text-center mt-6">
        <a href="{{ route('customer.vps.show', $server->id) }}" class="btn btn-primary btn-block">เปิดหน้าจัดการเซิร์ฟเวอร์</a>
    </div>

    <p style="color: #7c8aa8; font-size: 13px; line-height: 1.7; margin-top: 16px;">
        ในหน้าจัดการเซิร์ฟเวอร์ คุณเปิด/ปิด/รีสตาร์ทเครื่อง ตั้งรหัสผ่านใหม่ ติดตั้ง OS ใหม่ ทำสแนปช็อต
        และชี้โดเมนมาที่เครื่องนี้ได้เอง · ระบบสำรองข้อมูลอัตโนมัติรายสัปดาห์เปิดให้แล้ว
    </p>
@endsection
