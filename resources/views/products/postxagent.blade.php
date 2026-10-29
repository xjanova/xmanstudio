@extends($publicLayout ?? 'layouts.app')
{{-- PostXAgent — ยังเป็น Coming Soon: ไม่มีปุ่มดาวน์โหลดหรือปุ่มซื้อที่ใช้ได้ จนกว่าหลังร้านจะเปิดขาย (กฎเจ้าของ 2026-10-12, ComingSoonPurchase) --}}
@php $comingSoon = $product->isComingSoon(); @endphp

@section('title', 'PostXAgent — AI ช่วยโปรโมตแบรนด์ เขียนโพสต์ ตั้งเวลา โพสต์ให้เอง' . ($comingSoon ? ' · Coming Soon' : '') . ' | XMAN Studio')
@section('meta_description', 'PostXAgent โปรแกรม Windows ที่ใช้ AI เขียนแคปชันและแฮชแท็ก (Ollama ฟรีในเครื่อง หรือ Gemini / OpenAI / Claude ด้วย key ของคุณ) ตั้งเวลาโพสต์ล่วงหน้า แล้วโพสต์ขึ้น Facebook Page และ Instagram Business ให้ตามเวลา · An AI assistant that writes, schedules and publishes your brand\'s posts.')
@section('og_image', asset('images/products/postx-agent/v2/og.jpg'))

