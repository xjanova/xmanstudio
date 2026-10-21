@extends('game-support.layout')
@section('title', $mode === 'login' ? 'เข้าสู่ระบบ XMAN ID' : 'สมัคร XMAN ID')
@section('crumb', $mode === 'login' ? 'เข้าสู่ระบบ' : 'สมัครสมาชิก')
@section('content')
<div class="gs-auth">
    <section class="gs-auth-art" style="--art: url('{{ asset('images/gameshub/nova-gate.webp') }}')" aria-hidden="true">
        <span class="gs-tag">XMAN ID</span>
        <p>หนึ่งบัญชีสำหรับทุกเกมของ XMAN Studio — สนับสนุนเกม รับไอเท็ม รีวิว โหวต และให้ดาว</p>
    </section>
    <section class="gs-card gs-auth-card">
        @if($mode === 'login')
            <span class="gs-tag">WELCOME BACK</span>
            <h1>เข้าสู่ระบบ XMAN ID</h1>
            <form method="post" action="{{ route('login') }}" class="gs-form" onsubmit="this.querySelector('button[type=submit]').disabled = true">
                @csrf
                <label>อีเมล<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
                <label>รหัสผ่าน<input type="password" name="password" required autocomplete="current-password"></label>
                <div class="gs-auth-row">
                    <label class="gs-check"><input type="checkbox" name="remember" value="1"> จดจำฉันไว้</label>
                    @if(Route::has('password.request'))<a href="{{ route('password.request') }}">ลืมรหัสผ่าน?</a>@endif
                </div>
                <x-turnstile section="login" />
                <button type="submit" class="gs-button">เข้าสู่ระบบ</button>
            </form>
            <div class="gs-social"><x-social-login mode="login" :dark="true" /></div>
            <p class="gs-muted">ยังไม่มีบัญชี? <a href="{{ route('game-support.register') }}">สมัคร XMAN ID</a></p>
        @else
            <span class="gs-tag">JOIN THE CREW</span>
            <h1>สมัคร XMAN ID</h1>
            <form method="post" action="{{ route('register') }}" class="gs-form" onsubmit="this.querySelector('button[type=submit]').disabled = true">
                @csrf
                <label>ชื่อที่ใช้ในชุมชน<input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="nickname"></label>
                <label>อีเมล<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
                <label>รหัสผ่าน<input type="password" name="password" required autocomplete="new-password"></label>
                <label>ยืนยันรหัสผ่าน<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
                <x-turnstile section="register" />
                <button type="submit" class="gs-button">สมัครและกลับไปที่ชุมชน</button>
            </form>
            <div class="gs-social"><x-social-login mode="register" :dark="true" /></div>
            <p class="gs-muted">มีบัญชีแล้ว? <a href="{{ route('game-support.login') }}">เข้าสู่ระบบ</a></p>
        @endif
        <p class="gs-muted">บัญชีเดียวกับ xman4289.com · ชื่อในรีวิวจะแสดงแบบย่อ</p>
    </section>
</div>
@endsection
