<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดาวน์โหลด Tping - XMAN Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-b from-gray-900 via-indigo-900 to-gray-900 text-white">
    {{-- Nav --}}
    <nav class="p-4">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="/" class="text-white font-bold text-lg">XMAN Studio</a>
            <a href="{{ route('tping.pricing') }}" class="text-indigo-300 hover:text-white text-sm transition-colors">ดูราคา</a>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-12">
        {{-- Hero --}}
        <div class="text-center mb-12">
            <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/30">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <h1 class="text-4xl font-bold mb-3">ดาวน์โหลด Tping</h1>
            <p class="text-indigo-300 text-lg">แอพ Auto-Type สำหรับ Android — พิมพ์อัตโนมัติ ประหยัดเวลา</p>
        </div>

        @if(session('error'))
            <div class="mb-8 bg-red-500/20 border border-red-500/40 text-red-200 px-4 py-3 rounded-xl text-center">
                {{ session('error') }}
            </div>
        @endif

        {{-- Download Card --}}
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-8 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Tping for Android</h2>
                    @if($version)
                        <div class="flex items-center gap-3 text-sm text-indigo-300">
                            <span class="bg-indigo-500/20 px-2 py-0.5 rounded">v{{ $version->version }}</span>
                            @if($version->file_size)
                                <span>{{ round($version->file_size / 1024 / 1024, 1) }} MB</span>
                            @endif
                            @if($version->synced_at)
                                <span>อัพเดท {{ $version->synced_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    @else
                        <p class="text-indigo-300 text-sm">กำลังเตรียมไฟล์...</p>
                    @endif
                </div>

                @if($version)
                    <a href="{{ route('tping.download.apk') }}"
                       class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-violet-500 to-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-violet-500/30 hover:shadow-xl hover:shadow-violet-500/40 transition-all hover:-translate-y-0.5 text-lg">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        ดาวน์โหลด APK
                    </a>
                @else
                    <span class="inline-flex items-center px-8 py-4 bg-gray-600 text-gray-300 font-bold rounded-xl cursor-not-allowed text-lg">
                        ยังไม่พร้อมดาวน์โหลด
                    </span>
                @endif
            </div>
        </div>

        {{-- Changelog --}}
        @if($version && $version->changelog)
            <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-8 mb-8">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Release Notes
                </h3>
                <div class="text-indigo-200 text-sm prose prose-invert max-w-none whitespace-pre-wrap">{{ $version->changelog }}</div>
            </div>
        @endif

        {{-- Features --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white/5 border border-white/10 rounded-xl p-5 text-center">
                <div class="text-3xl mb-2">24h</div>
                <h4 class="font-semibold">ทดลองฟรี</h4>
                <p class="text-sm text-indigo-300 mt-1">ลองใช้งานฟรี 24 ชั่วโมง</p>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-xl p-5 text-center">
                <div class="text-3xl mb-2">Android</div>
                <h4 class="font-semibold">รองรับ Android 8+</h4>
                <p class="text-sm text-indigo-300 mt-1">ใช้งานได้ทุกเครื่อง</p>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-xl p-5 text-center">
                <div class="text-3xl mb-2">Auto</div>
                <h4 class="font-semibold">พิมพ์อัตโนมัติ</h4>
                <p class="text-sm text-indigo-300 mt-1">Workflow + Data Profiles</p>
            </div>
        </div>

        {{-- Install Instructions --}}
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-8">
            <h3 class="text-lg font-bold mb-4">วิธีติดตั้ง</h3>
            <ol class="space-y-3 text-indigo-200 text-sm">
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center text-xs font-bold text-white">1</span>
                    <span>กดปุ่ม "ดาวน์โหลด APK" ด้านบน</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center text-xs font-bold text-white">2</span>
                    <span>เปิดไฟล์ .apk ที่ดาวน์โหลดมา (อาจต้องอนุญาตติดตั้งจากแหล่งที่ไม่รู้จัก)</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center text-xs font-bold text-white">3</span>
                    <span>เปิดแอพ Tping แล้วเริ่มทดลองใช้ฟรี 24 ชั่วโมง</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center text-xs font-bold text-white">4</span>
                    <span>ต้องการใช้งานต่อ? <a href="{{ route('tping.pricing') }}" class="text-violet-400 hover:text-violet-300 underline">ซื้อ License</a></span>
                </li>
            </ol>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-12 text-indigo-400 text-sm">
            &copy; {{ date('Y') }} XMAN Studio | <a href="/" class="hover:text-white transition-colors">xman4289.com</a>
        </div>
    </div>
</body>
</html>
