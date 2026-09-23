@extends($adminLayout ?? 'layouts.admin')

@section('title', 'VPS · ตั้งค่า แพ็กเกจ และเครื่องลูกค้า')

@section('content')
@php
    $card = 'rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
    $input = 'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent';
    $small = 'px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white';

    // The app runs in UTC; the people reading this page do not.
    $bkk = static fn ($date, string $format = 'd/m/Y') => $date ? $date->copy()->timezone('Asia/Bangkok')->format($format) : null;

    // The only statuses VpsProvisioningService::adminRefund() accepts —
    // a live or expired rental is never refunded from a list.
    $refundableStatuses = [
        \App\Models\VpsInstance::STATUS_PENDING,
        \App\Models\VpsInstance::STATUS_PROVISIONING,
        \App\Models\VpsInstance::STATUS_FAILED,
    ];

    $sellableCount = $plans->filter(fn ($p) => $p->isSellable())->count();
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ระบบเช่า VPS</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ราคาขาย = <span class="font-medium">ต้นทุนจริงจากผู้ให้บริการ × (1 + กำไร)</span> ปัดขึ้น
                — แยกราคารอบแรกกับรอบต่ออายุ เพราะค่าต่ออายุที่ผู้ให้บริการเก็บเราแพงกว่ารอบแรก
            </p>
        </div>
        <a href="{{ route('vps.index') }}" target="_blank" rel="noopener"
           class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-medium text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
            ดูหน้าร้าน VPS ↗
        </a>
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

    {{-- ══════════ จ่ายเงินให้ผู้ให้บริการได้ไหม (ใช้ร่วมกับหน้าโดเมน) ══════════ --}}
    @include('admin.partials.upstream-billing', ['billing' => $billing ?? null])

    @unless ($hasToken)
        <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-5 py-4 text-sm text-amber-900 dark:text-amber-200">
            <span class="font-semibold">ยังไม่ได้ใส่ API token ของผู้ให้บริการ</span>
            — ดึงแพ็กเกจ สั่งซื้อ และติดตั้งเครื่องไม่ได้จนกว่าจะตั้ง · ใช้ token เดียวกับระบบขายโดเมน
            <a href="{{ route('admin.domains.index') }}" class="font-semibold underline hover:no-underline">ตั้งค่าที่หน้าระบบขายโดเมน →</a>
        </div>
    @endunless

    {{-- ══════════ ตัวเลขสรุป ══════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
        @php
            $tiles = [
                ['label' => 'เครื่องที่ใช้งานอยู่', 'value' => number_format((int) ($stats['active'] ?? 0)), 'tone' => 'text-gray-900 dark:text-white'],
                ['label' => 'กำลังติดตั้ง', 'value' => number_format((int) ($stats['provisioning'] ?? 0)), 'tone' => ($stats['provisioning'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white'],
                ['label' => 'ติดตั้งไม่สำเร็จ', 'value' => number_format((int) ($stats['failed'] ?? 0)), 'tone' => ($stats['failed'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'],
                ['label' => 'รายได้รวม', 'value' => number_format((float) ($stats['revenue'] ?? 0), 0) . ' ฿', 'tone' => 'text-gray-900 dark:text-white'],
                ['label' => 'ต้นทุนรวม', 'value' => number_format((float) ($stats['cost'] ?? 0), 0) . ' ฿', 'tone' => 'text-gray-900 dark:text-white'],
                ['label' => 'กำไรรวม', 'value' => number_format((float) ($stats['profit'] ?? 0), 0) . ' ฿', 'tone' => ($stats['profit'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'],
            ];
        @endphp
        @foreach ($tiles as $t)
            <div class="{{ $card }} p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t['label'] }}</p>
                <p class="text-2xl font-bold {{ $t['tone'] }}">{{ $t['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ══════════ ต้องดูแล ══════════ --}}
    @if ($attention->isNotEmpty())
        <div class="{{ $card }} overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">ต้องดูแล ({{ $attention->count() }})</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    ลูกค้าจ่ายเงินแล้วแต่เครื่องยังไม่พร้อม · ระบบตามเก็บเองทุก 5 นาที
                    และคืนเงินเองถ้าคำสั่งซื้อไม่ถึงผู้ให้บริการภายใน {{ \App\Services\VpsProvisioningService::PENDING_TIMEOUT_MINUTES }} นาที ·
                    “รอทีมงานตรวจสอบ” คือซื้อเครื่องแล้วแต่ติดตั้งไม่ขึ้น ต้องมีคนตัดสินใจ: ติดตั้งใหม่ หรือคืนเงิน
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    คืนเงินแล้วระบบปิดการต่ออายุฝั่งผู้ให้บริการให้ แต่เครื่องที่ซื้อไปแล้วยังอยู่ในบัญชีจนหมดรอบ — ตรวจใน hPanel ว่าจะใช้ต่อหรือยกเลิก
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">ลูกค้า</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">เครื่อง</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">สถานะ</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">รอมาแล้ว</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">ข้อผิดพลาดล่าสุด</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach ($attention as $i)
                            @php
                                $badge = $i->statusBadge();
                                // Hidden from JSON, not from PHP. It can quote the supplier
                                // word for word, which is fine here and nowhere else.
                                $lastError = trim((string) $i->getAttribute('last_error'));
                                $canRefund = in_array($i->status, $refundableStatuses, true);
                                $refundPrompt = sprintf(
                                    "คืนเงินรายการนี้ให้ %s (%s, #%d) ใช่ไหม?\n\nระบบจะคืนเงินเข้ากระเป๋าลูกค้าและปิดการต่ออายุฝั่งผู้ให้บริการ — เครื่องที่ซื้อไปแล้ว (ถ้ามี) ต้องไปจัดการเองใน hPanel",
                                    $i->user?->email ?? 'ลูกค้า',
                                    $i->hostname,
                                    $i->id,
                                );
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">#{{ $i->id }}</td>
                                <td class="px-4 py-3">
                                    <span class="block text-gray-900 dark:text-white">{{ $i->user?->name ?? '—' }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $i->user?->email }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="block font-mono text-xs text-gray-900 dark:text-white">{{ $i->hostname }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $i->plan_name }} · {{ $i->periodLabel() }}</span>
                                    @if ($i->remote_vm_id)
                                        <span class="block text-[11px] text-gray-400">VM #{{ $i->remote_vm_id }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badge['classes'] }}">{{ $badge['label_th'] }}</span>
                                    <span class="block mt-1 font-mono text-[11px] text-gray-500 dark:text-gray-400">{{ $i->status }}{{ $i->state ? ' · ' . $i->state : '' }}</span>
                                    @if ((int) $i->setup_attempts > 0)
                                        <span class="block text-[11px] text-gray-500 dark:text-gray-400">
                                            ติดตั้งไปแล้ว {{ (int) $i->setup_attempts }}/{{ \App\Services\VpsProvisioningService::MAX_SETUP_ATTEMPTS }} ครั้ง
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $i->created_at?->diffForHumans(null, true) ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($lastError !== '')
                                        <p class="max-w-xs text-xs text-red-700 dark:text-red-300 break-words" title="{{ $lastError }}">
                                            {{ \Illuminate\Support\Str::limit($lastError, 120) }}
                                        </p>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="w-64 space-y-2">
                                        <form method="POST" action="{{ route('admin.vps.retry', $i->id) }}"
                                              onsubmit="this.querySelector('button[type=submit]').disabled = true">
                                            @csrf
                                            <button type="submit"
                                                    class="w-full px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition disabled:opacity-50 disabled:cursor-wait">
                                                ตรวจสอบ/ติดตั้งใหม่
                                            </button>
                                        </form>

                                        @if ($canRefund)
                                            {{-- window.confirm, not a bare confirm: this form has a field
                                                 named "confirm", and inside an inline handler a bare
                                                 confirm resolves to that input. Calling it throws, the
                                                 handler dies, and the form submits with no dialog. --}}
                                            <form method="POST" action="{{ route('admin.vps.refund', $i->id) }}"
                                                  onsubmit="if (! window.confirm(@js($refundPrompt))) return false; this.querySelector('button[type=submit]').disabled = true;"
                                                  class="rounded-lg border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-500/10 p-2 space-y-1.5">
                                                @csrf
                                                <div class="flex items-center gap-1.5">
                                                    <input type="text" name="confirm" required pattern="REFUND" autocomplete="off" spellcheck="false"
                                                           placeholder="พิมพ์ REFUND" title="พิมพ์ REFUND (ตัวพิมพ์ใหญ่) เพื่อยืนยันการคืนเงิน"
                                                           class="w-28 {{ $small }} font-mono">
                                                    <button type="submit"
                                                            class="flex-1 px-3 py-1 rounded bg-red-600 hover:bg-red-500 text-white text-xs font-semibold transition disabled:opacity-50 disabled:cursor-wait">
                                                        คืนเงิน
                                                    </button>
                                                </div>
                                                <input type="text" name="note" maxlength="200" autocomplete="off"
                                                       placeholder="หมายเหตุ (ไม่บังคับ)" class="w-full {{ $small }}">
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ══════════ ตั้งค่าหลัก ══════════ --}}
    <form method="POST" action="{{ route('admin.vps.update') }}" class="{{ $card }} p-6">
        @csrf
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">ราคาและการต่ออายุ</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
            ใช้กับ VPS เท่านั้น — กำไรและการปัดราคาแยกจากระบบขายโดเมน · เปลี่ยนแล้วราคาทุกหน้าอัปเดตทันที
        </p>

        @unless ($settings['coherent'])
            <div class="mb-5 px-4 py-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 text-sm text-amber-800 dark:text-amber-200">
                <strong>ตั้งค่าปัจจุบันขัดกันเอง</strong> —
                แจ้งเตือนที่ {{ $settings['notice_days'] }} วัน
                แต่ตัดเงินที่ {{ $settings['charge_days'] }} วัน
                (บวกเวลารออ่าน {{ $settings['lead_days'] }} วัน)
                ลูกค้าจะถูกตัดเงินก่อนได้รับอีเมล
            </div>
        @endunless

        <div class="grid md:grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">กำไรเริ่มต้น (%)</label>
                <input type="number" name="vps_margin_percent" value="{{ old('vps_margin_percent', $settings['margin']) }}"
                       step="0.5" min="0" max="500" required class="{{ $input }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">ใช้กับทุกแพ็กเกจที่ไม่ได้ตั้งกำไรเฉพาะไว้</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ปัดราคาขึ้นเป็นหลัก</label>
                <select name="vps_price_rounding" class="{{ $input }}">
                    @foreach ([1 => '1 บาท', 5 => '5 บาท', 10 => '10 บาท', 50 => '50 บาท', 100 => '100 บาท'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('vps_price_rounding', $settings['rounding']) == $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <label class="flex items-center gap-2.5 cursor-pointer pb-2.5">
                    {{-- The hidden 0 makes an unchecked box come back as "0" after a
                         failed save, so old() shows what was submitted instead of
                         silently falling back to the stored value. --}}
                    <input type="hidden" name="vps_sales_enabled" value="0">
                    <input type="checkbox" name="vps_sales_enabled" value="1" @checked(old('vps_sales_enabled', $settings['sales_enabled']))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">เปิดขาย VPS</span>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">แจ้งเตือนก่อนตัดเงิน (วัน)</label>
                <input type="number" name="vps_notice_days" value="{{ old('vps_notice_days', $settings['notice_days']) }}"
                       min="1" max="{{ \App\Support\VpsSettings::MAX_DAYS }}" required class="{{ $input }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                    ก่อนหมดอายุกี่วัน · เฉพาะเครื่องที่เปิดต่ออายุอัตโนมัติ · บอกยอดที่จะตัดและบอกว่าเงินในกระเป๋าพอไหม
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ตัดเงินก่อนหมดอายุ (วัน)</label>
                <input type="number" name="vps_charge_days" value="{{ old('vps_charge_days', $settings['charge_days']) }}"
                       min="1" max="{{ \App\Support\VpsSettings::MAX_DAYS }}" required class="{{ $input }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">ตัดจากกระเป๋าเงิน เงินไม่พอจะข้ามแล้วลองใหม่พรุ่งนี้</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">รอให้อ่านอีเมลกี่วัน</label>
                <input type="number" name="vps_notice_lead_days" value="{{ old('vps_notice_lead_days', $settings['lead_days']) }}"
                       min="0" max="30" required class="{{ $input }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">อีเมลแจ้งเตือนต้องส่งไปแล้วอย่างน้อยเท่านี้จึงจะตัดเงินได้</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-6 pt-5 border-t border-gray-200 dark:border-gray-700">
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm transition">
                บันทึกการตั้งค่า
            </button>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                แจ้งเตือน/ตัดเงินต่ออายุรันเองทุกวัน 09:40 น. · เครื่องที่ปิดต่ออายุอัตโนมัติได้อีเมลเตือนก่อนหยุดแยกต่างหาก
                · ลองก่อนได้ที่ <code class="font-mono">php artisan vps:renew --dry</code>
            </span>
        </div>
    </form>

    {{-- ══════════ แพ็กเกจ ══════════ --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">แพ็กเกจ ({{ $plans->count() }})</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">ขายได้จริง {{ $sellableCount }}</span>
                        · เว้นช่องกำไรไว้ = ใช้ค่าเริ่มต้น {{ $settings['margin'] }}%
                        · ขนาดมาตรฐานเปิดขายเองตั้งแต่ดึงครั้งแรก แพ็กเกจที่ไม่รู้จักจะปิดไว้ก่อน
                        · ชื่อ ลำดับ และการเปิด/ปิดที่ตั้งไว้ ไม่ถูกทับตอนดึงใหม่
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.vps.sync') }}"
                      onsubmit="this.querySelector('button[type=submit]').disabled = true">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition disabled:opacity-50 disabled:cursor-wait whitespace-nowrap">
                        ดึงแพ็กเกจ/ราคาจริง
                    </button>
                </form>
            </div>

            @if (session('sync_output'))
                <pre class="mt-4 bg-gray-900 text-gray-100 rounded-lg px-4 py-3 text-xs overflow-x-auto whitespace-pre-wrap">{{ session('sync_output') }}</pre>
            @endif

            <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                ระบบดึงให้เองทุกวัน 04:10 น. · รันจากเครื่องก็ได้ที่ <code class="font-mono">php artisan vps:sync-catalogue</code>
            </p>
        </div>

        @if ($plans->isEmpty())
            <p class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                ยังไม่มีแพ็กเกจ — กด “ดึงแพ็กเกจ/ราคาจริง” ด้านบน (ต้องใส่ API token ก่อน)
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">แพ็กเกจ</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">
                                ราคาต่อรอบ
                                <span class="block text-[11px] font-normal text-gray-500 dark:text-gray-400">รอบแรก → รอบต่ออายุ</span>
                            </th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">ตั้งค่า</th>
                        </tr>
                    </thead>
                    @foreach ($plans as $plan)
                        @php
                            // One form per plan, but its fields sit in three cells and
                            // a detail row, so each field joins it through form="…".
                            $formId = 'vps-plan-' . $plan->id;
                            $periods = $plan->periods();
                            $sellable = $plan->isSellable();
                            $hasDescription = filled($plan->description_th) || filled($plan->description_en);
                            $fromMonthly = $plan->fromMonthlyThb();
                        @endphp
                        <tbody x-data="{ open: false }" class="border-t border-gray-100 dark:border-gray-700/60">
                            <tr @class(['align-top', 'opacity-60' => ! $plan->is_active])>
                                <td class="px-4 py-4 w-72">
                                    <input form="{{ $formId }}" type="text" name="name" value="{{ $plan->name }}" required maxlength="120"
                                           title="ชื่อที่ลูกค้าเห็น"
                                           class="w-full px-2 py-1.5 text-sm font-semibold border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                        @if ($plan->category === 'game')
                                            <span class="px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-500/20 text-violet-800 dark:text-violet-300 text-[11px] font-semibold">Game server</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[11px] font-semibold">VPS</span>
                                        @endif
                                        {{-- No priced period = cannot be bought whatever the toggle
                                             says. A plan that is merely switched off is dimmed instead. --}}
                                        @if ($periods === [])
                                            <span class="px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300 text-[11px] font-semibold" title="ยังไม่มีราคาจากผู้ให้บริการ — ขายไม่ได้จนกว่าจะดึงราคา">ยังขายไม่ได้</span>
                                        @endif
                                        @if ($plan->is_featured)
                                            <span class="px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-800 dark:text-indigo-300 text-[11px]">เด่น</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                        {{ (int) $plan->cpus }} vCPU
                                        · RAM {{ \App\Models\VpsPlan::sizeLabel($plan->memory_mb) }}
                                        · ดิสก์ {{ \App\Models\VpsPlan::sizeLabel($plan->disk_mb) }}
                                        · แบนด์วิดท์ {{ \App\Models\VpsPlan::sizeLabel($plan->bandwidth_mb) }}
                                        @if ($plan->network_mbps)
                                            · {{ number_format($plan->network_mbps) }} Mbps
                                        @endif
                                    </p>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                        ผู้ให้บริการเรียกว่า <span class="font-medium">{{ $plan->remote_name ?: '—' }}</span>
                                        · <span class="font-mono">{{ $plan->slug }}</span>
                                    </p>
                                    @if ($fromMonthly > 0)
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">หน้าร้านแสดง: เริ่ม {{ \App\Support\VpsPricing::format($fromMonthly) }}/เดือน</p>
                                    @endif
                                    @if ($plan->synced_at)
                                        <p class="text-[11px] text-gray-400 mt-0.5">sync {{ $plan->synced_at->diffForHumans() }}</p>
                                    @else
                                        <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">ยังไม่เคย sync</p>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    @forelse ($periods as $period)
                                        @php
                                            $price = $plan->price($period);
                                            $sellFirst = $plan->firstPriceThb($period);
                                            $sellRenew = $plan->renewPriceThb($period);
                                            $profitFirst = $sellFirst - \App\Support\DomainPricing::costThb($price['first'], null, $price['currency']);
                                            $profitRenew = $sellRenew - \App\Support\DomainPricing::costThb($price['renew'], null, $price['currency']);
                                        @endphp
                                        <div @class([
                                            'flex gap-3 py-1.5 text-xs',
                                            'border-t border-gray-100 dark:border-gray-700/60' => ! $loop->first,
                                        ])>
                                            <span class="w-16 shrink-0 font-semibold text-gray-700 dark:text-gray-300">{{ \App\Support\VpsPricing::periodLabel($period) }}</span>
                                            <div class="space-y-0.5 min-w-0">
                                                <p class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                    ทุน {{ number_format($price['first'] / 100, 2) }} → {{ number_format($price['renew'] / 100, 2) }} {{ $price['currency'] }}
                                                </p>
                                                <p class="font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                                    ขาย {{ \App\Support\VpsPricing::format($sellFirst) }} → {{ \App\Support\VpsPricing::format($sellRenew) }}
                                                </p>
                                                <p class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                    กำไร
                                                    <span @class([
                                                        'font-semibold',
                                                        'text-emerald-600 dark:text-emerald-400' => $profitFirst > 0,
                                                        'text-red-600 dark:text-red-400' => $profitFirst <= 0,
                                                    ])>{{ ($profitFirst > 0 ? '+' : '') . number_format($profitFirst, 0) }}</span>
                                                    →
                                                    <span @class([
                                                        'font-semibold',
                                                        'text-emerald-600 dark:text-emerald-400' => $profitRenew > 0,
                                                        'text-red-600 dark:text-red-400' => $profitRenew <= 0,
                                                    ])>{{ ($profitRenew > 0 ? '+' : '') . number_format($profitRenew, 0) }}</span>
                                                    ฿
                                                </p>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-xs text-red-600 dark:text-red-400">ยังไม่มีราคาจากผู้ให้บริการ — กด “ดึงแพ็กเกจ/ราคาจริง”</p>
                                    @endforelse
                                </td>

                                <td class="px-4 py-4 w-64">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400" title="กำไรเฉพาะแพ็กเกจนี้ (%) — เว้นว่าง = ใช้ค่าเริ่มต้น">
                                            กำไร
                                            <input form="{{ $formId }}" type="number" name="margin_percent" value="{{ $plan->margin_percent }}"
                                                   step="0.5" min="0" max="500" placeholder="{{ $settings['margin'] }}" class="w-20 {{ $small }}">
                                            %
                                        </label>
                                        <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400" title="ลำดับบนหน้าร้าน (น้อยขึ้นก่อน)">
                                            ลำดับ
                                            <input form="{{ $formId }}" type="number" name="sort_order" value="{{ $plan->sort_order }}"
                                                   min="0" max="9999" class="w-16 {{ $small }}">
                                        </label>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 mt-2">
                                        <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400" title="เปิดขาย">
                                            <input form="{{ $formId }}" type="checkbox" name="is_active" value="1" @checked($plan->is_active)
                                                   class="rounded border-gray-300 text-indigo-600">ขาย
                                        </label>
                                        <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400" title="แสดงเป็นแพ็กเกจแนะนำ">
                                            <input form="{{ $formId }}" type="checkbox" name="is_featured" value="1" @checked($plan->is_featured)
                                                   class="rounded border-gray-300 text-indigo-600">เด่น
                                        </label>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 mt-3">
                                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.vps.plan', $plan->id) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 rounded bg-gray-800 dark:bg-gray-600 text-white text-xs font-medium hover:bg-gray-700 transition">บันทึก</button>
                                        </form>
                                        <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open"
                                                class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-xs hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                            {{ $hasDescription ? 'แก้คำอธิบาย' : 'เพิ่มคำอธิบาย' }}
                                        </button>
                                    </div>
                                    @if ($plan->is_active && ! $sellable)
                                        <p class="text-[11px] text-red-600 dark:text-red-400 mt-2">เปิดไว้แต่ยังขายไม่ได้ — ไม่มีราคาจากผู้ให้บริการ</p>
                                    @endif
                                </td>
                            </tr>
                            <tr x-show="open" style="display: none">
                                <td colspan="3" class="px-4 pb-4">
                                    <div class="grid md:grid-cols-2 gap-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 p-4">
                                        <label class="block">
                                            <span class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย (ไทย)</span>
                                            <textarea form="{{ $formId }}" name="description_th" rows="2" maxlength="500" class="w-full {{ $small }}">{{ $plan->description_th }}</textarea>
                                        </label>
                                        <label class="block">
                                            <span class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย (English)</span>
                                            <textarea form="{{ $formId }}" name="description_en" rows="2" maxlength="500" class="w-full {{ $small }}">{{ $plan->description_en }}</textarea>
                                        </label>
                                        <p class="md:col-span-2 text-[11px] text-gray-500 dark:text-gray-400">
                                            ข้อความสั้นอธิบายแพ็กเกจบนหน้าร้าน ไม่เกิน 500 ตัวอักษร · เว้นว่างได้ · กด “บันทึก” ในแถวด้านบนเพื่อบันทึก
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </div>

    {{-- ══════════ เครื่องของลูกค้าทั้งหมด ══════════ --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">เครื่องของลูกค้า</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ล่าสุด {{ $instances->count() }} รายการ (แสดงสูงสุด 100) · เวลาไทย</p>
        </div>

        @if ($instances->isEmpty())
            <p class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีลูกค้าเช่า VPS</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">ลูกค้า</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Hostname</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">แพ็กเกจ</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">รอบบิล</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">สถานะ</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">เครื่อง</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">IPv4</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">หมดอายุ</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">ต่ออัตโนมัติ</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">สร้างเมื่อ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach ($instances as $i)
                            @php
                                $badge = $i->statusBadge();
                                $daysLeft = $i->daysUntilExpiry();
                                $showDaysLeft = $daysLeft !== null && in_array($i->status, [\App\Models\VpsInstance::STATUS_ACTIVE, \App\Models\VpsInstance::STATUS_EXPIRED], true);
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">#{{ $i->id }}</td>
                                <td class="px-4 py-3">
                                    <span class="block text-gray-900 dark:text-white">{{ $i->user?->name ?? '—' }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $i->user?->email }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-white">{{ $i->hostname }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $i->plan_name }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $i->periodLabel() }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badge['classes'] }}">{{ $badge['label_th'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    @if ($i->state)
                                        {{ $i->stateLabel()['th'] }}
                                        <span class="font-mono text-gray-400">{{ $i->state }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $i->ipv4 ?: '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($i->expires_at)
                                        <span class="block text-gray-900 dark:text-white">{{ $bkk($i->expires_at) }}</span>
                                        @if ($showDaysLeft)
                                            <span @class([
                                                'block text-xs',
                                                'text-red-600 dark:text-red-400' => $daysLeft < 0,
                                                'text-amber-600 dark:text-amber-400' => $daysLeft >= 0 && $daysLeft <= 7,
                                                'text-gray-500 dark:text-gray-400' => $daysLeft > 7,
                                            ])>{{ $daysLeft < 0 ? 'เลยมา ' . abs($daysLeft) . ' วัน' : 'อีก ' . $daysLeft . ' วัน' }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap">
                                    @if ($i->auto_renew)
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">เปิด</span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">ปิด</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap" title="{{ $i->created_at?->diffForHumans() }}">
                                    {{ $bkk($i->created_at, 'd/m/Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
