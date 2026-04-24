@extends('layouts.xdreamer')
@section('title', ($mode ?? 'login') === 'login' ? 'เข้าสู่ระบบ · X-DREAMER' : 'สมัครสมาชิก · X-DREAMER')
@section('meta_description', 'เข้าสู่ปราสาทแห่งความคิดของคุณบน X-DREAMER')

@php
    $hueShift = 70;
    $mode = $mode ?? 'login';
    $page = $mode;
    $isLogin = $mode === 'login';
@endphp

@section('content')
<div style="padding-top:110px;min-height:100vh;display:grid;place-items:center;padding:110px 24px 40px;">
    <div style="width:100%;max-width:440px;padding:40px;border-radius:24px;
        background:rgba(15,23,42,0.6);border:1px solid rgba(255,255,255,0.08);
        backdrop-filter:blur(18px);box-shadow:0 40px 80px -20px rgba(0,0,0,0.7);">

        <div style="text-align:center;margin-bottom:32px;">
            <img src="{{ asset('images/xdreamer/logo.png') }}" alt="X-DREAMER" style="
                width:64px;height:64px;border-radius:16px;margin:0 auto 16px;display:block;
                box-shadow:0 0 40px hsla({{ 270+$hueShift }},70%,50%,0.5);object-fit:cover;">
            <div style="font-size:22px;color:#fff;font-weight:300;">
                {{ $isLogin ? 'ยินดีต้อนรับกลับ' : 'เริ่มทอความฝันแรก' }}
            </div>
            <div style="font-size:13px;color:#94a3b8;margin-top:6px;">
                {{ $isLogin ? 'เข้าสู่ปราสาทของคุณ' : 'ฟรีตลอดชีพ · 50 งาน/เดือน' }}
            </div>
        </div>

        @if(session('status'))
            <div style="padding:10px 14px;border-radius:10px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86efac;font-size:13px;margin-bottom:14px;">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div style="padding:10px 14px;border-radius:10px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;font-size:13px;margin-bottom:14px;">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ $isLogin ? route('login') : route('register') }}" style="display:flex;flex-direction:column;gap:12px;">
            @csrf
            @if(!$isLogin)
            <div>
                <div style="font-size:11px;color:#94a3b8;margin-bottom:6px;">ชื่อที่จะแสดง</div>
                <input name="name" type="text" placeholder="ฝันทิพย์" required value="{{ old('name') }}"
                    style="width:100%;padding:12px 14px;border-radius:10px;background:rgba(2,6,23,0.5);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:14px;font-family:inherit;outline:none;">
            </div>
            @endif
            <div>
                <div style="font-size:11px;color:#94a3b8;margin-bottom:6px;">อีเมล</div>
                <input name="email" type="email" placeholder="you@example.com" required value="{{ old('email') }}"
                    style="width:100%;padding:12px 14px;border-radius:10px;background:rgba(2,6,23,0.5);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:14px;font-family:inherit;outline:none;">
            </div>
            <div>
                <div style="font-size:11px;color:#94a3b8;margin-bottom:6px;">รหัสผ่าน</div>
                <input name="password" type="password" placeholder="••••••••" required
                    style="width:100%;padding:12px 14px;border-radius:10px;background:rgba(2,6,23,0.5);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:14px;font-family:inherit;outline:none;">
            </div>
            @if(!$isLogin)
            <div>
                <div style="font-size:11px;color:#94a3b8;margin-bottom:6px;">ยืนยันรหัสผ่าน</div>
                <input name="password_confirmation" type="password" placeholder="••••••••" required
                    style="width:100%;padding:12px 14px;border-radius:10px;background:rgba(2,6,23,0.5);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:14px;font-family:inherit;outline:none;">
            </div>
            @endif

            @if($isLogin)
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#94a3b8;margin-top:4px;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="checkbox" name="remember" style="accent-color:#8b5cf6;"> จดจำฉันไว้
                </label>
                @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" style="color:#a5f3fc;text-decoration:none;">ลืมรหัสผ่าน?</a>
                @endif
            </div>
            @endif

            <button type="submit" style="
                margin-top:12px;padding:14px;border-radius:12px;
                background:linear-gradient(135deg, hsl({{ 160+$hueShift }},70%,50%), hsl({{ 270+$hueShift }},70%,55%));
                color:#fff;border:none;font-size:14px;font-weight:600;cursor:pointer;">
                {{ $isLogin ? 'เข้าสู่ระบบ' : 'สร้างบัญชี' }}
            </button>
        </form>

        <div style="display:flex;align-items:center;gap:12px;margin:20px 0 8px;color:#64748b;font-size:11px;">
            <div style="flex:1;height:1px;background:rgba(255,255,255,0.08);"></div>
            หรือ
            <div style="flex:1;height:1px;background:rgba(255,255,255,0.08);"></div>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach(['Continue with Google','Continue with Apple','Continue with LINE'] as $provider)
            <button type="button" disabled title="ยังไม่เปิดให้ใช้งาน" style="padding:12px;border-radius:12px;background:rgba(255,255,255,0.04);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);font-size:13px;cursor:not-allowed;opacity:0.7;">{{ $provider }}</button>
            @endforeach
        </div>

        <div style="text-align:center;margin-top:20px;font-size:13px;color:#94a3b8;">
            {{ $isLogin ? 'ยังไม่มีบัญชี? ' : 'มีบัญชีแล้ว? ' }}
            <a href="{{ $isLogin ? route('xdreamer.signup') : route('xdreamer.login') }}" style="color:#a5f3fc;text-decoration:none;">
                {{ $isLogin ? 'สมัครฟรี' : 'เข้าสู่ระบบ' }}
            </a>
        </div>
    </div>
</div>
@endsection
