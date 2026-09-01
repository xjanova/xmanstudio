@extends('emails.partials.email-base')

@section('title', 'ตั้งรหัสผ่านใหม่ / Reset your password')

@section('header')
    <div class="email-header-badge badge-order">ความปลอดภัย / Security</div>
    <h1>ตั้งรหัสผ่านใหม่</h1>
    <p>Reset your password</p>
@endsection

@section('body')
    <p class="greeting">สวัสดีครับ <strong>{{ $userName }}</strong></p>

    <p style="color: #a8b4d4; font-size: 14px; margin-bottom: 6px;">
        เราได้รับคำขอตั้งรหัสผ่านใหม่สำหรับบัญชี {{ config('app.name') }} ของคุณ
        กดปุ่มด้านล่างเพื่อตั้งรหัสผ่านใหม่
    </p>
    <p style="color: #6b7799; font-size: 13px; margin-bottom: 24px;">
        We received a request to reset the password for your {{ config('app.name') }} account.
        Use the button below to choose a new one.
    </p>

    <div class="text-center" style="margin-bottom: 24px;">
        <a href="{{ $resetUrl }}" class="btn btn-primary"
           style="display: inline-block; padding: 13px 30px; border-radius: 12px; font-size: 15px; font-weight: 600; text-decoration: none; text-align: center; background-color: #7c5cf6; background-image: linear-gradient(135deg, #22d3ee 0%, #8b5cf6 55%, #a855f7 100%); color: #ffffff;">
            ตั้งรหัสผ่านใหม่ / Reset password
        </a>
    </div>

    <div class="warning-box" style="background-color: #2a1a12; border: 1px solid #5c3a1e; border-left: 3px solid #ffd479; border-radius: 12px; padding: 16px 18px; font-size: 14px; color: #f3d7ae; margin-bottom: 20px;">
        ลิงก์นี้ใช้ได้ภายใน <strong>{{ $expiresInMinutes }} นาที</strong> เท่านั้น และใช้ได้ครั้งเดียว<br>
        <span style="color: #c9a97e; font-size: 13px;">This link expires in {{ $expiresInMinutes }} minutes and can only be used once.</span>
    </div>

    <p style="color: #a8b4d4; font-size: 14px; margin-bottom: 4px;">
        หากคุณไม่ได้เป็นผู้ขอตั้งรหัสผ่านใหม่ ไม่ต้องทำอะไรทั้งสิ้น รหัสผ่านเดิมของคุณยังใช้งานได้ตามปกติ
    </p>
    <p style="color: #6b7799; font-size: 13px; margin-bottom: 22px;">
        If you did not request a password reset, no action is required — your current password still works.
    </p>

    <div class="card" style="background-color: #0e1428; border: 1px solid #1c2545; border-radius: 14px; padding: 22px; margin-bottom: 0;">
        <div class="card-title" style="font-size: 12px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: #8b5cf6;">
            ปุ่มกดไม่ได้? / Button not working?
        </div>
        <p style="color: #a8b4d4; font-size: 13px; margin-top: 10px;">
            คัดลอกลิงก์นี้ไปวางในเบราว์เซอร์ / Copy this link into your browser:
        </p>
        <p style="margin-top: 8px; font-size: 12px; word-break: break-all; line-height: 1.6;">
            <a href="{{ $resetUrl }}" style="color: #22d3ee; text-decoration: none;">{{ $resetUrl }}</a>
        </p>
    </div>
@endsection
