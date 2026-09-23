@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ยืนยันตัวตน 2 ขั้น')
@section('page-title', 'ยืนยันตัวตน 2 ขั้น')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-800 via-indigo-800 to-violet-800 p-8 shadow-2xl">
        <h1 class="text-3xl font-bold text-white mb-2">ยืนยันตัวตน 2 ขั้น</h1>
        <p class="text-indigo-200 text-lg">รหัสผ่านอย่างเดียวเปิดหลังบ้านไม่ได้ ต้องมีรหัส 6 หลักจากแอปในมือถือคุณด้วย</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl font-medium">
            {{ $errors->first() }}
        </div>
    @endif

    @if($enabled)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 space-y-2">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">เปิดใช้งานอยู่</span>
                <span class="text-gray-600 dark:text-gray-300">รหัสสำรองที่ยังไม่ได้ใช้: <strong>{{ $remaining }}</strong> ชุด</span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">ทุกครั้งที่เข้าหลังบ้านใน session ใหม่ ระบบจะถามรหัส 6 หลักจากแอป · ทำมือถือหาย ใช้รหัสสำรองแทนได้ชุดละครั้ง</p>
        </div>

        @if($freshCodes)
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6">
                <h2 class="text-lg font-bold text-amber-800 dark:text-amber-200 mb-1">รหัสสำรอง — แสดงครั้งเดียวเท่านั้น</h2>
                <p class="text-sm text-amber-700 dark:text-amber-300 mb-4">จดหรือพิมพ์เก็บไว้ในที่ปลอดภัย (ไม่ใช่ในมือถือเครื่องเดียวกับแอป) · แต่ละรหัสใช้ได้ครั้งเดียว</p>
                <div class="grid grid-cols-2 gap-2 font-mono text-lg">
                    @foreach($freshCodes as $code)
                        <div class="bg-white dark:bg-gray-800 rounded-lg px-4 py-2 text-center text-gray-900 dark:text-white">{{ $code }}</div>
                    @endforeach
                </div>
                <a href="{{ route('admin.dashboard') }}" class="inline-block mt-4 px-5 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">เก็บแล้ว ไปที่หลังบ้าน</a>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 space-y-6">
            <form method="POST" action="{{ route('admin.security.two-factor.recovery-codes') }}"
                  onsubmit="return confirm('สร้างรหัสสำรองชุดใหม่? ชุดเดิมที่เก็บไว้จะใช้ไม่ได้ทันที')">
                @csrf
                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">สร้างรหัสสำรองชุดใหม่</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ใช้เมื่อรหัสสำรองเหลือน้อย หรือสงสัยว่ามีคนเห็นชุดเดิม</p>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gray-900 dark:bg-gray-700 text-white font-semibold hover:bg-gray-700">สร้างรหัสสำรองใหม่</button>
            </form>

            <form method="POST" action="{{ route('admin.security.two-factor.reset') }}" class="border-t border-gray-200 dark:border-gray-700 pt-6">
                @csrf
                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ย้ายไปมือถือเครื่องใหม่</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">กรอกรหัสปัจจุบันจากแอป (หรือรหัสสำรอง) เพื่อยกเลิกเครื่องเดิม แล้วสแกน QR ใหม่ด้วยเครื่องใหม่</p>
                <div class="flex gap-3">
                    <input type="text" name="code" maxlength="64" autocomplete="one-time-code" required
                           class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-center tracking-widest"
                           placeholder="รหัส 6 หลัก หรือรหัสสำรอง">
                    <button type="submit" class="px-5 py-2 rounded-xl bg-red-600 text-white font-semibold hover:bg-red-700">ยกเลิกเครื่องเดิม</button>
                </div>
            </form>
        </div>
    @else
        @if($required)
            <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 text-indigo-800 dark:text-indigo-200 px-6 py-4 rounded-xl">
                บัญชีผู้ดูแลต้องเปิดการยืนยันตัวตน 2 ขั้นก่อนใช้งานหลังบ้าน — ใช้เวลาประมาณ 1 นาที
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
            <ol class="space-y-6">
                <li>
                    <h3 class="font-semibold text-gray-900 dark:text-white">1. ติดตั้งแอป Authenticator ในมือถือ</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Google Authenticator, Microsoft Authenticator หรือ 1Password ก็ได้</p>
                </li>
                <li>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">2. สแกน QR นี้ในแอป</h3>
                    <div class="flex flex-col sm:flex-row items-start gap-6">
                        <div class="bg-white p-3 rounded-xl border border-gray-200">{!! $qr !!}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <p class="mb-2">สแกนไม่ได้? เลือก "ใส่รหัสเอง" ในแอป แล้วพิมพ์:</p>
                            <p class="font-mono text-base bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2 select-all break-all">{{ trim(chunk_split($secret, 4, ' ')) }}</p>
                        </div>
                    </div>
                </li>
                <li>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">3. กรอกรหัส 6 หลักที่แอปแสดง</h3>
                    <form method="POST" action="{{ route('admin.security.two-factor.confirm') }}" class="flex gap-3 max-w-md">
                        @csrf
                        <input type="text" name="code" inputmode="numeric" maxlength="16" autocomplete="one-time-code" required autofocus
                               class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-center text-lg tracking-widest"
                               placeholder="000000">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">เปิดใช้งาน</button>
                    </form>
                </li>
            </ol>
        </div>
    @endif
</div>
@endsection
