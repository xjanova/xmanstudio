@extends($publicLayout ?? 'layouts.app')

@section('title', 'ขอบคุณสำหรับการสั่งซื้อ - Tping')

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
                        <x-bi th="ชำระเงินสำเร็จ!" en="Payment Successful!" layout="stack" />
                    @else
                        <x-bi th="ขอบคุณสำหรับการสั่งซื้อ!" en="Thank You for Your Order!" layout="stack" />
                    @endif
                </h1>

                {{-- === WALLET PAYMENT: Show License Key immediately === --}}
                @if(isset($licenses) && $licenses->count() > 0)
                    <p class="text-gray-300 mb-6">
                        <x-bi th="ชำระเงินด้วย Wallet สำเร็จ • License Key พร้อมใช้งานทันที" en="Wallet payment successful • License Key ready to use immediately" />
                    </p>

                    {{-- License Key Display --}}
                    @foreach($licenses as $license)
                    <div class="bg-gradient-to-r from-green-900/40 to-emerald-900/40 border border-green-500/40 rounded-xl p-6 mb-6">
                        <h2 class="text-sm font-semibold text-green-400 mb-3">🔑 <x-bi th="License Key ของคุณ" en="Your License Key" /></h2>
                        <div class="flex items-center justify-center gap-3">
                            <code class="text-2xl md:text-3xl font-mono font-bold text-white tracking-wider" id="license-key">{{ $license->license_key }}</code>
                            <button onclick="copyKey()" title="{{ bi('common.copy') }}"
                                    class="p-2 bg-green-600/30 hover:bg-green-600/50 rounded-lg transition-colors">
                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                        <p id="copy-msg" class="text-xs text-green-400 mt-2 hidden"><x-bi k="common.copied" /></p>

                        {{-- HWID binding status --}}
                        @if($license->machine_id)
                        <div class="mt-4 pt-4 border-t border-green-500/20 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5C18.247 6.44 18.5 7.943 18.5 9.5c0 5.523-3.694 10.148-8.5 11.5-4.806-1.352-8.5-5.977-8.5-11.5 0-1.557.253-3.06.666-4.501z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-green-400 font-medium"><x-bi th="ผูกกับเครื่องแล้ว — ไม่ต้องกรอก Key ในแอพ" en="Bound to your device — no need to enter the key in the app" /></span>
                        </div>
                        @endif

                        {{-- QR Code for app scan --}}
                        @if(!$license->machine_id)
                        <div class="mt-4 pt-4 border-t border-green-500/20 text-center">
                            <p class="text-sm text-gray-400 mb-3"><x-bi th="สแกน QR Code เพื่อกรอก License Key ในแอพ" en="Scan the QR Code to enter the License Key in the app" /></p>
                            <div class="bg-white p-3 rounded-xl inline-block">
                                <div id="license-qrcode-{{ $loop->index }}"></div>
                            </div>
                        </div>
                        @endif

                        {{-- License details --}}
                        <div class="mt-4 pt-4 border-t border-green-500/20 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-gray-400"><x-bi th="ประเภท" en="Type" /></span>
                                <p class="text-white font-semibold">{{ ucfirst($license->license_type) }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400"><x-bi th="หมดอายุ" en="Expires" /></span>
                                <p class="text-white font-semibold">
                                    @if($license->license_type === 'lifetime')
                                        <span class="text-yellow-400"><x-bi th="ตลอดชีพ" en="Lifetime" /></span>
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
                        <x-bi th="เราได้รับหลักฐานการชำระเงินของคุณแล้ว" en="We have received your payment proof" /><br>
                        <x-bi th="กำลังตรวจสอบและจะส่ง License Key ไปยังอีเมลของคุณโดยเร็วที่สุด" en="We are verifying it and will send your License Key to your email as soon as possible" />
                    </p>
                @endif

                <!-- Order Summary -->
                <div class="bg-gray-700/30 rounded-xl p-6 text-left mb-6">
                    <h2 class="text-lg font-semibold text-white mb-4"><x-bi k="common.order_summary" /></h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-400"><x-bi k="common.order_number" /></dt>
                            <dd class="text-white font-mono">{{ $order->order_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400"><x-bi th="แพ็กเกจ" en="Package" /></dt>
                            <dd class="text-violet-400 font-semibold">Tping {{ $planInfo['name'] }}</dd>
                        </div>
                        @if($order->discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-400"><x-bi th="ราคาปกติ" en="Regular Price" /></dt>
                            <dd class="text-gray-400 line-through">฿{{ number_format($order->subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-green-400"><x-bi th="ส่วนลด Wallet" en="Wallet Discount" /></dt>
                            <dd class="text-green-400">-฿{{ number_format($order->discount) }}</dd>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-gray-400"><x-bi th="ยอดชำระ" en="Amount Paid" /></dt>
                            <dd class="text-white font-bold text-lg">฿{{ number_format($order->total) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400"><x-bi k="common.payment_method" /></dt>
                            <dd class="text-white">
                                @if($order->payment_method === 'wallet')
                                    <span class="text-purple-400">💰 Wallet</span>
                                @elseif($order->payment_method === 'promptpay')
                                    PromptPay
                                @else
                                    <x-bi th="โอนเงินธนาคาร" en="Bank Transfer" />
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400"><x-bi k="common.status" /></dt>
                            <dd>
                                @if($order->payment_status === 'paid')
                                    <span class="text-green-400 font-semibold">✓ <x-bi k="common.status_paid" /></span>
                                @else
                                    <span class="text-yellow-400"><x-bi th="รอตรวจสอบการชำระเงิน" en="Awaiting payment verification" /></span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400"><x-bi k="common.email" /></dt>
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
                            <x-bi th="ขั้นตอนถัดไป" en="Next Steps" />
                        </h3>
                        <ol class="space-y-2 text-sm text-gray-300 list-decimal list-inside">
                            <li><x-bi th="License ถูกผูกกับเครื่องของคุณอัตโนมัติแล้ว" en="Your license has been automatically bound to your device" /></li>
                            <li><x-bi th="เปิดแอพ Tping — ระบบจะตรวจสอบ License ให้โดยอัตโนมัติ" en="Open the Tping app — it will verify your license automatically" /></li>
                            <li><x-bi th="เริ่มใช้งานได้เลย!" en="Start using it right away!" /></li>
                        </ol>
                    </div>
                @elseif(isset($licenses) && $licenses->count() > 0)
                    {{-- Wallet but no HWID: need to enter key in app --}}
                    <div class="bg-violet-900/30 border border-violet-500/30 rounded-xl p-6 text-left mb-6">
                        <h3 class="text-violet-400 font-semibold mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <x-bi th="ขั้นตอนถัดไป" en="Next Steps" />
                        </h3>
                        <ol class="space-y-2 text-sm text-gray-300 list-decimal list-inside">
                            <li><x-bi th="คัดลอก License Key ด้านบน" en="Copy the License Key above" /></li>
                            <li><x-bi th="เปิดแอพ Tping แล้วกรอก License Key" en="Open the Tping app and enter the License Key" /></li>
                            <li><x-bi th="กด &quot;เปิดใช้งาน&quot; เพื่อ Activate" en="Press &quot;Activate&quot; to activate" /></li>
                        </ol>
                    </div>
                @else
                    {{-- Bank/PromptPay: waiting for verification --}}
                    <div class="bg-violet-900/30 border border-violet-500/30 rounded-xl p-6 text-left mb-6">
                        <h3 class="text-violet-400 font-semibold mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <x-bi th="ขั้นตอนถัดไป" en="Next Steps" />
                        </h3>
                        <ol class="space-y-2 text-sm text-gray-300 list-decimal list-inside">
                            <li><x-bi th="ทีมงานจะตรวจสอบหลักฐานการชำระเงินภายใน 1-24 ชั่วโมง" en="Our team will verify your payment proof within 1-24 hours" /></li>
                            <li><x-bi th="เมื่อยืนยันการชำระเงินแล้ว License Key จะถูกส่งไปยังอีเมล" en="Once payment is confirmed, your License Key will be sent to" /> <span class="text-violet-400">{{ $order->customer_email }}</span></li>
                            <li><x-bi th="นำ License Key ไปกรอกในแอพ Tping เพื่อเปิดใช้งาน" en="Enter the License Key in the Tping app to activate" /></li>
                        </ol>
                    </div>
                @endif

                <!-- Contact Info -->
                <div class="bg-gray-700/30 rounded-xl p-4 text-sm text-gray-400">
                    <p>
                        <x-bi th="หากมีข้อสงสัยหรือต้องการความช่วยเหลือ" en="If you have questions or need assistance" /><br>
                        <x-bi th="ติดต่อเราได้ที่ Line OA:" en="Contact us via Line OA:" /> <span class="text-white font-semibold">@xmanstudio</span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('home') }}"
                       class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-xl transition-colors">
                        <x-bi th="กลับหน้าแรก" en="Back to Home" />
                    </a>
                    <a href="{{ route('tping.pricing') }}"
                       class="px-6 py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-semibold rounded-xl transition-colors">
                        <x-bi th="ดูแพ็กเกจอื่น" en="View Other Packages" />
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
                text: 'tping://activate?key={{ $license->license_key }}',
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
