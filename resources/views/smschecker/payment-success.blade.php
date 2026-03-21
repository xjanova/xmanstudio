@extends($publicLayout ?? 'layouts.app')

@section('title', 'ขอบคุณสำหรับการสั่งซื้อ - SmsChecker')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-indigo-900 to-gray-900 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-700">
            <div class="p-8 text-center">
                <!-- Success Icon -->
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-500/20 rounded-full mb-6">
                    <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h1 class="text-3xl font-bold text-white mb-4">
                    @if(isset($licenses) && $licenses->count() > 0)
                        ชำระเงินสำเร็จ!
                    @else
                        ขอบคุณสำหรับการสั่งซื้อ!
                    @endif
                </h1>

                {{-- === WALLET PAYMENT: Show License Key immediately === --}}
                @if(isset($licenses) && $licenses->count() > 0)
                    <p class="text-gray-300 mb-6">
                        ชำระเงินด้วย Wallet สำเร็จ • License Key พร้อมใช้งานทันที
                    </p>

                    {{-- License Key Display --}}
                    @foreach($licenses as $license)
                    <div class="bg-gradient-to-r from-green-900/40 to-emerald-900/40 border border-green-500/40 rounded-xl p-6 mb-6">
                        <h2 class="text-sm font-semibold text-green-400 mb-3">🔑 License Key ของคุณ</h2>
                        <div class="flex items-center justify-center gap-3">
                            <code class="text-2xl md:text-3xl font-mono font-bold text-white tracking-wider" id="license-key">{{ $license->license_key }}</code>
                            <button onclick="copyKey()" title="คัดลอก"
                                    class="p-2 bg-green-600/30 hover:bg-green-600/50 rounded-lg transition-colors">
                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                        <p id="copy-msg" class="text-xs text-green-400 mt-2 hidden">คัดลอกแล้ว!</p>

                        {{-- HWID binding status --}}
                        @if($license->machine_id)
                        <div class="mt-4 pt-4 border-t border-green-500/20 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5C18.247 6.44 18.5 7.943 18.5 9.5c0 5.523-3.694 10.148-8.5 11.5-4.806-1.352-8.5-5.977-8.5-11.5 0-1.557.253-3.06.666-4.501z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-green-400 font-medium">ผูกกับเครื่องแล้ว — ไม่ต้องกรอก Key ในแอพ</span>
                        </div>
                        @endif

                        {{-- QR Code for app scan --}}
                        @if(!$license->machine_id)
                        <div class="mt-4 pt-4 border-t border-green-500/20 text-center">
                            <p class="text-sm text-gray-400 mb-3">สแกน QR Code เพื่อกรอก License Key ในแอพ</p>
                            <div class="bg-white p-3 rounded-xl inline-block">
                                <div id="license-qrcode-{{ $loop->index }}"></div>
                            </div>
                        </div>
                        @endif

                        {{-- License details --}}
                        <div class="mt-4 pt-4 border-t border-green-500/20 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-gray-400">ประเภท</span>
                                <p class="text-white font-semibold">{{ ucfirst($license->license_type) }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400">หมดอายุ</span>
                                <p class="text-white font-semibold">
                                    @if($license->license_type === 'lifetime')
                                        <span class="text-yellow-400">ตลอดชีพ</span>
                                    @elseif($license->expires_at)
                                        {{ $license->expires_at->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach

                {{-- === BANK/PROMPTPAY: Waiting for verification === --}}
                @else
                    <p class="text-gray-300 mb-6">
                        เราได้รับหลักฐานการชำระเงินของคุณแล้ว<br>
                        กำลังตรวจสอบและจะส่ง License Key ไปยังอีเมลของคุณโดยเร็วที่สุด
                    </p>
                @endif

                <!-- Order Summary -->
                <div class="bg-gray-700/30 rounded-xl p-6 text-left mb-6">
                    <h2 class="text-lg font-semibold text-white mb-4">สรุปคำสั่งซื้อ</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-400">หมายเลขคำสั่งซื้อ</dt>
                            <dd class="text-white font-mono">{{ $order->order_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">แพ็กเกจ</dt>
                            <dd class="text-violet-400 font-semibold">SmsChecker {{ $planInfo['name'] }}</dd>
                        </div>
                        @if($order->discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-400">ราคาปกติ</dt>
                            <dd class="text-gray-400 line-through">฿{{ number_format($order->subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-green-400">ส่วนลด Wallet</dt>
                            <dd class="text-green-400">-฿{{ number_format($order->discount) }}</dd>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-gray-400">ยอดชำระ</dt>
                            <dd class="text-white font-bold text-lg">฿{{ number_format($order->total) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">วิธีชำระ</dt>
                            <dd class="text-white">
                                @if($order->payment_method === 'wallet')
                                    <span class="text-purple-400">💰 Wallet</span>
                                @elseif($order->payment_method === 'promptpay')
                                    PromptPay
                                @else
                                    โอนเงินธนาคาร
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">สถานะ</dt>
                            <dd>
                                @if($order->payment_status === 'paid')
                                    <span class="text-green-400 font-semibold">✓ ชำระเงินแล้ว</span>
                                @else
                                    <span class="text-yellow-400">รอตรวจสอบการชำระเงิน</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">อีเมล</dt>
                            <dd class="text-white">{{ $order->customer_email }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Next Steps --}}
                @if(isset($licenses) && $licenses->count() > 0 && $licenses->first()->machine_id)
                    {{-- Wallet + HWID bound: just open the app --}}
                    <div class="bg-violet-900/30 border border-violet-500/30 rounded-xl p-6 text-left mb-6">
                        <h3 class="text-violet-400 font-semibold mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ขั้นตอนถัดไป
                        </h3>
                        <ol class="space-y-2 text-sm text-gray-300 list-decimal list-inside">
                            <li>License ถูกผูกกับเครื่องของคุณอัตโนมัติแล้ว</li>
                            <li>เปิดแอพ SmsChecker — ระบบจะตรวจสอบ License ให้โดยอัตโนมัติ</li>
                            <li>เริ่มใช้งานได้เลย!</li>
                        </ol>
                    </div>
                @elseif(isset($licenses) && $licenses->count() > 0)
                    {{-- Wallet but no HWID: need to enter key in app --}}
                    <div class="bg-violet-900/30 border border-violet-500/30 rounded-xl p-6 text-left mb-6">
                        <h3 class="text-violet-400 font-semibold mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ขั้นตอนถัดไป
                        </h3>
                        <ol class="space-y-2 text-sm text-gray-300 list-decimal list-inside">
                            <li>คัดลอก License Key ด้านบน</li>
                            <li>เปิดแอพ SmsChecker แล้วกรอก License Key</li>
                            <li>กด "เปิดใช้งาน" เพื่อ Activate</li>
                        </ol>
                    </div>
                @else
                    {{-- Bank/PromptPay: waiting for verification --}}
                    <div class="bg-violet-900/30 border border-violet-500/30 rounded-xl p-6 text-left mb-6">
                        <h3 class="text-violet-400 font-semibold mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ขั้นตอนถัดไป
                        </h3>
                        <ol class="space-y-2 text-sm text-gray-300 list-decimal list-inside">
                            <li>ทีมงานจะตรวจสอบหลักฐานการชำระเงินภายใน 1-24 ชั่วโมง</li>
                            <li>เมื่อยืนยันการชำระเงินแล้ว License Key จะถูกส่งไปยังอีเมล <span class="text-violet-400">{{ $order->customer_email }}</span></li>
                            <li>นำ License Key ไปกรอกในแอพ SmsChecker เพื่อเปิดใช้งาน</li>
                        </ol>
                    </div>
                @endif

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
                    <a href="{{ route('smschecker.pricing') }}"
                       class="px-6 py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-semibold rounded-xl transition-colors">
                        ดูแพ็กเกจอื่น
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
function copyKey() {
    const key = document.getElementById('license-key')?.textContent;
    if (key) {
        navigator.clipboard.writeText(key).then(() => {
            const msg = document.getElementById('copy-msg');
            if (msg) {
                msg.classList.remove('hidden');
                setTimeout(() => msg.classList.add('hidden'), 2000);
            }
        });
    }
}

// Generate QR Codes for license keys
document.addEventListener('DOMContentLoaded', function() {
    @if(isset($licenses))
    @foreach($licenses as $license)
    @if(!$license->machine_id)
    (function() {
        var el = document.getElementById('license-qrcode-{{ $loop->index }}');
        if (el) {
            new QRCode(el, {
                text: 'smschecker://activate?key={{ $license->license_key }}',
                width: 160,
                height: 160,
                colorDark: '#065f46',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    })();
    @endif
    @endforeach
    @endif
});
</script>
@endsection
