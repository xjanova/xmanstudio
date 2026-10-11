<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดาวน์โหลด LocalVPN - XMAN Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-b from-gray-900 via-cyan-900 to-gray-900 text-white">
    {{-- Nav --}}
    <nav class="p-4">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="/" class="text-white font-bold text-lg">XMAN Studio</a>
            <a href="{{ route('localvpn.pricing') }}" class="text-cyan-300 hover:text-white text-sm transition-colors">ดูราคา</a>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-12">
        {{-- Hero --}}
        <div class="text-center mb-12">
            <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg shadow-cyan-500/30">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <h1 class="text-4xl font-bold mb-3">ดาวน์โหลด LocalVPN</h1>
            <p class="text-cyan-300 text-lg">สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต — เชื่อมต่อง่าย ปลอดภัย</p>
        </div>

        @if(session('error'))
            <div class="mb-8 bg-red-500/20 border border-red-500/40 text-red-200 px-4 py-3 rounded-xl text-center">
                {{ session('error') }}
            </div>
        @endif

        {{-- Download Cards — APK กับตัวติดตั้ง Windows มาจาก release เดียวกัน ส่งจากเว็บเราเองทั้งคู่ --}}
        @php
            $downloads = [
                [
                    'title' => 'LocalVPN for Android',
                    'note' => 'Android 8+',
                    'size' => $version?->file_size,
                    'ready' => (bool) $version,
                    'url' => route('localvpn.download.apk'),
                    'label' => 'ดาวน์โหลด APK',
                ],
                [
                    'title' => 'LocalVPN for Windows',
                    'note' => 'Windows 10/11 (64-bit) · ตัวติดตั้ง .exe',
                    'size' => $windowsInstaller['size'] ?? null,
                    'ready' => (bool) $windowsInstaller,
                    'url' => route('localvpn.download.windows'),
                    'label' => 'ดาวน์โหลดตัวติดตั้ง',
                ],
            ];
        @endphp
        <div class="space-y-4 mb-8">
            @foreach($downloads as $download)
                <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-6 md:p-8">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">{{ $download['title'] }}</h2>
                            @if($download['ready'])
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-cyan-300">
                                    <span class="bg-cyan-500/20 px-2 py-0.5 rounded">v{{ $version->version }}</span>
                                    @if($download['size'])
                                        <span>{{ round($download['size'] / 1024 / 1024, 1) }} MB</span>
                                    @endif
                                    <span>{{ $download['note'] }}</span>
                                    @if($version->synced_at)
                                        <span>อัพเดท {{ $version->synced_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            @else
                                <p class="text-cyan-300 text-sm">กำลังเตรียมไฟล์...</p>
                            @endif
                        </div>

                        @if($download['ready'])
                            <a href="{{ $download['url'] }}"
                               class="inline-flex items-center justify-center shrink-0 px-8 py-4 bg-gradient-to-r from-cyan-500 to-teal-600 text-white font-bold rounded-xl shadow-lg shadow-cyan-500/30 hover:shadow-xl hover:shadow-cyan-500/40 transition-all hover:-translate-y-0.5 text-lg">
                                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                {{ $download['label'] }}
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center shrink-0 px-8 py-4 bg-gray-600 text-gray-300 font-bold rounded-xl cursor-not-allowed text-lg">
                                ยังไม่พร้อมดาวน์โหลด
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Changelog --}}
        @if($version && $version->changelog)
            <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-8 mb-8">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Release Notes
                </h3>
                <div class="text-cyan-200 text-sm prose prose-invert max-w-none whitespace-pre-wrap">{{ $version->changelog }}</div>
            </div>
        @endif

        {{-- Features --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white/5 border border-white/10 rounded-xl p-5 text-center">
                <div class="text-3xl mb-2">FREE</div>
                <h4 class="font-semibold">ใช้ฟรีตลอด</h4>
                <p class="text-sm text-cyan-300 mt-1">สมาชิกในวงสูงสุด 5 คน</p>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-xl p-5 text-center">
                <div class="text-3xl mb-2">Android · PC</div>
                <h4 class="font-semibold">Android 8+ · Windows 10/11</h4>
                <p class="text-sm text-cyan-300 mt-1">ใช้ได้ทั้งมือถือและคอม</p>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-xl p-5 text-center">
                <div class="text-3xl mb-2">LAN</div>
                <h4 class="font-semibold">Virtual LAN</h4>
                <p class="text-sm text-cyan-300 mt-1">เชื่อมต่อเหมือนอยู่วงแลนเดียวกัน</p>
            </div>
        </div>

        {{-- Install Instructions --}}
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">วิธีติดตั้ง</h3>
            </div>
            @php
                $installSteps = [
                    'Android' => [
                        'กดปุ่ม "ดาวน์โหลด APK" ด้านบน',
                        'เปิดไฟล์ .apk ที่ดาวน์โหลดมา (อาจต้องอนุญาตติดตั้งจากแหล่งที่ไม่รู้จัก)',
                        'เปิดแอพ LocalVPN แล้วเริ่มใช้งานฟรีได้ทันที',
                    ],
                    'Windows' => [
                        'กดปุ่ม "ดาวน์โหลดตัวติดตั้ง" ด้านบน',
                        'เปิดไฟล์ LocalVPN-Setup ที่ดาวน์โหลดมา ถ้าขึ้น "Windows protected your PC" ให้กด More info → Run anyway',
                        'กด Yes เมื่อ Windows ขอสิทธิ์ผู้ดูแลระบบ (ใช้ติดตั้งการ์ดเครือข่ายเสมือน) แล้วกด Next จนจบ LocalVPN จะเปิดขึ้นมาเอง',
                    ],
                ];
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($installSteps as $platform => $steps)
                    <div>
                        <h4 class="font-semibold mb-3">{{ $platform }}</h4>
                        <ol class="space-y-3 text-cyan-200 text-sm">
                            @foreach($steps as $step)
                                <li class="flex items-start gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 bg-cyan-500 rounded-full flex items-center justify-center text-xs font-bold text-white">{{ $loop->iteration }}</span>
                                    <span>{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-cyan-200 text-sm">ต้องการสมาชิกในวงมากกว่า 5 คน? <a href="{{ route('localvpn.pricing') }}" class="text-cyan-400 hover:text-cyan-300 underline">อัพเกรด License</a></p>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-12 text-cyan-400 text-sm">
            &copy; {{ date('Y') }} XMAN Studio | <a href="/" class="hover:text-white transition-colors">xman4289.com</a>
        </div>
    </div>
</body>
</html>
