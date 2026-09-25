{{-- ป้ายสถานะของเครื่องหนึ่งเครื่องในหน้าแอดมิน — เรียงจากสิ่งที่สำคัญที่สุดก่อน --}}
<div class="flex flex-wrap items-center gap-1">
    @if ($node->isBanned())
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-600 text-white">ถูกแบน</span>
    @elseif ($node->isSuspended())
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300">ถูกระงับ</span>
    @endif

    @if ($node->trashed())
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">ถอนแล้ว</span>
        @if ($node->retire_status === \App\Models\GpuNode::RETIRE_PENDING)
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">ถอนยังไม่เสร็จ</span>
        @elseif ($node->retire_status === \App\Models\GpuNode::RETIRE_AWAITING_RELAY)
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300" title="aixman ถอนแล้ว — relay รุ่นนี้ยังลบ worker ไม่ได้">ค้างที่ relay รุ่นเก่า</span>
        @endif
    @elseif ($node->online)
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>ออนไลน์
        </span>
    @else
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">ออฟไลน์</span>
    @endif

    @if (! $node->trashed() && $node->assessed)
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300">{{ strtoupper((string) $node->tier) }} · {{ number_format((int) $node->score) }}</span>
    @elseif (! $node->trashed())
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">ยังไม่ประเมิน</span>
    @endif

    @if ($node->busy)
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300">กำลังทำงาน</span>
    @elseif ($node->accepting === false)
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">ไม่รับงาน</span>
    @endif

    @if ((int) $node->free_share_pct > 0)
        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-teal-100 text-teal-700 dark:bg-teal-500/20 dark:text-teal-300">แชร์ฟรี {{ (int) $node->free_share_pct }}%</span>
    @endif
</div>
