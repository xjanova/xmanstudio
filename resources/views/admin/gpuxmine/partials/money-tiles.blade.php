{{-- ยอดรายได้แยกตามสถานะ ($money จาก GpuxMineController::moneyByStatus) — ลิงก์ไปคิวรายได้ของสถานะนั้น --}}
@php
    $tiles = [
        \App\Models\GpuJobEarning::STATUS_PENDING => ['อยู่ในระยะพัก', 'text-amber-600 dark:text-amber-400'],
        \App\Models\GpuJobEarning::STATUS_REVIEW => ['รอแอดมินตรวจ', 'text-orange-600 dark:text-orange-400'],
        \App\Models\GpuJobEarning::STATUS_CLEARED => ['รอโอนเข้ากระเป๋า', 'text-sky-600 dark:text-sky-400'],
        \App\Models\GpuJobEarning::STATUS_PAID => ['เข้ากระเป๋าแล้ว', 'text-emerald-600 dark:text-emerald-400'],
        \App\Models\GpuJobEarning::STATUS_VOID => ['ยกเลิก', 'text-gray-500 dark:text-gray-400'],
    ];
    $donated = collect($money)->except(\App\Models\GpuJobEarning::STATUS_VOID)->sum('donated');
    $referral = collect($money)->except(\App\Models\GpuJobEarning::STATUS_VOID)->sum('referral');
    // ส่วนแบ่งผู้แนะนำที่ถึงวันโอนแล้วไม่มีใครรับได้ — เกิดตอนโอน จึงอยู่ในแถวที่จ่ายแล้วเท่านั้น
    $returned = collect($money)->sum('returned');
    $kept = collect($money)->sum('kept');
@endphp
<div class="grid grid-cols-2 md:grid-cols-5 gap-3">
    @foreach ($tiles as $status => [$label, $tone])
        <a href="{{ route('admin.gpuxmine.earnings', ['status' => $status]) }}"
           class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 hover:border-amber-300 dark:hover:border-amber-500/50 transition">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $label }}</p>
            <p class="text-xl font-bold tabular-nums {{ ($money[$status]['jobs'] ?? 0) > 0 ? $tone : 'text-gray-900 dark:text-white' }}">
                ฿{{ number_format(($money[$status]['satang'] ?? 0) / 100, 2) }}
            </p>
            <p class="text-[11px] text-gray-400 mt-0.5">{{ number_format($money[$status]['jobs'] ?? 0) }} งาน</p>
        </a>
    @endforeach
</div>
@if ($donated > 0 || $referral > 0 || $returned > 0 || $kept > 0)
    <p class="text-xs text-gray-500 dark:text-gray-400">
        มูลค่างานที่แชร์ฟรี (ไม่จ่ายเครื่อง): <span class="font-medium tabular-nums">฿{{ number_format($donated / 100, 2) }}</span>
        · ส่วนแบ่งผู้แนะนำที่หักจากงาน: <span class="font-medium tabular-nums">฿{{ number_format($referral / 100, 2) }}</span>
        <span class="text-gray-400">(ไม่นับงานที่ยกเลิก)</span>
        @if ($returned > 0)
            · ส่วนแบ่งผู้แนะนำที่ไม่มีผู้รับ คืนให้เจ้าของเครื่อง: <span class="font-medium tabular-nums">฿{{ number_format($returned / 100, 2) }}</span>
        @endif
        @if ($kept > 0)
            · ส่วนแบ่งผู้แนะนำที่ไม่มีผู้รับ แพลตฟอร์มเก็บไว้: <span class="font-medium tabular-nums text-amber-700 dark:text-amber-300">฿{{ number_format($kept / 100, 2) }}</span>
        @endif
    </p>
@endif
