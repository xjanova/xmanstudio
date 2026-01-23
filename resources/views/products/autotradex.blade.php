@extends($publicLayout ?? 'layouts.app')

@section('title', 'AutoTradeX - Crypto Arbitrage Bot | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%239C92AC\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-purple-400 hover:text-purple-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Product Info -->
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-purple-500/20 rounded-full text-purple-300 text-sm mb-6 backdrop-blur-sm border border-purple-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Crypto Arbitrage Trading Bot
                    </div>

                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                        Auto<span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">TradeX</span>
                    </h1>

                    <p class="text-xl text-gray-300 mb-8">
                        บอทเทรด Cryptocurrency แบบ Arbitrage อัตโนมัติ รองรับ 6 Exchange ชั้นนำ ทำกำไรจากความแตกต่างของราคาได้ 24/7
                    </p>

                    <!-- Supported Exchanges -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm border border-yellow-500/30">Binance</span>
                        <span class="px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-sm border border-green-500/30">KuCoin</span>
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-sm border border-blue-500/30">OKX</span>
                        <span class="px-3 py-1 bg-orange-500/20 text-orange-300 rounded-full text-sm border border-orange-500/30">Bybit</span>
                        <span class="px-3 py-1 bg-cyan-500/20 text-cyan-300 rounded-full text-sm border border-cyan-500/30">Gate.io</span>
                        <span class="px-3 py-1 bg-pink-500/20 text-pink-300 rounded-full text-sm border border-pink-500/30">Bitkub</span>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('autotradex.pricing') }}"
                           class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-purple-500/25">
                            ดูแพ็กเกจราคา
                        </a>
                        <a href="#download"
                           class="px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                            ดาวน์โหลด
                        </a>
                    </div>
                </div>

                <!-- Right: Preview Image -->
                <div class="relative">
                    <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-2xl p-8 backdrop-blur-sm border border-purple-500/30">
                        <div class="aspect-video bg-gray-800 rounded-xl flex items-center justify-center overflow-hidden">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="AutoTradeX" class="w-full h-full object-cover">
                            @else
                                <div class="text-center">
                                    <svg class="w-24 h-24 mx-auto text-purple-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <p class="text-gray-400">AutoTradeX Dashboard</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">ฟีเจอร์หลัก</h2>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-purple-500/50 transition-all">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Real-Time Price Monitoring</h3>
                    <p class="text-gray-400">ติดตามราคาแบบ Real-time จาก 6 Exchange พร้อมแจ้งเตือนเมื่อพบโอกาส Arbitrage</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-purple-500/50 transition-all">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Simulation Mode</h3>
                    <p class="text-gray-400">ทดลองเทรดไม่เสียเงินจริง ฝึกฝนกลยุทธ์ก่อนเทรดด้วยเงินจริง</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-purple-500/50 transition-all">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Live Trading</h3>
                    <p class="text-gray-400">เทรดจริงอัตโนมัติ 24/7 ทำกำไรจาก Spread ระหว่าง Exchange</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-purple-500/50 transition-all">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">P&L Tracking</h3>
                    <p class="text-gray-400">ติดตามกำไร/ขาดทุนพร้อม Charts แสดงผลประสิทธิภาพ</p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-purple-500/50 transition-all">
                    <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Risk Management</h3>
                    <p class="text-gray-400">ตั้งค่าพารามิเตอร์ความเสี่ยง Position Size, Daily Loss Limit</p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-purple-500/50 transition-all">
                    <div class="w-12 h-12 bg-pink-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Glass Morphism UI</h3>
                    <p class="text-gray-400">Interface สวยงาม Dark Theme พร้อม Animations ลื่นไหล</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">หลักการทำงาน</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">Arbitrage คือการทำกำไรจากความแตกต่างของราคาสินทรัพย์เดียวกันในตลาดต่างๆ</p>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-purple-500">
                        <span class="text-2xl font-bold text-purple-400">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Monitor Prices</h3>
                    <p class="text-gray-400">ระบบติดตามราคา Crypto จากทุก Exchange แบบ Real-time</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-purple-500">
                        <span class="text-2xl font-bold text-purple-400">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Detect Opportunities</h3>
                    <p class="text-gray-400">เมื่อพบ Spread ที่มากพอ ระบบจะแจ้งเตือนหรือเทรดอัตโนมัติ</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-purple-500">
                        <span class="text-2xl font-bold text-purple-400">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Execute Trades</h3>
                    <p class="text-gray-400">ซื้อที่ราคาต่ำ ขายที่ราคาสูง ทำกำไรจาก Spread</p>
                </div>
            </div>
        </div>
    </section>

    <!-- License Registration -->
    <section class="py-16 bg-gray-900/50" id="license">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">การลงทะเบียน License</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">ขั้นตอนการเปิดใช้งาน AutoTradeX</p>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Steps -->
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">1</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">ซื้อ License</h3>
                            <p class="text-gray-400">เลือกแพ็กเกจที่เหมาะสมและทำการชำระเงิน</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">2</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">รับ License Key</h3>
                            <p class="text-gray-400">License Key จะถูกส่งไปยังอีเมลที่ลงทะเบียน</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">3</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">เปิดโปรแกรม AutoTradeX</h3>
                            <p class="text-gray-400">ดาวน์โหลดและติดตั้งโปรแกรมบนเครื่องของคุณ</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">4</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">Activate License</h3>
                            <p class="text-gray-400">ใส่ License Key ในโปรแกรมเพื่อเปิดใช้งานเต็มรูปแบบ</p>
                        </div>
                    </div>
                </div>

                <!-- License Info Card -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-xl font-bold text-white mb-4">ข้อมูล License</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            License ผูกกับเครื่อง (Machine ID)
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            ใช้งานได้ 1 เครื่องต่อ 1 License
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            สามารถย้ายเครื่องได้ (ติดต่อ Support)
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Trial 7 วันฟรี ไม่ต้องใช้ License
                        </li>
                    </ul>

                    <div class="mt-6 pt-6 border-t border-gray-700">
                        <a href="{{ route('autotradex.pricing') }}"
                           class="block w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-center font-bold rounded-xl transition-all">
                            ซื้อ License
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Requirements -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">ความต้องการระบบ</h2>

            <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        ขั้นต่ำ
                    </h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Windows 10 (64-bit)</li>
                        <li>.NET 8.0 Runtime</li>
                        <li>RAM 4GB</li>
                        <li>พื้นที่ว่าง 500MB</li>
                        <li>Internet Connection</li>
                    </ul>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-purple-500/50">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        แนะนำ
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li>Windows 11 (64-bit)</li>
                        <li>.NET 8.0 Runtime</li>
                        <li>RAM 8GB+</li>
                        <li>SSD Storage</li>
                        <li>Stable High-Speed Internet</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Download Section -->
    <section class="py-16 bg-gray-900/50" id="download">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">ดาวน์โหลด AutoTradeX</h2>
            <p class="text-gray-400 mb-8 max-w-2xl mx-auto">ดาวน์โหลดโปรแกรมเวอร์ชันล่าสุดและเริ่มต้นใช้งานได้ทันที</p>

            <div class="inline-flex flex-col sm:flex-row gap-4">
                <a href="https://github.com/xjanova/autotradex/releases/latest" target="_blank"
                   class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-purple-500/25">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.341-3.369-1.341-.454-1.155-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z"/>
                    </svg>
                    Download from GitHub
                </a>

                <a href="https://github.com/xjanova/autotradex" target="_blank"
                   class="inline-flex items-center px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    View Source Code
                </a>
            </div>

            <p class="text-gray-500 text-sm mt-6">
                เวอร์ชันล่าสุด: v1.0.0 | ขนาด: ~50MB | รองรับ Windows 10/11
            </p>
        </div>
    </section>

    <!-- Risk Warning -->
    <section class="py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-yellow-900/30 border border-yellow-500/50 rounded-2xl p-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-yellow-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h3 class="text-yellow-500 font-bold mb-2">คำเตือนความเสี่ยง</h3>
                        <p class="text-gray-300 text-sm">
                            การเทรด Cryptocurrency มีความเสี่ยงสูง ราคาสามารถผันผวนได้มาก และคุณอาจสูญเสียเงินทุนทั้งหมด
                            AutoTradeX เป็นเพียงเครื่องมือช่วยเทรด ไม่ใช่คำแนะนำการลงทุน โปรดศึกษาและทำความเข้าใจก่อนตัดสินใจลงทุน
                            ผลการดำเนินงานในอดีตไม่ได้รับประกันผลลัพธ์ในอนาคต
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">พร้อมเริ่มต้นใช้งาน?</h2>
            <p class="text-gray-400 mb-8">เริ่มต้นด้วย Trial 7 วันฟรี หรือเลือกแพ็กเกจที่เหมาะกับคุณ</p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('autotradex.pricing') }}"
                   class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-purple-500/25">
                    ดูแพ็กเกจราคา
                </a>
                <a href="{{ route('support.index') }}"
                   class="px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                    ติดต่อสอบถาม
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
