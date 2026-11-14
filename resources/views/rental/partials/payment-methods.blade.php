{{-- Payment method choice for a rental payment — checkout, renewal and add-on purchase share it. --}}
<div class="space-y-4">
    <!-- PromptPay -->
    <label class="block cursor-pointer">
        <input type="radio" name="payment_method" value="promptpay" class="sr-only peer" checked>
        <div class="flex items-center p-4 border-2 rounded-lg peer-checked:border-primary-500 peer-checked:bg-primary-50">
            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900"><x-bi th="พร้อมเพย์" en="PromptPay" /></p>
                <p class="text-sm text-gray-500"><x-bi th="สแกน QR Code เพื่อชำระเงิน" en="Scan QR code to pay" /></p>
            </div>
        </div>
    </label>

    <!-- Bank Transfer -->
    <label class="block cursor-pointer">
        <input type="radio" name="payment_method" value="bank_transfer" class="sr-only peer">
        <div class="flex items-center p-4 border-2 rounded-lg peer-checked:border-primary-500 peer-checked:bg-primary-50">
            <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900"><x-bi th="โอนเงินธนาคาร" en="Bank transfer" /></p>
                <p class="text-sm text-gray-500"><x-bi th="โอนเงินแล้วแนบสลิป" en="Transfer then attach the slip" /></p>
            </div>
        </div>
    </label>

    <!-- Credit Card (if enabled) -->
    @if(config('payment.card.enabled', false))
        <label class="block cursor-pointer">
            <input type="radio" name="payment_method" value="credit_card" class="sr-only peer">
            <div class="flex items-center p-4 border-2 rounded-lg peer-checked:border-primary-500 peer-checked:bg-primary-50">
                <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="font-semibold text-gray-900"><x-bi th="บัตรเครดิต/เดบิต" en="Credit/Debit card" /></p>
                    <p class="text-sm text-gray-500">Visa, Mastercard, JCB</p>
                </div>
            </div>
        </label>
    @endif

    <!-- Stripe -->
    @if(\App\Services\StripeService::isEnabled())
        @php
            $stripeFeeService = app(\App\Services\PaymentFeeService::class);
            $stripeHasFee = $stripeFeeService->hasFee('stripe');
            $stripeFeeDisplay = $stripeHasFee ? $stripeFeeService->formatFeeDisplay($stripeFeeService->getFeeType('stripe'), $stripeFeeService->getFeeAmount('stripe')) : null;
        @endphp
        <label class="block cursor-pointer">
            <input type="radio" name="payment_method" value="stripe" class="sr-only peer">
            <div class="flex items-center p-4 border-2 rounded-lg peer-checked:border-primary-500 peer-checked:bg-primary-50">
                <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="font-semibold text-gray-900">Stripe</p>
                    <p class="text-sm text-gray-500"><x-bi th="บัตรเครดิต/เดบิต และวิธีอื่นๆ" en="Credit/Debit cards and more" /></p>
                    @if($stripeFeeDisplay)
                    <p class="text-xs text-amber-600 mt-0.5"><x-bi th="ค่าธรรมเนียม" en="Fee" /> {{ $stripeFeeDisplay }}</p>
                    @endif
                </div>
            </div>
        </label>
    @endif
</div>
