@extends($publicLayout ?? 'layouts.app')

@section('title', 'เกี่ยวกับเรา - XMAN Studio')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-gray-900 via-primary-900 to-gray-900 text-white py-24 overflow-hidden">
    <x-page-art art="hero-about" :opacity="45" />
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-2 bg-primary-600/30 text-primary-300 text-sm font-semibold rounded-full mb-6 backdrop-blur-sm border border-primary-500/30">
            About Us
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold mb-6">เกี่ยวกับ XMAN Studio</h1>
        <p class="text-xl text-gray-300 max-w-3xl mx-auto">
            ผู้เชี่ยวชาญด้าน IT Solutions และ Software Development ครบวงจร
        </p>
    </div>
</section>

<!-- About Content -->
<section class="relative overflow-hidden py-20 bg-white dark:bg-gray-800">
    <x-page-art art="team-craft" :opacity="10" :scrim="false" />
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center tm-reveal">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                    พัฒนาเทคโนโลยี<br>
                    <span class="text-primary-600">สร้างอนาคต</span>
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-6">
                    XMAN Studio ก่อตั้งขึ้นด้วยความมุ่งมั่นที่จะนำเทคโนโลยีที่ทันสมัยมาช่วยแก้ปัญหาและสร้างมูลค่าให้กับธุรกิจ เราเชี่ยวชาญในการพัฒนาซอฟต์แวร์ที่ตอบโจทย์ทุกความต้องการ
                </p>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
                    ด้วยประสบการณ์กว่า 8 ปีในวงการ IT เราได้พัฒนาโซลูชั่นให้กับลูกค้ามากกว่า 150 โปรเจค ตั้งแต่ Startup จนถึงองค์กรขนาดใหญ่
                </p>

                <div class="grid grid-cols-2 gap-6">
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <div class="text-4xl font-bold text-primary-600 mb-2">8+</div>
                        <div class="text-gray-600 dark:text-gray-300">ปีประสบการณ์</div>
                    </div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <div class="text-4xl font-bold text-primary-600 mb-2">150+</div>
                        <div class="text-gray-600 dark:text-gray-300">โปรเจคสำเร็จ</div>
                    </div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <div class="text-4xl font-bold text-primary-600 mb-2">50+</div>
                        <div class="text-gray-600 dark:text-gray-300">ลูกค้าพึงพอใจ</div>
                    </div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <div class="text-4xl font-bold text-primary-600 mb-2">24/7</div>
                        <div class="text-gray-600 dark:text-gray-300">ซัพพอร์ต</div>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -inset-4 bg-gradient-to-r from-primary-600 to-purple-600 rounded-2xl opacity-20 blur-2xl"></div>
                <img src="{{ asset('artwork/card-studio.webp') }}" alt="สตูดิโอพัฒนาซอฟต์แวร์ของ XMAN Studio" loading="lazy" decoding="async" class="relative rounded-2xl shadow-2xl">
            </div>
        </div>
    </div>
</section>

<!-- Our Values -->
<section class="relative overflow-hidden py-20 bg-gray-50 dark:bg-gray-900">
    <x-page-art art="team-culture" :opacity="12" :scrim="false" />
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 tm-reveal">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">ค่านิยมของเรา</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300">หลักการที่เรายึดมั่นในการทำงาน</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 tm-reveal">
            <div class="p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg text-center">
                <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">นวัตกรรม</h3>
                <p class="text-gray-600 dark:text-gray-300">เราใช้เทคโนโลยีล่าสุดและนวัตกรรมใหม่ๆ เพื่อสร้างโซลูชั่นที่ดีที่สุดให้ลูกค้า</p>
            </div>

            <div class="p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ความน่าเชื่อถือ</h3>
                <p class="text-gray-600 dark:text-gray-300">เราให้ความสำคัญกับคุณภาพและความปลอดภัย ทุกโปรเจคผ่านการทดสอบอย่างเข้มงวด</p>
            </div>

            <div class="p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg text-center">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ทีมงานคุณภาพ</h3>
                <p class="text-gray-600 dark:text-gray-300">ทีมนักพัฒนามืออาชีพที่ผ่านการฝึกฝนและมีประสบการณ์ในงานระดับสากล</p>
            </div>
        </div>
    </div>
</section>

<!-- Services Overview -->
<section class="relative overflow-hidden py-20 bg-white dark:bg-gray-800">
    <x-page-art art="hero-services" :opacity="10" :scrim="false" />
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 tm-reveal">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">บริการของเรา</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300">เราให้บริการครบวงจรด้านเทคโนโลยีสารสนเทศ</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-4">🔗</div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">Blockchain</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">Smart Contract, DeFi, NFT</p>
            </div>
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-4">🌐</div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">Web Development</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">เว็บไซต์, E-commerce, CMS</p>
            </div>
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-4">📱</div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">Mobile App</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">iOS, Android, Flutter</p>
            </div>
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-4">🤖</div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">AI Solutions</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">Chatbot, AI Video, AI Music</p>
            </div>
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-4">⚡</div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">IoT Solutions</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">Smart Home, Smart Farm</p>
            </div>
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-4">🔒</div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">IT Security</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">Network, Firewall, Pentest</p>
            </div>
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-4">💻</div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">Custom Software</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">ERP, CRM, POS</p>
            </div>
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-xl hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-4">📲</div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">Flutter Training</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">อบรม, Workshop, Mentoring</p>
            </div>
        </div>
    </div>
