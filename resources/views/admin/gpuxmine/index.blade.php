@extends($adminLayout ?? 'layouts.admin')

@section('title', 'GPUxMINE · เครื่องในเครือข่าย')
@section('page-title', 'GPUxMINE · เครื่องในเครือข่าย')

@section('content')
@php
    $card = 'rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';

    // แอปเก็บเวลาเป็น UTC แต่คนอ่านหน้านี้อยู่เมืองไทย
    $bkk = static fn ($date, string $format = 'd/m/Y H:i') => $date ? $date->copy()->timezone('Asia/Bangkok')->format($format) : null;

    // ตัวจับเวลา: เขียว = ทันเวลา · เหลือง = ช้ากว่าที่ควร · แดง = ไม่รัน
    $beatTone = static function ($at, int $okMinutes, int $warnMinutes): string {
        if ($at === null) {
            return 'text-red-600 dark:text-red-400';
        }
        $minutes = $at->diffInMinutes(now(), true);

        return $minutes <= $okMinutes ? 'text-emerald-600 dark:text-emerald-400'
            : ($minutes <= $warnMinutes ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
    };
    $stuck = $health['stuck'];
    $review = $health['review'];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-yellow-600 flex items-center justify-center shadow">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                </span>
                GPUxMINE — เครื่องในเครือข่าย
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ทุกเครื่องที่เคยจับคู่ รวมที่เจ้าของถอนไปแล้ว · ระงับ = หยุดส่งงานชั่วคราวและพักรายได้ ·
                แบน = ถอนออกและห้ามจับคู่ใหม่ทั้งเครื่องและบัญชีเจ้าของ
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.gpuxmine.earnings') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-900 font-semibold text-sm transition whitespace-nowrap">
                รายได้ · คิวตรวจ
                @if ($review['count'] > 0)
                    <span class="bg-slate-900 text-amber-300 text-[11px] px-1.5 py-0.5 rounded-full">{{ $review['count'] }}</span>
                @endif
            </a>
            <a href="{{ route('gpuxmine.index') }}" target="_blank" rel="noopener"
               class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-medium text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
                หน้าเจ้าของเครื่อง ↗
            </a>
        </div>
    </div>

    {{-- ══════════ ระบบยังเดินอยู่ไหม ══════════
         อ่านจากฐานข้อมูลกับแคชเท่านั้น — การเชื่อมต่อ relay/aixman ตรวจด้วย gpuxmine:doctor --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
        <div class="{{ $card }} p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">ตัวจับเวลา sync-nodes (ทุกนาที)</p>
            <p class="mt-1 font-semibold {{ $beatTone($health['sync'], 3, 10) }}">
                {{ $health['sync'] ? 'รันล่าสุด ' . $health['sync']->diffForHumans() : 'ไม่มีบันทึกว่าเคยรัน' }}
            </p>
            <p class="text-[11px] text-gray-400 mt-0.5">ส่งสถานะเครื่องให้ aixman · crontab ต้องเป็น * * * * *</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">ตัวปล่อยเงิน settle-earnings (ทุกชั่วโมง)</p>
            <p class="mt-1 font-semibold {{ $beatTone($health['settle'], 65, 180) }}">
                {{ $health['settle'] ? 'รันล่าสุด ' . $health['settle']->diffForHumans() : 'ไม่มีบันทึกว่าเคยรัน' }}
            </p>
            <p class="text-[11px] text-gray-400 mt-0.5">พักเงิน {{ $health['holdHours'] }} ชม. แล้วโอนเข้ากระเป๋า XMAN</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">เงินค้างเกินระยะพัก</p>
            <p class="mt-1 font-semibold {{ $stuck['count'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                {{ $stuck['count'] > 0 ? number_format($stuck['count']) . ' งาน · ฿' . number_format($stuck['satang'] / 100, 2) : 'ไม่มี' }}
            </p>
            <p class="text-[11px] text-gray-400 mt-0.5">
                {{ $stuck['count'] > 0 ? 'เก่าสุด ' . $bkk($stuck['oldest']) . ' — ตัวปล่อยเงินไม่ได้รันหรือล้ม' : 'ไม่นับเครื่องที่ถูกระงับ (พักไว้โดยตั้งใจ)' }}
            </p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">รอแอดมินตรวจ</p>
            <p class="mt-1 font-semibold {{ $review['count'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                {{ $review['count'] > 0 ? number_format($review['count']) . ' งาน · ฿' . number_format($review['satang'] / 100, 2) : 'ไม่มี' }}
            </p>
            <p class="text-[11px] text-gray-400 mt-0.5">
                @if ($health['retiring'] > 0)
                    <span class="text-amber-600 dark:text-amber-400">ถอนเครื่องค้างนาน {{ $health['retiring'] }} เครื่อง</span> ·
                @endif
                ตรวจ relay/aixman: <span class="font-mono">php artisan gpuxmine:doctor</span>
            </p>
        </div>
    </div>

    {{-- ══════════ ตัวเลขเครื่อง ══════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        @foreach ([
            ['ใช้งานอยู่', $counts['active'], 'text-gray-900 dark:text-white', 'active'],
            ['ออนไลน์', $counts['online'], 'text-emerald-600 dark:text-emerald-400', 'online'],
            ['พร้อมรับงาน', $counts['eligible'], 'text-emerald-600 dark:text-emerald-400', 'online'],
            ['ถูกระงับ', $counts['suspended'], $counts['suspended'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white', 'suspended'],
            ['ถูกแบน', $counts['banned'], $counts['banned'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white', 'banned'],
            ['ถอนยังไม่เสร็จ', $counts['retiring'], $counts['retiring'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white', 'retiring'],
        ] as [$label, $value, $tone, $state])
            <a href="{{ route('admin.gpuxmine.index', ['state' => $state]) }}" class="{{ $card }} p-4 hover:border-amber-300 dark:hover:border-amber-500/50 transition">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $label }}</p>
                <p class="text-2xl font-bold tabular-nums {{ $tone }}">{{ number_format($value) }}</p>
            </a>
        @endforeach
    </div>

    {{-- ══════════ เงินทั้งเครือข่าย ══════════ --}}
    <div class="space-y-2">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">รายได้ของเจ้าของเครื่องทั้งเครือข่าย</h2>
        @include('admin.gpuxmine.partials.money-tiles', ['money' => $money])
    </div>

    {{-- ══════════ รายการเครื่อง ══════════ --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
            <div class="flex flex-wrap gap-1.5">
                @foreach (\App\Http\Controllers\Admin\GpuxMineController::NODE_FILTERS as $key => $label)
                    <a href="{{ route('admin.gpuxmine.index', array_filter(['state' => $key === 'all' ? null : $key, 'q' => $search ?: null])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $filter === $key
                           ? 'bg-slate-900 text-amber-300 dark:bg-amber-500 dark:text-slate-900'
                           : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('admin.gpuxmine.index') }}" class="flex flex-wrap gap-2">
                @if ($filter !== 'all')
                    <input type="hidden" name="state" value="{{ $filter }}">
                @endif
                <input type="search" name="q" value="{{ $search }}" maxlength="100"
                       placeholder="ค้นหา worker id, machine id, ชื่อเครื่อง, การ์ดจอ, ชื่อหรืออีเมลเจ้าของ"
                       class="flex-1 min-w-[16rem] px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium transition">ค้นหา</button>
                @if ($search !== '')
                    <a href="{{ route('admin.gpuxmine.index', array_filter(['state' => $filter === 'all' ? null : $filter])) }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">ล้าง</a>
                @endif
            </form>
        </div>

        @if ($nodes->isEmpty())
            <div class="p-10 text-center">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ $search !== '' || $filter !== 'all' ? 'ไม่มีเครื่องที่ตรงกับตัวกรองนี้' : 'ยังไม่มีเครื่องที่จับคู่กับระบบ' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เครื่องจะขึ้นที่นี่ทันทีที่เจ้าของพิมพ์รหัสจับคู่ในโปรแกรมสำเร็จ</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">เครื่อง</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">เจ้าของ</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">สถานะ</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">ระบบส่งงาน (aixman)</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">รายได้ของเครื่อง</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach ($nodes as $node)
                            @php
                                $t = $nodeTotals[$node->id] ?? [];
                                $sat = static fn (string $status) => (int) ($t[$status]['satang'] ?? 0);
                                $unpaid = $sat('pending') + $sat('review') + $sat('cleared');
                            @endphp
                            <tr class="align-top {{ $node->trashed() ? 'bg-gray-50/60 dark:bg-gray-900/30' : '' }}">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.gpuxmine.show', $node->id) }}" class="block font-medium text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">{{ $node->displayName() }}</a>
                                    <span class="block font-mono text-[11px] text-gray-500 dark:text-gray-400">{{ $node->worker_id }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                                        {{ $node->gpu_name ?: 'ยังไม่รู้การ์ดจอ' }}{{ $node->vram_total_mb > 0 ? ' · ' . number_format($node->vram_total_mb / 1024, 1) . ' GB' : '' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="block text-gray-900 dark:text-white">{{ $node->user?->name ?? 'บัญชีถูกลบ' }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $node->user?->email }}</span>
                                    @if ($node->referrer)
                                        <span class="block text-[11px] text-gray-400">ผู้แนะนำ: {{ $node->referrer->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @include('admin.gpuxmine.partials.node-badges', ['node' => $node])
                                    <span class="block mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        @if ($node->trashed())
                                            ถอนเมื่อ {{ $bkk($node->deleted_at) }}
                                        @else
                                            เห็นล่าสุด {{ $node->last_seen_at?->diffForHumans() ?? '—' }}
                                        @endif
                                    </span>
                                    @if ($node->isSuspended() && $node->suspended_reason)
                                        <span class="block mt-0.5 text-[11px] text-red-600 dark:text-red-400 max-w-[14rem] break-words">{{ \Illuminate\Support\Str::limit($node->banned_reason ?: $node->suspended_reason, 80) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($node->dispatch_status)
                                        <span class="block text-gray-900 dark:text-white">{{ $node->dispatchStatusLabel() }}</span>
                                        <span class="block font-mono text-[11px] text-gray-500 dark:text-gray-400">
                                            {{ $node->dispatch_status }}{{ $node->dispatch_worker_status ? ' · worker ' . $node->dispatch_worker_status : '' }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">ยังไม่เคยส่ง</span>
                                    @endif
                                    @if ($node->dispatch_last_error)
                                        <p class="mt-0.5 max-w-xs text-[11px] text-red-700 dark:text-red-300 break-words" title="{{ $node->dispatch_last_error }}">
                                            {{ \Illuminate\Support\Str::limit($node->dispatch_last_error, 100) }}
                                        </p>
                                    @elseif ($node->dispatch_status === 'error' && $node->dispatch_note)
                                        <p class="mt-0.5 text-[11px] text-red-700 dark:text-red-300">{{ $node->dispatch_note }}</p>
                                    @endif
                                    @if ($node->dispatch_synced_at)
                                        <span class="block text-[11px] text-gray-400">ส่งล่าสุด {{ $node->dispatch_synced_at->diffForHumans() }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">
                                    <span class="block font-semibold text-gray-900 dark:text-white" title="เข้ากระเป๋าแล้ว">฿{{ number_format($sat('paid') / 100, 2) }}</span>
                                    @if ($unpaid !== 0)
                                        <span class="block text-xs text-amber-600 dark:text-amber-400" title="พัก + รอตรวจ + รอโอน">ยังไม่จ่าย ฿{{ number_format($unpaid / 100, 2) }}</span>
                                    @endif
                                    @if ($sat('review') !== 0)
                                        <span class="block text-[11px] text-orange-600 dark:text-orange-400">รอตรวจ ฿{{ number_format($sat('review') / 100, 2) }}</span>
                                    @endif
                                    @if ($sat('void') !== 0)
                                        <span class="block text-[11px] text-gray-400">ยกเลิก ฿{{ number_format($sat('void') / 100, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.gpuxmine.show', $node->id) }}"
                                       class="inline-flex px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
                                        ดู / จัดการ
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($nodes->hasPages())
                <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">{{ $nodes->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
