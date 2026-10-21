{{--
    A DGX Spark bundle order: the payment deadline, the hardware's delivery status and the shipping
    address. Included on the order page (orders.show) and the member area's order detail
    ($portal = true, which also links to the payment page while the order is unpaid).
--}}
@php
    $dgxMeta = \App\Support\DgxSparkCampaign::metadataOf($order);
    $dgxShip = $dgxMeta['shipping'] ?? [];
    $dgxCompany = $dgxMeta['company'] ?? null;
    $dgxFulfillment = \App\Support\DgxSparkCampaign::fulfillment($order);
    $dgxDeadline = \App\Support\DgxSparkCampaign::holdExpiresAt($order);
    $dgxExpired = \App\Support\DgxSparkCampaign::holdExpired($order);
    $dgxSteps = ['awaiting_payment', 'paid', 'ordered', 'shipped', 'delivered'];
    $dgxCurrent = array_search($dgxFulfillment['status'], $dgxSteps, true);
    $portal = $portal ?? false;
@endphp

<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 border border-amber-200 dark:border-amber-700/60">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">ชุดแคมเปญ NVIDIA DGX Spark + CluadeX &amp; BrainX ตลอดชีพ</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        License ทั้งสองตัวออกให้ทันทีเมื่อยืนยันยอดเงิน · เครื่องจัดส่งภายในประมาณ {{ \App\Support\DgxSparkCampaign::config('delivery_estimate') }} หลังยืนยันยอด
    </p>

    @if($dgxFulfillment['status'] === 'cancelled')
        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-700 dark:text-red-300">
            การจองนี้ถูกยกเลิกแล้ว{{ $order->payment_status === 'expired' ? ' (เลยเวลาชำระเงิน)' : '' }}
            — หากคุณโอนเงินไปแล้ว กรุณา <a href="{{ route('contact.show') }}" class="underline font-semibold">ติดต่อเรา</a> พร้อมเลขคำสั่งซื้อ #{{ $order->order_number }}
            · สั่งจองใหม่ได้ที่ <a href="{{ route('campaign.dgx-spark') }}" class="underline font-semibold">หน้าแคมเปญ</a>
        </div>
    @elseif($order->payment_status === 'pending' && ! $dgxExpired)
        <div class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4 text-sm text-amber-800 dark:text-amber-200">
            <b>โอนเงินและแนบสลิปภายใน {{ \App\Support\DgxSparkCampaign::thaiDateTime($dgxDeadline) }}</b>
            — ระบบกันชุดไว้ให้คุณถึงเวลานี้ หากเลยเวลา การจองจะถูกยกเลิกและชุดนี้กลับไปให้คนถัดไป
            @if($portal)
                <div class="mt-3">
                    <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-500 text-white font-semibold hover:bg-amber-600">ไปหน้าชำระเงิน / แนบสลิป</a>
                </div>
            @endif
        </div>
    @elseif($order->payment_status === 'pending' && $dgxExpired)
        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-700 dark:text-red-300">
            <b>เลยเวลาจองแล้ว ({{ \App\Support\DgxSparkCampaign::thaiDateTime($dgxDeadline) }})</b>
            — ถ้าโอนเงินแล้ว แนบสลิปได้ทันที เรายืนยันให้หากยังมีชุดว่าง หากชุดเต็มแล้วระบบจะแจ้งและเราคืนเงินเต็มจำนวน
            @if($portal)
                <div class="mt-3">
                    <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-red-500 text-white font-semibold hover:bg-red-600">ไปหน้าแนบสลิป</a>
                </div>
            @endif
        </div>
    @endif

    @if($dgxFulfillment['status'] !== 'cancelled')
        <ol class="mt-5 grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs">
            @foreach($dgxSteps as $i => $step)
                @php $done = $dgxCurrent !== false && $i <= $dgxCurrent; @endphp
                <li class="rounded-lg px-3 py-2 border {{ $done ? 'border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-200 font-semibold' : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400' }}">
                    {{ $i + 1 }}. {{ \App\Support\DgxSparkCampaign::FULFILLMENT_SHORT[$step] }}
                </li>
            @endforeach
        </ol>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">สถานะตอนนี้: <b>{{ $dgxFulfillment['label'] }}</b></p>
        @if($dgxFulfillment['tracking'])
            <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">เลขพัสดุ: <span class="font-mono font-semibold">{{ $dgxFulfillment['tracking'] }}</span></p>
        @endif
        @if($dgxFulfillment['note'])
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">หมายเหตุจากเรา: {{ $dgxFulfillment['note'] }}</p>
        @endif
    @endif

    <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div>
            <div class="text-gray-500 dark:text-gray-400 mb-1">ที่อยู่จัดส่ง</div>
            <div class="text-gray-900 dark:text-white whitespace-pre-line">{{ $dgxShip['name'] ?? $order->customer_name }} · {{ $dgxShip['phone'] ?? $order->customer_phone }}
{{ $order->customer_address }}</div>
        </div>
        @if($dgxCompany)
            <div>
                <div class="text-gray-500 dark:text-gray-400 mb-1">ออกเอกสารในนาม</div>
                <div class="text-gray-900 dark:text-white">
                    {{ $dgxCompany['name'] ?? '' }}<br>
                    เลขผู้เสียภาษี {{ $dgxCompany['tax_id'] ?? '-' }} ({{ $dgxCompany['branch'] ?? 'สำนักงานใหญ่' }})
                    @if(! empty($dgxCompany['address']))
                        <br><span class="whitespace-pre-line">{{ $dgxCompany['address'] }}</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
