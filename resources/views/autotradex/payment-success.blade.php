@extends($publicLayout ?? 'layouts.app')

@section('title', 'ขอบคุณสำหรับการสั่งซื้อ - AutoTradeX')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-700">
            <div class="p-8 text-center">
                <!-- Success Icon -->
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-500/20 rounded-full mb-6">
                    <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h1 class="text-3xl font-bold text-white mb-4">ขอบคุณสำหรับการสั่งซื้อ!</h1>
                <p class="text-gray-300 mb-6">
                    เราได้รับหลักฐานการชำระเงินของคุณแล้ว<br>
                    กำลังตรวจสอบและจะส่ง License Key ไปยังอีเมลของคุณโดยเร็วที่สุด
                </p>

                <!-- Order Summary -->
                <div class="bg-gray-700/30 rounded-xl p-6 text-left mb-6">
                    <h2 class="text-lg font-semibold text-white mb-4">สรุปคำสั่งซื้อ</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-400">หมายเลขคำสั่งซื้อ</dt>
                            <dd class="text-white font-mono">#{{ $order->id }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">แพ็กเกจ</dt>
                            <dd class="text-purple-400 font-semibold">AutoTradeX {{ $planInfo['name'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">ยอดชำระ</dt>
                            <dd class="text-white font-bold">฿{{ number_format($order->total) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">สถานะ</dt>
                            <dd class="text-yellow-400">รอตรวจสอบการชำระเงิน</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">อีเมลที่จะได้รับ License</dt>
                            <dd class="text-white">{{ $order->email }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- What's Next -->
                <div class="bg-purple-900/30 border border-purple-500/30 rounded-xl p-6 text-left mb-6">
                    <h3 class="text-purple-400 font-semibold mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ขั้นตอนถัดไป
                    </h3>
                    <ol class="space-y-2 text-sm text-gray-300 list-decimal list-inside">
                        <li>ทีมงานจะตรวจสอบหลักฐานการชำระเงินภายใน 1-24 ชั่วโมง</li>
                        <li>เมื่อยืนยันการชำระเงินแล้ว License Key จะถูกส่งไปยังอีเมล <span class="text-purple-400">{{ $order->email }}</span></li>
                        <li>นำ License Key ไปใส่ในโปรแกรม AutoTradeX เพื่อเปิดใช้งาน</li>
                    </ol>
                </div>

                <!-- Contact Info -->
                <div class="bg-gray-700/30 rounded-xl p-4 text-sm text-gray-400">
                    <p>
                        หากมีข้อสงสัยหรือต้องการความช่วยเหลือ<br>
                        ติดต่อเราได้ที่ Line OA: <span class="text-white font-semibold">@xmanstudio</span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('home') }}"
                       class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-xl transition-colors">
                        กลับหน้าแรก
                    </a>
                    <a href="{{ route('autotradex.pricing') }}"
                       class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl transition-colors">
                        ดูแพ็กเกจอื่น
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
