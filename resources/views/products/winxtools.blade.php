@extends($publicLayout ?? 'layouts.app')

@section('title', 'WinXTools - จัดการเครือข่าย & Windows ระดับเคอร์เนล | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-gray-900">

    {{-- ============================ HERO ============================ --}}
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%233B82F6\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-blue-400 hover:text-blue-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Product Info -->
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-blue-500/20 rounded-full text-blue-300 text-sm mb-6 backdrop-blur-sm border border-blue-500/30">
                        <span class="relative flex h-2.5 w-2.5 mr-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-cyan-400"></span>
                        </span>
                        Windows Network &amp; System Tools · .NET 10 WPF
                    </div>

                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                        Win<span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400">X</span>Tools
                    </h1>

                    <p class="text-xl text-gray-300 mb-8">
                        คุมเน็ตทุกแอป จับแพ็กเก็ตจริงระดับเคอร์เนล ล้างขยะ เร่งเครื่อง และปรับแต่ง Windows ในโปรแกรมเดียว
                        เครื่องมือ <span class="text-white font-semibold">ของจริง</span> ไม่ใช่แค่บล็อกไฟร์วอลล์
                    </p>

                    <!-- Key Features Tags -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-sm border border-blue-500/30">มอนิเตอร์เน็ตรายแอป</span>
                        <span class="px-3 py-1 bg-cyan-500/20 text-cyan-300 rounded-full text-sm border border-cyan-500/30">จับแพ็กเก็ต WinDivert</span>
                        <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 rounded-full text-sm border border-indigo-500/30">โหมดเกมเมอร์</span>
                        <span class="px-3 py-1 bg-sky-500/20 text-sky-300 rounded-full text-sm border border-sky-500/30">ล้างขยะจริง</span>
                        <span class="px-3 py-1 bg-teal-500/20 text-teal-300 rounded-full text-sm border border-teal-500/30">Proxy/VPN</span>
                    </div>

                    <!-- Price + CTA -->
                    <div class="flex flex-wrap items-center gap-6">
                        <div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-white">฿199</span>
                                <span class="text-gray-400 text-sm">/ จ่ายครั้งเดียว</span>
                            </div>
                            <p class="text-cyan-300/80 text-sm mt-1">มีรุ่นฟรี + ทดลอง Pro ฟรีก่อนซื้อ</p>
                        </div>

                        @auth
                            @if($hasPurchased)
                                <a href="{{ route('customer.downloads') }}"
                                   class="px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25">
                                    ดาวน์โหลด
                                </a>
                            @else
                                <a href="{{ route('products.index') }}"
                                   class="px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-blue-500/25">
                                    ซื้อ Pro — ฿199
                                </a>
                            @endif
                        @else
                            <a href="{{ route('products.index') }}"
                               class="px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-blue-500/25">
                                ซื้อ Pro — ฿199
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Right: Hero Screenshot -->
                <div class="relative">
                    <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-2xl p-3 sm:p-4 backdrop-blur-sm border border-blue-500/30 shadow-2xl shadow-blue-900/40">
                        <div class="rounded-xl overflow-hidden bg-gray-950 border border-white/10">
                            <div class="flex items-center gap-1.5 px-3 py-2 bg-gray-900/80 border-b border-white/5">
                                <span class="w-3 h-3 rounded-full bg-red-400/80"></span>
                                <span class="w-3 h-3 rounded-full bg-yellow-400/80"></span>
                                <span class="w-3 h-3 rounded-full bg-green-400/80"></span>
                                <span class="ml-3 text-xs text-gray-400">WinXTools — Dashboard</span>
                            </div>
                            <img src="{{ asset('images/products/winxtools/01-dashboard.png') }}"
                                 alt="WinXTools Dashboard เรียลไทม์"
                                 loading="lazy"
                                 class="w-full h-auto object-cover">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== TRUST / WHY-REAL STRIP ==================== --}}
    <section class="py-12 border-y border-white/5 bg-gray-900/40 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-blue-300 font-semibold mb-8 tracking-wide">ทำไม WinXTools ถึงเป็น "ของจริง"</p>
            <div class="grid sm:grid-cols-3 gap-6">
                <div class="flex items-start gap-4 bg-gray-800/40 rounded-xl p-5 border border-gray-700/60">
                    <div class="flex-shrink-0 w-11 h-11 bg-blue-500/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-bold mb-1">Throttle ระดับ Kernel</h3>
                        <p class="text-gray-400 text-sm">จำกัดความเร็วจริงด้วยไดรเวอร์ WinDivert (token-bucket) ไม่ใช่แค่บล็อกด้วยไฟร์วอลล์</p>
                    </div>
                </div>
                <div class="flex items-start gap-4 bg-gray-800/40 rounded-xl p-5 border border-gray-700/60">
                    <div class="flex-shrink-0 w-11 h-11 bg-cyan-500/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-bold mb-1">Cleaner สแกนจริง</h3>
                        <p class="text-gray-400 text-sm">แสดงขนาดขยะจริง (Temp, cache, Windows Update, Windows.old) ปลอดภัย ไม่แตะไฟล์ส่วนตัว</p>
                    </div>
                </div>
                <div class="flex items-start gap-4 bg-gray-800/40 rounded-xl p-5 border border-gray-700/60">
                    <div class="flex-shrink-0 w-11 h-11 bg-emerald-500/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-bold mb-1">Gamer Mode ย้อนกลับได้</h3>
                        <p class="text-gray-400 text-sm">สร้าง System Restore Point ก่อนเสมอ ทุกทวีคย้อนกลับได้ 100%</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== SCREENSHOT GALLERY ==================== --}}
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">ดูของจริงทุกหน้าจอ</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">ภาพจริงจากโปรแกรม ไม่ใช่ mockup ทุกฟีเจอร์ทำงานจริงบนเครื่องคุณ</p>
            </div>

            <div class="space-y-16 lg:space-y-24">

                @php
                    $shots = [
                        ['file' => '01-dashboard.png',                    'title' => 'แดชบอร์ดเรียลไทม์',           'caption' => 'ความเร็วรวม ขึ้น/ลง กราฟทราฟฟิกสด และอันดับแอปที่กินเน็ตมากสุด เห็นทุกอย่างในหน้าเดียว'],
                        ['file' => '08-network-monitor.png',             'title' => 'มอนิเตอร์เน็ตรายแอป',        'caption' => 'เห็นทุกโปรเซสที่ใช้เน็ต (PID + ความเร็วจริง) จำกัดหรือบล็อกความเร็วต่อแอปได้ทันที'],
                        ['file' => '05-packet-monitor.png',              'title' => 'จับแพ็กเก็ตสดระดับเคอร์เนล',  'caption' => 'ดักจับด้วย WinDivert เห็น IP จริงทั้ง IPv4/IPv6, พอร์ต, โปรโตคอล, ขนาด และทิศทาง แบบเรียลไทม์'],
                        ['file' => '03-windows-optimizer-gamer-mode.png','title' => 'ออปติไมซ์ + โหมดเกมเมอร์',   'caption' => 'กดปุ่มเดียว ตรวจเวอร์ชัน Windows จริง ปิดของไม่จำเป็น ใส่ทวีคเกม ย้อนกลับได้ 100%'],
                        ['file' => '02-bandwidth.png',                   'title' => 'คุมความเร็วเน็ตจริง',        'caption' => 'จำกัดแบนด์วิดท์ระดับแพ็กเก็ตด้วย WinDivert ไม่ใช่แค่บล็อก คุมได้ต่ออแดปเตอร์/ต่อแอป'],
                        ['file' => '04-windows-tricks.png',              'title' => 'ทริก/คำสั่งลับ Windows',     'caption' => 'รวมทริกและคำสั่งลับจัดหมวด เลือกตามเวอร์ชันจริงของเครื่อง กดรันได้ทันที'],
                        ['file' => '07-cleaner-scanned.png',             'title' => 'ล้างไฟล์ขยะจริง',           'caption' => 'สแกนแล้วเจอขยะจริง (ตัวอย่างเจอ 13.2 GB) ลบปลอดภัย ไม่แตะไฟล์ส่วนตัว'],
                        ['file' => '06-ram-optimizer.png',               'title' => 'เพิ่ม RAM ว่าง',             'caption' => 'ล้าง standby cache ระดับเคอร์เนลจริง + trim working set แสดงหน่วยความจำก่อน/หลัง'],
                        ['file' => '09-proxy-vpn.png',                   'title' => 'พร็อกซีทั่วโลก',            'caption' => 'เชื่อมต่อพร็อกซีฟรีทั่วโลก จัดกลุ่มตามประเทศ คลิกเดียวเชื่อมต่อ'],
                    ];
                @endphp

                @foreach($shots as $i => $shot)
                    <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
                        {{-- Image --}}
                        <div class="{{ $i % 2 === 1 ? 'lg:order-2' : '' }}">
                            <div class="rounded-2xl overflow-hidden bg-gray-950 border border-white/10 shadow-2xl shadow-blue-900/30">
                                <div class="flex items-center gap-1.5 px-3 py-2 bg-gray-900/80 border-b border-white/5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-400/70"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-yellow-400/70"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-green-400/70"></span>
                                </div>
                                <img src="{{ asset('images/products/winxtools/' . $shot['file']) }}"
                                     alt="{{ $shot['title'] }}"
                                     loading="lazy"
                                     class="w-full h-auto object-cover">
                            </div>
                        </div>
                        {{-- Caption --}}
                        <div class="{{ $i % 2 === 1 ? 'lg:order-1' : '' }}">
                            <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-blue-500/15 border border-blue-500/30 text-blue-300 font-bold mb-4">
                                {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-3">{{ $shot['title'] }}</h3>
                            <p class="text-gray-300 text-lg leading-relaxed">{{ $shot['caption'] }}</p>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </section>

    {{-- ==================== FEATURE GRID (10) ==================== --}}
    <section class="py-20 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">ครบทุกเครื่องมือที่คนใช้ Windows ต้องการ</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">10 ฟีเจอร์หลัก ทำงานร่วมกันในโปรแกรมเดียว รองรับสองภาษา ไทย/อังกฤษ ธีมมืด และรุ่นพกพา</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- 1. Dashboard -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">แดชบอร์ดเรียลไทม์</h3>
                    <p class="text-gray-400 text-sm">ความเร็วรวมขึ้น/ลง จำนวนแอปที่ทำงาน กราฟทราฟฟิกสด และอันดับแอปที่กินเน็ตมากสุด</p>
                </div>

                <!-- 2. Per-App Monitor -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-cyan-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h6"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">มอนิเตอร์เน็ตรายแอป</h3>
                    <p class="text-gray-400 text-sm">เห็นทุกโปรเซส (PID + ความเร็วจริง) จำกัด/บล็อกต่อแอปได้ พรีเซ็ต Unlimited/10/5/1 MB/s หรือ Block</p>
                </div>

                <!-- 3. Bandwidth Control -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-indigo-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12h4l3 8 4-16 3 8h4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">คุมแบนด์วิดท์จริง</h3>
                    <p class="text-gray-400 text-sm">Throttle ระดับเคอร์เนลด้วยไดรเวอร์ WinDivert (token-bucket) จำกัดความเร็วจริงต่ออแดปเตอร์/แอป ไม่ใช่แค่บล็อก</p>
                </div>

                <!-- 4. Packet Monitor -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-sky-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">จับแพ็กเก็ตสด</h3>
                    <p class="text-gray-400 text-sm">แคปเจอร์ระดับเคอร์เนล เห็น IP ต้นทาง/ปลายทางจริงทั้ง IPv4 และ IPv6, พอร์ต, โปรโตคอล, ขนาด, ทิศทาง</p>
                </div>

                <!-- 5. Windows Optimizer + Gamer Mode -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-emerald-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">ออปติไมซ์ + โหมดเกมเมอร์</h3>
                    <p class="text-gray-400 text-sm">ตรวจเวอร์ชัน Windows จริง (เช่น 11 Pro 25H2) ปิด telemetry/AI/บริการไม่จำเป็น ใส่ทวีคเกม (GameDVR, HAGS, Ultimate power) สร้าง Restore Point ก่อน ย้อนกลับได้</p>
                </div>

                <!-- 6. Secret Commands & Tricks -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">คำสั่งลับ &amp; ทริก Windows</h3>
                    <p class="text-gray-400 text-sm">ทริกหลายสิบตัวจัดหมวด (Gamer, God Mode, Performance, Network, Privacy, Security) แสดงเฉพาะที่ใช้ได้กับ build ของคุณ</p>
                </div>

                <!-- 7. Disk Cleaner -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-teal-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">ล้างไฟล์ขยะจริง</h3>
                    <p class="text-gray-400 text-sm">สแกนและลบขยะอย่างปลอดภัย (Temp, browser cache, Windows Update cache, logs, thumbnails, Windows.old) แสดงขนาดจริง ไม่แตะไฟล์ส่วนตัว</p>
                </div>

                <!-- 8. RAM Optimizer -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-3-4h.01M17 16h.01"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">RAM Optimizer</h3>
                    <p class="text-gray-400 text-sm">ล้าง standby cache ระดับเคอร์เนลจริง + trim working set คืน RAM ว่าง แสดงหน่วยความจำก่อน/หลัง</p>
                </div>

                <!-- 9. Proxy/VPN -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-pink-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Proxy / VPN</h3>
                    <p class="text-gray-400 text-sm">เชื่อมต่อพร็อกซีฟรีทั่วโลก จัดกลุ่มตามประเทศ คลิกเดียวเชื่อมต่อ</p>
                </div>

                <!-- 10. Extra tools -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all md:col-span-2 lg:col-span-3">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white mb-2">และเครื่องมือเสริมอีกเพียบ</h3>
                            <p class="text-gray-400 text-sm">TCP/UDP Connection Monitor · Deep Uninstaller (ล้าง registry ต่อ) · Network Tools · รองรับสองภาษา ไทย/อังกฤษ · ธีมมืด · รุ่นพกพา (portable) ไม่ต้องติดตั้ง</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ==================== PRICING: FREE vs PRO ==================== --}}
    <section class="py-20" id="pricing">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">ราคาที่คุ้มที่สุด</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">เริ่มฟรีได้ทันที หรือปลดล็อกทุกฟีเจอร์ด้วย Pro จ่ายครั้งเดียว ใช้ได้ตลอด</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">

                <!-- Free -->
                <div class="bg-gray-800/50 rounded-2xl p-8 border border-gray-700 flex flex-col">
                    <h3 class="text-2xl font-bold text-white mb-1">Free</h3>
                    <p class="text-gray-400 text-sm mb-6">ฟีเจอร์หลักครบ เริ่มใช้ได้เลย</p>
                    <div class="mb-6">
                        <span class="text-4xl font-black text-white">฿0</span>
                        <span class="text-gray-400 text-sm">/ ตลอดกาล</span>
                    </div>
                    <ul class="space-y-3 text-gray-300 text-sm mb-8 flex-1">
                        <li class="flex items-start"><span class="text-green-400 mr-3 mt-0.5">&#10003;</span> แดชบอร์ดเรียลไทม์ + กราฟทราฟฟิก</li>
                        <li class="flex items-start"><span class="text-green-400 mr-3 mt-0.5">&#10003;</span> ล้างไฟล์ขยะจริง (Disk Cleaner)</li>
                        <li class="flex items-start"><span class="text-green-400 mr-3 mt-0.5">&#10003;</span> RAM Optimizer</li>
                        <li class="flex items-start"><span class="text-green-400 mr-3 mt-0.5">&#10003;</span> Windows Optimizer พื้นฐาน</li>
                        <li class="flex items-start"><span class="text-green-400 mr-3 mt-0.5">&#10003;</span> รองรับสองภาษา ไทย/อังกฤษ + ธีมมืด</li>
                    </ul>
                    <a href="{{ route('products.index') }}"
                       class="block w-full py-3 text-center bg-gray-700/60 hover:bg-gray-600/60 text-white font-semibold rounded-xl border border-gray-600 transition-all">
                        เริ่มใช้ฟรี
                    </a>
                </div>

                <!-- Pro -->
                <div class="relative bg-gradient-to-br from-blue-600/20 to-cyan-600/20 rounded-2xl p-8 border-2 border-blue-500/60 flex flex-col shadow-2xl shadow-blue-900/30">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-gradient-to-r from-blue-500 to-cyan-500 text-white text-xs font-bold rounded-full shadow-lg">
                        แนะนำ · คุ้มที่สุด
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-1">Pro</h3>
                    <p class="text-cyan-200/80 text-sm mb-6">ปลดล็อกทุกฟีเจอร์ขั้นสูง</p>
                    <div class="mb-6">
                        <span class="text-4xl font-black text-white">฿199</span>
                        <span class="text-gray-300 text-sm">/ จ่ายครั้งเดียว</span>
                    </div>
                    <ul class="space-y-3 text-gray-100 text-sm mb-8 flex-1">
                        <li class="flex items-start"><span class="text-cyan-300 mr-3 mt-0.5">&#10003;</span> <span><span class="font-semibold text-white">ทุกอย่างในรุ่น Free</span> และ...</span></li>
                        <li class="flex items-start"><span class="text-cyan-300 mr-3 mt-0.5">&#10003;</span> จับแพ็กเก็ตสดระดับเคอร์เนล (Packet Monitor)</li>
                        <li class="flex items-start"><span class="text-cyan-300 mr-3 mt-0.5">&#10003;</span> จำกัดความเร็วเน็ตต่อแอป (Bandwidth Control)</li>
                        <li class="flex items-start"><span class="text-cyan-300 mr-3 mt-0.5">&#10003;</span> Per-App Network Monitor เต็มรูปแบบ</li>
                        <li class="flex items-start"><span class="text-cyan-300 mr-3 mt-0.5">&#10003;</span> Proxy / VPN ทั่วโลก</li>
                        <li class="flex items-start"><span class="text-cyan-300 mr-3 mt-0.5">&#10003;</span> คำสั่งลับ &amp; ทริก Windows ทั้งหมด</li>
                        <li class="flex items-start"><span class="text-cyan-300 mr-3 mt-0.5">&#10003;</span> Rules / Automation ตั้งกฎอัตโนมัติ</li>
                    </ul>
                    @auth
                        @if($hasPurchased)
                            <a href="{{ route('customer.downloads') }}"
                               class="block w-full py-3.5 text-center bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25">
                                ดาวน์โหลด Pro
                            </a>
                        @else
                            <a href="{{ route('products.index') }}"
                               class="block w-full py-3.5 text-center bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-blue-500/25">
                                ซื้อ Pro — ฿199
                            </a>
                        @endif
                    @else
                        <a href="{{ route('products.index') }}"
                           class="block w-full py-3.5 text-center bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-blue-500/25">
                            ซื้อ Pro — ฿199
                        </a>
                    @endauth
                    <p class="text-center text-cyan-200/70 text-xs mt-4">ทดลอง Pro ฟรีก่อนตัดสินใจ · ไม่มีค่าใช้จ่ายซ่อน</p>
                </div>

            </div>
        </div>
    </section>

    {{-- ==================== SYSTEM REQUIREMENTS ==================== --}}
    <section class="py-20 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">ความต้องการระบบ</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">WinXTools ต้องการสิทธิ์ Administrator เพื่อทำงานระดับเคอร์เนล (WinDivert, standby cache, ทวีคระบบ)</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">ระบบปฏิบัติการ</h3>
                    <p class="text-gray-400 text-sm">Windows 10 หรือ 11<br>(64-bit เท่านั้น)</p>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-cyan-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Runtime</h3>
                    <p class="text-gray-400 text-sm">.NET 10 Desktop Runtime<br>(WPF)</p>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-blue-500/50 text-center">
                    <div class="w-14 h-14 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">สิทธิ์การใช้งาน</h3>
                    <p class="text-gray-300 text-sm">Run as Administrator<br>(จำเป็นสำหรับฟีเจอร์ระดับเคอร์เนล)</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== FINAL CTA ==================== --}}
    <section class="py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600/20 via-cyan-600/10 to-gray-900 border border-blue-500/30 p-10 md:p-14 text-center">
                <div class="absolute -top-16 -right-16 w-64 h-64 bg-cyan-500/20 rounded-full blur-3xl"></div>
                <div class="relative">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">พร้อมคุมเครื่องของคุณแล้วหรือยัง?</h2>
                    <p class="text-gray-300 mb-8 max-w-2xl mx-auto">
                        ดาวน์โหลดรุ่นฟรีเริ่มใช้ได้ทันที หรือปลดล็อกทุกฟีเจอร์ด้วย Pro เพียง ฿199 จ่ายครั้งเดียว ใช้ได้ตลอด
                    </p>

                    <div class="flex flex-wrap justify-center gap-4">
                        @auth
                            @if($hasPurchased)
                                <a href="{{ route('customer.downloads') }}"
                                   class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    ดาวน์โหลด
                                </a>
                            @else
                                <a href="{{ route('products.index') }}"
                                   class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-blue-500/25">
                                    ซื้อ Pro — ฿199
                                </a>
                            @endif
                        @else
                            <a href="{{ route('products.index') }}"
                               class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-blue-500/25">
                                ซื้อ Pro — ฿199
                            </a>
                        @endauth
                        <a href="{{ route('products.index') }}"
                           class="inline-flex items-center px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                            ดูผลิตภัณฑ์อื่น
                        </a>
                    </div>

                    <p class="text-gray-500 text-sm mt-6">รองรับ Windows 10/11 (64-bit) · .NET 10 · ต้องรันในสิทธิ์ Administrator</p>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
