@extends($adminLayout ?? 'layouts.admin')

@section('title', 'คอมมิชชั่นรอตรวจสอบ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">คอมมิชชั่นรอตรวจสอบ</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">อนุมัติหรือปฏิเสธค่าคอมมิชชั่น Affiliate (จ่ายเข้า Wallet อัตโนมัติ) · ค่าแนะนำ GPUxMINE เข้ากระเป๋าเองเมื่อรายได้พ้นระยะพัก — เรียกคืนได้ครั้งเดียวถ้ายอดยังไม่ถูกถอนออก</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.affiliates.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm transition">
                กลับ Affiliate
            </a>
            @if($commissions->where('status', 'pending')->count() > 0)
                <form action="{{ route('admin.affiliates.commission.bulk-approve') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition" onclick="return confirm('อนุมัติและจ่ายทั้งหมด?')">
                        จ่ายทั้งหมด ({{ $commissions->where('status', 'pending')->count() }} รายการ)
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="flex items-center gap-2">
        <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" onchange="this.form.submit()">
            <option value="">รอตรวจสอบ</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
            <option value="reversed" {{ request('status') === 'reversed' ? 'selected' : '' }}>เรียกคืนแล้ว</option>
        </select>
    </form>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Affiliate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รายละเอียด</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ซื้อ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอด</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">อัตรา</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คอมมิชชั่น</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($commissions as $c)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $c->affiliate->user->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $c->affiliate->referral_code ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-3">
                                @php
                                    $typeColors = [
                                        'tping' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                        'order' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                        'rental_payment' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                                        'autotradex' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300',
                                        'gpuxmine' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300',
                                    ];
                                    $typeColor = $typeColors[$c->source_type ?? ''] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColor }}">
                                    {{ $c->source_label }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $c->source_description ?: ($c->order->order_number ?? '-') }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $c->referredUser->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-700 dark:text-gray-300">฿{{ number_format($c->order_amount, 2) }}</td>
                            <td class="px-6 py-3 text-sm text-center text-gray-500">{{ number_format($c->commission_rate) }}%</td>
                            @php
                                $takenSatang = (int) ($c->referral_taken_satang ?? 0);
                                $leftSatang = max(0, \App\Support\Satang::fromBaht((string) $c->commission_amount) - $takenSatang);
                            @endphp
                            <td class="px-6 py-3 text-sm text-right font-semibold {{ $c->status === 'reversed' ? 'text-gray-400 line-through' : 'text-green-600' }}">
                                +฿{{ number_format($c->commission_amount, 2) }}
                                @if($c->status === 'paid' && $takenSatang > 0)
                                    <span class="block text-[11px] font-normal text-gray-500">เรียกคืนบางงานแล้ว {{ \App\Support\Satang::format($takenSatang) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $c->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $c->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $c->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $c->status === 'reversed' ? 'bg-gray-200 text-gray-700' : '' }}
                                ">{{ $c->status_label }}</span>
                                @if($c->status === 'reversed' && $c->reversal_reason)
                                    <span class="block mt-1 text-[11px] text-gray-500 max-w-[12rem] break-words">{{ $c->reversal_reason }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                @if($c->status === 'pending')
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.affiliates.commission.approve', $c) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg">จ่าย</button>
                                        </form>
                                        <form action="{{ route('admin.affiliates.commission.reject', $c) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg">ปฏิเสธ</button>
                                        </form>
                                    </div>
                                @elseif($c->canBeReversed())
                                    {{-- เรียกคืนค่าแนะนำ GPUxMINE ที่จ่ายไปแล้ว (R2-3) — หักกระเป๋าผู้แนะนำ ได้ต่อเมื่อยอดยังไม่ถูกถอนออก --}}
                                    <span class="block text-xs text-gray-400 mb-1">{{ $c->paid_at?->format('d/m/y') ?? '-' }}</span>
                                    <form action="{{ route('admin.affiliates.commission.reverse', $c->id) }}" method="POST" class="flex items-center gap-1.5"
                                          onsubmit="if (! window.confirm(@js('เรียกคืนค่าแนะนำ #' . $c->id . ' (' . \App\Support\Satang::format($leftSatang) . ($takenSatang > 0 ? ' — ส่วนที่ยังไม่ถูกเรียกคืนแยก' : '') . ') จากกระเป๋าผู้แนะนำ?' . "\n\n" . 'ได้ต่อเมื่อยอดยังไม่ถูกถอนออก — ทำได้ครั้งเดียว ย้อนกลับไม่ได้ ผู้แนะนำจะเห็นเหตุผลที่กรอก'))) return false; this.querySelector('button[type=submit]').disabled = true;">
                                        @csrf
                                        <input type="text" name="reason" required minlength="{{ \App\Services\GpuxMinePayoutReversalService::MIN_REASON }}" maxlength="255" autocomplete="off"
                                               placeholder="เหตุผล (≥10 ตัวอักษร)" class="w-36 px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                        <button type="submit" class="text-xs px-3 py-1 bg-rose-700 hover:bg-rose-600 text-white rounded-lg disabled:opacity-50">เรียกคืน</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">{{ $c->paid_at?->format('d/m/y') ?? '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-gray-500">ไม่มีรายการ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            {{ $commissions->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
