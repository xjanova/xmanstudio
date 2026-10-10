@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แคมเปญ DGX Spark')
@section('page-title', 'แคมเปญ DGX Spark')

@section('content')
@php
    $card = 'rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
    $input = 'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent';
    $Dgx = \App\Support\DgxSparkCampaign::class;
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แคมเปญ DGX Spark + CluadeX &amp; BrainX ตลอดชีพ</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ราคาชุด = <span class="font-medium">ราคา JIB + ส่วนต่าง</span> · ชุดที่เหลือนับจากออเดอร์จริง ·
                อนุมัติการชำระเงินที่หน้าคำสั่งซื้อหรือปุ่มใน Telegram ตามปกติ — อนุมัติแล้ว License ทั้งสองตัวออกให้ลูกค้าทันที
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
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([
            ['ราคาชุดตอนนี้', $Dgx::baht($price), 'text-amber-600 dark:text-amber-400'],
            ['ชำระแล้ว', $availability['sold'] . ' ชุด', 'text-emerald-600 dark:text-emerald-400'],
            ['จองรอชำระ / รอตรวจสลิป', $availability['reserved'] . ' ชุด', 'text-blue-600 dark:text-blue-400'],
            ['เหลือ', $availability['remaining'] . ' / ' . $availability['cap'] . ' ชุด', 'text-gray-900 dark:text-white'],
        ] as [$label, $value, $tone])
            <div class="{{ $card }} p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="text-2xl font-bold mt-1 {{ $tone }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    {{-- ══════════ ราคา ══════════ --}}
    <form method="POST" action="{{ route('admin.campaigns.dgx-spark.update') }}" class="{{ $card }} p-6 space-y-5"
          x-data="{ ref: {{ (int) old('reference_price', $referencePrice) }}, markup: {{ (int) old('markup', $markup) }} }">
        @csrf
        @method('PUT')
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ราคา</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                เมื่อ JIB เปลี่ยนราคา แก้แค่ "ราคา JIB" กับ "วันที่ตรวจราคา" — ราคาชุดตามไปเอง ·
                <a href="{{ \App\Support\DgxSparkCampaign::config('reference.url') }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 underline">เปิดหน้าสินค้า JIB</a>
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
            {{ \App\Support\DgxSparkCampaign::priceIncludesVat() ? '(รวม VAT)' : '(+ VAT 7%)' }}
            · หน้าแคมเปญแสดง "ราคาอ้างอิง JIB" ตามวันที่ที่กรอก
        </div>

        <label class="flex items-center gap-3">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $enabled)) class="w-5 h-5 rounded border-gray-300 text-indigo-600">
            <span class="text-sm text-gray-700 dark:text-gray-200">เปิดรับคำสั่งซื้อ และแสดงแบนเนอร์บนหน้าแรก</span>
        </label>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">บันทึก</button>
    </form>

    {{-- ══════════ ออเดอร์ ══════════ --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">คำสั่งซื้อในแคมเปญ</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">สั่งเครื่องจากผู้จัดจำหน่ายหลังยืนยันยอดเงินเข้าแล้วเท่านั้น · การจองที่เลยเวลา {{ \App\Support\DgxSparkCampaign::holdHours() }} ชม. ถูกยกเลิกอัตโนมัติ</p>
        </div>

        @if ($orders->isEmpty())
            <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400 text-center">ยังไม่มีคำสั่งซื้อ</p>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($orders as $order)
                    @php
                        $f = \App\Support\DgxSparkCampaign::fulfillment($order);
                        $paid = $order->payment_status === 'paid' && $order->status !== 'cancelled';
                    @endphp
                    <div class="px-6 py-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="text-sm">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">#{{ $order->order_number }}</a>
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $paid ? 'bg-emerald-100 text-emerald-800' : ($f['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ $order->payment_status }}
                            </span>
                            <div class="mt-1 text-gray-700 dark:text-gray-300">{{ $order->customer_name }} · {{ $order->customer_phone }} · {{ $order->customer_email }}</div>
                            <div class="text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $order->customer_address }}</div>
                            <div class="mt-1 text-gray-500 dark:text-gray-400">
                                ฿{{ number_format((float) $order->total, 2) }} · {{ $order->payment_method === 'promptpay' ? 'พร้อมเพย์' : 'โอนเงิน' }}
                                · สร้าง {{ \App\Support\DgxSparkCampaign::thaiDateTime($order->created_at) }}
                                @if ($order->payment_status === 'pending' && $order->status !== 'cancelled')
                                    · ต้องโอนภายใน {{ \App\Support\DgxSparkCampaign::thaiDateTime(\App\Support\DgxSparkCampaign::holdExpiresAt($order)) }}
                                @endif
                            </div>
                        </div>
                        <div class="text-sm">
                            <div class="text-gray-700 dark:text-gray-200">สถานะเครื่อง: <b>{{ $f['label'] }}</b>
                                @if ($f['tracking']) · เลขพัสดุ <span class="font-mono">{{ $f['tracking'] }}</span> @endif
                            </div>
                            @if ($paid)
                                <form method="POST" action="{{ route('admin.campaigns.dgx-spark.fulfillment', $order) }}" class="mt-2 flex flex-wrap items-end gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                        @foreach (['paid', 'ordered', 'shipped', 'delivered'] as $s)
                                            <option value="{{ $s }}" @selected($f['status'] === $s)>{{ \App\Support\DgxSparkCampaign::FULFILLMENT[$s] }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="tracking" maxlength="100" placeholder="เลขพัสดุ" value="{{ $f['tracking'] }}"
                                           class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm w-40">
                                    <input type="text" name="note" maxlength="500" placeholder="หมายเหตุถึงลูกค้า (ไม่บังคับ)" value="{{ $f['note'] }}"
                                           class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm flex-1 min-w-[10rem]">
                                    <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">อัปเดต</button>
                                </form>
                            @elseif ($f['status'] !== 'cancelled')
                                <p class="mt-1 text-amber-700 dark:text-amber-300">รอยืนยันยอดเงิน — ตรวจสลิปและอนุมัติที่ <a href="{{ route('admin.orders.show', $order) }}" class="underline">หน้าคำสั่งซื้อ</a> ก่อนสั่งเครื่อง</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="px-6 py-4">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