</section>

<!-- Numbers -->
<section class="relative overflow-hidden py-20 bg-gray-950">
    <x-page-art art="hero-network" :opacity="20" :scrim="false" />
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 tm-reveal">
            <span class="inline-block px-3 py-1 bg-primary-500/15 text-primary-300 text-xs font-bold uppercase tracking-wider rounded-full mb-4">By the numbers</span>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">ตัวเลขที่เดินมาด้วยกัน</h2>
            <p class="text-lg text-gray-400">สะสมจากงานจริงตลอดเส้นทางที่ผ่านมา</p>
        </div>

        @php
            $aboutStats = [
                ['n' => 8,   'suffix' => '+',   'th' => 'ปีประสบการณ์',   'en' => 'Years'],
                ['n' => 150, 'suffix' => '+',   'th' => 'โปรเจคสำเร็จ',   'en' => 'Projects'],
                ['n' => 50,  'suffix' => '+',   'th' => 'ลูกค้าพึงพอใจ',  'en' => 'Clients'],
                ['n' => 24,  'suffix' => '/7',  'th' => 'ดูแลต่อเนื่อง',  'en' => 'Support'],
            ];
        @endphp

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($aboutStats as $stat)
                <div class="relative rounded-2xl p-7 text-center bg-white/[0.04] ring-1 ring-white/10 backdrop-blur tm-reveal"
                     style="transition-delay: {{ $loop->index * 0.08 }}s;">
                    <div class="text-4xl md:text-5xl font-black mb-2 bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text text-transparent"
                         data-count="{{ $stat['n'] }}" data-count-suffix="{{ $stat['suffix'] }}">{{ $stat['n'] }}{{ $stat['suffix'] }}</div>
                    <div class="text-sm font-semibold text-gray-200">{{ $stat['th'] }}</div>
                    <div class="text-[0.65rem] uppercase tracking-[0.2em] text-gray-500 mt-0.5">{{ $stat['en'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- What the work looks like -->
<section class="relative overflow-hidden py-20 bg-white dark:bg-gray-800">
    <x-page-art art="team-journey" :opacity="10" :scrim="false" />
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 tm-reveal">
            <span class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-bold uppercase tracking-wider rounded-full mb-4">The craft</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">งานของเราหน้าตาเป็นแบบไหน</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">เขียนโค้ด วางสถาปัตยกรรม และดูแลโครงสร้างพื้นฐาน — สามอย่างนี้อยู่เบื้องหลังทุกงานที่ส่งมอบ</p>
        </div>

        @php
            $aboutCraft = [
                ['art' => 'space-planning', 'th' => 'วางระบบก่อนลงมือ', 'desc' => 'ออกแบบโครงสร้างข้อมูลและ flow ให้จบก่อน จะได้ไม่ต้องรื้อกลางทาง'],
                ['art' => 'space-studio',   'th' => 'พัฒนาและทดสอบ',    'desc' => 'ทยอยส่งให้ดูเป็นช่วงๆ แก้ไปพร้อมกัน ไม่หายไปแล้วโผล่ตอนจบ'],
                ['art' => 'space-infra',    'th' => 'ดูแลหลังส่งมอบ',   'desc' => 'เซิร์ฟเวอร์ ความปลอดภัย และการมอนิเตอร์ที่ทำให้ระบบอยู่ได้ยาว'],
            ];
        @endphp

        <div class="grid md:grid-cols-3 gap-6">
            @foreach($aboutCraft as $item)
                <figure class="group relative rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-white/10 tm-reveal"
                        style="transition-delay: {{ $loop->index * 0.1 }}s;">
                    <img src="{{ asset('artwork/' . $item['art'] . '.webp') }}" alt=""
                         width="1400" height="787" loading="lazy" decoding="async"
                         class="w-full aspect-[16/10] object-cover transition-transform duration-700 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/45 to-transparent"></div>
                    <figcaption class="absolute bottom-0 left-0 right-0 p-5">
                        <span class="block text-lg font-bold text-white mb-1">{{ $item['th'] }}</span>
                        <span class="block text-sm text-gray-300 leading-relaxed">{{ $item['desc'] }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
<!-- CTA Section -->
<section class="relative overflow-hidden py-20 bg-gradient-to-r from-primary-600 to-purple-700">
    <x-page-art art="team-cta" :opacity="20" :scrim="false" />
    <div class="relative max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">พร้อมเริ่มโปรเจคของคุณ?</h2>
        <p class="text-xl text-primary-100 mb-8">ติดต่อเราวันนี้เพื่อรับคำปรึกษาฟรี!</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="/support" class="inline-flex items-center px-8 py-4 bg-white text-primary-700 font-bold text-lg rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                ติดต่อเรา
            </a>
            <a href="/portfolio" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white font-bold text-lg rounded-xl transition-all duration-300 hover:bg-white/10">
                ดูผลงาน
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>

@include('partials.reveal-on-scroll')
@endsection
