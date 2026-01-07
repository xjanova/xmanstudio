@extends('layouts.app')

@section('title', 'ชำระเงิน - XMAN Studio')

@section('content')
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="{{ route('rental.index') }}" class="hover:text-primary-600">แพ็กเกจ</a></li>
                <li><span>/</span></li>
                <li class="text-gray-900">ชำระเงิน</li>
            </ol>
        </nav>

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="p-8">
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">ชำระเงิน</h1>
                    <p class="mt-2 text-gray-600">
                        แพ็กเกจ: <span class="font-semibold">{{ $rental->rentalPackage->display_name }}</span>
                    </p>
                    <p class="text-3xl font-bold text-primary-600 mt-4">
                        ฿{{ number_format($payment->amount) }}
                    </p>
                </div>

                @if($payment->payment_method === 'promptpay')
                    <!-- PromptPay QR Code -->
                    <div class="text-center">
                        <div class="inline-block p-4 bg-white border-4 border-primary-500 rounded-xl">
                            <img src="{{ $qrData['qr_image_url'] ?? '' }}" alt="PromptPay QR Code"
                                 class="w-64 h-64 mx-auto">
                        </div>
                        <p class="mt-4 text-gray-600">
                            สแกน QR Code ด้วยแอปธนาคารของคุณ
                        </p>
                        <p class="text-sm text-gray-500">
                            พร้อมเพย์: {{ $qrData['promptpay_number'] ?? '-' }}
                        </p>
                    </div>
                @elseif($payment->payment_method === 'bank_transfer')
                    <!-- Bank Transfer Info -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">บัญชีธนาคาร</h3>
                        @foreach($bankAccounts ?? [] as $bank)
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow">
                                        <span class="text-sm font-bold text-gray-700">{{ $bank['bank_code'] }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-semibold text-gray-900">{{ $bank['bank'] }}</p>
                                        <p class="text-lg font-mono text-primary-600">{{ $bank['account_number'] }}</p>
                                        <p class="text-sm text-gray-500">{{ $bank['account_name'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Upload Slip Form -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">แจ้งชำระเงิน</h3>

                    <form action="{{ route('rental.confirm-payment', $payment) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    อัพโหลดสลิปการโอนเงิน <span class="text-red-500">*</span>
                                </label>
                                <input type="file" name="payment_slip" accept="image/*" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                <p class="mt-1 text-sm text-gray-500">รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 5MB</p>
                                @error('payment_slip')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ (ถ้ามี)</label>
                                <textarea name="notes" rows="2"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                          placeholder="ข้อมูลเพิ่มเติม..."></textarea>
                            </div>

                            <button type="submit"
                                    class="w-full py-4 px-6 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors font-semibold">
                                แจ้งชำระเงิน
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Payment Info -->
                <div class="mt-8 p-4 bg-yellow-50 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <h4 class="text-sm font-semibold text-yellow-800">หมายเหตุ</h4>
                            <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside">
                                <li>กรุณาชำระเงินภายใน 24 ชั่วโมง</li>
                                <li>หมายเลขอ้างอิง: {{ $payment->reference_number }}</li>
                                <li>หากมีข้อสงสัย ติดต่อ Line OA: @xmanstudio</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
