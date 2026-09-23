@extends($publicLayout ?? 'layouts.app')

@section('title', 'เช่า VPS — เซิร์ฟเวอร์ส่วนตัว แรง เสถียร | XMAN Studio')
@section('meta_description', 'เช่า VPS เซิร์ฟเวอร์ส่วนตัว ได้สิทธิ์ root เต็ม ดิสก์ NVMe SSD สำรองข้อมูลอัตโนมัติทุกสัปดาห์ เลือกระบบปฏิบัติการได้กว่า 90 แบบ ศูนย์ข้อมูลใกล้ไทย ทีมงานคนไทยดูแล บอกราคาต่ออายุชัดเจนตั้งแต่ก่อนซื้อ')

@section('content')
{{--
    หน้าเช่า VPS — แพ็กเกจและราคา

    ราคาทุกตัวจัดรูปแบบมาจากเซิร์ฟเวอร์แล้ว (VpsController::card) Alpine แค่
    สลับว่าโชว์รอบบิลไหน ไม่คิดเงินเอง ทุกรอบบิลของทุกการ์ดเรนเดอร์ไว้ครบ
    ปุ่มเช่าจึงเป็นลิงก์จริงที่มี ?period= ติดมาจากฝั่งเซิร์ฟเวอร์

    กฎราคาของเจ้าของ: ราคารอบแรกต้องเขียนกำกับว่าเป็น "รอบแรก" เสมอ และต้อง
    บอกราคารอบถัดไป (ต่ออายุ) ทุกครั้ง เพราะรอบแรกเป็นราคาโปร รอบต่อไปแพงกว่า
    มาก — from_monthly จึงไม่ถูกใช้ในหน้านี้ มันคือราคาโปรหารเดือนที่ไม่มีราคา
    ต่ออายุกำกับ

    ห้ามเอ่ยชื่อผู้ให้บริการต้นทางที่ใดในหน้านี้
--}}
@php
    $periodMeta = \App\Support\VpsPricing::PERIODS;

    // ป้ายของราคารอบแรก / รอบถัดไป ตามรอบบิล
    $periodCopy = [
        '1m' => ['first_th' => 'เดือนแรก', 'first_en' => 'First month', 'next_th' => 'เดือนถัดไป'],
        '1y' => ['first_th' => 'ปีแรก', 'first_en' => 'First year', 'next_th' => 'ปีต่อไป'],
        '2y' => ['first_th' => '2 ปีแรก', 'first_en' => 'First 2 years', 'next_th' => '2 ปีต่อไป'],
    ];
    $fallbackCopy = ['first_th' => 'รอบแรก', 'first_en' => 'First term', 'next_th' => 'รอบถัดไป'];

    // ตัวสลับแสดงเฉพาะรอบบิลที่มีอย่างน้อยหนึ่งแพ็กเกจขายจริง
    $availablePeriods = collect(array_keys($periodMeta))
        ->filter(fn ($p) => $cards->contains(fn ($c) => ! empty($c['prices'][$p])))
        ->values();

    // ?period= ทำให้การรีโหลดและปุ่มย้อนกลับกลับมาที่รอบบิลเดิม ไม่เด้งกลับรายเดือน
    $requestedPeriod = request()->query('period');
    $defaultPeriod = is_string($requestedPeriod) && in_array($requestedPeriod, $availablePeriods->all(), true)
        ? $requestedPeriod
        : ($availablePeriods->first() ?? '1m');

    $sections = collect([
        ['key' => 'vps', 'cards' => $cards->filter(fn ($c) => ($c['category'] ?? 'vps') !== 'game')->values()],
        ['key' => 'game', 'cards' => $cards->filter(fn ($c) => ($c['category'] ?? 'vps') === 'game')->values()],
    ])->filter(fn ($s) => $s['cards']->isNotEmpty())->values();

    $anyDearer = $cards->contains(fn ($c) => collect($c['prices'] ?? [])->contains('dearer', true));

    // แคตตาล็อกบอกขนาดเป็น MB ค่าที่ไม่รู้จะออกมาเป็น "0 MB" — ไม่โฆษณาตัวเลขนั้น
    $known = fn ($label) => is_string($label) && $label !== '' && ! str_starts_with($label, '0 ');

    // จำนวนวันอ่านจากหลังบ้าน เขียนตายไว้จะกลายเป็นคำสัญญาที่ผิดทันทีที่มีคนแก้ค่า
    $chargeDays = \App\Support\VpsSettings::chargeDays();
    $noticeDays = \App\Support\VpsSettings::noticeDays();

    $features = [
        ['icon' => 'root', 'th' => 'สิทธิ์ root เต็มรูปแบบ', 'en' => 'Full root access',
         'body_th' => 'ติดตั้งและตั้งค่าได้ทุกอย่างเหมือนเครื่องของคุณเอง เข้าผ่าน SSH ด้วยรหัสผ่านหรือ SSH key',
         'body_en' => 'Install and configure anything, as if the machine sat on your desk. SSH in with a password or a key.'],
        ['icon' => 'nvme', 'th' => 'ดิสก์ NVMe SSD', 'en' => 'NVMe SSD storage',
         'body_th' => 'อ่านเขียนเร็วกว่า SSD ทั่วไปหลายเท่า เว็บ ฐานข้อมูล และแอปตอบสนองไว',
         'body_en' => 'Several times faster than ordinary SSDs, so sites, databases and apps respond quickly.'],
        ['icon' => 'backup', 'th' => 'สำรองข้อมูลอัตโนมัติ + สแนปช็อต', 'en' => 'Automatic backups + snapshots',
         'body_th' => 'สำรองให้ทุกสัปดาห์โดยไม่ต้องตั้งค่า และกดทำสแนปช็อตเองได้ก่อนแก้ระบบ พลาดเมื่อไหร่ก็ย้อนกลับได้',
         'body_en' => 'Weekly backups out of the box, plus snapshots you take yourself before a risky change.'],
        ['icon' => 'os', 'th' => 'เลือก OS และแอปได้กว่า 90 แบบ', 'en' => '90+ OS and app templates',
         'body_th' => 'Ubuntu, Debian, AlmaLinux, Docker, n8n, WordPress, CloudPanel และอีกมาก ติดตั้งให้พร้อมใช้ตั้งแต่เปิดเครื่อง',
         'body_en' => 'Ubuntu, Debian, AlmaLinux, Docker, n8n, WordPress, CloudPanel and more — ready the moment the server boots.'],
        ['icon' => 'location', 'th' => 'ศูนย์ข้อมูลใกล้ไทย', 'en' => 'Data centres near Thailand',
         'body_th' => 'ตั้งเครื่องที่มาเลเซีย ความหน่วงต่ำสำหรับผู้ใช้ในไทย หรือเลือกยุโรปและอเมริกาสำหรับลูกค้าต่างประเทศ',
         'body_en' => 'Malaysia for low latency to visitors in Thailand, or Europe and the Americas for customers abroad.'],
        ['icon' => 'support', 'th' => 'ทีมงานคนไทย + จัดการเองได้', 'en' => 'Thai team + self-service',
         'body_th' => 'เปิด ปิด รีสตาร์ท ติดตั้งใหม่ และชี้โดเมนได้เองจากหน้าเว็บ ติดตรงไหนทักทีมงานคนไทยได้เลย',
         'body_en' => 'Start, stop, restart, reinstall and point a domain from your dashboard — with a Thai team when you need one.'],
    ];

    $faqs = [
        [
            'q_th' => 'ชำระเงินอย่างไร?', 'q_en' => 'How do I pay?',
            'a_th' => 'ชำระจากกระเป๋าเงินในเว็บ — เติมเงินเข้ากระเป๋าก่อน แล้วสั่งเช่าได้ทันที ถ้าเปิดต่ออายุอัตโนมัติไว้ เราแจ้งยอดล่วงหน้า ' . $noticeDays . ' วัน แล้วตัดจากกระเป๋าก่อนหมดอายุ ' . $chargeDays . ' วัน ถ้าปิดไว้ เราส่งอีเมลเตือนให้ต่ออายุเองก่อนถึงกำหนด',
            'a_en' => "From your site wallet: top up, then order. With auto-renew on, we announce each renewal {$noticeDays} days ahead and charge the wallet {$chargeDays} days before expiry. With it off, we email you a reminder to renew.",
        ],
        [
            'q_th' => 'ทำไมราคาเดือนแรกกับเดือนถัดไปไม่เท่ากัน?', 'q_en' => 'Why does the first month cost less than the next?',
            'a_th' => 'ราคารอบแรกเป็นราคาโปรโมชันสำหรับการเช่าครั้งแรก รอบถัดไปคิดตามราคาปกติ เราจึงแสดงทั้งสองราคาบนทุกแพ็กเกจตั้งแต่ก่อนซื้อ ตัวเลขสีเหลืองคือราคารอบถัดไปที่สูงกว่ารอบแรก จะได้วางแผนค่าใช้จ่ายได้ ไม่มีเซอร์ไพรส์ตอนต่ออายุ',
            'a_en' => 'The first term is an introductory price; renewals are charged at the regular rate. Every plan shows both before you buy — amber marks a renewal that costs more — so nothing surprises you later.',
        ],
        [
            'q_th' => 'ได้สิทธิ์ root ไหม?', 'q_en' => 'Do I get root access?',
            'a_th' => 'ได้เต็มสิทธิ์ คุณตั้งรหัสผ่าน root เอง (หรือใส่ SSH key) ตอนสั่งเช่า แล้วเข้าผ่าน SSH ได้ทันทีที่เครื่องพร้อม เราไม่เก็บรหัสผ่านนั้นไว้ และไม่ส่งทางอีเมล',
            'a_en' => "Yes, full root. You set the password (or an SSH key) when you order and log in over SSH as soon as the server is up. We don't keep that password or email it.",
        ],
        [
            'q_th' => 'ถ้าไม่ต่ออายุจะเกิดอะไรขึ้น?', 'q_en' => "What happens if I don't renew?",
            'a_th' => 'เมื่อหมดอายุ เซิร์ฟเวอร์จะถูกระงับการใช้งาน ช่วงแรกยังต่ออายุได้จากหน้าเซิร์ฟเวอร์ แต่ถ้าปล่อยไว้นาน ข้อมูลในเครื่องจะถูกลบถาวร เราเตือนทางอีเมลก่อนหมดอายุเสมอ',
            'a_en' => 'The server is suspended when it expires. You can still renew it for a while, but left too long its data is deleted for good. We always email you before it expires.',
        ],
        [
            'q_th' => 'เปลี่ยนระบบปฏิบัติการทีหลังได้ไหม?', 'q_en' => 'Can I change the OS later?',
            'a_th' => 'ได้ กดติดตั้งระบบใหม่ได้เองจากหน้าเซิร์ฟเวอร์ เลือก OS หรือแอปตัวอื่นได้ตลอด แต่การติดตั้งใหม่จะล้างข้อมูลทั้งหมดในดิสก์ ควรทำสแนปช็อตไว้ก่อน',
            'a_en' => 'Yes — reinstall from your server page whenever you like. Reinstalling wipes the disk, so take a snapshot first.',
        ],
        [
            'q_th' => 'ใช้กับโดเมนของเราได้ไหม?', 'q_en' => 'Can I use my own domain?',
            'a_th' => 'ได้ ถ้าโดเมนจดกับเรา กดปุ่ม “ชี้โดเมน” ในหน้าเซิร์ฟเวอร์ ระบบตั้ง DNS ให้ทั้งโดเมนหลักและ www ถ้าโดเมนอยู่ที่อื่น ตั้ง A record มาที่ IP ของเครื่องได้เลย',
            'a_en' => "Yes. A domain registered with us points at your server with one button (the bare domain and www). For one held elsewhere, add an A record for the server's IP.",
            'link' => ['href' => route('domains.index'), 'th' => 'ยังไม่มีโดเมน? ค้นหาและจดได้ที่นี่', 'en' => 'No domain yet? Find one'],
        ],
    ];
