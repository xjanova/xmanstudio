{{--
    A service price on sale: the list price struck through, the discount, the sale price and its TPIX
    equivalent. The discount comes from Pricing::saleDiscount() (the caller passes it), the same
    number the quotation builder charges — never type a price into a page.
    size: sm (related cards) | md (option cards) | lg (a service's own page)
--}}
@props(['price', 'discount', 'size' => 'md'])
@php
    $sale = $price * (1 - $discount);
    $percent = intval($discount * 100);
@endphp
<div {{ $attributes->merge(['class' => 'svc-price']) }}>
    @if($size === 'lg')
        <div class="flex flex-wrap items-center gap-3 mb-2">
            <span class="text-lg text-gray-500 line-through">{{ number_format($price, 0) }} ฿</span>
            <span class="xp-badge svc-sale">SALE -{{ $percent }}%</span>
        </div>
        <p class="text-white font-black leading-none"><span class="text-5xl sm:text-6xl">{{ number_format($sale, 0) }}</span> <span class="text-2xl text-gray-300 font-bold">฿</span></p>
        <x-tpix-price variant="detail" :thb="$sale" class="mt-3" />
    @else
        <div class="flex items-center gap-1.5 mb-1">
            <span class="text-xs text-gray-500 line-through">{{ number_format($price, 0) }} ฿</span>
            <span class="text-[10px] svc-sale px-1.5 py-0.5 rounded font-bold">-{{ $percent }}%</span>
        </div>
        <p class="text-white font-extrabold leading-none"><span class="{{ $size === 'sm' ? 'text-xl' : 'text-2xl' }}">{{ number_format($sale, 0) }}</span> <span class="text-sm text-gray-400 font-semibold">฿</span></p>
        <div class="mt-1.5"><x-tpix-price :thb="$sale" /></div>
    @endif
</div>
