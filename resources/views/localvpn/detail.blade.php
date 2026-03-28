@extends($publicLayout ?? 'layouts.app')

@section('title', 'LocalVPN - สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต')
@section('meta_description', 'LocalVPN สร้าง Virtual LAN ผ่านอินเทอร์เน็ต ให้มือถือทุกเครื่องเชื่อมต่อกันเหมือนอยู่วงแลนเดียวกัน รองรับ NAT Traversal เข้ารหัส WireGuard ไม่ต้องตั้งค่าเครือข่ายใดๆ')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-cyan-900 to-gray-900">

    {{-- ============================================================ --}}
    {{-- HERO SECTION --}}
    {{-- ============================================================ --}}
    <section class="relative py-24 lg:py-32 overflow-hidden">
        {{-- Background decoration --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 20% 30%, rgba(6, 182, 212, 0.4) 0%, transparent 50%), radial-gradient(circle at 80% 70%, rgba(20, 184, 166, 0.4) 0%, transparent 50%), radial-gradient(circle at 50% 50%, rgba(8, 145, 178, 0.2) 0%, transparent 70%);"></div>
        </div>
        <div class="absolute top-20 left-10 w-72 h-72 bg-cyan-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-teal-500/10 rounded-full blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                {{-- Badge --}}
                <div class="inline-flex items-center px-4 py-2 bg-cyan-500/20 rounded-full text-cyan-300 text-sm mb-8 backdrop-blur-sm border border-cyan-500/30">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg>
                    Virtual LAN over Internet
                </div>

                {{-- Title --}}
                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-white mb-6 tracking-tight">
                    Local<span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-teal-400 to-emerald-400">VPN</span>
                </h1>

                {{-- Tagline --}}
                <p class="text-xl sm:text-2xl text-gray-300 max-w-3xl mx-auto mb-4 leading-relaxed">
                    สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต
                </p>
                <p class="text-base text-gray-400 max-w-2xl mx-auto mb-10">
                    ให้มือถือทุกเครื่องเชื่อมต่อกันเหมือนอยู่วงแลนเดียวกัน
                    ไม่ต้องตั้งค่าเครือข่ายใดๆ
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row justify-center gap-4 mb-12">
                    <a href="{{ route('localvpn.download') }}"
                       class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-cyan-500/25 text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        ดาวน์โหลดฟรี
                    </a>
                    <a href="{{ route('localvpn.pricing') }}"
                       class="inline-flex items-center justify-center px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-bold rounded-xl transition-all backdrop-blur-sm border border-white/20 text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        ดูแพ็กเกจราคา
                    </a>
                </div>

                {{-- Feature badges --}}
                <div class="flex flex-wrap justify-center gap-3 text-sm">
                    <span class="px-4 py-2 bg-white/5 rounded-full border border-white/10 text-gray-400">Virtual LAN</span>
                    <span class="px-4 py-2 bg-white/5 rounded-full border border-white/10 text-gray-400">NAT Traversal</span>
                    <span class="px-4 py-2 bg-white/5 rounded-full border border-white/10 text-gray-400">WireGuard Encryption</span>
                    <span class="px-4 py-2 bg-white/5 rounded-full border border-white/10 text-gray-400">Real-time Discovery</span>
                    <span class="px-4 py-2 bg-white/5 rounded-full border border-white/10 text-gray-400">Cross-platform</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FEATURES SECTION (8 cards) --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">ฟีเจอร์หลัก</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">LocalVPN ออกแบบมาให้สร้างวง LAN เสมือนได้ง่ายและปลอดภัย</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Feature 1: สร้าง Virtual LAN ง่ายๆ --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-cyan-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">สร้าง Virtual LAN ง่ายๆ</h3>
                    <p class="text-gray-400 leading-relaxed text-sm">สร้างเครือข่าย LAN เสมือนได้ด้วยการแตะเพียงไม่กี่ครั้ง ไม่ต้องตั้งค่าซับซ้อน</p>
                </div>

                {{-- Feature 2: สแกนหาเครือข่าย --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-teal-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">สแกนหาเครือข่าย</h3>
                    <p class="text-gray-400 leading-relaxed text-sm">ค้นหาเครือข่าย LAN เสมือนที่มีอยู่แล้วและเข้าร่วมได้ทันที</p>
                </div>

                {{-- Feature 3: ตั้งรหัสความปลอดภัย --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-emerald-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">ตั้งรหัสความปลอดภัย</h3>
                    <p class="text-gray-400 leading-relaxed text-sm">ป้องกันเครือข่ายด้วยรหัสผ่าน เฉพาะคนที่มีรหัสเท่านั้นที่เข้าร่วมได้</p>
                </div>

                {{-- Feature 4: เห็นอุปกรณ์แบบ Real-time --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-sky-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">เห็นอุปกรณ์แบบ Real-time</h3>
                    <p class="text-gray-400 leading-relaxed text-sm">เห็นอุปกรณ์ทุกเครื่องในวง LAN แบบเรียลไทม์ พร้อมสถานะออนไลน์</p>
                </div>

                {{-- Feature 5: NAT Traversal อัตโนมัติ --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-orange-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">NAT Traversal อัตโนมัติ</h3>
                    <p class="text-gray-400 leading-relaxed text-sm">ทะลุ NAT/Firewall อัตโนมัติ เชื่อมต่อได้แม้อยู่คนละเครือข่าย</p>
                </div>

                {{-- Feature 6: เข้ารหัส WireGuard --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-indigo-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">เข้ารหัส WireGuard</h3>
                    <p class="text-gray-400 leading-relaxed text-sm">ข้อมูลถูกเข้ารหัสด้วย WireGuard Protocol ปลอดภัยระดับสูง</p>
                </div>

                {{-- Feature 7: รองรับ Android & iOS --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-pink-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">รองรับ Android & iOS</h3>
                    <p class="text-gray-400 leading-relaxed text-sm">ใช้งานได้ทั้ง Android และ iOS เชื่อมต่อข้ามแพลตฟอร์มได้</p>
                </div>

                {{-- Feature 8: Auto Update --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-violet-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">Auto Update</h3>
                    <p class="text-gray-400 leading-relaxed text-sm">อัพเดทแอพอัตโนมัติ ได้ฟีเจอร์ใหม่และแพทช์ความปลอดภัยทันที</p>
                </div>

                {{-- Feature 9: VPN Proxy --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-yellow-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">VPN Proxy มุดประเทศ</h3>
                    <p class="text-gray-400 leading-relaxed text-sm">เลือกประเทศที่ต้องการมุด เปลี่ยน IP ได้ทันที ฟรี 3 ประเทศ Premium ได้ทุกประเทศ</p>
                </div>

                {{-- Feature 10: VPN Gateway --}}
                <div class="group bg-gray-800/40 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50 hover:border-red-500/50 transition-all duration-300">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">VPN Gateway <span class="text-xs bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded-full">Premium</span></h3>
                    <p class="text-gray-400 leading-relaxed text-sm">โฮสต์เปิด VPN พาสมาชิกทั้งวง LAN ออก IP เดียวกัน เหมาะสำหรับเล่นเกมออนไลน์ด้วยกัน</p>
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
                <p class="text-gray-400">ไม่ต้องตั้งค่าเครือข่าย ติดตั้งง่าย ใช้งานได้ทันที</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                {{-- Step 1 --}}
                <div class="relative text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-cyan-600 to-teal-700 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-cyan-500/20">
                        <span class="text-3xl font-black text-white">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">ดาวน์โหลดแอพ</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">ดาวน์โหลด LocalVPN จากเว็บไซต์ แล้วติดตั้งบนมือถือของคุณ</p>
                    {{-- Connector arrow (hidden on mobile) --}}
                    <div class="hidden md:block absolute top-10 -right-4 w-8 text-gray-600">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="relative text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-teal-600 to-emerald-700 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-teal-500/20">
                        <span class="text-3xl font-black text-white">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">สร้างหรือเข้าร่วมวง LAN</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">สร้างเครือข่ายใหม่หรือสแกนหาเครือข่ายที่มีอยู่แล้วเข้าร่วม</p>
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
                    <h3 class="text-lg font-bold text-white mb-3">เชื่อมต่อกันได้เลย</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">อุปกรณ์ทุกเครื่องในวง LAN จะเห็นกันแบบเรียลไทม์ พร้อมใช้งานทันที</p>
                </div>
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
                <p class="text-gray-400 max-w-2xl mx-auto">LocalVPN ช่วยให้การเชื่อมต่ออุปกรณ์เป็นเรื่องง่าย</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                {{-- Use Case 1 --}}
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-800/30 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50">
                    <div class="w-12 h-12 bg-cyan-500/20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">เล่นเกม LAN กับเพื่อน</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">เล่นเกมที่ต้องใช้ LAN กับเพื่อนได้แม้อยู่คนละที่ เชื่อมต่อเหมือนอยู่วง LAN เดียวกัน</p>
                </div>

                {{-- Use Case 2 --}}
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-800/30 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50">
                    <div class="w-12 h-12 bg-teal-500/20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">แชร์ไฟล์ในทีม</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">สร้างเครือข่ายส่วนตัวสำหรับทีมงาน แชร์ไฟล์และทรัพยากรได้อย่างปลอดภัย</p>
                </div>

                {{-- Use Case 3 --}}
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-800/30 backdrop-blur-sm rounded-2xl p-8 border border-gray-700/50">
                    <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">เข้าถึงอุปกรณ์จากระยะไกล</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">เข้าถึงอุปกรณ์ที่บ้านหรือที่ทำงานจากทุกที่ เหมือนอยู่ในเครือข่ายเดียวกัน</p>
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
                <p class="text-gray-400">ใช้ฟรีตลอด (สมาชิกในวงสูงสุด 5 คน) หรืออัพเกรดเพื่อรองรับ 50 คน</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                {{-- Monthly --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700 hover:border-teal-500/50 transition-all text-center">
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
                    <a href="{{ route('localvpn.pricing') }}" class="block w-full text-center py-3 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>

                {{-- Yearly (Recommended) --}}
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border-2 border-cyan-500 hover:border-cyan-400 transition-all text-center">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-gradient-to-r from-cyan-500 to-teal-500 text-white text-xs font-bold px-4 py-1 rounded-full shadow-lg">แนะนำ</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">{{ $pricing['yearly']['name_th'] }}</h3>
                    <div class="text-4xl font-black text-cyan-400 mb-1">
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
                    <a href="{{ route('localvpn.pricing') }}" class="block w-full text-center py-3 bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-700 hover:to-teal-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-cyan-500/25">
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
                    <a href="{{ route('localvpn.pricing') }}" class="block w-full text-center py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-colors">
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
                        <span class="font-semibold text-white">LocalVPN คืออะไร?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 1" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">LocalVPN เป็นแอพที่สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต ทำให้อุปกรณ์ทุกเครื่องสามารถเชื่อมต่อกันเหมือนอยู่ในเครือข่าย LAN เดียวกัน แม้จะอยู่คนละสถานที่ รองรับ NAT Traversal และเข้ารหัสด้วย WireGuard</p>
                    </div>
                </div>

                {{-- FAQ 2 --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 overflow-hidden">
                    <button @click="openFaq = openFaq === 2 ? null : 2" class="w-full flex items-center justify-between p-6 text-left">
                        <span class="font-semibold text-white">ต้องตั้งค่าเครือข่ายเองไหม?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 2" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">ไม่ต้องตั้งค่าเครือข่ายใดๆ LocalVPN จะจัดการ NAT Traversal, IP assignment และการเข้ารหัสให้อัตโนมัติ แค่ติดตั้งแอพ สร้างหรือเข้าร่วมวง LAN ก็พร้อมใช้งานทันที</p>
                    </div>
                </div>

                {{-- FAQ 3 --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 overflow-hidden">
                    <button @click="openFaq = openFaq === 3 ? null : 3" class="w-full flex items-center justify-between p-6 text-left">
                        <span class="font-semibold text-white">รองรับกี่อุปกรณ์ในวง LAN?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 3" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">แพ็กเกจรายเดือนและรายปีรองรับหลายอุปกรณ์ในวง LAN เดียวกัน แพ็กเกจตลอดชีพรองรับอุปกรณ์ไม่จำกัด</p>
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
                        <span class="font-semibold text-white">ข้อมูลปลอดภัยไหม?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 5 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 5" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">ปลอดภัยสูงสุด ข้อมูลทั้งหมดถูกเข้ารหัสด้วย WireGuard Protocol ซึ่งเป็นมาตรฐานการเข้ารหัสระดับสากล ไม่มีใครสามารถดักจับข้อมูลระหว่างทางได้</p>
                    </div>
                </div>

                {{-- FAQ 6 --}}
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 overflow-hidden">
                    <button @click="openFaq = openFaq === 6 ? null : 6" class="w-full flex items-center justify-between p-6 text-left">
                        <span class="font-semibold text-white">ใช้ฟรีได้ไหม?</span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': openFaq === 6 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === 6" x-collapse class="px-6 pb-6">
                        <p class="text-gray-400 text-sm leading-relaxed">ได้ครับ ใช้ LocalVPN ฟรีตลอดไม่มีวันหมดอายุ รองรับสมาชิกในวงสูงสุด 5 คน ดาวน์โหลดแอพแล้วใช้งานได้ทันที อัพเกรดเมื่อต้องการสมาชิกในวงมากกว่า 5 คน</p>
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
            <div class="bg-gradient-to-r from-cyan-600/20 to-teal-600/20 backdrop-blur-sm rounded-3xl p-12 border border-cyan-500/30">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">พร้อมใช้ LocalVPN แล้วหรือยัง?</h2>
                <p class="text-gray-300 mb-8 max-w-xl mx-auto">ดาวน์โหลดฟรี ใช้ได้ตลอด เชื่อมต่ออุปกรณ์ง่ายๆ ไม่ต้องตั้งค่าเครือข่าย</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="{{ route('localvpn.download') }}"
                       class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-cyan-500/25 text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        ดาวน์โหลดแอพ
                    </a>
                    <a href="{{ route('localvpn.pricing') }}"
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
