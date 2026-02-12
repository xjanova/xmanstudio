@extends('layouts.app')

@section('title', 'สถานะการเติมเงิน')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Gradient Header Banner -->
    <div class="bg-gradient-to-r from-purple-600 via-purple-500 to-indigo-600 dark:from-purple-700 dark:via-purple-600 dark:to-indigo-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <nav class="flex items-center text-purple-200 text-sm mb-2">
                        <a href="{{ route('user.wallet.index') }}" class="hover:text-white transition-colors duration-200">กระเป๋าเงิน</a>
                        <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-white">{{ $topup->topup_id }}</span>
                    </nav>
                    <h1 class="text-3xl font-bold text-white flex items-center">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        สถานะการเติมเงิน
                    </h1>
                    <p class="mt-1 text-purple-100">กรุณาโอนเงินตามยอดที่แสดงด้านล่าง</p>
                </div>
                <a href="{{ route('user.wallet.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white/10 text-white font-semibold rounded-xl hover:bg-white/20 transition-all duration-200 backdrop-blur-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    กลับ
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Status Banner -->
        @if($topup->status === 'pending')
            @if($uniqueAmount && $uniqueAmount->isExpired())
            <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-6 mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-amber-100 dark:bg-amber-800 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200">ยอดชำระหมดอายุแล้ว</h3>
                        <p class="text-amber-700 dark:text-amber-300">กรุณากดปุ่มสร้างยอดใหม่เพื่อรับยอดโอนที่ใช้ได้</p>
                    </div>
                    <form action="{{ route('user.wallet.regenerate-amount', $topup) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-xl transition-colors duration-200">
                            สร้างยอดใหม่
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-2xl p-6 mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-800 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">รอรับการโอนเงิน</h3>
                        <p class="text-blue-700 dark:text-blue-300">กรุณาโอนเงินภายใน <span class="font-bold" id="countdown">{{ $uniqueAmount ? $uniqueAmount->expires_at->diffForHumans() : '30 นาที' }}</span></p>
                    </div>
                </div>
            </div>
            @endif
        @elseif($topup->status === 'approved')
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-2xl p-6 mb-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-800 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">การเติมเงินสำเร็จ!</h3>
                    <p class="text-green-700 dark:text-green-300">ยอดเงินได้ถูกเพิ่มเข้ากระเป๋าของคุณแล้ว</p>
                </div>
            </div>
        </div>
        @elseif($topup->status === 'rejected')
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-2xl p-6 mb-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-800 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">รายการถูกปฏิเสธ</h3>
                    @if($topup->reject_reason)
                    <p class="text-red-700 dark:text-red-300">เหตุผล: {{ $topup->reject_reason }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Payment Amount Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        ยอดที่ต้องโอน
                    </h3>
                </div>
                <div class="p-6">
                    @if($topup->status === 'pending' && $uniqueAmount && !$uniqueAmount->isExpired())
                    <!-- Unique Amount Display -->
                    <div class="text-center mb-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">โอนเงินจำนวน</p>
                        <div class="bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/50 dark:to-emerald-900/50 rounded-2xl p-6 border-2 border-green-300 dark:border-green-700">
                            <p class="text-5xl font-bold text-green-600 dark:text-green-400">
                                {{ number_format($uniqueAmount->unique_amount, 2) }}
                            </p>
                            <p class="text-green-700 dark:text-green-300 mt-2">บาท</p>
                        </div>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-3 flex items-center justify-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            โอนตรงตามยอดนี้ ระบบจะตรวจสอบอัตโนมัติ
                        </p>
                    </div>
                    @else
                    <div class="text-center mb-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">ยอดเติมเงิน</p>
                        <p class="text-4xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($topup->amount, 2) }} <span class="text-xl">บาท</span>
                        </p>
                    </div>
                    @endif

                    <!-- Topup Details -->
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">ยอดเติมเงิน</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($topup->amount, 2) }} บาท</span>
                        </div>
                        @if($topup->bonus_amount > 0)
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">โบนัส</span>
                            <span class="font-medium text-green-600 dark:text-green-400">+{{ number_format($topup->bonus_amount, 2) }} บาท</span>
                        </div>
                        @endif
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">ยอดรวมที่จะได้รับ</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400">{{ number_format($topup->total_amount, 2) }} บาท</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-500 dark:text-gray-400">ช่องทางชำระ</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $topup->payment_method_label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20">
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        ข้อมูลการโอนเงิน
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    @if($bankAccounts->count() > 0)
                        @foreach($bankAccounts as $bank)
                        <!-- Bank Account: {{ $bank->bank_name }} -->
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-green-800 dark:text-green-200">{{ $bank->bank_name }}</h4>
                            </div>
                            <div class="space-y-2 text-sm">
                                <p class="text-gray-600 dark:text-gray-400"><span class="font-medium text-gray-800 dark:text-gray-200">ชื่อบัญชี:</span> {{ $bank->account_name }}</p>
                                <p class="text-gray-600 dark:text-gray-400"><span class="font-medium text-gray-800 dark:text-gray-200">เลขบัญชี:</span> <code class="px-2 py-1 bg-white dark:bg-gray-700 rounded text-purple-600 dark:text-purple-400 font-mono">{{ $bank->account_number }}</code></p>
                                @if($bank->branch)
                                <p class="text-gray-600 dark:text-gray-400"><span class="font-medium text-gray-800 dark:text-gray-200">สาขา:</span> {{ $bank->branch }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @endif

                    @if($promptpayNumber)
                    <!-- PromptPay -->
                    <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3 shadow-lg overflow-hidden bg-white">
                                <img src="https://www.bot.or.th/content/dam/bot/icons/icon-promptpay.png" alt="PromptPay" class="w-8 h-8 object-contain" onerror="this.style.display='none';this.parentElement.classList.add('bg-gradient-to-br','from-purple-500','to-indigo-500');this.parentElement.innerHTML='<svg class=\'w-5 h-5 text-white\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z\'/></svg>'">
                            </div>
                            <h4 class="font-semibold text-purple-800 dark:text-purple-200">PromptPay</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p class="text-gray-600 dark:text-gray-400"><span class="font-medium text-gray-800 dark:text-gray-200">หมายเลข:</span> <code class="px-2 py-1 bg-white dark:bg-gray-700 rounded text-purple-600 dark:text-purple-400 font-mono">{{ $promptpayNumber }}</code></p>
                        </div>
                    </div>
                    @endif

                    <!-- Important Notice -->
                    <div class="flex items-start p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                        <svg class="w-5 h-5 text-amber-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-amber-800 dark:text-amber-200">
                            <p class="font-medium mb-1">ระบบตรวจสอบอัตโนมัติ</p>
                            <p class="text-amber-700 dark:text-amber-300">โอนเงินตรงตามยอดที่แสดง ระบบจะตรวจสอบและเติมเงินให้อัตโนมัติ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex flex-col sm:flex-row gap-4 justify-center">
            @if($topup->status === 'pending')
                @if($uniqueAmount && $uniqueAmount->isExpired())
                <form action="{{ route('user.wallet.regenerate-amount', $topup) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        สร้างยอดชำระใหม่
                    </button>
                </form>
                @endif
            <form action="{{ route('user.wallet.cancel-topup', $topup) }}" method="POST" onsubmit="return confirm('ยกเลิกรายการเติมเงินนี้?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full sm:w-auto px-8 py-4 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-200">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    ยกเลิกรายการ
                </button>
            </form>
            @endif
            <a href="{{ route('user.wallet.index') }}" class="w-full sm:w-auto px-8 py-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 text-center">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับหน้ากระเป๋าเงิน
            </a>
        </div>
    </div>
</div>

@if($topup->status === 'pending' && $uniqueAmount && !$uniqueAmount->isExpired())
@push('scripts')
<script>
// AJAX polling every 5 seconds — check topup status without full page reload
(function() {
    var checkUrl = '{{ route("user.wallet.check-topup-status", $topup) }}';
    var walletUrl = '{{ route("user.wallet.index") }}';
    var polling = setInterval(function() {
        fetch(checkUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'approved') {
                clearInterval(polling);
                // Show success message and redirect to wallet
                document.querySelector('.max-w-4xl').innerHTML =
                    '<div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-2xl p-8 text-center">' +
                    '<svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' +
                    '<h2 class="text-2xl font-bold text-green-800 dark:text-green-200 mb-2">เติมเงินสำเร็จ!</h2>' +
                    '<p class="text-green-700 dark:text-green-300 mb-4">ยอดเงินได้ถูกเพิ่มเข้ากระเป๋าของคุณแล้ว</p>' +
                    '<p class="text-sm text-gray-500">กำลังนำคุณไปหน้ากระเป๋าเงิน...</p>' +
                    '</div>';
                setTimeout(function() { window.location.href = walletUrl; }, 2000);
            } else if (data.status === 'rejected') {
                clearInterval(polling);
                location.reload();
            }
        })
        .catch(function() { /* retry on next interval */ });
    }, 5000);

    // Fallback: full page reload every 60 seconds
    setTimeout(function() { location.reload(); }, 60000);
})();

// Countdown timer
@if($uniqueAmount)
(function() {
    const expiresAt = new Date('{{ $uniqueAmount->expires_at->utc()->toIso8601String() }}');
    const countdownEl = document.getElementById('countdown');

    function updateCountdown() {
        const now = new Date();
        const diff = expiresAt - now;

        if (diff <= 0) {
            location.reload();
            return;
        }

        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);

        countdownEl.textContent = minutes + ' นาที ' + seconds + ' วินาที';
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
})();
@endif
</script>
@endpush
@endif
@endsection
