{{--
    The "เหรียญ TPIX" choice for the product checkouts (Tping, SMS Checker, LocalVPN, AutoTradeX,
    AI credits). Shown only while TPIX can actually be paid; the controller accepts `tpix` under the
    same condition (TpixCheckout::available()) and sends the order to the TPIX pay page.

    $thb — the amount the order will be, shown converted at the live TPIX DEX price.
--}}
@if(\App\Services\TpixPay\TpixCheckout::available())
<label class="block cursor-pointer" onclick="if (typeof selectPayment === 'function') { selectPayment('tpix'); }">
    <input type="radio" name="payment_method" value="tpix" class="sr-only peer"
           id="pm-tpix" {{ old('payment_method') === 'tpix' ? 'checked' : '' }}>
    <div class="flex items-center p-4 border-2 rounded-xl dark:border-gray-600 peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/20 hover:bg-gray-50 dark:hover:bg-gray-700/40">
        <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center mr-4 shrink-0">
            <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9.5" stroke="currentColor" stroke-width="1.8"/><path d="M8 8.5h8M12 8.5v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </div>
        <div class="min-w-0">
            <p class="font-semibold text-gray-900 dark:text-white">เหรียญ TPIX</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">โอนเหรียญบน TPIX Chain ตามราคาตลาดปัจจุบัน (จ่ายผ่าน MetaMask ได้)</p>
            <x-tpix-price variant="detail" :thb="$thb" />
        </div>
    </div>
</label>
@endif
