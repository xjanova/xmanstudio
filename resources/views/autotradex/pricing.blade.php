@extends($publicLayout ?? 'layouts.app')

@section('title', 'ซื้อ License - AutoTradeX')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%239C92AC\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center px-4 py-2 bg-purple-500/20 rounded-full text-purple-300 text-sm mb-6 backdrop-blur-sm border border-purple-500/30">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Crypto Arbitrage Trading Bot
            </div>

            <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                Auto<span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">TradeX</span>
            </h1>

            <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                บอทเทรด Cryptocurrency แบบ Arbitrage อัตโนมัติ รองรับ 6 Exchange ชั้นนำ
                <br>ทำกำไรจากความแตกต่างของราคาได้ 24/7
            </p>

            <div class="flex flex-wrap justify-center gap-4 text-gray-400 text-sm">
                <span class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Binance</span>
                <span class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> KuCoin</span>
                <span class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> OKX</span>
                <span class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Bybit</span>
                <span class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Gate.io</span>
                <span class="flex items-center"><svg class="w-4 h-4 mr-1 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Bitkub</span>
            </div>
        </div>
    </section>

    <!-- Early Bird Discount Banner -->
    @if(isset($earlyBird) && $earlyBird['eligible'])
    <section class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="relative overflow-hidden bg-gradient-to-r from-orange-500 via-yellow-500 to-orange-500 rounded-2xl p-6 shadow-lg">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"40\" height=\"40\" viewBox=\"0 0 40 40\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"%23fff\" fill-opacity=\"0.1\"%3E%3Cpath d=\"M20 20l-4-4 4-4 4 4-4 4zm0 8l-4-4 4-4 4 4-4 4z\"/%3E%3C/g%3E%3C/svg%3E')]"></div>

                <div class="relative flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="text-5xl">🔥</div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-2xl font-black text-white">EARLY BIRD DISCOUNT</span>
                                <span class="bg-red-600 text-white text-sm font-bold px-3 py-1 rounded-full animate-pulse">
                                    {{ $earlyBird['discount_percent'] }}% OFF
                                </span>
                            </div>
                            <p class="text-white/90 font-medium">
                                {{ $earlyBird['message'] }}
                            </p>
                        </div>
                    </div>

                    <div class="bg-white/20 backdrop-blur-sm rounded-xl px-6 py-3 text-center">
                        <div class="text-white/70 text-sm">เหลือเวลาอีก</div>
                        <div class="text-3xl font-black text-white">{{ $earlyBird['days_remaining'] }} วัน</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Pricing Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-white mb-4">เลือกแพ็กเกจที่เหมาะกับคุณ</h2>
                <p class="text-gray-400">เริ่มต้นด้วย Trial 30 วันฟรี หรือเลือกแพ็กเกจที่ตรงใจ</p>
                @if(isset($earlyBird) && $earlyBird['eligible'])
                <p class="text-yellow-400 font-semibold mt-2">🎉 รับส่วนลด {{ $earlyBird['discount_percent'] }}% สำหรับทุกแพ็กเกจ!</p>
                @endif
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Trial -->
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700 hover:border-gray-600 transition-all">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">Trial</h3>
                        <p class="text-gray-400 text-sm mb-4">ทดลองใช้งาน</p>
                        <div class="text-4xl font-black text-white">ฟรี</div>
                        <p class="text-gray-500 text-sm mt-1">7 วัน</p>
                    </div>

                    <ul class="space-y-3 mb-6 text-sm">
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Simulation Mode
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Binance เท่านั้น
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Basic Alerts
                        </li>
                        <li class="flex items-center text-gray-500">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Live Trading
                        </li>
                    </ul>

                    <p class="text-center text-gray-500 text-xs">เริ่มทดลองจากโปรแกรม</p>
                </div>

                <!-- Monthly -->
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700 hover:border-purple-500 transition-all">
                    @if(isset($earlyBird) && $earlyBird['eligible'])
                    <div class="absolute -top-3 -right-3">
                        <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full animate-bounce">
                            -{{ $earlyBird['discount_percent'] }}%
                        </span>
                    </div>
                    @endif

                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">Monthly</h3>
                        <p class="text-gray-400 text-sm mb-4">รายเดือน</p>
                        @if(isset($pricing['monthly']) && $pricing['monthly']['discount_amount'] > 0)
                            <div class="text-2xl text-gray-500 line-through">฿{{ number_format($pricing['monthly']['original_price']) }}</div>
                            <div class="text-4xl font-black text-green-400">฿{{ number_format($pricing['monthly']['discounted_price']) }}</div>
                        @else
                            <div class="text-4xl font-black text-white">฿{{ number_format($pricing['monthly']['price'] ?? 990) }}</div>
                        @endif
                        <p class="text-gray-500 text-sm mt-1">ต่อเดือน</p>
                    </div>

                    <ul class="space-y-3 mb-6 text-sm">
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Live Trading
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            3 Exchanges
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            P&L Tracking
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Basic Arbitrage
                        </li>
                    </ul>

                    <a href="{{ route('autotradex.checkout', 'monthly') }}{{ $machineId ? '?machine_id=' . $machineId : '' }}"
                       class="block w-full py-3 px-4 bg-purple-600 hover:bg-purple-700 text-white text-center font-semibold rounded-xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>

                <!-- Yearly - Popular -->
                <div class="relative bg-gradient-to-b from-purple-900/50 to-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border-2 border-purple-500 transform hover:scale-105 transition-all">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 flex gap-2">
                        <span class="bg-gradient-to-r from-purple-500 to-pink-500 text-white text-xs font-bold px-4 py-1 rounded-full">
                            ยอดนิยม
                        </span>
                        @if(isset($earlyBird) && $earlyBird['eligible'])
                        <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full animate-bounce">
                            -{{ $earlyBird['discount_percent'] }}%
                        </span>
                        @endif
                    </div>

                    <div class="text-center mb-6 pt-2">
                        <h3 class="text-xl font-bold text-white mb-2">Yearly</h3>
                        <p class="text-gray-400 text-sm mb-4">รายปี</p>
                        @if(isset($pricing['yearly']) && $pricing['yearly']['discount_amount'] > 0)
                            <div class="text-2xl text-gray-500 line-through">฿{{ number_format($pricing['yearly']['original_price']) }}</div>
                            <div class="text-4xl font-black text-green-400">฿{{ number_format($pricing['yearly']['discounted_price']) }}</div>
                            <p class="text-gray-500 text-sm mt-1">ต่อปี <span class="text-green-400">(ประหยัด ฿{{ number_format($pricing['yearly']['discount_amount']) }})</span></p>
                        @else
                            <div class="text-4xl font-black text-white">฿{{ number_format($pricing['yearly']['price'] ?? 7900) }}</div>
                            <p class="text-gray-500 text-sm mt-1">ต่อปี <span class="text-green-400">(ประหยัด 33%)</span></p>
                        @endif
                    </div>

                    <ul class="space-y-3 mb-6 text-sm">
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Live Trading
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            5 Exchanges
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Advanced Arbitrage
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Auto Rebalance
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Priority Support
                        </li>
                    </ul>

                    <a href="{{ route('autotradex.checkout', 'yearly') }}{{ $machineId ? '?machine_id=' . $machineId : '' }}"
                       class="block w-full py-3 px-4 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white text-center font-semibold rounded-xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>

                <!-- Lifetime -->
                <div class="relative bg-gradient-to-b from-yellow-900/30 to-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-yellow-500/50 hover:border-yellow-500 transition-all">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 flex gap-2">
                        <span class="bg-gradient-to-r from-yellow-500 to-orange-500 text-black text-xs font-bold px-4 py-1 rounded-full">
                            ดีที่สุด
                        </span>
                        @if(isset($earlyBird) && $earlyBird['eligible'])
                        <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full animate-bounce">
                            -{{ $earlyBird['discount_percent'] }}%
                        </span>
                        @endif
                    </div>

                    <div class="text-center mb-6 pt-2">
                        <h3 class="text-xl font-bold text-white mb-2">Lifetime</h3>
                        <p class="text-gray-400 text-sm mb-4">ตลอดชีพ</p>
                        @if(isset($pricing['lifetime']) && $pricing['lifetime']['discount_amount'] > 0)
                            <div class="text-2xl text-gray-500 line-through">฿{{ number_format($pricing['lifetime']['original_price']) }}</div>
                            <div class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-400">฿{{ number_format($pricing['lifetime']['discounted_price']) }}</div>
                            <p class="text-green-400 text-sm mt-1 font-semibold">ประหยัด ฿{{ number_format($pricing['lifetime']['discount_amount']) }}!</p>
                        @else
                            <div class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">฿{{ number_format($pricing['lifetime']['price'] ?? 19900) }}</div>
                        @endif
                        <p class="text-gray-500 text-sm mt-1">จ่ายครั้งเดียว</p>
                    </div>

                    <ul class="space-y-3 mb-6 text-sm">
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            ทุกฟีเจอร์
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            6 Exchanges ทั้งหมด
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Triangular Arbitrage
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            API Access
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Lifetime Updates
                        </li>
                    </ul>

                    <a href="{{ route('autotradex.checkout', 'lifetime') }}{{ $machineId ? '?machine_id=' . $machineId : '' }}"
                       class="block w-full py-3 px-4 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-black text-center font-bold rounded-xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Comparison -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-900/50">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-2xl font-bold text-white text-center mb-8">เปรียบเทียบฟีเจอร์</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="text-left py-4 px-4 text-gray-400 font-medium">ฟีเจอร์</th>
                            <th class="text-center py-4 px-4 text-gray-400 font-medium">Trial</th>
                            <th class="text-center py-4 px-4 text-gray-400 font-medium">Monthly</th>
                            <th class="text-center py-4 px-4 text-purple-400 font-medium">Yearly</th>
                            <th class="text-center py-4 px-4 text-yellow-400 font-medium">Lifetime</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-300">
                        <tr class="border-b border-gray-800">
                            <td class="py-3 px-4">Simulation Mode</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                        </tr>
                        <tr class="border-b border-gray-800">
                            <td class="py-3 px-4">Live Trading</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                        </tr>
                        <tr class="border-b border-gray-800">
                            <td class="py-3 px-4">จำนวน Exchange</td>
                            <td class="py-3 px-4 text-center">1</td>
                            <td class="py-3 px-4 text-center">3</td>
                            <td class="py-3 px-4 text-center">5</td>
                            <td class="py-3 px-4 text-center text-yellow-400">6 (ทั้งหมด)</td>
                        </tr>
                        <tr class="border-b border-gray-800">
                            <td class="py-3 px-4">Basic Arbitrage</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                        </tr>
                        <tr class="border-b border-gray-800">
                            <td class="py-3 px-4">Advanced Arbitrage</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                        </tr>
                        <tr class="border-b border-gray-800">
                            <td class="py-3 px-4">Triangular Arbitrage</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                        </tr>
                        <tr class="border-b border-gray-800">
                            <td class="py-3 px-4">Auto Rebalance</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                        </tr>
                        <tr class="border-b border-gray-800">
                            <td class="py-3 px-4">API Access</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4">Priority Support</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-gray-600">✗</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                            <td class="py-3 px-4 text-center text-green-400">✓</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Warning Section -->
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
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
