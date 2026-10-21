{{--
    The TPIX price next to a THB price: "≈ 3,812.50 TPIX" at the live TPIX DEX price, or the
    product's fixed TPIX price when the admin set one.

    Renders nothing when TPIX is switched off or there is no trustworthy price right now, so it can
    sit next to every price on the site. The pool price is read once per request (TpixPriceService),
    not once per tag.

    Props:
      thb      the THB amount shown next to it
      tpix     a fixed TPIX price (products.price_tpix) — only where thb is that product's own price
      variant  "inline" (default, small pill) | "detail" (amount + rate/source line) | "plain" (text only)
--}}
@props([
    'thb' => null,
    'tpix' => null,
    'variant' => 'inline',
])
@php
    $tpixQuote = app(\App\Services\TpixPay\TpixPriceService::class)->forDisplay($thb, $tpix);
    $tpixSourceLine = null;
    if ($tpixQuote) {
        $tpixSourceLine = $tpixQuote['fixed']
            ? 'ราคาคงที่ที่ร้านตั้งเป็นเหรียญ TPIX'
            : '1 TPIX = ฿' . $tpixQuote['rate_formatted'] . ' · ราคาจาก TPIX DEX'
                . ($tpixQuote['updated_at'] ? ' · อัปเดต ' . $tpixQuote['updated_at']->copy()->timezone('Asia/Bangkok')->format('H:i') . ' น.' : '');
    }
@endphp
@if($tpixQuote)
    @if($variant === 'detail')
        <div {{ $attributes->merge(['class' => 'mt-1 text-sm']) }} data-tpix-price>
            <span class="inline-flex items-center gap-1.5 font-semibold text-amber-600 dark:text-amber-400">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9.5" stroke="currentColor" stroke-width="1.8"/><path d="M8 8.5h8M12 8.5v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                {{ $tpixQuote['fixed'] ? '' : '≈ ' }}{{ $tpixQuote['tpix_formatted'] }} TPIX
            </span>
            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $tpixSourceLine }}</span>
        </div>
    @elseif($variant === 'plain')
        <span {{ $attributes }} data-tpix-price title="{{ $tpixSourceLine }}">{{ $tpixQuote['fixed'] ? '' : '≈ ' }}{{ $tpixQuote['tpix_formatted'] }} TPIX</span>
    @else
        <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300 whitespace-nowrap align-middle']) }}
              data-tpix-price title="{{ $tpixSourceLine }}">
            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9.5" stroke="currentColor" stroke-width="1.8"/><path d="M8 8.5h8M12 8.5v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            {{ $tpixQuote['fixed'] ? '' : '≈ ' }}{{ $tpixQuote['tpix_formatted'] }} TPIX
        </span>
    @endif
@endif
