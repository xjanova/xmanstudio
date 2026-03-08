@props([
    'clientSecret',
    'publishableKey',
    'amount' => 0,
    'returnUrl' => url()->current(),
])

@if($clientSecret && $publishableKey)
<div x-data="stripePayment()" x-init="init()" class="stripe-payment-container">
    <!-- Stripe Payment Element -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-600 p-6">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">ชำระผ่าน Stripe</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">บัตรเครดิต/เดบิต และวิธีอื่นๆ</p>
            </div>
        </div>

        <div id="stripe-payment-element" class="mb-4 min-h-[150px]">
            <!-- Stripe Elements will mount here -->
            <div x-show="loading" class="flex items-center justify-center py-8">
                <svg class="animate-spin h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Error Message -->
        <div x-show="errorMessage" x-cloak class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-sm text-red-600 dark:text-red-400" x-text="errorMessage"></p>
        </div>

        <!-- Success Message -->
        <div x-show="succeeded" x-cloak class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-600 dark:text-green-400 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ชำระเงินสำเร็จ! กำลังดำเนินการ...
            </p>
        </div>

        <!-- Pay Button -->
        <button
            x-show="!succeeded"
            @click="handlePayment()"
            :disabled="processing || loading"
            class="w-full py-3.5 px-6 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
        >
            <template x-if="processing">
                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>
            <span x-text="processing ? 'กำลังดำเนินการ...' : 'ชำระเงิน @if($amount > 0) ฿{{ number_format($amount, 2) }} @endif'"></span>
        </button>

        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 text-center flex items-center justify-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            การชำระเงินปลอดภัยโดย Stripe
        </p>
    </div>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
function stripePayment() {
    return {
        stripe: null,
        elements: null,
        paymentElement: null,
        loading: true,
        processing: false,
        errorMessage: '',
        succeeded: false,

        init() {
            const publishableKey = @json($publishableKey);
            const clientSecret = @json($clientSecret);

            if (!publishableKey || !clientSecret) {
                this.errorMessage = 'ไม่สามารถเริ่มระบบชำระเงินได้ กรุณาลองใหม่';
                this.loading = false;
                return;
            }

            this.stripe = Stripe(publishableKey);

            const isDark = document.documentElement.classList.contains('dark');

            this.elements = this.stripe.elements({
                clientSecret: clientSecret,
                locale: 'th',
                appearance: {
                    theme: isDark ? 'night' : 'stripe',
                    variables: {
                        colorPrimary: '#6366f1',
                        borderRadius: '8px',
                    },
                },
            });

            this.paymentElement = this.elements.create('payment', {
                layout: 'tabs',
            });

            this.paymentElement.mount('#stripe-payment-element');

            this.paymentElement.on('ready', () => {
                this.loading = false;
            });

            this.paymentElement.on('change', (event) => {
                if (event.error) {
                    this.errorMessage = event.error.message;
                } else {
                    this.errorMessage = '';
                }
            });
        },

        async handlePayment() {
            if (this.processing) return;
            this.processing = true;
            this.errorMessage = '';

            const returnUrl = @json($returnUrl);

            const { error, paymentIntent } = await this.stripe.confirmPayment({
                elements: this.elements,
                confirmParams: {
                    return_url: returnUrl,
                },
                redirect: 'if_required',
            });

            if (error) {
                if (error.type === 'card_error' || error.type === 'validation_error') {
                    this.errorMessage = error.message;
                } else {
                    this.errorMessage = 'เกิดข้อผิดพลาดในการชำระเงิน กรุณาลองใหม่';
                }
                this.processing = false;
            } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                this.succeeded = true;
                // Poll server to wait for webhook to update payment status before reload
                this.pollPaymentStatus();
            } else {
                this.succeeded = true;
                setTimeout(() => { window.location.reload(); }, 3000);
            }
        },

        pollPaymentStatus(attempts = 0) {
            if (attempts >= 15) {
                // After 15 attempts (30s), reload anyway
                window.location.reload();
                return;
            }
            setTimeout(() => {
                fetch(window.location.href, {
                    headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(() => {
                    // Reload and check if status changed server-side
                    if (attempts >= 2) {
                        // After 4+ seconds, reload — webhook should have arrived
                        window.location.reload();
                    } else {
                        this.pollPaymentStatus(attempts + 1);
                    }
                })
                .catch(() => {
                    this.pollPaymentStatus(attempts + 1);
                });
            }, 2000);
        }
    };
}
</script>
@endpush
@endif
