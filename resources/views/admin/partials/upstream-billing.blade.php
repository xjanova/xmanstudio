{{--
    "Can we still pay our supplier?" — shared by the domain and VPS admin pages.

    Every domain and VPS we sell is bought upstream with the one card saved on
    our supplier account, after the customer has paid us. When that card is
    refused, both shops fail the same way, so both pages carry this panel.

    Expects $billing from VpsSettingController::billingState():
      paused        array|null  reason, source ('purchase'|'health'), at, until (ISO-8601)
      health        array|null  checked_at, ok, level, problems[], methods[]
      last_failure  array|null  reason, source, at
      telegram      bool        bot configured and switched on
      orders_alerts bool        the orders & payments category is on

    The state lives in the cache and in a setting, so any key can be missing
    or null (a cache flush, a first deploy). Every read below has a fallback:
    this panel must never be the reason an admin page answers with a 500.
--}}
@php
    $billingState = is_array($billing ?? null) ? $billing : [];
    $pause = is_array($billingState['paused'] ?? null) ? $billingState['paused'] : null;
    $health = is_array($billingState['health'] ?? null) ? $billingState['health'] : null;
    $lastFailure = is_array($billingState['last_failure'] ?? null) ? $billingState['last_failure'] : null;
    $telegramOn = (bool) ($billingState['telegram'] ?? false);
    $ordersAlertsOn = (bool) ($billingState['orders_alerts'] ?? false);

    $level = (string) ($health['level'] ?? 'unknown');
    $problems = array_values(array_filter((array) ($health['problems'] ?? []), 'is_string'));
    $methods = array_values(array_filter((array) ($health['methods'] ?? []), 'is_array'));

    // ISO-8601 from the cache, shown in Bangkok time. Missing or unparseable → null.
    $parseTime = static function ($value): ?\Carbon\Carbon {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->timezone('Asia/Bangkok');
        } catch (\Throwable) {
            return null;
        }
    };
    $clock = static fn (?\Carbon\Carbon $at): string => $at ? $at->format('d/m H:i') . ' น.' : '—';

    $pauseReason = is_scalar($pause['reason'] ?? null) && (string) $pause['reason'] !== '' ? (string) $pause['reason'] : 'ไม่ระบุ';
    $pauseAt = $parseTime($pause['at'] ?? null);
    $pauseUntil = $parseTime($pause['until'] ?? null);
    $pauseByHealth = ($pause['source'] ?? '') === 'health';

    $checkedAt = $parseTime($health['checked_at'] ?? null);

    $failureReason = is_scalar($lastFailure['reason'] ?? null) && (string) $lastFailure['reason'] !== '' ? (string) $lastFailure['reason'] : 'ไม่ระบุ';
    $failureAt = $parseTime($lastFailure['at'] ?? null);
    $failureByHealth = ($lastFailure['source'] ?? '') === 'health';
@endphp

