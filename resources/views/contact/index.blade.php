@extends($publicLayout ?? 'layouts.app')

@section('title', 'ติดต่อเรา / Contact us - XMAN Studio')
@section('meta_description', 'ส่งข้อความถึงทีมงาน XMAN Studio สอบถามบริการ ขอคำปรึกษา หรือแจ้งปัญหาการใช้งาน')

@section('content')
<!-- Hero -->
<section class="relative bg-gradient-to-br from-gray-900 via-primary-900 to-gray-900 text-white py-20 overflow-hidden">
    <x-page-art art="team-cta" :opacity="45" fade="bottom" />
    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 to-transparent"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-2 bg-primary-600/30 text-primary-300 text-sm font-semibold rounded-full mb-6 backdrop-blur-sm border border-primary-500/30">
            ยินดีให้คำปรึกษา / We are here to help
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold mb-6">
            ติดต่อ<span class="text-primary-400">เรา</span>
        </h1>
        <p class="text-xl text-gray-300 max-w-3xl mx-auto">
            ส่งข้อความถึงทีมงานได้เลย เราจะตอบกลับทางอีเมลโดยเร็วที่สุด<br class="hidden md:block">
            <span class="text-gray-400 text-lg">Send us a message and we will reply by email as soon as we can.</span>
        </p>
        <p class="mt-8 text-sm text-gray-400">
            อยากได้ราคาโครงการ? ใช้
            <a href="{{ route('support.index') }}" class="text-primary-300 underline underline-offset-4 hover:text-primary-200">ระบบขอใบเสนอราคา</a>
            จะได้ตัวเลขทันที / Need a price? Use the quotation builder instead.
        </p>
    </div>
</section>

<section class="py-16 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-8">

            <!-- Form -->
            <div class="lg:col-span-2">
                @if(session('contact_success'))
                    <div class="mb-6 rounded-2xl border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/30 px-6 py-4 text-green-800 dark:text-green-300">
                        {{ session('contact_success') }}
                    </div>
                @endif

                @if(session('contact_error'))
                    <div class="mb-6 rounded-2xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-6 py-4 text-red-800 dark:text-red-300">
                        {{ session('contact_error') }}
                    </div>
                @endif

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">ส่งข้อความถึงเรา</h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8">Send us a message</p>

                    <form method="POST" action="{{ route('contact.send') }}" class="space-y-6">
                        @csrf

                        {{-- Honeypot: hidden from people, irresistible to bots. --}}
                        <div class="hidden" aria-hidden="true">
                            <label>Website<input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ชื่อ-นามสกุล / Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="120"
                                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all @error('name') border-red-500 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    อีเมล / Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="255"
                                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all @error('email') border-red-500 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    เบอร์โทร / Phone
                                </label>
                                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" maxlength="40"
                                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all @error('phone') border-red-500 @enderror">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    เรื่องที่ติดต่อ / Subject <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="200"
                                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all @error('subject') border-red-500 @enderror">
                                @error('subject')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ข้อความ / Message <span class="text-red-500">*</span>
                            </label>
                            <textarea id="message" name="message" rows="7" required minlength="10" maxlength="5000"
                                      placeholder="เล่ารายละเอียดที่อยากให้เราช่วยได้เลย"
                                      class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all @error('message') border-red-500 @enderror">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-turnstile section="contact" />

                        <button type="submit"
                                class="w-full md:w-auto px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl shadow-lg transition-all">
                            ส่งข้อความ / Send message
                        </button>
                    </form>
                </div>
            </div>

            <!-- Channels -->
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">ช่องทางอื่น</h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-6 text-sm">Other channels</p>

                    <ul class="space-y-4 text-sm">
                        @if($contact['phone'])
                            <li>
                                <div class="text-gray-500 dark:text-gray-400">โทรศัพท์ / Phone</div>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}"
                                   class="text-gray-900 dark:text-white font-semibold hover:text-primary-600 dark:hover:text-primary-400">
                                    {{ $contact['phone'] }}
                                </a>
                                @if($contact['phone_name'])
                                    <span class="text-gray-500 dark:text-gray-400">({{ $contact['phone_name'] }})</span>
                                @endif
                            </li>
                        @endif

                        @if($contact['email'])
                            <li>
                                <div class="text-gray-500 dark:text-gray-400">อีเมล / Email</div>
                                <a href="mailto:{{ $contact['email'] }}"
                                   class="text-gray-900 dark:text-white font-semibold hover:text-primary-600 dark:hover:text-primary-400 break-all">
                                    {{ $contact['email'] }}
                                </a>
                            </li>
                        @endif

                        @if($contact['line_id'] || $contact['line_url'])
                            <li>
                                <div class="text-gray-500 dark:text-gray-400">LINE</div>
                                @if($contact['line_url'])
                                    <a href="{{ $contact['line_url'] }}" target="_blank" rel="noopener noreferrer"
                                       class="text-gray-900 dark:text-white font-semibold hover:text-primary-600 dark:hover:text-primary-400">
                                        {{ $contact['line_id'] ?: 'เพิ่มเพื่อน' }}
                                    </a>
                                @else
                                    <span class="text-gray-900 dark:text-white font-semibold">{{ $contact['line_id'] }}</span>
                                @endif
                            </li>
                        @endif

                        @if($contact['facebook_url'])
                            <li>
                                <div class="text-gray-500 dark:text-gray-400">Facebook</div>
                                <a href="{{ $contact['facebook_url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="text-gray-900 dark:text-white font-semibold hover:text-primary-600 dark:hover:text-primary-400">
                                    {{ $contact['facebook_name'] ?: 'XMAN Studio' }}
                                </a>
                            </li>
                        @endif

                        @if($contact['youtube_url'])
                            <li>
                                <div class="text-gray-500 dark:text-gray-400">YouTube</div>
                                <a href="{{ $contact['youtube_url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="text-gray-900 dark:text-white font-semibold hover:text-primary-600 dark:hover:text-primary-400">
                                    {{ $contact['youtube_name'] ?: 'XMAN Studio' }}
                                </a>
                            </li>
                        @endif

                        @if($contact['address'])
                            <li>
                                <div class="text-gray-500 dark:text-gray-400">ที่อยู่ / Address</div>
                                <p class="text-gray-900 dark:text-white font-semibold">{{ $contact['address'] }}</p>
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-primary-600 to-primary-800 text-white rounded-2xl shadow-xl p-8">
                    <h3 class="text-lg font-bold mb-2">อยากรู้ราคาก่อน?</h3>
                    <p class="text-primary-100 text-sm mb-5">
                        เลือกบริการที่ต้องการแล้วรับใบเสนอราคาได้ทันที ไม่ต้องรอตอบกลับ<br>
                        <span class="text-primary-200">Get an instant quote without waiting for a reply.</span>
                    </p>
                    <a href="{{ route('support.index') }}"
                       class="inline-block px-6 py-2.5 bg-white text-primary-700 font-semibold rounded-xl hover:bg-primary-50 transition-all">
                        ขอใบเสนอราคา / Get a quote
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection
