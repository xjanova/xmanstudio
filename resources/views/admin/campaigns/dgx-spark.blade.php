@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แคมเปญ DGX Spark')
@section('page-title', 'แคมเปญ DGX Spark')

@section('content')
@php
    $card = 'rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
    $input = 'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent';
    $small = 'px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm';
    $Dgx = \App\Support\DgxSparkCampaign::class;
    $onlineSold = $availability['sold'] - $offlineSold;
    $stageTone = [
        'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'verifying' => 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
        'to_order' => 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-500/15 dark:text-fuchsia-300',
        'ordered' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/15 dark:text-indigo-300',
        'shipped' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-500/15 dark:text-cyan-300',
        'delivered' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'cancelled' => 'bg-gray-200 text-gray-700 dark:bg-gray-600/40 dark:text-gray-300',
    ];
    $nextStep = [
        'pending' => 'รอลูกค้าโอนเงินและแนบสลิป',
        'verifying' => 'ตรวจสลิปกับยอดเข้าบัญชี แล้วกดอนุมัติหรือปฏิเสธ',
        'to_order' => 'สั่งเครื่องจากผู้จัดจำหน่าย แล้วเปลี่ยนสถานะเป็น "สั่งเครื่องแล้ว"',
        'ordered' => 'เมื่อส่งของ ใส่เลขพัสดุแล้วเปลี่ยนเป็น "จัดส่งแล้ว"',
        'shipped' => 'เมื่อลูกค้าได้รับเครื่อง เปลี่ยนเป็น "ส่งมอบเรียบร้อย" และนัดติดตั้ง',
        'delivered' => 'เสร็จสิ้น',
        'cancelled' => '—',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แคมเปญ DGX Spark + CluadeX และ BrainX ตลอดชีพ</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                จัดการออเดอร์แคมเปญได้ครบในหน้านี้: ตรวจสลิป → อนุมัติ (ออก License ทั้งสองตัวทันที) → สั่งเครื่อง → จัดส่ง → ส่งมอบ ·
                ออเดอร์ใหม่แจ้งเตือนทาง LINE/Telegram เหมือนเดิม
            </p>
        </div>
        <a href="{{ route('campaign.dgx-spark') }}" target="_blank" rel="noopener"
           class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">ดูหน้าแคมเปญ ↗</a>
    </div>

    @if (session('success'))
        <div class="rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-900 dark:text-green-200 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif
    @if ($problems)
        <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-900 dark:text-amber-200 px-4 py-3 text-sm">
            <b>ยังรับคำสั่งซื้อไม่ได้:</b>
            <ul class="list-disc list-inside mt-1 space-y-0.5">
                @foreach ($problems as $p) <li>{{ $p }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- ══════════ ตัวเลขสรุป ══════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach ([
            ['ราคาชุดตอนนี้', $Dgx::baht($price), 'text-amber-600 dark:text-amber-400', null],
            ['ขายแล้ว', $availability['sold'] . ' ชุด', 'text-emerald-600 dark:text-emerald-400', 'ผ่านเว็บ ' . $onlineSold . ' · นอกเว็บ ' . $offlineSold],
            ['จองรอชำระ / รอตรวจสลิป', $availability['reserved'] . ' ชุด', 'text-blue-600 dark:text-blue-400', null],
            ['เหลือ', $availability['remaining'] . ' / ' . $availability['cap'] . ' ชุด', 'text-gray-900 dark:text-white', 'ตัวเลขที่ลูกค้าเห็นบนหน้าแคมเปญ'],
            ['ต้องดำเนินการ', $counts['action'] . ' ออเดอร์', $counts['action'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white', 'รอตรวจสลิป + รอสั่งเครื่อง'],
        ] as [$label, $value, $tone, $hint])
            <div class="{{ $card }} p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="text-2xl font-bold mt-1 {{ $tone }}">{{ $value }}</div>
                @if ($hint)<div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $hint }}</div>@endif
            </div>
        @endforeach
    </div>

    {{-- ══════════ ออเดอร์ ══════════ --}}
    <div id="orders" class="{{ $card }} overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">คำสั่งซื้อในแคมเปญ</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สั่งเครื่องจากผู้จัดจำหน่ายหลังยืนยันยอดเงินเข้าแล้วเท่านั้น · การจองที่ไม่ชำระภายใน {{ $Dgx::holdHours() }} ชม. ถูกยกเลิกอัตโนมัติ</p>
                </div>
                <form method="GET" action="{{ route('admin.campaigns.dgx-spark') }}" class="flex gap-2">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="search" name="q" value="{{ $search }}" placeholder="ค้นหาเลขออเดอร์ ชื่อ เบอร์ อีเมล" class="{{ $small }} w-64">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-gray-800 dark:bg-gray-600 text-white text-sm font-semibold">ค้นหา</button>
                </form>
            </div>
            <nav class="flex flex-wrap gap-2" aria-label="สถานะคำสั่งซื้อ">
                @foreach ($Dgx::ADMIN_TABS as $key => $label)
                    <a href="{{ route('admin.campaigns.dgx-spark', array_filter(['tab' => $key, 'q' => $search])) }}#orders"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border
                              {{ $tab === $key ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        {{ $label }}
                        <span class="px-1.5 rounded-full {{ $tab === $key ? 'bg-white/20' : ($key === 'action' && $counts[$key] > 0 ? 'bg-red-500 text-white' : 'bg-gray-100 dark:bg-gray-700') }}">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        @if ($orders->isEmpty())
            <p class="px-6 py-10 text-sm text-gray-500 dark:text-gray-400 text-center">
                {{ $counts['all'] === 0 && $search === '' ? 'ยังไม่มีคำสั่งซื้อ — เมื่อมีลูกค้าจอง ออเดอร์จะขึ้นที่นี่ และแจ้งเตือนทาง LINE/Telegram' : 'ไม่มีคำสั่งซื้อในหมวดนี้' }}
            </p>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($orders as $order)
                    @php
                        $stage = $stages[$order->id];
                        $f = $Dgx::fulfillment($order);
                        $meta = $Dgx::metadataOf($order);
                        $company = $meta['company'] ?? null;
                        $paid = $order->payment_status === 'paid' && $order->status !== 'cancelled';
                        $open = ! $paid && $stage !== 'cancelled';
                        $orderLicenses = $licenses->get($order->id, collect());
                        $orderRenewals = $renewals->get($order->id, collect());
                    @endphp
                    <article class="px-6 py-5 grid grid-cols-1 xl:grid-cols-3 gap-5" id="order-{{ $order->id }}">
                        {{-- ลูกค้า --}}
                        <div class="text-sm space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">#{{ $order->order_number }}</a>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $stageTone[$stage] ?? $stageTone['cancelled'] }}">{{ $Dgx::ADMIN_TABS[$stage] ?? $stage }}</span>
                            </div>
                            <div class="text-gray-900 dark:text-white font-medium">{{ $order->customer_name }}</div>
                            <div class="text-gray-600 dark:text-gray-300">
                                <a href="tel:{{ $order->customer_phone }}" class="hover:underline">{{ $order->customer_phone }}</a> ·
                                <a href="mailto:{{ $order->customer_email }}" class="hover:underline">{{ $order->customer_email }}</a>
                            </div>
                            <div class="text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $order->customer_address }}</div>
                            @if ($company)
                                <div class="mt-2 rounded-lg bg-gray-50 dark:bg-gray-700/40 px-3 py-2 text-xs text-gray-600 dark:text-gray-300">
                                    <b>ใบกำกับภาษีในนาม:</b> {{ $company['name'] ?? '' }} · เลขผู้เสียภาษี {{ $company['tax_id'] ?? '-' }} · {{ $company['branch'] ?? '' }}
                                    @if (! empty($company['address']))<div class="whitespace-pre-line">{{ $company['address'] }}</div>@endif
                                </div>
                            @endif
                            @if ($order->notes)
                                <details class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <summary class="cursor-pointer">หมายเหตุ</summary>
                                    <div class="whitespace-pre-line mt-1">{{ $order->notes }}</div>
                                </details>
                            @endif
                        </div>

                        {{-- การชำระเงิน --}}
                        <div class="text-sm space-y-2">
                            <div class="text-gray-700 dark:text-gray-200">
                                <b>{{ $Dgx::baht((float) $order->total) }}</b> · {{ $order->payment_method === 'promptpay' ? 'พร้อมเพย์' : 'โอนเงิน' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                จองเมื่อ {{ $Dgx::thaiDateTime($order->created_at) }}
                                @if ($order->payment_status === 'pending' && $order->status !== 'cancelled')
                                    · ต้องโอนภายใน {{ $Dgx::thaiDateTime($Dgx::holdExpiresAt($order)) }}
                                @endif
                                @if ($order->paid_at) · ชำระเมื่อ {{ $Dgx::thaiDateTime($order->paid_at) }} @endif
                            </div>

                            @if ($order->payment_slip)
                                @php
                                    // สลิปอยู่นอก web root — เปิดผ่าน route ที่ตรวจสิทธิ์เท่านั้น (ไม่มี /storage/ ให้ fallback แล้ว)
                                    $slipUrl = route('payment-slips.order', $order);
                                @endphp
                                <a href="{{ $slipUrl }}" target="_blank" rel="noopener" class="inline-block">
                                    <img src="{{ $slipUrl }}" alt="สลิปของ #{{ $order->order_number }}" loading="lazy"
                                         class="h-40 w-auto rounded-lg border border-gray-200 dark:border-gray-600 object-contain bg-gray-50">
                                </a>
                            @elseif ($open)
                                <p class="text-xs text-amber-700 dark:text-amber-300">ยังไม่มีสลิป</p>
                            @endif

                            @if ($open)
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <form method="POST" action="{{ route('admin.campaigns.dgx-spark.payment', $order) }}"
                                          onsubmit="return confirm('อนุมัติการชำระเงิน #{{ $order->order_number }} ยอด {{ $Dgx::baht((float) $order->total) }}?\nตรวจแล้วว่าเงินเข้าบัญชีจริง — ระบบจะออก License CluadeX และ BrainX ตลอดชีพให้ลูกค้าทันที');">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">อนุมัติการชำระเงิน</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.campaigns.dgx-spark.payment', $order) }}" class="flex gap-2"
                                          onsubmit="return confirm('ปฏิเสธ/ยกเลิก #{{ $order->order_number }}?\nออเดอร์จะถูกยกเลิกและชุดนี้กลับมาว่างให้คนถัดไป');">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="reject">
                                        <input type="text" name="note" maxlength="500" placeholder="เหตุผล (ไม่บังคับ)" class="{{ $small }} w-40">
                                        <button type="submit" class="px-4 py-2 rounded-lg border border-red-300 text-red-700 dark:text-red-300 dark:border-red-500/50 text-sm font-semibold hover:bg-red-50 dark:hover:bg-red-500/10">ปฏิเสธ/ยกเลิก</button>
                                    </form>
                                </div>
                            @endif

                            @if ($paid)
                                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-500/10 px-3 py-2 text-xs text-emerald-900 dark:text-emerald-200">
                                    <b>License ที่ออกให้:</b>
                                    @forelse ($orderLicenses as $license)
                                        <div>{{ $license->product?->name ?? 'License' }} · <span class="font-mono">{{ $license->license_key }}</span> · {{ $license->license_type }}</div>
                                    @empty
                                        @if ($orderRenewals->isEmpty())<div class="text-red-700 dark:text-red-300">ยังไม่พบ License — เปิดหน้าคำสั่งซื้อเพื่อตรวจสอบ</div>@endif
                                    @endforelse
                                    @foreach ($orderRenewals as $renewal)
                                        <div>{{ $renewal->licenseKey?->product?->name ?? 'License' }} · อัปเกรดคีย์เดิม <span class="font-mono">{{ $renewal->licenseKey?->license_key }}</span> เป็น {{ $renewal->license_type }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- เครื่อง / จัดส่ง --}}
                        <div class="text-sm space-y-2">
                            <div class="text-gray-700 dark:text-gray-200">สถานะเครื่อง: <b>{{ $f['label'] }}</b>
                                @if ($f['tracking']) · เลขพัสดุ <span class="font-mono">{{ $f['tracking'] }}</span> @endif
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ขั้นต่อไป: {{ $nextStep[$stage] ?? '—' }}</div>
                            @if ($paid)
                                <form method="POST" action="{{ route('admin.campaigns.dgx-spark.fulfillment', $order) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" class="{{ $small }}">
                                        @foreach (['paid', 'ordered', 'shipped', 'delivered'] as $s)
                                            <option value="{{ $s }}" @selected($f['status'] === $s)>{{ $Dgx::FULFILLMENT[$s] }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="tracking" maxlength="100" placeholder="เลขพัสดุ" value="{{ $f['tracking'] }}" class="{{ $small }} w-36">
                                    <input type="text" name="note" maxlength="500" placeholder="หมายเหตุถึงลูกค้า (ไม่บังคับ)" value="{{ $f['note'] }}" class="{{ $small }} flex-1 min-w-[10rem]">
                                    <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">อัปเดต</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="px-6 py-4">{{ $orders->fragment('orders')->links() }}</div>
        @endif
    </div>

    {{-- ══════════ จำนวนชุด ══════════ --}}
    <form method="POST" action="{{ route('admin.campaigns.dgx-spark.stock') }}" class="{{ $card }} p-6 space-y-4"
          x-data="{ offline: {{ (int) old('offline_sold', $offlineSold) }} }">
        @csrf
        @method('PUT')
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">จำนวนชุด</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ชุดที่ขายผ่านเว็บนับจากออเดอร์จริงอัตโนมัติ · ถ้าขายนอกเว็บ (โทร, LINE, หน้าร้าน) ใส่จำนวนที่นี่ —
                นับเป็น "ขายแล้ว" บนหน้าแคมเปญ และเว็บจะรับจองได้เฉพาะชุดที่เหลือ
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <label class="block">
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ขายนอกเว็บ (ชุด)</span>
                <input type="number" name="offline_sold" min="0" max="{{ $offlineMax }}" step="1" required x-model.number="offline" class="{{ $input }}">
            </label>
            <div class="md:col-span-2 rounded-xl bg-gray-50 dark:bg-gray-700/40 px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                หน้าแคมเปญจะแสดง:
                <b x-text="'เหลือ ' + Math.max(0, {{ $availability['cap'] }} - {{ $onlineSold + $availability['reserved'] }} - (offline || 0)) + ' จาก {{ $availability['cap'] }} ชุด'">เหลือ {{ $availability['remaining'] }} จาก {{ $availability['cap'] }} ชุด</b>
                <span class="text-gray-500 dark:text-gray-400">(ผ่านเว็บ: ขายแล้ว {{ $onlineSold }} · จอง {{ $availability['reserved'] }} · ใส่นอกเว็บได้สูงสุด {{ $offlineMax }})</span>
            </div>
        </div>
        <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">บันทึกจำนวนชุด</button>
    </form>

    {{-- ══════════ ราคา ══════════ --}}
    <form method="POST" action="{{ route('admin.campaigns.dgx-spark.update') }}" class="{{ $card }} p-6 space-y-5"
          x-data="{ ref: {{ (int) old('reference_price', $referencePrice) }}, markup: {{ (int) old('markup', $markup) }} }">
        @csrf
        @method('PUT')
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ราคาและการเปิดรับ</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                เมื่อ JIB เปลี่ยนราคา แก้แค่ "ราคา JIB" กับ "วันที่ตรวจราคา" — ราคาชุดตามไปเอง ·
                <a href="{{ $Dgx::config('reference.url') }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 underline">เปิดหน้าสินค้า JIB</a>
                · คำสั่งซื้อที่สร้างไปแล้วคงราคาเดิม
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="block">
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคา JIB (บาท, รวม VAT)</span>
                <input type="number" name="reference_price" min="1000" step="1" required x-model.number="ref" class="{{ $input }}">
            </label>
            <label class="block">
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่ตรวจราคา</span>
                <input type="date" name="reference_checked_at" required value="{{ old('reference_checked_at', $referenceDate->toDateString()) }}" class="{{ $input }}">
            </label>
            <label class="block">
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ส่วนต่าง (บาท)</span>
                <input type="number" name="markup" min="0" step="1" required x-model.number="markup" class="{{ $input }}">
            </label>
        </div>

        <div class="rounded-xl bg-gray-50 dark:bg-gray-700/40 px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
            ราคาชุดที่ลูกค้าจะเห็น:
            <b class="text-amber-600 dark:text-amber-400" x-text="'฿' + ((ref || 0) + (markup || 0)).toLocaleString('en-US')">{{ $Dgx::baht($price) }}</b>
            {{ $Dgx::priceIncludesVat() ? '(รวม VAT)' : '(+ VAT 7%)' }}
            · หน้าแคมเปญแสดง "ราคาอ้างอิง JIB" ตามวันที่ที่กรอก
        </div>

        <label class="flex items-center gap-3">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $enabled)) class="w-5 h-5 rounded border-gray-300 text-indigo-600">
            <span class="text-sm text-gray-700 dark:text-gray-200">เปิดรับคำสั่งซื้อ และแสดงแบนเนอร์บนหน้าแรก</span>
        </label>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">บันทึก</button>
    </form>
</div>
@endsection
