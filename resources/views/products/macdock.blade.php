@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('macdock'); @endphp

@section('title', 'MacDock — Dock สไตล์ macOS สำหรับ Windows · ใช้ฟรีทุกฟีเจอร์ | XMAN Studio')
@section('meta_description', 'MacDock แถบ Dock สไตล์ macOS บน Windows ไอคอนขยายตามเมาส์ เห็นแอปที่เปิดอยู่ จัดไอคอนเป็นหมวดให้เอง วางได้ 4 ขอบจอ พร้อมนาฬิกาตั้งโต๊ะ ซ่อนไอคอนเดสก์ท็อปและแถบงานให้หน้าจอโล่ง ใช้ฟรีทุกฟีเจอร์ ไม่ต้องติดตั้ง · รับไลเซ่นฟรีเพื่อเปิดอัปเดตอัตโนมัติ · A free macOS-style dock for Windows.')
@section('og_image', asset('images/products/macdock/v2/og.jpg'))

@section('content')
@php
    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว เปิดหน้านี้ไม่ถาม GitHub — read-through อยู่ใน /macdock/download)
    $latest = $product->latestVersion();
    // ลูกค้าต้องไม่รู้ repo (กฎเจ้าของ 2026-09-24) — ล้างลิงก์ GitHub ก่อนแสดง
    $releaseNotes = \App\Support\ReleaseNotes::forCustomers($latest?->changelog, \App\Support\ReleaseNotes::studioAccounts());

    // แอปฟรีทั้งตัว (ไม่มีรุ่นเสียเงินในโค้ดแอป) — ราคาบนหน้านี้อ่านจากสินค้าเอง ไม่เขียนตัวเลขลงหน้า
    $price = number_format((float) $product->price);

    // ไลเซ่นฟรีเกิดจากการกดยืนยันบนเว็บ ไม่มีคำสั่งซื้อ ($userLicense ของ controller จึงไม่เห็น) — นับคีย์ FREE ของบัญชีนี้เอง
    $myFreeKeys = auth()->check()
        ? \App\Models\LicenseKey::where('product_id', $product->id)
            ->where('user_id', auth()->id())
            ->where('license_type', \App\Models\LicenseKey::TYPE_FREE)
            ->count()
        : 0;

    // ภาพหน้าจอ: mockup ที่วาดตามโค้ดแอป (DockWindow / ClockWidget / LicenseWindow) — หน้าปัดนาฬิกาเป็นภาพจริงจากแอป
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py macdock
    $shot = fn (string $file) => asset('images/products/macdock/v2/' . $file);

    // ข้อเท็จจริงจากโค้ดแอป D:\Code\MACDOCK (DockWindow.cs, CategoryClassifier.cs, ClockWidget.cs, Settings.cs)
    $rows = [
        [
            'img' => $shot('menu.webp'), 'mock' => true, 'name' => 'จัดไอคอนเป็นหมวดให้เอง', 'eyebrow' => 'Auto-organize', 'tier' => 'free',
            'th' => 'กด "จัดหมวดไอคอนอัตโนมัติ" MacDock อ่านชื่อ ไฟล์โปรแกรม และลิงก์ของแต่ละไอคอน แล้วแยกเป็นหมวด เช่น เบราว์เซอร์ แชท & ประชุม นักพัฒนา เกม งานเอกสาร กราฟิก & ตัดต่อ เพลง & วิดีโอ เครื่องมือ โฟลเดอร์ และลิงก์เว็บ',
            'en' => 'One click sorts every icon into categories by its name, program and link.',
            'points' => ['คลิกหัวหมวดเพื่อพับ / กาง ไอคอนในหมวดนั้น', 'คลิกขวาที่ไอคอนเพื่อย้ายหมวด หรือสร้าง เปลี่ยนชื่อ ลบหมวดเอง', 'หมวดที่มีไอคอนเดียวถูกรวมไว้ใน "อื่นๆ" ให้ Dock ไม่รก'],
            'alt' => 'ภาพตัวอย่าง MacDock: เมนูคลิกขวาที่ไอคอน พร้อมเมนูย่อยเลือกหมวด และหัวหมวดบน Dock',
        ],
        [
            'img' => $shot('side.webp'), 'mock' => true, 'name' => 'วางได้ทั้ง 4 ขอบจอ', 'eyebrow' => 'Any edge · auto-hide', 'tier' => 'free',
            'th' => 'เริ่มต้นที่ขอบบนของจอ ย้ายไปล่าง ซ้าย หรือขวาได้จากเมนู หรือลากที่จับตรงหัว Dock ไปวาง ซ่อนตัวอัตโนมัติแล้วโผล่เมื่อเลื่อนเมาส์ไปชนขอบจอ หรือปิดการซ่อนให้ Dock กันที่ของตัวเองไว้ไม่ให้หน้าต่างบัง',
            'en' => 'Top by default; move it to any edge from the menu or by dragging its grip. Auto-hides until the pointer touches the edge.',
            'points' => ['ไอคอน 4 ขนาด: เล็ก กลาง ใหญ่ ใหญ่พิเศษ', 'ไอคอนเยอะเกินจอ เลื่อนด้วยลูกกลิ้งเมาส์ได้', 'เปิด / ปิดการขยายตามเมาส์ได้'],
            'alt' => 'ภาพตัวอย่าง MacDock วางที่ขอบซ้ายของจอ ไอคอนขยายตามเมาส์ และนาฬิกาดิจิทัลมุมขวาบน',
        ],
        [
            'img' => $shot('clock.webp'), 'mock' => true, 'name' => 'นาฬิกาตั้งโต๊ะ', 'eyebrow' => 'Clock widget', 'tier' => 'free',
            'th' => 'นาฬิกาเข็ม หรือนาฬิกาดิจิทัลที่บอกวันเป็นภาษาไทย ลากไปวางตรงไหนก็จำตำแหน่งไว้ คลิกขวาเปิดปฏิทิน ไปตั้งปลุกในแอปนาฬิกาของ Windows หรือตั้งค่าวันเวลาได้ทันที',
            'en' => 'An analog or digital desk clock with the Thai date. Drag it anywhere; right-click for the calendar and alarms.',
            'points' => ['ดับเบิลคลิกนาฬิกาเพื่อดูไอคอนถาดระบบ (เสียง เน็ต แอปพื้นหลัง) ตอนที่แถบงานถูกซ่อน', 'ให้อยู่บนสุดเสมอ หรืออยู่ใต้หน้าต่างอื่นก็ได้', 'ปิดไว้เป็นค่าเริ่มต้น เปิดจากเมนู Clock widget'],
            'alt' => 'ภาพตัวอย่าง MacDock: นาฬิกาเข็มและนาฬิกาดิจิทัล (หน้าปัดจากแอปจริง) พร้อมเมนูคลิกขวาของนาฬิกา',
        ],
    ];

    // ทำงานแบบ Dock — ทุกข้อเช็กกับ DockWindow.cs / Settings.cs / SystemTweaks.cs
    $tools = [
        [
            'key' => 'running', 'name' => 'เห็นแอปที่เปิดอยู่', 'short' => 'จุดสีฟ้า · คลิกเดียวสลับหน้าต่าง', 'tier' => 'free',
            'th' => 'แอปที่กำลังเปิดอยู่ขึ้นก่อนเส้นคั่น มีจุดสีฟ้าและแสงเรืองรอบไอคอน คลิกเพื่อดึงหน้าต่างขึ้นมา ไอคอนที่ปักหมุดไว้ก็ขึ้นจุดเมื่อโปรแกรมนั้นเปิดอยู่',
            'en' => 'Open apps come first with a blue dot and a glow; one click brings the window up.',
            'points' => ['คลิกขวา: Show · Quit · Keep in Dock', 'คลิกเปิดโปรแกรมแล้วไอคอนเด้งให้รู้ว่ากำลังเปิด', 'ซ่อนส่วนแอปที่เปิดอยู่ได้ ถ้าอยากเห็นแค่ที่ปักหมุด'],
        ],
        [
            'key' => 'desktop', 'name' => 'ย้ายไอคอนเดสก์ท็อปมาไว้ใน Dock', 'short' => 'ครั้งแรกดึงให้เอง · ลากวางเพิ่มได้', 'tier' => 'free',
            'th' => 'เปิด Dock ครั้งแรก MacDock ดึงทุกอย่างบนเดสก์ท็อป (ของคุณและของทุกคนในเครื่อง) มาไว้ใน Dock ให้ ลากไฟล์ โฟลเดอร์ หรือช็อตคัตมาวางเพิ่มได้ และลากไอคอนเพื่อจัดลำดับหรือย้ายหมวด',
            'en' => 'The first start pins everything on your desktop; drop more files, folders or shortcuts on the dock any time.',
            'points' => ['เอาออกจาก Dock = เอาแค่ไอคอนออก ไฟล์ยังอยู่ที่เดิม', 'ดึงจากเดสก์ท็อปใหม่ได้ทุกเมื่อ (Re-import Desktop items)', 'คลิกขวา → Show in Explorer เปิดตำแหน่งไฟล์'],
        ],
        [
            'key' => 'smart', 'name' => 'เรียงตามที่ใช้บ่อย', 'short' => 'Smart order', 'tier' => 'free',
            'th' => 'MacDock จดว่าคุณใช้โปรแกรมไหนบ่อย แล้วเลื่อนตัวที่ใช้บ่อยขึ้นหน้าในแต่ละหมวด คะแนนเก่าค่อย ๆ ลดวันละราว 7% นิสัยช่วงหลังจึงมีน้ำหนักกว่า',
            'en' => 'Most-used first within each category; old habits fade by about 7% a day.',
            'points' => ['จัดลำดับใหม่ตอนเปิดและทุก 10 นาที ไม่ขยับตอนเมาส์อยู่บน Dock', 'ปิดได้ แล้วเรียงตามที่คุณลากจัดเอง', 'ข้อมูลการใช้งานเก็บในเครื่องเท่านั้น'],
        ],
        [
            'key' => 'clean', 'name' => 'หน้าจอโล่ง', 'short' => 'ซ่อนไอคอนเดสก์ท็อปและแถบงาน', 'tier' => 'free',
            'th' => 'ค่าเริ่มต้นซ่อนไอคอนบนเดสก์ท็อปและแถบงาน Windows ให้เหลือแค่ Dock แยกเปิด / ปิดได้ทีละอย่างจากเมนู ถ้ามีหลายจอ แถบงานของทุกจอถูกซ่อนด้วย',
            'en' => 'Desktop icons and the Windows taskbar are hidden by default, each with its own switch.',
            'points' => ['ปิด Dock หรือออกจากโปรแกรม = ทุกอย่างกลับมาเหมือนเดิม', 'Explorer รีสตาร์ตแล้วแถบงานโผล่ MacDock ซ่อนให้อีกครั้ง'],
        ],
        [
            'key' => 'start', 'name' => 'เปิดพร้อม Windows', 'short' => 'Start with Windows · ไอคอนถาด', 'tier' => 'free',
            'th' => 'ติ๊ก Start with Windows ให้ Dock ขึ้นเองทุกครั้งที่เข้าเครื่อง ไอคอนในถาดระบบมีเมนูลัด: เปิดหน้าต่าง MacDock ซ่อน / แสดงไอคอนเดสก์ท็อปและแถบงาน เลือกนาฬิกา ไลเซ่น & อัปเดต และออกจากโปรแกรม',
            'en' => 'Starts with Windows if you tick it; the tray icon has the main switches.',
            'points' => ['บันทึกแค่ในบัญชีผู้ใช้ของคุณ ไม่ต้องใช้สิทธิ์ผู้ดูแล'],
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'MacDock',
        'operatingSystem' => 'Windows 10, Windows 11 (64-bit)',
        'applicationCategory' => 'UtilitiesApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'Dock สไตล์ macOS สำหรับ Windows ไอคอนขยายตามเมาส์ เห็นแอปที่เปิดอยู่ จัดไอคอนเป็นหมวด พร้อมนาฬิกาตั้งโต๊ะ ใช้ฟรีทุกฟีเจอร์',
        'image' => asset('images/products/macdock/v2/og.jpg'),
        'url' => route('products.show', 'macdock'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => ['@type' => 'Offer', 'price' => (string) (float) $product->price, 'priceCurrency' => 'THB'],
    ];
    if ($latest) {
        $ld['softwareVersion'] = $latest->version;
    }
@endphp
<x-xp.page a="#5ef2ff" b="#b57cff" c="#7dd3fc" glow="94 242 255" g1="#5ef2ff" g2="#8fb4ff" g3="#b57cff">

    {{-- ============================ HERO ============================ --}}
    {{-- โหลดไม่สำเร็จ ServesReleaseDownloads พากลับมาที่นี่พร้อม session('error') ซึ่ง layout สาธารณะแสดงให้เอง --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="Dock สไตล์ macOS สำหรับ Windows"
               tagline-en="A macOS-style dock for Windows."
               lead="ไอคอนขยายตามเมาส์ เห็นแอปที่เปิดอยู่ จัดไอคอนเป็นหมวดให้เอง พร้อมนาฬิกาตั้งโต๊ะ — ซ่อนไอคอนเดสก์ท็อปและแถบงานให้หน้าจอโล่ง แล้วคืนทุกอย่างกลับเมื่อปิด Dock"
               lead-en="Icons that grow under the pointer, your open apps, automatic categories and a desk clock — with a clean desktop that comes back the moment you switch it off.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'MacDock v' . $latest->version : 'MacDock' }}</span>
            <span class="xp-chip">Windows 10/11 · 64-bit</span>
        </x-slot:chips>
        <x-slot:title>Mac<span class="xp-text-glow">Dock</span></x-slot:title>
        <x-slot:actions>
            <x-xp.download :href="route('macdock.download')" :coming-soon="$csDownload" />
            <a href="#license" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">
                <x-bi th="รับไลเซ่นฟรี" en="Free licence" />
            </a>
        </x-slot:actions>
        <x-slot:notes>
            <p class="mt-4 text-sm text-gray-400">
                @if($latest)v{{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · @endif
                ไฟล์ .zip จาก xman4289.com โดยตรง · ไฟล์เดียว ไม่ต้องติดตั้ง ไม่ต้องสมัคร
            </p>
            @if($myFreeKeys > 0)
                <p class="mt-3 text-sm text-sky-200/90">
                    บัญชีนี้มีไลเซ่นฟรี MacDock {{ $myFreeKeys }} เครื่อง ·
                    <a href="{{ route('customer.licenses') }}" class="font-semibold underline decoration-sky-300/50 hover:text-white">ดูไลเซ่นของฉัน</a>
                </p>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="free" /> ใช้ได้ทุกฟีเจอร์</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่ต้องลง .NET</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่ต้องใช้สิทธิ์ผู้ดูแล</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('dock.webp')" :eager="true" :mock="true"
                             alt="ภาพตัวอย่าง MacDock ที่ขอบบนของจอ: แอปที่เปิดอยู่มีจุดสีฟ้า ไอคอนขยายตามเมาส์ หัวหมวด และนาฬิกาเข็มบนเดสก์ท็อป" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">Magnification</p>
                <p class="text-white font-extrabold text-lg leading-tight">ไอคอนขยาย<span class="text-sky-300">ตามเมาส์</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ปิด Dock · Switch off</p>
                <p class="text-white font-bold leading-tight">แถบงานกลับมาเหมือนเดิม<br><span class="text-gray-400 text-sm font-normal">ไอคอนเดสก์ท็อปด้วย</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== FEATURES ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Free · every feature" title="Dock ที่จัดตัวเอง" title2="ให้หน้าจอเป็นระเบียบ"
                       sub="ทุกฟีเจอร์ใช้ได้ฟรี ไม่มีรุ่นเสียเงิน — ดาวน์โหลด แตกไฟล์ แล้วกด เปิด Dock ได้เลย"
                       en="Every feature is free. Download, unzip and switch the dock on." />
            <x-xp.rows :items="$rows" />
        </div>
    </section>

    {{-- ========================== HOW IT WORKS ========================== --}}
    <section class="relative py-20 isolate">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="A real dock" title="ทำงานแบบ Dock" title2="ไม่ใช่แค่แถบไอคอน"
                       sub="เห็นแอปที่เปิดอยู่ ย้ายไอคอนเดสก์ท็อปมาไว้ใน Dock เรียงตัวที่ใช้บ่อยขึ้นก่อน และเปิดพร้อม Windows"
                       en="Open apps, your desktop items, most-used first, and it starts with Windows." />
            <x-xp.explorer id="dock" label="การทำงานของ MacDock" :tools="$tools" />
        </div>
    </section>

    {{-- ============================ SAFETY ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Always undoable" title="เปลี่ยนหน้าตา Windows" title2="แล้วคืนได้ทุกเมื่อ"
                       sub="MacDock ไม่ลบอะไรของคุณ แค่ซ่อน — และคืนให้ครบเมื่อปิด"
                       en="It hides, never deletes, and puts everything back." />
            <x-xp.cards :items="[
                ['icon' => 'undo', 'title' => 'ปิดแล้วคืนให้ครบ', 'body' => 'กด ปิด Dock หรือออกจากโปรแกรม แถบงาน Windows และไอคอนเดสก์ท็อปกลับมาทันที ไฟล์บนเดสก์ท็อปอยู่ที่เดิมตลอด', 'en' => 'Switch off or quit and everything is back'],
                ['icon' => 'shield', 'title' => 'ค้างกลางทางก็ซ่อมเอง', 'body' => 'MacDock จดสถานะเดิมของแถบงานไว้ ถ้าโปรแกรมปิดตัวผิดปกติตอนซ่อนอยู่ เปิดครั้งถัดไปคืนให้เอง หรือสั่ง MacDock.exe --restore', 'en' => 'Repairs a crashed session on the next start'],
                ['icon' => 'user', 'title' => 'ไม่ต้องใช้สิทธิ์ผู้ดูแล', 'body' => 'ทำงานในสิทธิ์ผู้ใช้ปกติ เปิดพร้อม Windows บันทึกแค่ในบัญชีผู้ใช้ของคุณ', 'en' => 'Runs as you, no Administrator needed'],
                ['icon' => 'chip', 'title' => 'พกพาได้', 'body' => 'การตั้งค่าเก็บข้างไฟล์ MacDock.exe ยกทั้งโฟลเดอร์ไปเครื่องอื่นได้ (ถ้าโฟลเดอร์นั้นเขียนไม่ได้ จะเก็บใน AppData แทน)', 'en' => 'Settings travel with the exe'],
                ['icon' => 'lock', 'title' => 'อัปเดตที่ตรวจไฟล์', 'body' => 'โหลดจาก xman4289.com ทาง HTTPS เท่านั้น ตรวจขนาด SHA-256 และเวอร์ชันในไฟล์ก่อนแทนที่ และถามคุณก่อนติดตั้งทุกครั้ง', 'en' => 'Size, SHA-256 and version checked, and it asks first'],
                ['icon' => 'eye', 'title' => 'คุยกับเว็บแค่ที่จำเป็น', 'body' => 'ติดต่อ xman4289.com เพื่อลงทะเบียนเครื่อง (รหัสเครื่องแบบแฮช ชื่อเครื่อง เวอร์ชัน) ตรวจไลเซ่น และเช็กอัปเดตเท่านั้น ไอคอนและสถิติการใช้งานอยู่ในเครื่อง', 'en' => 'Registers the PC, checks the licence and updates — nothing else'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICE + FREE LICENCE =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Free · free licence" title="ใช้ฟรี" title2="อยากได้อัปเดตเองก็รับไลเซ่นฟรี"
                       sub="ไม่มีไลเซ่นก็ใช้ Dock ได้ครบทุกฟีเจอร์ — ไลเซ่นฟรีมีไว้เปิดการอัปเดตอัตโนมัติเท่านั้น"
                       en="Everything works without a licence. The free licence only turns on automatic updates." />

            <div class="grid lg:grid-cols-2 gap-5 max-w-5xl mx-auto">
                <div class="xp-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">MacDock</h3>
                        <x-xp.badge tier="free" />
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ทุกฟีเจอร์ ไม่จำกัดเวลา" en="Every feature, no time limit" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿{{ $price }}</span></div>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['Dock 4 ขอบจอ · ขยายตามเมาส์ · ซ่อนอัตโนมัติ', 'หมวดอัตโนมัติ · เรียงตามที่ใช้บ่อย', 'นาฬิกาเข็ม / ดิจิทัล', 'ซ่อนไอคอนเดสก์ท็อปและแถบงาน', 'ไม่ต้องล็อกอินเพื่อดาวน์โหลด']" />
                    <x-xp.download block :href="route('macdock.download')" :coming-soon="$csDownload" />
                </div>

                <div class="xp-card p-7 flex flex-col scroll-mt-24" id="license">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">ไลเซ่นฟรี</h3>
                        <x-xp.badge tier="free" label="฿0 · FREE" />
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="เปิดการอัปเดตอัตโนมัติ" en="Turns on automatic updates" /></p>
                    <ol class="space-y-4 text-sm mb-6 flex-1">
                        @foreach([
                            ['ในโปรแกรม เปิดเมนู "ไลเซ่น & อัปเดต..."', 'คลิกขวาที่ Dock หรือที่ไอคอน MacDock ในถาดระบบ'],
                            ['กด "สมัครสมาชิก / เข้าสู่ระบบ เพื่อรับไลเซ่นฟรี"', 'เบราว์เซอร์เปิดหน้ารับไลเซ่นฟรีบน xman4289.com พร้อมรหัสเครื่องนี้'],
                            ['เข้าสู่ระบบหรือสมัคร แล้วกด "ยืนยัน รับไลเซ่นฟรี"', 'ไลเซ่นเข้าโปรแกรมเองภายในไม่กี่วินาที ไม่ต้องพิมพ์คีย์'],
                        ] as $n => [$step, $detail])
                            <li class="flex gap-4">
                                <span class="flex-none w-10 h-10 rounded-xl xp-btn-main flex items-center justify-center font-black text-lg">{{ $n + 1 }}</span>
                                <span>
                                    <span class="block text-white font-bold">{{ $step }}</span>
                                    <span class="block text-gray-300 mt-0.5">{{ $detail }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                    <x-xp.ticks class="!space-y-2.5 text-sm" :items="['ผูกกับบัญชีและเครื่องนั้น — 1 เครื่อง 1 ไลเซ่น มีหลายเครื่องก็รับฟรีได้ทุกเครื่อง', 'ไม่มีวันหมดอายุ ดูได้ในหน้า ไลเซ่นของฉัน', 'ตรวจอัปเดตทุก 6 ชั่วโมง ถามก่อนติดตั้ง ปิดได้ในหน้าต่างไลเซ่น']" />
                    @if($myFreeKeys > 0)
                        <a href="{{ route('customer.licenses') }}" class="xp-link mt-6 text-sm font-semibold">บัญชีนี้มีไลเซ่นฟรี {{ $myFreeKeys }} เครื่อง — ดูไลเซ่นของฉัน →</a>
                    @endif
                </div>
            </div>

            <div class="max-w-5xl mx-auto mt-10">
                <x-xp.screen :src="$shot('license.webp')" :mock="true"
                             alt="ภาพตัวอย่างหน้าต่าง ไลเซ่น & อัปเดต ของ MacDock หลังได้รับไลเซ่นฟรี: คีย์ เครื่องที่ผูก และตัวเลือกอัปเดตอัตโนมัติ" />
            </div>
            <p class="text-center text-xs text-gray-500 mt-6">ไม่มีไลเซ่นก็ดาวน์โหลดเวอร์ชันใหม่จากหน้านี้ได้เสมอ · เครื่องที่รับไลเซ่นไว้กับบัญชีอื่นแล้ว รับซ้ำกับอีกบัญชีไม่ได้</p>
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows 10 หรือ Windows 11 แบบ 64-bit', 'Windows 10/11, 64-bit'],
            ['ไม่ต้องติดตั้ง .NET', 'MacDock.exe ไฟล์เดียวใน zip เปิดได้เลย', 'Self-contained, single file'],
            ['ไม่ต้องใช้สิทธิ์ผู้ดูแล', 'ทำงานในสิทธิ์ผู้ใช้ปกติ', 'No Administrator needed'],
            ['อินเทอร์เน็ต', 'ไม่จำเป็นต่อการใช้ Dock — ใช้ตอนรับไลเซ่นฟรีและอัปเดต', 'Only for the licence and updates'],
        ]"
        specs-note="Dock อยู่บนจอหลัก · มีหลายจอ แถบงานของทุกจอถูกซ่อน (ปิดได้ในเมนู Hide Windows taskbar)"
        :steps="[
            ['ดาวน์โหลดไฟล์ .zip', 'ไฟล์มาจาก xman4289.com โดยตรง ไม่ต้องล็อกอิน', 'Download the .zip'],
            ['แตกไฟล์ เปิด MacDock.exe แล้วกด เปิด Dock', 'ครั้งแรก MacDock ดึงไอคอนบนเดสก์ท็อปมาไว้ใน Dock ซ่อนไอคอนเดสก์ท็อปและแถบงาน — คลิกขวาที่ Dock เพื่อปรับ', 'Unzip, run MacDock.exe, switch the dock on'],
            ['รับไลเซ่นฟรีเพื่อเปิดอัปเดตอัตโนมัติ', 'เมนู ไลเซ่น & อัปเดต → สมัครหรือเข้าสู่ระบบ → กดยืนยันบนเว็บ', 'Get the free licence for updates'],
        ]" />

    {{-- ============================ RELEASE NOTES ============================ --}}
    @if($releaseNotes)
        <section class="relative py-20 scroll-mt-20" id="whats-new">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-8">
                    <p class="xp-eyebrow mb-3">What's new</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white">มีอะไรใหม่ @if($latest)<span class="xp-text-glow">v{{ $latest->version }}</span>@endif</h2>
                </div>
                <div class="xp-card p-6 sm:p-8">
                    <div class="prose prose-invert prose-sm max-w-none prose-a:text-sky-300 prose-strong:text-white">
                        {{-- release notes เป็น markdown: แปลงเป็น HTML เอง ตัด HTML ดิบทิ้ง --}}
                        {!! Str::markdown($releaseNotes, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                </div>
            </div>
        </section>
    @endif

    <x-xp.faq :items="[
        ['ฟรีจริงไหม?', 'ฟรีจริง ทุกฟีเจอร์ใช้ได้ไม่จำกัดเวลา ไม่มีรุ่นเสียเงิน ดาวน์โหลดได้เลยโดยไม่ต้องล็อกอิน'],
        ['ไลเซ่นฟรีมีไว้ทำอะไร ต้องรับไหม?', 'ไม่ต้องรับก็ใช้ Dock ได้ครบ ไลเซ่นฟรีเปิดการอัปเดตอัตโนมัติ: โปรแกรมตรวจเวอร์ชันใหม่ทุก 6 ชั่วโมง ถามก่อน แล้วอัปเดตและเปิดใหม่ให้เอง ถ้าไม่รับ ดาวน์โหลดเวอร์ชันใหม่จากหน้านี้เองได้เสมอ'],
        ['รับไลเซ่นฟรีอย่างไร?', 'ในโปรแกรม คลิกขวาที่ Dock → ไลเซ่น & อัปเดต... → สมัครสมาชิก / เข้าสู่ระบบ เพื่อรับไลเซ่นฟรี เบราว์เซอร์จะเปิดหน้ายืนยันพร้อมรหัสเครื่อง เข้าสู่ระบบแล้วกด ยืนยัน รับไลเซ่นฟรี ไลเซ่นจะเข้าโปรแกรมเองภายในไม่กี่วินาที'],
        ['มีหลายเครื่องทำอย่างไร?', 'ไลเซ่นฟรีผูกกับบัญชีและเครื่องที่กดรับ 1 เครื่องต่อ 1 ไลเซ่น — เปิด MacDock ที่เครื่องอื่นแล้วกดรับอีกครั้งด้วยบัญชีเดิม ได้ฟรีทุกเครื่อง ดูทั้งหมดได้ในหน้า ไลเซ่นของฉัน'],
        ['แถบงาน Windows กับไอคอนเดสก์ท็อปหายไปไหน?', 'MacDock ซ่อนไว้เป็นค่าเริ่มต้นให้หน้าจอโล่ง ไม่ได้ลบ ปิดการซ่อนได้ในเมนูคลิกขวา (Hide desktop icons / Hide Windows taskbar) และเมื่อปิด Dock หรือออกจากโปรแกรม ทุกอย่างกลับมาเอง ถ้าเครื่องค้างระหว่างซ่อน เปิด MacDock ใหม่หรือสั่ง MacDock.exe --restore'],
        ['แถบงานถูกซ่อน แล้วไอคอนเสียง เน็ต ดูตรงไหน?', 'เปิดนาฬิกาของ MacDock แล้วดับเบิลคลิกที่นาฬิกา (หรือคลิกขวา → แสดงไอคอนถาดระบบ) แถบงานจะโผล่ให้ใช้ถาดระบบชั่วคราว'],
        ['เอาไอคอนออกจาก Dock แล้วไฟล์หายไหม?', 'ไม่หาย Remove from Dock เอาออกแค่ไอคอนใน Dock ไฟล์และช็อตคัตบนเดสก์ท็อปอยู่ที่เดิม ดึงกลับมาได้ด้วย Re-import Desktop items'],
        ['ย้ายไปเครื่องใหม่ได้ไหม?', 'ได้ การตั้งค่า (ไอคอน หมวด ตำแหน่ง) เก็บข้างไฟล์ MacDock.exe ยกทั้งโฟลเดอร์ไปได้ ส่วนไลเซ่นฟรีผูกกับเครื่อง ที่เครื่องใหม่กดรับไลเซ่นฟรีอีกครั้ง'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="เปลี่ยน Windows " glow="ให้มี Dock"
              sub="ดาวน์โหลดฟรี แตกไฟล์ แล้วเปิดใช้ได้ทันที ไม่ต้องติดตั้ง ไม่ต้องสมัคร"
              en="Free. Unzip, run, switch it on.">
        <x-xp.download :href="route('macdock.download')" :coming-soon="$csDownload" class="!px-8" />
        <a href="#license" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ไลเซ่นฟรีคืออะไร</a>
        @if($myFreeKeys > 0)
            <a href="{{ route('customer.licenses') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดูไลเซ่นของฉัน</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10/11 (64-bit) · ไม่ต้องลง .NET · ไม่ต้องใช้สิทธิ์ผู้ดูแล · ใช้ฟรีทุกฟีเจอร์</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
