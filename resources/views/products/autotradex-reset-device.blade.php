@extends($publicLayout ?? 'layouts.app')

@section('title', 'Reset Device - AutoTradeX | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 py-16">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <nav class="mb-8">
                <a href="{{ route('products.show', 'autotradex') }}" class="text-purple-400 hover:text-purple-300 flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปหน้า AutoTradeX
                </a>
            </nav>

            <div class="inline-flex items-center px-4 py-2 bg-purple-500/20 rounded-full text-purple-300 text-sm mb-6 backdrop-blur-sm border border-purple-500/30">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Lifetime License Only
            </div>

            <h1 class="text-4xl font-black text-white mb-4">
                Reset <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">Device</span>
            </h1>
            <p class="text-gray-400 text-lg">
                สำหรับลูกค้า Lifetime - เปลี่ยนเครื่องคอมพิวเตอร์ใหม่
            </p>
        </div>

        <!-- Info Box -->
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-6 mb-8">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-400 mt-1 mr-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-blue-300 font-semibold mb-2">ข้อมูลสำคัญ</h3>
                    <ul class="text-blue-200/80 text-sm space-y-1">
                        <li>- เฉพาะ <strong>Lifetime License</strong> เท่านั้นที่สามารถ Reset Device ได้</li>
                        <li>- สามารถ Reset ได้ <strong>1 ครั้งทุก 30 วัน</strong></li>
                        <li>- หลัง Reset สำเร็จ สามารถ Activate บนเครื่องใหม่ได้ทันที</li>
                        <li>- ต้องใช้อีเมลที่ใช้สั่งซื้อ License</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Reset Form -->
        <div class="bg-gray-800/50 rounded-2xl p-8 border border-gray-700 backdrop-blur-sm">
            <form id="resetDeviceForm" class="space-y-6">
                @csrf

                <!-- License Key -->
                <div>
                    <label for="license_key" class="block text-sm font-medium text-gray-300 mb-2">
                        License Key <span class="text-red-400">*</span>
                    </label>
                    <input type="text"
                           id="license_key"
                           name="license_key"
                           placeholder="ATX-XXXX-XXXX-XXXX"
                           class="w-full px-4 py-3 bg-gray-900/50 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent uppercase"
                           required>
                    <p class="text-gray-500 text-xs mt-1">License Key ที่ได้รับหลังสั่งซื้อ</p>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                        อีเมล <span class="text-red-400">*</span>
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           placeholder="your@email.com"
                           class="w-full px-4 py-3 bg-gray-900/50 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           required>
                    <p class="text-gray-500 text-xs mt-1">อีเมลที่ใช้สั่งซื้อ License</p>
                </div>

                <!-- Reason (Optional) -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-300 mb-2">
                        เหตุผล (ไม่บังคับ)
                    </label>
                    <textarea id="reason"
                              name="reason"
                              rows="3"
                              placeholder="เช่น เปลี่ยนเครื่องคอมพิวเตอร์ใหม่"
                              class="w-full px-4 py-3 bg-gray-900/50 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"></textarea>
                </div>

                <!-- Error Message -->
                <div id="errorMessage" class="hidden bg-red-500/10 border border-red-500/30 rounded-xl p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span id="errorText" class="text-red-300"></span>
                    </div>
                </div>

                <!-- Success Message -->
                <div id="successMessage" class="hidden bg-green-500/10 border border-green-500/30 rounded-xl p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p id="successText" class="text-green-300 font-medium"></p>
                            <p id="successDetails" class="text-green-200/80 text-sm mt-1"></p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        id="submitBtn"
                        class="w-full py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl transition-all transform hover:scale-[1.02] shadow-lg shadow-purple-500/25 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                    <span id="btnText">Reset Device</span>
                    <span id="btnLoading" class="hidden">
                        <svg class="animate-spin inline w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        กำลังดำเนินการ...
                    </span>
                </button>
            </form>
        </div>

        <!-- Help Section -->
        <div class="mt-8 text-center">
            <p class="text-gray-500 text-sm">
                มีปัญหาหรือต้องการความช่วยเหลือ?
                <a href="mailto:support@xman4289.com" class="text-purple-400 hover:text-purple-300">ติดต่อ Support</a>
            </p>
        </div>
    </div>
</div>

<script>
document.getElementById('resetDeviceForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnLoading = document.getElementById('btnLoading');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const successMessage = document.getElementById('successMessage');
    const successText = document.getElementById('successText');
    const successDetails = document.getElementById('successDetails');

    // Reset messages
    errorMessage.classList.add('hidden');
    successMessage.classList.add('hidden');

    // Show loading
    submitBtn.disabled = true;
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');

    try {
        const response = await fetch('/api/v1/autotradex/reset-device', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                license_key: document.getElementById('license_key').value,
                email: document.getElementById('email').value,
                reason: document.getElementById('reason').value,
            })
        });

        const data = await response.json();

        if (data.success) {
            successText.textContent = data.message;
            successDetails.textContent = `Reset ครั้งที่ ${data.data.total_resets} - สามารถ Reset ได้อีกครั้งหลัง ${new Date(data.data.next_reset_available_at).toLocaleDateString('th-TH')}`;
            successMessage.classList.remove('hidden');

            // Clear form
            document.getElementById('resetDeviceForm').reset();
        } else {
            let errorMsg = data.message;
            if (data.error_code === 'RESET_COOLDOWN' && data.cooldown_days_remaining) {
                errorMsg += ` (เหลือ ${data.cooldown_days_remaining} วัน)`;
            }
            errorText.textContent = errorMsg;
            errorMessage.classList.remove('hidden');
        }
    } catch (error) {
        errorText.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง';
        errorMessage.classList.remove('hidden');
    } finally {
        // Hide loading
        submitBtn.disabled = false;
        btnText.classList.remove('hidden');
        btnLoading.classList.add('hidden');
    }
});

// Auto uppercase license key
document.getElementById('license_key').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>
@endsection
