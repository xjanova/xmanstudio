<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>บริจาคสนับสนุน Aipray | XMAN Studio</title>
    <meta name="description" content="บริจาคสนับสนุนการพัฒนา Aipray แอปสวดมนต์อัจฉริยะ">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .gold-gradient { background: linear-gradient(135deg, #D4A647 0%, #F5D78E 50%, #D4A647 100%); }
        .gold-text { color: #D4A647; }
        .gold-border { border-color: #D4A647; }
        .gold-bg { background-color: #D4A647; }
        .gold-bg-soft { background-color: rgba(212, 166, 71, 0.1); }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased">

    {{-- Header --}}
    <header class="max-w-2xl mx-auto px-4 pt-10 pb-6 text-center">
        <a href="{{ route('aipray.show') }}" class="inline-flex items-center text-gray-400 hover:gold-text transition-colors mb-8 text-sm">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            กลับหน้า Aipray
        </a>
        <div class="mx-auto w-20 h-20 rounded-2xl gold-gradient flex items-center justify-center shadow-xl mb-6">
            <span class="text-4xl">🙏</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold mb-2">
            บริจาคสนับสนุน <span class="gold-text">Aipray</span>
        </h1>
        <p class="text-gray-400">ทุกบาทช่วยให้เราพัฒนาแอปสวดมนต์ให้ดียิ่งขึ้น</p>
    </header>

    <main class="max-w-2xl mx-auto px-4 pb-16">

        {{-- Success: Show QR Code --}}
        @if(!empty($success) && !empty($qr))
        <div class="bg-gray-800 rounded-2xl border border-gray-700 p-6 sm:p-8 mb-8 text-center">
            <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="text-xl font-bold gold-text mb-2">สแกนเพื่อบริจาค</h2>
            <p class="text-gray-400 text-sm mb-6">
                จำนวน <span class="gold-text font-semibold text-lg">{{ number_format($amount ?? 0) }}</span> บาท
            </p>

            {{-- QR Code --}}
            <div class="bg-white rounded-2xl p-6 inline-block mx-auto mb-6">
                <div class="w-64 h-64 sm:w-72 sm:h-72 mx-auto flex items-center justify-center">
                    {!! $qr !!}
                </div>
            </div>

            <p class="text-gray-500 text-sm mb-6">สแกน QR Code ด้วยแอปธนาคารของคุณ (PromptPay)</p>

            <a href="{{ route('aipray.donate') }}"
               class="inline-flex items-center px-6 py-3 bg-gray-700 hover:bg-gray-600 text-gray-200 font-medium rounded-xl transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                บริจาคอีกครั้ง
            </a>
        </div>
        @else

        {{-- Donation Form --}}
        <form action="{{ route('aipray.donate.store') }}" method="POST"
              class="bg-gray-800 rounded-2xl border border-gray-700 p-6 sm:p-8 mb-8"
              id="donationForm">
            @csrf

            {{-- Validation Errors --}}
            @if($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6">
                    <ul class="text-red-400 text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-1.5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Amount Selection --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-3">จำนวนเงิน (บาท) <span class="text-red-400">*</span></label>
                <div class="grid grid-cols-4 gap-3 mb-3">
                    @foreach([20, 50, 100, 500] as $preset)
                        <button type="button"
                                onclick="selectAmount({{ $preset }})"
                                class="amount-btn px-4 py-3 rounded-xl border border-gray-600 text-gray-200 font-semibold text-center hover:border-[#D4A647] hover:gold-text transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#D4A647] focus:ring-offset-2 focus:ring-offset-gray-800"
                                data-amount="{{ $preset }}">
                            {{ $preset }}
                        </button>
                    @endforeach
                </div>
                <div class="relative">
                    <input type="number"
                           name="amount"
                           id="amountInput"
                           value="{{ old('amount') }}"
                           min="1"
                           max="100000"
                           step="1"
                           placeholder="หรือใส่จำนวนเอง..."
                           required
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#D4A647] focus:border-transparent transition-colors">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">บาท</span>
                </div>
            </div>

            {{-- Donor Name --}}
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">ชื่อผู้บริจาค (ไม่จำเป็น)</label>
                <input type="text"
                       name="donor_name"
                       id="donor_name"
                       value="{{ old('donor_name') }}"
                       maxlength="255"
                       placeholder="ชื่อของคุณ"
                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#D4A647] focus:border-transparent transition-colors">
            </div>

            {{-- Message --}}
            <div class="mb-6">
                <label for="message" class="block text-sm font-medium text-gray-300 mb-2">ข้อความ (ไม่จำเป็น)</label>
                <textarea name="message"
                          id="message"
                          rows="3"
                          maxlength="500"
                          placeholder="ฝากข้อความถึงทีมพัฒนา..."
                          class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#D4A647] focus:border-transparent transition-colors resize-none">{{ old('message') }}</textarea>
            </div>

            {{-- Anonymous Checkbox --}}
            <div class="mb-8">
                <label class="flex items-center cursor-pointer group">
                    <input type="checkbox"
                           name="is_anonymous"
                           value="1"
                           {{ old('is_anonymous') ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-gray-600 bg-gray-700 text-[#D4A647] focus:ring-[#D4A647] focus:ring-offset-gray-800">
                    <span class="ml-3 text-sm text-gray-300 group-hover:text-gray-200 transition-colors">ไม่ประสงค์ออกนาม</span>
                </label>
            </div>

            {{-- Submit Button --}}
            <button type="submit"
                    class="w-full gold-gradient text-gray-900 font-bold text-lg py-4 rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                บริจาค
            </button>
        </form>
        @endif

        {{-- Recent Donations --}}
        @if(!empty($donations) && $donations->count() > 0)
        <div class="bg-gray-800 rounded-2xl border border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="font-semibold gold-text">ผู้บริจาคล่าสุด</h3>
            </div>
            <div class="divide-y divide-gray-700">
                @foreach($donations->take(15) as $donation)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center min-w-0">
                            <div class="w-9 h-9 rounded-full gold-bg-soft flex items-center justify-center flex-shrink-0">
                                <span class="gold-text text-sm font-bold">{{ mb_substr($donation->display_name, 0, 1) }}</span>
                            </div>
                            <div class="ml-3 min-w-0">
                                <p class="text-gray-200 text-sm font-medium truncate">{{ $donation->display_name }}</p>
                                @if(!empty($donation->message))
                                    <p class="text-gray-500 text-xs truncate">{{ $donation->message }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-4">
                            <p class="gold-text font-semibold text-sm">{{ number_format($donation->amount) }} ฿</p>
                            <p class="text-gray-500 text-xs">{{ $donation->created_at ? $donation->created_at->diffForHumans() : '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-800 py-8">
        <div class="max-w-2xl mx-auto px-4 text-center">
            <p class="gold-text font-semibold mb-1">ใช้งานฟรีตลอดไป - Free Forever</p>
            <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} XMAN Studio</p>
        </div>
    </footer>

    <script>
        function selectAmount(amount) {
            document.getElementById('amountInput').value = amount;
            document.querySelectorAll('.amount-btn').forEach(btn => {
                const isSelected = parseInt(btn.dataset.amount) === amount;
                btn.classList.toggle('border-[#D4A647]', isSelected);
                btn.classList.toggle('gold-text', isSelected);
                btn.classList.toggle('gold-bg-soft', isSelected);
                btn.classList.toggle('border-gray-600', !isSelected);
                btn.classList.toggle('text-gray-200', !isSelected);
            });
        }

        // Sync preset buttons when custom input changes
        document.getElementById('amountInput').addEventListener('input', function() {
            const val = parseInt(this.value);
            document.querySelectorAll('.amount-btn').forEach(btn => {
                const isSelected = parseInt(btn.dataset.amount) === val;
                btn.classList.toggle('border-[#D4A647]', isSelected);
                btn.classList.toggle('gold-text', isSelected);
                btn.classList.toggle('gold-bg-soft', isSelected);
                btn.classList.toggle('border-gray-600', !isSelected);
                btn.classList.toggle('text-gray-200', !isSelected);
            });
        });

        // Form validation
        document.getElementById('donationForm')?.addEventListener('submit', function(e) {
            const amount = parseInt(document.getElementById('amountInput').value);
            if (!amount || amount < 1) {
                e.preventDefault();
                alert('กรุณาระบุจำนวนเงินที่ต้องการบริจาค');
                return false;
            }
            if (amount > 100000) {
                e.preventDefault();
                alert('จำนวนเงินสูงสุด 100,000 บาท');
                return false;
            }
        });

        // Pre-select old value
        @if(old('amount'))
            selectAmount({{ (int) old('amount') }});
        @endif
    </script>
</body>
</html>