@endphp

{{-- ══════════ HERO ══════════ --}}
<section class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white overflow-hidden">
    <x-page-art art="hero-vps" :opacity="45" :scrim="false" fade="bottom" />

    <div class="absolute inset-0 opacity-40 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-20 left-1/4 w-72 h-72 bg-indigo-500 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
        <div class="absolute -bottom-24 right-1/4 w-72 h-72 bg-cyan-500 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 3s;"></div>
    </div>
    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/30 to-transparent pointer-events-none" aria-hidden="true"></div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
        <span class="inline-block px-4 py-1.5 bg-indigo-600/30 text-indigo-200 text-xs font-semibold rounded-full mb-5 backdrop-blur-sm border border-indigo-400/30 tracking-[0.2em] uppercase">
            VPS
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold mb-4 leading-tight">
            {{-- mb_chr(160) คือช่องว่างห้ามตัดบรรทัด — กัน "10" ค้างท้ายบรรทัดแล้ว "นาที" ตกไปอยู่บรรทัดใหม่คำเดียว --}}
            <x-bi :th="'เซิร์ฟเวอร์ส่วนตัว แรง เสถียร พร้อมใช้ใน 10' . mb_chr(160) . 'นาที'" en="Your own server — fast, stable, ready in minutes" layout="stack" />
        </h1>
        <p class="text-slate-300 text-base sm:text-lg max-w-2xl mx-auto">
            <x-bi th="ได้สิทธิ์ root เต็ม ดิสก์ NVMe สำรองข้อมูลอัตโนมัติทุกสัปดาห์ และทีมงานคนไทยคอยดูแลตลอดอายุการเช่า"
                  en="Full root access, NVMe storage, automatic weekly backups — and a Thai team looking after you for as long as you rent." />
        </p>

        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="#plans"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-gradient-to-r from-indigo-500 to-cyan-500 text-white font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:scale-[1.02] active:scale-[0.99] transition">
                <x-bi th="ดูแพ็กเกจและราคา" en="See plans" />
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            </a>
            @auth
                <a href="{{ route('customer.vps.index') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/20 backdrop-blur-sm text-white font-semibold transition">
                    <x-bi th="เซิร์ฟเวอร์ของฉัน" en="My servers" />
                </a>
            @endauth
        </div>
    </div>
