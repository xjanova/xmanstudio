@extends($adminLayout ?? 'layouts.admin')

@section('title', 'เงื่อนไขราคาใบเสนอราคา')
@section('page-title', 'เงื่อนไขราคาใบเสนอราคา')

@section('content')
@php
    // ค่าที่โชว์ในฟอร์มต้องเป็นค่าที่ระบบใช้จริง ไม่ใช่ค่าที่ยังไม่ผ่านการตรวจ
    // ถ้าเพิ่ง validate ไม่ผ่าน ให้เอาของที่พิมพ์มาแสดงกลับ
    $tierRows = old('tiers') ?: array_map(fn ($t) => ['from' => $t['from'], 'percent' => $t['percent']], $tiers);
    $splitRows = old('split') ?: array_map(fn ($s) => ['percent' => $s['percent'], 'label' => $s['label']], $split);
    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
@endphp
<div class="space-y-6">

    {{-- หัวเรื่อง --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 p-8 shadow-2xl">
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">เงื่อนไขราคา</h1>
                <p class="text-blue-100 text-lg">ส่วนลดตามยอด · ค่าเร่งงาน · อายุใบเสนอราคา · งวดการชำระเงิน</p>
            </div>
            <div class="hidden md:block">
                <div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl shadow-lg font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow-lg">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 px-5 py-4 text-sm text-blue-900 dark:text-blue-200">
        ค่าที่ตั้งไว้นี้ใช้พร้อมกันทั้ง <strong>หน้าสั่งงาน</strong> (ตัวเลขที่ลูกค้าเห็นตอนเลือก),
        <strong>ใบเสนอราคา PDF</strong> และ <strong>ใบแจ้งหนี้แต่ละงวด</strong> ที่ออกตอนลูกค้าตอบรับ
        — แก้ที่เดียว เปลี่ยนทั้งสามที่ ไม่ต้องรอ deploy
    </div>

    <form method="POST" action="{{ route('admin.quotations.pricing.update') }}" class="space-y-6"
          x-data="pricingForm({{ Illuminate\Support\Js::from($splitRows) }})">
        @csrf
        @method('PUT')

        {{-- ส่วนลดตามยอด --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">ส่วนลดตามยอด</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    ยอดก่อน VAT ถึงเท่าไรได้ลดกี่เปอร์เซ็นต์ — หน้าสั่งงานจะบอกลูกค้าเองว่า “อีก x บาท จะได้ส่วนลด y%”
                    ลบขั้นไหนออกให้ล้างช่องนั้นให้ว่าง
                </p>
            </div>
            <div class="space-y-3">
                @for ($i = 0; $i < 6; $i++)
                    @php $row = $tierRows[$i] ?? ['from' => '', 'percent' => '']; @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr] gap-3 items-center">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ยอดตั้งแต่ (บาท)</label>
                            <input type="number" min="0" step="1000" name="tiers[{{ $i }}][from]"
                                   value="{{ $row['from'] !== '' ? (int) $row['from'] : '' }}"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white tabular-nums focus:ring-2 focus:ring-blue-500"
                                   placeholder="200000">
                        </div>
                        <div class="hidden sm:block text-gray-400 pt-5">→</div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ส่วนลด (%)</label>
                            <input type="number" min="0" max="99" step="0.5" name="tiers[{{ $i }}][percent]"
                                   value="{{ $row['percent'] !== '' ? $num($row['percent']) : '' }}"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white tabular-nums focus:ring-2 focus:ring-blue-500"
                                   placeholder="5">
                        </div>
                    </div>
                @endfor
            </div>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                ค่าเริ่มต้นของระบบ:
                @foreach ($defaults['tiers'] as $d)
                    {{ number_format($d['from']) }} บาท ลด {{ $d['percent'] }}%{{ ! $loop->last ? ' · ' : '' }}
                @endforeach
                — ถ้าล้างทุกช่องจนว่างหมด ระบบจะกลับไปใช้ค่าเริ่มต้นนี้
            </p>
        </div>

        {{-- ค่าเร่ง + อายุเอกสาร --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ค่าเร่งงานและอายุเอกสาร</h2>
            <div class="grid md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ค่าเร่งงาน (%)</label>
                    <input type="number" min="0" max="200" step="0.5" name="rush_percent"
                           value="{{ old('rush_percent', $num($rushPercent)) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white tabular-nums focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">คิดจากยอดหลังหักส่วนลด · ใส่ 0 = ปิดค่าเร่ง (ตัวเลือก “เร่งด่วน” ยังอยู่ แต่ไม่คิดเพิ่ม)</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">อายุใบเสนอราคา (วัน)</label>
                    <input type="number" min="1" max="365" name="valid_days"
                           value="{{ old('valid_days', $validDays) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white tabular-nums focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">นับจากวันที่ออกเอกสาร · เตือนลูกค้าอัตโนมัติก่อนหมดอายุ 3 วัน</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">กำหนดชำระงวดแรก (วัน)</label>
                    <input type="number" min="0" max="180" name="due_days"
                           value="{{ old('due_days', $dueDays) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white tabular-nums focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">นับจากวันที่ลูกค้าตอบรับ · ใช้เป็นวันครบกำหนดบนใบแจ้งหนี้งวดแรก</p>
                </div>
            </div>
        </div>

        {{-- งวดชำระ --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">งวดการชำระเงิน</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        พิมพ์ลงใบเสนอราคา และใช้ออกใบแจ้งหนี้ทีละงวดตอนลูกค้าตอบรับ · รวมกันต้องได้ 100%
                    </p>
                </div>
                <div class="text-sm font-bold px-3 py-1.5 rounded-lg tabular-nums"
                     :class="ok ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300'">
                    รวม <span x-text="totalLabel"></span>%
                </div>
            </div>

            <div class="space-y-3">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="grid grid-cols-1 sm:grid-cols-[110px_1fr_auto] gap-3 items-end">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">สัดส่วน (%)</label>
                            <input type="number" min="0" max="100" step="0.01" x-model.number="row.percent"
                                   :name="'split[' + i + '][percent]'"
                                   class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white tabular-nums focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">เก็บเมื่อ</label>
                            <input type="text" maxlength="60" x-model="row.label"
                                   :name="'split[' + i + '][label]'"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                   placeholder="เริ่มงาน">
                        </div>
                        <button type="button" x-show="rows.length > 1" @click="rows.splice(i, 1)"
                                class="px-3 py-2.5 rounded-xl border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                            ลบ
                        </button>
                    </div>
                </template>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" x-show="rows.length < 6" @click="rows.push({ percent: 0, label: '' })"
                        class="px-4 py-2 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:border-blue-400 transition">
                    + เพิ่มงวด
                </button>
                <button type="button" @click="rows = defaults.map((d) => ({ ...d }))"
                        class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    กลับเป็นค่าเริ่มต้น ({{ collect($defaults['split'])->pluck('percent')->implode('/') }})
                </button>
            </div>

            <p class="mt-3 text-xs" :class="ok ? 'text-gray-500 dark:text-gray-400' : 'text-red-600 dark:text-red-400 font-semibold'"
               x-text="ok ? 'งวดสุดท้ายจะรับเศษสตางค์เอง เพื่อให้ทุกงวดรวมกันเท่ายอดสุทธิเป๊ะ' : 'ยังรวมไม่ได้ 100% — บันทึกไม่ผ่าน'"></p>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="px-8 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-bold shadow-lg hover:shadow-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="! ok">
                บันทึกเงื่อนไขราคา
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function pricingForm(initial) {
        return {
            rows: (initial && initial.length ? initial : @js($defaults['split'])).map((r) => ({
                percent: Number(r.percent) || 0,
                label: r.label ?? '',
            })),
            defaults: @js(array_map(fn ($s) => ['percent' => (float) $s['percent'], 'label' => $s['label']], $defaults['split'])),
            get total() {
                // ปัดทศนิยมก่อนเทียบ ไม่งั้น 33.33+33.33+33.34 ลอยไม่ถึง 100 เพราะ float
                return Math.round(this.rows.reduce((s, r) => s + (Number(r.percent) || 0), 0) * 100) / 100;
            },
            get ok() {
                return Math.abs(this.total - 100) <= 0.05;
            },
            get totalLabel() {
                const t = this.total;
                return Number.isInteger(t) ? String(t) : t.toFixed(2);
            },
        };
    }
</script>
@endpush
@endsection
