@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'ชำระด้วยเหรียญ TPIX')

@section('content')
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 p-8 mb-8 shadow-xl">
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">ชำระด้วยเหรียญ TPIX</h1>
            <p class="text-amber-50 mt-1">TPIX Chain (chain id {{ config('tpix_pay.chain_id') }}) · ราคาจากพูล TPIX DEX (WTPIX/USDT) × อัตรา USD/THB สด</p>
        </div>
        <div class="text-right text-white">
            @if($spot)
                <p class="text-3xl font-extrabold">1 TPIX = ฿{{ \App\Services\TpixPay\TpixPriceService::formatRate($spot['price_thb']) }}</p>
                <p class="text-sm text-amber-50">${{ \App\Services\TpixPay\TpixMath::format($spot['price_usd'], 6) }} · เฉลี่ย 30 นาที {{ $spot['twap_usd'] ? '$' . \App\Services\TpixPay\TpixMath::format($spot['twap_usd'], 6) : 'ยังไม่พอ' }} · USD/THB {{ $spot['usd_thb'] }}{{ $spot['fx_live'] ? '' : ' (สำรอง)' }}</p>
                <p class="text-sm text-amber-50">ใบเสนอราคาใช้ {{ $spot['quote_rate_thb'] ? '฿' . \App\Services\TpixPay\TpixPriceService::formatRate($spot['quote_rate_thb']) : '— (รอค่าเฉลี่ยครบ ยังไม่รับชำระ)' }} · สูงสุด ฿{{ number_format((float) $spot['max_quote_thb'], 2) }}/รายการ</p>
                <p class="text-xs text-amber-100">พูล: {{ \App\Services\TpixPay\TpixMath::format($spot['reserve_tpix'], 2) }} TPIX / {{ \App\Services\TpixPay\TpixMath::format($spot['reserve_usdt'], 2) }} USDT</p>
            @else
                <p class="text-xl font-bold">ราคาไม่พร้อมใช้งาน</p>
                <p class="text-sm text-amber-50">ไม่มีราคาที่เชื่อถือได้ (เชนหรืออัตราแลกเปลี่ยนไม่ตอบ / พูลบางเกินไป) — ระบบซ่อนราคา TPIX และไม่รับชำระชั่วคราว</p>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
    <div class="mb-6 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-6 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mb-6 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
    {{-- Settings --}}
    <form action="{{ route('admin.tpix-payments.settings.update') }}" method="POST" class="xl:col-span-2 rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 p-6 space-y-5">
        @csrf
        @method('PUT')
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ตั้งค่า</h2>

        <label class="flex items-center gap-3">
            <input type="hidden" name="tpix_enabled" value="0">
            <input type="checkbox" name="tpix_enabled" value="1" class="w-5 h-5 rounded text-amber-600 focus:ring-amber-500" {{ old('tpix_enabled', $settings['enabled']) ? 'checked' : '' }}>
            <span class="text-gray-800 dark:text-gray-200 font-medium">เปิดรับชำระด้วยเหรียญ TPIX</span>
        </label>

        <label class="flex items-center gap-3">
            <input type="hidden" name="tpix_show_prices" value="0">
            <input type="checkbox" name="tpix_show_prices" value="1" class="w-5 h-5 rounded text-amber-600 focus:ring-amber-500" {{ old('tpix_show_prices', $settings['show_prices']) ? 'checked' : '' }}>
            <span class="text-gray-800 dark:text-gray-200 font-medium">แสดงราคาเป็น TPIX บนหน้าสินค้าทุกหน้า</span>
        </label>
        <p class="-mt-3 text-xs text-gray-500 dark:text-gray-400">แยกจากการรับชำระ — แสดงราคาได้แม้ยังไม่เปิดรับชำระ (ราคาจากพูล TPIX DEX ซ่อนเองเมื่อไม่มีราคาที่เชื่อถือได้)</p>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ที่อยู่กระเป๋ารับเหรียญ (TPIX Chain)</label>
            <input type="text" name="tpix_receive_address" value="{{ old('tpix_receive_address', $settings['address']) }}" placeholder="0x…" spellcheck="false" autocomplete="off"
                   class="w-full px-4 py-3 rounded-xl font-mono text-sm border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">0x ตามด้วย 40 ตัวอักษร (ตัวพิมพ์เล็ก/ใหญ่แบบ EIP-55 ได้ทั้งคู่) — ต้องเป็นกระเป๋าที่ร้านถือกุญแจเอง ลูกค้าโอนเข้าที่อยู่นี้โดยตรง</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">บล็อกยืนยันก่อนส่งของ</label>
                <input type="number" name="tpix_confirmations" min="1" max="64" value="{{ old('tpix_confirmations', $settings['confirmations']) }}"
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">บล็อกละ ~2 วินาที — 3 บล็อก ≈ 6 วินาที</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อายุยอดชำระ (นาที)</label>
                <input type="number" name="tpix_quote_ttl_minutes" min="5" max="240" value="{{ old('tpix_quote_ttl_minutes', $settings['ttl']) }}"
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">โอนหลังหมดเวลาจะพักเป็น "late" ให้แอดมินตัดสิน (ระบบยังมองหาอีก {{ config('tpix_pay.late_grace_minutes') }} นาที)</p>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ยอดต่อรายการสูงสุด (เท่าของ USDT ในพูล)</label>
                <input type="number" name="tpix_max_pool_ratio" min="0.1" max="100" step="0.1" value="{{ old('tpix_max_pool_ratio', $settings['max_pool_ratio']) }}"
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">พูลตื้นมาก (USDT ไม่กี่ตัวก็ดันราคาได้) — ยอดชำระหนึ่งรายการต้องไม่เกินค่านี้ × USDT ในพูล × USD/THB</p>
            </div>
        </div>

        <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold shadow-lg hover:from-amber-600 hover:to-orange-600 transition">บันทึก</button>
    </form>

    {{-- How it works --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 p-6 text-sm text-gray-600 dark:text-gray-300 space-y-2">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ทำงานอย่างไร</h2>
        <p>• ยอดชำระ = ยอดบาท ÷ ราคา (ใช้ราคาที่ต่ำกว่าระหว่างราคาสดกับค่าเฉลี่ย 30 นาที กันคนปั่นพูล) ปัดขึ้น 4 ตำแหน่ง + เศษท้ายเฉพาะรายการ</p>
        <p>• ระบบตรวจกับเชนเอง: โอนเข้าที่อยู่ร้าน · chain 4289 · ยอดตรงทุก wei · สำเร็จ · ยืนยันครบ · อยู่ในเวลา</p>
        <p>• ธุรกรรมหนึ่งใช้ได้กับรายการเดียว · คำสั่งซื้อที่ชำระ/ยกเลิกไปแล้ว → เงินเข้า Wallet ลูกค้า</p>
        <p>• สแกนบล็อกทุกนาที (<code class="text-xs">tpix:pay-scan</code>) · บล็อกล่าสุดที่ตรวจ: {{ \App\Services\TpixPay\TpixPaymentService::scanCursor() ?? '—' }} · snapshot ล่าสุด: {{ $lastSnapshot?->created_at?->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') ?? '—' }}</p>
        <p>• เหรียญที่เข้ามานอกขั้นตอนปกติ (คำสั่งซื้อปิดไปแล้ว) เข้า Wallet ตามมูลค่าเหรียญตอนนี้ ไม่เกินยอดบาท และไม่เกิน ฿{{ number_format((float) config('tpix_pay.auto_credit_daily_cap_thb'), 0) }}/คน/วัน — เกินนั้นรอแอดมินอนุมัติ</p>
        <p>• สินค้าตั้ง "ราคา TPIX คงที่" ได้ที่หน้าแก้ไขสินค้า</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">สถานะ</label>
        <select name="status" class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
            <option value="">ทั้งหมด</option>
            @foreach(\App\Models\TpixPayment::STATUSES as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ (new \App\Models\TpixPayment(['status' => $s]))->statusLabel() }} ({{ $counts[$s] ?? 0 }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">สำหรับ</label>
        <select name="purpose" class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
            <option value="">ทั้งหมด</option>
            <option value="order" @selected(request('purpose') === 'order')>คำสั่งซื้อ</option>
            <option value="wallet_topup" @selected(request('purpose') === 'wallet_topup')>เติมเงิน Wallet</option>
        </select>
    </div>
    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ค้นหา (เลขรายการ / tx hash / ที่อยู่ผู้โอน / อีเมล)</label>
        <input type="text" name="q" value="{{ request('q') }}" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
    </div>
    <button type="submit" class="px-4 py-2 rounded-lg bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold">กรอง</button>
</form>

<div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">รายการ</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">ลูกค้า</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-300">ยอด</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">สถานะ</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">ธุรกรรม</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-300">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($payments as $payment)
                    @php
                        $badge = match ($payment->status) {
                            'paid', 'credited_wallet' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                            'confirming' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                            'late', 'held' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                            'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                            default => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                        };
                    @endphp
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <p class="font-mono text-xs text-gray-900 dark:text-white">{{ $payment->reference }}</p>
                            <p class="text-gray-600 dark:text-gray-300">
                                @if($payment->payable instanceof \App\Models\Order)
                                    <a href="{{ route('admin.orders.show', $payment->payable) }}" class="text-amber-600 dark:text-amber-400 hover:underline">{{ $payment->purposeLabel() }}</a>
                                @else
                                    {{ $payment->purposeLabel() }}
                                @endif
                            </p>
                            <p class="text-xs text-gray-400">{{ $payment->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') }} · หมดเวลา {{ $payment->expires_at?->copy()->timezone('Asia/Bangkok')->format('H:i') }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $payment->user?->name }}<br><span class="text-xs text-gray-400">{{ $payment->user?->email }}</span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <p class="font-semibold text-gray-900 dark:text-white">฿{{ number_format((float) $payment->amount_thb, 2) }}</p>
                            <p class="font-mono text-xs text-amber-600 dark:text-amber-400">{{ $payment->tpixDisplay() }} TPIX</p>
                            <p class="text-xs text-gray-400">@ ฿{{ \App\Services\TpixPay\TpixPriceService::formatRate((string) $payment->rate_thb) }}{{ ($payment->meta['fixed_price'] ?? false) ? ' (คงที่)' : '' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ $payment->statusLabel() }}</span>
                            @if($payment->status === 'confirming')
                                <p class="text-xs text-gray-400 mt-1">{{ $payment->confirmations }} / {{ $settings['confirmations'] }} บล็อก</p>
                            @endif
                            @if($payment->failure_reason)
                                <p class="text-xs text-red-500 mt-1 max-w-[220px] break-words">{{ $payment->failure_reason }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($payment->tx_hash)
                                <a href="{{ $payment->explorerTxUrl() }}" target="_blank" rel="noopener" class="font-mono text-amber-600 dark:text-amber-400 hover:underline">{{ \Illuminate\Support\Str::limit($payment->tx_hash, 18) }}</a>
                                <p class="font-mono text-gray-400">จาก {{ \Illuminate\Support\Str::limit((string) $payment->from_address, 14) }}</p>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap space-y-1">
                            @unless($payment->isSettled())
                                <form method="POST" action="{{ route('admin.tpix-payments.recheck', $payment) }}" class="inline">
                                    @csrf
                                    <button class="px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-medium hover:bg-gray-200 dark:hover:bg-gray-600">ตรวจซ้ำบนเชน</button>
                                </form>
                            @endunless
                            @if($payment->status === 'late')
                                <form method="POST" action="{{ route('admin.tpix-payments.accept-late', $payment) }}" class="inline" onsubmit="return confirm('รับเงินที่โอนหลังหมดเวลา? คำสั่งซื้อที่ยังรอชำระจะถูกส่งของ ไม่เช่นนั้นเงินเข้า Wallet ลูกค้า')">
                                    @csrf
                                    <button class="px-3 py-1.5 rounded-lg bg-amber-500 text-white text-xs font-medium hover:bg-amber-600">รับเงิน (late)</button>
                                </form>
                            @endif
                            @if($payment->status === 'held')
                                <form method="POST" action="{{ route('admin.tpix-payments.approve-held', $payment) }}" class="inline" onsubmit="return confirm('เติมมูลค่าเหรียญตอนนี้ (ไม่เกินยอดบาทของรายการ) เข้า Wallet ลูกค้า?')">
                                    @csrf
                                    <button class="px-3 py-1.5 rounded-lg bg-amber-500 text-white text-xs font-medium hover:bg-amber-600">อนุมัติเครดิต</button>
                                </form>
                            @endif
                            @if($payment->credited_thb)
                                <p class="text-xs text-gray-500 dark:text-gray-400">เข้า Wallet ฿{{ number_format((float) $payment->credited_thb, 2) }}</p>
                            @endif
                            @if(in_array($payment->status, ['pending', 'expired'], true))
                                <form method="POST" action="{{ route('admin.tpix-payments.cancel', $payment) }}" class="inline" onsubmit="return confirm('ยกเลิกยอดชำระนี้? ถ้าลูกค้าโอนมาภายหลัง จะพักเป็น late ให้ตัดสิน')">
                                    @csrf
                                    <button class="px-3 py-1.5 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs font-medium hover:bg-red-100">ยกเลิก</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">ยังไม่มีรายการชำระด้วย TPIX</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $payments->links() }}</div>
@endsection