</section>

{{-- ══════════ แพ็กเกจ ══════════ --}}
<section id="plans" class="bg-gray-50 dark:bg-slate-900 scroll-mt-20" x-data="vpsPlanPicker(@js(['period' => $defaultPeriod]))">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">

        @if($sections->isEmpty())
            {{-- ยังไม่มีแพ็กเกจที่ขายได้ --}}
            <div class="text-center py-16 max-w-md mx-auto">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="6" rx="2" stroke-width="1.8"/><rect x="3" y="14" width="18" height="6" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M7 7h.01M7 17h.01"/></svg>
                </div>
                <p class="text-slate-700 dark:text-slate-200 font-semibold mb-2">
                    <x-bi th="กำลังเตรียมแพ็กเกจ" en="Plans are on their way" />
                </p>
                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    เรากำลังจัดเตรียมแพ็กเกจเซิร์ฟเวอร์ กลับมาดูใหม่เร็ว ๆ นี้ หรือติดต่อทีมงานหากต้องการใช้งานด่วน
                </p>
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500 leading-relaxed">
                    We're lining up our server plans — check back soon, or contact us if you need one now.
                </p>
                <a href="{{ route('contact.show') }}"
                   class="mt-6 inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-sm transition">
                    <x-bi th="ติดต่อทีมงาน" en="Contact us" />
                </a>
            </div>
        @else
            @unless($salesOpen)
                <div class="max-w-3xl mx-auto mb-10 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-900 dark:text-amber-200 p-5 flex items-start gap-3" role="status">
                    <svg class="w-6 h-6 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div class="min-w-0">
                        <p class="font-semibold">
                            <x-bi th="ขณะนี้ยังไม่เปิดรับคำสั่งเช่า" en="Not taking new orders right now" />
                        </p>
                        <p class="mt-1 text-sm text-amber-800 dark:text-amber-300/90">
                            <x-bi th="ดูสเปกและราคาได้ตามปกติ หากต้องการเซิร์ฟเวอร์ด่วน ติดต่อทีมงานได้เลย"
                                  en="Browse the plans and prices as usual. Need a server soon? Get in touch." />
                        </p>
                        <a href="{{ route('contact.show') }}" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-amber-800 dark:text-amber-200 underline underline-offset-2 hover:no-underline">
                            <x-bi th="ติดต่อทีมงาน" en="Contact us" /> →
                        </a>
                    </div>
                </div>
            @endunless

            @foreach($sections as $section)
                <div class="{{ $loop->first ? '' : 'mt-16 sm:mt-20 pt-12 sm:pt-16 border-t border-slate-200 dark:border-slate-800' }}">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                            @if($section['key'] === 'game')
                                <x-bi th="เซิร์ฟเวอร์เกม" en="Game servers" layout="stack" />
                            @else
                                <x-bi th="เลือกแพ็กเกจที่ใช่" en="Pick your plan" layout="stack" />
                            @endif
                        </h2>
                        <p class="mt-3 text-sm sm:text-base text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                            @if($section['key'] === 'game')
                                <x-bi th="สเปกเดียวกับ VPS มาพร้อมแผงจัดการเซิร์ฟเวอร์เกม"
                                      en="The same hardware as our VPS, with a game-server panel on top." />
                            @else
                                <x-bi th="ทุกแพ็กเกจบอกทั้งราคารอบแรก และราคารอบถัดไป (ต่ออายุ) ก่อนซื้อ ชำระจากกระเป๋าเงินในเว็บ"
                                      en="Every plan shows its first-term price and what each renewal costs, before you buy. Paid from your site wallet." />
                            @endif
                        </p>
                    </div>

                    {{-- ตัวสลับรอบบิล — ทุกส่วนใช้สถานะเดียวกัน จึงแสดงซ้ำเหนือทุกกริด --}}
                    @if($availablePeriods->count() > 1)
                        <div class="flex justify-center mb-10">
                            <div class="flex w-full sm:w-auto p-1 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm"
                                 role="group" aria-label="รอบบิล / Billing period">
                                @foreach($availablePeriods as $p)
                                    <button type="button"
                                            @click="period = @js($p)"
                                            :aria-pressed="period === @js($p)"
                                            :class="period === @js($p)
                                                ? 'bg-gradient-to-r from-indigo-500 to-cyan-500 text-white shadow-md shadow-indigo-500/20'
                                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50'"
                                            class="flex-1 sm:flex-none px-3 sm:px-6 py-2 rounded-lg text-sm font-semibold transition">
                                        <x-bi :th="$periodMeta[$p]['th']" :en="$periodMeta[$p]['en']" layout="stack" />
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                        @foreach($section['cards'] as $card)
                            @php $featured = ! empty($card['featured']); @endphp
                            <article class="relative flex flex-col rounded-2xl bg-white dark:bg-slate-800 p-6 transition hover:-translate-y-0.5 {{ $featured ? 'border-2 border-indigo-400 dark:border-indigo-500/70 shadow-xl shadow-indigo-500/10' : 'border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-lg' }}">
                                @if($featured)
                                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gradient-to-r from-indigo-500 to-cyan-500 text-white text-[11px] font-bold shadow-lg shadow-indigo-500/30 whitespace-nowrap">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
                                        <x-bi th="แนะนำ" en="Popular" />
                                    </span>
                                @endif

                                <h3 class="text-lg font-bold text-slate-900 dark:text-white break-words">{{ $card['name'] }}</h3>
                                @if(filled($card['description_th'] ?? null))
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{{ $card['description_th'] }}</p>
                                @endif
                                @if(filled($card['description_en'] ?? null))
                                    <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500 leading-relaxed">{{ $card['description_en'] }}</p>
                                @endif

                                {{-- สเปก --}}
                                <ul class="mt-5 space-y-2.5 text-sm text-slate-600 dark:text-slate-300 flex-1">
                                    <li class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4 shrink-0 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="1.5" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/></svg>
                                        <span><span class="font-semibold text-slate-900 dark:text-white">{{ $card['cpus'] }}</span> vCPU</span>
                                    </li>
                                    @if($known($card['memory'] ?? null))
                                        <li class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 shrink-0 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="10" rx="1.5" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M7 11h2M11 11h2M15 11h2M6 17v2M18 17v2"/></svg>
                                            <span><span class="font-semibold text-slate-900 dark:text-white">{{ $card['memory'] }}</span> RAM</span>
                                        </li>
                                    @endif
                                    @if($known($card['disk'] ?? null))
                                        <li class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 shrink-0 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><ellipse cx="12" cy="6" rx="8" ry="3" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M4 6v12c0 1.66 3.58 3 8 3s8-1.34 8-3V6M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/></svg>
                                            <span><span class="font-semibold text-slate-900 dark:text-white">{{ $card['disk'] }}</span> NVMe SSD</span>
                                        </li>
                                    @endif
                                    @if($known($card['bandwidth'] ?? null))
                                        <li class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 shrink-0 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 20V4m0 0L3 8m4-4l4 4m6-4v16m0 0l-4-4m4 4l4-4"/></svg>
                                            <span><span class="font-semibold text-slate-900 dark:text-white">{{ $card['bandwidth'] }}</span> <x-bi th="แบนด์วิดท์" en="bandwidth" /></span>
                                        </li>
                                    @endif
                                    @if(! empty($card['network']))
                                        <li class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 shrink-0 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 13l3.5-3.5M4.2 17a9 9 0 1115.6 0"/></svg>
                                            <span><span class="font-semibold text-slate-900 dark:text-white">{{ $card['network'] }} Mbps</span> <x-bi th="เครือข่าย" en="network" /></span>
                                        </li>
                                    @endif
                                </ul>

                                {{-- ราคา — หนึ่งบล็อกต่อรอบบิล Alpine เลือกโชว์ทีละบล็อก
                                     บล็อกของรอบเริ่มต้นไม่มี x-cloak จึงเห็นราคาตั้งแต่ก่อน JS โหลด --}}
                                <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-700">
                                    @foreach($availablePeriods as $p)
                                        @php
                                            $price = $card['prices'][$p] ?? null;
                                            $copy = $periodCopy[$p] ?? $fallbackCopy;
                                            $multiMonth = $price && (int) ($price['months'] ?? 1) > 1;
                                        @endphp
                                        <div x-show="period === @js($p)" @if($p !== $defaultPeriod) x-cloak @endif>
                                            @if($price)
                                                <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                                    <x-bi :th="$copy['first_th']" :en="$copy['first_en']" />
                                                </p>
                                                <p class="mt-0.5 text-3xl font-bold text-slate-900 dark:text-white">{{ $price['first'] }}</p>
                                                @if($multiMonth)
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                                        <x-bi th="เฉลี่ย" en="avg." /> <span class="whitespace-nowrap">{{ $price['first_monthly'] }}</span>/<x-bi th="เดือน" en="mo" />
                                                    </p>
                                                @endif

                                                {{-- ราคารอบถัดไปแสดงเสมอ ไม่ใช่เฉพาะตอนแพงกว่า --}}
                                                <p class="mt-2.5 text-sm text-slate-500 dark:text-slate-400">
                                                    <x-bi :th="$copy['next_th']" en="then" />
                                                    <span class="font-semibold whitespace-nowrap {{ ! empty($price['dearer']) ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-slate-100' }}">{{ $price['renew'] }}</span>/<x-bi :th="$periodMeta[$p]['unit_th']" :en="$periodMeta[$p]['unit_en']" />
                                                    @if($multiMonth)
                                                        <span class="block text-xs">
                                                            <x-bi th="เฉลี่ย" en="avg." /> <span class="whitespace-nowrap">{{ $price['renew_monthly'] }}</span>/<x-bi th="เดือน" en="mo" />
                                                        </span>
                                                    @endif
                                                </p>

                                                @if($salesOpen)
                                                    <a href="{{ $card['order_url'] }}?period={{ urlencode($p) }}"
                                                       class="mt-5 w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold transition {{ $featured ? 'bg-gradient-to-r from-indigo-500 to-cyan-500 text-white shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:scale-[1.02] active:scale-[0.99]' : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm hover:shadow-md' }}">
                                                        <x-bi th="เช่าเลย" en="Rent now" />
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                                    </a>
                                                @else
                                                    <p class="mt-5 w-full px-5 py-3 rounded-xl bg-slate-100 dark:bg-slate-700/60 text-slate-500 dark:text-slate-400 text-sm font-semibold text-center">
                                                        <x-bi th="ยังไม่เปิดรับคำสั่งเช่า" en="Not open for orders" />
                                                    </p>
                                                @endif
                                            @else
                                                <div class="py-5 text-center">
                                                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                                        <x-bi th="ไม่มีรอบบิลนี้" en="Not offered for this period" />
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                                                        <x-bi th="เลือกรอบบิลอื่นด้านบน" en="Pick another billing period above" />
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if($anyDearer)
                <p class="mt-10 max-w-3xl mx-auto text-xs text-slate-500 dark:text-slate-400 flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 8h.01M11 12h1v4h1"/>
                    </svg>
                    <span>
                        <x-bi th="ตัวเลขสีเหลืองคือราคารอบถัดไปที่สูงกว่ารอบแรก — รอบแรกเป็นราคาโปรโมชัน เราแสดงไว้ตั้งแต่ก่อนซื้อเพื่อให้คุณวางแผนค่าใช้จ่ายได้"
                              en="Amber figures renew for more than the first term, which is promotional. We show both before you buy so there are no surprises." />
                    </span>
                </p>
            @endif

            @guest
                <p class="mt-4 text-xs text-center text-slate-500 dark:text-slate-400">
                    <x-bi th="ต้องเข้าสู่ระบบก่อนสั่งเช่า — ค่าเช่าชำระจากกระเป๋าเงินในเว็บ" en="Sign in to order — rent is paid from your site wallet." />
                </p>
            @endguest
        @endif
    </div>
