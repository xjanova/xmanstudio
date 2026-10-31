@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย และไม่มีทางซื้อจากหน้านี้ (กฎเจ้าของ 2026-10-12) — $comingSoon มาจาก ChanthraStudioWebController::detail --}}

@section('title', 'Chanthra Studio — สตูดิโอวิดีโอ AI บน Windows · License เริ่ม ฿' . number_format(\App\Support\LicensePlans::price('chanthra-studio', 'monthly') ?? 0) . '/เดือน | XMAN Studio')
@section('meta_description', 'Chanthra Studio สตูดิโอวิดีโอ AI บน Windows: สร้างภาพและวิดีโอด้วย ComfyUI ในเครื่อง เช่า GPU หรือบริการคลาวด์อย่าง Kling, Seedance, Veo, Runway · Storyboard Auto Pilot รวมคลิปแล้วโพสต์ Facebook ให้เอง · ตัดต่อ NLE · เสียงพากย์ TTS และเพลง AI · Node Flow · An AI video atelier for Windows.')
@section('og_image', asset('images/products/chanthra-studio/v2/og.jpg'))

@section('content')
@php
    // ราคาทุกแผนมาจาก config/licenses.php 'plans' ที่เดียว — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $prices = \App\Support\LicensePlans::for('chanthra-studio');
    $monthly = number_format($prices['monthly'] ?? 0);
    $yearlySaving = \App\Support\LicensePlans::yearlySaving('chanthra-studio');

    $cs = (bool) ($comingSoon ?? false);
    $ownsChanthra = auth()->check() && ($hasPurchased ?? false);

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว) — latestVersion() เรียงตามวันที่ ไม่ใช่ตามตัวอักษร ("0.9.0" > "0.11.0")
    $latest = $product?->latestVersion();

    // ยังไม่มีภาพจริงของแอป — ทุกหน้าจอเป็น mockup วาดตามธีม Lunar ของแอป (scripts/product-mockups/chanthra-studio)
    $shot = fn (string $file) => asset('images/products/chanthra-studio/v2/' . $file);

    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'Monthly', 'unit' => '/ เดือน', 'note' => 'ซื้อทีละ 30 วัน', 'days' => '30 วัน'],
        'yearly' => ['name' => 'รายปี', 'en' => 'Yearly', 'unit' => '/ ปี', 'note' => $yearlySaving ? 'ถูกกว่ารายเดือน ' . $yearlySaving . '%' : 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'Lifetime', 'unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ'],
    ];

    // "รายเดือน ฿399 · รายปี ฿2,500 · …" — เฉพาะแผนที่ตารางราคามีจริง
    $priceLine = collect($plans)->filter(fn ($plan, $term) => isset($prices[$term]))
        ->map(fn ($plan, $term) => $plan['name'] . ' ฿' . number_format($prices[$term]))->implode(' · ');

    // หน้าหลักของแอป — ข้อเท็จจริงจากโค้ด v0.11.0 (Views/*.xaml, Services/StoryboardAutoPilot.cs, EditorViewModel, VoiceViewModel)
    $workRows = [
        [
            'img' => $shot('storyboard.webp'), 'mock' => true, 'name' => 'Storyboard + Auto Pilot', 'eyebrow' => 'Idea → film → Facebook',
            'th' => 'พิมพ์คอนเซ็ปต์บรรทัดเดียวหรือเลือกเทมเพลต LLM แตกเป็นสตอรี่บอร์ดหลายคลิป พร้อมมุมกล้อง เสียง และคำโพสต์ + แฮชแท็ก ล็อกหน้าตัวละคร ฉาก และชุดด้วยภาพอ้างอิงได้ แล้วกด Auto Pilot ทีเดียว: สร้างทุกคลิป รวมเป็น MP4 ไฟล์เดียว เก็บเข้า Library และโพสต์ขึ้น Facebook Page ให้เอง',
            'en' => 'One line becomes a multi-clip storyboard. Auto Pilot renders every clip, joins them into one MP4 and posts it to your Facebook Page.',
            'points' => ['คลิปที่สร้างเสร็จแล้วไม่สร้างซ้ำ — รันใหม่หลังพังก็ทำต่อจากเดิม', 'ใช้หลายเครื่องยนต์ปนกันได้ ระบบปรับทุกคลิปให้ขนาด เฟรมเรต และเสียงตรงกันก่อนรวม', 'ก๊อปพร้อมต์ ส่งออกเป็น workflow ของ ComfyUI หรือส่งทีละคลิปเข้า Queue ก็ได้'],
            'alt' => 'ภาพตัวอย่างหน้า Storyboard ของ Chanthra Studio: เทมเพลต คลิป 6 ช่อง และแผง Auto Pilot',
        ],
        [
            'img' => $shot('editor.webp'), 'mock' => true, 'name' => 'ตัดต่อในแอป', 'eyebrow' => 'Editor · NLE-lite',
            'th' => 'ลากคลิปจาก Library ลงไทม์ไลน์ ตัดด้วยมีดตัด ตั้งเวลาแต่ละช่อง ใส่ Ken Burns ปรับสี ครอสเฟด เฟดเข้าออก วางภาพซ้อน (PiP) ใส่ตัวหนังสือ และผสมเสียงหลายแทร็ก แล้วเรนเดอร์ออกเป็นไฟล์ด้วย ffmpeg',
            'en' => 'Timeline, razor, Ken Burns, colour grade, crossfades, picture-in-picture, title cards and a multi-track audio mix, rendered with ffmpeg.',
            'points' => ['เรนเดอร์ 720p · 1080p · 4K ที่ 24 / 30 / 60 fps · 9:16 1:1 16:9 21:9', 'Undo / Redo และกู้งานจาก autosave เมื่อแอปปิดไปกลางคัน'],
            'alt' => 'ภาพตัวอย่างหน้า Editor ของ Chanthra Studio: คลังคลิป ตัวอย่างภาพ ไทม์ไลน์หลายแทร็ก และแผงปรับแต่ง',
        ],
        [
            'img' => $shot('voice.webp'), 'mock' => true, 'name' => 'เสียงพากย์และเพลง', 'eyebrow' => 'Sound atelier',
            'th' => 'พิมพ์โจทย์บรรทัดเดียวให้ LLM เขียนบทพากย์ แล้วสร้างเสียงด้วย OpenAI TTS หรือ ElevenLabs (ใส่ voice ID ที่โคลนไว้ได้) สร้างเพลงประกอบด้วย ACE-Step บนการ์ดจอของคุณหรือการ์ดที่เช่า หรือผ่าน Replicate จะใส่เนื้อร้องหรือบรรเลงล้วนก็ได้',
            'en' => 'Voice-overs with OpenAI TTS or ElevenLabs, music with ACE-Step on your own or a rented GPU, or through Replicate.',
            'points' => ['ทุกเทคเก็บเป็นไฟล์ในเครื่อง หยิบไปใส่แทร็กเสียงตอนเรนเดอร์ได้ทันที'],
            'alt' => 'ภาพตัวอย่างหน้า Sound atelier ของ Chanthra Studio: ตั้งค่าเสียงพากย์ สร้างเพลง และรายการเทค',
        ],
    ];

    // เครื่องมือขั้นสูง — ทุกข้อเช็กกับหน้าและ service ในโค้ด v0.11.0
    $tools = [
        [
            'key' => 'nodeflow', 'name' => 'Node Flow', 'short' => 'ต่อโหนดแบบ ComfyUI',
            'img' => $shot('nodeflow.webp'), 'mock' => true,
            'th' => 'ตัวแก้กราฟในแอป ใช้กับ workflow ของ ComfyUI ได้ตรง ๆ ลากโหนดจากรายการ ต่อสาย ดูภาพรวมด้วยแผนที่ย่อ จัดเรียงอัตโนมัติ แล้วกดรันกราฟได้เลย หรือบอก AI ว่าอยากได้อะไรแล้วให้มันสร้าง workflow ให้',
            'en' => 'A ComfyUI-compatible graph editor with templates, auto-arrange, a mini-map and an AI workflow builder.',
            'points' => ['เทมเพลตพร้อมใช้: SDXL · FLUX.1 dev · Wan 2.1 · Hunyuan · ACE-Step', 'Load / Save เป็นไฟล์ workflow ได้'],
            'alt' => 'ภาพตัวอย่างหน้า Node Flow ของ Chanthra Studio: โหนดของ ComfyUI ต่อสายกัน และแผงพารามิเตอร์',
        ],
        [
            'key' => 'comfy', 'name' => 'ComfyUI ในเครื่อง', 'short' => 'ติดตั้งให้ในคลิกเดียว',
            'th' => 'แอปติดตั้งเอนจิน ComfyUI รุ่นพกพาให้เอง (ดาวน์โหลดราว 2 GB มี Python และ torch ในตัว ไม่แตะระบบนอกโฟลเดอร์) เลือกรุ่นสำหรับการ์ด NVIDIA หรือ AMD เปิด/ปิดเอนจิน โหลดชุดโมเดล ใส่ LoRA และตั้งค่าเรนเดอร์ได้จากหน้า Models',
            'en' => 'Installs a portable ComfyUI for NVIDIA or AMD cards, downloads model packs and lets you tune sampler, steps, CFG and LoRAs.',
            'points' => ['ชุดโมเดล: SD 1.5 · SDXL · FLUX.1 dev · Wan 2.1 · Hunyuan · ACE-Step', 'ปุ่มคืน VRAM และหยุดงานที่รันค้าง', 'มี ComfyUI อยู่แล้ว? ชี้ URL ไปที่เซิร์ฟเวอร์เดิมได้'],
        ],
        [
            'key' => 'gpu', 'name' => 'เช่า GPU', 'short' => 'เช่าการ์ดมาเรนเดอร์ แล้วคืน',
            'img' => $shot('gpu.webp'), 'mock' => true,
            'th' => 'ไม่มีการ์ดแรง? เลือกเส้นทาง Rented GPU แอปจะเช่าการ์ดจาก SimplePod ติดตั้ง ComfyUI และโมเดลให้ เรนเดอร์ แล้วคืนเครื่องเองเมื่อว่าง คิดเงินเป็นวินาทีตามเวลาที่เครื่องเปิดอยู่',
            'en' => 'Rents a GPU from SimplePod, installs ComfyUI on it, renders and hands it back, with hard spending limits.',
            'points' => ['ปิดไว้เป็นค่าเริ่มต้น — ต้องเปิด "Allow renting" เอง', 'ค่าเริ่มต้น: งบ $10/วัน · ไม่เกิน $0.60/ชม. · ว่าง 10 นาทีคืนเครื่อง · ไม่มีเครื่องไหนอยู่เกิน 240 นาที', 'คืนทุกเครื่องเมื่อปิดแอป'],
            'alt' => 'ภาพตัวอย่างหน้า Rented GPU ของ Chanthra Studio: คีย์ SimplePod โปรไฟล์ที่ติดตั้ง และเพดานค่าใช้จ่าย',
        ],
        [
            'key' => 'seedance', 'name' => 'Seedance 2.0 wizard', 'short' => 'เขียนพร้อมต์ทีละขั้น',
            'th' => 'ตัวช่วยเขียนพร้อมต์ 5 ขั้นสำหรับ Seedance 2.0: เลือกโหมด (สตอรี่บอร์ดหรือลิปซิงก์) ใส่วัตถุดิบอ้างอิง กำหนดฉากและกล้อง ไทม์ไลน์รายวินาทีกับเสียง แล้วตรวจผล พร้อมต์ประกอบสดให้ดูด้านขวา ก๊อปไปใช้หรือส่งเข้า Composer ได้',
            'en' => 'A five-step prompt wizard for Seedance 2.0, with live prompt assembly and limits checked as you go.',
            'points' => ['เตือนเมื่อเกินเพดานของ Seedance (ภาพ ≤ 9 · วิดีโอ ≤ 3 · เสียง ≤ 3)'],
        ],
        [
            'key' => 'web', 'name' => 'Web Studio', 'short' => 'ให้แอปกดเว็บ AI แทนคุณ',
            'th' => 'เปิดเว็บเครื่องมือ AI ในหน้าต่างของแอป ล็อกอินครั้งเดียว (แอปเก็บคุกกี้ของเบราว์เซอร์ ไม่เก็บรหัสผ่าน) แล้วสอนขั้นตอนเป็น recipe ให้แอปกดตามเองอัตโนมัติ มีสูตรสำหรับ Magnific มาให้',
            'en' => 'Drives AI web tools in an embedded browser from recipes you teach it. Log in once; no password is stored.',
            'points' => [],
        ],
        [
            'key' => 'schedule', 'name' => 'ตั้งเวลาอัตโนมัติ', 'short' => 'เรนเดอร์และโพสต์ตามเวลา',
            'th' => 'ตั้งให้แอปสร้างคลิปตามเวลาที่กำหนด (เช่น 08:00 และ 18:00 ทุกวัน หรือทุก N นาที) แล้วโพสต์ขึ้น Facebook Page หรือส่ง webhook ให้เอง',
            'en' => 'Renders on a daily or interval schedule, then posts to Facebook or a webhook.',
            'points' => ['ต้องเปิดแอปทิ้งไว้ตอนถึงเวลา'],
        ],
        [
            'key' => 'usage', 'name' => 'ค่าใช้จ่าย', 'short' => 'รู้ทุกบาทที่จ่ายให้บริการ AI',
            'th' => 'บันทึกทุกครั้งที่เรียกบริการ AI ผ่านแอป คิดตามราคาจริงของแต่ละเจ้า แปลงเป็นบาทตามเรทที่ตั้งไว้ สรุปตามบริการและโมเดล 30 วันล่าสุด ตั้งงบรายเดือนได้ แถบสถานะจะเตือนเมื่อใช้ถึง 75% และ 100%',
            'en' => 'Every provider call is logged and priced in baht, with a monthly budget warning at 75% and 100%.',
            'points' => [],
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Chanthra Studio',
        'operatingSystem' => 'Windows 10/11 (64-bit)',
        'applicationCategory' => 'MultimediaApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'สตูดิโอวิดีโอ AI บน Windows: สร้างภาพและวิดีโอด้วย ComfyUI ในเครื่อง เช่า GPU หรือบริการคลาวด์ ทำสตอรี่บอร์ด ตัดต่อ ใส่เสียงพากย์และเพลง แล้วโพสต์ Facebook อัตโนมัติ',
        'image' => asset('images/products/chanthra-studio/v2/og.jpg'),
        'url' => route('chanthra-studio.detail'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => collect($prices)->map(fn ($price, $term) => ['@type' => 'Offer', 'name' => 'License (' . ($plans[$term]['en'] ?? $term) . ')', 'price' => (string) $price, 'priceCurrency' => 'THB'])->values()->all(),
    ];
    if ($latest) {
        $ld['softwareVersion'] = $latest->version;
    }
@endphp
<x-xp.page a="#f0cb88" b="#d4a76a" c="#d8b4fe" glow="212 167 106" g1="#f0cb88" g2="#e0768a" g3="#c084fc">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="สตูดิโอวิดีโอ AI บน Windows"
               tagline-en="An AI video atelier for Windows — script, shot, score and stitch in one app."
               lead="เขียนบทด้วย LLM สร้างภาพและวิดีโอด้วย ComfyUI บนการ์ดจอของคุณ การ์ดที่เช่า หรือบริการคลาวด์ ใส่เสียงพากย์และเพลง ตัดต่อเป็นหนังสั้น แล้วโพสต์ขึ้น Facebook Page ให้เอง — ครบในแอปเดียว"
               lead-en="Storyboard, generate, voice, score, edit and post — on your own GPU, a rented one or the cloud services you already use.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'Chanthra Studio ' . $latest->version : 'Chanthra Studio' }}</span>
            <span class="xp-chip">Windows 10/11 · 64-bit</span>
            @if($cs)<x-xp.badge tier="soon" label="Coming Soon" />@endif
        </x-slot:chips>
        <x-slot:title>Chanthra <span class="xp-text-glow">Studio</span></x-slot:title>
        <x-slot:actions>
            <x-xp.download :href="route('chanthra-studio.download')" :coming-soon="$cs" />
            <a href="#pricing" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">ราคา License — เริ่ม ฿{{ $monthly }}/เดือน</a>
            <a href="{{ route('chanthra-studio.manual') }}" class="xp-link inline-flex items-center px-2 py-4 font-semibold"><x-bi th="อ่านคู่มือ" en="Manual" />&nbsp;→</a>
        </x-slot:actions>
        <x-slot:notes>
            @if($cs)
                <p class="mt-4 text-sm text-gray-400">ยังไม่เปิดให้ดาวน์โหลดและยังไม่เปิดขาย · อ่านคู่มือและดูราคาไว้ก่อนได้</p>
            @elseif($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ไฟล์ .zip จาก xman4289.com โดยตรง</p>
            @endif
            @if($ownsChanthra)
                <p class="mt-3 text-sm text-emerald-200/90">
                    คุณมี License แล้ว · 1 คีย์ใช้ได้ 1 เครื่อง ·
                    <a href="{{ route('customer.licenses') }}" class="font-semibold underline decoration-emerald-300/50 hover:text-white">ดูคีย์ License ของฉัน</a>
                </p>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ComfyUI ในเครื่องหรือเช่า GPU</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ใช้คีย์ API ของคุณเอง</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> งานและไฟล์อยู่ในเครื่องคุณ</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('generate.webp')" :eager="true" :mock="true"
                             alt="ภาพตัวอย่างหน้า Generate ของ Chanthra Studio: Composer พร้อมต์ สไตล์ เส้นทางเรนเดอร์ ภาพตัวอย่างฉาก และสตอรี่บอร์ด" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">เลือกเครื่องยนต์ได้ · Your choice of engine</p>
                <p class="text-white font-extrabold text-lg leading-tight">ComfyUI · เช่า GPU · <span class="xp-text-glow">7 บริการคลาวด์</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">Auto Pilot</p>
                <p class="text-white font-bold leading-tight">สตอรี่บอร์ด → MP4<br><span class="text-gray-400 text-sm font-normal">→ โพสต์ Facebook ในปุ่มเดียว</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== WORKFLOW ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Script · shot · score · stitch" title="จากไอเดียบรรทัดเดียว" title2="ถึงคลิปที่โพสต์ได้"
                       sub="หน้า Generate คือโต๊ะทำงานหลัก: เขียนพร้อมต์ (กด ✦ ให้ AI ขัดเกลา) เลือกสไตล์ อัตราส่วน ความยาว มุมกล้อง ภาพอ้างอิง และเส้นทางเรนเดอร์ แล้วกด Summon Scene ทุกงานเข้าคิวให้เห็นความคืบหน้าและเก็บผลไว้ใน Library"
                       en="Compose a shot, pick the engine and summon it; every job is queued, tracked and kept in your library." />
            <x-xp.rows :items="$workRows" />
        </div>
    </section>

    {{-- =========================== TOOLS =========================== --}}
    <section class="relative py-20 isolate">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="Engines & power tools" title="เรนเดอร์ที่ไหนก็ได้" title2="คุมได้ทุกขั้น"
                       sub="ใช้การ์ดจอของคุณเอง เช่าการ์ดเฉพาะตอนเรนเดอร์ หรือส่งงานให้บริการคลาวด์ — พร้อมเครื่องมือสำหรับคนที่อยากลงลึก"
                       en="Your GPU, a rented one or the cloud — plus the tools for when you want to go deeper." />
            <x-xp.explorer id="tools" label="เครื่องมือของ Chanthra Studio" :tools="$tools" />
        </div>
    </section>

    {{-- ========================== PROVIDERS ========================== --}}
    <section class="relative py-20">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Bring your own keys" title="ต่อกับบริการ AI" title2="ที่คุณใช้อยู่แล้ว"
                       sub="ใส่คีย์ API ของบริการที่สมัครไว้ในหน้า Settings แล้วจ่ายตรงกับผู้ให้บริการ — ไม่มีค่าเครดิตคั่นกลาง"
                       en="Paste your own API keys and pay each provider directly." />
            <x-xp.cards :items="[
                ['icon' => 'sparkles', 'title' => 'ภาพและวิดีโอ', 'body' => 'ComfyUI ในเครื่อง · เช่า GPU (SimplePod) · Replicate · Kling AI · Seedance 2.0 · MiniMax Hailuo · Google Veo 3.1 · Runway Gen-3 · fal.ai', 'en' => 'Image and video engines'],
                ['icon' => 'doc', 'title' => 'เขียนบทและพร้อมต์', 'body' => 'Google Gemini · OpenAI · Anthropic Claude · xAI Grok · OpenRouter', 'en' => 'Script and prompt writing'],
                ['icon' => 'user', 'title' => 'เสียงพากย์', 'body' => 'OpenAI TTS · ElevenLabs (รวมเสียงที่โคลนไว้)', 'en' => 'Voice-over'],
                ['icon' => 'bolt', 'title' => 'เพลงประกอบ', 'body' => 'ACE-Step บน ComfyUI ในเครื่องหรือการ์ดที่เช่า · Replicate (MusicGen · ACE-Step · Riffusion)', 'en' => 'Music'],
                ['icon' => 'globe', 'title' => 'โพสต์อัตโนมัติ', 'body' => 'Facebook Page (Graph API) และ webhook ทั่วไป — ส่งหลังเรนเดอร์เสร็จ จาก Auto Pilot ตารางเวลา หรือ Library', 'en' => 'Auto-posting'],
                ['icon' => 'chip', 'title' => 'Workflow ของคุณเอง', 'body' => 'วางไฟล์ workflow ของ ComfyUI (รูปแบบ API) ลงโฟลเดอร์ แล้วเลือกใช้จากหน้า Generate ได้ทันที', 'en' => 'Your own ComfyUI workflows'],
            ]" />
        </div>
    </section>

    {{-- ============================ TRUST ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Yours, on your machine" title="งานของคุณ" title2="อยู่ในเครื่องคุณ"
                       sub="ไม่มีบัญชีคลาวด์ของเราคั่นกลาง คีย์ งาน และไฟล์ทั้งหมดเก็บไว้ในเครื่องของคุณเอง"
                       en="No cloud account in the middle: keys, projects and files stay on your PC." />
            <x-xp.cards :items="[
                ['icon' => 'lock', 'title' => 'คีย์ API เข้ารหัส', 'body' => 'คีย์ทุกบริการเข้ารหัสด้วย Windows DPAPI ก่อนเขียนลงดิสก์ อ่านได้เฉพาะบัญชี Windows ของคุณ', 'en' => 'Keys encrypted with Windows DPAPI'],
                ['icon' => 'server', 'title' => 'ข้อมูลอยู่ในเครื่อง', 'body' => 'Library คิวงาน ตารางเวลา และการตั้งค่าเก็บใน SQLite ข้างตัวโปรแกรม แอปติดต่อ xman4289.com เพื่อตรวจ License และเช็กอัปเดตเท่านั้น นอกนั้นคุยกับบริการที่คุณเลือกเอง', 'en' => 'Your library and settings live next to the app'],
                ['icon' => 'chart', 'title' => 'คุมงบได้', 'body' => 'นับค่าใช้จ่ายทุกครั้งที่เรียกบริการ AI เป็นบาท ตั้งงบรายเดือน และการเช่า GPU มีเพดานต่อวัน ต่อชั่วโมง และอายุเครื่อง', 'en' => 'Spend tracked in baht, with hard GPU limits'],
                ['icon' => 'undo', 'title' => 'ไม่เสียงาน', 'body' => 'Editor บันทึกอัตโนมัติและกู้งานได้หลังแอปปิดกลางคัน Auto Pilot ที่พังกลางทางรันต่อจากคลิปที่เสร็จแล้ว', 'en' => 'Autosave and resumable runs'],
                ['icon' => 'download', 'title' => 'อัปเดตจากที่เดียว', 'body' => 'ตัวอัปเดตรับไฟล์จาก https://xman4289.com เท่านั้น และตรวจ SHA-256 ก่อนติดตั้ง', 'en' => 'Updates only from xman4289.com, hash-checked'],
                ['icon' => 'eye', 'title' => 'สองธีม', 'body' => 'ธีม Lunar โทนทองบนม่วงเข้ม หรือ Dawn โทนสว่าง เปลี่ยนได้ในหน้า Settings', 'en' => 'Lunar or Dawn theme'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="License" title="ราคา License" title2="Chanthra Studio"
                       sub="ไฟล์เดียวกันทุกแผน · ทุกหน้าในแอปใช้ได้ตั้งแต่ดาวน์โหลด · License ปลดล็อกการอัปเดตอัตโนมัติในแอป · 1 คีย์ใช้ได้ 1 เครื่อง"
                       en="One download. Every workspace works out of the box; a license unlocks in-app auto-update. One key per machine." />
            @if($cs)
                <p class="text-center mb-8"><x-xp.badge tier="soon" label="Coming Soon — ยังไม่เปิดขาย" /></p>
            @endif

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 max-w-6xl mx-auto">
                <div class="xp-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">ดาวน์โหลด</h3>
                        <x-xp.badge tier="free" label="ไม่ต้องมีคีย์" />
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ตัวแอป ใช้ได้ทุกหน้า" en="The app, every workspace" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['Generate · Storyboard · Editor · Sound · Node Flow', 'ComfyUI ในเครื่อง · เช่า GPU · บริการคลาวด์', 'จ่ายค่าบริการ AI ตรงกับผู้ให้บริการ', 'อัปเดตเองด้วยการโหลดไฟล์ใหม่จากหน้านี้']" />
                    <x-xp.download block :href="route('chanthra-studio.download')" :coming-soon="$cs" />
                </div>

                @foreach($plans as $term => $plan)
                    @continue(! isset($prices[$term]))
                    @php($featured = $term === 'yearly')
                    <div class="{{ $featured ? 'xp-featured' : 'xp-card' }}">
                        <div class="p-7 flex flex-col h-full">
                            @if($featured)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2"><x-xp.badge tier="pro" label="คุ้มที่สุด · BEST VALUE" class="shadow-lg" /></div>
                            @endif
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-2xl font-black text-white">{{ $plan['name'] }}</h3>
                                <x-xp.badge tier="pro" label="LICENSE" />
                            </div>
                            <p class="text-amber-100/80 text-sm mb-6">{{ $plan['note'] }}</p>
                            <div class="mb-6 flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white">฿{{ number_format($prices[$term]) }}</span>
                                <span class="text-gray-300">{{ $plan['unit'] }}</span>
                            </div>
                            <x-tpix-price variant="detail" :thb="$prices[$term]" class="-mt-4 mb-6" />
                            <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="['อัปเดตอัตโนมัติในแอป: เช็ก โหลด และติดตั้งเวอร์ชันใหม่ให้เอง', '1 คีย์ = 1 เครื่อง · ย้ายเครื่องได้ด้วย Deactivate', 'อายุ License: ' . $plan['days']]" />
                            @if($cs)
                                <span class="xp-btn-ghost xp-btn-off block w-full py-3.5 px-3 text-center text-sm rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                            @else
                                {{-- Chanthra ขายผ่านหน้าแพ็กเกจของมันเอง (ไม่อยู่ใน config licenses.cart_products) --}}
                                <a href="{{ route('chanthra-studio.pricing') }}" class="{{ $featured ? 'xp-btn-pro' : 'xp-btn-ghost' }} block w-full py-3.5 text-center rounded-xl font-extrabold transition">
                                    {{ $ownsChanthra ? 'ซื้อ License เพิ่ม' : 'ซื้อ License' }} {{ $plan['name'] }} — ฿{{ number_format($prices[$term]) }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($ownsChanthra)
                <p class="text-center mt-6">
                    <a href="{{ route('customer.licenses') }}" class="xp-link text-sm font-semibold">มี License แล้ว? ดูคีย์ของคุณ →</a>
                </p>
            @endif
            <p class="text-center text-xs text-gray-500 mt-6 max-w-3xl mx-auto">ใส่คีย์ในหน้า Settings → Manage license ของโปรแกรม ไม่ต้องลงใหม่ · ลงแอปใหม่บนเครื่องเดิม แอปจำเครื่องได้เอง · ย้ายเครื่องด้วยปุ่ม Deactivate บนเครื่องเดิม · ค่าบริการ AI (Kling, Veo, ElevenLabs ฯลฯ) และค่าเช่า GPU จ่ายกับผู้ให้บริการแยกต่างหาก ไม่รวมใน License</p>
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows 10 หรือ 11 แบบ 64-bit', 'Windows, 64-bit'],
            ['ไม่ต้องติดตั้ง', 'แตก zip แล้วเปิดได้เลย ไม่ต้องลง .NET งานเก็บไว้ข้างตัวโปรแกรม', 'Portable, self-contained'],
            ['ffmpeg', 'ใช้ตอนรวมคลิปและเรนเดอร์หนัง ติดตั้งแยก (เช่นผ่าน winget) แอปหาให้เองจาก PATH หรือชี้ตำแหน่งในหน้า Settings', 'Needed to stitch and render'],
            ['การ์ดจอ (ถ้าเรนเดอร์ในเครื่อง)', 'NVIDIA หรือ AMD สำหรับ ComfyUI ในเครื่อง — ไม่มีการ์ดแรงก็ใช้บริการคลาวด์หรือเช่า GPU แทน', 'Only for local ComfyUI'],
        ]"
        specs-note="ต้องต่ออินเทอร์เน็ตและมีคีย์ API ของบริการที่จะใช้ · Web Studio ใช้ WebView2 ของ Microsoft Edge"
        :steps="[
            ['ดาวน์โหลดไฟล์ .zip', 'ไฟล์มาจาก xman4289.com โดยตรง', 'Download the .zip'],
            ['แตกไฟล์แล้วเปิด ChanthraStudio.exe', 'ไม่ต้องติดตั้ง ย้ายทั้งโฟลเดอร์ไปที่ไหนก็ได้', 'Extract and run'],
            ['ใส่คีย์ API หรือติดตั้ง ComfyUI', 'ใส่คีย์ในหน้า Settings หรือกดติดตั้งเอนจินในหน้า Models แล้วกด ✦ Summon Scene', 'Add keys or install ComfyUI, then summon'],
        ]" />

    <x-xp.faq :items="[
        ['ตอนนี้ดาวน์โหลดหรือซื้อได้หรือยัง?', $cs ? 'ยังไม่ได้ Chanthra Studio ยังเป็น Coming Soon — ยังไม่เปิดให้ดาวน์โหลดและยังไม่เปิดขาย ราคาในหน้านี้คือราคาที่ตั้งไว้' : 'ได้ ดาวน์โหลดไฟล์ .zip จากหน้านี้ได้เลย และซื้อ License ได้จากหน้าแพ็กเกจ'],
        ['ไม่มีการ์ดจอแรงใช้ได้ไหม?', 'ได้ เลือกเส้นทางบริการคลาวด์ (Kling, Seedance, Veo, Runway, MiniMax, Replicate, fal.ai) หรือเช่า GPU เฉพาะตอนเรนเดอร์ ส่วน ComfyUI ในเครื่องต้องใช้การ์ด NVIDIA หรือ AMD'],
        ['ค่าบริการ AI คิดอย่างไร?', 'คุณใช้คีย์ API ของตัวเองและจ่ายตรงกับผู้ให้บริการแต่ละเจ้า แอปบันทึกทุกครั้งที่เรียกใช้ คิดเป็นบาท และตั้งงบรายเดือนได้ ส่วนการเรนเดอร์บน ComfyUI ในเครื่องไม่มีค่าบริการ'],
        ['License ได้อะไร ราคาเท่าไร?', 'License ปลดล็อกการอัปเดตอัตโนมัติในแอป (เช็ก โหลด และติดตั้งเวอร์ชันใหม่ให้เอง) · ' . $priceLine . ' — 1 คีย์ใช้ได้ 1 เครื่อง'],
        ['ย้ายไปเครื่องใหม่ได้ไหม?', 'ได้ กด Deactivate ในหน้า Settings → Manage license บนเครื่องเดิม แล้วใส่คีย์บนเครื่องใหม่ ถ้าลงแอปใหม่บนเครื่องเดิม แอปจำเครื่องได้เอง'],
        ['เช่า GPU จะเผลอเสียเงินบานไหม?', 'การเช่าปิดไว้เป็นค่าเริ่มต้น เมื่อเปิดแล้วมีเพดานงบต่อวัน ราคาสูงสุดต่อชั่วโมง คืนเครื่องเมื่อว่าง อายุเครื่องสูงสุด และคืนทุกเครื่องเมื่อปิดแอป ทุกค่าแก้ได้ในหน้า Rented GPU'],
        ['โพสต์ Facebook อัตโนมัติต้องตั้งอะไร?', 'ใส่ Page ID และ Page access token ของเพจในหน้า Settings → Posting แล้วติ๊กโพสต์เมื่อเสร็จใน Auto Pilot หรือในตารางเวลา (ตารางเวลาต้องเปิดแอปทิ้งไว้)'],
        ['คีย์ API ของฉันปลอดภัยไหม?', 'คีย์เข้ารหัสด้วย Windows DPAPI ก่อนเขียนลงดิสก์ อ่านได้เฉพาะบัญชี Windows ของคุณ'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="เล่าเรื่องใต้" glow="แสงจันทร์"
              :sub="$cs ? 'Chanthra Studio กำลังจะเปิดตัว — อ่านคู่มือและดูราคาไว้ก่อนได้' : 'ดาวน์โหลดแล้วเริ่มสร้างคลิปแรกได้เลย License เริ่มเพียง ฿' . $monthly . ' ต่อเดือน'"
              en="Tell your story by moonlight.">
        <x-xp.download :href="route('chanthra-studio.download')" :coming-soon="$cs" class="!px-8" />
        <a href="{{ route('chanthra-studio.manual') }}" class="xp-btn-ghost inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">อ่านคู่มือ</a>
        @if($ownsChanthra)
            <a href="{{ route('customer.licenses') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดูคีย์ License ของฉัน</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10/11 (64-bit) · ไม่ต้องติดตั้ง · ComfyUI ในเครื่อง เช่า GPU หรือบริการคลาวด์ · 1 คีย์ = 1 เครื่อง</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
