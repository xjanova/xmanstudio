@extends($publicLayout ?? 'layouts.app')

@section('title', 'ชำระเงิน - AutoTradeX')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-gray-400">
                <li><a href="{{ route('autotradex.pricing') }}" class="hover:text-purple-400">AutoTradeX</a></li>
                <li><span>/</span></li>
                <li class="text-white">ชำระเงิน</li>
            </ol>
        </nav>

        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-700">
            <div class="p-8">
                <!-- Order Summary -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-500/20 rounded-full mb-4">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-white">ชำระเงิน</h1>
                    <p class="mt-2 text-gray-400">
                        แพ็กเกจ: <span class="font-semibold text-purple-400">AutoTradeX {{ $planInfo['name'] }}</span>
                    </p>
                    <p class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400 mt-4">
                        ฿{{ number_format($order->total) }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        หมายเลขคำสั่งซื้อ: #{{ $order->id }}
                    </p>
                </div>

                @php
                    $promptpayEnabled = \App\Models\PaymentSetting::get('promptpay_enabled', false);
                    $promptpayQrImage = \App\Models\PaymentSetting::get('promptpay_qr_image');
                    $promptpayNumber = \App\Models\PaymentSetting::get('promptpay_number');
                    $promptpayName = \App\Models\PaymentSetting::get('promptpay_name');
                    $bankAccounts = \App\Models\BankAccount::where('is_active', true)->get();
                @endphp

                @if($order->payment_method === 'promptpay' && $promptpayEnabled)
                    <!-- PromptPay QR Code -->
                    <div class="text-center">
                        <div class="inline-block p-4 bg-white rounded-xl">
                            @if($promptpayQrImage)
                                <img src="{{ Storage::url($promptpayQrImage) }}" alt="PromptPay QR Code"
                                     class="w-64 h-64 mx-auto object-contain">
                            @else
                                <div class="w-64 h-64 flex items-center justify-center bg-gray-100 rounded-lg">
                                    <span class="text-gray-400">QR Code</span>
                                </div>
                            @endif
                        </div>
                        <p class="mt-4 text-gray-300">
                            สแกน QR Code ด้วยแอปธนาคารของคุณ
                        </p>
                        @if($promptpayNumber)
                            <p class="text-sm text-gray-500">
                                พร้อมเพย์: {{ $promptpayNumber }}
                            </p>
                        @endif
                        @if($promptpayName)
                            <p class="text-sm text-gray-500">
                                ชื่อ: {{ $promptpayName }}
                            </p>
                        @endif
                    </div>
                @elseif($order->payment_method === 'bank_transfer' && $bankAccounts->count() > 0)
                    <!-- Bank Transfer Info -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-white">บัญชีธนาคาร</h3>
                        @foreach($bankAccounts as $bank)
                            <div class="p-4 bg-gray-700/50 rounded-xl border border-gray-600">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-12 h-12 bg-white rounded-lg flex items-center justify-center">
                                        @if($bank->logo_path)
                                            <img src="{{ Storage::url($bank->logo_path) }}" alt="{{ $bank->bank_name }}" class="w-8 h-8 object-contain">
                                        @else
                                            <span class="text-sm font-bold text-gray-700">{{ $bank->bank_code ?? 'BANK' }}</span>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-semibold text-white">{{ $bank->bank_name }}</p>
                                        <p class="text-lg font-mono text-purple-400">{{ $bank->account_number }}</p>
                                        <p class="text-sm text-gray-400">{{ $bank->account_name }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Fallback - Show all payment options -->
                    <div class="space-y-6">
                        @if($promptpayEnabled)
                            <div class="text-center">
                                <h3 class="text-lg font-semibold text-white mb-4">พร้อมเพย์</h3>
                                <div class="inline-block p-4 bg-white rounded-xl">
                                    @if($promptpayQrImage)
                                        <img src="{{ Storage::url($promptpayQrImage) }}" alt="PromptPay QR Code"
                                             class="w-48 h-48 mx-auto object-contain">
                                    @endif
                                </div>
                                @if($promptpayNumber)
                                    <p class="text-sm text-gray-400 mt-2">{{ $promptpayNumber }}</p>
                                @endif
                            </div>
                        @endif

                        @if($bankAccounts->count() > 0)
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-4">โอนเงินธนาคาร</h3>
                                @foreach($bankAccounts as $bank)
                                    <div class="p-4 bg-gray-700/50 rounded-xl border border-gray-600 mb-3">
                                        <p class="font-semibold text-white">{{ $bank->bank_name }}</p>
                                        <p class="text-lg font-mono text-purple-400">{{ $bank->account_number }}</p>
                                        <p class="text-sm text-gray-400">{{ $bank->account_name }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Upload Slip Form -->
                <div class="mt-8 pt-8 border-t border-gray-700">
                    <h3 class="text-lg font-semibold text-white mb-4">แจ้งชำระเงิน</h3>

                    <form action="{{ route('autotradex.confirm-payment', $order->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        @if($errors->any())
                            <div class="mb-6 bg-red-900/50 border border-red-500/50 text-red-200 px-4 py-3 rounded-xl">
                                <ul class="list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    อัพโหลดสลิปการโอนเงิน <span class="text-red-400">*</span>
                                </label>
                                <input type="file" name="payment_slip" accept="image/*" required
                                       class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600 rounded-xl text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <p class="mt-1 text-sm text-gray-500">รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 5MB</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">หมายเหตุ (ถ้ามี)</label>
                                <textarea name="notes" rows="2"
                                          class="w-full px-4 py-3 bg-gray-700/50 border border-gray-600 rounded-xl text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                          placeholder="ข้อมูลเพิ่มเติม..."></textarea>
                            </div>

                            <button type="submit"
                                    class="w-full py-4 px-6 rounded-xl text-white bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 transition-colors font-bold text-lg">
                                แจ้งชำระเงิน
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Payment Info -->
                <div class="mt-8 p-4 bg-yellow-900/30 border border-yellow-500/50 rounded-xl">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <h4 class="text-sm font-semibold text-yellow-400">หมายเหตุ</h4>
                            <ul class="mt-1 text-sm text-gray-300 list-disc list-inside space-y-1">
                                <li>กรุณาชำระเงินภายใน 24 ชั่วโมง</li>
                                <li>License Key จะส่งไปยังอีเมล: <span class="text-purple-400">{{ $order->email }}</span></li>
                                <li>หลังตรวจสอบยอดเงินแล้ว จะได้รับ License ภายใน 1-24 ชม.</li>
                                <li>หากมีข้อสงสัย ติดต่อ Line OA: @xmanstudio</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="mt-6 p-4 bg-gray-700/30 rounded-xl">
                    <h4 class="text-sm font-semibold text-gray-300 mb-3">รายละเอียดคำสั่งซื้อ</h4>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">ชื่อลูกค้า</dt>
                            <dd class="text-white">{{ $order->customer_name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">อีเมล</dt>
                            <dd class="text-white">{{ $order->email }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">โทรศัพท์</dt>
                            <dd class="text-white">{{ $order->phone }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">แพ็กเกจ</dt>
                            <dd class="text-purple-400 font-semibold">AutoTradeX {{ $planInfo['name'] }}</dd>
                        </div>
                        @if($planInfo['duration_days'])
                            <div class="flex justify-between">
                                <dt class="text-gray-500">ระยะเวลา</dt>
                                <dd class="text-white">{{ $planInfo['duration_days'] }} วัน</dd>
                            </div>
                        @else
                            <div class="flex justify-between">
                                <dt class="text-gray-500">ระยะเวลา</dt>
                                <dd class="text-yellow-400">ตลอดชีพ</dd>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-gray-600">
                            <dt class="text-gray-400 font-semibold">ยอดรวม</dt>
                            <dd class="text-white font-bold">฿{{ number_format($order->total) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
