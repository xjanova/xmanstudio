@extends($publicLayout ?? 'layouts.app')

@section('title', 'LocalVPN - สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต')
@section('meta_description', 'LocalVPN สร้าง Virtual LAN ผ่านอินเทอร์เน็ต ให้มือถือทุกเครื่องเชื่อมต่อกันเหมือนอยู่วงแลนเดียวกัน รองรับ NAT Traversal เข้ารหัส WireGuard BitTorrent P2P ไม่ต้องตั้งค่าเครือข่ายใดๆ')

@push('styles')
<style>
    /* ── Animated gradient background ── */
    .hero-bg {
        background: linear-gradient(135deg, #0a0e21 0%, #0d1b2a 30%, #0a192f 60%, #0a0e21 100%);
        position: relative;
        overflow: hidden;
    }
    .hero-bg::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 600px 400px at 20% 30%, rgba(6,182,212,0.12) 0%, transparent 70%),
            radial-gradient(ellipse 500px 500px at 80% 60%, rgba(124,77,255,0.08) 0%, transparent 70%),
            radial-gradient(ellipse 400px 300px at 60% 80%, rgba(16,185,129,0.06) 0%, transparent 70%);
        animation: bgPulse 8s ease-in-out infinite alternate;
    }
    @keyframes bgPulse {
        0% { opacity: 0.6; }
        100% { opacity: 1; }
    }

    /* ── Grid lines overlay ── */
    .grid-overlay {
        background-image:
            linear-gradient(rgba(6,182,212,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(6,182,212,0.03) 1px, transparent 1px);
        background-size: 60px 60px;
    }

    /* ── Floating nodes animation ── */
    @keyframes float {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-20px) scale(1.05); }
    }
    @keyframes floatSlow {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-12px) rotate(3deg); }
    }
    .float-1 { animation: float 6s ease-in-out infinite; }
    .float-2 { animation: float 8s ease-in-out 1s infinite; }
    .float-3 { animation: floatSlow 7s ease-in-out 0.5s infinite; }
    .float-4 { animation: float 9s ease-in-out 2s infinite; }

    /* ── Pulse ring ── */
    @keyframes pulseRing {
        0% { transform: scale(0.8); opacity: 0.6; }
        50% { transform: scale(1.2); opacity: 0; }
        100% { transform: scale(0.8); opacity: 0; }
    }
    .pulse-ring { animation: pulseRing 3s ease-out infinite; }
    .pulse-ring-delay { animation: pulseRing 3s ease-out 1.5s infinite; }

    /* ── Data flow line ── */
    @keyframes dataFlow {
        0% { stroke-dashoffset: 100; }
        100% { stroke-dashoffset: 0; }
    }
    .data-line {
        stroke-dasharray: 8 12;
        animation: dataFlow 2s linear infinite;
    }

    /* ── Glow text ── */
    .glow-text {
        text-shadow: 0 0 40px rgba(6,182,212,0.3), 0 0 80px rgba(6,182,212,0.1);
    }

    /* ── Feature card hover ── */
    .feature-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 60px -20px rgba(6,182,212,0.2);
    }

    /* ── Scroll reveal ── */
    .reveal {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .reveal.visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* ── Torrent progress bar ── */
    @keyframes torrentProgress {
        0% { width: 0%; }
        20% { width: 15%; }
        40% { width: 35%; }
        60% { width: 58%; }
        80% { width: 82%; }
        100% { width: 100%; }
    }
    .torrent-bar {
        animation: torrentProgress 4s ease-in-out infinite;
    }

    /* ── Connection line draw ── */
    @keyframes drawLine {
        0% { stroke-dashoffset: 200; }
        100% { stroke-dashoffset: 0; }
    }
    .draw-line {
        stroke-dasharray: 200;
        animation: drawLine 3s ease-in-out forwards;
    }

    /* ── Stat counter ── */
    .stat-glow {
        background: linear-gradient(135deg, rgba(6,182,212,0.1) 0%, rgba(124,77,255,0.1) 100%);
        border: 1px solid rgba(6,182,212,0.15);
    }
</style>
@endpush

<div class="hero-bg">
<div class="grid-overlay">

    {{-- ============================================================ --}}
    {{-- HERO SECTION — animated network illustration --}}
    {{-- ============================================================ --}}
    <section class="relative min-h-[90vh] flex items-center py-20 lg:py-28 overflow-hidden">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                {{-- Left: Text content --}}
                <div class="text-center lg:text-left">
                    {{-- Badge --}}
                    <div class="inline-flex items-center px-4 py-2 bg-cyan-500/10 rounded-full text-cyan-400 text-sm mb-8 border border-cyan-500/20 backdrop-blur-sm">
                        <span class="w-2 h-2 bg-cyan-400 rounded-full mr-2 animate-pulse"></span>
                        Virtual LAN over Internet
                    </div>

                    {{-- Title --}}
                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-white mb-6 tracking-tight leading-[1.1] glow-text">
                        Local<span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-teal-400 to-emerald-400">VPN</span>
                    </h1>

                    {{-- Subtitle --}}
                    <p class="text-xl sm:text-2xl text-gray-300 mb-3 leading-relaxed">
                        สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต
                    </p>
                    <p class="text-base text-gray-500 mb-10 max-w-lg mx-auto lg:mx-0">
                        เชื่อมต่อมือถือทุกเครื่องเหมือนอยู่วงแลนเดียวกัน พร้อม VPN Proxy มุดประเทศ และ BitTorrent แชร์ไฟล์ P2P
                    </p>

                    {{-- CTA --}}
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-10">
                        <a href="{{ route('localvpn.download') }}"
                           class="group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-400 hover:to-teal-400 text-white font-bold rounded-2xl transition-all shadow-lg shadow-cyan-500/25 hover:shadow-cyan-500/40 text-lg">
                            <svg class="w-5 h-5 mr-2 group-hover:animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            ดาวน์โหลดฟรี
                        </a>
                        <a href="{{ route('localvpn.pricing') }}"
                           class="inline-flex items-center justify-center px-8 py-4 bg-white/5 hover:bg-white/10 text-white font-bold rounded-2xl transition-all border border-white/10 hover:border-white/20 text-lg backdrop-blur-sm">
                            ดูแพ็กเกจราคา
                        </a>
                    </div>

                    {{-- Stats bar --}}
                    <div class="flex flex-wrap gap-6 justify-center lg:justify-start">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-cyan-500/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <span class="text-sm text-gray-400">WireGuard Encrypted</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-teal-500/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <span class="text-sm text-gray-400">NAT Traversal</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="text-sm text-gray-400">Android & iOS</span>
                        </div>
                    </div>
                </div>

                {{-- Right: Network illustration (SVG) --}}
                <div class="relative hidden lg:block">
                    <div class="relative w-full aspect-square max-w-lg mx-auto">
                        {{-- Central hub --}}
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-24 h-24 float-3">
                            <div class="absolute inset-0 bg-cyan-500/20 rounded-2xl blur-xl pulse-ring"></div>
                            <div class="absolute inset-0 bg-cyan-500/10 rounded-2xl blur-xl pulse-ring-delay"></div>
                            <div class="relative w-full h-full bg-gradient-to-br from-cyan-500/30 to-teal-500/30 backdrop-blur-sm rounded-2xl border border-cyan-500/30 flex items-center justify-center">
                                <svg class="w-10 h-10 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                            </div>
                        </div>

                        {{-- Device nodes --}}
                        {{-- Phone 1 (top-left) --}}
                        <div class="absolute top-[10%] left-[10%] float-1">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500/20 to-indigo-500/20 backdrop-blur-sm rounded-xl border border-purple-500/20 flex items-center justify-center">
                                <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="block text-xs text-purple-300/70 text-center mt-1">10.10.0.2</span>
                        </div>

                        {{-- Laptop (top-right) --}}
                        <div class="absolute top-[8%] right-[12%] float-2">
                            <div class="w-16 h-16 bg-gradient-to-br from-sky-500/20 to-blue-500/20 backdrop-blur-sm rounded-xl border border-sky-500/20 flex items-center justify-center">
                                <svg class="w-7 h-7 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="block text-xs text-sky-300/70 text-center mt-1">10.10.0.3</span>
                        </div>

                        {{-- Tablet (bottom-left) --}}
                        <div class="absolute bottom-[12%] left-[8%] float-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-emerald-500/20 to-green-500/20 backdrop-blur-sm rounded-xl border border-emerald-500/20 flex items-center justify-center">
                                <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="block text-xs text-emerald-300/70 text-center mt-1">10.10.0.4</span>
                        </div>

                        {{-- Phone 2 (bottom-right) --}}
                        <div class="absolute bottom-[15%] right-[8%] float-1">
                            <div class="w-16 h-16 bg-gradient-to-br from-amber-500/20 to-orange-500/20 backdrop-blur-sm rounded-xl border border-amber-500/20 flex items-center justify-center">
                                <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="block text-xs text-amber-300/70 text-center mt-1">10.10.0.5</span>
                        </div>

                        {{-- VPN Globe (left-center) --}}
                        <div class="absolute top-[45%] left-[-5%] float-2">
                            <div class="w-14 h-14 bg-gradient-to-br from-rose-500/20 to-pink-500/20 backdrop-blur-sm rounded-xl border border-rose-500/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="block text-xs text-rose-300/70 text-center mt-1">VPN</span>
                        </div>

                        {{-- Connection lines (SVG overlay) --}}
                        <svg class="absolute inset-0 w-full h-full" viewBox="0 0 400 400" fill="none">
                            {{-- Center to top-left --}}
                            <line x1="200" y1="200" x2="70" y2="70" class="data-line" stroke="rgba(6,182,212,0.2)" stroke-width="1"/>
                            {{-- Center to top-right --}}
                            <line x1="200" y1="200" x2="330" y2="60" class="data-line" stroke="rgba(6,182,212,0.2)" stroke-width="1" style="animation-delay:0.5s"/>
                            {{-- Center to bottom-left --}}
                            <line x1="200" y1="200" x2="65" y2="330" class="data-line" stroke="rgba(6,182,212,0.2)" stroke-width="1" style="animation-delay:1s"/>
                            {{-- Center to bottom-right --}}
                            <line x1="200" y1="200" x2="340" y2="310" class="data-line" stroke="rgba(6,182,212,0.2)" stroke-width="1" style="animation-delay:1.5s"/>
                            {{-- Center to VPN --}}
                            <line x1="200" y1="200" x2="20" y2="195" class="data-line" stroke="rgba(244,63,94,0.2)" stroke-width="1" style="animation-delay:2s"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FEATURE HIGHLIGHT — 3 big cards with icons --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8 reveal" id="features">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-cyan-400 text-sm font-semibold tracking-widest uppercase">Features</span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mt-3 mb-4">ทำได้มากกว่า VPN ทั่วไป</h2>
                <p class="text-gray-400 max-w-2xl mx-auto text-lg">รวม 3 พลังไว้ในแอพเดียว: Virtual LAN, VPN Proxy มุดประเทศ, และ BitTorrent แชร์ไฟล์</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                {{-- Virtual LAN --}}
                <div class="feature-card group relative bg-gradient-to-br from-gray-800/60 to-gray-900/60 backdrop-blur-sm rounded-3xl p-8 border border-gray-700/50 hover:border-cyan-500/40 overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-500/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:bg-cyan-500/10 transition-colors"></div>
                    <div class="relative">
                        <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-cyan-500/20 group-hover:shadow-cyan-500/40 transition-shadow">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Virtual LAN</h3>
                        <p class="text-gray-400 leading-relaxed mb-6">สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต อุปกรณ์ทุกเครื่องเห็นกันแบบ Real-time ทะลุ NAT อัตโนมัติ</p>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-cyan-400 rounded-full mr-2.5"></span>สร้าง/เข้าร่วมเครือข่ายทันที</li>
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-cyan-400 rounded-full mr-2.5"></span>UDP Hole Punching อัตโนมัติ</li>
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-cyan-400 rounded-full mr-2.5"></span>Server Relay fallback</li>
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-cyan-400 rounded-full mr-2.5"></span>WireGuard encryption</li>
                        </ul>
                    </div>
                </div>

                {{-- VPN Proxy --}}
                <div class="feature-card group relative bg-gradient-to-br from-gray-800/60 to-gray-900/60 backdrop-blur-sm rounded-3xl p-8 border border-gray-700/50 hover:border-purple-500/40 overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:bg-purple-500/10 transition-colors"></div>
                    <div class="relative">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-purple-500/20 group-hover:shadow-purple-500/40 transition-shadow">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">VPN Proxy มุดประเทศ</h3>
                        <p class="text-gray-400 leading-relaxed mb-6">เปลี่ยน IP เป็นประเทศที่ต้องการ เข้าถึงเว็บที่ถูกบล็อก พร้อมวัด Latency แบบ Real-time</p>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-purple-400 rounded-full mr-2.5"></span>ฟรี: JP, US, KR</li>
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-purple-400 rounded-full mr-2.5"></span>Premium: ทุกประเทศ</li>
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-purple-400 rounded-full mr-2.5"></span>Latency gauge แบบ Real-time</li>
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-purple-400 rounded-full mr-2.5"></span>OpenVPN protocol</li>
                        </ul>
                    </div>
                </div>

                {{-- BitTorrent --}}
                <div class="feature-card group relative bg-gradient-to-br from-gray-800/60 to-gray-900/60 backdrop-blur-sm rounded-3xl p-8 border border-gray-700/50 hover:border-emerald-500/40 overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:bg-emerald-500/10 transition-colors"></div>
                    <div class="relative">
                        <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-emerald-500/20 group-hover:shadow-emerald-500/40 transition-shadow">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">BitTorrent แชร์ไฟล์</h3>
                        <p class="text-gray-400 leading-relaxed mb-6">แชร์ไฟล์ P2P ภายในวง LAN คำนวณ Hash อัตโนมัติ ดาวน์โหลดข้ามเครือข่ายผ่าน Server Relay</p>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-emerald-400 rounded-full mr-2.5"></span>แชร์ไฟล์แบบ P2P</li>
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-emerald-400 rounded-full mr-2.5"></span>SHA-256 integrity check</li>
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-emerald-400 rounded-full mr-2.5"></span>Server relay สำหรับข้ามเครือข่าย</li>
                            <li class="flex items-center text-gray-300"><span class="w-1.5 h-1.5 bg-emerald-400 rounded-full mr-2.5"></span>Background download</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- HOW IT WORKS — visual timeline --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8 reveal">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-teal-400 text-sm font-semibold tracking-widest uppercase">How it works</span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mt-3 mb-4">เริ่มใช้งานง่ายมาก</h2>
                <p class="text-gray-400 text-lg">3 ขั้นตอนก็เชื่อมต่อได้</p>
            </div>

            <div class="relative">
                {{-- Timeline line --}}
                <div class="hidden md:block absolute top-24 left-[16.67%] right-[16.67%] h-0.5 bg-gradient-to-r from-cyan-500/30 via-teal-500/30 to-emerald-500/30"></div>

                <div class="grid md:grid-cols-3 gap-10">
                    {{-- Step 1 --}}
                    <div class="relative text-center group">
                        <div class="relative z-10 w-20 h-20 mx-auto mb-8">
                            <div class="absolute inset-0 bg-cyan-500/20 rounded-2xl blur-xl group-hover:bg-cyan-500/30 transition-colors"></div>
                            <div class="relative w-full h-full bg-gradient-to-br from-cyan-500 to-cyan-700 rounded-2xl flex items-center justify-center shadow-lg shadow-cyan-500/30">
                                <span class="text-3xl font-black text-white">1</span>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">ดาวน์โหลดแอพ</h3>
                        <p class="text-gray-400 leading-relaxed">ดาวน์โหลด LocalVPN แล้วติดตั้ง ใช้ได้ทั้ง Android และ iOS</p>
                    </div>

                    {{-- Step 2 --}}
                    <div class="relative text-center group">
                        <div class="relative z-10 w-20 h-20 mx-auto mb-8">
                            <div class="absolute inset-0 bg-teal-500/20 rounded-2xl blur-xl group-hover:bg-teal-500/30 transition-colors"></div>
                            <div class="relative w-full h-full bg-gradient-to-br from-teal-500 to-teal-700 rounded-2xl flex items-center justify-center shadow-lg shadow-teal-500/30">
                                <span class="text-3xl font-black text-white">2</span>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">สร้างหรือเข้าร่วมวง LAN</h3>
                        <p class="text-gray-400 leading-relaxed">สร้างเครือข่ายใหม่ หรือสแกนหาเครือข่ายที่มีแล้วเข้าร่วม</p>
                    </div>

                    {{-- Step 3 --}}
                    <div class="relative text-center group">
                        <div class="relative z-10 w-20 h-20 mx-auto mb-8">
                            <div class="absolute inset-0 bg-emerald-500/20 rounded-2xl blur-xl group-hover:bg-emerald-500/30 transition-colors"></div>
                            <div class="relative w-full h-full bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                <span class="text-3xl font-black text-white">3</span>
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">เชื่อมต่อกันได้เลย!</h3>
                        <p class="text-gray-400 leading-relaxed">อุปกรณ์ทุกเครื่องเห็นกันแบบ Real-time พร้อมแชร์ไฟล์ มุด VPN</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- BITTORRENT DEEP DIVE --}}
    {{-- ============================================================ --}}
    <section class="py-24 px-4 sm:px-6 lg:px-8 reveal">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                {{-- Left: Torrent visualization --}}
                <div class="relative">
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-3xl border border-gray-700/50 p-8 overflow-hidden">
                        {{-- Mock torrent UI --}}
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-emerald-500/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div>
                                    <div class="text-white font-semibold text-sm">project-files.zip</div>
                                    <div class="text-gray-500 text-xs">245.8 MB</div>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 text-xs font-bold rounded-full">Seeding</span>
                        </div>

                        {{-- Progress bar --}}
                        <div class="mb-4">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>ดาวน์โหลดเสร็จสิ้น</span>
                                <span>100%</span>
                            </div>
                            <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-emerald-500 to-green-400 rounded-full w-full"></div>
                            </div>
                        </div>

                        {{-- Another file downloading --}}
                        <div class="flex items-center justify-between mb-4 mt-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-cyan-500/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <div class="text-white font-semibold text-sm">video-edit-v2.mp4</div>
                                    <div class="text-gray-500 text-xs">1.2 GB</div>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-cyan-500/20 text-cyan-400 text-xs font-bold rounded-full">Downloading</span>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>3 peers</span>
                                <span class="tabular-nums">58%</span>
                            </div>
                            <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-cyan-500 to-teal-400 rounded-full torrent-bar"></div>
                            </div>
                        </div>

                        {{-- Peer list --}}
                        <div class="mt-6 pt-4 border-t border-gray-700/50">
                            <div class="text-xs text-gray-500 mb-3 font-medium">Peers ในวง LAN</div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                        <span class="text-xs text-gray-300">iPhone-Somchai</span>
                                        <span class="text-xs text-gray-600">10.10.0.2</span>
                                    </div>
                                    <span class="text-xs text-emerald-400">12.5 MB/s</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                        <span class="text-xs text-gray-300">Galaxy-Nong</span>
                                        <span class="text-xs text-gray-600">10.10.0.3</span>
                                    </div>
                                    <span class="text-xs text-emerald-400">8.3 MB/s</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                                        <span class="text-xs text-gray-300">Pixel-Boss</span>
                                        <span class="text-xs text-gray-600">Relay</span>
                                    </div>
                                    <span class="text-xs text-amber-400">2.1 MB/s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Description --}}
                <div>
                    <span class="text-emerald-400 text-sm font-semibold tracking-widest uppercase">BitTorrent P2P</span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mt-3 mb-6">แชร์ไฟล์แบบ Peer-to-Peer</h2>
                    <p class="text-gray-400 text-lg leading-relaxed mb-8">
                        แชร์ไฟล์ขนาดใหญ่ภายในวง LAN ด้วยเทคโนโลยี BitTorrent ไม่ต้องอัพโหลดไปคลาวด์ ส่งตรงถึงกันได้เลย
                    </p>

                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-white font-semibold mb-1">เร็วกว่าส่งผ่านคลาวด์</h4>
                                <p class="text-gray-400 text-sm">ส่งตรง peer-to-peer ไม่ต้องรอ upload/download ผ่าน server กลาง</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-white font-semibold mb-1">ตรวจสอบความถูกต้อง SHA-256</h4>
                                <p class="text-gray-400 text-sm">ทุกไฟล์ถูกคำนวณ hash และตรวจสอบอัตโนมัติ มั่นใจว่าไฟล์ไม่ถูกแก้ไข</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            </div>
                            <div>
                                <h4 class="text-white font-semibold mb-1">Server Relay สำหรับข้ามเครือข่าย</h4>
                                <p class="text-gray-400 text-sm">เมื่อ NAT ทะลุไม่ได้ ไฟล์จะถูกส่งผ่าน server relay ให้อัตโนมัติ</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- DETAILED FEATURES — alternating layout --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8 reveal">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-sky-400 text-sm font-semibold tracking-widest uppercase">All Features</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-white mt-3 mb-4">ฟีเจอร์ครบจบในแอพเดียว</h2>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                {{-- Feature: สร้าง Virtual LAN --}}
                <div class="group bg-gray-800/30 hover:bg-gray-800/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/30 hover:border-cyan-500/30 transition-all duration-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-11 h-11 bg-cyan-500/10 rounded-xl flex items-center justify-center group-hover:bg-cyan-500/20 transition-colors">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold mb-1">สร้าง Virtual LAN ง่ายๆ</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">แตะไม่กี่ครั้งก็สร้างเครือข่ายเสมือนได้</p>
                        </div>
                    </div>
                </div>

                {{-- Feature: สแกนหาเครือข่าย --}}
                <div class="group bg-gray-800/30 hover:bg-gray-800/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/30 hover:border-teal-500/30 transition-all duration-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-11 h-11 bg-teal-500/10 rounded-xl flex items-center justify-center group-hover:bg-teal-500/20 transition-colors">
                            <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold mb-1">สแกนหาเครือข่าย</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">ค้นหาวง LAN ที่มีอยู่แล้วเข้าร่วมทันที</p>
                        </div>
                    </div>
                </div>

                {{-- Feature: ตั้งรหัสความปลอดภัย --}}
                <div class="group bg-gray-800/30 hover:bg-gray-800/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/30 hover:border-emerald-500/30 transition-all duration-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-11 h-11 bg-emerald-500/10 rounded-xl flex items-center justify-center group-hover:bg-emerald-500/20 transition-colors">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold mb-1">ตั้งรหัสความปลอดภัย</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">ป้องกันเครือข่ายด้วยรหัสผ่าน</p>
                        </div>
                    </div>
                </div>

                {{-- Feature: เห็นอุปกรณ์ Real-time --}}
                <div class="group bg-gray-800/30 hover:bg-gray-800/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/30 hover:border-sky-500/30 transition-all duration-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-11 h-11 bg-sky-500/10 rounded-xl flex items-center justify-center group-hover:bg-sky-500/20 transition-colors">
                            <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold mb-1">เห็นอุปกรณ์ Real-time</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">เห็นอุปกรณ์ทุกเครื่องพร้อมสถานะออนไลน์</p>
                        </div>
                    </div>
                </div>

                {{-- Feature: NAT Traversal --}}
                <div class="group bg-gray-800/30 hover:bg-gray-800/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/30 hover:border-orange-500/30 transition-all duration-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-11 h-11 bg-orange-500/10 rounded-xl flex items-center justify-center group-hover:bg-orange-500/20 transition-colors">
                            <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold mb-1">NAT Traversal อัตโนมัติ</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">ทะลุ NAT/Firewall เชื่อมต่อข้ามเครือข่าย</p>
                        </div>
                    </div>
                </div>

                {{-- Feature: เข้ารหัส WireGuard --}}
                <div class="group bg-gray-800/30 hover:bg-gray-800/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/30 hover:border-indigo-500/30 transition-all duration-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-11 h-11 bg-indigo-500/10 rounded-xl flex items-center justify-center group-hover:bg-indigo-500/20 transition-colors">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold mb-1">เข้ารหัส WireGuard</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">ข้อมูลเข้ารหัสระดับสากล ปลอดภัยสูงสุด</p>
                        </div>
                    </div>
                </div>

                {{-- Feature: Android & iOS --}}
                <div class="group bg-gray-800/30 hover:bg-gray-800/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/30 hover:border-pink-500/30 transition-all duration-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-11 h-11 bg-pink-500/10 rounded-xl flex items-center justify-center group-hover:bg-pink-500/20 transition-colors">
                            <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold mb-1">Android & iOS</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">รองรับทั้งสองแพลตฟอร์ม ใช้ข้ามกันได้</p>
                        </div>
                    </div>
                </div>

                {{-- Feature: Auto Update --}}
                <div class="group bg-gray-800/30 hover:bg-gray-800/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/30 hover:border-violet-500/30 transition-all duration-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-11 h-11 bg-violet-500/10 rounded-xl flex items-center justify-center group-hover:bg-violet-500/20 transition-colors">
                            <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold mb-1">Auto Update</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">อัพเดทอัตโนมัติ ได้ฟีเจอร์ใหม่ทันที</p>
                        </div>
                    </div>
                </div>

                {{-- Feature: VPN Proxy --}}
                <div class="group bg-gray-800/30 hover:bg-gray-800/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/30 hover:border-yellow-500/30 transition-all duration-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-11 h-11 bg-yellow-500/10 rounded-xl flex items-center justify-center group-hover:bg-yellow-500/20 transition-colors">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold mb-1">VPN Proxy มุดประเทศ</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">เปลี่ยน IP ได้ทันที ฟรี 3 ประเทศ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- USE CASES — with illustrations --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8 reveal">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-amber-400 text-sm font-semibold tracking-widest uppercase">Use Cases</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-white mt-3 mb-4">เหมาะกับใคร?</h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-cyan-500/10 to-cyan-500/5 rounded-2xl p-6 border border-cyan-500/10 hover:border-cyan-500/30 transition-all">
                    <div class="text-4xl mb-4">🎮</div>
                    <h3 class="text-lg font-bold text-white mb-2">เล่นเกม LAN</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">เล่นเกมที่ต้องใช้ LAN กับเพื่อนได้แม้อยู่คนละที่</p>
                </div>
                <div class="bg-gradient-to-br from-teal-500/10 to-teal-500/5 rounded-2xl p-6 border border-teal-500/10 hover:border-teal-500/30 transition-all">
                    <div class="text-4xl mb-4">📁</div>
                    <h3 class="text-lg font-bold text-white mb-2">แชร์ไฟล์ในทีม</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">ส่งไฟล์ขนาดใหญ่ P2P โดยตรง ไม่ต้องพึ่งคลาวด์</p>
                </div>
                <div class="bg-gradient-to-br from-purple-500/10 to-purple-500/5 rounded-2xl p-6 border border-purple-500/10 hover:border-purple-500/30 transition-all">
                    <div class="text-4xl mb-4">🌏</div>
                    <h3 class="text-lg font-bold text-white mb-2">มุดเว็บต่างประเทศ</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">เปลี่ยน IP เป็นประเทศที่ต้องการ เข้าเว็บที่ถูกบล็อก</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-500/10 to-emerald-500/5 rounded-2xl p-6 border border-emerald-500/10 hover:border-emerald-500/30 transition-all">
                    <div class="text-4xl mb-4">🏠</div>
                    <h3 class="text-lg font-bold text-white mb-2">เข้าถึงจากระยะไกล</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">เข้าถึงอุปกรณ์ที่บ้านหรือที่ทำงานจากทุกที่</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- PRICING --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8 reveal">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-green-400 text-sm font-semibold tracking-widest uppercase">Pricing</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-white mt-3 mb-4">แพ็กเกจ</h2>
                <p class="text-gray-400 text-lg">ใช้ฟรีตลอด (สมาชิกสูงสุด 5 คน) หรืออัพเกรดเพื่อรองรับ 50 คน</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                {{-- Monthly --}}
                <div class="bg-gray-800/40 backdrop-blur-sm rounded-3xl p-8 border border-gray-700/50 hover:border-teal-500/30 transition-all text-center">
                    <h3 class="text-lg font-bold text-white mb-2">{{ $pricing['monthly']['name_th'] }}</h3>
                    <div class="text-4xl font-black text-white mb-1">
                        <span class="text-lg font-normal text-gray-500">฿</span>{{ number_format($pricing['monthly']['price']) }}
                    </div>
                    <p class="text-gray-500 text-sm mb-6">{{ $pricing['monthly']['duration_days'] }} วัน</p>
                    <ul class="space-y-2.5 mb-8 text-sm text-left">
                        @foreach($pricing['monthly']['features'] as $feature)
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2.5 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('localvpn.pricing') }}" class="block w-full text-center py-3.5 bg-teal-600 hover:bg-teal-500 text-white font-semibold rounded-2xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>

                {{-- Yearly (Recommended) --}}
                <div class="relative bg-gray-800/40 backdrop-blur-sm rounded-3xl p-8 border-2 border-cyan-500/50 hover:border-cyan-400 transition-all text-center shadow-lg shadow-cyan-500/10">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                        <span class="bg-gradient-to-r from-cyan-500 to-teal-500 text-white text-xs font-bold px-5 py-1.5 rounded-full shadow-lg shadow-cyan-500/30">แนะนำ</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">{{ $pricing['yearly']['name_th'] }}</h3>
                    <div class="text-4xl font-black text-cyan-400 mb-1">
                        <span class="text-lg font-normal text-gray-500">฿</span>{{ number_format($pricing['yearly']['price']) }}
                    </div>
                    <p class="text-gray-500 text-sm mb-1">{{ $pricing['yearly']['duration_days'] }} วัน</p>
                    <span class="inline-block text-xs font-bold text-green-400 bg-green-400/10 px-3 py-1 rounded-full mb-6">ประหยัด 48%</span>
                    <ul class="space-y-2.5 mb-8 text-sm text-left">
                        @foreach($pricing['yearly']['features'] as $feature)
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2.5 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('localvpn.pricing') }}" class="block w-full text-center py-3.5 bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-400 hover:to-teal-400 text-white font-semibold rounded-2xl transition-all shadow-lg shadow-cyan-500/20">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>

                {{-- Lifetime --}}
                <div class="bg-gray-800/40 backdrop-blur-sm rounded-3xl p-8 border border-gray-700/50 hover:border-emerald-500/30 transition-all text-center">
                    <h3 class="text-lg font-bold text-white mb-2">{{ $pricing['lifetime']['name_th'] }}</h3>
                    <div class="text-4xl font-black text-emerald-400 mb-1">
                        <span class="text-lg font-normal text-gray-500">฿</span>{{ number_format($pricing['lifetime']['price']) }}
                    </div>
                    <p class="text-yellow-400 text-sm mb-6">ไม่มีวันหมดอายุ</p>
                    <ul class="space-y-2.5 mb-8 text-sm text-left">
                        @foreach($pricing['lifetime']['features'] as $feature)
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2.5 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('localvpn.pricing') }}" class="block w-full text-center py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-2xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FREE vs PREMIUM comparison --}}
    {{-- ============================================================ --}}
    <section class="py-16 px-4 sm:px-6 lg:px-8 reveal">
        <div class="max-w-4xl mx-auto">
            <div class="bg-gray-800/30 backdrop-blur-sm rounded-3xl border border-gray-700/50 overflow-hidden">
                <div class="p-8 pb-0">
                    <h3 class="text-2xl font-bold text-white text-center mb-6">Free vs Premium</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-700/50">
                                <th class="text-left text-gray-400 font-medium p-4 pl-8">ฟีเจอร์</th>
                                <th class="text-center text-gray-400 font-medium p-4">Free</th>
                                <th class="text-center text-cyan-400 font-medium p-4 pr-8">Premium</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/30">
                            <tr>
                                <td class="p-4 pl-8 text-gray-300">สมาชิกในวง LAN</td>
                                <td class="p-4 text-center text-gray-400">5 คน</td>
                                <td class="p-4 pr-8 text-center text-white font-semibold">50 คน</td>
                            </tr>
                            <tr>
                                <td class="p-4 pl-8 text-gray-300">VPN Proxy ประเทศ</td>
                                <td class="p-4 text-center text-gray-400">JP, US, KR</td>
                                <td class="p-4 pr-8 text-center text-white font-semibold">ทุกประเทศ</td>
                            </tr>
                            <tr>
                                <td class="p-4 pl-8 text-gray-300">BitTorrent แชร์ไฟล์</td>
                                <td class="p-4 text-center"><svg class="w-5 h-5 text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="p-4 pr-8 text-center"><svg class="w-5 h-5 text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="p-4 pl-8 text-gray-300">NAT Traversal</td>
                                <td class="p-4 text-center"><svg class="w-5 h-5 text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="p-4 pr-8 text-center"><svg class="w-5 h-5 text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="p-4 pl-8 text-gray-300">WireGuard Encryption</td>
                                <td class="p-4 text-center"><svg class="w-5 h-5 text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="p-4 pr-8 text-center"><svg class="w-5 h-5 text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="p-4 pl-8 text-gray-300">Premium Proxy Servers</td>
                                <td class="p-4 text-center"><svg class="w-5 h-5 text-gray-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></td>
                                <td class="p-4 pr-8 text-center"><svg class="w-5 h-5 text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="p-4 pl-8 text-gray-300">Auto Update</td>
                                <td class="p-4 text-center"><svg class="w-5 h-5 text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="p-4 pr-8 text-center"><svg class="w-5 h-5 text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FAQ --}}
    {{-- ============================================================ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8 reveal">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-12">
                <span class="text-gray-400 text-sm font-semibold tracking-widest uppercase">FAQ</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-white mt-3 mb-4">คำถามที่พบบ่อย</h2>
            </div>

            <div class="space-y-3" x-data="{ openFaq: null }">
                @php
                $faqs = [
                    ['q' => 'LocalVPN คืออะไร?', 'a' => 'LocalVPN เป็นแอพที่สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต ทำให้อุปกรณ์ทุกเครื่องเชื่อมต่อกันเหมือนอยู่ LAN เดียวกัน พร้อมฟีเจอร์ VPN Proxy มุดประเทศ และ BitTorrent แชร์ไฟล์'],
                    ['q' => 'ต้องตั้งค่าเครือข่ายเองไหม?', 'a' => 'ไม่ต้อง LocalVPN จัดการ NAT Traversal, IP assignment และการเข้ารหัสให้อัตโนมัติ แค่ติดตั้ง สร้างหรือเข้าร่วมวง LAN ก็พร้อมใช้งาน'],
                    ['q' => 'ใช้ฟรีได้ไหม?', 'a' => 'ได้ ใช้ LocalVPN ฟรีตลอดไม่มีวันหมดอายุ รองรับสมาชิกสูงสุด 5 คน VPN Proxy ฟรี 3 ประเทศ (JP, US, KR) อัพเกรดเมื่อต้องการมากกว่านี้'],
                    ['q' => 'BitTorrent แชร์ไฟล์ทำงานอย่างไร?', 'a' => 'เลือกไฟล์ที่ต้องการแชร์ แอพจะคำนวณ hash อัตโนมัติ สมาชิกในวง LAN สามารถดาวน์โหลดแบบ P2P โดยตรง หรือผ่าน server relay เมื่ออยู่คนละเครือข่าย'],
                    ['q' => 'ข้อมูลปลอดภัยไหม?', 'a' => 'ปลอดภัยสูงสุด ข้อมูลทั้งหมดถูกเข้ารหัสด้วย WireGuard Protocol มาตรฐานสากล ไม่มีใครดักจับข้อมูลระหว่างทางได้'],
                    ['q' => 'ชำระเงินอย่างไร?', 'a' => 'รองรับ PromptPay (สแกน QR), โอนเงินธนาคาร, และ Wallet บนเว็บไซต์ ชำระผ่าน Wallet ได้ส่วนลดเพิ่ม 10%'],
                ];
                @endphp

                @foreach($faqs as $i => $faq)
                <div class="bg-gray-800/30 backdrop-blur-sm rounded-2xl border border-gray-700/30 overflow-hidden">
                    <button @click="openFaq = openFaq === {{ $i }} ? null : {{ $i }}" class="w-full flex items-center justify-between p-5 text-left hover:bg-gray-800/30 transition-colors">
                        <span class="font-semibold text-white pr-4">{{ $faq['q'] }}</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform flex-shrink-0" :class="{ 'rotate-180': openFaq === {{ $i }} }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openFaq === {{ $i }}" x-collapse class="px-5 pb-5">
                        <p class="text-gray-400 text-sm leading-relaxed">{{ $faq['a'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- FINAL CTA --}}
    {{-- ============================================================ --}}
    <section class="py-24 px-4 sm:px-6 lg:px-8 reveal">
        <div class="max-w-4xl mx-auto text-center">
            <div class="relative bg-gradient-to-br from-cyan-600/10 to-teal-600/10 backdrop-blur-sm rounded-3xl p-12 lg:p-16 border border-cyan-500/20 overflow-hidden">
                {{-- Decorative elements --}}
                <div class="absolute top-0 left-0 w-40 h-40 bg-cyan-500/5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-60 h-60 bg-teal-500/5 rounded-full translate-x-1/3 translate-y-1/3"></div>

                <div class="relative">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4">พร้อมใช้ LocalVPN?</h2>
                    <p class="text-gray-300 mb-10 max-w-xl mx-auto text-lg">ดาวน์โหลดฟรี ใช้ได้ตลอด เชื่อมต่ออุปกรณ์ มุดเว็บ แชร์ไฟล์ ครบในแอพเดียว</p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="{{ route('localvpn.download') }}"
                           class="group inline-flex items-center justify-center px-10 py-4 bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-400 hover:to-teal-400 text-white font-bold rounded-2xl transition-all shadow-lg shadow-cyan-500/25 hover:shadow-cyan-500/40 text-lg">
                            <svg class="w-5 h-5 mr-2 group-hover:animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            ดาวน์โหลดแอพ
                        </a>
                        <a href="{{ route('localvpn.pricing') }}"
                           class="inline-flex items-center justify-center px-10 py-4 bg-white/5 hover:bg-white/10 text-white font-bold rounded-2xl transition-all border border-white/10 hover:border-white/20 text-lg">
                            ดูราคาแพ็กเกจ
                        </a>
                    </div>

                    @if($version)
                    <p class="text-gray-600 text-sm mt-8">
                        เวอร์ชั่นล่าสุด: v{{ $version->version }}
                        @if($version->release_date)
                            ({{ $version->release_date->format('d/m/Y') }})
                        @endif
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </section>

</div>
</div>

@endsection

@push('scripts')
<script>
// Scroll reveal animation
document.addEventListener('DOMContentLoaded', function() {
    const reveals = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    reveals.forEach(el => observer.observe(el));
});
</script>
@endpush
