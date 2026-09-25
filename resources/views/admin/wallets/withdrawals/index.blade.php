@extends($adminLayout ?? 'layouts.admin')

@section('title', 'คำขอถอนเงิน')
@section('page-title', 'คำขอถอนเงิน')

@section('content')
@php
    $card = 'rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
    $filters = \App\Http\Controllers\Admin\WalletWithdrawalController::FILTERS;
    $bkk = static fn ($date) => $date ? $date->copy()->timezone('Asia/Bangkok')->format('d/m/y H:i') : '—';
    $fmt = static fn (int $satang) => \App\Support\Satang::format($satang);
    $tone = static fn (string $status) => match ($status) {
        'approved' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30',
        'pending' => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-500/30',
        'rejected' => 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300 border-red-200 dark:border-red-500/30',
        default => 'bg-gray-100 dark:bg-gray-700/40 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600',
    };
    $pendingTotal = \App\Support\Satang::fromBaht((string) ($counts['pending']->total ?? '0'));
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">คำขอถอนเงินจากกระเป๋า</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-3xl">
                ยอดถูกพักไว้ตั้งแต่ผู้ใช้ขอ (หักจากยอดคงเหลือแล้ว) · โอนเงินจริงในแอปธนาคาร แล้วเปิดคำขอกด “บันทึกว่าโอนแล้ว” พร้อมเลขอ้างอิงหรือสลิป ·
                ไม่อนุมัติ = ยอดที่พักคืนเข้ากระเป๋าผู้ใช้ทันที · ผู้ใช้ต้องยืนยันตัวตนแล้วทุกคน
            </p>
        </div>
        <a href="{{ route('admin.wallets.withdrawals.export', array_filter(['status' => $status, 'q' => $search ?: null])) }}"
           class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-medium text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
            ส่งออก CSV
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $key)
            <a href="{{ route('admin.wallets.withdrawals', ['status' => $key]) }}" class="{{ $card }} p-4 hover:border-emerald-300 dark:hover:border-emerald-500/50 transition">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $filters[$key] }}</p>
                <p class="text-xl font-bold tabular-nums {{ $key === 'pending' && ($counts[$key]->n ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">
                    {{ $fmt(\App\Support\Satang::fromBaht((string) ($counts[$key]->total ?? '0'))) }}
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ number_format((int) ($counts[$key]->n ?? 0)) }} คำขอ</p>
            </a>
        @endforeach
    </div>

    <div class="{{ $card }} overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
            <div class="flex flex-wrap gap-1.5">
                @foreach ($filters as $key => $label)
                    <a href="{{ route('admin.wallets.withdrawals', array_filter(['status' => $key, 'q' => $search ?: null])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $status === $key
                           ? 'bg-slate-900 text-emerald-300 dark:bg-emerald-500 dark:text-slate-900'
                           : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}">
                        {{ $label }}
                        @if ($key !== 'all')
                            <span class="opacity-70 tabular-nums">{{ number_format((int) ($counts[$key]->n ?? 0)) }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('admin.wallets.withdrawals') }}" class="flex flex-wrap gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="search" name="q" value="{{ $search }}" maxlength="100"
                       placeholder="ค้นหาเลขที่คำขอ ชื่อบัญชี เลขอ้างอิง ชื่อหรืออีเมลผู้ใช้"
                       class="flex-1 min-w-[16rem] px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium transition">ค้นหา</button>
                @if ($search !== '')
                    <a href="{{ route('admin.wallets.withdrawals', ['status' => $status]) }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">ล้าง</a>
                @endif
            </form>
            @if ($status === 'pending' && $pendingTotal > 0)
                <p class="text-xs text-amber-700 dark:text-amber-300">ยอดที่รอโอนทั้งหมด {{ $fmt($pendingTotal) }} — เรียงจากคำขอที่รอนานที่สุด</p>
            @endif
        </div>

        @if ($withdrawals->isEmpty())
            <div class="p-10 text-center text-sm text-gray-600 dark:text-gray-300">
                {{ $status === 'pending' && $search === '' ? 'ไม่มีคำขอที่รออนุมัติ' : 'ไม่มีคำขอที่ตรงกับตัวกรองนี้' }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">คำขอ</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">ผู้ใช้</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">ยอด</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">ปลายทาง</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">สถานะ</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach ($withdrawals as $w)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <span class="block font-mono text-xs text-gray-800 dark:text-gray-200">{{ $w->withdrawal_id }}</span>
                                    <span class="block text-[11px] text-gray-400 tabular-nums">{{ $bkk($w->created_at) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="block text-gray-900 dark:text-white">{{ $w->user?->name ?? 'บัญชีถูกลบ' }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $w->user?->email }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums whitespace-nowrap text-gray-900 dark:text-white">{{ $fmt($w->amountSatang()) }}</td>
                                <td class="px-4 py-3">
                                    <span class="block text-gray-800 dark:text-gray-200">{{ $w->maskedDestination() }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $w->account_name }}</span>
                                    @if ($w->account_source !== \App\Models\WalletWithdrawal::SOURCE_KYC)
                                        <span class="block text-[11px] font-semibold text-orange-600 dark:text-orange-300">กรอกเอง — ตรวจชื่อกับ KYC</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block text-xs px-2 py-0.5 rounded-full border whitespace-nowrap {{ $tone($w->status) }}">{{ $w->statusLabel() }}</span>
                                    @if ($w->reviewer)
                                        <span class="block text-[11px] text-gray-400 mt-0.5">โดย {{ $w->reviewer->name }} · {{ $bkk($w->reviewed_at) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.wallets.withdrawals.show', $w->id) }}"
                                       class="inline-flex px-3 py-1.5 rounded-lg text-xs font-semibold {{ $w->isPending() ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200' }} transition whitespace-nowrap">
                                        {{ $w->isPending() ? 'ตรวจและโอน' : 'ดูรายละเอียด' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($withdrawals->hasPages())
                <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">{{ $withdrawals->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
