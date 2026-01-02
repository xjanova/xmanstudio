@extends('layouts.app')

@section('title', 'ชำระเงิน - XMAN Studio')

@section('content')
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">ชำระเงิน</h1>

    @if($errors->any())
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('orders.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Customer Info -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลผู้สั่งซื้อ</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                            <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name ?? '') }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล <span class="text-red-500">*</span></label>
                            <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">วิธีการชำระเงิน</h2>

                    <div class="space-y-3">
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:border-primary-500 transition">
                            <input type="radio" name="payment_method" value="promptpay" checked
                                   class="text-primary-600 focus:ring-primary-500">
                            <div class="ml-3">
                                <span class="font-medium text-gray-900">PromptPay QR</span>
                                <p class="text-sm text-gray-500">สแกน QR Code ชำระผ่าน Mobile Banking</p>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:border-primary-500 transition">
                            <input type="radio" name="payment_method" value="bank_transfer"
                                   class="text-primary-600 focus:ring-primary-500">
                            <div class="ml-3">
                                <span class="font-medium text-gray-900">โอนเงินผ่านธนาคาร</span>
                                <p class="text-sm text-gray-500">โอนเงินและแนบสลิปหลักฐาน</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">หมายเหตุ</h2>
                    <textarea name="notes" rows="3" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">รายการสั่งซื้อ</h2>

                    <div class="space-y-3 mb-6">
                        @foreach($cart->items as $item)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">
                                    {{ $item->product->name }} x {{ $item->quantity }}
                                </span>
                                <span class="text-gray-900">{{ number_format($item->price * $item->quantity, 2) }} บาท</span>
                            </div>
                        @endforeach

                        <div class="border-t pt-3 flex justify-between text-lg font-bold text-gray-900">
                            <span>ยอดรวมทั้งหมด</span>
                            <span>{{ number_format($cart->total, 2) }} บาท</span>
                        </div>
                    </div>

                    <button type="submit"
                            class="block w-full text-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold">
                        ยืนยันสั่งซื้อ
                    </button>

                    <p class="mt-4 text-xs text-gray-500 text-center">
                        เมื่อกดยืนยัน ถือว่าคุณยอมรับ<a href="#" class="text-primary-600 hover:underline">ข้อตกลงและเงื่อนไข</a>
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
