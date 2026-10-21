{{--
    ป้าย Coming Soon ที่ขึ้นแทนปุ่มดาวน์โหลดทุกปุ่ม (กฎเจ้าของ 2026-10-12: สินค้าที่ตั้ง Coming Soon ห้ามโหลดได้จากทุกจุด)
    คลาสเดียวกับปุ่ม Coming Soon เดิมของหน้าสินค้า (products/show) — มีใน build อยู่แล้ว
--}}
@props(['product' => null, 'label' => 'Coming Soon — ยังไม่เปิดให้ดาวน์โหลด'])

<div {{ $attributes->merge(['class' => 'inline-flex items-center justify-center px-8 py-4 bg-orange-600/50 text-orange-200 font-bold rounded-xl text-center cursor-not-allowed']) }}
     role="status" aria-disabled="true" data-coming-soon>
    <svg class="w-5 h-5 inline mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>
        {{ $label }}
        @if($product?->coming_soon_until)
            · เปิด {{ $product->coming_soon_until->format('d/m/Y') }}
        @endif
    </span>
</div>