</section>

{{-- ══════════ จุดเด่น ══════════ --}}
<section class="bg-white dark:bg-slate-950">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                <x-bi th="ทุกเครื่องได้สิ่งเหล่านี้" en="Every server comes with" layout="stack" />
            </h2>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($features as $f)
                <div class="rounded-2xl bg-gray-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center mb-3 shadow-lg shadow-indigo-500/20">
                        @if($f['icon'] === 'root')
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 9l3 3-3 3M13 15h4"/></svg>
                        @elseif($f['icon'] === 'nvme')
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 3L4 14h7l-1 7 9-11h-7l1-7z"/></svg>
                        @elseif($f['icon'] === 'backup')
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12a9 9 0 103-6.7L3 8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3v5h5M12 8v4l3 2"/></svg>
                        @elseif($f['icon'] === 'os')
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l9 5-9 5-9-5 9-5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13l9 5 9-5"/></svg>
                        @elseif($f['icon'] === 'location')
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s-7-6.1-7-11a7 7 0 1114 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5" stroke-width="1.8"/></svg>
                        @else
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 14v-2a8 8 0 1116 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5" stroke-width="1.8"/><rect x="17" y="14" width="4" height="6" rx="1.5" stroke-width="1.8"/></svg>
                        @endif
                    </div>
                    <p class="font-semibold text-slate-900 dark:text-white mb-1.5">
                        <x-bi :th="$f['th']" :en="$f['en']" />
                    </p>
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ $f['body_th'] }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $f['body_en'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ คำถามที่พบบ่อย ══════════ --}}
<section class="bg-gray-50 dark:bg-slate-900">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20">
        <h2 class="text-center text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
            <x-bi th="คำถามที่พบบ่อย" en="Common questions" layout="stack" />
        </h2>

        <div class="mt-10 space-y-3" x-data="{ open: 0 }">
            @foreach($faqs as $i => $faq)
                <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <h3>
                        <button type="button"
                                id="vps-faq-q-{{ $i }}"
                                @click="open = open === @js($i) ? null : @js($i)"
                                :aria-expanded="open === @js($i)"
                                aria-controls="vps-faq-a-{{ $i }}"
                                class="w-full flex items-center justify-between gap-4 text-left px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition">
                            <span class="font-semibold text-slate-900 dark:text-white">
                                <x-bi :th="$faq['q_th']" :en="$faq['q_en']" layout="stack" />
                            </span>
                            <svg class="w-5 h-5 shrink-0 text-slate-400 transition-transform duration-200" :class="open === @js($i) ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </h3>
                    <div id="vps-faq-a-{{ $i }}" role="region" aria-labelledby="vps-faq-q-{{ $i }}"
                         x-show="open === @js($i)" x-transition.opacity @if($i !== 0) x-cloak @endif
                         class="px-5 pb-5">
                        <p class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed">{{ $faq['a_th'] }}</p>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $faq['a_en'] }}</p>
                        @if(! empty($faq['link']))
                            <a href="{{ $faq['link']['href'] }}" class="mt-3 inline-block text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                <x-bi :th="$faq['link']['th']" :en="$faq['link']['en']" /> →
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-8 text-center text-sm text-slate-600 dark:text-slate-400">
            <x-bi th="มีคำถามอื่น?" en="Something else?" />
            <a href="{{ route('contact.show') }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                <x-bi th="ติดต่อทีมงาน" en="Contact us" /> →
            </a>
        </p>
    </div>
</section>
@endsection

@push('scripts')
<script>
function vpsPlanPicker(config) {
    return {
        period: config.period,

        // เก็บรอบบิลที่เลือกไว้ใน URL — รีโหลดหรือกดย้อนกลับจากหน้าสั่งเช่า
        // จะเจอราคาชุดเดิม ไม่เด้งกลับไปรายเดือน
        init() {
            this.$watch('period', (value) => {
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('period', value);
                    window.history.replaceState(window.history.state, '', url);
                } catch (e) { /* ตัวสลับยังทำงานได้แม้บันทึก URL ไม่ได้ */ }
            });
        },
    };
}
</script>
@endpush
