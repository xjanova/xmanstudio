{{-- ตารางรายได้ทีละงาน สำหรับหน้าแอดมิน — ใช้ทั้งหน้าเครื่องหนึ่งเครื่อง ($showNode = false) และคิวรายได้ --}}
@php
    $showNode = $showNode ?? true;
    $bkk = static fn ($date, string $format = 'd/m/y H:i') => $date ? $date->copy()->timezone('Asia/Bangkok')->format($format) : '—';
    $baht = static fn (int $satang) => '฿' . number_format($satang / 100, 2);
@endphp
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
            <tr>
                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">เสร็จเมื่อ</th>
                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">งาน</th>
                @if ($showNode)
                    <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">เครื่อง / เจ้าของ</th>
                @endif
                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">เครื่องได้</th>
                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">ที่มาของเงิน</th>
                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">สถานะ</th>
                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">ตัดสิน</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
            @foreach ($earnings as $row)
                <tr class="align-top">
                    <td class="px-4 py-3 whitespace-nowrap tabular-nums text-gray-600 dark:text-gray-300">
                        {{ $bkk($row->completed_at) }}
                        @if ($row->status === 'pending' && ($ends = $row->holdEndsAt($holdHours)))
                            <span class="block text-[11px] text-gray-400">พ้นระยะพัก {{ $bkk($ends, 'd/m H:i') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="block text-gray-900 dark:text-white">{{ $row->kindLabel() }}{{ $row->lane === 'slow' ? ' · ไม่เร่ง' : '' }}</span>
                        <span class="block font-mono text-[11px] text-gray-500 dark:text-gray-400">{{ $row->job_id }}</span>
                        <span class="block text-[11px] text-gray-400">{{ $row->model_key ?: '—' }}{{ $row->seconds > 0 ? ' · ' . $row->seconds . ' วินาที' : '' }}</span>
                    </td>
                    @if ($showNode)
                        <td class="px-4 py-3">
                            @if ($row->node)
                                <a href="{{ route('admin.gpuxmine.show', $row->node->id) }}" class="block text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">{{ $row->node->displayName() }}</a>
                            @else
                                <span class="block font-mono text-[11px] text-gray-500">{{ $row->worker_id }}</span>
                            @endif
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $row->user?->email ?? 'บัญชีถูกลบ' }}</span>
                            @if (in_array((int) $row->id, $frozenIds ?? [], true) && in_array($row->status, ['pending', 'review', 'cleared'], true))
                                <span class="block text-[11px] font-semibold text-red-600 dark:text-red-400">เครื่องถูกระงับ — เงินพักไว้</span>
                            @endif
                        </td>
                    @endif
                    <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums font-semibold {{ $row->status === 'void' ? 'text-gray-400 line-through' : ($row->amount_satang < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white') }}">
                        {{ $baht((int) $row->amount_satang) }}
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums text-[11px] text-gray-500 dark:text-gray-400">
                        @if ($row->revenue_satang > 0)
                            <span class="block">รายรับ {{ $baht((int) $row->revenue_satang) }} · {{ (int) $row->credits_charged }} เครดิต</span>
                            <span class="block">ค่าธรรมเนียม {{ $baht((int) $row->platform_fee_satang) }}</span>
                        @endif
                        @if ($row->referral_satang > 0)
                            <span class="block">ผู้แนะนำ −{{ $baht((int) $row->referral_satang) }}</span>
                        @endif
                        @if ($row->referral_unpaid_satang > 0)
                            <span class="block text-amber-700 dark:text-amber-300">
                                {{ $row->referral_unpaid_to === 'owner' ? 'ผู้แนะนำไม่ active แล้ว — คืนเจ้าของ +' : 'ผู้แนะนำไม่ active แล้ว — แพลตฟอร์มเก็บ ' }}{{ $baht((int) $row->referral_unpaid_satang) }}
                            </span>
                        @endif
                        @if ($row->free_share || $row->donated_value_satang > 0)
                            <span class="block text-teal-600 dark:text-teal-400">แชร์ฟรี มูลค่า {{ $baht((int) $row->donated_value_satang) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @include('gpuxmine.partials.earning-status', ['earning' => $row])
                        @if ($row->review_reason && in_array($row->status, ['review', 'pending', 'cleared'], true))
                            <span class="block mt-1 text-[11px] text-orange-700 dark:text-orange-300 max-w-[14rem] break-words">ติดตรวจเพราะ: {{ $row->review_reason }}</span>
                        @endif
                        @if ($row->status === 'void' && $row->void_reason)
                            <span class="block mt-1 text-[11px] text-gray-500 dark:text-gray-400 max-w-[14rem] break-words">ยกเลิกเพราะ: {{ $row->void_reason }}</span>
                        @endif
                        @if ($row->reviewed_at)
                            <span class="block text-[11px] text-gray-400">โดย {{ $row->reviewer?->name ?? 'แอดมินที่ถูกลบ' }} · {{ $bkk($row->reviewed_at, 'd/m H:i') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @include('admin.gpuxmine.partials.earning-actions', ['earning' => $row, 'holdHours' => $holdHours])
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
