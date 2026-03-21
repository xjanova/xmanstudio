@extends('emails.partials.email-base')

@section('title', 'ทดสอบอีเมล')

@section('header')
    <div class="email-header-badge badge-test">ทดสอบ</div>
    <h1>ทดสอบระบบอีเมล</h1>
    <p>อีเมลนี้ถูกส่งจากหน้าแอดมิน</p>
@endsection

@section('body')
    <p class="greeting">สวัสดีครับ <strong>Admin</strong></p>
    <p style="color: #4b5563; font-size: 14px; margin-bottom: 20px;">
        อีเมลนี้เป็นการทดสอบว่าระบบส่งอีเมลทำงานได้ปกติ
    </p>

    <div class="card">
        <div class="card-title">ข้อมูลการทดสอบ</div>
        <div class="info-row">
            <span class="info-label">ระบบ</span>
            <span class="info-value">Resend</span>
        </div>
        <div class="info-row">
            <span class="info-label">จากอีเมล</span>
            <span class="info-value">{{ config('mail.from.address') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">เวลาส่ง</span>
            <span class="info-value">{{ now()->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') }} น.</span>
        </div>
        <div class="info-row">
            <span class="info-label">สถานะ</span>
            <span class="info-value" style="color: #10b981;">ส่งสำเร็จ</span>
        </div>
    </div>

    <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 16px; text-align: center; margin-top: 20px;">
        <span style="font-size: 24px;">&#10003;</span>
        <p style="font-size: 14px; color: #166534; margin-top: 8px; font-weight: 600;">
            ระบบอีเมลทำงานปกติ
        </p>
    </div>
@endsection
