@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย และไม่มีอะไรให้ซื้อ (กฎเจ้าของ 2026-10-12) --}}
@php $cs = \App\Models\Product::slugIsComingSoon('gpusharx'); @endphp

@section('title', 'GPUsharX — แพลตฟอร์มแชร์พลังการ์ดจอ' . ($cs ? ' · Coming Soon' : '') . ' | XMAN Studio')
@section('meta_description', 'GPUsharX แพลตฟอร์มแชร์พลังการ์ดจอสำหรับงานสร้างภาพและวิดีโอด้วย AI จาก XMAN Studio' . ($cs ? ' — อยู่ระหว่างพัฒนา ยังไม่เปิดให้ดาวน์โหลดหรือสมัคร ราคาและเงื่อนไขประกาศพร้อมเปิดตัว · A GPU-sharing platform, in development.' : ' · A GPU-sharing platform for AI image and video jobs.'))
@section('og_image', is_file(public_path('images/products/gpusharx/v2/og.jpg')) ? asset('images/products/gpusharx/v2/og.jpg') : asset('artwork/product/gpusharx.webp'))

@section('content')
@php
    // GPUsharX ยังไม่มีซอร์สโค้ด (2026-10-29): D:\Code\GPUX เป็นแค่บันทึก feasibility ของอีกแนวคิดหนึ่ง และ D:\Code\Gpu-AI-Mining มีแต่โลโก้
    // หน้านี้จึงพูดได้แค่ "แผน" ตามข้อมูลสินค้าในร้าน (database/seeders/XmanProductsSeeder.php) และติดป้ายว่าอาจเปลี่ยน
    // ไม่มีราคา ไม่มีส่วนแบ่งเป็นตัวเลข ไม่มีสเปก ไม่มีหน้าจอ — จนกว่าจะมีโค้ดจริงให้ตรวจ
    // ปุ่ม: เหมือนหน้าเดิมทุกประการ — Coming Soon = ป้าย · คนที่ซื้อแล้ว = ศูนย์ดาวน์โหลด · คนอื่น = รายการสินค้า (ไม่มีตะกร้า)
    $owns = auth()->check() && $hasPurchased;

    // ภาพ: key art ของหน้านี้ (scripts/product-mockups/build.py gpusharx) — ระหว่างรอใช้ภาพสินค้าเดิมของร้าน
    $v2 = fn (string $file) => is_file(public_path('images/products/gpusharx/v2/' . $file)) ? asset('images/products/gpusharx/v2/' . $file) : null;
    $artHero = $v2('art-hero.webp') ?? asset('artwork/product/gpusharx.webp');
    $art2 = $v2('art-2.webp');

    // GPUxMINE เปิดให้ใช้แล้วหรือยัง — อ่านจาก DB เท่านั้น ถ้ายัง Coming Soon หรือปิดอยู่ หน้านี้ไม่พูดถึง
    $gpuxmine = \App\Models\Product::where('slug', 'gpuxmine')->where('is_active', true)->first();
    $gpuxmineLive = $gpuxmine && ! $gpuxmine->isComingSoon();

    $opensOn = $product->coming_soon_until?->format('d/m/Y');

    // แนวคิดของสินค้าในสามขั้น — จากคำอธิบายสินค้าในร้าน (Workflow) ไม่ใช่หน้าจอโปรแกรม
    $flow = [
        ['แชร์การ์ดจอ', 'ติดตั้งโปรแกรมบน Windows ให้ตรวจการ์ดจอและวัดประสิทธิภาพในเครื่องเอง', 'Install the Windows client; it detects and benchmarks your GPU.'],
        ['รับงานจากกองกลาง', 'เครื่องรับงานสร้างภาพและวิดีโอด้วย AI ไปประมวลผล แล้วส่งผลกลับอัตโนมัติ', 'It picks up AI image and video jobs and sends the results back.'],
        ['ได้ส่วนแบ่งรายได้', 'ส่วนแบ่งคิดตามงานที่ทำสำเร็จและกำลังของการ์ด ดูยอดได้บนเว็บ', 'Your share follows the work done and the power of the card.'],
    ];

    // ส่วนประกอบตามแผน — แต่ละข้อมาจากคำอธิบายสินค้าในร้าน ยังไม่มีโค้ดให้ตรวจ จึงติดป้าย "เร็ว ๆ นี้" ทุกใบ
    $plan = [
        ['icon' => 'chip', 'tier' => 'soon', 'title' => 'โปรแกรมบน Windows', 'body' => 'ตรวจการ์ดจอในเครื่องเอง วัดประสิทธิภาพก่อนรับงาน รับและประมวลผลงานอัตโนมัติ และแสดงยอดสะสมให้เห็นตลอด', 'en' => 'A Windows client: hardware detection, benchmark, automatic jobs, live earnings'],
        ['icon' => 'server', 'tier' => 'soon', 'title' => 'เว็บแพลตฟอร์ม', 'body' => 'ลงทะเบียนเครื่อง ดูสถานะการ์ดทุกใบที่แชร์ กระจายงานไปยังเครื่องที่รับไหว และคำนวณรายได้ตามงานที่ทำ', 'en' => 'A web platform: machines, status, job distribution, earnings'],
        ['icon' => 'chart', 'tier' => 'soon', 'title' => 'รายได้และการถอน', 'body' => 'ดูสรุปรายได้และขอถอนเงินได้จากบนเว็บ — อัตราส่วนแบ่ง ขั้นต่ำ และช่องทางจ่าย จะประกาศพร้อมเปิดตัว', 'en' => 'Earnings and payouts on the web; rates and terms come with the launch'],
        ['icon' => 'shield', 'tier' => 'soon', 'title' => 'ระบบกันโกง', 'body' => 'ระบุเครื่องด้วยลายนิ้วมือฮาร์ดแวร์ สุ่มส่งงานตรวจ และโจทย์ proof-of-work เพื่อยืนยันว่างานทำจริงบนการ์ดจริง', 'en' => 'Anti-cheat: hardware fingerprint, random check jobs, proof-of-work'],
    ];

    $faq = array_filter([
        $cs ? ['GPUsharX เปิดให้ใช้เมื่อไร?', $opensOn
            ? 'กำหนดเปิด ' . $opensOn . ' — ระหว่างนี้ยังไม่มีโปรแกรมให้ดาวน์โหลดและยังสมัครไม่ได้'
            : 'ยังไม่ประกาศวันเปิด ระหว่างนี้ยังไม่มีโปรแกรมให้ดาวน์โหลดและยังสมัครไม่ได้ หน้านี้จะอัปเดตเมื่อเปิดให้ใช้'] : null,
        ['ต้องจ่ายเงินไหม แบ่งรายได้กันอย่างไร?', 'ยังไม่ประกาศ ราคา เงื่อนไข และอัตราส่วนแบ่งรายได้จะประกาศพร้อมเปิดตัว ตอนนี้ยังไม่มีอะไรให้ซื้อหรือจ่าย'],
        ['ใช้การ์ดจอรุ่นไหนได้บ้าง?', 'ยังไม่ประกาศ การ์ดที่รองรับและสเปกขั้นต่ำจะประกาศพร้อมโปรแกรม'],
        ['รายการฟีเจอร์บนหน้านี้แน่นอนแล้วหรือยัง?', 'ยัง ทุกข้อเป็นแผนของสินค้าและอาจเปลี่ยนก่อนเปิดตัว จึงติดป้าย "เร็ว ๆ นี้" ไว้ทุกข้อ'],
    ]);
    if ($gpuxmineLive) {
        $faq[] = ['ตอนนี้มีโปรแกรมแชร์การ์ดจอที่ใช้ได้เลยไหม?', 'มี GPUxMINE ของ XMAN Studio เปิดให้ใช้แล้ว ดูรายละเอียดได้ที่หน้า GPUxMINE'];
    }

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'GPUsharX',
        'inLanguage' => ['th', 'en'],
        'description' => 'แพลตฟอร์มแชร์พลังการ์ดจอสำหรับงานสร้างภาพและวิดีโอด้วย AI จาก XMAN Studio' . ($cs ? ' (อยู่ระหว่างพัฒนา)' : ''),
        'image' => $v2('og.jpg') ?? asset('artwork/product/gpusharx.webp'),
        'url' => route('products.show', 'gpusharx'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
    ];
