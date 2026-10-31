@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('giggok'); @endphp

@section('title', 'GigGok — เลขาสาว 3D บนมือถือ Android · ใช้ฟรี ดาวน์โหลด APK | XMAN Studio')
@section('meta_description', 'GigGok เลขาสาว 3D บนมือถือ Android คุยด้วยเสียง ตอบเป็นเสียง ดูปฏิทิน จดบันทึก และรับสายแทนได้ เลือกสมองได้ 4 แบบ รวม Gemma 4 ที่คิดในเครื่องแบบออฟไลน์ ใช้ฟรีทั้งแอป ไม่ต้องสมัคร · A free 3D AI secretary for Android.')
@section('og_image', asset('images/products/giggok/v2/og.jpg'))

@section('content')
@php
    // ตัวแอปฟรีทั้งหมด (ราคา 0 ทุกเครื่องได้ license FREE อัตโนมัติจาก check-machine — ProductLicenseController::FREEMIUM_PRODUCTS)
    // ไม่มีตะกร้า ฿0 · ของที่ขายคือชุดตัวมายด์ (หมวด giggok-packs ซื้อบนเว็บ) และข้อความผ่านบริการของเรา (หักกระเป๋าเงิน — AppAiBilling)

    // อ่านจาก DB อย่างเดียว เปิดหน้านี้ไม่ถามแหล่งไฟล์ภายนอก — ปุ่มโหลดตามทัน release ใหม่เอง (read-through ใน /giggok/download)
    $latest = $product->latestVersion();
    // ลูกค้าต้องไม่รู้ repo (กฎเจ้าของ 2026-09-24) — ล้างลิงก์ต้นทางก่อนแสดง
    $releaseNotes = \App\Support\ReleaseNotes::forCustomers($latest?->changelog, \App\Support\ReleaseNotes::studioAccounts());

    // ภาพหน้าจอเป็น mockup ทั้งหมด (ยังไม่มีภาพจริงจากเครื่อง) วาดตามธีม Liquid Glass ของแอป ตัวเธอเรนเดอร์จากเวทีอวาตาร์ของแอปเอง
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py giggok
    $shot = fn (string $file) => asset('images/products/giggok/v2/' . $file);

    // ข้อเท็จจริงทุกข้อเช็กกับโค้ดแอป (xjanova/videogirl: lib/, docs/telephony.md, docs/studio.md, docs/local-brain.md)
    $dailyRows = [
        [
            'img' => $shot('home.webp'), 'mock' => true, 'phone' => true, 'name' => 'คุยด้วยเสียง เธอตอบเป็นเสียง', 'eyebrow' => 'Talk to her', 'tier' => 'free',
            'th' => 'พิมพ์หรือพูดใส่ไมค์ เธอตอบกลับเป็นเสียง ปากขยับตามเสียงจริง และมีท่าทางตามอารมณ์ สลับได้ระหว่างโหมดงาน (เลขา) กับโหมดส่วนตัว หรือให้สลับเองตามเวลางาน',
            'en' => 'Type or talk; she answers out loud with real lip sync. Work mode, personal mode, or automatic by the clock.',
            'points' => ['ดูปฏิทินจริงของเครื่อง (อ่านอย่างเดียว ไม่แก้ ไม่ลบ) ตอบได้ว่าวันนี้มีนัดอะไร บ่ายนี้ว่างไหม', 'หาข้อมูลให้ได้ — วิกิพีเดีย พยากรณ์อากาศ อัตราแลกเปลี่ยน', 'จำเรื่องสำคัญที่คุยกันไว้ และตั้งชื่อ บุคลิก น้ำเสียงของเธอได้เอง'],
            'alt' => 'ภาพตัวอย่างหน้าหลักของ GigGok: ตัวเธอ 3D แผงแชทกระจก ชิปคำถามลัด และแถบนำทาง',
        ],
        [
            'img' => $shot('call.webp'), 'mock' => true, 'phone' => true, 'name' => 'รับสายแทน รับฝากเรื่อง', 'eyebrow' => 'She answers your calls · beta', 'tier' => 'free',
            'th' => 'ตั้ง GigGok เป็นแอปโทรศัพท์หลัก แล้วเธอรับสายแทนเมื่อคุณไม่รับ ทักคนโทรมา คุยและรับฝากเรื่อง พอวางสายก็สรุปว่าใครโทรมา เรื่องอะไร ต้องโทรกลับไหม ลงไทม์ไลน์และแจ้งเตือน',
            'en' => 'Make GigGok your phone app and she picks up when you don\'t, takes a message and leaves a summary in your timeline.',
            'points' => ['เลือกได้ว่ารับเฉพาะเบอร์ในสมุดโทรศัพท์ และให้กริ่งดังนานเท่าไรก่อนเธอรับ', 'กด "แทรกสาย" คุยเองได้ทุกเมื่อ หรือพิมพ์ให้เธอพูดเข้าสาย', 'สั่งให้เธอโทรออกแทนได้ — ยืนยันก่อนโทรทุกครั้ง และเธอบอกปลายสายว่าเป็นเลขา AI', 'ระหว่างที่เธอคุยลำโพงเปิดตลอด (Android ไม่ให้แอปส่งเสียงเข้าสายตรง ๆ) ผลจึงต่างกันไปตามรุ่นมือถือ'],
            'alt' => 'ภาพตัวอย่างจอสายของ GigGok: มายด์กำลังคุยสายนี้ คำพูดสองฝั่ง ปุ่มแทรกสายและวางสาย',
        ],
        [
            'img' => $shot('timeline.webp'), 'mock' => true, 'phone' => true, 'name' => 'สมุดบันทึกของเธอ', 'eyebrow' => 'Timeline', 'tier' => 'free',
            'th' => 'ทุกอย่างที่เธอทำให้ลงไทม์ไลน์จริง ทั้งสายที่รับแทนพร้อมสรุปและเสียงสนทนา สายที่โทรออกแทน และเรื่องใหม่ที่เธอจำได้ ไม่ใช่ตัวเลขตกแต่ง',
            'en' => 'A real journal: calls with their summary and recording, calls she made for you, and what she has learned.',
            'points' => ['เสียงสนทนาเก็บในเครื่องนี้เท่านั้น ลบพร้อมบันทึก และเธอบอกคู่สายตอนทักว่ามีการบันทึก', 'ลงแอปใหม่แล้วไม่ลืม — สำเนาความจำและค่าตั้งเก็บไว้นอกพื้นที่แอป (โฟลเดอร์ GigGok ในเครื่อง)'],
            'alt' => 'ภาพตัวอย่างไทม์ไลน์ของ GigGok: สรุปสายเข้า สายโทรออก และเรื่องที่จำได้',
        ],
    ];

    // สมอง 4 ทาง (lib/ai/brain_provider.dart) + เสียงพูด + สมองร่วมกับ BrainX
    $brains = [
        [
            'key' => 'on-device', 'name' => 'ในเครื่อง · Gemma 4', 'short' => 'ออฟไลน์จริง · ฟรี · ค่าเริ่มต้น', 'tier' => 'free',
            'th' => 'สมองหลักรันบนมือถือเลย ไม่ต้องมีเน็ต บทสนทนาไม่ออกนอกเครื่อง ลองใช้ GPU ของมือถือก่อน ถ้าเครื่องไม่รองรับก็ใช้ CPU แทนเอง',
            'en' => 'Gemma 4 on the phone itself: truly offline, nothing leaves the device. Tries the GPU first, falls back to the CPU.',
            'points' => ['โหลดโมเดลครั้งเดียว: E2B ราว 2.6 GB (แนะนำ) หรือ E4B ราว 3.7 GB', 'ต้องเป็นมือถือแรม 6 GB ขึ้นไป (E4B ต้อง 12 GB) — แอปตรวจแรมให้ก่อนโหลด', 'ตอบช้ากว่าสมองผ่านเน็ต'],
        ],
        [
            'key' => 'proxy', 'name' => 'ผ่านบริการของเรา', 'short' => 'ไม่ต้องมีคีย์ · จ่ายตามข้อความ',
            'th' => 'ใช้ได้เลยโดยไม่ต้องมีคีย์ของตัวเอง ข้อความผ่านเซิร์ฟเวอร์ของ XMAN Studio และหักจากกระเป๋าเงินของบัญชีตามจำนวนข้อความ ไม่มีโควต้าฟรี',
            'en' => 'No key of your own: messages go through our server and are paid per message from your XMAN wallet.',
            'points' => ['ต้องผูกเครื่องกับบัญชี XMAN ก่อน', 'ราคาต่อข้อความต่างกันตามรุ่น แอปแสดงรุ่นที่เปิดให้ใช้และราคาก่อนส่ง', 'ตอบไม่สำเร็จไม่หักเงิน'],
        ],
        [
            'key' => 'openai', 'name' => 'คีย์ของคุณเอง', 'short' => 'OpenAI ด้วยคีย์ของคุณ',
            'th' => 'ต่อ OpenAI ตรงด้วยคีย์ที่คุณกรอกเอง ค่าใช้จ่ายเข้าบัญชี OpenAI ของคุณ ไม่ผ่านเรา คีย์เก็บใน Keystore ของเครื่อง',
            'en' => 'Straight to OpenAI with your own key, kept in the phone\'s Keystore. The bill is yours, not ours.',
            'points' => ['ทางเดียวที่เธอค้นข่าวและเรื่องล่าสุดจากเว็บได้', 'คุยในสายแบบสดได้ลื่นกว่า (OpenAI Realtime)'],
        ],
        [
            'key' => 'home', 'name' => 'เซิร์ฟเวอร์ในบ้าน', 'short' => 'Ollama · LM Studio · llama.cpp', 'tier' => 'free',
            'th' => 'ใช้คอมที่บ้านเป็นสมอง ผ่าน Ollama, LM Studio หรือ llama.cpp ข้อมูลไม่ออกนอกบ้าน แต่ต้องอยู่วงไวไฟเดียวกับคอม และคอมต้องเปิดอยู่',
            'en' => 'Your own PC as her brain over the home Wi-Fi: Ollama, LM Studio or llama.cpp.',
            'points' => [],
        ],
        [
            'key' => 'voice', 'name' => 'เสียงพูดของเธอ', 'short' => 'เสียงเครื่อง · คีย์ของคุณ · เสียงโคลน', 'tier' => 'free',
            'th' => 'เริ่มด้วยเสียงของ Android ซึ่งฟรีและใช้ออฟไลน์ได้ อยากได้เสียงที่สมจริงกว่าใส่คีย์ของคุณเองได้ หรือใช้เสียงโคลนของคุณที่เสิร์ฟจากคอมที่บ้าน',
            'en' => 'The free Android voice works offline; bring your own key for Gemini, ElevenLabs, Azure or OpenAI voices.',
            'points' => ['เสียงพรีเมียมจาก Google Gemini · ElevenLabs · Azure · OpenAI ด้วยคีย์ของคุณ', 'คีย์ใช้ไม่ได้หรือเครดิตหมด เธอกลับไปใช้เสียงเครื่องและบอกเหตุผล'],
        ],
        [
            'key' => 'brainx', 'name' => 'สมองเดียวกับ BrainX', 'short' => 'สำหรับคนที่ใช้ BrainX Cloud',
            'th' => 'ถ้าใช้ BrainX Cloud บนคอมอยู่แล้ว เชื่อมให้มายด์บนมือถือจำเรื่องเดียวกับมายด์บนคอมได้ ค้นสมองของคุณตอนตอบ และกู้ความจำกลับได้เมื่อลงแอปใหม่',
            'en' => 'Already on BrainX Cloud? Link it and she shares one memory with Mind on your PC.',
            'points' => ['ต้องมี BrainX แบบจ่ายเงิน (รายเดือน รายปี หรือตลอดชีพ) มือถือเชื่อมได้โดยไม่ต้องจ่ายซ้ำ', 'สายโทรศัพท์แตะสมองนี้ไม่ได้เลย'],
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'MobileApplication',
        'name' => 'GigGok',
        'operatingSystem' => 'Android 8.0+',
        'applicationCategory' => 'LifestyleApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'เลขาสาว 3D บนมือถือ Android คุยด้วยเสียง ดูปฏิทิน จดบันทึก และรับสายแทนได้ สมองหลักคิดในเครื่อง ใช้ฟรีทั้งแอป',
        'image' => asset('images/products/giggok/v2/og.jpg'),
        'url' => route('products.show', 'giggok'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => [['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'THB']],
    ];
    if ($latest) {
        $ld['softwareVersion'] = $latest->version;
    }
@endphp
<x-xp.page a="#5eead4" b="#f0abfc" c="#e879f9" glow="232 121 249" g1="#2dd4bf" g2="#a855f7" g3="#f472b6">

    {{-- ============================ HERO ============================ --}}
    {{-- โหลดไม่สำเร็จ (ยังไม่มีไฟล์ / ช่องส่งไฟล์เต็ม) ServesReleaseDownloads พากลับมาที่นี่พร้อม session('error') ซึ่ง layout สาธารณะแสดงให้เองแล้ว --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="เลขาสาว 3D บนมือถือ"
               tagline-en="A 3D AI secretary for your Android phone."
               lead="คุยด้วยเสียง เธอตอบเป็นเสียงและขยับปากตามจริง ดูปฏิทิน จดบันทึกให้ และรับสายแทนได้ สมองหลักคิดในเครื่อง บทสนทนาไม่ต้องออกนอกมือถือ"
               lead-en="She talks back, reads your calendar, keeps a journal and can answer your calls — thinking on the phone itself by default.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'GigGok v' . $latest->version : 'GigGok' }}</span>
            <span class="xp-chip">Android 8.0+ · 64-bit</span>
        </x-slot:chips>
        <x-slot:title>Gig<span class="xp-text-glow">Gok</span></x-slot:title>
        <x-slot:actions>
            <x-xp.download :href="route('giggok.download')" :coming-soon="$csDownload" th="ดาวน์โหลด APK" en="Free Android download" />
            <a href="#brains" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">สมองของเธอ 4 แบบ</a>
        </x-slot:actions>
        <x-slot:notes>
            <p class="mt-4 text-sm text-gray-400">
                @if($latest)เวอร์ชันล่าสุด v{{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · @endif
                ไฟล์ .apk จาก xman4289.com โดยตรง · Android 8.0 ขึ้นไป
            </p>
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="free" /> ใช้ฟรีทั้งแอป</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่ต้องสมัคร</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> คิดในเครื่อง ออฟไลน์ได้</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="max-w-xs mx-auto w-full">
                <x-xp.screen phone :src="$shot('home.webp')" :eager="true" :mock="true" :width="390" :height="845"
                             alt="ภาพตัวอย่างหน้าหลักของ GigGok: เลขาสาว 3D แผงแชทกระจก และแถบนำทาง" />
            </div>
            <div class="xp-float absolute -left-1 sm:left-0 bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">สมองในเครื่อง · On-device</p>
                <p class="text-white font-extrabold text-lg leading-tight">Gemma 4<br><span class="text-gray-400 text-sm font-normal">ไม่ต้องมีเน็ต</span></p>
            </div>
            <div class="xp-float-2 absolute right-0 top-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">License · ทุกเครื่อง</p>
                <p class="text-white font-bold leading-tight">ได้ฟรีอัตโนมัติ<br><span class="text-gray-400 text-sm font-normal">ตอนเปิดแอปครั้งแรก</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== EVERY DAY ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Free · every feature" title="เลขาที่อยู่ในมือถือ" title2="ทำงานให้จริง"
                       sub="ทุกฟีเจอร์ในแอปใช้ได้ฟรี ไม่มีรุ่นจ่ายเงินที่กั๊กไว้ ทุกเครื่องได้ License ฟรีอัตโนมัติตอนเปิดแอปครั้งแรก"
                       en="Every feature in the app is free. Each phone gets its free licence the first time the app opens." />
            <x-xp.rows :items="$dailyRows" />
        </div>
    </section>

    {{-- ============================ BRAINS ============================ --}}
    <section class="relative py-20 isolate scroll-mt-20" id="brains">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="Her brain · your choice" title="เลือกเองว่า" title2="ใครคิดให้เธอ"
                       sub="ฉลาด เป็นส่วนตัว ไม่ต้องมีเน็ต — แต่ละทางแลกกันคนละแบบ สลับได้ทุกเมื่อในหน้า ตั้งค่า › สมองของเธอ"
                       en="Smart, private, offline: each brain trades them differently. Switch any time in Settings." />
            <x-xp.explorer id="brain" label="สมองและเสียงของ GigGok" :tools="$brains" />
        </div>
    </section>

    {{-- ===================== STUDIO + PACK STORE ===================== --}}
    <section class="relative py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.rows :items="[
                [
                    'img' => $shot('brain.webp'), 'mock' => true, 'phone' => true, 'name' => 'สลับสมองได้ทุกเมื่อ', 'eyebrow' => 'Settings · her brain', 'tier' => 'free',
                    'th' => 'หน้า ตั้งค่า › สมองของเธอ บอกข้อดีข้อเสียของแต่ละทางไว้ตรง ๆ แอปตรวจแรมของเครื่องก่อนว่าไหวสมองในเครื่องรุ่นไหน แล้วค่อยให้โหลดโมเดล',
                    'en' => 'Each brain shows its trade-off, and the app checks your RAM before it lets you download a model.',
                    'points' => ['โหลดโมเดลแล้วใช้แบบออฟไลน์ได้ ลบออกได้ในหน้าเดียวกัน', 'เครื่องที่แรมไม่พอสำหรับสมองในเครื่อง ยังเลือกสมองแบบอื่นแทนได้'],
                    'alt' => 'ภาพตัวอย่างหน้าตั้งค่า สมองของเธอ: เลือกสมองได้ 4 แบบ และการ์ด Gemma ที่พร้อมใช้แบบออฟไลน์',
                ],
                [
                    'img' => $shot('studio.webp'), 'mock' => true, 'phone' => true, 'name' => 'สตูดิโอวิดีโอ', 'eyebrow' => 'Video studio · beta', 'tier' => 'free',
                    'th' => 'ทั้งจอเป็นเธอล้วน ๆ ให้แอปวิดีโอคอลแชร์หน้าจอได้ ฉากหลังสีทึบ (เขียว ฟ้า บานเย็น ดำ ขาว หรือรหัสสีเอง) สำหรับไลฟ์และ OBS และอัดคลิปลงเครื่อง',
                    'en' => 'A full-screen stage for screen-sharing into video calls, flat key colours for streaming, and clip recording.',
                    'points' => ['หน้าเธอตามหน้าเราผ่านกล้องหน้าได้', 'จอลอยทับแอปอื่นได้ · อัดคลิปครั้งละไม่เกิน 30 นาที ลงโฟลเดอร์ Movies/GigGok', 'ไม่ใช่กล้องเสมือน — Android ไม่ให้แอปทั่วไปเป็นกล้อง จึงใช้วิธีแชร์หน้าจอ'],
                    'alt' => 'ภาพตัวอย่างสตูดิโอวิดีโอของ GigGok: เธอบนฉากเขียว พร้อมปุ่มเลือกฉากหลัง จอลอย และอัดคลิป',
                ],
                [
                    'img' => $shot('shop.webp'), 'mock' => true, 'phone' => true, 'name' => 'ร้านชุดตัวมายด์', 'eyebrow' => 'Pack store',
                    'th' => 'ร้านของมายด์ในแอปมีชุดแต่งตัว ตัวละครใหม่ และของประดับเวที บางชุดฟรี ชุดที่ต้องจ่ายเงินซื้อบนเว็บนี้ แล้วกลับไปกดรีเฟรชในแอป',
                    'en' => 'Outfits, new characters and stage props. Some are free; paid ones are bought on this website.',
                    'points' => ['มีได้หลายชุดพร้อมกัน สลับได้ในหน้าตั้งค่า', 'ชุดใหม่ใช้ท่าทางร่วมกับชุดเดิม ไม่ต้องโหลดซ้ำ', 'ชุดที่ซื้อบนเว็บขึ้นในแอปเมื่อผูกเครื่องกับบัญชี XMAN'],
                    'alt' => 'ภาพตัวอย่างร้านของมายด์ในแอป GigGok: ตัวละคร ชุดแต่งตัว ปุ่มโหลดลงเครื่องและซื้อ',
                ],
            ]" />
        </div>
    </section>

    {{-- ============================ TRUST ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Your data stays yours" title="เรื่องส่วนตัว" title2="อยู่ในมือถือคุณ"
                       sub="เลขาที่รู้ตารางนัดและรับสายแทน ต้องไม่เอาเรื่องของคุณไปบอกใคร"
                       en="A secretary that knows your diary must keep it to herself." />
            <x-xp.cards :items="[
                ['icon' => 'chip', 'title' => 'คิดในเครื่องเป็นค่าเริ่มต้น', 'body' => 'สมองตั้งต้นคือ Gemma ในเครื่อง บทสนทนาไม่ออกนอกมือถือ ข้อความออกนอกเครื่องเฉพาะเมื่อคุณเลือกสมองที่ทำงานผ่านเน็ต', 'en' => 'On-device by default'],
                ['icon' => 'phone', 'title' => 'คนในสายไม่รู้เรื่องคุณ', 'body' => 'ระหว่างรับสาย เธอไม่มีเรื่องส่วนตัวของคุณให้หลุด ไม่เห็นเรื่องที่คนอื่นฝากไว้ และแตะความจำที่เชื่อมกับ BrainX ไม่ได้ — บังคับด้วยโค้ด ไม่ใช่แค่คำสั่ง', 'en' => 'Callers get nothing private out of her'],
                ['icon' => 'key', 'title' => 'คีย์อยู่ใน Keystore', 'body' => 'คีย์ OpenAI หรือคีย์เสียงของคุณเก็บใน Keystore ของเครื่อง ไม่ลงฐานข้อมูล ส่วนสมองผ่านบริการของเรา คีย์อยู่ที่เซิร์ฟเวอร์ ไม่เคยอยู่ในแอป', 'en' => 'Keys never sit in plain storage'],
                ['icon' => 'lock', 'title' => 'อัปเดตที่ตรวจไฟล์', 'body' => 'แอปเช็กรุ่นใหม่จาก xman4289.com แล้วตรวจ SHA-256 ของไฟล์ก่อนติดตั้งทุกครั้ง ไฟล์ไม่ตรงถูกลบทิ้ง ไม่ติดตั้ง', 'en' => 'Every update is checked before install'],
                ['icon' => 'bug', 'title' => 'รายงานบั๊กที่ดูก่อนส่งได้', 'body' => 'ไม่ส่งบทสนทนา ความจำ ไทม์ไลน์ คีย์ เบอร์โทร หรือชื่อคน และกดดูได้ทั้งฉบับก่อนส่ง', 'en' => 'Bug reports you can read first'],
                ['icon' => 'user', 'title' => 'ไม่ต้องสมัคร', 'body' => 'เปิดแอปแล้วใช้ได้เลย ผูกบัญชี XMAN เฉพาะตอนอยากได้ชุดที่ซื้อบนเว็บหรือใช้สมองผ่านบริการของเรา', 'en' => 'No sign-up to start'],
            ]" />
        </div>
    </section>

    {{-- ===================== FREE + WHAT COSTS MONEY ===================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Free app" title="ตัวแอปฟรี" title2="จ่ายเฉพาะที่อยากได้เพิ่ม"
                       sub="ไม่มีรุ่น Pro ไม่มีทดลองใช้ ทุกฟีเจอร์ในแอปใช้ได้ฟรีบนทุกเครื่อง"
                       en="No Pro tier and no trial: the whole app is free on every phone." />

            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5 max-w-6xl mx-auto">
                <div class="xp-featured">
                    <div class="p-7 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-2xl font-black text-white">GigGok</h3>
                            <x-xp.badge tier="free" />
                        </div>
                        <p class="text-gray-400 text-sm mb-6"><x-bi th="ทั้งแอป ไม่จำกัดเวลา" en="The whole app, no time limit" /></p>
                        <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                        <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['คุยด้วยเสียง · ปฏิทิน · ไทม์ไลน์', 'รับสายแทน · โทรออกแทน', 'สมองในเครื่อง / คีย์ของคุณ / เซิร์ฟเวอร์ในบ้าน', 'สตูดิโอวิดีโอ · อัปเดตในแอป', 'License ฟรีอัตโนมัติทุกเครื่อง']" />
                        <x-xp.download block :href="route('giggok.download')" :coming-soon="$csDownload" th="ดาวน์โหลด APK" en="Free Android download" />
                    </div>
                </div>

                <div class="xp-card p-7 flex flex-col">
                    <p class="xp-eyebrow mb-2">Optional · pay as you use</p>
                    <h3 class="text-2xl font-black text-white mb-3">สมองผ่านบริการของเรา</h3>
                    <p class="text-gray-300 text-sm leading-relaxed mb-5">ไม่ต้องมีคีย์ของตัวเอง หักจากกระเป๋าเงินบัญชี XMAN ทีละข้อความ ราคาต่อข้อความขึ้นกับรุ่น แอปแสดงราคาก่อนส่ง</p>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ไม่มีค่าสมาชิก ไม่ตัดเงินอัตโนมัติ', 'การจดจำเรื่องสำคัญและสรุปสายนับเป็นข้อความด้วย', 'ตอบไม่สำเร็จไม่หักเงิน']" />
                    <a href="{{ route('user.wallet.topup') }}" class="xp-btn-ghost block w-full py-3.5 text-center rounded-xl font-bold transition">เติมเงินเข้ากระเป๋า</a>
                </div>

                <div class="xp-card p-7 flex flex-col" id="link">
                    <p class="xp-eyebrow mb-2">Packs · bought on the web</p>
                    <h3 class="text-2xl font-black text-white mb-3">ร้านชุดตัวมายด์</h3>
                    <p class="text-gray-300 text-sm leading-relaxed mb-5">กดซื้อจากร้านในแอป แล้วจ่ายบนเว็บนี้ ราคาตามแต่ละชุด ชุดที่ซื้อจะขึ้นในร้านของแอปเมื่อผูก License Key ของเครื่อง (ดูได้ในหน้าตั้งค่าของแอป) กับบัญชี XMAN ของคุณ</p>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ผูกครั้งเดียวต่อเครื่อง', 'คีย์ที่ผูกกับบัญชีอื่นแล้วจะไม่ถูกย้ายมา']" />
                    <a href="{{ route('giggok.link') }}" class="xp-btn-main block w-full py-3.5 text-center rounded-xl font-extrabold transition">
                        @auth ผูกเครื่องกับบัญชี @else เข้าสู่ระบบเพื่อผูกเครื่องกับบัญชี @endauth →
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ RELEASE NOTES ============================ --}}
    @if($releaseNotes)
        <section class="relative pb-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="xp-eyebrow mb-3 text-center">What's new</p>
                <h2 class="text-3xl md:text-4xl font-black text-white text-center mb-8">มีอะไรใหม่ @if($latest)<span class="xp-text-glow">v{{ $latest->version }}</span>@endif</h2>
                <div class="xp-card p-6">
                    <div class="prose prose-invert prose-sm max-w-none">
                        {{-- release notes เป็น markdown: แปลงเป็น HTML เอง ตัด HTML ดิบทิ้ง --}}
                        {!! Str::markdown($releaseNotes, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                </div>
            </div>
        </section>
    @endif

    <x-xp.setup
        specs-title="ใช้กับมือถือแบบไหนได้"
        :specs="[
            ['Android 8.0 ขึ้นไป', 'มือถือ 64-bit (ARM) เท่านั้น — เครื่อง 32-bit ติดตั้งไม่ได้', 'Android 8.0+, 64-bit ARM'],
            ['สมองในเครื่อง', 'มือถือแรม 6 GB ขึ้นไป และที่ว่างราว 3 GB สำหรับโมเดล — แรมน้อยกว่านั้นใช้สมองแบบอื่นแทนได้', '6 GB RAM for the on-device brain'],
            ['พูดใส่ไมค์ (สมองในเครื่อง)', 'ใช้ตัวถอดเสียงในเครื่องของ Android ต้องเป็น Android 12 ขึ้นไป และมีชุดภาษา', 'On-device speech needs Android 12+'],
            ['รับสายแทน', 'ต้องตั้ง GigGok เป็นแอปโทรศัพท์หลัก และเปิดบริการการช่วยเหลือพิเศษให้เธอได้ยินคู่สาย', 'Default phone app + accessibility'],
        ]"
        specs-note="แอปไม่ได้มาจาก Play Store — Android 13 ขึ้นไปอาจกันการตั้งเป็นแอปโทรศัพท์หลัก ให้เปิดหน้าข้อมูลแอป → ⋮ → อนุญาตการตั้งค่าที่ถูกจำกัด แอปมีคำแนะนำให้ในหน้าตั้งค่า"
        :steps="[
            ['ดาวน์โหลดไฟล์ .apk', 'ไฟล์มาจาก xman4289.com โดยตรง ไม่ต้องล็อกอิน', 'Download the .apk'],
            ['ติดตั้ง', 'อนุญาตให้เบราว์เซอร์ติดตั้งแอปที่ไม่รู้จักเมื่อมือถือถาม แล้วกดติดตั้ง', 'Allow the install and install it'],
            ['เปิดแอปแล้วเลือกสมอง', 'ได้ License ฟรีอัตโนมัติ โหลด Gemma ลงเครื่อง (แนะนำผ่านไวไฟ) หรือเลือกสมองแบบอื่นในหน้าตั้งค่า', 'Open it and pick her brain'],
        ]" />

    <x-xp.faq :items="[
        ['ฟรีจริงไหม?', 'ฟรีจริง ทุกฟีเจอร์ในแอปใช้ได้ไม่จำกัดเวลา ไม่ต้องสมัคร ทุกเครื่องได้ License ฟรีอัตโนมัติตอนเปิดแอปครั้งแรก ที่ต้องจ่ายมีแค่ชุดตัวมายด์ที่ขายแยก และข้อความผ่านบริการของเราถ้าคุณเลือกใช้'],
        ['ต้องมีเน็ตไหม?', 'ไม่จำเป็น ถ้าใช้สมองในเครื่อง (Gemma) คุยได้แบบออฟไลน์หลังโหลดโมเดลครั้งแรก ส่วนสมองผ่านบริการของเรา คีย์ของคุณเอง และการหาข้อมูลจากเว็บต้องมีเน็ต'],
        ['มือถือของฉันใช้ได้ไหม?', 'ต้องเป็น Android 8.0 ขึ้นไปแบบ 64-bit สมองในเครื่องต้องการแรม 6 GB ขึ้นไป แอปตรวจแรมให้ก่อนโหลดโมเดล เครื่องที่แรมไม่พอยังใช้ได้ด้วยสมองผ่านบริการของเรา คีย์ของคุณเอง หรือเซิร์ฟเวอร์ในบ้าน'],
        ['รับสายแทนใช้ได้ทุกเครื่องไหม?', 'ยังเป็นฟีเจอร์ทดลอง Android ไม่ให้แอปส่งเสียงเข้าสายโดยตรง เสียงเธอจึงออกทางลำโพงให้ไมค์รับเข้าสาย บางรุ่นตัดเสียงก้องจนปลายสายได้ยินไม่ชัด มีสวิตช์ทางเสียงให้ลองสลับในหน้าตั้งค่า และพิมพ์ให้เธอพูดหรือกดแทรกสายคุยเองได้เสมอ'],
        ['ซื้อชุดตัวมายด์แล้วไม่ขึ้นในแอป?', 'ผูก License Key ของเครื่อง (ดูได้ในหน้าตั้งค่าของแอป) กับบัญชี XMAN ที่ใช้ซื้อ ที่หน้า ผูกเครื่องกับบัญชี แล้วกดรีเฟรชในร้านของแอป'],
        ['สมองผ่านบริการของเราคิดเงินยังไง?', 'หักจากกระเป๋าเงินบัญชี XMAN ทีละข้อความ ราคาขึ้นกับรุ่นที่เลือกและแอปแสดงให้เห็นก่อนส่ง ไม่มีค่าสมาชิก ตอบไม่สำเร็จไม่หักเงิน ต้องผูกเครื่องกับบัญชีก่อน'],
        ['ข้อมูลเก็บที่ไหน?', 'บทสนทนา ความจำ ไทม์ไลน์ และเสียงสายที่บันทึกไว้อยู่ในมือถือของคุณ มีสำเนาในโฟลเดอร์ GigGok ของเครื่องเพื่อให้ลงแอปใหม่แล้วไม่หาย ข้อความออกนอกเครื่องเฉพาะเมื่อคุณเลือกสมองหรือเสียงที่ทำงานผ่านเน็ต'],
        ['อัปเดตยังไง?', 'แอปเช็กรุ่นใหม่จาก xman4289.com และอัปเดตในตัว ตรวจ SHA-256 ของไฟล์ก่อนติดตั้งทุกครั้ง หรือโหลด .apk ตัวล่าสุดจากหน้านี้ไปติดตั้งทับก็ได้'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="เลขาของคุณ" glow="ในมือถือ"
              sub="ดาวน์โหลดฟรี ติดตั้ง แล้วคุยกับเธอได้เลย ไม่ต้องสมัคร"
              en="Download free, install, and say hello. No sign-up.">
        <x-xp.download :href="route('giggok.download')" :coming-soon="$csDownload" th="ดาวน์โหลด APK" en="Free Android download" class="!px-8" />
        <a href="{{ route('giggok.link') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ผูกเครื่องกับบัญชี</a>
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Android 8.0+ (64-bit) · ฟรีทั้งแอป · License ฟรีอัตโนมัติทุกเครื่อง</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
