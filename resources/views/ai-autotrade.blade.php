@extends('layouts.app')

@section('title', 'AI AutoTrade - ระบบเทรดอัตโนมัติด้วย AI | XMAN STUDIO')
@section('meta_description', 'บริการพัฒนาระบบ AI AutoTrade สำหรับเทรดทอง น้ำมัน หุ้น อัตโนมัติ วิเคราะห์ตลาดด้วย AI ขั้นสูง ผลตอบแทนสูง ความเสี่ยงต่ำ พัฒนาโดยทีมผู้เชี่ยวชาญ XMAN STUDIO')

@section('content')
<div class="min-h-screen bg-gray-950 text-white overflow-hidden">

    {{-- Hero Section --}}
    <section class="relative min-h-screen flex items-center justify-center pt-20 pb-16">
        {{-- Animated Background --}}
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-br from-gray-950 via-emerald-950/30 to-gray-950"></div>
            {{-- Grid pattern --}}
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%2310b981&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            {{-- Animated gradient orbs --}}
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-emerald-500/10 rounded-full blur-[128px] animate-pulse-slow"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-amber-500/10 rounded-full blur-[128px] animate-pulse-slow" style="animation-delay: 1.5s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-500/5 rounded-full blur-[128px] animate-pulse-slow" style="animation-delay: 3s;"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 mb-8 animate-fade-in">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-emerald-400 text-sm font-medium">AI-Powered Trading System</span>
                </div>

                {{-- Main Title --}}
                <h1 class="text-5xl sm:text-6xl lg:text-8xl font-black tracking-tight mb-6 animate-fade-in-up">
                    <span class="bg-gradient-to-r from-emerald-400 via-amber-400 to-emerald-400 bg-clip-text text-transparent bg-[length:200%_auto] animate-shimmer">
                        AI AutoTrade
                    </span>
                </h1>

                <p class="text-xl sm:text-2xl lg:text-3xl font-light text-gray-300 mb-4 animate-fade-in-up" style="animation-delay: 0.1s;">
                    ระบบเทรดอัตโนมัติอัจฉริยะ
                </p>

                <p class="max-w-3xl mx-auto text-lg text-gray-400 mb-12 animate-fade-in-up" style="animation-delay: 0.2s;">
                    พัฒนาโปรแกรมเทรดอัตโนมัติด้วย AI วิเคราะห์ตลาด<strong class="text-white">ทองคำ น้ำมัน หุ้น</strong>
                    แบบ Real-time ด้วยเทคโนโลยี Machine Learning ขั้นสูง
                    <br class="hidden sm:block">ที่ล้ำกว่าระบบ AutoTrade ทั่วไปในตลาด
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center animate-fade-in-up" style="animation-delay: 0.3s;">
                    <a href="/support" class="group inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-bold text-lg rounded-2xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:-translate-y-1 transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        ปรึกษาฟรี
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="#features" class="inline-flex items-center gap-3 px-8 py-4 bg-white/5 border border-white/10 text-white font-bold text-lg rounded-2xl hover:bg-white/10 hover:border-emerald-500/30 transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        ดูรายละเอียด
                    </a>
                </div>

                {{-- Floating Stats --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-16 animate-fade-in-up" style="animation-delay: 0.5s;">
                    <div class="px-4 py-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                        <div class="text-3xl font-black text-emerald-400 mb-1">AI</div>
                        <div class="text-sm text-gray-400">Machine Learning</div>
                    </div>
                    <div class="px-4 py-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                        <div class="text-3xl font-black text-amber-400 mb-1">24/7</div>
                        <div class="text-sm text-gray-400">เทรดตลอดเวลา</div>
                    </div>
                    <div class="px-4 py-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                        <div class="text-3xl font-black text-blue-400 mb-1">Multi</div>
                        <div class="text-sm text-gray-400">หลายตลาดพร้อมกัน</div>
                    </div>
                    <div class="px-4 py-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                        <div class="text-3xl font-black text-purple-400 mb-1">Real-time</div>
                        <div class="text-sm text-gray-400">วิเคราะห์แบบเรียลไทม์</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scroll indicator --}}
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-emerald-400/60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        </div>
    </section>

    {{-- Markets Section --}}
    <section class="relative py-24">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-950 via-gray-900 to-gray-950"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-sm font-bold text-emerald-400 uppercase tracking-widest mb-4">Supported Markets</h2>
                <p class="text-3xl sm:text-4xl lg:text-5xl font-bold">
                    ตลาดที่รองรับ
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Gold --}}
                <div class="group relative p-8 bg-gradient-to-br from-amber-500/5 to-amber-500/0 rounded-3xl border border-amber-500/10 hover:border-amber-500/30 transition-all duration-500 hover:-translate-y-2">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-500 to-yellow-600 flex items-center justify-center mb-6 shadow-lg shadow-amber-500/20 group-hover:shadow-amber-500/40 transition-shadow">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-amber-400 mb-3">Gold (XAU/USD)</h3>
                    <p class="text-gray-400 mb-4">ทองคำ — สินทรัพย์ปลอดภัย วิเคราะห์แนวโน้มราคาทองด้วย AI ที่เรียนรู้จากข้อมูลย้อนหลังกว่า 20 ปี รองรับทั้ง Spot Gold และ Gold Futures</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium bg-amber-500/10 text-amber-400 rounded-full border border-amber-500/20">Spot Trading</span>
                        <span class="px-3 py-1 text-xs font-medium bg-amber-500/10 text-amber-400 rounded-full border border-amber-500/20">Futures</span>
                        <span class="px-3 py-1 text-xs font-medium bg-amber-500/10 text-amber-400 rounded-full border border-amber-500/20">Scalping</span>
                    </div>
                </div>

                {{-- Oil --}}
                <div class="group relative p-8 bg-gradient-to-br from-gray-500/5 to-gray-500/0 rounded-3xl border border-gray-500/10 hover:border-gray-400/30 transition-all duration-500 hover:-translate-y-2">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-gray-600 to-gray-800 flex items-center justify-center mb-6 shadow-lg shadow-gray-500/20 group-hover:shadow-gray-400/40 transition-shadow">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z"/></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-300 mb-3">Crude Oil (WTI/Brent)</h3>
                    <p class="text-gray-400 mb-4">น้ำมันดิบ — ตลาดพลังงาน AI วิเคราะห์ปัจจัยพื้นฐาน สต็อกน้ำมัน OPEC geopolitics และ seasonal patterns เพื่อทำนายแนวโน้มอย่างแม่นยำ</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium bg-gray-500/10 text-gray-400 rounded-full border border-gray-500/20">WTI Crude</span>
                        <span class="px-3 py-1 text-xs font-medium bg-gray-500/10 text-gray-400 rounded-full border border-gray-500/20">Brent Crude</span>
                        <span class="px-3 py-1 text-xs font-medium bg-gray-500/10 text-gray-400 rounded-full border border-gray-500/20">Swing Trade</span>
                    </div>
                </div>

                {{-- Stocks --}}
                <div class="group relative p-8 bg-gradient-to-br from-blue-500/5 to-blue-500/0 rounded-3xl border border-blue-500/10 hover:border-blue-500/30 transition-all duration-500 hover:-translate-y-2">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mb-6 shadow-lg shadow-blue-500/20 group-hover:shadow-blue-500/40 transition-shadow">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-blue-400 mb-3">Stock Market</h3>
                    <p class="text-gray-400 mb-4">ตลาดหุ้น — วิเคราะห์หุ้นทั้ง SET, NASDAQ, NYSE ด้วย AI ที่รวม Technical Analysis, Sentiment Analysis จาก News, Social Media เพื่อจับจังหวะเข้าออก</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium bg-blue-500/10 text-blue-400 rounded-full border border-blue-500/20">SET Index</span>
                        <span class="px-3 py-1 text-xs font-medium bg-blue-500/10 text-blue-400 rounded-full border border-blue-500/20">US Stocks</span>
                        <span class="px-3 py-1 text-xs font-medium bg-blue-500/10 text-blue-400 rounded-full border border-blue-500/20">Forex</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Why Our AI is Different --}}
    <section id="features" class="relative py-24">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-950 via-emerald-950/10 to-gray-950"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-sm font-bold text-emerald-400 uppercase tracking-widest mb-4">Why Choose Us</h2>
                <p class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">
                    ทำไมต้อง <span class="text-emerald-400">AI ของเรา</span>
                </p>
                <p class="text-lg text-gray-400 max-w-2xl mx-auto">
                    ระบบ AutoTrade ในตลาดมีเยอะ แต่เราพัฒนาด้วย AI จริงๆ ไม่ใช่แค่ Indicator ธรรมดา
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Feature 1 --}}
                <div class="group relative p-8 bg-white/[0.02] rounded-3xl border border-white/5 hover:border-emerald-500/20 transition-all duration-500">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-emerald-500/5 flex items-center justify-center border border-emerald-500/20">
                            <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Deep Learning Analysis</h3>
                            <p class="text-gray-400">ใช้ Neural Network หลายชั้นในการวิเคราะห์ Pattern ของตลาด ไม่ใช่แค่ Moving Average หรือ RSI ธรรมดา แต่เรียนรู้จากข้อมูลนับล้าน Data Points</p>
                        </div>
                    </div>
                </div>

                {{-- Feature 2 --}}
                <div class="group relative p-8 bg-white/[0.02] rounded-3xl border border-white/5 hover:border-emerald-500/20 transition-all duration-500">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500/20 to-amber-500/5 flex items-center justify-center border border-amber-500/20">
                            <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Risk Management อัจฉริยะ</h3>
                            <p class="text-gray-400">AI จัดการความเสี่ยงอัตโนมัติ ตั้ง Stop Loss / Take Profit แบบ Dynamic ปรับตามสภาวะตลาด ไม่ใช่ค่าคงที่ ลดความเสี่ยงจากเหตุการณ์ไม่คาดฝัน</p>
                        </div>
                    </div>
                </div>

                {{-- Feature 3 --}}
                <div class="group relative p-8 bg-white/[0.02] rounded-3xl border border-white/5 hover:border-emerald-500/20 transition-all duration-500">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500/20 to-blue-500/5 flex items-center justify-center border border-blue-500/20">
                            <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Sentiment Analysis</h3>
                            <p class="text-gray-400">วิเคราะห์อารมณ์ตลาดจากข่าว Social Media และข้อมูลเศรษฐกิจ Real-time เพื่อจับจังหวะ Fear/Greed ของตลาดได้ก่อนคนอื่น</p>
                        </div>
                    </div>
                </div>

                {{-- Feature 4 --}}
                <div class="group relative p-8 bg-white/[0.02] rounded-3xl border border-white/5 hover:border-emerald-500/20 transition-all duration-500">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500/20 to-purple-500/5 flex items-center justify-center border border-purple-500/20">
                            <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Adaptive Strategy</h3>
                            <p class="text-gray-400">ระบบปรับกลยุทธ์อัตโนมัติตามสภาวะตลาด — Trending, Ranging, High Volatility — ไม่ยึดติดกลยุทธ์เดียว แต่ปรับตัวตามสถานการณ์จริง</p>
                        </div>
                    </div>
                </div>

                {{-- Feature 5 --}}
                <div class="group relative p-8 bg-white/[0.02] rounded-3xl border border-white/5 hover:border-emerald-500/20 transition-all duration-500">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-500/20 to-rose-500/5 flex items-center justify-center border border-rose-500/20">
                            <svg class="w-7 h-7 text-rose-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Backtesting / Optimization</h3>
                            <p class="text-gray-400">ทดสอบระบบกับข้อมูลย้อนหลังหลายปี ปรับแต่ง Parameters ด้วย Genetic Algorithm เพื่อหา Setting ที่ดีที่สุดก่อนเทรดจริง</p>
                        </div>
                    </div>
                </div>

                {{-- Feature 6 --}}
                <div class="group relative p-8 bg-white/[0.02] rounded-3xl border border-white/5 hover:border-emerald-500/20 transition-all duration-500">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-cyan-500/20 to-cyan-500/5 flex items-center justify-center border border-cyan-500/20">
                            <svg class="w-7 h-7 text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Real-time Alert / Dashboard</h3>
                            <p class="text-gray-400">แจ้งเตือนทุกการเทรดผ่าน LINE / Telegram / Email พร้อม Dashboard แสดง Performance, P/L, Drawdown แบบ Real-time</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="relative py-24">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-950 via-gray-900 to-gray-950"></div>
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-sm font-bold text-emerald-400 uppercase tracking-widest mb-4">How It Works</h2>
                <p class="text-3xl sm:text-4xl lg:text-5xl font-bold">
                    ขั้นตอนการทำงาน
                </p>
            </div>

            <div class="space-y-8">
                {{-- Step 1 --}}
                <div class="flex gap-6 items-start">
                    <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-2xl font-black shadow-lg shadow-emerald-500/30">1</div>
                    <div class="flex-1 p-6 bg-white/[0.02] rounded-2xl border border-white/5">
                        <h3 class="text-lg font-bold mb-2">ปรึกษาและวิเคราะห์ความต้องการ</h3>
                        <p class="text-gray-400">พูดคุยเพื่อเข้าใจ Style การเทรด ตลาดที่สนใจ ระดับความเสี่ยงที่ยอมรับได้ และเป้าหมายผลตอบแทน</p>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="flex gap-6 items-start">
                    <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-2xl font-black shadow-lg shadow-amber-500/30">2</div>
                    <div class="flex-1 p-6 bg-white/[0.02] rounded-2xl border border-white/5">
                        <h3 class="text-lg font-bold mb-2">พัฒนา AI Model เฉพาะทาง</h3>
                        <p class="text-gray-400">สร้างและ Train โมเดล AI เฉพาะสำหรับตลาดที่ต้องการ ทดสอบด้วยข้อมูลย้อนหลัง ปรับแต่งจนได้ Performance ที่น่าพอใจ</p>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="flex gap-6 items-start">
                    <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-2xl font-black shadow-lg shadow-blue-500/30">3</div>
                    <div class="flex-1 p-6 bg-white/[0.02] rounded-2xl border border-white/5">
                        <h3 class="text-lg font-bold mb-2">ทดสอบ Demo และปรับแต่ง</h3>
                        <p class="text-gray-400">รันระบบบน Demo Account เพื่อทดสอบ Performance จริงในตลาด Live ปรับแต่ง Parameter จนลูกค้าพอใจ</p>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div class="flex gap-6 items-start">
                    <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-2xl font-black shadow-lg shadow-purple-500/30">4</div>
                    <div class="flex-1 p-6 bg-white/[0.02] rounded-2xl border border-white/5">
                        <h3 class="text-lg font-bold mb-2">Go Live และดูแลต่อเนื่อง</h3>
                        <p class="text-gray-400">ติดตั้งระบบบน Account จริง พร้อมการดูแลและอัปเดต AI Model ต่อเนื่อง Monitor Performance 24/7</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Comparison Section --}}
    <section class="relative py-24">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-950 via-emerald-950/10 to-gray-950"></div>
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-sm font-bold text-emerald-400 uppercase tracking-widest mb-4">Comparison</h2>
                <p class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">
                    เทียบกับระบบ AutoTrade ทั่วไป
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left py-4 px-4 text-gray-400 font-medium">คุณสมบัติ</th>
                            <th class="text-center py-4 px-4 text-gray-400 font-medium">ระบบทั่วไป</th>
                            <th class="text-center py-4 px-4">
                                <span class="text-emerald-400 font-bold">XMAN AI</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="text-sm sm:text-base">
                        <tr class="border-b border-white/5">
                            <td class="py-4 px-4 text-gray-300">เทคนิคการวิเคราะห์</td>
                            <td class="py-4 px-4 text-center text-gray-500">Indicator พื้นฐาน (MA, RSI)</td>
                            <td class="py-4 px-4 text-center text-emerald-400 font-medium">Deep Learning + Multi-factor</td>
                        </tr>
                        <tr class="border-b border-white/5">
                            <td class="py-4 px-4 text-gray-300">การปรับตัว</td>
                            <td class="py-4 px-4 text-center text-gray-500">ค่าคงที่ ต้องปรับเอง</td>
                            <td class="py-4 px-4 text-center text-emerald-400 font-medium">ปรับอัตโนมัติตามสภาวะตลาด</td>
                        </tr>
                        <tr class="border-b border-white/5">
                            <td class="py-4 px-4 text-gray-300">การจัดการความเสี่ยง</td>
                            <td class="py-4 px-4 text-center text-gray-500">SL/TP คงที่</td>
                            <td class="py-4 px-4 text-center text-emerald-400 font-medium">Dynamic SL/TP + Position Sizing</td>
                        </tr>
                        <tr class="border-b border-white/5">
                            <td class="py-4 px-4 text-gray-300">การวิเคราะห์ข่าว</td>
                            <td class="py-4 px-4 text-center text-gray-500">ไม่มี</td>
                            <td class="py-4 px-4 text-center text-emerald-400 font-medium">Sentiment Analysis Real-time</td>
                        </tr>
                        <tr class="border-b border-white/5">
                            <td class="py-4 px-4 text-gray-300">หลายตลาดพร้อมกัน</td>
                            <td class="py-4 px-4 text-center text-gray-500">ตลาดเดียว</td>
                            <td class="py-4 px-4 text-center text-emerald-400 font-medium">Multi-market Portfolio</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-4 text-gray-300">การดูแลหลังขาย</td>
                            <td class="py-4 px-4 text-center text-gray-500">ไม่มี / จำกัด</td>
                            <td class="py-4 px-4 text-center text-emerald-400 font-medium">Monitor 24/7 + อัปเดต Model</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Disclaimer + CTA --}}
    <section class="relative py-24">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-950 to-gray-900"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            {{-- CTA Card --}}
            <div class="relative p-10 sm:p-14 rounded-3xl bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-transparent border border-emerald-500/20 overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/5 rounded-full blur-[80px]"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-amber-500/5 rounded-full blur-[80px]"></div>

                <div class="relative">
                    <h2 class="text-3xl sm:text-4xl font-bold mb-4">
                        พร้อมให้ <span class="text-emerald-400">AI</span> ทำงานแทนคุณ?
                    </h2>
                    <p class="text-lg text-gray-400 mb-8 max-w-2xl mx-auto">
                        ติดต่อเราเพื่อปรึกษาฟรี ทีมผู้เชี่ยวชาญพร้อมออกแบบระบบ AI AutoTrade ที่เหมาะกับคุณโดยเฉพาะ
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="/support" class="group inline-flex items-center justify-center gap-3 px-8 py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-bold text-lg rounded-2xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:-translate-y-1 transition-all duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            ปรึกษาฟรี
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                        <a href="https://lin.ee/xmanstudio" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-3 px-8 py-4 bg-[#06C755] text-white font-bold text-lg rounded-2xl shadow-lg shadow-[#06C755]/30 hover:shadow-[#06C755]/50 hover:-translate-y-1 transition-all duration-300">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                            LINE สอบถาม
                        </a>
                    </div>

                    {{-- Disclaimer --}}
                    <div class="mt-10 p-4 bg-white/[0.02] rounded-xl border border-white/5">
                        <p class="text-xs text-gray-500 leading-relaxed">
                            <strong class="text-gray-400">คำเตือน:</strong> การลงทุนมีความเสี่ยง ผลตอบแทนในอดีตไม่ได้เป็นเครื่องรับประกันผลตอบแทนในอนาคต
                            ผู้ลงทุนควรศึกษาข้อมูลและพิจารณาความเสี่ยงอย่างรอบคอบก่อนตัดสินใจลงทุน
                            ระบบ AI AutoTrade เป็นเครื่องมือช่วยในการเทรดเท่านั้น ไม่ใช่คำแนะนำการลงทุน
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
