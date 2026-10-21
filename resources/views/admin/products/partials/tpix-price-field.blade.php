{{--
    "ราคา TPIX (คงที่)" on the admin product form. Empty = the product is priced in THB and converted
    at the live TPIX DEX price everywhere; a value here is honoured as-is (the shop is the only seller).
    The hint converts both ways at the price quotes use right now.

    $value — the stored price_tpix (or null), $thb — the product's THB price (or null on create)
--}}
@php
    $tpixSpot = app(\App\Services\TpixPay\TpixPriceService::class)->spot();
    $tpixRate = $tpixSpot['quote_rate_thb'] ?? null;
    $tpixValue = old('price_tpix', $value !== null ? rtrim(rtrim((string) $value, '0'), '.') : '');
@endphp
<div x-data="{ tpix: @js((string) $tpixValue), rate: {{ $tpixRate !== null ? (float) $tpixRate : 'null' }} }">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ราคา TPIX (คงที่)</label>
    <input type="number" name="price_tpix" x-model="tpix" min="0" step="0.00000001" placeholder="เว้นว่าง = คิดจากราคาบาทตามราคาตลาด"
           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white transition-all @error('price_tpix') border-red-500 @enderror">
    @error('price_tpix')
        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
    @enderror
    @if($tpixRate !== null)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            ราคาตลาดตอนนี้ 1 TPIX = ฿{{ \App\Services\TpixPay\TpixPriceService::formatRate($tpixRate) }} (TPIX DEX)
            @if($thb !== null && (float) $thb > 0)
                · ราคาบาทของสินค้านี้ ≈ {{ \App\Services\TpixPay\TpixMath::formatTpix(app(\App\Services\TpixPay\TpixPriceService::class)->thbToTpix($thb, $tpixRate)) }} TPIX
            @endif
        </p>
        <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-400" x-show="parseFloat(tpix) > 0" x-cloak
           x-text="'ราคาที่ตั้ง ≈ ฿' + (parseFloat(tpix) * rate).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ตามราคาตลาดตอนนี้'"></p>
    @else
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ยังไม่มีราคา TPIX ล่าสุด (ระบบอ่านราคาจากพูล TPIX DEX ทุกนาที)</p>
    @endif
</div>
