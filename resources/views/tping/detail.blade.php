@extends($publicLayout ?? 'layouts.app')

@section('title', 'Tping - ช่วยพิมพ์ สำหรับผู้ที่ใช้นิ้วไม่สะดวก')
@section('meta_description', 'Tping แอพช่วยพิมพ์บน Android ออโต้กรอกฟอร์ม บันทึก Workflow พิมพ์แทนคุณในทุกแอพ เหมาะสำหรับผู้ที่ใช้นิ้วไม่สะดวก งาน Data Entry กรอกฟอร์มซ้ำๆ')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-indigo-900 to-gray-900">

    {{-- ============================================================ --}}
    {{-- HERO SECTION --}}
    {{-- ============================================================ --}}
    <section class="relative py-24 lg:py-32 overflow-hidden">
        {{-- Background decoration --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 20% 30%, rgba(139, 92, 246, 0.4) 0%, transparent 50%), radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.4) 0%, transparent 50%), radial-gradient(circle at 50% 50%, rgba(168, 85, 247, 0.2) 0%, transparent 70%);"></div>
        </div>
        <div class="absolute top-20 left-10 w-72 h-72 bg-violet-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                {{-- Badge --}}
                <div class="inline-flex items-center px-4 py-2 bg-violet-500/20 rounded-full text-violet-300 text-sm mb-8 backdrop-blur-sm border border-violet-500/30">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Android Accessibility Service
                </div>

                {{-- Title --}}
                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-white mb-6 tracking-tight">
                    T<span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 via-purple-400 to-blue-400">ping</span>
                </h1>

                {{-- Tagline --}}
                <p class="text-xl sm:text-2xl text-gray-300 max-w-3xl mx-auto mb-4 leading-relaxed">
                    ช่วยพิมพ์ สำหรับผู้ที่ใช้นิ้วไม่สะดวก
                </p>
                <p class="text-base text-gray-400 max-w-2xl mx-auto mb-10">
                    ออโต้กรอกฟอร์ม บันทึก Workflow พิมพ์แทนคุณในทุกแอพ
                    ด้วย Android Accessibility Service
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row justify-center gap-4 mb-12">
                    <a href="{{ route('tping.download') }}"
                       class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-violet-500/25 text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        ดาวน์โหลด ทดลองฟรี 24 ชม.
                    </a>
                    <a href="{{ route('tping.pricing') }}"
                       class="inline-flex items-center justify-center px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-bold rounded-xl transition-all backdrop-blur-sm border border-white/20 text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        ดูราคา
                    </a>
                </div>

                {{-- Feature badges --}}
                <div class="flex flex-wrap justify-center gap-3 text-sm">
                    <span class="px-4 py-2 bg-white/5 rounded-full border border-white/10 text-gray-400">Auto Fill</span>
                    <span class="px-4 py-2 bg-white/5 rounded-full border border-white/10 text-gray-400">Workflow Record</span>
                    <span class="px-4 py-2 bg-white/5 rounded-full border border-white/10 text-gray-400">Cloud Sync</span>
                    <span class="px-4 py-2 bg-white/5 rounded-full border border-white/10 text-gray-400">Data Profiles</span>
                    <span class="px-4 py-2 bg-white/5 rounded-full border border-white/10 text-gray-400">Multi-device</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FEATURES SECTION (6 cards) --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">ฟีเจอร์หลัก</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">Tping ออกแบบมาให้ช่วยพิมพ์แทนคุณได้อย่างสะดวกและปลอดภัย</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Feature 1: Auto Fill --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-violet-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Auto Fill</h3>
                    <p class="text-gray-400 leading-relaxed">กรอกฟอร์มอัตโนมัติในทุกแอพ แค่แตะปุ่มเดียว ข้อมูลที่ตั้งไว้จะถูกกรอกให้ทันที</p>
                </div>

                {{-- Feature 2: Workflow Record --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-blue-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Workflow Record</h3>
                    <p class="text-gray-400 leading-relaxed">บันทึกขั้นตอนการทำงาน แล้วเล่นซ้ำอัตโนมัติ ลดเวลางานซ้ำๆ จากชั่วโมงเหลือไม่กี่นาที</p>
                </div>

                {{-- Feature 3: Data Profiles --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-emerald-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Data Profiles</h3>
                    <p class="text-gray-400 leading-relaxed">สร้างโปรไฟล์ข้อมูลหลายชุด สลับใช้งานได้ง่าย เหมาะกับงานที่ต้องกรอกข้อมูลต่างกัน</p>
                </div>

                {{-- Feature 4: Cloud Sync --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-sky-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Cloud Sync</h3>
                    <p class="text-gray-400 leading-relaxed">ซิงค์ข้อมูลผ่านคลาวด์ ตั้งค่าครั้งเดียว ใช้ได้ทุกเครื่อง ไม่ต้องกลัวข้อมูลหาย</p>
                </div>

                {{-- Feature 5: Multi-device --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-orange-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Multi-device</h3>
                    <p class="text-gray-400 leading-relaxed">ใช้งานได้หลายเครื่องพร้อมกัน (แพ็กเกจ Lifetime) เปลี่ยนเครื่องได้โดยไม่ต้องซื้อใหม่</p>
                </div>

                {{-- Feature 6: Accessibility Service --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-pink-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Accessibility Service</h3>
                    <p class="text-gray-400 leading-relaxed">ทำงานผ่าน Android Accessibility ไม่ต้อง Root เครื่อง ปลอดภัย ใช้ได้กับทุกแอพ</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- HOW IT WORKS (3 steps) --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">เริ่มใช้งานง่ายๆ 3 ขั้นตอน</h2>
                <p class="text-gray-400">ไม่ต้อง Root เครื่อง ติดตั้งง่าย ใช้งานได้ทันที</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                {{-- Step 1 --}}
                <div class="relative text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-violet-600 to-purple-700 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-violet-500/20">
                        <span class="text-3xl font-black text-white">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">ดาวน์โหลดแอพ</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">ดาวน์โหลด Tping APK จากเว็บไซต์ แล้วติดตั้งบนมือถือ Android ของคุณ</p>
                    {{-- Connector arrow (hidden on mobile) --}}
                    <div class="hidden md:block absolute top-10 -right-4 w-8 text-gray-600">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="relative text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-blue-500/20">
                        <span class="text-3xl font-black text-white">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">เปิด Accessibility Service</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">เข้าไปที่ตั้งค่ามือถือ &gt; Accessibility &gt; เปิดใช้งาน Tping ตามคู่มือติดตั้ง</p>
                    {{-- Connector arrow --}}
                    <div class="hidden md:block absolute top-10 -right-4 w-8 text-gray-600">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-emerald-600 to-green-700 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-emerald-500/20">
                        <span class="text-3xl font-black text-white">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">ใช้งานได้เลย</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">ตั้งค่า Profile ข้อมูลของคุณ บันทึก Workflow แล้วให้ Tping ทำงานแทน</p>
                </div>
            </div>

            <div class="text-center mt-12">
                <a href="{{ route('tping.install-guide') }}"
                   class="inline-flex items-center text-violet-400 hover:text-violet-300 font-medium transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    อ่านคู่มือติดตั้งแบบละเอียด
                </a>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- USE CASES --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">เหมาะกับใคร?</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">Tping ช่วยให้ชีวิตง่ายขึ้นสำหรับทุกคนที่ต้องพิมพ์ซ้ำๆ</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                {{-- Use Case 1 --}}
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-800/30 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50">
                    <div class="w-12 h-12 bg-violet-500/20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">คนที่ต้องกรอกฟอร์มซ้ำๆ</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">สมัครงาน สมัครบริการ กรอกเอกสาร ข้อมูลซ้ำๆ เช่น ชื่อ ที่อยู่ เบอร์โทร ให้ Tping กรอกให้อัตโนมัติ</p>
                </div>

                {{-- Use Case 2 --}}
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-800/30 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">ผู้ที่ใช้นิ้วไม่สะดวก</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">ผู้สูงอายุ ผู้พิการทางนิ้วมือ หรือผู้ที่มีอาการบาดเจ็บ Tping ช่วยลดการพิมพ์ให้เหลือน้อยที่สุด</p>
                </div>

                {{-- Use Case 3 --}}
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-800/30 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50">
                    <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">งาน Data Entry</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">บันทึก Workflow การกรอกข้อมูลทั้งหมด แล้วเล่นซ้ำ ทำงาน 100 รายการเหมือนทำแค่รายการเดียว</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- PRICING PREVIEW --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">แพ็กเกจ</h2>
                <p class="text-gray-400">เริ่มทดลองฟรี 24 ชั่วโมง จากนั้นเลือกแพ็กเกจที่เหมาะกับคุณ</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                {{-- Monthly --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700 hover:border-blue-500/50 transition-all text-center">
                    <h3 class="text-lg font-bold text-white mb-2">{{ $pricing['monthly']['name_th'] }}</h3>
                    <div class="text-4xl font-black text-white mb-1">
                        <span class="text-lg font-normal text-gray-400">฿</span>{{ number_format($pricing['monthly']['price']) }}
                    </div>
                    <p class="text-gray-500 text-sm mb-6">{{ $pricing['monthly']['duration_days'] }} วัน</p>
                    <ul class="space-y-2 mb-8 text-sm text-left">
                        @foreach($pricing['monthly']['features'] as $feature)
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('tping.pricing') }}" class="block w-full text-center py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>

                {{-- Yearly (Recommended) --}}
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border-2 border-violet-500 hover:border-violet-400 transition-all text-center">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-gradient-to-r from-violet-500 to-purple-500 text-white text-xs font-bold px-4 py-1 rounded-full shadow-lg">แนะนำ</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">{{ $pricing['yearly']['name_th'] }}</h3>
                    <div class="text-4xl font-black text-violet-400 mb-1">
                        <span class="text-lg font-normal text-gray-400">฿</span>{{ number_format($pricing['yearly']['price']) }}
                    </div>
                    <p class="text-gray-500 text-sm mb-1">{{ $pricing['yearly']['duration_days'] }} วัน</p>
                    <span class="inline-block text-xs font-bold text-green-400 bg-green-400/10 px-3 py-1 rounded-full mb-6">ประหยัด 48%</span>
                    <ul class="space-y-2 mb-8 text-sm text-left">
                        @foreach($pricing['yearly']['features'] as $feature)
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('tping.pricing') }}" class="block w-full text-center py-3 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-violet-500/25">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>

                {{-- Lifetime --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700 hover:border-emerald-500/50 transition-all text-center">
                    <h3 class="text-lg font-bold text-white mb-2">{{ $pricing['lifetime']['name_th'] }}</h3>
                    <div class="text-4xl font-black text-emerald-400 mb-1">
                        <span class="text-lg font-normal text-gray-400">฿</span>{{ number_format($pricing['lifetime']['price']) }}
                    </div>
                    <p class="text-yellow-400 text-sm mb-6">ไม่มีวันหมดอายุ</p>
                    <ul class="space-y-2 mb-8 text-sm text-left">
                        @foreach($pricing['lifetime']['features'] as $feature)
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('tping.pricing') }}" class="block w-full text-center py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FAQ --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">คำถามที่พบบ่อย</h2>
            </div>

            <div class="space-y-4" x-data="{ openFaq: null }">
                {{-- FAQ 1 --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 overflow-hidden">
                    <button @click="openFaq = openFaq === 1 ? null : 1" class="w-full flex items-center justify-between p-6 text-left">
                        <span class="font-semibold text-white">Tping คืออะไร?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 1" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">Tping เป็นแอพ Android ที่ช่วยพิมพ์แทนคุณ ทำงานผ่าน Accessibility Service ช่วยกรอกฟอร์มอัตโนมัติ บันทึก Workflow การทำงาน และซิงค์ข้อมูลผ่านคลาวด์ เหมาะสำหรับผู้ที่ใช้นิ้วไม่สะดวก หรือต้องกรอกข้อมูลซ้ำๆ</p>
                    </div>
                </div>

                {{-- FAQ 2 --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 overflow-hidden">
                    <button @click="openFaq = openFaq === 2 ? null : 2" class="w-full flex items-center justify-between p-6 text-left">
                        <span class="font-semibold text-white">ต้อง Root เครื่องไหม?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 2" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">ไม่ต้อง Root เครื่อง Tping ใช้ Android Accessibility Service ซึ่งเป็นฟีเจอร์มาตรฐานของ Android แค่เปิดใช้งานในตั้งค่า Accessibility ก็พร้อมใช้งานได้เลย</p>
                    </div>
                </div>

                {{-- FAQ 3 --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 overflow-hidden">
                    <button @click="openFaq = openFaq === 3 ? null : 3" class="w-full flex items-center justify-between p-6 text-left">
                        <span class="font-semibold text-white">ใช้ได้กี่เครื่อง?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 3" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">แพ็กเกจรายเดือนและรายปีใช้ได้ 1 เครื่อง แพ็กเกจตลอดชีพสามารถใช้ได้สูงสุด 3 เครื่องพร้อมกัน</p>
                    </div>
                </div>

                {{-- FAQ 4 --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 overflow-hidden">
                    <button @click="openFaq = openFaq === 4 ? null : 4" class="w-full flex items-center justify-between p-6 text-left">
                        <span class="font-semibold text-white">ชำระเงินอย่างไร?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 4" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">รองรับ PromptPay (สแกน QR Code), โอนเงินผ่านธนาคาร, และชำระผ่าน Wallet บนเว็บไซต์ ชำระผ่าน Wallet จะได้รับส่วนลดเพิ่ม 10%</p>
                    </div>
                </div>

                {{-- FAQ 5 --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 overflow-hidden">
                    <button @click="openFaq = openFaq === 5 ? null : 5" class="w-full flex items-center justify-between p-6 text-left">
                        <span class="font-semibold text-white">ข้อมูลของฉันปลอดภัยไหม?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 5 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 5" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">ข้อมูลทั้งหมดถูกเข้ารหัสและเก็บไว้อย่างปลอดภัย ข้อมูลที่ซิงค์ผ่านคลาวด์เป็นข้อมูลเฉพาะของคุณเท่านั้น ไม่มีใครอื่นสามารถเข้าถึงได้</p>
                    </div>
                </div>

                {{-- FAQ 6 --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 overflow-hidden">
                    <button @click="openFaq = openFaq === 6 ? null : 6" class="w-full flex items-center justify-between p-6 text-left">
                        <span class="font-semibold text-white">ทดลองใช้ฟรีได้ไหม?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 6 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 6" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">ได้ครับ ทุกคนสามารถทดลองใช้ Tping ฟรี 24 ชั่วโมงเต็มโดยไม่ต้องผูกบัตรเครดิต ดาวน์โหลดแอพแล้วใช้งานได้ทันที</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FINAL CTA --}}
    {{-- ============================================================ --}}
    <section class="py-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <div class="bg-gradient-to-r from-violet-600/20 to-purple-600/20 backdrop-blur-sm rounded-3xl p-12 border border-violet-500/30">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">พร้อมใช้ Tping แล้วหรือยัง?</h2>
                <p class="text-gray-300 mb-8 max-w-xl mx-auto">ดาวน์โหลดฟรี ทดลองใช้ 24 ชั่วโมง ไม่ต้องผูกบัตร ไม่ต้อง Root เครื่อง</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="{{ route('tping.download') }}"
                       class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-violet-500/25 text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        ดาวน์โหลดแอพ
                    </a>
                    <a href="{{ route('tping.pricing') }}"
                       class="inline-flex items-center justify-center px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-bold rounded-xl transition-all backdrop-blur-sm border border-white/20 text-lg">
                        ดูราคาแพ็กเกจ
                    </a>
                </div>

                @if($version)
                <p class="text-gray-500 text-sm mt-6">
                    เวอร์ชั่นล่าสุด: v{{ $version->version }}
                    @if($version->release_date)
                        ({{ $version->release_date->format('d/m/Y') }})
                    @endif
                </p>
                @endif
            </div>
        </div>
    </section>

</div>
@endsection
