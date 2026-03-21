@extends($publicLayout ?? 'layouts.app')

@section('title', 'ซื้อ License - SmsChecker')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-indigo-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, rgba(139, 92, 246, 0.3) 0%, transparent 50%), radial-gradient(circle at 75% 75%, rgba(59, 130, 246, 0.3) 0%, transparent 50%);"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center px-4 py-2 bg-violet-500/20 rounded-full text-violet-300 text-sm mb-6 backdrop-blur-sm border border-violet-500/30">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                SMS Payment Checker for Android
            </div>

            <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                T<span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-blue-400">ping</span>
            </h1>

            <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                ระบบตรวจสอบ SMS อัตโนมัติ — ตรวจจับ SMS ธนาคาร อนุมัติออเดอร์อัตโนมัติ รองรับ 14+ ธนาคารไทย
            </p>

            <div class="flex flex-wrap justify-center gap-4 text-gray-400 text-sm">
                <span class="px-3 py-1 bg-white/5 rounded-full border border-white/10">SMS Detection</span>
                <span class="px-3 py-1 bg-white/5 rounded-full border border-white/10">Bank Parser</span>
                <span class="px-3 py-1 bg-white/5 rounded-full border border-white/10">Cloud Sync</span>
                <span class="px-3 py-1 bg-white/5 rounded-full border border-white/10">Multi-Server</span>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-white mb-4">เลือกแพ็กเกจที่เหมาะกับคุณ</h2>
                <p class="text-gray-400">ทดลองใช้ฟรี 24 ชั่วโมง จากนั้นเลือกแพ็กเกจที่ต้องการ</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Trial Card -->
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700 hover:border-gray-600 transition-all">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">ทดลองใช้</h3>
                        <div class="text-4xl font-black text-white">ฟรี</div>
                        <p class="text-gray-500 text-sm mt-1">24 ชั่วโมง</p>
                    </div>
                    <ul class="space-y-3 mb-6 text-sm">
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            ฟีเจอร์พื้นฐาน
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Multi-Server
                        </li>
                        <li class="flex items-center text-gray-500">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Cloud Sync
                        </li>
                    </ul>
                    <a href="{{ route('smschecker.download') }}" class="block text-center text-white text-sm py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 rounded-xl transition-all font-medium">
                        ดาวน์โหลดแอพ
                    </a>
                </div>

                <!-- Monthly Card -->
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700 hover:border-blue-500 transition-all group">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">{{ $pricing['monthly']['name_th'] }}</h3>
                        <div class="text-4xl font-black text-white">฿{{ number_format($pricing['monthly']['price']) }}</div>
                        <p class="text-gray-500 text-sm mt-1">{{ $pricing['monthly']['duration_days'] }} วัน</p>
                    </div>
                    <ul class="space-y-3 mb-6 text-sm">
                        @foreach($pricing['monthly']['features'] as $feature)
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('smschecker.checkout', 'monthly') }}{{ $machineId ? '?machine_id=' . $machineId : '' }}"
                       class="block w-full text-center py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>

                <!-- Yearly Card (Recommended) -->
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border-2 border-violet-500 hover:border-violet-400 transition-all group">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-gradient-to-r from-violet-500 to-purple-500 text-white text-xs font-bold px-4 py-1 rounded-full shadow-lg">
                            แนะนำ
                        </span>
                    </div>
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">{{ $pricing['yearly']['name_th'] }}</h3>
                        <div class="text-4xl font-black text-violet-400">฿{{ number_format($pricing['yearly']['price']) }}</div>
                        <p class="text-gray-500 text-sm mt-1">{{ $pricing['yearly']['duration_days'] }} วัน</p>
                        <span class="inline-block mt-2 text-xs font-bold text-green-400 bg-green-400/10 px-3 py-1 rounded-full">ประหยัด 48%</span>
                    </div>
                    <ul class="space-y-3 mb-6 text-sm">
                        @foreach($pricing['yearly']['features'] as $feature)
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('smschecker.checkout', 'yearly') }}{{ $machineId ? '?machine_id=' . $machineId : '' }}"
                       class="block w-full text-center py-3 px-4 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-violet-500/25">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>

                <!-- Lifetime Card -->
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700 hover:border-emerald-500 transition-all group">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">{{ $pricing['lifetime']['name_th'] }}</h3>
                        <div class="text-4xl font-black text-emerald-400">฿{{ number_format($pricing['lifetime']['price']) }}</div>
                        <p class="text-yellow-400 text-sm mt-1">ไม่มีวันหมดอายุ</p>
                    </div>
                    <ul class="space-y-3 mb-6 text-sm">
                        @foreach($pricing['lifetime']['features'] as $feature)
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('smschecker.checkout', 'lifetime') }}{{ $machineId ? '?machine_id=' . $machineId : '' }}"
                       class="block w-full text-center py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-2xl font-bold text-white text-center mb-8">คำถามที่พบบ่อย</h2>
            <div class="space-y-4">
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="font-semibold text-white mb-2">License Key คืออะไร?</h3>
                    <p class="text-gray-400 text-sm">License Key เป็นรหัสสำหรับเปิดใช้งานแอพ SmsChecker หลังจากชำระเงินแล้ว คุณจะได้รับ Key ทางอีเมลเพื่อนำไปกรอกในแอพ</p>
                </div>
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="font-semibold text-white mb-2">ใช้ได้กี่เครื่อง?</h3>
                    <p class="text-gray-400 text-sm">แพ็กเกจรายเดือนและรายปีใช้ได้ 1 เครื่อง แพ็กเกจตลอดชีพสามารถใช้ได้หลายเครื่อง</p>
                </div>
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="font-semibold text-white mb-2">ชำระเงินอย่างไร?</h3>
                    <p class="text-gray-400 text-sm">รองรับการชำระเงินผ่าน PromptPay (สแกน QR Code) และโอนเงินผ่านธนาคาร</p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
