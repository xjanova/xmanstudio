{{--
    The download button. A Coming Soon product shows a label instead (the owner's rule, 2026-10-12):
    no link to the file anywhere on the page while the shop says Coming Soon.
--}}
@props(['href', 'comingSoon' => false, 'th' => 'ดาวน์โหลดฟรี', 'en' => 'Free download', 'block' => false, 'big' => true])
@php($box = $block ? 'block w-full py-3.5 text-center' : 'inline-flex items-center gap-2 ' . ($big ? 'px-7 py-4 text-lg' : 'px-6 py-3.5'))
@if($comingSoon)
    <span {{ $attributes->merge(['class' => 'xp-btn-main xp-btn-off rounded-xl font-extrabold transition ' . $box]) }} role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดให้ดาวน์โหลด</span>
@else
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'xp-btn-main rounded-xl font-extrabold transition ' . $box]) }}>
        @unless($block)<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>@endunless
        <x-bi :th="$th" :en="$en" />
    </a>
@endif
