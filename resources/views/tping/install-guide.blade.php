@extends($publicLayout ?? 'layouts.app')

@section('title', 'วิธีติดตั้ง Tping - คู่มือฉบับสมบูรณ์')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-gray-900 via-indigo-900 to-gray-900 text-white">

    {{-- Hero --}}
    <div class="max-w-5xl mx-auto px-4 pt-12 pb-8 text-center">
        <a href="{{ route('tping.download') }}" class="inline-flex items-center text-indigo-300 hover:text-white text-sm mb-6 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            กลับหน้าดาวน์โหลด
        </a>
        <div class="w-16 h-16 mx-auto mb-5 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/30">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold mb-3">วิธีติดตั้ง Tping</h1>
        <p class="text-indigo-300 text-lg max-w-2xl mx-auto">คู่มือฉบับสมบูรณ์ — ติดตั้งและเริ่มใช้งานภายใน 5 นาที</p>
        <div class="flex items-center justify-center gap-4 mt-6 text-sm text-indigo-400">
            <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                ใช้เวลา ~5 นาที
            </span>
            <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Android 8.0 ขึ้นไป
            </span>
            <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                ฟรี 24 ชม.
            </span>
        </div>
    </div>

    {{-- Steps --}}
    <div class="max-w-5xl mx-auto px-4 pb-16">

        {{-- Step 1: Download --}}
        <div class="mb-16" id="step-1">
            <div class="flex flex-col lg:flex-row items-center gap-8">
                {{-- Phone Mockup --}}
                <div class="flex-shrink-0">
                    @if(isset($screenshots[1]))
                        <div class="phone-frame">
                            <img src="{{ Storage::url($screenshots[1]) }}" alt="ขั้นตอนที่ 1" class="w-full h-full object-cover rounded-[2rem]">
                        </div>
                    @else
                        <div class="phone-frame">
                            <div class="phone-screen bg-gradient-to-b from-gray-900 to-indigo-900 flex flex-col items-center justify-center p-6 text-center">
                                <div class="w-12 h-12 bg-violet-500/30 rounded-xl flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                                </div>
                                <p class="text-white text-sm font-medium mb-1">xman4289.com</p>
                                <p class="text-indigo-300 text-xs mb-4">/tping/download</p>
                                <div class="w-full bg-violet-600 rounded-lg py-2.5 px-4 text-white text-sm font-medium flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    ดาวน์โหลด APK
                                </div>
                                <div class="mt-3 text-indigo-400 text-xs">v1.2.27 • 161 MB</div>
                            </div>
                        </div>
                    @endif
                </div>
                {{-- Description --}}
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-full flex items-center justify-center text-lg font-bold shadow-lg shadow-violet-500/30">1</span>
                        <h2 class="text-2xl font-bold">ดาวน์โหลด APK</h2>
                    </div>
                    <div class="space-y-3 text-indigo-200">
                        <p>เปิดเบราว์เซอร์บนมือถือ Android แล้วไปที่:</p>
                        <div class="bg-white/10 border border-white/20 rounded-xl px-4 py-3 font-mono text-violet-300 text-sm flex items-center justify-between">
                            <span>xman4289.com/tping/download</span>
                            <button onclick="navigator.clipboard.writeText('https://xman4289.com/tping/download')" class="text-xs bg-violet-500/30 hover:bg-violet-500/50 px-2 py-1 rounded transition">คัดลอก</button>
                        </div>
                        <p>กดปุ่ม <strong class="text-violet-400">"ดาวน์โหลด APK"</strong> รอไฟล์ดาวน์โหลดเสร็จ (ขนาดประมาณ 160 MB)</p>
                        <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl px-4 py-3 text-amber-200 text-sm flex gap-2">
                            <svg class="w-5 h-5 flex-shrink-0 text-amber-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>หากดาวน์โหลดไม่ได้ ลองใช้เบราว์เซอร์ Chrome แทน</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Allow Unknown Sources --}}
        <div class="mb-16" id="step-2">
            <div class="flex flex-col lg:flex-row-reverse items-center gap-8">
                <div class="flex-shrink-0">
                    @if(isset($screenshots[2]))
                        <div class="phone-frame">
                            <img src="{{ Storage::url($screenshots[2]) }}" alt="ขั้นตอนที่ 2" class="w-full h-full object-cover rounded-[2rem]">
                        </div>
                    @else
                        <div class="phone-frame">
                            <div class="phone-screen bg-gray-100 flex flex-col p-4">
                                <div class="bg-white rounded-xl p-3 mb-2 shadow-sm">
                                    <p class="text-gray-500 text-xs mb-1">ตั้งค่า › ความปลอดภัย</p>
                                    <p class="text-gray-900 text-sm font-medium">ติดตั้งจากแหล่งที่ไม่รู้จัก</p>
                                </div>
                                <div class="bg-white rounded-xl p-3 shadow-sm flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 bg-green-500 rounded-md flex items-center justify-center">
                                                <span class="text-white text-xs font-bold">C</span>
                                            </div>
                                            <span class="text-gray-900 text-sm font-medium">Chrome</span>
                                        </div>
                                        <p class="text-gray-500 text-xs mt-1 ml-8">อนุญาตจากแหล่งนี้</p>
                                    </div>
                                    <div class="w-10 h-6 bg-violet-500 rounded-full flex items-center justify-end px-0.5">
                                        <div class="w-5 h-5 bg-white rounded-full shadow"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-full flex items-center justify-center text-lg font-bold shadow-lg shadow-violet-500/30">2</span>
                        <h2 class="text-2xl font-bold">อนุญาตติดตั้งจากแหล่งที่ไม่รู้จัก</h2>
                    </div>
                    <div class="space-y-3 text-indigo-200">
                        <p>Android จะถามว่าต้องการอนุญาตติดตั้งแอพจากแหล่งนี้หรือไม่ ให้ทำตามนี้:</p>
                        <ol class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-indigo-500/30 rounded-full flex items-center justify-center text-xs text-indigo-300">a</span>
                                <span>เมื่อดาวน์โหลดเสร็จ กดเปิดไฟล์ <code class="bg-white/10 px-1.5 py-0.5 rounded text-violet-300">Tping-v1.2.27.apk</code></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-indigo-500/30 rounded-full flex items-center justify-center text-xs text-indigo-300">b</span>
                                <span>ระบบจะแสดงข้อความ <strong class="text-white">"ไม่อนุญาตให้ติดตั้งจากแหล่งนี้"</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-indigo-500/30 rounded-full flex items-center justify-center text-xs text-indigo-300">c</span>
                                <span>กด <strong class="text-violet-400">"ตั้งค่า"</strong> → เปิดสวิตช์ <strong class="text-violet-400">"อนุญาตจากแหล่งนี้"</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-indigo-500/30 rounded-full flex items-center justify-center text-xs text-indigo-300">d</span>
                                <span>กดปุ่ม <strong class="text-white">ย้อนกลับ</strong> เพื่อกลับมาติดตั้ง</span>
                            </li>
                        </ol>
                        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl px-4 py-3 text-blue-200 text-sm flex gap-2">
                            <svg class="w-5 h-5 flex-shrink-0 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><strong>Samsung:</strong> ตั้งค่า → Biometrics and security → Install unknown apps<br>
                            <strong>Xiaomi:</strong> ตั้งค่า → Privacy protection → Special permissions → Install unknown apps</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Install APK --}}
        <div class="mb-16" id="step-3">
            <div class="flex flex-col lg:flex-row items-center gap-8">
                <div class="flex-shrink-0">
                    @if(isset($screenshots[3]))
                        <div class="phone-frame">
                            <img src="{{ Storage::url($screenshots[3]) }}" alt="ขั้นตอนที่ 3" class="w-full h-full object-cover rounded-[2rem]">
                        </div>
                    @else
                        <div class="phone-frame">
                            <div class="phone-screen bg-gray-100 flex flex-col items-center justify-center p-6">
                                <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                                    <span class="text-white text-2xl font-bold">T</span>
                                </div>
                                <p class="text-gray-900 text-sm font-semibold mb-1">ต้องการติดตั้งแอปนี้?</p>
                                <p class="text-gray-500 text-xs mb-1">Tping</p>
                                <p class="text-gray-400 text-xs mb-4">161 MB</p>
                                <div class="w-full space-y-2">
                                    <div class="w-full bg-violet-600 rounded-lg py-2 text-white text-sm font-medium text-center">ติดตั้ง</div>
                                    <div class="w-full bg-gray-200 rounded-lg py-2 text-gray-600 text-sm text-center">ยกเลิก</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-full flex items-center justify-center text-lg font-bold shadow-lg shadow-violet-500/30">3</span>
                        <h2 class="text-2xl font-bold">ติดตั้งแอพ</h2>
                    </div>
                    <div class="space-y-3 text-indigo-200">
                        <p>หลังจากอนุญาตแล้ว ระบบจะแสดงหน้าต่างยืนยันการติดตั้ง:</p>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>กดปุ่ม <strong class="text-violet-400">"ติดตั้ง"</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>รอจนแถบ progress bar เสร็จ (ใช้เวลา ~30 วินาที)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>กดปุ่ม <strong class="text-white">"เปิด"</strong> เพื่อเข้าแอพ</span>
                            </li>
                        </ul>
                        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 text-emerald-200 text-sm flex gap-2">
                            <svg class="w-5 h-5 flex-shrink-0 text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Tping ผ่านการ sign ด้วย keystore อย่างเป็นทางการ — ปลอดภัย 100%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 4: Enable Accessibility Service --}}
        <div class="mb-16" id="step-4">
            <div class="flex flex-col lg:flex-row-reverse items-center gap-8">
                <div class="flex-shrink-0">
                    @if(isset($screenshots[4]))
                        <div class="phone-frame">
                            <img src="{{ Storage::url($screenshots[4]) }}" alt="ขั้นตอนที่ 4" class="w-full h-full object-cover rounded-[2rem]">
                        </div>
                    @else
                        <div class="phone-frame">
                            <div class="phone-screen bg-gray-100 flex flex-col p-4">
                                <p class="text-gray-500 text-xs mb-2">ตั้งค่า › การช่วยเหลือพิเศษ</p>
                                <div class="bg-white rounded-xl p-3 mb-2 shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-lg flex items-center justify-center">
                                            <span class="text-white text-xs font-bold">T</span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-gray-900 text-sm font-medium">Tping Service</p>
                                            <p class="text-gray-500 text-xs">แอพช่วยพิมพ์อัตโนมัติ</p>
                                        </div>
                                        <div class="w-10 h-6 bg-gray-300 rounded-full flex items-center px-0.5">
                                            <div class="w-5 h-5 bg-white rounded-full shadow"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 bg-white rounded-xl p-3 shadow-sm border-2 border-violet-500">
                                    <p class="text-gray-900 text-sm font-medium mb-1">⚠️ เปิดใช้ Tping Service?</p>
                                    <p class="text-gray-500 text-xs mb-3">แอพนี้ต้องการสิทธิ์ Accessibility เพื่อช่วยพิมพ์ข้อความ</p>
                                    <div class="flex gap-2">
                                        <div class="flex-1 bg-gray-200 rounded-lg py-1.5 text-gray-600 text-xs text-center">ยกเลิก</div>
                                        <div class="flex-1 bg-violet-600 rounded-lg py-1.5 text-white text-xs text-center font-medium">อนุญาต</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-full flex items-center justify-center text-lg font-bold shadow-lg shadow-violet-500/30">4</span>
                        <h2 class="text-2xl font-bold">เปิด Accessibility Service</h2>
                    </div>
                    <div class="space-y-3 text-indigo-200">
                        <p>Tping ต้องใช้ <strong class="text-white">Accessibility Service</strong> เพื่อพิมพ์ข้อความอัตโนมัติ:</p>
                        <ol class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-indigo-500/30 rounded-full flex items-center justify-center text-xs text-indigo-300">a</span>
                                <span>เปิดแอพ Tping → จะแสดงหน้าแนะนำให้เปิดสิทธิ์</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-indigo-500/30 rounded-full flex items-center justify-center text-xs text-indigo-300">b</span>
                                <span>กด <strong class="text-violet-400">"ไปเปิดสิทธิ์"</strong> → ระบบจะพาไปหน้าตั้งค่า</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-indigo-500/30 rounded-full flex items-center justify-center text-xs text-indigo-300">c</span>
                                <span>หา <strong class="text-white">"Tping Service"</strong> แล้ว<strong class="text-violet-400">เปิดสวิตช์</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-indigo-500/30 rounded-full flex items-center justify-center text-xs text-indigo-300">d</span>
                                <span>กด <strong class="text-violet-400">"อนุญาต"</strong> ในหน้าต่างยืนยัน</span>
                            </li>
                        </ol>
                        <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl px-4 py-3 text-amber-200 text-sm flex gap-2">
                            <svg class="w-5 h-5 flex-shrink-0 text-amber-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            <span><strong>สำคัญ:</strong> หากไม่เปิด Accessibility แอพจะพิมพ์ข้อความอัตโนมัติไม่ได้!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 5: Start Trial --}}
        <div class="mb-16" id="step-5">
            <div class="flex flex-col lg:flex-row items-center gap-8">
                <div class="flex-shrink-0">
                    @if(isset($screenshots[5]))
                        <div class="phone-frame">
                            <img src="{{ Storage::url($screenshots[5]) }}" alt="ขั้นตอนที่ 5" class="w-full h-full object-cover rounded-[2rem]">
                        </div>
                    @else
                        <div class="phone-frame">
                            <div class="phone-screen bg-gradient-to-b from-violet-600 to-indigo-700 flex flex-col items-center justify-center p-6 text-center">
                                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mb-4">
                                    <span class="text-white text-2xl font-bold">T</span>
                                </div>
                                <p class="text-white text-lg font-bold mb-1">Tping</p>
                                <p class="text-violet-200 text-xs mb-4">Auto-Typing Assistant</p>
                                <div class="w-full bg-white/20 rounded-xl p-3 mb-3">
                                    <p class="text-white text-sm font-medium">ทดลองฟรี 24 ชั่วโมง!</p>
                                    <p class="text-violet-200 text-xs mt-1">ใช้ฟีเจอร์ทั้งหมดได้เต็มที่</p>
                                </div>
                                <div class="w-full bg-white rounded-lg py-2.5 text-violet-700 text-sm font-bold">เริ่มทดลองฟรี</div>
                                <p class="text-violet-300 text-xs mt-3">มี License แล้ว? <span class="underline">Activate</span></p>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-full flex items-center justify-center text-lg font-bold shadow-lg shadow-violet-500/30">5</span>
                        <h2 class="text-2xl font-bold">เริ่มทดลองฟรี 24 ชม.</h2>
                    </div>
                    <div class="space-y-3 text-indigo-200">
                        <p>เมื่อเปิดแอพครั้งแรก คุณจะได้ <strong class="text-emerald-400">ทดลองใช้ฟรี 24 ชั่วโมง</strong> — ใช้ฟีเจอร์ทั้งหมดได้เต็มที่!</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-center">
                                <p class="text-lg mb-1">📝</p>
                                <p class="text-sm text-white font-medium">Learning Mode</p>
                                <p class="text-xs text-indigo-300">บันทึกขั้นตอน</p>
                            </div>
                            <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-center">
                                <p class="text-lg mb-1">▶️</p>
                                <p class="text-sm text-white font-medium">Auto Playback</p>
                                <p class="text-xs text-indigo-300">เล่นซ้ำอัตโนมัติ</p>
                            </div>
                            <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-center">
                                <p class="text-lg mb-1">📊</p>
                                <p class="text-sm text-white font-medium">Data Profiles</p>
                                <p class="text-xs text-indigo-300">ข้อมูลหลายชุด</p>
                            </div>
                            <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-center">
                                <p class="text-lg mb-1">☁️</p>
                                <p class="text-sm text-white font-medium">Cloud Sync</p>
                                <p class="text-xs text-indigo-300">สำรองข้อมูล</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 6: Activate License --}}
        <div class="mb-16" id="step-6">
            <div class="flex flex-col lg:flex-row-reverse items-center gap-8">
                <div class="flex-shrink-0">
                    @if(isset($screenshots[6]))
                        <div class="phone-frame">
                            <img src="{{ Storage::url($screenshots[6]) }}" alt="ขั้นตอนที่ 6" class="w-full h-full object-cover rounded-[2rem]">
                        </div>
                    @else
                        <div class="phone-frame">
                            <div class="phone-screen bg-gradient-to-b from-gray-900 to-indigo-900 flex flex-col items-center justify-center p-5 text-center">
                                <div class="w-10 h-10 bg-emerald-500/30 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <p class="text-emerald-400 text-sm font-bold mb-1">Activate สำเร็จ!</p>
                                <p class="text-indigo-300 text-xs mb-4">License ใช้งานได้ถึง 08/03/2027</p>
                                <div class="w-full bg-white/10 rounded-xl p-3 mb-3">
                                    <p class="text-indigo-400 text-xs mb-1">License Key</p>
                                    <p class="text-white text-sm font-mono tracking-wider">ABCD-EFGH-IJKL-MNOP</p>
                                </div>
                                <div class="w-full bg-white/10 rounded-xl p-3">
                                    <p class="text-indigo-400 text-xs mb-1">Machine ID</p>
                                    <p class="text-white text-xs font-mono">a1b2c3d4e5f6</p>
                                    <p class="text-emerald-400 text-xs mt-1">✓ ผูกกับเครื่องนี้แล้ว</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full flex items-center justify-center text-lg font-bold shadow-lg shadow-emerald-500/30">6</span>
                        <h2 class="text-2xl font-bold">Activate License <span class="text-sm font-normal text-indigo-300">(เมื่อซื้อแล้ว)</span></h2>
                    </div>
                    <div class="space-y-3 text-indigo-200">
                        <p>หลังจากหมดช่วงทดลอง ซื้อ License เพื่อใช้งานต่อ:</p>
                        <ol class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-emerald-500/30 rounded-full flex items-center justify-center text-xs text-emerald-300">a</span>
                                <span>ซื้อ License ที่ <a href="{{ route('tping.pricing') }}" class="text-violet-400 hover:text-violet-300 underline">หน้าราคา</a></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-emerald-500/30 rounded-full flex items-center justify-center text-xs text-emerald-300">b</span>
                                <span>หลังชำระเงิน จะได้ <strong class="text-white">License Key</strong> (เช่น ABCD-EFGH-IJKL-MNOP)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-emerald-500/30 rounded-full flex items-center justify-center text-xs text-emerald-300">c</span>
                                <span>ในแอพ Tping → กด <strong class="text-violet-400">"Activate License"</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 bg-emerald-500/30 rounded-full flex items-center justify-center text-xs text-emerald-300">d</span>
                                <span>กรอก License Key → กด <strong class="text-violet-400">"Activate"</strong></span>
                            </li>
                        </ol>
                        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 text-emerald-200 text-sm flex gap-2">
                            <svg class="w-5 h-5 flex-shrink-0 text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><strong>ชำระด้วย Wallet?</strong> License จะ activate อัตโนมัติ ไม่ต้องกรอก key เอง!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Troubleshooting FAQ --}}
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-8 mb-8">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                แก้ปัญหาที่พบบ่อย
            </h2>
            <div class="space-y-4" x-data="{ open: null }">
                {{-- FAQ items --}}
                @php
                $faqs = [
                    [
                        'q' => 'ดาวน์โหลดไม่ได้ / ไฟล์โหลดไม่สมบูรณ์',
                        'a' => 'ลองใช้ Chrome เป็นเบราว์เซอร์หลัก, ตรวจสอบว่ามีพื้นที่เพียงพอ (ต้องการ ~200 MB), หากใช้ Wi-Fi ลองสลับเป็น 4G/5G หรือกลับกัน'
                    ],
                    [
                        'q' => 'ติดตั้งไม่ได้ / Parse Error',
                        'a' => 'ตรวจสอบว่า Android เวอร์ชัน 8.0 ขึ้นไป, ลองดาวน์โหลดใหม่เผื่อไฟล์เสีย, ถ้ามี Tping เวอร์ชันเก่า ลบออกก่อนแล้วติดตั้งใหม่'
                    ],
                    [
                        'q' => 'หา Accessibility Service ไม่เจอ',
                        'a' => 'ไปที่ ตั้งค่า → การช่วยเหลือพิเศษ → แอพที่ดาวน์โหลด, บางรุ่นอยู่ที่ ตั้งค่า → เพิ่มเติม → การเข้าถึง, Samsung: ตั้งค่า → Accessibility → Installed services'
                    ],
                    [
                        'q' => 'แอพพิมพ์ไม่ได้ / ไม่ทำงาน',
                        'a' => 'ตรวจสอบว่าเปิด Accessibility Service แล้ว, ลองปิดแล้วเปิดใหม่, ตรวจสอบว่าแอพไม่ถูก Battery Optimization ปิด — เข้า ตั้งค่า → แบตเตอรี่ → ไม่จำกัด สำหรับ Tping'
                    ],
                    [
                        'q' => 'License Key ใช้ไม่ได้',
                        'a' => 'ตรวจสอบว่ากรอก Key ถูกต้อง (ตัวพิมพ์ใหญ่), ตรวจสอบว่ามีอินเทอร์เน็ต, หาก activate ไม่ได้ ติดต่อ LINE: @xmanstudio'
                    ],
                ];
                @endphp

                @foreach($faqs as $i => $faq)
                    <div class="border border-white/10 rounded-xl overflow-hidden">
                        <button @click="open = open === {{ $i }} ? null : {{ $i }}" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-white/5 transition-colors">
                            <span class="font-medium text-white">{{ $faq['q'] }}</span>
                            <svg class="w-5 h-5 text-indigo-400 transform transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === {{ $i }}" x-collapse class="px-5 pb-4 text-indigo-200 text-sm">
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- CTA --}}
        <div class="text-center space-y-4">
            <a href="{{ route('tping.download') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-violet-500 to-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-violet-500/30 hover:shadow-xl hover:shadow-violet-500/40 transition-all hover:-translate-y-0.5 text-lg">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                ดาวน์โหลด Tping ตอนนี้เลย
            </a>
            <p class="text-indigo-400 text-sm">
                ต้องการความช่วยเหลือ?
                <a href="https://line.me/ti/p/@xmanstudio" class="text-violet-400 hover:text-violet-300 underline" target="_blank">ติดต่อ LINE @xmanstudio</a>
            </p>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-12 text-indigo-400 text-sm">
            &copy; {{ date('Y') }} XMAN Studio | <a href="/" class="hover:text-white transition-colors">xman4289.com</a>
        </div>
    </div>
</div>

{{-- Phone Frame CSS --}}
<style>
    .phone-frame {
        width: 220px;
        height: 440px;
        background: linear-gradient(135deg, #374151, #1f2937);
        border-radius: 2.2rem;
        padding: 8px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255,255,255,0.1);
        position: relative;
    }
    .phone-frame::before {
        content: '';
        position: absolute;
        top: 12px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 6px;
        background: #111827;
        border-radius: 3px;
        z-index: 10;
    }
    .phone-screen {
        width: 100%;
        height: 100%;
        border-radius: 1.8rem;
        overflow: hidden;
    }
    @media (max-width: 768px) {
        .phone-frame {
            width: 200px;
            height: 400px;
        }
    }
</style>

{{-- Alpine.js for FAQ accordion --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
