@extends($adminLayout ?? 'layouts.admin')

@section('title', 'เข้าสู่ระบบด้วยโซเชียล')
@section('page-title', 'เข้าสู่ระบบด้วยโซเชียล')

@section('content')
<div class="space-y-6">

    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-sky-600 via-blue-600 to-indigo-700 p-8 shadow-2xl">
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">เข้าสู่ระบบด้วยโซเชียล</h1>
                <p class="text-blue-100 text-lg">ให้ลูกค้าสมัครและเข้าสู่ระบบด้วย Google, LINE หรือ Telegram</p>
            </div>
            <div class="hidden md:flex w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.social-login.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- ─────────────────────────────────────────────────── Google --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between mb-5">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl bg-white border border-gray-200 flex items-center justify-center mr-4 shadow">
                        <svg class="w-6 h-6" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M23.52 12.27c0-.79-.07-1.54-.2-2.27H12v4.51h6.47a5.54 5.54 0 01-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.87z"/>
                            <path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.95-2.91l-3.88-3.01c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.26v3.09A12 12 0 0012 24z"/>
                            <path fill="#FBBC05" d="M5.27 14.28a7.2 7.2 0 010-4.56V6.63H1.26a12 12 0 000 10.74l4.01-3.09z"/>
                            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 001.26 6.63l4.01 3.09C6.22 6.86 8.87 4.75 12 4.75z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Google</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Google Cloud Console → APIs &amp; Services → Credentials → OAuth client ID (Web application)</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold shrink-0
                    {{ $live['google']
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                    {{ $live['google'] ? 'ใช้งานอยู่' : 'ยังไม่พร้อม' }}
                </span>
            </div>

            <label class="flex items-center gap-3 mb-5 cursor-pointer">
                <input type="hidden" name="google_login_enabled" value="0">
                <input type="checkbox" name="google_login_enabled" value="1"
                       class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       @checked(old('google_login_enabled', $settings['google_login_enabled']))>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">เปิดใช้งาน Google Login</span>
            </label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Client ID</label>
                    <input type="text" name="google_login_client_id"
                           value="{{ old('google_login_client_id', $settings['google_login_client_id']) }}"
                           placeholder="1234567890-abcdef.apps.googleusercontent.com"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Client Secret
                        @if($settings['google_has_secret'])
                            <span class="text-xs font-normal text-green-600 dark:text-green-400">· บันทึกไว้แล้ว</span>
                        @endif
                    </label>
                    {{-- Empty value with a masked placeholder: if the bullets were
                         the value, saving the page would store the bullets. --}}
                    <input type="password" name="google_login_client_secret" value="" autocomplete="new-password"
                           placeholder="{{ $settings['google_has_secret'] ? '•••••••••••••••• (เว้นว่างเพื่อใช้ค่าเดิม)' : 'GOCSPX-...' }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                    @if($settings['google_has_secret'])
                        <label class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400 cursor-pointer">
                            <input type="checkbox" name="clear[]" value="google_login_client_secret" class="rounded border-gray-300 text-red-600">
                            ลบ Client Secret ที่บันทึกไว้
                        </label>
                    @endif
                </div>
            </div>

            <div class="mt-5 p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                <p class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-1">Authorized redirect URI</p>
                <code class="block text-xs font-mono text-blue-800 dark:text-blue-300 break-all">{{ $callbacks['google'] }}</code>
                <p class="text-xs text-blue-700 dark:text-blue-400 mt-2">
                    ต้องตรงกันทุกตัวอักษร — เครื่องหมาย / เกินมาตัวเดียวก็ล็อกอินไม่ผ่าน และ Google จะไม่บอกว่าต่างตรงไหน
                </p>
            </div>
        </div>

        {{-- ───────────────────────────────────────────────────── LINE --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between mb-5">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 shadow" style="background-color:#06C755;">
                        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">LINE</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">LINE Developers → Channel (LINE Login) → Basic settings</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold shrink-0
                    {{ $live['line']
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                    {{ $live['line'] ? 'ใช้งานอยู่' : 'ยังไม่พร้อม' }}
                </span>
            </div>

            <label class="flex items-center gap-3 mb-5 cursor-pointer">
                <input type="hidden" name="line_login_enabled" value="0">
                <input type="checkbox" name="line_login_enabled" value="1"
                       class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500"
                       @checked(old('line_login_enabled', $settings['line_login_enabled']))>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">เปิดใช้งาน LINE Login</span>
            </label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Channel ID</label>
                    <input type="text" name="line_login_channel_id"
                           value="{{ old('line_login_channel_id', $settings['line_login_channel_id']) }}"
                           placeholder="2001234567"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Channel Secret
                        @if($settings['line_has_secret'])
                            <span class="text-xs font-normal text-green-600 dark:text-green-400">· บันทึกไว้แล้ว</span>
                        @endif
                    </label>
                    <input type="password" name="line_login_channel_secret" value="" autocomplete="new-password"
                           placeholder="{{ $settings['line_has_secret'] ? '•••••••••••••••• (เว้นว่างเพื่อใช้ค่าเดิม)' : 'channel secret' }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                </div>
            </div>

            <div class="mt-5 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                <p class="text-sm font-semibold text-green-900 dark:text-green-200 mb-1">Callback URL</p>
                <code class="block text-xs font-mono text-green-800 dark:text-green-300 break-all">{{ $callbacks['line'] }}</code>
                <p class="text-xs text-green-700 dark:text-green-400 mt-2">
                    ต้องเปิด <strong>OpenID Connect</strong> และขอสิทธิ์ <strong>Email address permission</strong> ใน LINE Developers
                    ไม่อย่างนั้นระบบจะจับคู่กับบัญชีเดิมของลูกค้าไม่ได้ และจะสร้างบัญชีใหม่ทุกครั้ง
                </p>
            </div>
        </div>

        {{-- ───────────────────────────────────────────────── Telegram --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between mb-5">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 shadow" style="background-color:#229ED9;">
                        <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Telegram</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ใช้ Login Widget ของ Telegram — ไม่ใช่ OAuth</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold shrink-0
                    {{ $live['telegram']
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                    {{ $live['telegram'] ? 'ใช้งานอยู่' : 'ยังไม่พร้อม' }}
                </span>
            </div>

            <label class="flex items-center gap-3 mb-5 cursor-pointer">
                <input type="hidden" name="telegram_login_enabled" value="0">
                <input type="checkbox" name="telegram_login_enabled" value="1"
                       class="w-5 h-5 rounded border-gray-300 text-sky-600 focus:ring-sky-500"
                       @checked(old('telegram_login_enabled', $settings['telegram_login_enabled']))>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">เปิดใช้งาน Telegram Login</span>
            </label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ชื่อบอท (username)</label>
                    <div class="flex">
                        <span class="px-3 py-3 rounded-l-xl border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 text-gray-500 text-sm">@</span>
                        <input type="text" name="telegram_login_bot_username"
                               value="{{ old('telegram_login_bot_username', $settings['telegram_login_bot_username']) }}"
                               placeholder="xmanstudio_bot"
                               class="w-full px-4 py-3 rounded-r-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Bot Token
                        @if($settings['telegram_has_own_token'])
                            <span class="text-xs font-normal text-green-600 dark:text-green-400">· บันทึกไว้แล้ว</span>
                        @elseif($settings['telegram_alerts_token'])
                            <span class="text-xs font-normal text-sky-600 dark:text-sky-400">· ใช้บอทเดียวกับการแจ้งเตือน</span>
                        @endif
                    </label>
                    <input type="password" name="telegram_login_bot_token" value="" autocomplete="new-password"
                           placeholder="{{ $settings['telegram_has_own_token']
                                ? '•••••••••••••••• (เว้นว่างเพื่อใช้ค่าเดิม)'
                                : ($settings['telegram_alerts_token'] ? 'เว้นว่างไว้ = ใช้โทเคนของบอทแจ้งเตือน' : '123456789:AA...') }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                    @if($settings['telegram_has_own_token'])
                        <label class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400 cursor-pointer">
                            <input type="checkbox" name="clear[]" value="telegram_login_bot_token" class="rounded border-gray-300 text-red-600">
                            ลบโทเคนนี้ แล้วกลับไปใช้บอทแจ้งเตือน
                        </label>
                    @endif
                </div>
            </div>

            <div class="mt-5 p-4 rounded-xl bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800">
                <p class="text-sm font-semibold text-sky-900 dark:text-sky-200 mb-2">ต้องตั้งโดเมนกับ @BotFather ก่อน</p>
                <ol class="text-xs text-sky-800 dark:text-sky-300 space-y-1 list-decimal list-inside">
                    <li>เปิดแชท <strong>@BotFather</strong> → <code class="font-mono">/setdomain</code></li>
                    <li>เลือกบอทตัวนี้ แล้วส่งโดเมน: <code class="font-mono font-bold">{{ $callbacks['telegram'] }}</code></li>
                    <li>กลับมาบันทึกหน้านี้ แล้วเปิดหน้า <a href="{{ route('login') }}" target="_blank" class="underline">เข้าสู่ระบบ</a> เพื่อดูปุ่ม</li>
                </ol>
                <p class="text-xs text-sky-700 dark:text-sky-400 mt-2">
                    ถ้าชื่อบอทผิดหรือยังไม่ได้ตั้งโดเมน ปุ่มจะ<strong>ไม่ขึ้นเลย</strong>และไม่มีข้อความแจ้งเตือนใด ๆ
                </p>
            </div>

            <div class="mt-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <p class="text-xs text-amber-800 dark:text-amber-300">
                    <strong>หมายเหตุ:</strong> Telegram ไม่ส่งอีเมลมาให้ ระบบจึงสร้างอีเมลภายในให้บัญชีที่สมัครด้วย Telegram
                    และ<strong>จะไม่จับคู่กับบัญชีเดิมของลูกค้าโดยอัตโนมัติ</strong> — ถ้าลูกค้ามีบัญชีอยู่แล้ว
                    ให้ล็อกอินก่อนแล้วค่อยเชื่อม Telegram จากหน้าโปรไฟล์
                </p>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-8 py-3 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-700 hover:to-indigo-700 text-white font-bold shadow-lg">
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>
@endsection