<div @class([
    'rounded-2xl bg-white dark:bg-gray-800 border p-6',
    'border-red-300 dark:border-red-500/50' => $pause !== null,
    'border-gray-200 dark:border-gray-700' => $pause === null,
])>
    {{-- ── หัวการ์ด: ระดับความพร้อม + ปุ่มตรวจ ── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">การจ่ายเงินให้ผู้ให้บริการ (บัตรในบัญชี Hostinger)</h2>
                @if ($level === 'ok')
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-xs font-semibold">ปกติ</span>
                @elseif ($level === 'warning')
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 text-xs font-semibold">ใกล้หมดอายุ</span>
                @elseif ($level === 'critical')
                    <span class="px-2.5 py-0.5 rounded-full bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300 text-xs font-semibold">จ่ายไม่ได้</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold">{{ $health === null ? 'ยังไม่เคยตรวจ' : 'ไม่ทราบ' }}</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ทุกโดเมนและ VPS ที่ขาย เราซื้อต่อด้วยบัตรในบัญชีนี้หลังลูกค้าจ่ายเราแล้ว — บัตรจ่ายไม่ผ่านเมื่อไร ขายไม่ได้ทั้งสองระบบ
            </p>
        </div>

        <form method="POST" action="{{ route('admin.upstream-billing.check') }}"
              onsubmit="this.querySelector('button[type=submit]').disabled = true">
            @csrf
            <button type="submit"
                    class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-semibold text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-wait whitespace-nowrap">
                ตรวจตอนนี้
            </button>
        </form>
    </div>

    {{-- ── หยุดขายอยู่ ── --}}
    @if ($pause)
        <div class="mt-5 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-5 py-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-red-900 dark:text-red-200">หยุดรับคำสั่งซื้อโดเมนและ VPS ชั่วคราว</p>
                    <p class="text-sm text-red-800 dark:text-red-300 mt-1 break-words">สาเหตุ: {{ $pauseReason }}</p>
                    <p class="text-xs text-red-700 dark:text-red-300/80 mt-1.5">
                        ตั้งแต่ {{ $clock($pauseAt) }}
                        ถึง {{ $clock($pauseUntil) }}
                        @if ($pauseByHealth)
                            · หยุดจากผลตรวจวิธีชำระเงิน — เปิดเองเมื่อการตรวจรอบถัดไปผ่าน
                        @else
                            · ผู้ให้บริการปฏิเสธการตัดเงินตอนซื้อให้ลูกค้า — เปิดเองเมื่อครบเวลา
                        @endif
                    </p>
                    <p class="text-xs text-red-700 dark:text-red-300/80 mt-1">
                        ระหว่างนี้ลูกค้าเห็นข้อความ “{{ \App\Support\UpstreamBilling::customerMessage() }}” ก่อนมีการตัดเงินใด ๆ
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.upstream-billing.resume') }}"
                      onsubmit="return confirm('เปิดรับคำสั่งซื้อโดเมนและ VPS ต่อเลยไหม?\n\nกดเมื่อแก้บัตรในบัญชีผู้ให้บริการแล้วเท่านั้น — ถ้าบัตรยังจ่ายไม่ผ่าน ลูกค้าคนถัดไปจะถูกตัดเงินแล้วได้เงินคืน และระบบจะหยุดขายเองอีกครั้ง')">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white font-semibold text-sm transition whitespace-nowrap">
                        เปิดขายต่อ (แก้บัตรแล้ว)
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- ── ผลตรวจล่าสุด + วิธีชำระเงินในบัญชี ── --}}
    <div class="mt-5 grid lg:grid-cols-5 gap-5">
        <div class="lg:col-span-2 space-y-3">
            @if ($health === null)
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    ยังไม่เคยตรวจ — กด “ตรวจตอนนี้” หรือรอรอบตรวจอัตโนมัติ
                </p>
            @else
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    ตรวจล่าสุด {{ $clock($checkedAt) }}
                    @if ($checkedAt)
                        <span>({{ $checkedAt->diffForHumans() }})</span>
                    @endif
                </p>

                @if ($problems !== [])
                    <ul @class([
                        'list-disc list-inside space-y-1 rounded-xl border px-4 py-3 text-sm',
                        'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200' => $level === 'critical',
                        'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30 text-amber-900 dark:text-amber-200' => $level === 'warning',
                        'bg-slate-50 dark:bg-slate-500/10 border-slate-200 dark:border-slate-500/30 text-slate-800 dark:text-slate-200' => ! in_array($level, ['critical', 'warning'], true),
                    ])>
                        @foreach ($problems as $problem)
                            <li>{{ $problem }}</li>
                        @endforeach
                    </ul>
                @elseif ($level === 'ok')
                    <p class="text-sm text-emerald-700 dark:text-emerald-300">
                        วิธีชำระเงินหลักใช้ได้ และไม่หมดอายุภายใน {{ \App\Support\UpstreamBilling::EXPIRY_WARNING_DAYS }} วัน
                    </p>
                @endif
            @endif
        </div>

        <div class="lg:col-span-3">
            @if ($methods !== [])
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                            <tr>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-300">วิธีชำระเงิน</th>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-300">หมดอายุ</th>
                                <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-300">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @foreach ($methods as $method)
                                @php
                                    $isDefault = (bool) ($method['is_default'] ?? false);
                                    $isExpired = (bool) ($method['is_expired'] ?? false);
                                    $isSuspended = (bool) ($method['is_suspended'] ?? false);
                                    $typeLabel = \App\Support\UpstreamBilling::label(is_scalar($method['type'] ?? null) ? (string) $method['type'] : 'unknown');
                                    $methodId = is_scalar($method['id'] ?? null) ? (string) $method['id'] : '?';
                                    $expiresRaw = is_scalar($method['expires_at'] ?? null) ? (string) $method['expires_at'] : '';
                                    $expiresAt = $parseTime($expiresRaw);
                                    $expiresSoon = $expiresAt !== null
                                        && $expiresAt->lte(now()->addDays(\App\Support\UpstreamBilling::EXPIRY_WARNING_DAYS));
                                @endphp
                                <tr>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $typeLabel }}</span>
                                            @if ($isDefault)
                                                <span class="px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-800 dark:text-indigo-300 text-[11px] font-semibold">หลัก</span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-gray-400">#{{ $methodId }}</span>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if ($expiresAt)
                                            <span @class([
                                                'text-amber-600 dark:text-amber-400 font-semibold' => $expiresSoon,
                                                'text-gray-600 dark:text-gray-400' => ! $expiresSoon,
                                            ])>{{ $expiresAt->format('d/m/Y') }}</span>
                                        @elseif ($expiresRaw !== '')
                                            <span class="text-gray-600 dark:text-gray-400">{{ $expiresRaw }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap gap-1">
                                            @if ($isExpired)
                                                <span class="px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300 text-[11px] font-semibold">หมดอายุแล้ว</span>
                                            @endif
                                            @if ($isSuspended)
                                                <span class="px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300 text-[11px] font-semibold">ถูกระงับ</span>
                                            @endif
                                            @if (! $isExpired && ! $isSuspended)
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-[11px] font-semibold">ใช้ได้</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif ($health !== null)
                <p class="text-sm text-gray-500 dark:text-gray-400">ไม่มีรายการวิธีชำระเงินจากการตรวจครั้งล่าสุด</p>
            @endif
        </div>
    </div>

    {{-- ── ครั้งล่าสุดที่จ่ายไม่ผ่าน (อยู่ใน setting จึงยังเห็นได้หลังหยุดขายหมดเวลาแล้ว) ── --}}
    @if ($lastFailure)
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400 break-words">
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $failureByHealth ? 'หยุดขายจากผลตรวจครั้งล่าสุด' : 'ปฏิเสธการตัดเงินครั้งล่าสุด' }}</span>
            {{ $clock($failureAt) }}
            @if ($failureAt)
                <span class="text-gray-400">({{ $failureAt->diffForHumans() }})</span>
            @endif
            — {{ $failureReason }}
        </p>
    @endif

    {{-- ── แจ้งเตือนจะไปถึงแอดมินทางไหน ── --}}
    @if (! $telegramOn)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-4 py-3 text-sm text-amber-900 dark:text-amber-200">
            <span>ยังไม่ได้ตั้งค่าบอท Telegram — แจ้งเตือน “ยอดเงิน/บัตรไม่ผ่าน” จะส่งทางอีเมลแทน</span>
            <a href="{{ route('admin.alerts.index') }}"
               class="shrink-0 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition">
                ตั้งค่าบอท Telegram
            </a>
        </div>
    @elseif (! $ordersAlertsOn)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-4 py-3 text-sm text-amber-900 dark:text-amber-200">
            <span>หมวด “ใบสั่งซื้อ &amp; การชำระเงิน” ปิดอยู่ แจ้งเตือนเรื่องนี้จะไม่ถึง Telegram</span>
            <a href="{{ route('admin.alerts.index') }}"
               class="shrink-0 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition">
                เปิดหมวดนี้
            </a>
        </div>
    @endif

    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
        ระบบตรวจวิธีชำระเงินให้เองทุก 6 ชั่วโมง และเตือนก่อนบัตรหลักหมดอายุ {{ \App\Support\UpstreamBilling::EXPIRY_WARNING_DAYS }} วัน ·
        ถ้าตัดเงินไม่ผ่าน ระบบจะหยุดขายอัตโนมัติ {{ \App\Support\UpstreamBilling::PAUSE_MINUTES }} นาที (ระหว่างนั้นลูกค้าไม่ถูกตัดเงิน) และแจ้งแอดมินทันที ·
        ผู้ให้บริการไม่บอกยอดเงินคงเหลือในบัตร — บัตรเงินไม่พอจึงรู้ได้ตอนถูกปฏิเสธเท่านั้น
        · รันเองได้ที่ <code class="font-mono">php artisan hostinger:billing-check</code>
    </p>
</div>
