@extends('emails.partials.email-base')

@section('title', '[ติดต่อเรา] ' . $subjectLine)

@section('header')
    <div class="email-header-badge badge-order">ข้อความจากหน้าติดต่อเรา</div>
    <h1>{{ $subjectLine }}</h1>
    <p>{{ $name }}</p>
@endsection

@section('body')
    <p class="greeting">มีข้อความใหม่จากฟอร์ม <strong>ติดต่อเรา</strong></p>
    <p style="color: #4b5563; font-size: 14px; margin-bottom: 20px;">
        กด Reply ได้เลย อีเมลจะตอบกลับไปหาผู้ส่งโดยตรง
    </p>

    {{-- Sender --}}
    <div class="card">
        <div class="card-title">ผู้ส่ง</div>
        <div class="info-row">
            <span class="info-label">ชื่อ</span>
            <span class="info-value">{{ $name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">อีเมล</span>
            <span class="info-value">{{ $email }}</span>
        </div>
        @if($phone)
            <div class="info-row">
                <span class="info-label">โทรศัพท์</span>
                <span class="info-value">{{ $phone }}</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">เรื่อง</span>
            <span class="info-value">{{ $subjectLine }}</span>
        </div>
    </div>

    {{-- Message --}}
    <div class="card">
        <div class="card-title">ข้อความ</div>
        <div style="color: #1f2937; font-size: 14px; line-height: 1.7; white-space: pre-wrap;">{{ $body }}</div>
    </div>
@endsection
