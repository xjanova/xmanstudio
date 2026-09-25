{{-- ป้ายสถานะของรายได้หนึ่งงาน — ใช้ทั้งหน้าเจ้าของเครื่องและหน้าแอดมิน
     สีอยู่ในไฟล์ Blade เพราะ Tailwind สแกนเฉพาะ resources/ คลาสที่อยู่ใน model จะไม่ถูกสร้าง --}}
@php
    $tone = match ($earning->status) {
        \App\Models\GpuJobEarning::STATUS_PAID => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30',
        \App\Models\GpuJobEarning::STATUS_CLEARED => 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-500/30',
        \App\Models\GpuJobEarning::STATUS_REVIEW => 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-500/30',
        \App\Models\GpuJobEarning::STATUS_VOID => 'bg-gray-100 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600',
        default => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-500/30',
    };
@endphp
<span class="inline-block text-xs px-2 py-0.5 rounded-full border whitespace-nowrap {{ $tone }}">{{ $earning->statusLabel() }}</span>
