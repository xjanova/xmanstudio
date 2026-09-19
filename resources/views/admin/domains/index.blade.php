@extends($adminLayout ?? 'layouts.admin')

@section('title', 'โดเมน · ตั้งค่าและราคา')

@section('content')
@php
    $card = 'rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
    $input = 'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent';
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ระบบขายโดเมน</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            ราคาขายคำนวณจาก <span class="font-medium">ต้นทุนจริง × อัตราแลกเปลี่ยน × (1 + กำไร)</span> แล้วปัดขึ้น — ไม่มีราคาไหน hardcode ในโค้ด
        </p>
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

    {{-- ══════════ ตัวเลขสรุป ══════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $tiles = [
                ['label' => 'โดเมนใช้งานอยู่', 'value' => number_format($stats['active']), 'tone' => 'text-gray-900 dark:text-white'],
                ['label' => 'รายได้รวม', 'value' => number_format($stats['revenue'], 0) . ' ฿', 'tone' => 'text-gray-900 dark:text-white'],
                ['label' => 'กำไรรวม', 'value' => number_format($stats['profit'], 0) . ' ฿', 'tone' => 'text-emerald-600 dark:text-emerald-400'],
                ['label' => 'ใกล้หมดอายุ 30 วัน', 'value' => number_format($stats['expiring30']), 'tone' => $stats['expiring30'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white'],
            ];
        @endphp
        @foreach($tiles as $t)
            <div class="{{ $card }} p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t['label'] }}</p>
                <p class="text-2xl font-bold {{ $t['tone'] }}">{{ $t['value'] }}</p>
            </div>
        @endforeach
    </div>

    @if($stats['unsettled'] > 0 || $stats['refunded'] > 0)
        <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-5 py-4 text-sm text-amber-900 dark:text-amber-200">
            <span class="font-semibold">{{ $stats['unsettled'] }}</span> รายการค้างอยู่ ·
            <span class="font-semibold">{{ $stats['refunded'] }}</span> รายการคืนเงินไปแล้ว
            <span class="text-amber-700 dark:text-amber-300/80">— ตัวตามเก็บรันทุก 5 นาที (<code class="text-xs">domains:reconcile</code>)</span>
        </div>
    @endif

    {{-- ══════════ ตั้งค่าหลัก ══════════ --}}
    <form method="POST" action="{{ route('admin.domains.update') }}" class="{{ $card }} p-6">
        @csrf
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-5">การเชื่อมต่อและราคา</h2>

        <div class="grid lg:grid-cols-2 gap-5">
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    API token ของผู้ให้บริการ
                    @if($hasToken)
                        <span class="ml-2 px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-300 text-xs">ตั้งค่าแล้ว</span>
                    @else
                        <span class="ml-2 px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300 text-xs">ยังไม่ได้ตั้ง</span>
                    @endif
                </label>
                {{-- value="" เสมอ + placeholder เป็นจุดไข่ปลา ถ้าเอาจุดไข่ปลาไปใส่
                     ใน value การกดบันทึกครั้งถัดไปจะทับ token จริงด้วยคำว่า "••••" --}}
                <input type="password" name="hostinger_api_token" value="" autocomplete="off"
                       placeholder="{{ $hasToken ? '••••••••••••••••  (มี token แล้ว — เว้นว่างเพื่อใช้ค่าเดิม)' : 'วาง token ที่สร้างจากหน้า API ของผู้ให้บริการ' }}"
                       class="{{ $input }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                    เก็บแบบเข้ารหัสในฐานข้อมูล · token นี้<span class="font-semibold">ใช้เงินได้จริง</span> — สั่งซื้อจะตัดบัตรที่ผูกไว้กับบัญชีผู้ให้บริการ
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">กำไรเริ่มต้น (%)</label>
                <input type="number" name="domain_margin_percent" value="{{ old('domain_margin_percent', $margin) }}"
                       step="0.5" min="0" max="500" class="{{ $input }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">ใช้กับทุกนามสกุลที่ไม่ได้ตั้งค่าเฉพาะไว้</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">อัตราแลกเปลี่ยน USD → THB</label>
                <input type="number" name="domain_usd_thb_rate" value="{{ old('domain_usd_thb_rate', $fxRate) }}"
                       step="0.01" min="1" max="200" class="{{ $input }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                    ตั้งเผื่อไว้สูงกว่าอัตรากลางเล็กน้อย — ค่าธรรมเนียมบัตรและค่าเงินผันผวนกินกำไรตรงนี้
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ปัดราคาขึ้นเป็นหลัก</label>
                <select name="domain_price_rounding" class="{{ $input }}">
                    @foreach([1 => '1 บาท', 5 => '5 บาท', 10 => '10 บาท', 50 => '50 บาท', 100 => '100 บาท'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('domain_price_rounding', $rounding) == $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <label class="flex items-center gap-2.5 cursor-pointer pb-2.5">
                    <input type="checkbox" name="domain_sales_enabled" value="1" @checked(old('domain_sales_enabled', $salesEnabled))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">เปิดขายโดเมน</span>
                </label>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mt-6 pt-5 border-t border-gray-200 dark:border-gray-700">
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm transition">
                บันทึกการตั้งค่า
            </button>
            <button type="submit" formaction="{{ route('admin.domains.test') }}" formnovalidate
                    class="px-6 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-medium text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                ทดสอบการเชื่อมต่อ
            </button>
        </div>
    </form>

    {{-- ══════════ คำสั่งที่ต้องรันเอง ══════════ --}}
    <div class="{{ $card }} p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ดึงราคาจริงจากผู้ให้บริการ</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            ตารางข้างล่างยังเป็น<span class="font-semibold">ราคาตั้งต้นโดยประมาณ</span>จนกว่าจะรันคำสั่งนี้ครั้งแรก
            นามสกุลที่ยังไม่มี item id จะ<span class="font-semibold">ขายไม่ได้</span> (ระบบปฏิเสธการสั่งซื้อ) ซึ่งเป็นพฤติกรรมที่ตั้งใจ — ดีกว่าขายในราคาที่กุขึ้นเอง
        </p>
        <form method="POST" action="{{ route('admin.domains.sync') }}" class="flex flex-wrap items-center gap-3">
            @csrf
            <button type="submit" name="dry" value="1"
                    class="px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-semibold text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                ดูก่อนว่าจะเปลี่ยนอะไร
            </button>
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition">
                ดึงราคาล่าสุดเลย
            </button>
            <span class="text-xs text-gray-500 dark:text-gray-400">ใช้เวลาไม่กี่วินาที</span>
        </form>

        @if (session('sync_output'))
            <pre class="mt-4 bg-gray-900 text-gray-100 rounded-lg px-4 py-3 text-xs overflow-x-auto whitespace-pre-wrap">{{ session('sync_output') }}</pre>
        @endif

        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
            ระบบดึงให้เองทุกวันตี 4 อยู่แล้ว · นามสกุลใหม่ที่เจอจะถูกปิดไว้ก่อน ต้องมาเปิดเองในตารางล่าง
            · รันจากเครื่องก็ได้ที่ <code class="font-mono">php artisan domains:sync-catalogue</code>
        </p>
    </div>

    {{-- ══════════ ตารางนามสกุล ══════════ --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">นามสกุลโดเมน ({{ $tlds->count() }})</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เว้นช่องกำไรไว้ = ใช้ค่าเริ่มต้น {{ $margin }}%</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">นามสกุล</th>
                        <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">ต้นทุน</th>
                        <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">ขาย</th>
                        <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">ต่ออายุ</th>
                        <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">กำไร/ชิ้น</th>
                        <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 w-72">ตั้งค่า</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @foreach($tlds as $t)
                        @php
                            $sell = $t->registerPriceThb();
                            $costThb = \App\Support\DomainPricing::costThb($t->cost_usd_cents);
                            $unitProfit = $sell - $costThb;
                            $sellable = (bool) $t->item_id_register;
                        @endphp
                        <tr class="{{ $t->is_active ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900 dark:text-white">.{{ $t->tld }}</span>
                                    @unless($sellable)
                                        <span class="px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300 text-[11px] font-semibold" title="ยังไม่มี item id — ขายไม่ได้จนกว่าจะ sync">ยังขายไม่ได้</span>
                                    @endunless
                                    @if($t->is_featured)
                                        <span class="px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-800 dark:text-indigo-300 text-[11px]">เด่น</span>
                                    @endif
                                </div>
                                @if($t->synced_at)
                                    <p class="text-[11px] text-gray-400 mt-0.5">sync {{ $t->synced_at->diffForHumans() }}</p>
                                @else
                                    <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">ราคาตั้งต้น ยังไม่ sync</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                ${{ number_format($t->cost_usd_cents / 100, 2) }}
                                <span class="block text-[11px] text-gray-400">{{ number_format($costThb, 0) }} ฿</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ number_format($sell, 0) }} ฿</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap {{ $t->renewalIsDearer() ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400' }}">
                                {{ number_format($t->renewPriceThb(), 0) }} ฿
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-medium {{ $unitProfit > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ number_format($unitProfit, 0) }} ฿
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.domains.tld', $t->id) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="number" name="margin_percent" value="{{ $t->margin_percent }}" step="0.5" min="0" max="500"
                                           placeholder="{{ $margin }}" title="กำไรเฉพาะนามสกุลนี้ (%)"
                                           class="w-20 px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <input type="number" name="sort_order" value="{{ $t->sort_order }}" min="0" max="9999" title="ลำดับ"
                                           class="w-16 px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400" title="เปิดขาย">
                                        <input type="checkbox" name="is_active" value="1" @checked($t->is_active) class="rounded border-gray-300 text-indigo-600">ขาย
                                    </label>
                                    <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400" title="แสดงเป็นนามสกุลเด่น">
                                        <input type="checkbox" name="is_featured" value="1" @checked($t->is_featured) class="rounded border-gray-300 text-indigo-600">เด่น
                                    </label>
                                    <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400" title="ค้นหาให้อัตโนมัติเวลาลูกค้าพิมพ์ชื่อเปล่า">
                                        <input type="checkbox" name="search_by_default" value="1" @checked($t->search_by_default) class="rounded border-gray-300 text-indigo-600">ค้น
                                    </label>
                                    <button type="submit" class="px-3 py-1 rounded bg-gray-800 dark:bg-gray-600 text-white text-xs font-medium hover:bg-gray-700 transition">บันทึก</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ══════════ ออเดอร์ค้าง ══════════ --}}
    @if($unsettled->isNotEmpty())
        <div class="{{ $card }} overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">ออเดอร์ที่ยังไม่จบ ({{ $unsettled->count() }})</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    ลูกค้าจ่ายเงินแล้วแต่โดเมนยังไม่พร้อม · ระบบตามเก็บเองทุก 5 นาที และคืนเงินอัตโนมัติถ้าเกิน {{ \App\Services\DomainRegistrarService::SETTLE_TIMEOUT_MINUTES }} นาที
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">โดเมน</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">ลูกค้า</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">สถานะ</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">ยอด</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">รอมาแล้ว</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach($unsettled as $r)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $r->domain }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $r->user?->email ?? '—' }}</td>
                                <td class="px-4 py-3"><code class="text-xs">{{ $r->status }}</code></td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($r->price_thb, 0) }} ฿</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $r->created_at->diffForHumans(null, true) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
