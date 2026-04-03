@extends('layouts.app')

@section('title', 'AI AutoTrade - ระบบเทรดอัตโนมัติด้วย AI | XMAN STUDIO')
@section('meta_description', 'บริการพัฒนาระบบ AI AutoTrade สำหรับเทรดทอง น้ำมัน หุ้น อัตโนมัติ วิเคราะห์ตลาดด้วย AI ขั้นสูง ผลตอบแทนสูง ความเสี่ยงต่ำ พัฒนาโดยทีมผู้เชี่ยวชาญ XMAN STUDIO')

@section('content')
<style>
    @keyframes at-pulse-slow { 0%,100% { opacity: 0.3; transform: scale(1); } 50% { opacity: 0.6; transform: scale(1.1); } }
    @keyframes at-fade-in { from { opacity: 0; } to { opacity: 1; } }
    @keyframes at-fade-in-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes at-shimmer { 0% { background-position: 200% center; } 100% { background-position: -200% center; } }
    .at-pulse-slow { animation: at-pulse-slow 4s ease-in-out infinite; }
    .at-fade-in { animation: at-fade-in 0.8s ease-out both; }
    .at-fade-in-up { animation: at-fade-in-up 0.8s ease-out both; }
    .at-shimmer { background-size: 200% auto; animation: at-shimmer 6s linear infinite; }
    .at-glass { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(4px); }
    .at-glass-hover:hover { background: rgba(255,255,255,0.08); border-color: rgba(16,185,129,0.3); }
    .at-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 1.5rem; }
    .at-card:hover { border-color: rgba(16,185,129,0.2); }
</style>

