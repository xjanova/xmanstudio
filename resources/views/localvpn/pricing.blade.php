@extends($publicLayout ?? 'layouts.app')

@section('title', 'ซื้อ License - LocalVPN')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-cyan-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, rgba(6, 182, 212, 0.3) 0%, transparent 50%), radial-gradient(circle at 75% 75%, rgba(20, 184, 166, 0.3) 0%, transparent 50%);"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center px-4 py-2 bg-cyan-500/20 rounded-full text-cyan-300 text-sm mb-6 backdrop-blur-sm border border-cyan-500/30">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg>
                Virtual LAN over Internet
            </div>

            <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                Local<span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-teal-400">VPN</span>
            </h1>

            <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต — NAT Traversal อัตโนมัติ เข้ารหัส WireGuard
            </p>

            <div class="flex flex-wrap justify-center gap-4 text-gray-400 text-sm">
                <span class="px-3 py-1 bg-white/5 rounded-full border border-white/10">Virtual LAN</span>
                <span class="px-3 py-1 bg-white/5 rounded-full border border-white/10">NAT Traversal</span>
                <span class="px-3 py-1 bg-white/5 rounded-full border border-white/10">WireGuard Encryption</span>
                <span class="px-3 py-1 bg-white/5 rounded-full border border-white/10">Real-time Discovery</span>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-white mb-4">เลือกแพ็กเกจที่เหมาะกับคุณ</h2>
                <p class="text-gray-400">ใช้ฟรีตลอด หรืออัพเกรดเพื่อปลดล็อกสมาชิกในวง 50 คน</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Free Card -->
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700 hover:border-gray-600 transition-all">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">ฟรี</h3>
                        <div class="text-4xl font-black text-white">฿0</div>
                        <p class="text-green-400 text-sm mt-1">ใช้ฟรีตลอด</p>
                    </div>
                    <ul class="space-y-3 mb-6 text-sm">
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            สร้าง/เข้าร่วม Virtual LAN
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            สมาชิกในวงสูงสุด 5 คน
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            NAT Traversal
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-4 h-4 mr-2 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            เข้ารหัส WireGuard
                        </li>
                    </ul>
                    <a href="{{ route('localvpn.download') }}" class="block text-center text-white text-sm py-3 bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 rounded-xl transition-all font-medium">
                        ดาวน์โหลดฟรี
                    </a>
                </div>

                <!-- Monthly Card -->
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700 hover:border-teal-500 transition-all group">
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
                    <a href="{{ route('localvpn.checkout', 'monthly') }}{{ $machineId ? '?machine_id=' . $machineId : '' }}"
                       class="block w-full text-center py-3 px-4 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-xl transition-colors">
                        เลือกแพ็กเกจนี้
                    </a>
                </div>

                <!-- Yearly Card (Recommended) -->
                <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border-2 border-cyan-500 hover:border-cyan-400 transition-all group">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-gradient-to-r from-cyan-500 to-teal-500 text-white text-xs font-bold px-4 py-1 rounded-full shadow-lg">
                            แนะนำ
                        </span>
                    </div>
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">{{ $pricing['yearly']['name_th'] }}</h3>
                        <div class="text-4xl font-black text-cyan-400">฿{{ number_format($pricing['yearly']['price']) }}</div>
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
                    <a href="{{ route('localvpn.checkout', 'yearly') }}{{ $machineId ? '?machine_id=' . $machineId : '' }}"
                       class="block w-full text-center py-3 px-4 bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-700 hover:to-teal-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-cyan-500/25">
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
                    <a href="{{ route('localvpn.checkout', 'lifetime') }}{{ $machineId ? '?machine_id=' . $machineId : '' }}"
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
                    <p class="text-gray-400 text-sm">License Key เป็นรหัสสำหรับเปิดใช้งานแอพ LocalVPN หลังจากชำระเงินแล้ว คุณจะได้รับ Key ทางอีเมลเพื่อนำไปกรอกในแอพ</p>
                </div>
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="font-semibold text-white mb-2">ฟรีกับแพ็กเกจเสียเงินต่างกันอย่างไร?</h3>
                    <p class="text-gray-400 text-sm">แพ็กเกจฟรีรองรับสมาชิกในวง LAN สูงสุด 5 คน แพ็กเกจเสียเงินรองรับสูงสุด 50 คน พร้อมซัพพอร์ตพรีเมียม</p>
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
