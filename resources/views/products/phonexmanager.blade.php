@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ไม่มีปุ่มดาวน์โหลด ไม่มีทางซื้อ (กฎเจ้าของ 2026-10-12) --}}
@php $csPhoneX = $product->isComingSoon(); @endphp

@section('title', 'PhoneX Manager — ชุดเครื่องมือช่างมือถือ Android บน Windows · ADB · Fastboot · ไดรเวอร์ · ค้นรอม | XMAN Studio')
@section('meta_description', 'PhoneX Manager ดูข้อมูลเครื่องผ่าน ADB/Fastboot แฟลชและสำรองพาร์ทิชัน ค้นรอมจากหลายแหล่ง ติดตั้งไดรเวอร์และเครื่องมือแฟลชของ Samsung, Qualcomm, MediaTek ในคลิกเดียว พร้อม Hex Editor, ADB terminal, logcat และจอสะท้อนผ่าน Scrcpy · Windows 10/11 · An Android service suite for Windows.')
@section('og_image', asset('images/products/phonex-manager/v2/og.jpg'))

@section('content')
@php
    // ราคามาจากแถวสินค้าในร้าน ($product->price) — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $priceText = number_format((float) $product->price);

    // ซื้อแล้ว: ไฟล์อยู่ในหน้า บัญชีของฉัน › ดาวน์โหลด (กลไกเดิมของหน้านี้ — ไม่มีลิงก์ดาวน์โหลดสาธารณะ)
    $ownsPhoneX = auth()->check() && $hasPurchased;

    // เวอร์ชันที่ร้านส่งให้ — อ่านจาก DB อย่างเดียว
    $latest = $product->latestVersion();
    $opensAt = $csPhoneX && $product->coming_soon_until ? $product->coming_soon_until->format('d/m/Y') : null;

    // ยังไม่มีภาพจริงจากแอป: ทุกหน้าจอเป็น mockup ที่วาดจาก XAML ของแอป (D:\Code\Phone\PhoneRomFlashTool)
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py phonex-manager
    $shot = fn (string $file) => asset('images/products/phonex-manager/v2/' . $file);

    // งานประจำวัน — เช็กกับแท็บ Device / Setup / ROM Search ของแอป
    $benchRows = [
        [
            'img' => $shot('device.webp'), 'mock' => true, 'name' => 'เห็นเครื่องทันทีที่เสียบสาย', 'eyebrow' => 'Device',
            'th' => 'เลือกเครื่องจากรายการด้านบน แล้วเห็นยี่ห้อ รุ่น เวอร์ชัน Android, Serial, โหมดที่อยู่ (Normal / Recovery / Fastboot) และช่องทางเชื่อมต่อ สั่งรีบูตเข้า Recovery, Fastboot หรือกลับเข้าระบบได้ในคลิกเดียว',
            'en' => 'Brand, model, Android version, serial, mode and connection, with one-click reboot to recovery, fastboot or system.',
            'points' => ['โหลดรายการพาร์ทิชันของเครื่องไว้ใช้ต่อในแท็บ Backup และ Flash (ต้องมีสิทธิ์ root)', 'สถานะเชื่อมต่อและรุ่นเครื่องอยู่ที่แถบล่างตลอด ไม่ว่าเปิดแท็บไหน'],
            'alt' => 'ภาพตัวอย่างแท็บ Device ของ PhoneX Manager: ข้อมูลเครื่อง ปุ่มรีบูต และรายการพาร์ทิชัน',
        ],
        [
            'img' => $shot('setup.webp'), 'mock' => true, 'name' => 'ไดรเวอร์และเครื่องมือ ติดตั้งในคลิกเดียว', 'eyebrow' => 'Setup',
            'th' => 'ปุ่ม Install Everything ดาวน์โหลดและติดตั้ง Android Platform Tools (ADB + Fastboot) ไดรเวอร์ Google USB, Samsung, Qualcomm QDLoader 9008 และ MediaTek Preloader พร้อมเครื่องมือแฟลชเฉพาะยี่ห้อ ทุกรายการบอกว่าติดตั้งแล้วหรือยัง ลบออกได้ และมีปุ่มเปิดหน้าดาวน์โหลดถ้าอยากติดตั้งเอง',
            'en' => 'Install Everything fetches ADB + Fastboot, the Google, Samsung, Qualcomm 9008 and MediaTek drivers and the brand flashing tools.',
            'points' => ['ติดตั้งไดรเวอร์ต้องใช้สิทธิ์ Administrator', 'EDL Client และ MTK Client ต้องมี Python · Xiaomi ADB/Fastboot Tools ต้องมี Java · EDL-NG ต้องมี .NET 9'],
            'alt' => 'ภาพตัวอย่างแท็บ Setup ของ PhoneX Manager: ปุ่ม Install Everything รายการเครื่องมือและไดรเวอร์',
        ],
        [
            'img' => $shot('romsearch.webp'), 'mock' => true, 'name' => 'ค้นรอมจากหลายแหล่งในช่องเดียว', 'eyebrow' => 'ROM Search',
            'th' => 'พิมพ์รุ่นหรือ codename แล้วค้นจาก SamFW, SamMobile, Xiaomi Firmware, LineageOS, Pixel Experience, TWRP, XDA และ APKMirror พร้อมกัน กรองรอมศูนย์ รอมคอมมูนิตี้ หรือ Recovery แล้วเปิดหน้าดาวน์โหลดหรือคัดลอกลิงก์',
            'en' => 'One search across eight ROM sources, filtered by stock, custom or recovery, straight to the download page.',
            'points' => ['ROM Database ในเครื่อง แยกหมวด Stock, Custom, Recovery, Rare/Legacy, Firmware, Unbrick พร้อมระดับความเสี่ยง', 'คู่มือปลดล็อก bootloader ภาษาไทย 8 ยี่ห้อ/กลุ่ม บอกสิ่งที่ต้องเตรียม ขั้นตอน และเวลาที่ต้องรอ'],
            'alt' => 'ภาพตัวอย่างแท็บ ROM Search ของ PhoneX Manager: ช่องค้นหา ผลจากหลายแหล่ง และรายละเอียดรอม',
        ],
    ];

    // เครื่องมือช่าง — เช็กกับแท็บ Flash / Backup / Tools / Hex Editor และบริการในโค้ดของแอป
    $benchTools = [
        [
            'key' => 'flash', 'name' => 'แฟลชอิมเมจ', 'short' => 'Fastboot · boot / recovery / vbmeta / super',
            'th' => 'เลือกไฟล์ .img แล้วแฟลชลงพาร์ทิชันที่ต้องการ เครื่องในโหมด Fastboot แฟลชผ่าน fastboot โดยตรง ส่วนในโหมดปกติหรือ Recovery แฟลชผ่าน ADB ได้เมื่อเครื่องมีสิทธิ์ root',
            'en' => 'Flash an .img to a partition over fastboot, or over ADB on a rooted phone.',
            'points' => ['แท็บ Fastboot: devices, getvar all, reboot และเลือกพาร์ทิชัน boot, recovery, system, vendor, vbmeta, dtbo, super', 'ปลดล็อก / ล็อก bootloader ของเครื่องที่รองรับ — การปลดล็อกล้างข้อมูลทั้งหมดในเครื่อง'],
        ],
        [
            'key' => 'backup', 'name' => 'สำรองพาร์ทิชันและ EFS', 'short' => 'ต้องมีสิทธิ์ root',
            'th' => 'สำรองพาร์ทิชันที่เลือก หรือทั้งชุด (boot, recovery, system, vendor, userdata) ลงคอม แยกโฟลเดอร์ตามรุ่นและเวลา พร้อมไฟล์ข้อมูลกำกับและค่า SHA-256 ดูย้อนหลังได้ใน Backup History และสำรองพาร์ทิชัน EFS ไว้คืนกลับเองได้ภายหลัง',
            'en' => 'Back up one partition or the whole set, with SHA-256, plus an EFS backup you can restore later. Needs root.',
            'points' => ['ทุกงานสำรองในส่วนนี้ต้องมีสิทธิ์ root บนเครื่อง (ใช้ dd)'],
        ],
        [
            'key' => 'chips', 'name' => 'Qualcomm · MediaTek', 'short' => 'EDL 9008 · BROM / Preloader',
            'img' => $shot('tools.webp'), 'mock' => true,
            'th' => 'ติดตั้งและเปิด EDL Client กับ EDL-NG สำหรับเครื่อง Qualcomm ในโหมด EDL และ MTK Client สำหรับ MediaTek ในโหมด BROM/Preloader ใช้อ่าน/เขียนพาร์ทิชันและแฟลชรอมศูนย์ พร้อมวิธีเข้าโหมดแต่ละแบบเขียนไว้ในการ์ด',
            'en' => 'Install and launch EDL Client, EDL-NG and MTK Client for Qualcomm EDL and MediaTek BROM work.',
            'points' => ['เป็นโปรเจกต์โอเพนซอร์สของผู้พัฒนาอื่น PhoneX ดาวน์โหลดและเปิดให้จากในโปรแกรม'],
            'alt' => 'ภาพตัวอย่างแท็บ Tools › Qualcomm/MTK ของ PhoneX Manager: EDL Client, EDL-NG, MTK Client',
        ],
        [
            'key' => 'brands', 'name' => 'Samsung · Xiaomi', 'short' => 'Thor · Heimdall · Freya · SamFirm',
            'th' => 'ติดตั้งและเปิดเครื่องมือแฟลช Samsung จากในโปรแกรม: Thor, Heimdall, Freya (อ่าน/แฟลช/แบ่งพาร์ทิชันด้วยไฟล์ PIT) และ SamFirm สำหรับดาวน์โหลดเฟิร์มแวร์ศูนย์ ส่วนเครื่อง Xiaomi เปิด Xiaomi ADB/Fastboot Tools ได้จากแท็บ Utilities',
            'en' => 'Samsung flashing tools (Thor, Heimdall, Freya, SamFirm) and Xiaomi ADB/Fastboot Tools, one click away.',
            'points' => ['พร้อมไดรเวอร์ Samsung USB สำหรับโหมด Download'],
        ],
        [
            'key' => 'recovery', 'name' => 'Recovery', 'short' => 'แฟลช · บูตชั่วคราว',
            'th' => 'ตรวจ recovery ที่ติดตั้งอยู่ แฟลช recovery ใหม่ผ่าน Fastboot หรือบูต recovery ชั่วคราวโดยไม่เขียนทับของเดิม แล้วสั่งรีบูตเข้า Recovery',
            'en' => 'Detect, flash or temporarily boot a recovery image over fastboot.',
            'points' => [],
        ],
        [
            'key' => 'debloat', 'name' => 'ลบแอปที่ติดมากับเครื่อง', 'short' => 'ปิดหรือถอนแบบคืนได้',
            'th' => 'สแกนแอปที่ติดมากับเครื่องจากรายชื่อของ Samsung, Xiaomi, Google, Huawei, OPPO และ vivo พร้อมระดับความเสี่ยง (Safe / Caution) แล้วเลือกปิด หรือถอนเฉพาะผู้ใช้ปัจจุบัน',
            'en' => 'Disable or uninstall pre-installed apps for the current user only, with a risk label on each.',
            'points' => ['ถอนแบบ --user 0 ไฟล์ระบบยังอยู่ กด Enable All เปิดกลับได้'],
        ],
        [
            'key' => 'mirror', 'name' => 'จอสะท้อน · Logcat', 'short' => 'Scrcpy · ภาพหน้าจอ · ADB terminal',
            'img' => $shot('mirror.webp'), 'mock' => true,
            'th' => 'สะท้อนและควบคุมจอมือถือบนคอมผ่าน Scrcpy ตั้ง bitrate และ FPS แสดงจุดแตะ กันจอดับ และอัดเป็นไฟล์ได้ แท็บ Device Info โหลดข้อมูลเครื่อง จับภาพหน้าจอ และดึง logcat ตามจำนวนบรรทัดที่ต้องการ',
            'en' => 'Scrcpy mirroring and recording, device info, screenshots and logcat.',
            'points' => ['ADB terminal พร้อมปุ่มคำสั่งที่ใช้บ่อย: devices, shell, reboot, recovery, bootloader, sideload', 'ตรวจ MD5 / SHA1 / SHA256 ของไฟล์ก่อนแฟลช'],
            'alt' => 'ภาพตัวอย่างแท็บ Tools › Utilities ของ PhoneX Manager: Scrcpy, Xiaomi Tools, ตรวจ checksum',
        ],
        [
            'key' => 'hex', 'name' => 'Hex Editor', 'short' => 'เปิดไฟล์อิมเมจ · ค้น hex / ข้อความ',
            'img' => $shot('hex.webp'), 'mock' => true,
            'th' => 'เปิดไฟล์อิมเมจดูเป็น hex และ ASCII ค้นหาด้วย hex หรือข้อความ แก้แล้วย้อนกลับได้ (Undo / Undo All) บอกชนิดไฟล์ให้ เช่น Android Boot Image, EXT4, GPT แล้วส่งออก hex dump หรือผลวิเคราะห์',
            'en' => 'Hex and ASCII view of an image file, hex or text search, undo, file-type detection and exports.',
            'points' => ['มีหน้าต่าง Hex Editor เต็มรูปแบบแยกอีกหน้าต่าง'],
            'alt' => 'ภาพตัวอย่างแท็บ Hex Editor ของ PhoneX Manager: ตาราง hex/ASCII ผลค้นหา และชนิดไฟล์',
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'PhoneX Manager',
        'operatingSystem' => 'Windows 10, Windows 11 (64-bit)',
        'applicationCategory' => 'UtilitiesApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'ชุดเครื่องมือช่างมือถือ Android บน Windows: ข้อมูลเครื่องผ่าน ADB/Fastboot แฟลชและสำรองพาร์ทิชัน ค้นรอม ไดรเวอร์ และเครื่องมือแฟลชของ Samsung, Qualcomm, MediaTek',
        'image' => asset('images/products/phonex-manager/v2/og.jpg'),
        'url' => route('products.show', 'phonex-manager'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => ['@type' => 'Offer', 'price' => (string) (float) $product->price, 'priceCurrency' => 'THB'],
    ];
@endphp
<x-xp.page a="#22d3ee" b="#818cf8" c="#67e8f9" glow="0 212 255" g1="#00d4ff" g2="#818cf8" g3="#c084fc">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="ชุดเครื่องมือช่างมือถือ Android ในโปรแกรมเดียว"
               tagline-en="An Android service suite for the repair bench, on Windows."
               lead="ดูข้อมูลเครื่องผ่าน ADB/Fastboot แฟลชและสำรองพาร์ทิชัน ค้นรอมจากหลายแหล่ง และติดตั้งไดรเวอร์กับเครื่องมือแฟลชของ Samsung, Qualcomm และ MediaTek จากหน้าเดียว — พร้อม Hex Editor, ADB terminal, logcat และจอสะท้อนผ่าน Scrcpy"
               lead-en="Device info over ADB/Fastboot, partition flashing and backup, a multi-source ROM finder, one-click drivers and flashing tools, a hex editor, ADB terminal, logcat and Scrcpy.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'PhoneX Manager ' . $latest->version : 'PhoneX Manager' }}</span>
            <span class="xp-chip">Windows 10/11 · 64-bit</span>
            @if($csPhoneX)<x-xp.badge tier="soon" label="Coming Soon" />@endif
        </x-slot:chips>
        <x-slot:title>Phone<span class="xp-text-glow">X</span> Manager</x-slot:title>
        <x-slot:actions>
            @if($csPhoneX)
                <x-xp.download :href="route('customer.downloads')" :coming-soon="true" />
            @elseif($ownsPhoneX)
                <x-xp.download :href="route('customer.downloads')" th="ดาวน์โหลด" en="Download" />
            @else
                <a href="{{ route('products.index') }}" class="xp-btn-main inline-flex items-center px-7 py-4 rounded-xl font-extrabold text-lg transition">ดูแพคเกจ</a>
            @endif
            <a href="#pricing" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">ราคา ฿{{ $priceText }}</a>
        </x-slot:actions>
        <x-slot:notes>
            @if($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif</p>
            @endif
            @if($opensAt)
                <p class="mt-3 text-sm text-gray-400">กำหนดเปิด {{ $opensAt }}</p>
            @endif
            @if($ownsPhoneX && ! $csPhoneX)
                <p class="mt-3 text-sm text-cyan-300">คุณซื้อแล้ว · ไฟล์อยู่ในหน้า บัญชีของฉัน › ดาวน์โหลด</p>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ADB · Fastboot</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> Samsung · Qualcomm · MTK</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไดรเวอร์ในคลิกเดียว</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('device.webp')" :eager="true" :mock="true"
                             alt="ภาพตัวอย่างหน้าจอ PhoneX Manager: ข้อมูลเครื่องที่ต่ออยู่ ปุ่มรีบูต และรายการพาร์ทิชัน" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">เชื่อมต่อ · Connect</p>
                <p class="text-white font-extrabold text-lg leading-tight">ADB · <span class="text-cyan-300">Fastboot</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ชิป · Chipsets</p>
                <p class="text-white font-bold leading-tight">Qualcomm EDL<br><span class="text-gray-400 text-sm font-normal">MediaTek BROM</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== BENCH ROWS ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Everyday bench work" title="เสียบสาย" title2="แล้วเริ่มงานได้เลย"
                       sub="ดูเครื่อง ติดตั้งไดรเวอร์ และหารอมที่ตรงรุ่น ครบในโปรแกรมเดียว ไม่ต้องสลับหลายหน้าต่าง"
                       en="See the phone, install the drivers and find the right ROM in one place." />
            <x-xp.rows :items="$benchRows" />
        </div>
    </section>

    {{-- =========================== TOOLS =========================== --}}
    <section class="relative py-20 isolate" id="tools">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="Technician tools" title="เครื่องมือช่าง" title2="ในแท็บเดียว"
                       sub="แฟลช สำรอง เครื่องมือของแต่ละชิปและยี่ห้อ Recovery จอสะท้อน และ Hex Editor"
                       en="Flashing, backups, chipset and brand tools, recovery, mirroring and a hex editor." />
            <x-xp.explorer id="tools" label="เครื่องมือของ PhoneX Manager" :tools="$benchTools" />
        </div>
    </section>

    {{-- ============================ SAFETY ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Backup first" title="สำรองก่อน" title2="แฟลชทีหลัง"
                       sub="การแฟลชผิดไฟล์ทำให้เครื่องบูตไม่ขึ้นได้ โปรแกรมจึงมีของที่ช่วยให้ย้อนกลับได้"
                       en="A wrong flash can stop a phone from booting, so the way back is built in." />
            <x-xp.cards :items="[
                ['icon' => 'shield', 'title' => 'สำรองพาร์ทิชันก่อน', 'body' => 'แท็บ Backup เก็บพาร์ทิชันลงคอม แยกตามรุ่นและเวลา พร้อมค่า SHA-256 และสำรอง EFS ได้อีกชั้น (ต้องมีสิทธิ์ root)', 'en' => 'Partition and EFS backups, with SHA-256'],
                ['icon' => 'undo', 'title' => 'ลบแอปแบบคืนได้', 'body' => 'ปิดหรือถอนเฉพาะผู้ใช้ปัจจุบัน ไม่แตะไฟล์ระบบ กด Enable All เปิดกลับทั้งหมด', 'en' => 'Debloat for the current user only, reversible'],
                ['icon' => 'key', 'title' => 'ตรวจไฟล์ก่อนแฟลช', 'body' => 'คำนวณ MD5 / SHA1 / SHA256 แล้วเทียบกับค่าที่แหล่งรอมให้มา', 'en' => 'Checksums before you flash'],
                ['icon' => 'doc', 'title' => 'คำเตือนในหน้า Flash', 'body' => 'แบตควรเกิน 50% อย่าถอดสายระหว่างแฟลช ใช้ไฟล์ที่ตรงรุ่น และให้เครื่องอยู่ในโหมดที่ถูกต้อง', 'en' => 'The flashing checklist, on the Flash tab'],
                ['icon' => 'download', 'title' => 'เครื่องมือจากหน้าเผยแพร่ของแต่ละตัว', 'body' => 'ไดรเวอร์และเครื่องมือเสริมดาวน์โหลดจากหน้าเผยแพร่ของแต่ละตัว และมีปุ่มเปิดหน้าดาวน์โหลดถ้าอยากติดตั้งเอง', 'en' => 'Each tool from its own release page'],
                ['icon' => 'eye', 'title' => 'เห็นทุกขั้นใน Log', 'body' => 'ทุกงานขึ้นใน Log พร้อมเวลา และมีหน้าต่าง Debug ให้ดูรายละเอียดเพิ่ม', 'en' => 'Every step in the log'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="ราคาเดียว" title2="ได้ทุกเครื่องมือในโปรแกรม"
                       :sub="$csPhoneX ? 'ยังไม่เปิดขาย (Coming Soon) — ราคาด้านล่างคือราคาที่ร้านตั้งไว้ตอนนี้' : 'ซื้อแล้วดาวน์โหลดได้ในหน้า บัญชีของฉัน › ดาวน์โหลด'"
                       en="One price, every tool in the app." />

            <div class="max-w-md mx-auto xp-card p-7 flex flex-col">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-2xl font-black text-white">PhoneX Manager</h3>
                    @if($csPhoneX)<x-xp.badge tier="soon" label="Coming Soon" />@endif
                </div>
                <p class="text-gray-400 text-sm mb-6"><x-bi th="ชุดเครื่องมือช่างมือถือ Android" en="Android service suite" /></p>
                <div class="mb-6"><span class="text-5xl font-black text-white">฿{{ $priceText }}</span></div>
                <x-tpix-price variant="detail" :thb="$product->price" :tpix="$product->price_tpix" class="-mt-4 mb-6" />
                <x-xp.ticks class="!space-y-2.5 text-sm mb-8" :items="['ข้อมูลเครื่อง · รีบูตโหมด · ADB terminal', 'แฟลชอิมเมจผ่าน Fastboot', 'สำรองพาร์ทิชันและ EFS (ต้อง root)', 'ค้นรอม 8 แหล่ง · ROM Database · คู่มือ bootloader', 'ติดตั้งไดรเวอร์และเครื่องมือ Samsung / Qualcomm / MediaTek', 'Scrcpy · logcat · Hex Editor · ตรวจ checksum']" />
                @if($csPhoneX)
                    <span class="xp-btn-main xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                @elseif($ownsPhoneX)
                    <x-xp.download block :href="route('customer.downloads')" th="ดาวน์โหลด" en="Download" />
                @else
                    <a href="{{ route('products.index') }}" class="xp-btn-main block w-full py-3.5 text-center rounded-xl font-extrabold transition">ดูแพคเกจ</a>
                @endif
            </div>
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows 10/11 แบบ 64-bit', 'Windows 10/11, 64-bit'],
            ['.NET 8 Desktop Runtime', 'โปรแกรมสร้างด้วย .NET 8 (WPF) ต้องมี .NET 8 Runtime ในเครื่อง', '.NET 8 runtime required'],
            ['มือถือ', 'เปิด USB Debugging · การโหลดรายการพาร์ทิชัน สำรองพาร์ทิชัน และสำรอง/คืน EFS ต้องมีสิทธิ์ root', 'USB debugging on; backups need root'],
            ['เครื่องมือเสริม', 'Python สำหรับ EDL Client / MTK Client · Java สำหรับ Xiaomi Tools · .NET 9 สำหรับ EDL-NG', 'Python, Java or .NET 9 for some tools'],
            ['สิทธิ์ Administrator', 'ใช้ตอนติดตั้งไดรเวอร์', 'Admin to install drivers'],
            ['อินเทอร์เน็ต', 'ใช้ดาวน์โหลดเครื่องมือ ไดรเวอร์ และค้นหารอม', 'Online for tools, drivers and ROM search'],
        ]"
        :steps="[
            ['แตกไฟล์แล้วเปิด PhoneXManager.exe', 'ไฟล์อยู่ในหน้า บัญชีของฉัน › ดาวน์โหลด หลังซื้อ', 'Extract and run PhoneXManager.exe'],
            ['แท็บ ⚙ Setup › Install Everything', 'ติดตั้ง ADB/Fastboot ไดรเวอร์ และเครื่องมือที่ต้องใช้ในครั้งเดียว', 'Setup → Install Everything'],
            ['เปิด USB Debugging แล้วเสียบสาย', 'เลือกเครื่องจากรายการด้านบน แล้วใช้งานแต่ละแท็บได้เลย', 'Enable USB debugging, plug in, pick the phone'],
        ]" />

    <x-xp.faq :items="[
        ['ซื้อได้หรือยัง?', $csPhoneX ? 'ยังไม่ได้ PhoneX Manager ยังเป็น Coming Soon ยังไม่เปิดขายและยังไม่เปิดให้ดาวน์โหลด' . ($opensAt ? ' · กำหนดเปิด ' . $opensAt : '') : 'ราคา ฿' . $priceText . ' ซื้อแล้วดาวน์โหลดได้ในหน้า บัญชีของฉัน › ดาวน์โหลด'],
        ['ใช้กับมือถือยี่ห้อไหนได้?', 'งานผ่าน ADB/Fastboot ใช้ได้กับ Android ทั่วไปที่เปิด USB Debugging หรือเข้าโหมด Fastboot ได้ และมีเครื่องมือเฉพาะสำหรับ Samsung (โหมด Download), Qualcomm (EDL 9008), MediaTek (BROM/Preloader) และ Xiaomi'],
        ['ต้อง root เครื่องไหม?', 'งานส่วนใหญ่ไม่ต้อง เช่น ดูข้อมูลเครื่อง รีบูตโหมด แฟลชผ่าน Fastboot จอสะท้อน logcat และลบแอปที่ติดมากับเครื่อง แต่การโหลดรายการพาร์ทิชัน สำรองพาร์ทิชัน สำรอง/คืน EFS และแฟลชผ่าน ADB ต้องมีสิทธิ์ root'],
        ['แฟลชพลาดแล้วเครื่องพังได้ไหม?', 'ได้ การแฟลชไฟล์ผิดรุ่นหรือถอดสายระหว่างแฟลชทำให้เครื่องบูตไม่ขึ้นได้ โปรแกรมให้มาตามสภาพ (as-is) ควรสำรองพาร์ทิชันและ EFS ก่อนทุกครั้ง'],
        ['ปลดล็อก bootloader แล้วข้อมูลหายไหม?', 'หาย การปลดล็อก bootloader ล้างข้อมูลทั้งหมดในเครื่อง และบางยี่ห้อต้องขออนุญาตหรือรอเวลาจากผู้ผลิตก่อน ดูขั้นตอนของแต่ละยี่ห้อได้ในแท็บ Guides'],
        ['รอมดาวน์โหลดมาจากไหน?', 'จากแหล่งของแต่ละที่ (SamFW, SamMobile, Xiaomi Firmware, LineageOS, TWRP ฯลฯ) โปรแกรมค้นและพาไปหน้าดาวน์โหลดให้ ไม่ได้ฝากไฟล์รอมไว้ที่ xman4289.com ควรตรวจ checksum ก่อนแฟลชทุกครั้ง'],
        ['ใช้บน Mac หรือ Linux ได้ไหม?', 'ไม่ได้ PhoneX Manager เป็นโปรแกรม Windows (WPF, .NET 8) ใช้บน Windows 10/11 แบบ 64-bit'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="โต๊ะช่างที่" glow="ครบในจอเดียว"
              :sub="$csPhoneX ? 'PhoneX Manager ยังเป็น Coming Soon — ติดตามได้ที่หน้านี้' : 'ราคา ฿' . $priceText"
              en="Your Android bench, in one window.">
        @if($csPhoneX)
            <x-xp.download :href="route('customer.downloads')" :coming-soon="true" class="!px-8" />
        @elseif($ownsPhoneX)
            <x-xp.download :href="route('customer.downloads')" th="ดาวน์โหลด" en="Download" class="!px-8" />
        @else
            <a href="{{ route('products.index') }}" class="xp-btn-main inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูแพคเกจ</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10/11 (64-bit) · .NET 8 · ADB · Fastboot · Samsung · Qualcomm · MediaTek</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