<div class="text-white" style="background-color: #030712;">

    {{-- Hero Section --}}
    <section class="relative flex items-center justify-center pt-20 pb-16" style="min-height: 100vh;">
        {{-- Animated Background --}}
        <div class="absolute inset-0">
            <div class="absolute inset-0" style="background: linear-gradient(to bottom right, #030712, rgba(6,78,59,0.3), #030712);"></div>
            {{-- Dot pattern --}}
            <div class="absolute inset-0" style="opacity: 0.04; background-image: radial-gradient(circle, #10b981 1px, transparent 1px); background-size: 40px 40px;"></div>
            {{-- Animated gradient orbs --}}
            <div class="absolute at-pulse-slow" style="top: 25%; left: 25%; width: 24rem; height: 24rem; background: rgba(16,185,129,0.12); border-radius: 9999px; filter: blur(128px);"></div>
            <div class="absolute at-pulse-slow" style="bottom: 25%; right: 25%; width: 24rem; height: 24rem; background: rgba(245,158,11,0.15); border-radius: 9999px; filter: blur(128px); animation-delay: 1.5s;"></div>
            <div class="absolute at-pulse-slow" style="top: 50%; left: 50%; transform: translate(-50%,-50%); width: 600px; height: 600px; background: rgba(59,130,246,0.08); border-radius: 9999px; filter: blur(128px); animation-delay: 3s;"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-8 at-fade-in" style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2);">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background-color: #34d399;"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5" style="background-color: #10b981;"></span>
                    </span>
                    <span class="text-sm font-medium" style="color: #34d399;">AI-Powered Trading System</span>
                </div>

                {{-- Main Title --}}
                <h1 class="text-5xl sm:text-6xl lg:text-8xl font-black tracking-tight mb-6 at-fade-in-up">
                    <span class="at-shimmer" style="background-image: linear-gradient(to right, #34d399, #fbbf24, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        AI AutoTrade
                    </span>
                </h1>

                <p class="text-xl sm:text-2xl lg:text-3xl font-light mb-4 at-fade-in-up" style="color: #d1d5db; animation-delay: 0.1s;">
                    ระบบเทรดอัตโนมัติอัจฉริยะ
                </p>

                <p class="max-w-3xl mx-auto text-lg mb-12 at-fade-in-up" style="color: #9ca3af; animation-delay: 0.2s;">
                    พัฒนาโปรแกรมเทรดอัตโนมัติด้วย AI วิเคราะห์ตลาด<strong class="text-white">ทองคำ น้ำมัน หุ้น</strong>
                    แบบ Real-time ด้วยเทคโนโลยี Machine Learning ขั้นสูง
                    <br class="hidden sm:block">ที่ล้ำกว่าระบบ AutoTrade ทั่วไปในตลาด
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center at-fade-in-up" style="animation-delay: 0.3s;">
                    <a href="/support" class="group inline-flex items-center gap-3 px-8 py-4 text-white font-bold text-lg rounded-2xl hover:-translate-y-1 transition-all duration-300" style="background: linear-gradient(to right, #10b981, #059669); box-shadow: 0 10px 25px rgba(16,185,129,0.3);">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        ปรึกษาฟรี
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="#features" class="inline-flex items-center gap-3 px-8 py-4 text-white font-bold text-lg rounded-2xl at-glass at-glass-hover transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        ดูรายละเอียด
                    </a>
                </div>

                {{-- Floating Stats --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-16 at-fade-in-up" style="animation-delay: 0.5s;">
                    <div class="px-4 py-6 rounded-2xl at-glass">
                        <div class="text-3xl font-black mb-1" style="color: #34d399;">AI</div>
                        <div class="text-sm" style="color: #9ca3af;">Machine Learning</div>
                    </div>
                    <div class="px-4 py-6 rounded-2xl at-glass">
                        <div class="text-3xl font-black mb-1" style="color: #fbbf24;">24/7</div>
                        <div class="text-sm" style="color: #9ca3af;">เทรดตลอดเวลา</div>
                    </div>
                    <div class="px-4 py-6 rounded-2xl at-glass">
                        <div class="text-3xl font-black mb-1" style="color: #60a5fa;">Multi</div>
                        <div class="text-sm" style="color: #9ca3af;">หลายตลาดพร้อมกัน</div>
                    </div>
                    <div class="px-4 py-6 rounded-2xl at-glass">
                        <div class="text-3xl font-black mb-1" style="color: #c084fc;">Real-time</div>
                        <div class="text-sm" style="color: #9ca3af;">วิเคราะห์แบบเรียลไทม์</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scroll indicator --}}
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6" style="color: rgba(52,211,153,0.6);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        </div>
    </section>

    {{-- Markets Section --}}
    <section class="relative py-24">
        <div class="absolute inset-0" style="background: linear-gradient(to bottom, #111827, #1f2937, #111827);"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-sm font-bold uppercase tracking-widest mb-4" style="color: #34d399;">Supported Markets</h2>
                <p class="text-3xl sm:text-4xl lg:text-5xl font-bold">
                    ตลาดที่รองรับ
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Gold --}}
                <div class="group relative p-8 transition-all duration-500 hover:-translate-y-2" style="background: linear-gradient(to bottom right, rgba(245,158,11,0.08), transparent); border-radius: 1.5rem; border: 1px solid rgba(245,158,11,0.15);">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-6" style="background: linear-gradient(to bottom right, #f59e0b, #ca8a04); box-shadow: 0 10px 25px rgba(245,158,11,0.25);">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-3" style="color: #fbbf24;">Gold (XAU/USD)</h3>
                    <p class="mb-4" style="color: #9ca3af;">ทองคำ — สินทรัพย์ปลอดภัย วิเคราะห์แนวโน้มราคาทองด้วย AI ที่เรียนรู้จากข้อมูลย้อนหลังกว่า 20 ปี รองรับทั้ง Spot Gold และ Gold Futures</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium rounded-full" style="background: rgba(245,158,11,0.1); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2);">Spot Trading</span>
                        <span class="px-3 py-1 text-xs font-medium rounded-full" style="background: rgba(245,158,11,0.1); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2);">Futures</span>
                        <span class="px-3 py-1 text-xs font-medium rounded-full" style="background: rgba(245,158,11,0.1); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2);">Scalping</span>
                    </div>
                </div>

                {{-- Oil --}}
                <div class="group relative p-8 transition-all duration-500 hover:-translate-y-2" style="background: linear-gradient(to bottom right, rgba(107,114,128,0.08), transparent); border-radius: 1.5rem; border: 1px solid rgba(107,114,128,0.15);">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-6" style="background: linear-gradient(to bottom right, #4b5563, #1f2937); box-shadow: 0 10px 25px rgba(107,114,128,0.2);">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z"/></svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-3" style="color: #d1d5db;">Crude Oil (WTI/Brent)</h3>
                    <p class="mb-4" style="color: #9ca3af;">น้ำมันดิบ — ตลาดพลังงาน AI วิเคราะห์ปัจจัยพื้นฐาน สต็อกน้ำมัน OPEC geopolitics และ seasonal patterns เพื่อทำนายแนวโน้มอย่างแม่นยำ</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium rounded-full" style="background: rgba(107,114,128,0.1); color: #9ca3af; border: 1px solid rgba(107,114,128,0.2);">WTI Crude</span>
                        <span class="px-3 py-1 text-xs font-medium rounded-full" style="background: rgba(107,114,128,0.1); color: #9ca3af; border: 1px solid rgba(107,114,128,0.2);">Brent Crude</span>
                        <span class="px-3 py-1 text-xs font-medium rounded-full" style="background: rgba(107,114,128,0.1); color: #9ca3af; border: 1px solid rgba(107,114,128,0.2);">Swing Trade</span>
                    </div>
                </div>

                {{-- Stocks --}}
                <div class="group relative p-8 transition-all duration-500 hover:-translate-y-2" style="background: linear-gradient(to bottom right, rgba(59,130,246,0.08), transparent); border-radius: 1.5rem; border: 1px solid rgba(59,130,246,0.15);">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-6" style="background: linear-gradient(to bottom right, #3b82f6, #4f46e5); box-shadow: 0 10px 25px rgba(59,130,246,0.25);">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-3" style="color: #60a5fa;">Stock Market</h3>
                    <p class="mb-4" style="color: #9ca3af;">ตลาดหุ้น — วิเคราะห์หุ้นทั้ง SET, NASDAQ, NYSE ด้วย AI ที่รวม Technical Analysis, Sentiment Analysis จาก News, Social Media เพื่อจับจังหวะเข้าออก</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium rounded-full" style="background: rgba(59,130,246,0.1); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2);">SET Index</span>
                        <span class="px-3 py-1 text-xs font-medium rounded-full" style="background: rgba(59,130,246,0.1); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2);">US Stocks</span>
                        <span class="px-3 py-1 text-xs font-medium rounded-full" style="background: rgba(59,130,246,0.1); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2);">Forex</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Why Our AI is Different --}}
    <section id="features" class="relative py-24">
        <div class="absolute inset-0" style="background: linear-gradient(to bottom, #111827, rgba(6,78,59,0.15), #111827);"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-sm font-bold uppercase tracking-widest mb-4" style="color: #34d399;">Why Choose Us</h2>
                <p class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">
                    ทำไมต้อง <span style="color: #34d399;">AI ของเรา</span>
                </p>
                <p class="text-lg max-w-2xl mx-auto" style="color: #9ca3af;">
                    ระบบ AutoTrade ในตลาดมีเยอะ แต่เราพัฒนาด้วย AI จริงๆ ไม่ใช่แค่ Indicator ธรรมดา
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @php
                $features = [
                    ['icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z',
                     'color' => '#34d399', 'bg' => 'rgba(16,185,129,0.15)', 'border' => 'rgba(16,185,129,0.2)',
                     'title' => 'Deep Learning Analysis', 'desc' => 'ใช้ Neural Network หลายชั้นในการวิเคราะห์ Pattern ของตลาด ไม่ใช่แค่ Moving Average หรือ RSI ธรรมดา แต่เรียนรู้จากข้อมูลนับล้าน Data Points'],
                    ['icon' => 'M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z',
                     'color' => '#fbbf24', 'bg' => 'rgba(245,158,11,0.15)', 'border' => 'rgba(245,158,11,0.2)',
                     'title' => 'Risk Management อัจฉริยะ', 'desc' => 'AI จัดการความเสี่ยงอัตโนมัติ ตั้ง Stop Loss / Take Profit แบบ Dynamic ปรับตามสภาวะตลาด ไม่ใช่ค่าคงที่ ลดความเสี่ยงจากเหตุการณ์ไม่คาดฝัน'],
                    ['icon' => 'M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z',
                     'color' => '#60a5fa', 'bg' => 'rgba(59,130,246,0.15)', 'border' => 'rgba(59,130,246,0.2)',
                     'title' => 'Sentiment Analysis', 'desc' => 'วิเคราะห์อารมณ์ตลาดจากข่าว Social Media และข้อมูลเศรษฐกิจ Real-time เพื่อจับจังหวะ Fear/Greed ของตลาดได้ก่อนคนอื่น'],
                    ['icon' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
                     'color' => '#c084fc', 'bg' => 'rgba(168,85,247,0.15)', 'border' => 'rgba(168,85,247,0.2)',
                     'title' => 'Adaptive Strategy', 'desc' => 'ระบบปรับกลยุทธ์อัตโนมัติตามสภาวะตลาด — Trending, Ranging, High Volatility — ไม่ยึดติดกลยุทธ์เดียว แต่ปรับตัวตามสถานการณ์จริง'],
                    ['icon' => 'M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605',
                     'color' => '#fb7185', 'bg' => 'rgba(244,63,94,0.15)', 'border' => 'rgba(244,63,94,0.2)',
                     'title' => 'Backtesting / Optimization', 'desc' => 'ทดสอบระบบกับข้อมูลย้อนหลังหลายปี ปรับแต่ง Parameters ด้วย Genetic Algorithm เพื่อหา Setting ที่ดีที่สุดก่อนเทรดจริง'],
                    ['icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
                     'color' => '#22d3ee', 'bg' => 'rgba(6,182,212,0.15)', 'border' => 'rgba(6,182,212,0.2)',
                     'title' => 'Real-time Alert / Dashboard', 'desc' => 'แจ้งเตือนทุกการเทรดผ่าน LINE / Telegram / Email พร้อม Dashboard แสดง Performance, P/L, Drawdown แบบ Real-time'],
                ];
                @endphp

                @foreach($features as $f)
                <div class="group relative p-8 at-card transition-all duration-500">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-14 h-14 rounded-2xl flex items-center justify-center" style="background: {{ $f['bg'] }}; border: 1px solid {{ $f['border'] }};">
                            <svg class="w-7 h-7" style="color: {{ $f['color'] }};" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-2">{{ $f['title'] }}</h3>
                            <p style="color: #9ca3af;">{{ $f['desc'] }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="relative py-24">
        <div class="absolute inset-0" style="background: linear-gradient(to bottom, #111827, #1f2937, #111827);"></div>
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-sm font-bold uppercase tracking-widest mb-4" style="color: #34d399;">How It Works</h2>
                <p class="text-3xl sm:text-4xl lg:text-5xl font-bold">
                    ขั้นตอนการทำงาน
                </p>
            </div>

            @php
            $steps = [
                ['num' => '1', 'gradient' => 'linear-gradient(to bottom right, #10b981, #059669)', 'shadow' => 'rgba(16,185,129,0.3)',
                 'title' => 'ปรึกษาและวิเคราะห์ความต้องการ', 'desc' => 'พูดคุยเพื่อเข้าใจ Style การเทรด ตลาดที่สนใจ ระดับความเสี่ยงที่ยอมรับได้ และเป้าหมายผลตอบแทน'],
                ['num' => '2', 'gradient' => 'linear-gradient(to bottom right, #f59e0b, #d97706)', 'shadow' => 'rgba(245,158,11,0.3)',
                 'title' => 'พัฒนา AI Model เฉพาะทาง', 'desc' => 'สร้างและ Train โมเดล AI เฉพาะสำหรับตลาดที่ต้องการ ทดสอบด้วยข้อมูลย้อนหลัง ปรับแต่งจนได้ Performance ที่น่าพอใจ'],
                ['num' => '3', 'gradient' => 'linear-gradient(to bottom right, #3b82f6, #2563eb)', 'shadow' => 'rgba(59,130,246,0.3)',
                 'title' => 'ทดสอบ Demo และปรับแต่ง', 'desc' => 'รันระบบบน Demo Account เพื่อทดสอบ Performance จริงในตลาด Live ปรับแต่ง Parameter จนลูกค้าพอใจ'],
                ['num' => '4', 'gradient' => 'linear-gradient(to bottom right, #a855f7, #9333ea)', 'shadow' => 'rgba(168,85,247,0.3)',
                 'title' => 'Go Live และดูแลต่อเนื่อง', 'desc' => 'ติดตั้งระบบบน Account จริง พร้อมการดูแลและอัปเดต AI Model ต่อเนื่อง Monitor Performance 24/7'],
            ];
            @endphp

            <div class="space-y-8">
                @foreach($steps as $step)
                <div class="flex gap-6 items-start">
                    <div class="flex-shrink-0 w-14 h-14 rounded-2xl flex items-center justify-center text-2xl font-black text-white" style="background: {{ $step['gradient'] }}; box-shadow: 0 10px 25px {{ $step['shadow'] }};">{{ $step['num'] }}</div>
                    <div class="flex-1 p-6 rounded-2xl" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06);">
                        <h3 class="text-lg font-bold mb-2">{{ $step['title'] }}</h3>
                        <p style="color: #9ca3af;">{{ $step['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Comparison Section --}}
    <section class="relative py-24">
        <div class="absolute inset-0" style="background: linear-gradient(to bottom, #111827, rgba(6,78,59,0.15), #111827);"></div>
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-sm font-bold uppercase tracking-widest mb-4" style="color: #34d399;">Comparison</h2>
                <p class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">
                    เทียบกับระบบ AutoTrade ทั่วไป
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <th class="text-left py-4 px-4 font-medium" style="color: #9ca3af;">คุณสมบัติ</th>
                            <th class="text-center py-4 px-4 font-medium" style="color: #9ca3af;">ระบบทั่วไป</th>
                            <th class="text-center py-4 px-4">
                                <span class="font-bold" style="color: #34d399;">XMAN AI</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="text-sm sm:text-base">
                        @php
                        $rows = [
                            ['คุณสมบัติ' => 'เทคนิคการวิเคราะห์', 'normal' => 'Indicator พื้นฐาน (MA, RSI)', 'xman' => 'Deep Learning + Multi-factor'],
                            ['คุณสมบัติ' => 'การปรับตัว', 'normal' => 'ค่าคงที่ ต้องปรับเอง', 'xman' => 'ปรับอัตโนมัติตามสภาวะตลาด'],
                            ['คุณสมบัติ' => 'การจัดการความเสี่ยง', 'normal' => 'SL/TP คงที่', 'xman' => 'Dynamic SL/TP + Position Sizing'],
                            ['คุณสมบัติ' => 'การวิเคราะห์ข่าว', 'normal' => 'ไม่มี', 'xman' => 'Sentiment Analysis Real-time'],
                            ['คุณสมบัติ' => 'หลายตลาดพร้อมกัน', 'normal' => 'ตลาดเดียว', 'xman' => 'Multi-market Portfolio'],
                            ['คุณสมบัติ' => 'การดูแลหลังขาย', 'normal' => 'ไม่มี / จำกัด', 'xman' => 'Monitor 24/7 + อัปเดต Model'],
                        ];
                        @endphp
                        @foreach($rows as $i => $row)
                        <tr style="{{ $i < count($rows) - 1 ? 'border-bottom: 1px solid rgba(255,255,255,0.05);' : '' }}">
                            <td class="py-4 px-4" style="color: #d1d5db;">{{ $row['คุณสมบัติ'] }}</td>
                            <td class="py-4 px-4 text-center" style="color: #6b7280;">{{ $row['normal'] }}</td>
                            <td class="py-4 px-4 text-center font-medium" style="color: #34d399;">{{ $row['xman'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Disclaimer + CTA --}}
    <section class="relative py-24">
        <div class="absolute inset-0" style="background: linear-gradient(to bottom, #111827, #1f2937);"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            {{-- CTA Card --}}
            <div class="relative p-10 sm:p-14 overflow-hidden" style="border-radius: 1.5rem; background: linear-gradient(to bottom right, rgba(16,185,129,0.1), rgba(16,185,129,0.03), transparent); border: 1px solid rgba(16,185,129,0.2);">
                <div class="absolute at-pulse-slow" style="top: 0; right: 0; width: 16rem; height: 16rem; background: rgba(16,185,129,0.08); border-radius: 9999px; filter: blur(80px);"></div>
                <div class="absolute at-pulse-slow" style="bottom: 0; left: 0; width: 16rem; height: 16rem; background: rgba(245,158,11,0.08); border-radius: 9999px; filter: blur(80px); animation-delay: 2s;"></div>

                <div class="relative">
                    <h2 class="text-3xl sm:text-4xl font-bold mb-4">
                        พร้อมให้ <span style="color: #34d399;">AI</span> ทำงานแทนคุณ?
                    </h2>
                    <p class="text-lg mb-8 max-w-2xl mx-auto" style="color: #9ca3af;">
                        ติดต่อเราเพื่อปรึกษาฟรี ทีมผู้เชี่ยวชาญพร้อมออกแบบระบบ AI AutoTrade ที่เหมาะกับคุณโดยเฉพาะ
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="/support" class="group inline-flex items-center justify-center gap-3 px-8 py-4 text-white font-bold text-lg rounded-2xl hover:-translate-y-1 transition-all duration-300" style="background: linear-gradient(to right, #10b981, #059669); box-shadow: 0 10px 25px rgba(16,185,129,0.3);">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            ปรึกษาฟรี
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                        <a href="https://lin.ee/xmanstudio" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-3 px-8 py-4 text-white font-bold text-lg rounded-2xl hover:-translate-y-1 transition-all duration-300" style="background-color: #06C755; box-shadow: 0 10px 25px rgba(6,199,85,0.3);">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                            LINE สอบถาม
                        </a>
                    </div>

                    {{-- Disclaimer --}}
                    <div class="mt-10 p-4 rounded-xl" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06);">
                        <p class="text-xs leading-relaxed" style="color: #6b7280;">
                            <strong style="color: #9ca3af;">คำเตือน:</strong> การลงทุนมีความเสี่ยง ผลตอบแทนในอดีตไม่ได้เป็นเครื่องรับประกันผลตอบแทนในอนาคต
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
