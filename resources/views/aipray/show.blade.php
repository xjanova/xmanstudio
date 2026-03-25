<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Aipray - สวดมนต์อัจฉริยะ | XMAN Studio</title>
    <meta name="description" content="Aipray - แอปสวดมนต์อัจฉริยะ พร้อม AI ช่วยนำสวด ใช้งานฟรีตลอดไป">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .gold-gradient { background: linear-gradient(135deg, #D4A647 0%, #F5D78E 50%, #D4A647 100%); }
        .gold-text { color: #D4A647; }
        .gold-border { border-color: #D4A647; }
        .gold-bg { background-color: #D4A647; }
        .gold-bg-soft { background-color: rgba(212, 166, 71, 0.1); }
        .gold-bg-hover:hover { background-color: rgba(212, 166, 71, 0.2); }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased">

    {{-- Hero Section --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-800 to-gray-900"></div>
        <div class="relative max-w-4xl mx-auto px-4 py-16 sm:py-24 text-center">
            {{-- App Icon --}}
            <div class="mx-auto w-24 h-24 sm:w-32 sm:h-32 rounded-3xl gold-gradient flex items-center justify-center shadow-2xl mb-8">
                <span class="text-5xl sm:text-6xl">🙏</span>
            </div>

            {{-- Title --}}
            <h1 class="text-3xl sm:text-5xl font-bold mb-3">
                <span class="gold-text">Aipray</span> - สวดมนต์อัจฉริยะ
            </h1>
            <p class="text-gray-400 text-lg sm:text-xl mb-6">พัฒนาโดย XMAN Studio</p>

            {{-- Version Badge --}}
            @if(!empty($version))
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium gold-bg-soft gold-text gold-border border mb-8">
                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    v{{ $version }}
                </span>
            @endif

            {{-- Download Button --}}
            <div class="mt-4">
                <a href="{{ $downloadUrl }}"
                   class="inline-flex items-center px-8 py-4 gold-gradient text-gray-900 font-bold text-lg rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    ดาวน์โหลด APK
                </a>
                <p class="text-gray-500 text-sm mt-3">Android 5.0 ขึ้นไป</p>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    @if(!empty($product) && !empty($product->features) && is_array($product->features))
    <section class="max-w-4xl mx-auto px-4 py-16">
        <h2 class="text-2xl sm:text-3xl font-bold text-center mb-12">
            <span class="gold-text">คุณสมบัติ</span>เด่น
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($product->features as $feature)
                <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700 hover:border-[#D4A647] transition-colors duration-200">
                    <div class="w-10 h-10 rounded-xl gold-bg-soft flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 gold-text" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </div>
                    <p class="text-gray-200 font-medium">{{ $feature }}</p>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Changelog Section --}}
    @if(!empty($changelog))
    <section class="max-w-4xl mx-auto px-4 py-16">
        <h2 class="text-2xl sm:text-3xl font-bold text-center mb-12">
            <span class="gold-text">บันทึก</span>การเปลี่ยนแปลง
        </h2>
        <div class="bg-gray-800 rounded-2xl p-6 sm:p-8 border border-gray-700">
            <div class="prose prose-invert prose-sm sm:prose-base max-w-none
                        prose-headings:gold-text prose-a:gold-text prose-strong:text-gray-100
                        prose-ul:text-gray-300 prose-li:text-gray-300">
                {!! $changelog !!}
            </div>
        </div>
    </section>
    @endif

    {{-- Donation Section --}}
    <section class="max-w-4xl mx-auto px-4 py-16">
        <h2 class="text-2xl sm:text-3xl font-bold text-center mb-4">
            <span class="gold-text">สนับสนุน</span>การพัฒนา
        </h2>
        <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">
            Aipray เป็นแอปฟรีตลอดไป การบริจาคของคุณช่วยให้เราพัฒนาฟีเจอร์ใหม่ ๆ ได้
        </p>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-4 sm:gap-6 mb-10 max-w-lg mx-auto">
            <div class="bg-gray-800 rounded-2xl p-6 text-center border border-gray-700">
                <p class="text-2xl sm:text-3xl font-bold gold-text">
                    {{ number_format($donationStats['total_amount'] ?? 0) }}
                </p>
                <p class="text-gray-400 text-sm mt-1">บาท</p>
            </div>
            <div class="bg-gray-800 rounded-2xl p-6 text-center border border-gray-700">
                <p class="text-2xl sm:text-3xl font-bold gold-text">
                    {{ number_format($donationStats['total_donors'] ?? 0) }}
                </p>
                <p class="text-gray-400 text-sm mt-1">ผู้บริจาค</p>
            </div>
        </div>

        {{-- Recent Donors --}}
        @if(!empty($donations) && $donations->count() > 0)
        <div class="bg-gray-800 rounded-2xl border border-gray-700 mb-10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="font-semibold gold-text">ผู้บริจาคล่าสุด</h3>
            </div>
            <div class="divide-y divide-gray-700">
                @foreach($donations->take(10) as $donation)
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

        {{-- Donate Button --}}
        <div class="text-center">
            <a href="{{ route('aipray.donate') }}"
               class="inline-flex items-center px-8 py-4 gold-gradient text-gray-900 font-bold text-lg rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                บริจาค
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-gray-800 py-12">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <p class="gold-text font-semibold text-lg mb-2">ใช้งานฟรีตลอดไป - Free Forever</p>
            <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} XMAN Studio. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
