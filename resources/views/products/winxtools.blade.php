@extends($publicLayout ?? 'layouts.app')

@section('title', 'WinXTools 1.1 — ดูแลวินโดวส์ครบในโปรแกรมเดียว · ฟรี 8 เครื่องมือ · Pro ฿' . number_format(\App\Support\LicensePlans::price('winx-tools', 'yearly')) . '/ปี | XMAN Studio')
@section('meta_description', 'WinXTools 1.1: Defrag 3D ดูดิสก์หมุนจริง, 1-Click Full Optimize, ทดสอบความเร็วดิสก์, สแกนแบดบล็อก, Smart CPU, ล้างขยะรวม CapCut และคุมเน็ตทุกแอป ฟรี 8 เครื่องมือ Pro ฿' . number_format(\App\Support\LicensePlans::price('winx-tools', 'yearly')) . '/ปี ทดลอง Pro ฟรี 48 ชั่วโมง · Windows care in one app for Windows 10/11.')
@section('og_image', asset('images/products/winxtools/v11/og.jpg'))

@push('styles')
<style>
    /* WinXTools product page — scoped under .wxt, colours match the app and the intro video */
    .wxt { --lime: #b9ff3a; --mint: #43e08a; --cyan: #22d3ee; --violet: #8b5cf6; --amber: #ffcf3a; --orange: #ff8a1f; background: #04060d; color: #d7dce5; }
    .wxt-grid { background-image: linear-gradient(rgba(148, 163, 184, .07) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, .07) 1px, transparent 1px);
        background-size: 56px 56px; -webkit-mask-image: radial-gradient(ellipse 75% 65% at 50% 35%, #000 25%, transparent 75%); mask-image: radial-gradient(ellipse 75% 65% at 50% 35%, #000 25%, transparent 75%); }
    .wxt-glow { position: absolute; border-radius: 9999px; filter: blur(90px); pointer-events: none; }
    /* the AI art is painted on black: screen-blending adds its light to the page instead of sitting in a box,
       and the elliptical mask dissolves every edge */
    .wxt-art { mix-blend-mode: screen; pointer-events: none; user-select: none; max-width: none;
        -webkit-mask-image: radial-gradient(ellipse 58% 60% at 56% 50%, #000 42%, transparent 100%); mask-image: radial-gradient(ellipse 58% 60% at 56% 50%, #000 42%, transparent 100%); }
    .wxt-text-glow { background: linear-gradient(92deg, var(--lime) 0%, #7dffb1 40%, var(--cyan) 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .wxt-badge { display: inline-flex; align-items: center; gap: .3rem; font-weight: 800; font-size: .72rem; letter-spacing: .06em; line-height: 1; padding: .34rem .62rem .38rem; border-radius: .5rem; white-space: nowrap; }
    .wxt-free { background: linear-gradient(105deg, var(--lime), var(--mint)); color: #08130a; }
    .wxt-pro { background: linear-gradient(105deg, var(--amber), var(--orange)); color: #1a1004; }
    .wxt-chip { display: inline-flex; align-items: center; gap: .45rem; padding: .45rem .85rem; border-radius: 9999px; font-size: .85rem; color: #e6ebf3;
        background: rgba(255, 255, 255, .04); border: 1px solid rgba(255, 255, 255, .09); backdrop-filter: blur(6px); }
    .wxt-chip i { width: .45rem; height: .45rem; border-radius: 9999px; background: var(--lime); box-shadow: 0 0 10px var(--lime); }
    /* the app's own window: a glowing hairline frame, rounded inner corners, soft cyan bloom underneath */
    .wxt-window { position: relative; isolation: isolate; border-radius: 1.15rem; padding: 1px;
        background: linear-gradient(140deg, rgba(34, 211, 238, .65), rgba(139, 92, 246, .22) 42%, rgba(185, 255, 58, .5));
        box-shadow: 0 40px 90px -30px rgba(34, 211, 238, .35), 0 20px 50px -20px rgba(0, 0, 0, .8); }
    .wxt-window > div { border-radius: calc(1.15rem - 1px); overflow: hidden; background: #060a16; }
    .wxt-window img { display: block; width: 100%; height: auto; }
    .wxt-window::after { content: ""; position: absolute; left: 8%; right: 8%; bottom: -34px; height: 46px; border-radius: 50%;
        background: radial-gradient(ellipse 50% 50% at 50% 50%, rgba(34, 211, 238, .28), transparent 70%); filter: blur(12px); z-index: -1; }
    .wxt-tilt { transform: perspective(1800px) rotateY(-9deg) rotateX(3deg); transform-origin: 60% 50%; transition: transform .6s ease; }
    .wxt-tilt:hover { transform: perspective(1800px) rotateY(-3deg) rotateX(1deg); }
    .wxt-card { background: linear-gradient(180deg, rgba(255, 255, 255, .045), rgba(255, 255, 255, .015)); border: 1px solid rgba(255, 255, 255, .08); border-radius: 1.1rem; }
    .wxt-card:hover { border-color: rgba(185, 255, 58, .28); }
    .wxt-eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .28em; text-transform: uppercase; color: rgba(34, 211, 238, .85); }
    .wxt-dot { width: .5rem; height: .5rem; border-radius: 9999px; flex: none; }
    .wxt-tick { flex: none; width: 1.35rem; height: 1.35rem; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(140deg, var(--lime), var(--mint)); color: #08130a; font-size: .75rem; font-weight: 900; margin-top: .15rem; }
    .wxt-btn-main { background: linear-gradient(105deg, var(--lime), var(--mint)); color: #06110a; box-shadow: 0 10px 40px -10px rgba(185, 255, 58, .55); }
    .wxt-btn-main:hover { filter: brightness(1.06); transform: translateY(-1px); }
    .wxt-btn-pro { background: linear-gradient(105deg, var(--amber), var(--orange)); color: #1a1004; box-shadow: 0 10px 40px -12px rgba(255, 160, 40, .55); }
    .wxt-btn-pro:hover { filter: brightness(1.05); transform: translateY(-1px); }
    .wxt-btn-ghost { background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .14); color: #fff; }
    .wxt-btn-ghost:hover { background: rgba(255, 255, 255, .09); }
    .wxt-legend b { display: inline-block; width: .7rem; height: .7rem; border-radius: .2rem; margin-right: .35rem; vertical-align: -.05rem; }
    /* video */
    .wxt-video { aspect-ratio: 16 / 9; background: #000; }
    .wxt-video video { width: 100%; height: 100%; object-fit: contain; background: #000; }
    .wxt-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 0; padding: 0;
        background: linear-gradient(180deg, rgba(4, 6, 13, 0) 45%, rgba(4, 6, 13, .78) 100%); }
    .wxt-play[hidden] { display: none; }
    .wxt-play span.ring { width: 5.5rem; height: 5.5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center;
        background: linear-gradient(140deg, var(--lime), var(--mint)); box-shadow: 0 0 0 10px rgba(185, 255, 58, .16), 0 0 60px rgba(185, 255, 58, .55); transition: transform .25s ease; }
    .wxt-play:hover span.ring { transform: scale(1.07); }
    .wxt-chapter[aria-current="true"] { background: rgba(185, 255, 58, .1); border-color: rgba(185, 255, 58, .45); }
    .wxt-chapter[aria-current="true"] .t { color: var(--lime); }
    /* tool explorer */
    .wxt-tab { text-align: left; border: 1px solid rgba(255, 255, 255, .08); background: rgba(7, 10, 20, .82); backdrop-filter: blur(10px); border-radius: .9rem; transition: background .2s, border-color .2s; }
    .wxt-tab:hover { background: rgba(14, 19, 34, .9); }
    .wxt-tab[aria-selected="true"] { background: linear-gradient(105deg, rgba(185, 255, 58, .12), rgba(34, 211, 238, .06)); border-color: rgba(185, 255, 58, .45); }
    .wxt-tab[aria-selected="true"] .n { color: #fff; }
    .wxt-panel[hidden] { display: none; }
    .wxt-panel > .wxt-card { background: linear-gradient(180deg, rgba(10, 14, 26, .9), rgba(7, 10, 20, .86)); backdrop-filter: blur(12px); }
    .wxt-table th, .wxt-table td { border-bottom: 1px solid rgba(255, 255, 255, .06); }
    .wxt h1, .wxt h2, .wxt h3 { text-wrap: balance; }
    .wxt-faq summary { list-style: none; cursor: pointer; }
    .wxt-faq summary::-webkit-details-marker { display: none; }
    .wxt-faq[open] summary .pm { transform: rotate(45deg); }
    .wxt-faq summary .pm { transition: transform .2s ease; }
    @keyframes wxt-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-9px); } }
    .wxt-float { animation: wxt-float 7s ease-in-out infinite; }
    .wxt-float-2 { animation: wxt-float 8s ease-in-out -3s infinite; }
    @media (prefers-reduced-motion: reduce) {
        .wxt-float, .wxt-float-2 { animation: none; }
        .wxt-tilt, .wxt-tilt:hover { transition: none; }
    }
    @media (max-width: 1023px) { .wxt-tilt, .wxt-tilt:hover { transform: none; } }
</style>
@endpush

@section('content')
@php
    // Pro ราคาเดียวกับที่ตะกร้าคิด — config/licenses.php 'plans' (ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ)
    $proYearly = \App\Support\LicensePlans::price('winx-tools', 'yearly');
    $proPrice = number_format($proYearly);

    // ปุ่มซื้อต้องมีเสมอ — แอปส่งลูกค้ามาหน้านี้เพื่อซื้อ และ 1 คีย์ใช้ได้ 1 เครื่อง
    // คนที่เคยซื้อแล้วก็ต้องซื้อเพิ่มให้เครื่องอื่นได้ (เดิมบัญชีที่เคยซื้อเห็นแต่ปุ่มดาวน์โหลด)
    $ownsWinXTools = auth()->check() && $hasPurchased;
    $buyLabel = $ownsWinXTools ? 'ซื้อ License เพิ่ม — ฿' . $proPrice . '/ปี' : 'ซื้อ Pro — ฿' . $proPrice . '/ปี';

    $shot = fn (string $file) => asset('images/products/winxtools/v11/' . $file);

    // คลิปแนะนำอยู่ใน storage ของเซิร์ฟเวอร์ (ไฟล์ใหญ่เกินจะเก็บใน git) — public_html/storage → storage/app/public
    $introVideo = asset('storage/videos/winxtools/winxtools-intro-1080p.mp4');

    // เวลาเริ่มของแต่ละช่วงในคลิป (วินาที) — ตรงกับ plan.json ของคลิป
    $chapters = [
        [0, 'Nova พาทัวร์', 'Meet Nova'],
        [22, 'แดชบอร์ด + 1-Click Full Optimize', 'Dashboard & 1-Click'],
        [52, 'Defrag 3D + Smart Defrag', 'Defrag 3D & Smart Defrag'],
        [97, 'ฮาร์ดดิสก์ USB', 'USB hard disks'],
        [110, 'Disk Speed Test', 'Disk Speed Test'],
        [136, 'สแกนแบดบล็อก', 'Bad block scan'],
        [170, 'Disk Space + Cleaner (CapCut)', 'Disk Space & Cleaner'],
        [200, 'Smart CPU', 'Smart CPU'],
        [243, 'RAM Optimizer', 'RAM Optimizer'],
        [262, 'เครื่องมือเครือข่าย', 'Network tools'],
        [283, 'Windows Optimizer · Tricks · Uninstaller', 'Windows tools'],
        [302, 'ราคา ฟรี + Pro', 'Free & Pro'],
        [321, 'อัปเดตเองอย่างปลอดภัย', 'Signed updates'],
    ];

    $diskTools = [
        [
            'img' => 'defrag-3d.webp', 'pro' => false, 'name' => 'Defrag 3D', 'eyebrow' => 'Defragment & Optimize',
            'th' => 'ใช้ตัวจัดเรียงของ Windows เอง แต่โชว์ดิสก์ของคุณเป็นจานหมุน 3D ของจริง ทุกบล็อกสีคือข้อมูลจริงบนดิสก์ และระหว่างทำงาน หัวอ่านจะวิ่งตามข้อมูลที่กำลังย้ายแบบสด ๆ',
            'en' => 'Windows\' own defragmenter, shown live on a 3D platter of your real drive. Every coloured block is real data, and the head follows it as it moves.',
            'points' => [
                'ฮาร์ดดิสก์ได้จัดเรียง ส่วน SSD ได้ TRIM แทน เพราะ SSD ไม่ควรถูกจัดเรียง',
                'วัดความเร็วอ่านไฟล์ที่กระจายมากที่สุด ก่อนและหลังจัดเรียง',
                'เลือกจัดพื้นที่ว่างให้ต่อกันได้ ไฟล์ใหม่จะกระจายน้อยลง',
            ],
        ],
        [
            'img' => 'defrag-smart.webp', 'pro' => false, 'name' => 'Smart Defrag', 'eyebrow' => 'Learns what you use',
            'th' => 'ฉลาดกว่าการจัดเรียงทั่วไป: ดูจาก Windows ว่าคุณเปิดโปรแกรมไหนบ่อย แล้วย้ายไฟล์ของโปรแกรมเหล่านั้นไปไว้ขอบนอกของจาน ซึ่งเป็นช่วงที่อ่านเร็วที่สุดของดิสก์',
            'en' => 'It learns from Windows which programs you open most and moves their files to the outer edge of the platter, the fastest part of the disk.',
            'points' => [
                'ตั้งให้ทำเองเงียบ ๆ ตอนเครื่องว่างได้',
                'ใช้ข้อมูลที่ Windows เก็บไว้อยู่แล้ว ไม่ต้องตั้งค่าเอง',
            ],
        ],
        [
            'img' => 'defrag-usb.webp', 'pro' => false, 'name' => 'ฮาร์ดดิสก์ USB', 'eyebrow' => 'External drives, safely',
            'th' => 'ฮาร์ดดิสก์ USB ภายนอกก็จัดเรียงได้ ถ้า Windows กำลังจะถอดไดรฟ์ หรือโน้ตบุ๊กเปลี่ยนไปใช้แบตเตอรี่ WinXTools จะหยุดงานและปล่อยดิสก์อย่างปลอดภัย',
            'en' => 'External USB hard disks work too. If Windows is about to remove the drive, or the laptop switches to battery, WinXTools stops and releases the disk safely.',
            'points' => [
                'ทั้งหน้า Defrag 3D รวม Smart Defrag ใช้ได้ฟรี',
            ],
        ],
        [
            'img' => 'disk-speed.webp', 'pro' => true, 'name' => 'Disk Speed Test', 'eyebrow' => 'Is it still fast?',
            'th' => 'ดิสก์ยังเร็วเหมือนวันแรกไหม? วัดอ่าน/เขียนแบบต่อเนื่องและแบบสุ่มเหมือน CrystalDiskMark ทดสอบหลายลูกพร้อมกัน ได้คะแนน แล้วเทียบกับสเปกทางการของรุ่นนั้น หรือค่าปกติของดิสก์ประเภทเดียวกัน',
            'en' => 'Sequential and random reads and writes like CrystalDiskMark, several drives at once, a score, and a comparison with your model\'s official rating or what is normal for that kind of drive.',
            'points' => [
                'ช้ากว่าที่ควร? บอกสาเหตุให้ เช่น เสียบพอร์ต USB 2.0 หรือช่อง PCIe รุ่นเก่า',
                'กราฟความเร็วตลอดทั้งดิสก์ (whole-disk speed curve)',
                'แชร์ผลแบบไม่ระบุตัวตนได้ถ้าคุณเลือก เพื่อให้ทุกคนรู้ว่าดิสก์รุ่นไหนเร็วเท่าไรจริง',
            ],
        ],
        [
            'img' => 'bad-blocks.webp', 'pro' => true, 'name' => 'สแกนแบดบล็อก', 'eyebrow' => 'Bad block scan',
            'th' => 'กลัวดิสก์ใกล้พัง? อ่านดิสก์ตรงจากฮาร์ดแวร์ จับเวลาทุกช่วง แล้วระบายผลลงจาน 3D สด ๆ จุดที่ช้าจะถูกอ่านซ้ำอีกรอบ จะได้ไม่เข้าใจผิดเพราะโปรแกรมอื่นแย่งใช้ดิสก์ชั่วขณะ',
            'en' => 'Reads the drive straight from the hardware, times every area and paints it live on the 3D platter. Slow spots get a second read, so a moment of interference is not mistaken for a weak surface.',
            'points' => [
                'เจอ sector เสีย บอกได้ว่าเป็นไฟล์ไหน และทำเครื่องหมายไม่ให้ Windows ใช้อีก',
                'การสแกนอ่านอย่างเดียว ไม่เขียนอะไรลงดิสก์',
            ],
            'legend' => true,
        ],
        [
            'img' => 'disk-space.webp', 'pro' => true, 'name' => 'Disk Space', 'eyebrow' => 'What filled it up',
            'th' => 'ดิสก์เต็มแต่ไม่รู้ว่าเพราะอะไร? หาโฟลเดอร์และไฟล์ที่ใหญ่ที่สุด และชี้ว่าอะไรคืนพื้นที่ได้อย่างปลอดภัย เช่น ไฟล์ติดตั้งเก่าใน Downloads จุดคืนค่าเก่า และดิสก์ของ emulator',
            'en' => 'Finds the biggest folders and files, and points out what can safely be given back, like old installers and older restore points.',
            'points' => [
                'ไม่มีอะไรถูกลบจนกว่าคุณจะเลือกและกดยืนยัน',
            ],
        ],
    ];

    $perfTools = [
        [
            'key' => 'cpu', 'img' => 'smart-cpu.webp', 'pro' => true, 'name' => 'Smart CPU', 'short' => 'ใครแอบทำงานเบื้องหลัง',
            'th' => 'เช็กทุกโปรแกรมที่ทำงานเบื้องหลัง ว่ากิน CPU และแรมเท่าไร และเปิดขึ้นเองจากที่ไหน ทั้ง Registry, Startup folder, Task Scheduler หรือ Service บอกชัดว่าตัวไหนเป็นของ Windows ต้องเก็บไว้ ตัวไหนแค่ตัวอัปเดต และตัวไหนน่าสงสัย',
            'en' => 'Every background program: what it costs, where it starts from, and whether it belongs to Windows, is just an updater, or looks suspicious.',
            'points' => [
                'ปิดถาวรได้ในคลิกเดียว และย้อนกลับได้ทุกเมื่อ',
                'โปรแกรมเปิดตัวเองกลับมาหลังอัปเดต? Smart CPU ปิดให้อีกครั้ง',
                'ให้โปรแกรมเบื้องหลังที่กิน CPU เข้า Efficiency mode เองโดยไม่ต้องปิดทิ้ง',
                'หยุด Chrome ที่ยังทำงานต่อหลังปิดหน้าต่างได้ด้วยสวิตช์เดียว',
            ],
        ],
        [
            'key' => 'ram', 'img' => 'ram.webp', 'pro' => false, 'name' => 'RAM Optimizer', 'short' => 'คืนแรมแบบไม่กวนงาน',
            'th' => 'ในโหมดอัตโนมัติจะวัดก่อน และทำเฉพาะตอนที่ช่วยได้จริง ไม่บีบโปรแกรมที่คุณเพิ่งใช้อยู่ และถ้าแรมใกล้หมดจริง ๆ จะบอกทันทีว่าโปรแกรมไหนถือแรมไว้ แทนที่จะล้างวนไปเรื่อย ๆ จนเครื่องช้าลง',
            'en' => 'In automatic mode it measures first and only acts when it helps, never squeezes the programs you have just used, and tells you which programs hold the memory when it really runs out.',
            'points' => [
                'เห็นว่าแรมหายไปไหน ทีละโปรแกรม',
            ],
        ],
        [
            'key' => 'clean', 'img' => 'cleaner.webp', 'pro' => false, 'name' => 'System Cleaner', 'short' => 'ล้างขยะ รวมแคช CapCut',
            'th' => 'สแกนแล้วบอกตามจริงว่าจะได้พื้นที่คืนเท่าไร ไฟล์ที่โปรแกรมอื่นเปิดอยู่จะถูกข้ามและไม่นับรวม ตอนนี้ใช้ตัวล้างของ Windows เองร่วมด้วยจึงคืนพื้นที่ได้มากขึ้น และหาเวอร์ชันเก่ากับแคชซ่อนของ CapCut ได้ทุกที่ บางเครื่องคืนได้เกิน 10 GB',
            'en' => 'Shows exactly the space you will get back, now with Windows\' own cleanup tools too, and finds CapCut\'s old versions and hidden caches wherever they are.',
            'points' => [
                'ไม่แตะโปรเจกต์ CapCut เอกสาร รูป รหัสผ่าน คุกกี้ หรือโปรแกรมที่ติดตั้งไว้',
            ],
        ],
        [
            'key' => 'win', 'img' => 'windows-optimizer.webp', 'pro' => false, 'name' => 'Windows Optimizer', 'short' => 'Gamer Mode + ปิดของไม่จำเป็น',
            'th' => 'ปิดบริการและฟีเจอร์ AI ที่ไม่จำเป็นเพื่อให้เครื่องเบาขึ้น Gamer Mode เตรียมเครื่องให้พร้อมเล่นเกม และสร้างจุดคืนค่า (Restore Point) ก่อนทุกครั้ง จึงย้อนกลับได้ทั้งหมด',
            'en' => 'Turns off services and AI features you do not need. Gamer Mode gets the PC ready to play and creates a restore point first, so everything can be undone.',
            'points' => [],
        ],
        [
            'key' => 'tricks', 'img' => 'windows-tricks.webp', 'pro' => true, 'name' => 'Windows Tricks', 'short' => 'ทริกลับตามเวอร์ชันเครื่อง',
            'th' => 'รวมทริกและคำสั่งลับของ Windows แยกเป็นหมวด โชว์เวอร์ชันและฮาร์ดแวร์จริงของเครื่อง และบอกว่าทริกไหนใช้กับเครื่องคุณได้ ทุกทริกเช็กสถานะจริงก่อน และย้อนกลับได้ด้วยปุ่ม Restore default',
            'en' => 'Windows secret tricks by category, matched to your exact version and hardware. Every trick checks the real state of the PC first and can be undone with Restore default.',
            'points' => ['12 หมวด: Gaming · Explorer & Taskbar · Look & Input · Privacy & Ads · Performance · Power & Startup · Network · Windows Update · Security · Storage · Repair & Reports · Windows Tools'],
        ],
        [
            'key' => 'uninstall', 'img' => null, 'pro' => false, 'name' => 'Deep Uninstaller', 'short' => 'ถอนแล้วเก็บกวาดต่อ',
            'th' => 'ถอนโปรแกรมแล้วตามเก็บสิ่งที่โปรแกรมทิ้งไว้ ทั้งไฟล์และรายการ registry ที่ค้างอยู่',
            'en' => 'Uninstalls programs and cleans up the files and registry keys they leave behind.',
            'points' => ['Deep Clean หาไฟล์และ registry ที่ค้างหลังถอน', 'Force Remove สำหรับโปรแกรมที่ถอนตามปกติไม่ออก'],
        ],
    ];

    $netTools = [
        [
            'key' => 'monitor', 'img' => 'network-monitor.webp', 'pro' => true, 'name' => 'Network Monitor', 'short' => 'เน็ตแต่ละแอปแบบสด',
            'th' => 'เห็นทุกแอปที่ใช้เน็ตแบบเรียลไทม์ ทั้ง PID ความเร็วขึ้น/ลง และจำนวนการเชื่อมต่อ จำกัดความเร็วหรือบล็อกรายแอปได้ทันที',
            'en' => 'Every app\'s internet use in real time, with a speed limit or a block for any single app.',
            'points' => ['พรีเซ็ต Unlimited · 10 MB/s · 5 MB/s · 1 MB/s · 512 KB/s · Blocked'],
        ],
        [
            'key' => 'bandwidth', 'img' => 'bandwidth.webp', 'pro' => false, 'name' => 'Bandwidth Control', 'short' => 'จำกัดความเร็วทั้งเครื่อง',
            'th' => 'จำกัดความเร็วเน็ตทั้งเครื่อง วัดและบังคับใช้ที่ระดับแพ็กเก็ต ไม่ใช่แค่บล็อกด้วยไฟร์วอลล์ และรวมกฎจำกัด/บล็อกรายแอปไว้ในที่เดียว',
            'en' => 'Limit the whole PC, measured and enforced at the packet level, not just blocked.',
            'points' => ['การจำกัดความเร็วรายแอปตั้งได้จาก Network Monitor (Pro)'],
        ],
        [
            'key' => 'connections', 'img' => 'connections.webp', 'pro' => false, 'name' => 'Connections', 'short' => 'ทุกการเชื่อมต่อของเครื่อง',
            'th' => 'ดูการเชื่อมต่อ TCP/UDP ทั้งหมดของเครื่อง แยกขาเข้า/ขาออก และ IP ที่ถูกบล็อก รีเฟรชให้อัตโนมัติ',
            'en' => 'Every TCP and UDP connection on the PC, incoming and outgoing, refreshed automatically.',
            'points' => [],
        ],
        [
            'key' => 'packets', 'img' => null, 'pro' => true, 'name' => 'Packet Monitor', 'short' => 'จับแพ็กเก็ตระดับเคอร์เนล',
            'th' => 'จับแพ็กเก็ตสดระดับเคอร์เนลด้วยไดรเวอร์ WinDivert เห็น IP จริงทั้ง IPv4/IPv6 พอร์ต โปรโตคอล ขนาด และทิศทาง กรองตามโปรเซสได้และ Export ผลได้',
            'en' => 'Live kernel-level packet capture with real IPv4/IPv6 addresses, ports, protocol, size and direction, filtered by process.',
            'points' => [],
        ],
        [
            'key' => 'tools', 'img' => 'network-tools.webp', 'pro' => true, 'name' => 'Network Tools', 'short' => '16 เครื่องมือในหน้าเดียว',
            'th' => 'ชุดเครื่องมือวิเคราะห์และแก้ปัญหาเน็ต 16 ตัวรวมไว้ในหน้าเดียว',
            'en' => '16 diagnostic and analysis tools for network troubleshooting, on one page.',
            'points' => [
                'วินิจฉัย: Ping · Traceroute · DNS Lookup · Port Scanner · Whois',
                'ความปลอดภัย: SSL Checker · HTTP Headers',
                'ข้อมูลเครือข่าย: My IP · Subnet Calculator · IP Converter · ARP Table · Route Table · Network Stats',
                'เครื่องมืออื่น: Packet Sender · Wake-on-LAN · Speed Test',
            ],
        ],
        [
            'key' => 'proxy', 'img' => null, 'pro' => true, 'name' => 'Free Proxy', 'short' => 'พร็อกซีฟรีต่างประเทศ',
            'th' => 'ท่องเว็บผ่านพร็อกซีสาธารณะฟรีในประเทศอื่น เลือกตามประเทศแล้วเชื่อมต่อในคลิกเดียว',
            'en' => 'Browse through a free public proxy in another country.',
            'points' => [
                'ทดสอบพร็อกซีผ่าน HTTPS ก่อนเชื่อมต่อ แล้วบอก IP ขาออกจริงกับความหน่วง (และเตือนถ้าออกจริงคนละประเทศ)',
                'ตัดการเชื่อมต่อแล้วคืนค่าพร็อกซีเดิมของ Windows ให้ แม้โปรแกรมถูกปิดกลางคัน',
                'ไม่ใช่ VPN และไม่เข้ารหัส อย่าใช้กับรหัสผ่านหรือข้อมูลสำคัญ',
            ],
        ],
        [
            'key' => 'rules', 'img' => null, 'pro' => true, 'name' => 'Automation Rules', 'short' => 'ตั้งกฎให้ทำงานเอง',
            'th' => 'ตั้งกฎให้โปรแกรมทำงานเองเมื่อถึงเงื่อนไขที่กำหนด',
            'en' => 'Run actions automatically when conditions are met.',
            'points' => [],
        ],
    ];

    // ตารางเทียบ: 18 หน้าในโปรแกรม (ตรงกับ TrialService.ProOnlyPages ของแอป)
    $compare = [
        ['ดิสก์ · Disk', [
            ['Defrag 3D + Smart Defrag + USB', false],
            ['Disk Speed Test', true],
            ['สแกนแบดบล็อก · Bad blocks', true],
            ['Disk Space', true],
        ]],
        ['ล้างและเร่งเครื่อง · Clean & speed', [
            ['1-Click Full Optimize', false],
            ['System Cleaner (รวม CapCut)', false],
            ['RAM Optimizer', false],
            ['Smart CPU', true],
            ['Windows Optimizer + Gamer Mode', false],
            ['Windows Tricks', true],
            ['Deep Uninstaller', false],
        ]],
        ['เครือข่าย · Network', [
            ['Dashboard', false],
            ['Bandwidth Control (ทั้งเครื่อง)', false],
            ['Connections', false],
            ['Network Monitor (รายแอป)', true],
            ['Packet Monitor', true],
            ['Network Tools', true],
            ['Free Proxy', true],
            ['Automation Rules', true],
        ]],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'WinXTools',
        'softwareVersion' => '1.1',
        'operatingSystem' => 'Windows 10, Windows 11 (64-bit)',
        'applicationCategory' => 'UtilitiesApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'ดูแลวินโดวส์ครบในโปรแกรมเดียว: Defrag 3D, 1-Click Full Optimize, Disk Speed Test, สแกนแบดบล็อก, Smart CPU, ล้างขยะ และคุมเน็ตทุกแอป',
        'image' => asset('images/products/winxtools/v11/og.jpg'),
        'url' => route('products.show', 'winx-tools'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => [
            ['@type' => 'Offer', 'name' => 'Free', 'price' => '0', 'priceCurrency' => 'THB'],
            ['@type' => 'Offer', 'name' => 'Pro (1 year)', 'price' => (string) $proYearly, 'priceCurrency' => 'THB'],
        ],
    ];
@endphp
<div class="wxt relative overflow-hidden">

    {{-- ============================ HERO ============================ --}}
    <section class="relative isolate">
        <div class="wxt-grid absolute inset-0 -z-10"></div>
        <div class="wxt-glow -z-10 w-[36rem] h-[36rem] -top-40 -left-40 bg-cyan-500/20"></div>
        <div class="wxt-glow -z-10 w-[30rem] h-[30rem] top-40 right-0 bg-lime-400/10"></div>
        <img src="{{ $shot('art-hero.webp') }}" alt="" aria-hidden="true" width="1536" height="1024" fetchpriority="high"
             class="wxt-art absolute -z-10 top-[-4%] right-[-30%] w-[150%] opacity-50 sm:right-[-12%] sm:w-[110%] lg:right-[-8%] lg:w-[78%] lg:opacity-95">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-20 lg:pt-12 lg:pb-28">
            <nav class="mb-10">
                <a href="{{ route('products.index') }}" class="inline-flex items-center text-sm text-cyan-300/90 hover:text-cyan-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <x-bi th="กลับไปรายการผลิตภัณฑ์" en="All products" />
                </a>
            </nav>

            <div class="grid lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-6">
                    <div class="flex flex-wrap items-center gap-2 mb-6">
                        <span class="wxt-chip"><i></i>WinXTools 1.1</span>
                        <span class="wxt-chip">Windows 10 / 11</span>
                    </div>

                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight text-white leading-[1.02] mb-5">
                        Win<span class="wxt-text-glow">X</span>Tools
                    </h1>
                    <p class="text-2xl sm:text-3xl font-bold text-white mb-2">ดูแลวินโดวส์ครบในโปรแกรมเดียว</p>
                    <p class="text-base sm:text-lg text-gray-400 mb-6">Everything your Windows PC needs, in one app.</p>

                    <p class="text-lg text-gray-300 leading-relaxed mb-2">
                        จัดเรียงดิสก์แบบเห็นจานหมุน 3D จริง ทดสอบความเร็วดิสก์ สแกนแบดบล็อก ล้างขยะ คืนแรม ให้โปรแกรมเบื้องหลังสงบลง และคุมเน็ตทุกแอป — ทุกอย่างที่เห็นคือโปรแกรมจริงที่ทำงานจริง
                    </p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-8">
                        Defrag you can watch in 3D, disk speed tests, a bad-block scan, junk cleaning, smarter RAM, calmer background apps and a handle on every app's internet.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('winx-tools.download') }}"
                           class="wxt-btn-main inline-flex items-center gap-2 px-7 py-4 rounded-xl font-extrabold text-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <x-bi th="ดาวน์โหลดฟรี" en="Free download" />
                        </a>
                        {{-- Pro = license รายปี · ราคามาจาก config/licenses.php 'plans' ไม่ใช่จากฟอร์ม --}}
                        <form action="{{ route('cart.add', $product) }}" method="POST">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="license_type" value="yearly">
                            <input type="hidden" name="buy_now" value="1">
                            <button type="submit" class="wxt-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition cursor-pointer">
                                {{ $buyLabel }}
                            </button>
                        </form>
                        <a href="#intro-video" class="wxt-btn-ghost inline-flex items-center gap-2 px-5 py-4 rounded-xl font-semibold transition">
                            <svg class="w-5 h-5 text-lime-300" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5.14v13.72a1 1 0 001.5.86l11-6.86a1 1 0 000-1.72l-11-6.86A1 1 0 008 5.14z"/></svg>
                            <x-bi th="ดูคลิป 5 นาที" en="Watch" />
                        </a>
                    </div>

                    @if($ownsWinXTools)
                        <p class="mt-4 text-sm text-lime-200/90">
                            คุณมี License แล้ว · 1 คีย์ใช้ได้ 1 เครื่อง — ซื้อเพิ่มได้สำหรับเครื่องอื่น ·
                            <a href="{{ route('customer.licenses') }}" class="font-semibold underline decoration-lime-300/50 hover:text-white">ดูคีย์ License ของฉัน</a>
                        </p>
                    @endif

                    <ul class="mt-8 grid sm:grid-cols-3 gap-3 text-sm">
                        <li class="flex items-center gap-2 text-gray-300"><span class="wxt-badge wxt-free">ฟรี</span> 8 เครื่องมือ ใช้ได้ตลอด</li>
                        <li class="flex items-center gap-2 text-gray-300"><span class="wxt-badge wxt-pro">PRO</span> ทดลองฟรี 48 ชม.</li>
                        <li class="flex items-center gap-2 text-gray-300"><span class="wxt-dot bg-cyan-400 shadow-[0_0_10px_#22d3ee]"></span> ไม่ต้องลง .NET</li>
                    </ul>
                </div>

                <div class="lg:col-span-6 relative">
                    <div class="wxt-tilt">
                        <div class="wxt-window">
                            <div><img src="{{ $shot('defrag-3d.webp') }}" alt="หน้า Defrag 3D ของ WinXTools ขณะจัดเรียงฮาร์ดดิสก์ เห็นจานหมุนและบล็อกข้อมูลจริง" width="1600" height="900"></div>
                        </div>
                    </div>
                    <div class="wxt-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 wxt-card backdrop-blur-md px-4 py-3 shadow-2xl">
                        <p class="text-[11px] tracking-widest uppercase text-gray-400">ตัวอย่างจริง · Real result</p>
                        <p class="text-white font-extrabold text-lg leading-tight">Cleaner เจอพื้นที่คืนได้ <span class="text-lime-300">31.64 GB</span></p>
                    </div>
                    <div class="wxt-float-2 absolute -right-2 sm:-right-6 -top-5 wxt-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                        <p class="text-[11px] tracking-widest uppercase text-gray-400">SSD</p>
                        <p class="text-white font-bold leading-tight">ได้ TRIM อัตโนมัติ<br><span class="text-gray-400 text-sm font-normal">ไม่ถูกจัดเรียงให้สึกหรอ</span></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ VIDEO ============================ --}}
    <section id="intro-video" class="relative py-20 scroll-mt-20">
        <div class="wxt-glow w-[40rem] h-[24rem] left-1/2 -translate-x-1/2 top-24 bg-violet-600/15"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-10">
                <p class="wxt-eyebrow mb-3">Intro video · 5:39</p>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">Nova พาทัวร์ของจริงทุกหน้า</h2>
                <p class="text-gray-300">ทุกฉากในคลิปคือ WinXTools ที่ทำงานจริงบนเครื่องจริง เสียงบรรยายภาษาอังกฤษ พร้อมซับไทยในคลิป</p>
                <p class="text-sm text-gray-500 mt-1">Every scene is the real app running on a real PC. English narration with Thai subtitles.</p>
            </div>

            <div class="grid lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-8">
                    <div class="wxt-window">
                        <div class="wxt-video relative">
                            <video id="wxt-video" preload="none" playsinline poster="{{ $shot('intro-poster.webp') }}"
                                   aria-label="คลิปแนะนำ WinXTools โดย Nova · WinXTools intro video">
                                <source src="{{ $introVideo }}" type="video/mp4">
                            </video>
                            <button type="button" id="wxt-play" class="wxt-play" aria-label="เล่นคลิปแนะนำ · Play the intro video">
                                <span class="ring"><svg class="w-9 h-9 ml-1 text-[#06110a]" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5.14v13.72a1 1 0 001.5.86l11-6.86a1 1 0 000-1.72l-11-6.86A1 1 0 008 5.14z"/></svg></span>
                                <span class="absolute left-5 bottom-4 text-left">
                                    <span class="block text-white font-extrabold text-lg sm:text-xl">WinXTools 1.1 — ของจริงทุกหน้า</span>
                                    <span class="block text-gray-300 text-sm">5:39 · English · ซับไทย</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-4">
                    <div class="wxt-card p-3">
                        <p class="px-2 pt-1 pb-3 text-sm font-bold text-white"><x-bi th="เลือกดูทีละหัวข้อ" en="Chapters" /></p>
                        <ol class="space-y-1.5 max-h-[27rem] overflow-y-auto pr-1">
                            @foreach($chapters as [$sec, $th, $en])
                                <li>
                                    <button type="button" data-seek="{{ $sec }}"
                                            class="wxt-chapter w-full flex items-center gap-3 px-3 py-2 rounded-lg border border-transparent hover:bg-white/5 text-left transition">
                                        <span class="t font-mono text-xs text-cyan-300 w-10 flex-none">{{ intdiv($sec, 60) }}:{{ str_pad($sec % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="min-w-0">
                                            <span class="block text-sm text-gray-100 truncate">{{ $th }}</span>
                                            <span class="block text-[11px] text-gray-500 truncate">{{ $en }}</span>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================== 1-CLICK ========================== --}}
    <section class="relative py-20 isolate">
        <img src="{{ $shot('art-oneclick.webp') }}" alt="" aria-hidden="true" width="1536" height="1024" loading="lazy"
             class="wxt-art absolute -z-10 left-[-28%] top-1/2 -translate-y-1/2 w-[130%] opacity-40 lg:left-[-14%] lg:w-[70%] lg:opacity-90">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-5 lg:col-start-7">
                    <p class="wxt-eyebrow mb-3">One press · everything safe</p>
                    <h2 class="text-3xl md:text-4xl xl:text-5xl font-black text-white mb-4 flex flex-wrap items-center gap-3">1-Click Full Optimize <span class="wxt-badge wxt-free text-sm">ฟรี</span></h2>
                    <p class="text-lg text-gray-300 mb-1">ปุ่มที่ทุกคนรอ กดครั้งเดียว ทำทุกอย่างที่ปลอดภัยให้ในรอบเดียว แล้วสรุปว่าทำอะไรไปบ้าง และอะไรควรทำต่อ</p>
                    <p class="text-sm text-gray-500 mb-7">One press does everything that is safe, then shows exactly what it did and what is worth doing next.</p>
                    <ul class="space-y-3">
                        <li class="flex gap-3"><span class="wxt-tick">✓</span><span class="text-gray-200">ล้างไฟล์ขยะในหมวดที่ปลอดภัย <span class="text-gray-500 text-sm">· junk files</span></span></li>
                        <li class="flex gap-3"><span class="wxt-tick">✓</span><span class="text-gray-200">คืนแรม โดยไม่ทำให้โปรแกรมที่เปิดอยู่ช้าลง <span class="text-gray-500 text-sm">· RAM</span></span></li>
                        <li class="flex gap-3"><span class="wxt-tick">✓</span><span class="text-gray-200">ใส่ Efficiency mode ให้โปรแกรมเบื้องหลังที่กิน CPU <span class="wxt-badge wxt-pro ml-1 align-middle">PRO</span></span></li>
                        <li class="flex gap-3"><span class="wxt-tick">✓</span><span class="text-gray-200">สั่ง TRIM ให้ SSD <span class="text-gray-500 text-sm">· SSD TRIM</span></span></li>
                        <li class="flex gap-3"><span class="wxt-tick">✓</span><span class="text-gray-200">ล้างแคช DNS <span class="text-gray-500 text-sm">· DNS cache</span></span></li>
                    </ul>
                    <p class="mt-6 text-sm text-gray-400 border-l-2 border-lime-400/60 pl-4">ไฟล์งานของคุณและโปรแกรมที่เปิดอยู่ไม่ถูกแตะ · ทุกขั้นใช้ฟรี ยกเว้น Efficiency mode ที่เป็นของ Pro<br><span class="text-gray-500">Your files and open programs are never touched.</span></p>
                </div>
            </div>
            <div class="mt-14 max-w-4xl mx-auto">
                <div class="wxt-window">
                    <div><img src="{{ $shot('dashboard.webp') }}" alt="แดชบอร์ด WinXTools พร้อมการ์ด 1-Click Full Optimize ความเร็วเน็ตสด และแอปที่ใช้เน็ตมากที่สุด" width="1600" height="900" loading="lazy"></div>
                </div>
                <p class="text-center text-sm text-gray-500 mt-5">แดชบอร์ด · ความเร็วอัป/ดาวน์โหลดสด กราฟทราฟฟิก และแอปที่ใช้เน็ตมากที่สุด <span class="wxt-badge wxt-free ml-1">ฟรี</span></p>
            </div>
        </div>
    </section>

    {{-- ========================= DISK SUITE ========================= --}}
    <section class="relative py-20">
        <div class="wxt-glow w-[34rem] h-[34rem] -right-40 top-10 bg-cyan-500/15"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <p class="wxt-eyebrow mb-3">New in 1.1 · disk tools</p>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">ชุดเครื่องมือดิสก์ ที่ดูแล้วเพลิน</h2>
                <p class="text-gray-300">ไฮไลต์ของเวอร์ชันนี้: เห็นดิสก์ของคุณเป็นจานหมุน 3D ของจริง ตั้งแต่จัดเรียง ทดสอบความเร็ว ไปจนถึงสแกนหาจุดเสีย</p>
                <p class="text-sm text-gray-500 mt-1">The highlight of this version: your real drive as a spinning 3D platter, from defrag to speed tests to a bad-block scan.</p>
            </div>

            <div class="space-y-20 lg:space-y-28">
                @foreach($diskTools as $i => $tool)
                    <div class="grid lg:grid-cols-12 gap-8 lg:gap-14 items-center">
                        <div class="lg:col-span-7 {{ $i % 2 === 1 ? 'lg:order-2' : '' }}">
                            <div class="wxt-window">
                                <div><img src="{{ $shot($tool['img']) }}" alt="{{ $tool['name'] }} — WinXTools" width="1600" height="900" loading="lazy"></div>
                            </div>
                        </div>
                        <div class="lg:col-span-5 {{ $i % 2 === 1 ? 'lg:order-1' : '' }}">
                            <p class="wxt-eyebrow mb-3">{{ $tool['eyebrow'] }}</p>
                            <h3 class="text-2xl md:text-4xl font-black text-white mb-4 flex flex-wrap items-center gap-3">
                                {{ $tool['name'] }}
                                @if($tool['pro'])<span class="wxt-badge wxt-pro">PRO</span>@else<span class="wxt-badge wxt-free">ฟรี</span>@endif
                            </h3>
                            <p class="text-lg text-gray-300 leading-relaxed">{{ $tool['th'] }}</p>
                            <p class="text-sm text-gray-500 leading-relaxed mt-2">{{ $tool['en'] }}</p>
                            @if(! empty($tool['legend']))
                                <p class="wxt-legend mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-300">
                                    <span><b style="background:#22c55e"></b>ปกติ</span>
                                    <span><b style="background:#eab308"></b>ช้า</span>
                                    <span><b style="background:#f97316"></b>ช้ามาก</span>
                                    <span><b style="background:#ef4444"></b>อ่านไม่ได้</span>
                                </p>
                            @endif
                            @if($tool['points'])
                                <ul class="mt-6 space-y-3">
                                    @foreach($tool['points'] as $point)
                                        <li class="flex gap-3"><span class="wxt-tick">✓</span><span class="text-gray-200">{{ $point }}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== PERFORMANCE EXPLORER ===================== --}}
    @foreach([
        ['id' => 'perf', 'art' => 'art-performance.webp', 'artSide' => 'left', 'eyebrow' => 'Speed & clean-up', 'title' => 'เครื่องช้า?', 'title2' => 'ทำให้เบาลงแบบไม่เสี่ยง', 'sub' => 'ดูว่าอะไรกินเครื่อง แล้วจัดการทีละจุด ทุกอย่างย้อนกลับได้', 'subEn' => 'See what is slowing the PC down and fix it one step at a time, with an undo for everything.', 'tools' => $perfTools],
        ['id' => 'net', 'art' => 'art-network.webp', 'artSide' => 'right', 'eyebrow' => 'Network', 'title' => 'คุมเน็ตทุกแอป', 'title2' => 'ถึงระดับแพ็กเก็ต', 'sub' => 'ดูว่าแอปไหนใช้เน็ต จำกัดความเร็ว จับแพ็กเก็ต และวิเคราะห์ปัญหาเน็ตได้ครบ', 'subEn' => 'See which app uses the internet, limit speeds, capture packets and troubleshoot the connection.', 'tools' => $netTools],
    ] as $group)
        <section class="relative py-20 isolate">
            <img src="{{ $shot($group['art']) }}" alt="" aria-hidden="true" width="1536" height="1024" loading="lazy"
                 class="wxt-art absolute -z-10 top-0 w-[130%] opacity-35 {{ $group['artSide'] === 'left' ? 'left-[-30%] lg:left-[-18%]' : 'right-[-30%] lg:right-[-16%]' }} lg:w-[68%] lg:opacity-80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mb-10 {{ $group['artSide'] === 'left' ? 'lg:ml-auto lg:text-right' : '' }}">
                    <p class="wxt-eyebrow mb-3">{{ $group['eyebrow'] }}</p>
                    <h2 class="text-3xl md:text-5xl font-black text-white mb-4"><span class="whitespace-nowrap">{{ $group['title'] }}</span> <span class="whitespace-nowrap">{{ $group['title2'] }}</span></h2>
                    <p class="text-gray-300">{{ $group['sub'] }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $group['subEn'] }}</p>
                </div>

                <div class="grid lg:grid-cols-12 gap-6" data-explorer>
                    <div class="lg:col-span-4 flex lg:flex-col gap-2 overflow-x-auto pb-2 lg:pb-0 -mx-4 px-4 lg:mx-0 lg:px-0" role="tablist" aria-label="{{ $group['title'] }} {{ $group['title2'] }}">
                        @foreach($group['tools'] as $k => $tool)
                            <button type="button" role="tab" data-tab id="{{ $group['id'] }}-tab-{{ $tool['key'] }}" aria-controls="{{ $group['id'] }}-panel-{{ $tool['key'] }}"
                                    aria-selected="{{ $k === 0 ? 'true' : 'false' }}"
                                    class="wxt-tab flex-none lg:flex-auto w-60 lg:w-full px-4 py-3 flex items-center gap-3">
                                <span class="min-w-0 flex-1">
                                    <span class="n block font-bold text-gray-200">{{ $tool['name'] }}</span>
                                    <span class="block text-xs text-gray-500 truncate">{{ $tool['short'] }}</span>
                                </span>
                                @if($tool['pro'])<span class="wxt-badge wxt-pro">PRO</span>@else<span class="wxt-badge wxt-free">ฟรี</span>@endif
                            </button>
                        @endforeach
                    </div>
                    <div class="lg:col-span-8">
                        @foreach($group['tools'] as $k => $tool)
                            <div class="wxt-panel" role="tabpanel" data-panel id="{{ $group['id'] }}-panel-{{ $tool['key'] }}" aria-labelledby="{{ $group['id'] }}-tab-{{ $tool['key'] }}">
                                <div class="wxt-card p-5 sm:p-7 backdrop-blur-sm">
                                    <h3 class="text-2xl font-black text-white mb-3 flex flex-wrap items-center gap-3">
                                        {{ $tool['name'] }}
                                        @if($tool['pro'])<span class="wxt-badge wxt-pro">PRO</span>@else<span class="wxt-badge wxt-free">ฟรี</span>@endif
                                    </h3>
                                    <p class="text-gray-300 leading-relaxed">{{ $tool['th'] }}</p>
                                    <p class="text-sm text-gray-500 leading-relaxed mt-2">{{ $tool['en'] }}</p>
                                    @if($tool['points'])
                                        <ul class="mt-5 space-y-2.5">
                                            @foreach($tool['points'] as $point)
                                                <li class="flex gap-3"><span class="wxt-tick">✓</span><span class="text-gray-200">{{ $point }}</span></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if($tool['img'])
                                        <div class="wxt-window mt-7">
                                            <div><img src="{{ $shot($tool['img']) }}" alt="{{ $tool['name'] }} — WinXTools" width="1600" height="900" loading="lazy"></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endforeach

    {{-- ============================ SAFETY ============================ --}}
    <section class="relative py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <p class="wxt-eyebrow mb-3">Safe by design</p>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">กล้ากดเพราะย้อนกลับได้</h2>
                <p class="text-gray-300">เครื่องมือดูแลเครื่องที่ดีต้องไม่ทำให้เครื่องพังเสียเอง WinXTools จึงถูกออกแบบให้ปลอดภัยตั้งแต่ต้น</p>
                <p class="text-sm text-gray-500 mt-1">A PC care tool must never be the thing that breaks your PC.</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach([
                    ['M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'ย้อนกลับได้', 'Gamer Mode และ Windows Optimizer สร้างจุดคืนค่าก่อนเสมอ ทุกการเปลี่ยนแปลงใน Smart CPU มีปุ่ม Undo', 'Restore point first, undo for every change'],
                    ['M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'ไม่ลบถ้าคุณไม่ยืนยัน', 'Disk Space และ Cleaner บอกขนาดตามจริง และไม่ลบอะไรจนกว่าคุณจะเลือกและกดยืนยัน', 'Nothing is deleted until you confirm'],
                    ['M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', 'สแกนแบบอ่านอย่างเดียว', 'การสแกนแบดบล็อกและการทดสอบอ่านไม่เขียนอะไรลงดิสก์ของคุณ', 'Scans only read'],
                    ['M13 10V3L4 14h7v7l9-11h-7z', 'ไม่กวนงานที่ทำอยู่', '1-Click และ RAM Optimizer ไม่แตะไฟล์งาน และไม่บีบโปรแกรมที่คุณเพิ่งใช้', 'Your open programs keep their speed'],
                    ['M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'อัปเดตที่ตรวจลายเซ็น', 'อัปเดตมาจาก xman4289.com เท่านั้น ทุกแพ็กเกจมีลายเซ็นดิจิทัลและถูกตรวจก่อนติดตั้งทุกครั้ง', 'Every update is signed and checked'],
                    ['M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z', 'SSD ไม่สึกเปล่า ๆ', 'SSD ไม่ถูกจัดเรียง แต่ได้คำสั่ง TRIM แทน ตามแบบที่ Windows ทำ', 'SSDs get TRIM, never a defrag'],
                ] as [$icon, $title, $body, $en])
                    <div class="wxt-card p-6 transition">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4 bg-lime-400/10 border border-lime-400/25">
                            <svg class="w-6 h-6 text-lime-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-white mb-1.5">{{ $title }}</h3>
                        <p class="text-sm text-gray-300 leading-relaxed">{{ $body }}</p>
                        <p class="text-xs text-gray-500 mt-2">{{ $en }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="wxt-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <p class="wxt-eyebrow mb-3">Pricing</p>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">เริ่มฟรี อยากได้ครบค่อยอัป Pro</h2>
                <p class="text-gray-300">ไฟล์เดียวกันทั้งรุ่นฟรีและ Pro — Pro ปลดล็อกในโปรแกรมด้วย License key</p>
                <p class="text-sm text-gray-500 mt-1">The same download for Free and Pro. Pro unlocks inside the app with a licence key.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 max-w-5xl mx-auto">
                <div class="wxt-card p-8 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">Free</h3>
                        <span class="wxt-badge wxt-free">ฟรี</span>
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="8 เครื่องมือ ใช้ได้ไม่จำกัดเวลา" en="8 tools, no time limit" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                    <ul class="space-y-2.5 text-gray-200 text-sm mb-8 flex-1">
                        @foreach(['Dashboard + 1-Click Full Optimize', 'Defrag 3D + Smart Defrag (รวมฮาร์ดดิสก์ USB)', 'System Cleaner รวมแคช CapCut', 'RAM Optimizer', 'Windows Optimizer + Gamer Mode', 'Deep Uninstaller', 'Bandwidth Control ทั้งเครื่อง', 'Connections'] as $item)
                            <li class="flex gap-3"><span class="wxt-tick">✓</span><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                    {{-- ไฟล์เดียวกับ Pro — Pro ปลดล็อกในแอปด้วย license key --}}
                    <a href="{{ route('winx-tools.download') }}" class="wxt-btn-main block w-full py-3.5 text-center rounded-xl font-extrabold transition">
                        <x-bi th="ดาวน์โหลดฟรี" en="Free download" />
                    </a>
                </div>

                <div class="relative rounded-[1.1rem] p-px bg-gradient-to-br from-amber-300/80 via-orange-500/40 to-lime-300/60 shadow-[0_30px_90px_-30px_rgba(255,160,40,.45)]">
                    <div class="h-full rounded-[1.05rem] bg-[#0b0d14] p-8 flex flex-col">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2"><span class="wxt-badge wxt-pro shadow-lg">ครบทุกฟีเจอร์ · ALL FEATURES</span></div>
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-2xl font-black text-white">Pro</h3>
                            <span class="wxt-badge wxt-pro">PRO</span>
                        </div>
                        <p class="text-amber-100/80 text-sm mb-6"><x-bi th="ทุกอย่างในรุ่นฟรี + อีก 10 เครื่องมือ" en="Everything in Free + 10 more tools" /></p>
                        <div class="mb-6 flex items-baseline gap-2">
                            <span class="text-5xl font-black text-white">฿{{ $proPrice }}</span>
                            <span class="text-gray-300"><x-bi th="/ ปี" en="year" /></span>
                        </div>
                        <ul class="space-y-2.5 text-gray-100 text-sm mb-8 flex-1">
                            @foreach(['Disk Speed Test', 'สแกนแบดบล็อก (Bad block scan)', 'Disk Space', 'Smart CPU + Efficiency mode ใน 1-Click', 'Windows Tricks', 'Network Monitor จำกัด/บล็อกรายแอป', 'Packet Monitor', 'Network Tools 16 ตัว', 'Free Proxy', 'Automation Rules'] as $item)
                                <li class="flex gap-3"><span class="wxt-tick" style="background:linear-gradient(140deg,#ffcf3a,#ff8a1f);color:#1a1004">✓</span><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                        <form action="{{ route('cart.add', $product) }}" method="POST">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="license_type" value="yearly">
                            <input type="hidden" name="buy_now" value="1">
                            <button type="submit" class="wxt-btn-pro block w-full py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                {{ $buyLabel }}
                            </button>
                        </form>
                        @if($ownsWinXTools)
                            <a href="{{ route('customer.licenses') }}" class="block text-center text-lime-300 hover:text-lime-200 text-sm font-semibold mt-3">
                                มี License แล้ว? ดูคีย์ของคุณ →
                            </a>
                        @endif
                        <p class="text-center text-amber-100/70 text-xs mt-4">ทดลอง Pro ฟรี 48 ชั่วโมงก่อนตัดสินใจ · 1 คีย์ใช้ได้ 1 เครื่อง · ไม่มีค่าใช้จ่ายซ่อน</p>
                    </div>
                </div>
            </div>

            {{-- ตารางเทียบทุกหน้า --}}
            <div class="max-w-5xl mx-auto mt-14 wxt-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="wxt-table w-full text-sm">
                        <thead>
                            <tr class="text-left bg-white/[.03]">
                                <th class="px-4 sm:px-5 py-4 font-bold text-white"><x-bi th="เครื่องมือ" en="Tool" /></th>
                                <th class="px-2 sm:px-5 py-4 text-center w-16 sm:w-28"><span class="wxt-badge wxt-free">ฟรี</span></th>
                                <th class="px-2 sm:px-5 py-4 text-center w-16 sm:w-28"><span class="wxt-badge wxt-pro">PRO</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($compare as [$groupName, $rows])
                                <tr><td colspan="3" class="px-4 sm:px-5 pt-5 pb-2 text-xs font-bold tracking-widest uppercase text-cyan-300/80">{{ $groupName }}</td></tr>
                                @foreach($rows as [$name, $proOnly])
                                    <tr>
                                        <td class="px-4 sm:px-5 py-3 text-gray-200">{{ $name }}</td>
                                        <td class="px-2 sm:px-5 py-3 text-center">
                                            @if($proOnly)<span class="text-gray-600" aria-label="ไม่มี">—</span>@else<span class="text-lime-300 font-black" aria-label="มี">✓</span>@endif
                                        </td>
                                        <td class="px-2 sm:px-5 py-3 text-center"><span class="text-amber-300 font-black" aria-label="มี">✓</span></td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="px-5 py-4 text-xs text-gray-500 border-t border-white/5">1-Click Full Optimize ใช้ฟรีทุกขั้น ยกเว้นขั้น Efficiency mode ที่เป็นของ Pro · ระหว่างทดลอง 48 ชั่วโมงใช้ได้ทุกฟีเจอร์</p>
            </div>
        </div>
    </section>

    {{-- ===================== REQUIREMENTS + INSTALL ===================== --}}
    <section class="relative py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-8">
                <div>
                    <p class="wxt-eyebrow mb-3">Requirements</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-8">ใช้กับเครื่องไหนได้บ้าง</h2>
                    <dl class="grid sm:grid-cols-2 gap-4">
                        @foreach([
                            ['ระบบปฏิบัติการ', 'Windows 10 หรือ 11 (64-bit)', 'Windows 10 / 11, 64-bit'],
                            ['ไม่ต้องติดตั้ง .NET', 'ทุกอย่างอยู่ในไฟล์เดียว แตก zip แล้วใช้ได้เลย', 'One self-contained file'],
                            ['สิทธิ์ Administrator', 'โปรแกรมขอสิทธิ์เองตอนเปิด เพราะงานดิสก์และเน็ตต้องใช้สิทธิ์ระดับระบบ', 'Asks for admin rights itself'],
                            ['อินเทอร์เน็ต', 'ต่อเน็ตตอนเปิดครั้งแรกเพื่อเริ่มทดลอง Pro และตอนใส่คีย์', 'Online for the trial and activation'],
                        ] as [$k, $v, $en])
                            <div class="wxt-card p-5">
                                <dt class="text-white font-bold mb-1">{{ $k }}</dt>
                                <dd class="text-sm text-gray-300">{{ $v }}</dd>
                                <dd class="text-xs text-gray-500 mt-1">{{ $en }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div>
                    <p class="wxt-eyebrow mb-3">Get started</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-8">เริ่มใช้ใน 3 ขั้น</h2>
                    <ol class="space-y-4">
                        @foreach([
                            ['ดาวน์โหลดไฟล์ .zip', 'ไฟล์มาจาก xman4289.com โดยตรง ใช้ได้ทั้งรุ่นฟรีและ Pro', 'Download the .zip'],
                            ['แตกไฟล์ แล้วเปิด WinXTools.exe', 'กด Yes ที่หน้าต่าง Windows ขอสิทธิ์ (UAC) โปรแกรมเลือกภาษาไทยหรืออังกฤษได้', 'Extract and open WinXTools.exe'],
                            ['ใช้ฟรีได้ทันที หรือทดลอง Pro 48 ชั่วโมง', 'ถูกใจแล้วซื้อคีย์ Pro ใส่ในโปรแกรมได้เลย ไม่ต้องลงใหม่', 'Free right away, or try Pro for 48 hours'],
                        ] as $n => [$title, $body, $en])
                            <li class="wxt-card p-5 flex gap-4">
                                <span class="flex-none w-10 h-10 rounded-xl wxt-btn-main flex items-center justify-center font-black text-lg">{{ $n + 1 }}</span>
                                <span>
                                    <span class="block text-white font-bold">{{ $title }}</span>
                                    <span class="block text-sm text-gray-300 mt-0.5">{{ $body }}</span>
                                    <span class="block text-xs text-gray-500 mt-1">{{ $en }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================== FAQ ============================== --}}
    <section class="relative py-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <p class="wxt-eyebrow mb-3">FAQ</p>
                <h2 class="text-3xl md:text-4xl font-black text-white">คำถามที่พบบ่อย</h2>
            </div>
            <div class="space-y-3">
                @foreach([
                    ['ฟรีจริงไหม ต้องสมัครอะไรก่อนหรือเปล่า?', 'ฟรีจริง 8 เครื่องมือ ใช้ได้ไม่จำกัดเวลา ดาวน์โหลดได้เลยโดยไม่ต้องล็อกอินหรือสมัครสมาชิก'],
                    ['ทดลอง Pro ทำอย่างไร?', 'เปิดโปรแกรมครั้งแรกตอนต่อเน็ต จะได้ทดลองทุกฟีเจอร์ Pro 48 ชั่วโมง (ครั้งเดียวต่อเครื่อง) หมดเวลาแล้วฟีเจอร์ฟรียังใช้ได้ตามปกติ'],
                    ['Pro ราคาเท่าไร ใช้ได้กี่เครื่อง?', 'Pro ราคา ฿' . $proPrice . ' ต่อปี 1 คีย์ใช้ได้ 1 เครื่อง ถ้ามีหลายเครื่องซื้อคีย์เพิ่มได้ ครบปีแล้วฟีเจอร์ Pro จะปิด ส่วนฟีเจอร์ฟรียังใช้ได้ตามเดิม'],
                    ['Defrag จะทำให้ SSD เสียไหม?', 'ไม่ WinXTools ไม่จัดเรียง SSD — SSD ได้คำสั่ง TRIM แทน ตามแบบที่ Windows แนะนำ ส่วนฮาร์ดดิสก์จะถูกจัดเรียงด้วยตัวจัดเรียงของ Windows เอง'],
                    ['สแกนแบดบล็อกจะเขียนทับข้อมูลไหม?', 'ไม่ การสแกนอ่านอย่างเดียว ไม่เขียนอะไรลงดิสก์ ส่วนการทำเครื่องหมาย sector เสียไม่ให้ Windows ใช้ จะทำเมื่อคุณสั่งเท่านั้น'],
                    ['Free Proxy เป็น VPN หรือเปล่า?', 'ไม่ใช่ เป็นพร็อกซีสาธารณะฟรีในต่างประเทศ และไม่เข้ารหัส เหมาะกับการเปิดเว็บผ่านประเทศอื่นชั่วคราว ไม่ควรใช้กับรหัสผ่านหรือข้อมูลสำคัญ'],
                    ['เล่นเกมที่มีระบบกันโกงได้ไหม?', 'ได้ ถ้าเกมที่มีระบบกันโกง (เช่น FACEIT, Vanguard) เตือนเรื่องไดรเวอร์ WinDivert ที่ใช้คุมเน็ต ให้ปิด WinXTools ก่อนเล่น'],
                    ['อัปเดตอย่างไร ปลอดภัยไหม?', 'โปรแกรมเช็กและอัปเดตตัวเองจาก xman4289.com ทุกแพ็กเกจมีลายเซ็นดิจิทัล และถูกตรวจก่อนติดตั้งทุกครั้ง ไฟล์ที่ถูกแก้ไขจะไม่ถูกติดตั้ง'],
                ] as [$q, $a])
                    <details class="wxt-faq wxt-card px-5 py-4">
                        <summary class="flex items-center justify-between gap-4 text-white font-bold">
                            <span>{{ $q }}</span>
                            <span class="pm flex-none w-7 h-7 rounded-full border border-white/15 flex items-center justify-center text-lime-300 text-lg leading-none">+</span>
                        </summary>
                        <p class="mt-3 text-gray-300 leading-relaxed">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================== FINAL CTA =========================== --}}
    <section class="relative py-24 isolate">
        <img src="{{ $shot('art-hero.webp') }}" alt="" aria-hidden="true" width="1536" height="1024" loading="lazy"
             class="wxt-art absolute -z-10 left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[160%] sm:w-[110%] lg:w-[80%] opacity-35">
        <div class="absolute inset-0 -z-10" style="background:radial-gradient(ellipse 42% 46% at 50% 52%, rgba(4,6,13,.86) 0%, rgba(4,6,13,.55) 55%, transparent 100%);"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-6xl font-black text-white mb-5">ทำให้เครื่องคุณ<span class="wxt-text-glow">เร็วอีกครั้ง</span></h2>
            <p class="text-lg text-gray-300 mb-1">ดาวน์โหลดรุ่นฟรีได้ทันที หรือปลดล็อกทุกฟีเจอร์ด้วย Pro เพียง ฿{{ $proPrice }} ต่อปี</p>
            <p class="text-sm text-gray-500 mb-9">Let's make your PC fast again.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('winx-tools.download') }}" class="wxt-btn-main inline-flex items-center gap-2 px-8 py-4 rounded-xl font-extrabold text-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <x-bi th="ดาวน์โหลดฟรี" en="Free download" />
                </a>
                <form action="{{ route('cart.add', $product) }}" method="POST">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <input type="hidden" name="license_type" value="yearly">
                    <input type="hidden" name="buy_now" value="1">
                    <button type="submit" class="wxt-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition cursor-pointer">
                        {{ $buyLabel }}
                    </button>
                </form>
                @if($ownsWinXTools)
                    <a href="{{ route('customer.licenses') }}" class="wxt-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">
                        ดูคีย์ License ของฉัน
                    </a>
                @endif
            </div>
            <p class="text-gray-500 text-sm mt-7">Windows 10/11 (64-bit) · ไม่ต้องลง .NET · ทดลอง Pro ฟรี 48 ชั่วโมง · 1 คีย์ = 1 เครื่อง</p>
            <a href="{{ route('products.index') }}" class="inline-block mt-4 text-sm text-cyan-300/90 hover:text-cyan-200"><x-bi th="ดูผลิตภัณฑ์อื่นของ XMAN Studio" en="More from XMAN Studio" /> →</a>
        </div>
    </section>

</div>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection

@push('scripts')
<script>
(function () {
    // tool explorers: every panel is visible without script; with it, one panel at a time
    document.querySelectorAll('[data-explorer]').forEach(function (ex) {
        var tabs = Array.prototype.slice.call(ex.querySelectorAll('[data-tab]'));
        var panels = Array.prototype.slice.call(ex.querySelectorAll('[data-panel]'));
        function show(i, focus) {
            tabs.forEach(function (t, k) { t.setAttribute('aria-selected', k === i ? 'true' : 'false'); t.tabIndex = k === i ? 0 : -1; });
            panels.forEach(function (p, k) { p.hidden = k !== i; });
            if (focus) { tabs[i].focus(); }
        }
        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { show(i, false); });
            t.addEventListener('keydown', function (e) {
                var n = tabs.length, j = null;
                if (e.key === 'ArrowDown' || e.key === 'ArrowRight') { j = (i + 1) % n; }
                if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') { j = (i - 1 + n) % n; }
                if (e.key === 'Home') { j = 0; }
                if (e.key === 'End') { j = n - 1; }
                if (j !== null) { e.preventDefault(); show(j, true); }
            });
        });
        show(0, false);
    });

    // intro video: poster + play button until the first play; chapters seek and follow along
    var video = document.getElementById('wxt-video');
    var cover = document.getElementById('wxt-play');
    if (!video || !cover) { return; }
    var chapters = Array.prototype.slice.call(document.querySelectorAll('[data-seek]'));
    function start(at) {
        cover.hidden = true;
        video.controls = true;
        if (typeof at === 'number') {
            try { video.currentTime = at; } catch (e) { /* not loaded yet: set again once metadata arrives */
                video.addEventListener('loadedmetadata', function once() { video.removeEventListener('loadedmetadata', once); video.currentTime = at; });
            }
        }
        var p = video.play();
        if (p && p.catch) { p.catch(function () {}); }
    }
    cover.addEventListener('click', function () { start(); });
    chapters.forEach(function (b) {
        b.addEventListener('click', function () {
            start(parseFloat(b.getAttribute('data-seek')) || 0);
            var box = video.getBoundingClientRect();
            if (box.top < 0 || box.bottom > window.innerHeight) { video.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        });
    });
    video.addEventListener('timeupdate', function () {
        var t = video.currentTime, current = null;
        chapters.forEach(function (b) { if (t + 0.5 >= parseFloat(b.getAttribute('data-seek'))) { current = b; } });
        chapters.forEach(function (b) { b.setAttribute('aria-current', b === current ? 'true' : 'false'); });
    });
})();
</script>
@endpush