@endphp
<x-xp.page a="#34d399" b="#38bdf8" c="#38bdf8" glow="56 189 248" g1="#38bdf8" g2="#34d399" g3="#a7f3d0">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$artHero"
               tagline="แพลตฟอร์มแชร์พลังการ์ดจอ"
               tagline-en="A GPU-sharing platform for AI image and video jobs."
               lead="แนวคิดของ GPUsharX คือให้เจ้าของการ์ดจอนำพลังประมวลผลที่ว่างอยู่มาแชร์เข้ากองกลาง สำหรับงานสร้างภาพและวิดีโอด้วย AI แล้วได้ส่วนแบ่งรายได้ตามงานที่ทำ"
               lead-en="Put the idle power of your graphics card into a shared pool for AI image and video jobs, and take a share of what it earns.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>GPUsharX</span>
            @if($cs)<span class="xp-chip">กำลังพัฒนา · In development</span>@endif
        </x-slot:chips>
        <x-slot:title>GPU<span class="xp-text-glow">sharX</span></x-slot:title>
        <x-slot:actions>
            @if($cs)
                <x-xp.download :href="route('customer.downloads')" :coming-soon="true" />
                <a href="#plan" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">สิ่งที่กำลังสร้าง</a>
            @elseif($owns)
                <x-xp.download :href="route('customer.downloads')" th="ดาวน์โหลด" en="Download" />
            @else
                <a href="{{ route('products.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">ดูผลิตภัณฑ์ทั้งหมด</a>
            @endif
        </x-slot:actions>
        <x-slot:notes>
            @if($cs)
                <p class="mt-4 text-sm text-gray-400">{{ $opensOn ? 'กำหนดเปิด ' . $opensOn : 'ยังไม่ประกาศวันเปิด' }} · ราคา เงื่อนไข และส่วนแบ่งรายได้ประกาศพร้อมเปิดตัว</p>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            @if($cs)
                <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="soon" /> ยังไม่เปิดให้ใช้</li>
            @endif
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> งานภาพและวิดีโอ AI</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ส่วนแบ่งตามงานที่ทำ</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <div class="xp-window">
                    <div class="p-5 sm:p-7">
                        <p class="xp-eyebrow mb-5">แนวคิด · How it is meant to work</p>
                        <ol class="space-y-4">
                            @foreach($flow as $n => [$title, $th, $en])
                                <li class="xp-card p-5 flex gap-4">
                                    <span class="flex-none w-10 h-10 rounded-xl xp-btn-main flex items-center justify-center font-black text-lg">{{ $n + 1 }}</span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-2 text-white font-bold">{{ $title }} <x-xp.badge tier="soon" /></span>
                                        <span class="block text-sm text-gray-300 mt-0.5">{{ $th }}</span>
                                        <span class="block text-xs text-gray-500 mt-1">{{ $en }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                        <p class="mt-5 text-xs text-gray-500">แผนภาพแนวคิด ไม่ใช่หน้าจอโปรแกรม · A concept, not a screenshot</p>
                    </div>
                </div>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ============================ THE PLAN ============================ --}}
    <section class="relative py-20 isolate scroll-mt-20" id="plan">
        @if($art2)
            <x-xp.art :src="$art2" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        @endif
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Planned · subject to change" title="สิ่งที่กำลังสร้าง"
                       sub="รายการนี้คือแผนของสินค้า ยังไม่มีโปรแกรมให้ทดลอง และรายละเอียดอาจเปลี่ยนก่อนเปิดตัว"
                       en="This is the plan, not a finished product: nothing to try yet, and details may change before launch." />
            <x-xp.cards :items="$plan" cols="2" class="max-w-5xl mx-auto" />
        </div>
    </section>

    {{-- ===================== ใช้ได้แล้ววันนี้: GPUxMINE ===================== --}}
    @if($gpuxmineLive)
        <section class="relative py-20" id="today">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="xp-card p-7 sm:p-10 text-center">
                    <p class="xp-eyebrow mb-3">Available today</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-4">อยากให้การ์ดจอทำงานตั้งแต่วันนี้?</h2>
                    <p class="text-lg text-gray-300 leading-relaxed mb-2">GPUxMINE ของ XMAN Studio เปิดให้ใช้แล้ว — แชร์การ์ดจอที่บ้านให้รับงาน AI ระหว่างที่คุณไม่ได้ใช้เครื่อง</p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-8">GPUxMINE is already live: share your home GPU for AI jobs while you are away from it.</p>
                    <a href="{{ route('products.show', 'gpuxmine') }}" class="xp-btn-main inline-flex items-center px-7 py-4 rounded-xl font-extrabold text-lg transition" data-gpuxmine-link>ดู GPUxMINE</a>
                </div>
            </div>
        </section>
    @endif

    <x-xp.faq :items="$faq" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$artHero" title="แชร์พลังการ์ดจอ" :glow="$cs ? ' เร็ว ๆ นี้' : null"
              :sub="$cs ? 'GPUsharX ยังไม่เปิดให้ใช้ ราคา เงื่อนไข และส่วนแบ่งรายได้จะประกาศพร้อมเปิดตัว' : 'GPUsharX จาก XMAN Studio'"
              :en="$cs ? 'GPUsharX is not open yet. Pricing, terms and the revenue share come with the launch.' : null">
        @if($cs)
            <x-xp.download :href="route('customer.downloads')" :coming-soon="true" class="!px-8" />
        @elseif($owns)
            <x-xp.download :href="route('customer.downloads')" th="ดาวน์โหลด" en="Download" class="!px-8" />
        @endif
        @if($gpuxmineLive)
            <a href="{{ route('products.show', 'gpuxmine') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดู GPUxMINE</a>
        @endif
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