@section('content')
@php
    // ราคามาจากแถวสินค้า (products.price) ที่เดียว — ตะกร้าคิดตามนี้ ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    // PostXAgent ขายแบบราคาเดียว ไม่มีแผนรายเดือน/รายปีใน config/licenses.php (LicensePlans::for ว่าง — LicensePlansTest)
    $price = (float) $product->price;
    $priceText = number_format($price, 0);
    $hasDiscount = $product->original_price && $product->original_price > $product->price;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว)
    $latest = $product->latestVersion();

    // ภาพหน้าจอ: ยังไม่มีภาพจริงของโปรแกรม — ทุกภาพเป็น mockup ตามธีมของแอป (MyPostXAgent.UI)
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py postx-agent
    $shot = fn (string $file) => asset('images/products/postx-agent/v2/' . $file);

    $openDate = $product->coming_soon_until?->format('d/m/Y');

    // ทุกข้อเช็กกับโค้ดโปรแกรม Windows ใน repo PostXAgent (MyPostXAgent/src)
    $rows = [
        [
            'img' => $shot('content.webp'), 'mock' => true, 'name' => 'เขียนโพสต์ด้วย AI', 'eyebrow' => 'AI content generator',
            'th' => 'บอกหัวข้อ คีย์เวิร์ด ประเภทโพสต์ (โปรโมท เล่าเรื่อง รีวิวสินค้า ข่าวสาร Tips คำถาม/Poll แรงบันดาลใจ) และโทนเสียง แล้ว AI เขียนแคปชันพร้อมแฮชแท็กแนะนำให้ เลือกความยาว ภาษาไทย อังกฤษ หรือผสม และจะใส่อีโมจิหรือคำชวนให้กด (Call-to-Action) หรือไม่ก็ได้',
            'en' => 'Give it a topic, keywords, a post type and a tone; it writes the caption and suggests hashtags, in Thai, English or both.',
            'points' => [
                'Ollama ทำงานในเครื่องคุณเอง ไม่ต้องมี API key — เปิดโปรแกรมครั้งแรกจะถามว่าจะติดตั้ง Ollama และโมเดล llama3.2:3b ให้ไหม',
                'Gemini, OpenAI และ Claude ใช้ API key ของคุณเอง ใส่ในหน้า ตั้งค่า',
                'ถ้าตัวที่เลือกใช้ไม่ได้ ลองตัวถัดไปให้เอง: Ollama → Gemini → OpenAI → Claude',
            ],
            'alt' => 'ภาพตัวอย่างหน้าสร้างเนื้อหา AI ของ PostXAgent: เลือก AI ประเภทโพสต์ โทนเสียง และตัวอย่างแคปชันพร้อมแฮชแท็ก',
        ],
        [
            'img' => $shot('schedule.webp'), 'mock' => true, 'name' => 'ตั้งเวลา แล้วโพสต์ให้เอง', 'eyebrow' => 'Scheduler',
            'th' => 'เลือกโพสต์จาก Draft กำหนดวันและเวลา โปรแกรมตรวจคิวทุก 1 นาที แล้วโพสต์ขึ้น Facebook Page และ Instagram Business ให้เมื่อถึงเวลา เห็นได้ทันทีว่าโพสต์ไหนขึ้นแล้ว โพสต์ไหนรอคิว',
            'en' => 'Pick a draft, set a date and time; the queue is checked every minute and the post goes out on time.',
            'points' => [
                'โพสต์ผ่าน Graph API ของ Meta ด้วย access token ของบัญชีคุณ',
                'Instagram ต้องเป็นบัญชี Business ที่ผูกกับ Facebook Page และโพสต์ต้องมีรูป',
                'โพสต์ บัญชี และการตั้งค่าเก็บในฐานข้อมูลในเครื่อง',
            ],
            'alt' => 'ภาพตัวอย่างหน้าตั้งเวลาโพสต์ของ PostXAgent: ปฏิทิน รายการ Draft และโพสต์ที่ตั้งเวลาไว้ในวันนั้น',
        ],
    ];

    // ส่วนที่มีในโค้ดของระบบ PostXAgent แล้ว (AIManagerCore) แต่ยังไม่ได้รวมเข้าโปรแกรม Windows ที่จะขาย
    $engine = [
        [
            'key' => 'platforms', 'name' => '9 แพลตฟอร์ม', 'short' => 'TikTok · X · LINE · YouTube …', 'tier' => 'soon',
            'th' => 'ตัวโพสต์ของแต่ละแพลตฟอร์มในระบบเบื้องหลังมีครบ 9 ตัว: Facebook, Instagram, TikTok, X (Twitter), LINE, YouTube, Threads, LinkedIn และ Pinterest ตอนนี้โปรแกรม Windows โพสต์ได้เฉพาะ Facebook Page และ Instagram Business',
            'en' => 'Workers for nine networks exist in the engine. The Windows app posts to Facebook Pages and Instagram Business today.',
            'points' => [],
        ],
        [
            'key' => 'pool', 'name' => 'พูลบัญชี', 'short' => 'หมุนบัญชี · พักบัญชีที่มีปัญหา', 'tier' => 'soon',
            'th' => 'รวมหลายบัญชีของแบรนด์ไว้เป็นพูล แล้วเลือกบัญชีที่จะโพสต์ตามลำดับ (Round-robin) ตามที่ใช้น้อยที่สุด ตามลำดับความสำคัญ หรือสุ่ม บัญชีที่โดนจำกัดการโพสต์จะถูกข้ามจนกว่าจะหมดเวลาจำกัด',
            'en' => 'Several accounts per brand, picked round-robin, least-used, by priority or at random; rate-limited accounts are skipped until they reset.',
            'points' => ['บัญชีที่ล้มเหลวติดกันจะถูกพักอัตโนมัติ นานขึ้นทีละเท่าตัว สูงสุด 24 ชั่วโมง'],
        ],
        [
            'key' => 'teach', 'name' => 'สอนให้ทำบนหน้าเว็บ', 'short' => 'Teaching mode · ซ่อมขั้นตอนเอง', 'tier' => 'soon',
            'th' => 'สำหรับเว็บที่ไม่มี API: ทำให้ดูหนึ่งรอบ ระบบเรียนขั้นตอนเป็นเวิร์กโฟลว์ จำปุ่มและช่องด้วยหลายวิธี (CSS, XPath, ID, ข้อความ ฯลฯ) และเมื่อหน้าเว็บเปลี่ยนจนขั้นตอนเดิมใช้ไม่ได้ จะพยายามหาทางซ่อมขั้นตอนให้เอง',
            'en' => 'Show it once on a site with no API; it learns the steps, finds elements several ways and tries to repair the workflow when the page changes.',
            'points' => [],
        ],
        [
            'key' => 'images', 'name' => 'สร้างรูปประกอบ', 'short' => 'Stable Diffusion · Leonardo · DALL·E', 'tier' => 'soon',
            'th' => 'ตัวสร้างรูปประกอบโพสต์มีในโค้ดแล้ว ทั้ง Stable Diffusion ในเครื่อง, Leonardo และ DALL·E แต่ยังไม่มีหน้าจอให้ใช้ในโปรแกรม',
            'en' => 'Image generators (local Stable Diffusion, Leonardo, DALL·E) are in the code, not yet in the app\'s screens.',
            'points' => [],
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'PostXAgent',
        'operatingSystem' => 'Windows 10/11 (64-bit)',
        'applicationCategory' => 'BusinessApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'โปรแกรม Windows ที่ใช้ AI เขียนโพสต์ ตั้งเวลา และโพสต์ขึ้น Facebook Page และ Instagram Business ให้อัตโนมัติ',
        'image' => asset('images/products/postx-agent/v2/og.jpg'),
        'url' => route('products.show', $product->slug),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
    ];
    if (! $product->is_custom) {
        $ld['offers'] = ['@type' => 'Offer', 'price' => (string) $price, 'priceCurrency' => 'THB'];
    }
@endphp
<x-xp.page a="#38f0ff" b="#c084fc" c="#f0abfc" glow="139 92 246" g1="#00f5ff" g2="#8b5cf6" g3="#ec4899">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="AI ช่วยโปรโมตแบรนด์ เขียน ตั้งเวลา โพสต์ให้เอง"
               tagline-en="An AI assistant that writes your brand's posts, schedules them and publishes them for you."
               lead="เขียนแคปชันและแฮชแท็กด้วย AI ได้ทั้งแบบฟรีในเครื่อง (Ollama) หรือใช้ key ของ Gemini, OpenAI, Claude ของคุณเอง ตั้งเวลาไว้ล่วงหน้า แล้วโปรแกรมโพสต์ขึ้น Facebook Page และ Instagram Business ให้ตามเวลา"
               lead-en="Free local AI or your own Gemini, OpenAI or Claude key; scheduled posts to Facebook Pages and Instagram Business.">
        <x-slot:chips>
            @if($comingSoon)
                <x-xp.badge tier="soon" label="COMING SOON" />
            @endif
            <span class="xp-chip"><i></i>{{ $latest ? 'PostXAgent ' . $latest->version : 'PostXAgent' }}</span>
            <span class="xp-chip">Windows 10/11 · 64-bit</span>
        </x-slot:chips>
        <x-slot:title>Post<span class="xp-text-glow">X</span>Agent</x-slot:title>
        <x-slot:actions>
            @if($comingSoon)
                <x-xp.download :href="route('customer.downloads')" :coming-soon="true" />
                <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">แจ้งเตือนเมื่อเปิดขาย</a>
            @else
                @if(auth()->check() && $hasPurchased)
                    <x-xp.download :href="route('customer.downloads')" th="ดาวน์โหลด" en="Download" />
                @endif
                @unless($product->is_custom)
                    <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">ซื้อ License — ฿{{ $priceText }}</a>
                @else
                    <a href="{{ route('quote.index') }}" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">ติดต่อสอบถามราคา</a>
                @endunless
            @endif
        </x-slot:actions>
        <x-slot:notes>
            @if(session('success'))
                <p class="mt-4 text-sm text-emerald-200/90">{{ session('success') }}</p>
            @endif
            @if($comingSoon)
                <p class="mt-4 text-sm text-gray-400">
                    @if($openDate)เปิดขายวันที่ {{ $product->coming_soon_until->format('d/m/Y H:i') }}@else กำลังพัฒนา — ยังไม่เปิดขายและยังไม่เปิดให้ดาวน์โหลด@endif
                </p>
            @elseif($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ดาวน์โหลดได้หลังซื้อ จากหน้า ดาวน์โหลดของฉัน</p>
            @endif
            @if(isset($userLicense) && $userLicense)
                @if(! $userLicense->isValid())
                    <p class="mt-3 text-sm text-red-300">
                        License ของคุณหมดอายุแล้ว @if($userLicense->expires_at)เมื่อ {{ $userLicense->expires_at->format('d/m/Y') }}@endif ·
                        <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline hover:text-white">ต่ออายุตอนนี้</a>
                    </p>
                @elseif($userLicense->license_type !== 'lifetime' && $userLicense->daysRemaining() <= 7)
                    <p class="mt-3 text-sm text-amber-200">
                        License ใกล้หมดอายุ — เหลืออีก {{ $userLicense->daysRemaining() }} วัน ({{ $userLicense->expires_at->format('d/m/Y') }}) ·
                        <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline hover:text-white">ต่ออายุตอนนี้</a>
                    </p>
                @else
                    <p class="mt-3 text-sm text-emerald-200/90">
                        คุณมี License แล้ว · {{ $userLicense->license_type === 'lifetime' ? 'ตลอดชีพ' : 'หมดอายุ ' . $userLicense->expires_at->format('d/m/Y') }} ·
                        <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline decoration-emerald-300/50 hover:text-white">ดู License ของฉัน</a>
                    </p>
                @endif
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="soon" label="กำลังพัฒนา" /> ยังไม่เปิดขาย</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> AI ฟรีในเครื่องด้วย Ollama</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> 1 License = 1 เครื่อง</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('dashboard.webp')" :eager="true" :mock="true"
                             alt="ภาพตัวอย่างหน้าแดชบอร์ดของ PostXAgent: จำนวนบัญชี โพสต์วันนี้ โพสต์ที่รอคิว และสถานะ AI" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">Ollama · ในเครื่อง</p>
                <p class="text-white font-extrabold text-lg leading-tight">เขียนโพสต์ด้วย AI<br><span style="color: var(--xp-c)">ไม่ต้องมี API key</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">Scheduler</p>
                <p class="text-white font-bold leading-tight">ตรวจคิวทุก 1 นาที<br><span class="text-gray-400 text-sm font-normal">→ ถึงเวลาก็โพสต์ให้เอง</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== WINDOWS APP ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Windows app · in development" title="เขียน ตั้งเวลา" title2="แล้วปล่อยให้โพสต์เอง"
                       sub="งานโพสต์ประจำวันของแบรนด์ ตั้งแต่คิดแคปชันจนโพสต์ขึ้นเพจ ทำจบในโปรแกรมเดียวบน Windows"
                       en="From the caption to the published post, in one Windows app." />
            <x-xp.rows :items="$rows" />
        </div>
    </section>

    {{-- ============================ ENGINE (ยังไม่อยู่ในโปรแกรม) ============================ --}}
    <section class="relative py-20 isolate" id="engine">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="On the way · not in the app yet" title="กำลังมา" title2="ในรุ่นถัดไป"
                       sub="ส่วนเหล่านี้มีในโค้ดของระบบ PostXAgent แล้ว แต่ยังไม่ได้รวมเข้าโปรแกรม Windows — จะเปิดให้ใช้เมื่อพร้อมและทดสอบครบ"
                       en="Already in PostXAgent's engine code, not yet part of the Windows app." />
            <x-xp.explorer id="engine" label="ส่วนที่กำลังพัฒนาของ PostXAgent" :tools="$engine" />
        </div>
    </section>

    {{-- ============================ TRUST ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Your data, your keys" title="ข้อมูลอยู่ในเครื่อง" title2="key เป็นของคุณ"
                       sub="ไม่มีตัวกลางคิดค่า AI เพิ่ม และไม่ต้องย้ายข้อมูลแบรนด์ไปไว้ที่อื่น"
                       en="No middleman on AI costs, no brand data moved elsewhere." />
            <x-xp.cards :items="[
                ['icon' => 'chip', 'title' => 'AI ในเครื่องได้', 'body' => 'ใช้ Ollama สร้างเนื้อหาบนคอมของคุณเอง ไม่ต้องส่งหัวข้อไปที่ผู้ให้บริการ AI ภายนอก', 'en' => 'Local AI with Ollama'],
                ['icon' => 'key', 'title' => 'ใช้ API key ของคุณเอง', 'body' => 'Gemini, OpenAI และ Claude คิดค่าใช้งานกับบัญชีของคุณโดยตรง ใส่หรือไม่ใส่ก็ได้', 'en' => 'Your own provider keys, billed to you'],
                ['icon' => 'server', 'title' => 'ข้อมูลเก็บในเครื่อง', 'body' => 'โพสต์ บัญชี และการตั้งค่าอยู่ในฐานข้อมูล SQLite บนเครื่องที่ติดตั้ง', 'en' => 'Posts and settings in a local database'],
                ['icon' => 'shield', 'title' => 'โพสต์ผ่าน API ทางการ', 'body' => 'Facebook และ Instagram โพสต์ผ่าน Graph API ของ Meta ด้วย access token ของบัญชีคุณ', 'en' => 'Meta\'s Graph API with your access token'],
                ['icon' => 'lock', 'title' => '1 License ต่อ 1 เครื่อง', 'body' => 'License ผูกกับรหัสเครื่องที่คำนวณจากฮาร์ดแวร์ของคอมเครื่องนั้น', 'en' => 'Bound to the machine it is activated on'],
                ['icon' => 'globe', 'title' => 'ไทย / English', 'body' => 'สลับภาษาหน้าจอโปรแกรมได้จากปุ่มบนแถบด้านบน และเขียนโพสต์ได้ทั้งไทย อังกฤษ หรือผสม', 'en' => 'Thai or English, in the app and in posts'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="ราคาเดียว" title2="ไม่มีแพ็กเกจให้งง"
                       :sub="$comingSoon ? 'ยังไม่เปิดขาย — ราคาด้านล่างคือราคาที่ตั้งไว้ในร้านตอนนี้' : '1 License ใช้ได้ 1 เครื่อง · ได้คีย์ทางอีเมลและในหน้า บัญชีของฉัน'"
                       en="One price, one licence per machine." />

            <div class="max-w-md mx-auto">
                <div class="xp-featured">
                    <div class="p-7 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-2xl font-black text-white">PostXAgent License</h3>
                            @if($comingSoon)<x-xp.badge tier="soon" />@endif
                        </div>
                        <p class="text-amber-100/80 text-sm mb-6">โปรแกรม Windows · 1 License = 1 เครื่อง</p>
                        @if($product->is_custom)
                            <div class="mb-6"><span class="text-4xl font-black text-white">สอบถามราคา</span></div>
                        @else
                            <div class="mb-6 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <span class="text-5xl font-black text-white">฿{{ $priceText }}</span>
                                @if($hasDiscount)
                                    <span class="text-xl text-gray-500 line-through">฿{{ number_format($product->original_price, 0) }}</span>
                                    <span class="xp-badge xp-pro">-{{ round((1 - $product->price / $product->original_price) * 100) }}%</span>
                                @endif
                            </div>
                            <x-tpix-price variant="detail" :thb="$product->price" :tpix="$product->price_tpix" class="-mt-4 mb-6" />
                        @endif
                        <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="[
                            'เขียนโพสต์ด้วย AI: Ollama (ฟรีในเครื่อง) · Gemini · OpenAI · Claude',
                            'ตั้งเวลาโพสต์ Facebook Page + Instagram Business',
                            'แดชบอร์ด · Draft · ปฏิทินโพสต์',
                            '1 License ใช้ได้ 1 เครื่อง',
                        ]" />
                        @if($product->is_custom)
                            <a href="{{ route('quote.index') }}" class="xp-btn-pro block w-full py-3.5 text-center rounded-xl font-extrabold transition">ติดต่อสอบถามราคา</a>
                        @elseif($comingSoon)
                            <x-coming-soon-download :product="$product" label="Coming Soon — ยังไม่เปิดขาย" class="w-full" />
                            <a href="{{ route('quote.index') }}" class="xp-link block text-center text-sm mt-4">แจ้งเตือนเมื่อเปิดขาย →</a>
                        @else
                            {{-- ราคามาจาก products.price ไม่ใช่จากฟอร์ม — ตะกร้าคิดตามแถวสินค้า --}}
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="xp-btn-pro block w-full py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                    ซื้อ License — ฿{{ $priceText }}
                                </button>
                            </form>
                        @endif
                        @if(isset($userLicense) && $userLicense)
                            <a href="{{ route('customer.licenses.show', $userLicense) }}" class="xp-link block text-center text-sm mt-4">ดู License ของฉัน →</a>
                        @endif
                    </div>
                </div>
            </div>
            <p class="text-center text-xs text-gray-500 mt-6 max-w-2xl mx-auto">ค่าใช้งาน Gemini / OpenAI / Claude (ถ้าเลือกใช้) จ่ายกับผู้ให้บริการนั้นโดยตรง ไม่รวมในราคานี้ · Ollama ใช้ฟรีในเครื่อง</p>
        </div>
    </section>

    {{-- =========================== DOWNLOAD =========================== --}}
    @if($latest)
        <section class="relative py-16 scroll-mt-20" id="download">
            <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="xp-card p-7">
                    <div class="flex items-center justify-between gap-4 mb-5">
                        <div>
                            <p class="text-xs text-gray-400">เวอร์ชันล่าสุด · Latest</p>
                            <p class="text-2xl font-black text-white">v{{ $latest->version }}</p>
                        </div>
                        @if($latest->file_size)
                            <div class="text-right">
                                <p class="text-xs text-gray-400">ขนาดไฟล์</p>
                                <p class="text-lg font-bold text-white">{{ $latest->file_size_formatted }}</p>
                            </div>
                        @endif
                    </div>
                    @if($latest->changelog)
                        <p class="text-sm text-gray-300 whitespace-pre-wrap mb-6 rounded-lg bg-white/[.03] p-4">{{ \Illuminate\Support\Str::limit($latest->changelog, 200) }}</p>
                    @endif
                    @if($comingSoon)
                        <x-xp.download block :href="route('customer.downloads')" :coming-soon="true" />
                    @elseif(auth()->check() && $hasPurchased)
                        <x-xp.download block :href="route('customer.downloads')" th="ดาวน์โหลด" en="Download" />
                    @else
                        <a href="#pricing" class="xp-btn-ghost block w-full py-3.5 text-center rounded-xl font-bold transition">ซื้อ License ก่อน แล้วดาวน์โหลดได้จากหน้า ดาวน์โหลดของฉัน</a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows 10 หรือ 11 แบบ 64-bit', 'Windows 10/11, 64-bit'],
            ['ไม่ต้องติดตั้ง .NET', 'โปรแกรมมาเป็นไฟล์เดียวพร้อมทุกอย่างที่ต้องใช้', 'Self-contained single file'],
            ['บัญชีที่จะโพสต์', 'Facebook Page และ/หรือ Instagram Business ที่ผูกกับเพจ พร้อม access token', 'A Facebook Page / Instagram Business account'],
            ['อินเทอร์เน็ต', 'ใช้ตอนเปิดใช้งาน License ตอนโพสต์ และตอนใช้ AI ออนไลน์ (Ollama ทำงานในเครื่อง)', 'Online to activate, post and use cloud AI'],
        ]"
        specs-note="Ollama ใช้พื้นที่และแรมของเครื่องเพิ่มตามโมเดลที่เลือก — โมเดลเริ่มต้นคือ llama3.2:3b ไม่ติดตั้ง Ollama ก็ใช้ Gemini / OpenAI / Claude ด้วย key ของคุณแทนได้"
        :steps="[
            ['ซื้อ License เมื่อเปิดขาย', 'ได้ License key ทางอีเมลและในหน้า บัญชีของฉัน', 'Buy a licence once it is on sale'],
            ['ดาวน์โหลดแล้วเปิดโปรแกรม', 'ดาวน์โหลดจากหน้า ดาวน์โหลดของฉัน ใส่ License key แล้วเลือกว่าจะติดตั้ง Ollama ไหม', 'Download, open, enter your key'],
            ['เพิ่มบัญชี แล้วเริ่มโพสต์', 'เพิ่มบัญชี Facebook / Instagram ให้ AI เขียนโพสต์ แล้วตั้งเวลา', 'Add your accounts and schedule'],
        ]" />

    <x-xp.faq :items="[
        ['เปิดขายเมื่อไร?', $comingSoon
            ? ($openDate ? 'กำหนดเปิดขายวันที่ ' . $openDate . ' — ก่อนหน้านั้นยังซื้อและดาวน์โหลดไม่ได้' : 'ยังอยู่ระหว่างพัฒนา ยังไม่มีกำหนดวัน — กดแจ้งเตือนเมื่อเปิดขายไว้ได้ แล้วเราจะติดต่อกลับ')
            : 'เปิดขายแล้ว ซื้อได้จากส่วนราคาของหน้านี้'],
        ['ราคาเท่าไร?', $product->is_custom ? 'ติดต่อสอบถามราคาได้ที่หน้าใบเสนอราคา' : 'License ละ ฿' . $priceText . ' ใช้ได้ 1 เครื่อง — ค่าใช้งาน AI ออนไลน์ (ถ้าเลือกใช้) จ่ายกับผู้ให้บริการโดยตรง'],
        ['ต้องจ่ายค่า AI เพิ่มไหม?', 'ไม่จำเป็น Ollama ทำงานบนเครื่องคุณเองฟรี ถ้าอยากใช้ Gemini, OpenAI หรือ Claude ให้ใส่ API key ของคุณเอง ค่าใช้งานคิดกับบัญชีนั้นโดยตรง'],
        ['โพสต์ได้แพลตฟอร์มไหนบ้าง?', 'โปรแกรม Windows ตอนนี้โพสต์ขึ้น Facebook Page และ Instagram Business (ผ่าน Graph API) ตัวโพสต์ของ TikTok, X, LINE, YouTube, Threads, LinkedIn และ Pinterest มีในระบบเบื้องหลังแล้ว แต่ยังไม่ได้รวมเข้าโปรแกรม'],
        ['Instagram โพสต์แต่ข้อความได้ไหม?', 'ไม่ได้ Instagram ต้องมีรูปทุกโพสต์ และต้องเป็นบัญชี Business ที่ผูกกับ Facebook Page'],
        ['ใช้ได้กี่เครื่อง?', '1 License ใช้ได้ 1 เครื่อง License ผูกกับรหัสเครื่องที่คำนวณจากฮาร์ดแวร์ มีหลายเครื่องต้องใช้หลาย License'],
        ['ข้อมูลเก็บที่ไหน?', 'โพสต์ บัญชี และการตั้งค่าเก็บในฐานข้อมูลบนเครื่องของคุณ โปรแกรมติดต่อเซิร์ฟเวอร์ License, ผู้ให้บริการ AI ที่คุณเลือก และ Meta ตอนโพสต์เท่านั้น'],
    ]" />

    {{-- ======================== RELATED PRODUCTS ======================== --}}
    @if($relatedProducts && $relatedProducts->count() > 0)
        <section class="relative py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <x-xp.head class="mb-10" eyebrow="More from XMAN Studio" title="ผลิตภัณฑ์ที่เกี่ยวข้อง" size="md" />
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach($relatedProducts as $related)
                        <a href="{{ route('products.show', $related->slug) }}" class="xp-card overflow-hidden transition group">
                            <div class="aspect-video bg-white/[.03] overflow-hidden">
                                @if($related->artwork_url)
                                    <img src="{{ $related->artwork_url }}" alt="{{ $related->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-white mb-1">{{ $related->name }}</h3>
                                @if($related->short_description)
                                    <p class="text-gray-400 text-sm mb-2 line-clamp-2">{{ $related->short_description }}</p>
                                @endif
                                @if(! $related->is_custom)
                                    <p class="xp-link font-bold">฿{{ number_format($related->price, 0) }}</p>
                                    <x-tpix-price :thb="$related->price" :tpix="$related->price_tpix" />
                                @else
                                    <p class="text-gray-400 text-sm">สอบถามราคา</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="ให้ AI ช่วย" glow="โปรโมตแบรนด์"
              :sub="$comingSoon ? 'PostXAgent ยังเป็น Coming Soon — ยังไม่เปิดขายและดาวน์โหลด กดแจ้งเตือนไว้ แล้วเราจะบอกเมื่อพร้อม' : 'เขียน ตั้งเวลา แล้วปล่อยให้โปรแกรมโพสต์ให้ ในโปรแกรมเดียวบน Windows'"
              en="Let AI write and post for your brand.">
        @if($comingSoon)
            <x-xp.download :href="route('customer.downloads')" :coming-soon="true" class="!px-8" />
            <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-8 py-4 rounded-xl font-bold text-lg transition">แจ้งเตือนเมื่อเปิดขาย</a>
        @else
            @unless($product->is_custom)
                <a href="#pricing" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ซื้อ License — ฿{{ $priceText }}</a>
            @endunless
            <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-8 py-4 rounded-xl font-bold text-lg transition">ติดต่อสอบถาม</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10/11 (64-bit) · ไม่ต้องลง .NET · Ollama ฟรีในเครื่อง · 1 License = 1 เครื่อง</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
