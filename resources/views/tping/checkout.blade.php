@extends($publicLayout ?? 'layouts.app')

@section('title', 'ชำระเงิน - Tping ' . $planInfo['name'])

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="{{ route('tping.pricing') }}" class="hover:text-violet-600">Tping</a></li>
                <li><span>/</span></li>
                <li class="text-gray-900">ชำระเงิน</li>
            </ol>
        </nav>

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="md:flex">
                <!-- Order Summary -->
                <div class="md:w-1/3 bg-gradient-to-br from-indigo-900 to-gray-900 p-8 text-white">
                    <h2 class="text-lg font-semibold mb-6">สรุปคำสั่งซื้อ</h2>

                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center mb-2">
                                <span class="text-2xl mr-2">⌨️</span>
                                <h3 class="font-bold text-xl">Tping</h3>
                            </div>
                            <p class="text-gray-300 text-sm">ช่วยพิมพ์ สำหรับผู้ที่ใช้นิ้วไม่สะดวก</p>
                        </div>

                        <div class="py-4 border-t border-white/20">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-300">แพ็กเกจ</span>
                                <span class="font-semibold">{{ $planInfo['name_th'] }}</span>
                            </div>
                            @if($planInfo['duration_days'])
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-400">ระยะเวลา</span>
                                    <span class="text-gray-300">{{ $planInfo['duration_days'] }} วัน</span>
                                </div>
                            @else
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-400">ระยะเวลา</span>
                                    <span class="text-yellow-400">ตลอดชีพ</span>
                                </div>
                            @endif
                        </div>

                        <div class="py-4 border-t border-white/20">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">รวมทั้งสิ้น</span>
                                <span class="text-3xl font-black">฿{{ number_format($planInfo['price']) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="mt-6 pt-6 border-t border-white/20">
                        <h4 class="text-sm font-semibold text-gray-300 mb-3">รวมในแพ็กเกจนี้:</h4>
                        <ul class="space-y-2 text-sm">
                            @foreach($planInfo['features'] as $feature)
                            <li class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- Checkout Form -->
                <div class="md:w-2/3 p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">ข้อมูลการชำระเงิน</h2>

                    <form action="{{ route('tping.process', $plan) }}" method="POST">
                        @csrf

                        @if($machineId ?? false)
                            <input type="hidden" name="machine_id" value="{{ $machineId }}">
                        @endif

                        @if($errors->any())
                            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                                <ul class="list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Customer Info -->
                        <div class="space-y-4 mb-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                                <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name ?? '') }}" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล <span class="text-red-500">*</span></label>
                                <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">License Key จะส่งไปยังอีเมลนี้</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                                <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">เลือกวิธีชำระเงิน</h3>

                            <div class="space-y-3">
                                <label class="block cursor-pointer">
                                    <input type="radio" name="payment_method" value="promptpay" class="sr-only peer" checked>
                                    <div class="flex items-center p-4 border-2 rounded-xl peer-checked:border-violet-500 peer-checked:bg-violet-50 hover:bg-gray-50">
                                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">พร้อมเพย์ (PromptPay)</p>
                                            <p class="text-sm text-gray-500">สแกน QR Code เพื่อชำระเงิน</p>
                                        </div>
                                    </div>
                                </label>

                                <label class="block cursor-pointer">
                                    <input type="radio" name="payment_method" value="bank_transfer" class="sr-only peer">
                                    <div class="flex items-center p-4 border-2 rounded-xl peer-checked:border-violet-500 peer-checked:bg-violet-50 hover:bg-gray-50">
                                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">โอนเงินธนาคาร</p>
                                            <p class="text-sm text-gray-500">โอนเงินแล้วแนบสลิป</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full py-4 px-6 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-bold text-lg rounded-xl transition-all">
                            ดำเนินการชำระเงิน ฿{{ number_format($planInfo['price']) }}
                        </button>

                        <p class="mt-4 text-sm text-gray-500 text-center">
                            การดำเนินการต่อถือว่าคุณยอมรับ
                            <a href="/terms" class="text-violet-600 hover:underline">ข้อกำหนดการใช้งาน</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
