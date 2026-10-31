@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย และไม่มีอะไรให้ซื้อ (กฎเจ้าของ 2026-10-12) --}}
@php
    $cs = $product->isComingSoon();
    $v2 = fn (string $file) => is_file(public_path('images/products/xcluade-agent/v2/' . $file)) ? asset('images/products/xcluade-agent/v2/' . $file) : null;
    $ogImage = $v2('og.jpg') ?? asset('artwork/hub/xcluade-agent.webp');
@endphp

@section('title', 'XcluadeAgent — ซิงก์ GitHub Release ขึ้นเซิร์ฟเวอร์อัตโนมัติ' . ($cs ? ' · Coming Soon' : '') . ' | XMAN Studio')
@section('meta_description', 'XcluadeAgent ดึง release ใหม่จาก GitHub ขึ้นเซิร์ฟเวอร์ Linux ให้เอง สำรองไฟล์เดิมก่อนทุกครั้ง ย้อนกลับได้ และย้อนกลับเองเมื่อ health check ไม่ผ่าน มีผู้ช่วย AI (Ollama, Claude, OpenAI) วิเคราะห์ว่า sync พังเพราะอะไร แจ้งเตือน Discord, Telegram, LINE, Slack และอีเมล' . ($cs ? ' — Coming Soon ยังไม่เปิดขายและดาวน์โหลด' : '') . ' · Self-hosted GitHub release sync with backups, rollback and AI error analysis.')
@section('og_image', $ogImage)

@section('content')
@php
    // ทุกข้อบนหน้านี้ตรวจกับซอร์สของแอป github.com/xjanova/xcluadeagent (release ล่าสุด v0.12.11) — ไม่ใช่คำอธิบายสินค้าในร้าน
    // (database/seeders/XmanProductsSeeder.php) ที่ยังพูดถึง SSL monitoring, AI แก้ production เอง และ Windows Server ซึ่งโค้ดยังไม่มี
    $owns = auth()->check() && $hasPurchased;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว — เปิดหน้านี้ต้องไม่ถามแหล่งไฟล์ภายนอก)
    $latest = $product->latestVersion();
    $opensOn = $product->coming_soon_until?->format('d/m/Y');

    // ราคาอ่านจากแถวสินค้าเท่านั้น (ห้ามเขียนตัวเลขลงหน้า) · ตะกร้าไม่ส่ง license_type มา → อายุคีย์ตาม Product::defaultLicenseType()
    $price = number_format((float) $product->price);
    $term = [
        \App\Models\LicenseKey::TYPE_LIFETIME => 'ไม่หมดอายุ',
        \App\Models\LicenseKey::TYPE_MONTHLY => '30 วัน',
        \App\Models\LicenseKey::TYPE_YEARLY => '1 ปี',
    ][$product->defaultLicenseType()] ?? null;
    $canBuy = ! $cs && ! $product->is_custom;

    // ภาพ: mock-up จากหน้าจอ Blazor ของแอป (scripts/product-mockups/build.py xcluade-agent) — ยังไม่มีภาพจับจริง
    $shot = fn (string $file) => asset('images/products/xcluade-agent/v2/' . $file);
    $artHero = $v2('art-hero.webp') ?? asset('artwork/hub/xcluade-agent.webp');
    $art2 = $v2('art-2.webp');

    // งานหลัก — SyncService.SyncAsync, ProjectConfig, FrameworkTypeExtensions.GetDefaultPostDeployCommands
    $coreRows = [
        [
            'img' => $shot('projects.webp'), 'mock' => true, 'name' => 'ดึง release ใหม่ขึ้นเซิร์ฟเวอร์ให้เอง', 'eyebrow' => 'GitHub release sync',
            'th' => 'ผูกแต่ละโปรเจกต์กับ repo บน GitHub แล้ว XcluadeAgent ดึงไฟล์ release ล่าสุดมาวางในโฟลเดอร์เว็บให้ ทันทีที่ GitHub ส่ง webhook มา หรือเช็กเองทุก 5 นาทีเมื่อเปิด Auto Sync จากนั้นรันคำสั่งหลัง deploy ตาม framework ต่อให้',
            'en' => 'Each project follows a GitHub repo: a new release is pulled into the web folder on a webhook, or every 5 minutes with Auto Sync, then the post-deploy commands run.',
            'points' => [
                'คำสั่งตั้งต้นตาม framework — Laravel, Symfony, Node.js, React, Vue, Nuxt, Next.js, Django, Flask, FastAPI, .NET — แก้เองได้',
                'ไม่ทับ .env, storage/, node_modules/ และ .git/ ของเซิร์ฟเวอร์',
                'คืนเจ้าของไฟล์ (owner / group) เดิมให้หลังวางไฟล์ใหม่',
            ],
            'alt' => 'ภาพตัวอย่างหน้า Projects ของ XcluadeAgent: การ์ดโปรเจกต์พร้อมสถานะ เวอร์ชัน และคำสั่งหลัง deploy ของ Laravel',
        ],
        [
            'img' => $shot('history.webp'), 'mock' => true, 'name' => 'สำรองก่อนทุก sync ย้อนกลับได้', 'eyebrow' => 'Backup · rollback',
            'th' => 'ก่อนวางไฟล์ใหม่ทุกครั้งจะสำรองของเดิมไว้ (เก็บ 5 ชุดล่าสุดเป็นค่าตั้งต้น) ถ้าตั้ง health check ไว้แล้วเว็บตอบไม่ตรงหลัง deploy ระบบย้อนกลับเป็นเวอร์ชันก่อนหน้าให้เอง และกดย้อนกลับเองจากหน้าประวัติได้',
            'en' => 'Every sync backs up the old files first. A failed health check rolls back on its own, and any backed-up sync can be rolled back by hand.',
            'points' => [
                'ประวัติทุกครั้ง: เวอร์ชันจากไหนไปไหน สั่งด้วยมือ webhook หรือตามรอบ ใช้เวลาเท่าไร กี่ไฟล์',
                'ส่งออกประวัติเป็น CSV หรือ JSON',
                'แจ้งเตือนทุกครั้งที่ sync สำเร็จหรือล้มเหลว',
            ],
            'alt' => 'ภาพตัวอย่างหน้า Sync History ของ XcluadeAgent: ตารางประวัติ sync พร้อมปุ่ม Rollback',
        ],
    ];

    // เครื่องมือรอบตัว — AiService, NotificationService, HealthCheckWorker, Setup (WebServerDetector), XcluadeAgent.Cli, UserRole
    $tools = [
        [
            'key' => 'ai', 'name' => 'ผู้ช่วย AI', 'short' => 'Ollama · Claude · OpenAI',
            'img' => $shot('ai.webp'), 'mock' => true,
            'th' => 'เมื่อ sync ล้มเหลว AI อ่าน error แล้วสรุปว่าเกิดอะไร สาเหตุจริงคืออะไร และควรทำอะไรต่อ เก็บไว้กับประวัติครั้งนั้น หรือวาง error เองในหน้า AI Assistant เพื่อขอวิธีแก้พร้อมคำสั่งที่ควรรัน',
            'en' => 'When a sync fails, the AI explains what happened, why, and what to do next. Paste any error to get a fix suggestion with the commands to run.',
            'points' => ['เลือกได้: Ollama ที่รันเอง หรือ Claude / OpenAI', 'ตั้งงบรายเดือนและดูจำนวน token ที่ใช้', 'รุ่นนี้ AI แนะนำอย่างเดียว ยังไม่แก้ไฟล์บนเซิร์ฟเวอร์เอง'],
            'alt' => 'ภาพตัวอย่างหน้า AI Assistant ของ XcluadeAgent: ผลวิเคราะห์ error สรุป สาเหตุ และสิ่งที่ควรทำ',
        ],
        [
            'key' => 'notify', 'name' => 'แจ้งเตือน 5 ช่องทาง', 'short' => 'Discord · Telegram · LINE · Slack · อีเมล',
            'th' => 'แจ้งทุกครั้งที่ sync สำเร็จ ล้มเหลว หรือ health check ไม่ผ่าน ไปที่ Discord, Telegram, LINE Official Account, Slack และอีเมล (SMTP) และส่งเข้า webhook ของคุณเองได้อีกหลายปลายทาง',
            'en' => 'Sync results and failed health checks go to Discord, Telegram, LINE OA, Slack and e-mail, plus any webhooks of your own.',
            'points' => ['ปุ่มทดสอบแต่ละช่องทางในหน้า Notifications'],
        ],
        [
            'key' => 'health', 'name' => 'เฝ้าเว็บทุก 5 นาที', 'short' => 'Health check · พื้นที่ดิสก์',
            'th' => 'ยิง URL health check ของทุกโปรเจกต์ทุก 5 นาที ถ้าสถานะไม่ตรงก็แจ้งเตือนทันที หน้า Dashboard บอกพื้นที่ดิสก์และเวลาทำงานของเครื่อง และเตือนเมื่อดิสก์ใกล้เต็ม',
            'en' => 'Every project\'s health URL is checked every 5 minutes; the dashboard shows disk space and uptime and warns when the disk runs low.',
            'points' => [],
        ],
        [
            'key' => 'scan', 'name' => 'นำเว็บเดิมเข้าระบบ', 'short' => 'ตัวช่วยตั้งค่าสแกนเซิร์ฟเวอร์',
            'th' => 'ตอนตั้งค่าครั้งแรก ตัวช่วยสแกนเซิร์ฟเวอร์หา web server (Nginx, Apache, Caddy, LiteSpeed, OpenLiteSpeed) เว็บที่มีอยู่ และ framework ของแต่ละเว็บ แล้วเลือกนำเข้าเป็นโปรเจกต์ได้เลย',
            'en' => 'The setup wizard finds your web server, the sites already on it and their frameworks, and imports them as projects.',
            'points' => [],
        ],
        [
            'key' => 'cli', 'name' => 'syncctl บรรทัดคำสั่ง', 'short' => 'status · list · sync · rollback',
            'th' => 'สั่งงานจาก terminal ได้ ทั้งดูสถานะ รายชื่อโปรเจกต์ สั่ง sync ย้อนกลับ และเช็กอัปเดตของตัวโปรแกรม',
            'en' => 'Status, project list, sync, rollback and update checks from the terminal.',
            'points' => [],
        ],
        [
            'key' => 'team', 'name' => 'ทำงานเป็นทีม', 'short' => 'สิทธิ์ 4 ระดับ · 2FA · Audit Log',
            'th' => 'แยกผู้ใช้เป็น Viewer, Operator, Admin และ SuperAdmin เปิดยืนยันตัวตนสองชั้น (TOTP) ได้ และ Audit Log บันทึกว่าใครทำอะไรเมื่อไร',
            'en' => 'Four roles, optional two-factor sign-in and an audit log of who did what.',
            'points' => [],
        ],
    ];

    $faq = array_values(array_filter([
        $cs ? ['XcluadeAgent เปิดขายเมื่อไร?', $opensOn
            ? 'กำหนดเปิดขาย ' . $opensOn . ' — ระหว่างนี้ยังซื้อและดาวน์โหลดไม่ได้'
            : 'ยังไม่ประกาศวันเปิดขาย ระหว่างนี้ยังซื้อและดาวน์โหลดไม่ได้ กด "แจ้งเตือนเมื่อเปิดขาย" ทิ้งไว้ได้'] : null,
        $product->is_custom ? null : ['ราคาเท่าไร ได้อะไร?', '฿' . $price . ' ต่อ 1 License key' . ($term ? ' อายุ ' . $term : '') . ' ได้คีย์ทางอีเมลและในหน้า License ของฉัน — จำนวนโปรเจกต์และผู้ใช้ที่ License ปลดในโปรแกรมจะประกาศพร้อมเปิดขาย'],
        ['AI แก้โค้ดบนเซิร์ฟเวอร์ให้เองไหม?', 'ยัง ในรุ่นปัจจุบัน AI วิเคราะห์ error และเสนอวิธีแก้พร้อมคำสั่งที่ควรรันเท่านั้น คุณเป็นคนตัดสินใจและลงมือเอง หน้าตั้งค่ามีโหมดระดับสูงกว่า (Fix + Review, Sandbox, Full Auto-Fix) ให้เลือกแล้ว แต่ส่วนที่นำ patch ไปใช้จริงยังไม่เปิด'],
        ['ย้อนกลับไปเวอร์ชันก่อนได้ไหม?', 'ได้ sync ที่มีสำรองไว้กดย้อนกลับได้จากหน้า Sync History หรือสั่ง syncctl rollback และถ้าตั้ง health check ไว้ ระบบย้อนกลับเองเมื่อเว็บตอบไม่ตรงหลัง deploy'],
        ['ต้องใช้ Docker ไหม?', 'ไม่ต้อง ตัวติดตั้งรันบน Ubuntu / Debian ตรง ๆ และใช้กับเว็บที่ไม่ได้อยู่ใน Docker (PHP, Node.js, Python บนเครื่อง) ได้ ถ้าอยากรัน XcluadeAgent ใน Docker ก็มี Docker Compose ให้'],
        ['ข้อมูลถูกส่งไปที่ไหนบ้าง?', 'XcluadeAgent ติดต่อ GitHub เพื่ออ่านและดาวน์โหลด release เช็กอัปเดตตัวเอง และตรวจ License ส่ง error ไปให้ผู้ให้บริการ AI ที่คุณเลือก (หรือ Ollama ที่รันเอง) และส่งแจ้งเตือนไปช่องทางที่เปิดไว้เท่านั้น ประวัติและการตั้งค่าเก็บในฐานข้อมูล SQLite บนเซิร์ฟเวอร์ของคุณ'],
    ]));

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'XcluadeAgent',
        'operatingSystem' => 'Linux',
        'applicationCategory' => 'DeveloperApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'ซิงก์ GitHub Release ขึ้นเซิร์ฟเวอร์อัตโนมัติ สำรองก่อนทุกครั้ง ย้อนกลับได้ พร้อมผู้ช่วย AI วิเคราะห์ error' . ($cs ? ' (Coming Soon)' : ''),
        'image' => $ogImage,
        'url' => route('products.show', $product->slug),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
    ];
    if ($canBuy) {
        $ld['offers'] = ['@type' => 'Offer', 'price' => (string) (float) $product->price, 'priceCurrency' => 'THB'];
    }
@endphp
<x-xp.page a="#7dd3fc" b="#a78bfa" c="#7dd3fc" glow="125 211 252" g1="#7dd3fc" g2="#a78bfa" g3="#c4b5fd">

    @if(session('success') || session('error'))
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            @if(session('success'))
                <div class="bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 rounded-xl backdrop-blur-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-xl backdrop-blur-sm">{{ session('error') }}</div>
            @endif
        </div>
    @endif

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$artHero"
               tagline="ดึง release ใหม่จาก GitHub ขึ้นเซิร์ฟเวอร์ให้เอง"
               tagline-en="Self-hosted GitHub release sync, with backups, rollback and an AI that explains failures."
               lead="ติดตั้งไว้บนเซิร์ฟเวอร์ของคุณเอง ผูกแต่ละเว็บกับ repo บน GitHub แล้ว XcluadeAgent วางไฟล์ release ใหม่ รันคำสั่งหลัง deploy ตรวจว่าเว็บยังตอบ และย้อนกลับให้ถ้าไม่ผ่าน — ดูทุกโปรเจกต์ได้จาก Dashboard เดียว"
               lead-en="Runs on your own server: it puts each new release in place, runs the post-deploy commands, checks the site still answers and rolls back if it does not.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'XcluadeAgent ' . $latest->version : 'XcluadeAgent' }}</span>
            <span class="xp-chip">Linux server · Web dashboard</span>
            @if($cs)<span class="xp-chip">Coming Soon</span>@endif
        </x-slot:chips>
        <x-slot:title>Xcluade<span class="xp-text-glow">Agent</span></x-slot:title>
        <x-slot:actions>
            @if($cs)
                <x-xp.download :href="route('customer.downloads')" :coming-soon="true" />
                <a href="#features" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">ดูว่าทำอะไรได้</a>
            @else
                @if($owns)
                    <x-xp.download :href="route('customer.downloads')" th="ดาวน์โหลด" en="Download" />
                @endif
                @if($product->is_custom)
                    <a href="{{ route('quote.index') }}" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">ติดต่อสอบถามราคา</a>
                @else
                    <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">{{ $owns ? 'ซื้อ License เพิ่ม' : 'ซื้อ License' }} — ฿{{ $price }}</a>
                @endif
            @endif
        </x-slot:actions>
        <x-slot:notes>
            @if($cs)
                <p class="mt-4 text-sm text-gray-400">
                    {{ $opensOn ? 'กำหนดเปิดขาย ' . $opensOn : 'ยังไม่ประกาศวันเปิดขาย' }} · ยังไม่เปิดให้ซื้อหรือดาวน์โหลด ·
                    <a href="{{ route('quote.index') }}" class="xp-link font-semibold">แจ้งเตือนเมื่อเปิดขาย</a>
                </p>
            @elseif($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · สำหรับผู้ที่มี License</p>
            @endif
            @if(isset($userLicense) && $userLicense)
                @if($userLicense->isValid())
                    @php($daysLeft = $userLicense->daysRemaining())
                    @if($daysLeft <= 7 && $userLicense->license_type !== 'lifetime')
                        <p class="mt-3 text-sm text-amber-200" data-license-banner="expiring">
                            License ใกล้หมดอายุ — เหลืออีก {{ $daysLeft }} วัน @if($userLicense->expires_at)({{ $userLicense->expires_at->format('d/m/Y') }})@endif ·
                            <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline">ต่ออายุตอนนี้</a>
                        </p>
                    @else
                        <p class="mt-3 text-sm text-emerald-200/90" data-license-banner="valid">
                            คุณมี License แล้ว · {{ $userLicense->license_type === 'lifetime' || ! $userLicense->expires_at ? 'ใบอนุญาตตลอดชีพ' : 'หมดอายุ ' . $userLicense->expires_at->format('d/m/Y') }} ·
                            <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline">ดู License ของฉัน</a>
                        </p>
                    @endif
                @else
                    <p class="mt-3 text-sm text-red-300" data-license-banner="expired">
                        License ของคุณหมดอายุแล้ว @if($userLicense->expires_at)เมื่อ {{ $userLicense->expires_at->format('d/m/Y') }}@endif ·
                        <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline">ต่ออายุตอนนี้</a>
                    </p>
                @endif
            @endif
        </x-slot:notes>
        <x-slot:badges>
            @if($cs)
                <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="soon" /> ยังไม่เปิดขาย</li>
            @endif
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> สำรองก่อนทุก sync</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ติดตั้งบนเซิร์ฟเวอร์ของคุณเอง</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('dashboard.webp')" :eager="true" :mock="true"
                             alt="ภาพตัวอย่างหน้า Dashboard ของ XcluadeAgent: จำนวนโปรเจกต์ สถานะ sync ล่าสุด สุขภาพเครื่อง ผู้ช่วย AI และ License" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ก่อนวางไฟล์ใหม่ · Before every sync</p>
                <p class="text-white font-extrabold text-lg leading-tight">สำรอง<span class="text-sky-300">ของเดิมไว้ก่อน</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">Health check ไม่ผ่าน</p>
                <p class="text-white font-bold leading-tight">ย้อนกลับเวอร์ชันก่อน<br><span class="text-gray-400 text-sm font-normal">→ แล้วแจ้งเตือนคุณ</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ============================ CORE ============================ --}}
    <section class="relative py-20 scroll-mt-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Release in, site up" title="ออก release แล้ว" title2="เว็บอัปเดตเอง"
                       sub="เลิก SSH เข้าไปดึงไฟล์ รันคำสั่ง แล้วลุ้นว่าเว็บจะพังไหม — XcluadeAgent ทำขั้นตอนเดิมให้ทุกครั้งเหมือนกันหมด และเก็บทางถอยไว้เสมอ"
                       en="No more SSH, copy, run and hope: the same steps every time, with a way back kept ready." />
            <x-xp.rows :items="$coreRows" />
        </div>
    </section>

    {{-- ============================ TOOLS ============================ --}}
    <section class="relative py-20 isolate">
        @if($art2)
            <x-xp.art :src="$art2" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        @endif
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="Around every sync" title="AI บอกว่าพังเพราะอะไร" title2="และรู้ก่อนลูกค้า"
                       sub="ผู้ช่วย AI วิเคราะห์ error แจ้งเตือน 5 ช่องทาง เฝ้า health check ทุก 5 นาที ตัวช่วยนำเว็บเดิมเข้าระบบ บรรทัดคำสั่ง และสิทธิ์ผู้ใช้หลายระดับ"
                       en="AI error analysis, alerts on five channels, health checks, a setup scan, a CLI and team roles." />
            <x-xp.explorer id="tools" label="เครื่องมือของ XcluadeAgent" :tools="$tools" />
        </div>
    </section>

    {{-- ============================ SAFETY ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Always a way back" title="deploy ได้บ่อย" title2="โดยไม่กลัวพัง"
                       sub="ค่าตั้งต้นของทุกโปรเจกต์ออกแบบให้ถอยได้ และไม่แตะของที่เซิร์ฟเวอร์ต้องเก็บไว้"
                       en="Every project's defaults keep a way back and leave the server's own files alone." />
            <x-xp.cards :items="[
                ['icon' => 'shield', 'title' => 'สำรองก่อนเสมอ', 'body' => 'สำรองไฟล์เดิมก่อนวางของใหม่ทุกครั้ง เก็บ 5 ชุดล่าสุดเป็นค่าตั้งต้น ปรับจำนวนได้ต่อโปรเจกต์', 'en' => 'A backup before every sync, the last five kept'],
                ['icon' => 'undo', 'title' => 'ย้อนกลับเมื่อ health check ไม่ผ่าน', 'body' => 'หลังรันคำสั่ง deploy ระบบเรียก URL ที่ตั้งไว้ ถ้าไม่ได้สถานะที่คาด (ค่าตั้งต้น 200) ก็คืนไฟล์จากสำรองแล้วแจ้งเตือน', 'en' => 'A failed health check restores the backup'],
                ['icon' => 'lock', 'title' => 'ไม่ทับไฟล์ของเซิร์ฟเวอร์', 'body' => '.env, .env.*, storage/, node_modules/ และ .git/ ถูกข้ามเป็นค่าตั้งต้น และคืนเจ้าของไฟล์เดิมให้หลังวางไฟล์', 'en' => 'Your .env and storage stay as they are'],
                ['icon' => 'key', 'title' => 'webhook ต้องมีลายเซ็น', 'body' => 'แต่ละโปรเจกต์ได้ secret ของตัวเอง webhook จาก GitHub ที่ไม่มีลายเซ็น X-Hub-Signature-256 ถูกปฏิเสธ', 'en' => 'Signed GitHub webhooks only'],
                ['icon' => 'user', 'title' => 'กันเดารหัสเข้า Dashboard', 'body' => 'รหัสผิด 5 ครั้งล็อกบัญชี 15 นาที เปิดยืนยันตัวตนสองชั้น (TOTP) ได้ และแยกสิทธิ์ 4 ระดับ', 'en' => 'Lockout after 5 failed logins, optional 2FA'],
                ['icon' => 'eye', 'title' => 'AI ไม่แตะไฟล์เอง', 'body' => 'รุ่นนี้ AI วิเคราะห์และแนะนำเท่านั้น การนำ patch ไปใช้กับไฟล์จริงยังไม่เปิด ทุกการแก้ยังผ่านมือคุณ', 'en' => 'The AI advises; it does not change your files'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="ราคา" :title2="$cs ? 'เปิดขายเร็ว ๆ นี้' : 'XcluadeAgent License'"
                       :sub="$cs ? 'ราคาที่ตั้งไว้ด้านล่าง · ยังไม่เปิดให้ซื้อจนกว่าจะเปิดขาย' : 'ซื้อแล้วได้ License key ทางอีเมลและในหน้า License ของฉัน'"
                       :en="$cs ? 'The listed price. Not on sale until launch.' : 'Your key arrives by e-mail and in My Licenses.'" />

            <div class="grid md:grid-cols-2 gap-5 max-w-4xl mx-auto">
                <div class="xp-featured">
                    <div class="p-7 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-2xl font-black text-white">XcluadeAgent License</h3>
                            @if($cs)<x-xp.badge tier="soon" />@endif
                        </div>
                        <p class="text-amber-100/80 text-sm mb-6">{{ $term ? 'อายุ License ' . $term . ' · ' : '' }}1 คีย์ต่อการซื้อ 1 ชิ้น</p>
                        @if($product->is_custom)
                            <div class="mb-6"><span class="text-4xl font-black text-white">สอบถามราคา</span></div>
                        @else
                            <div class="mb-6 flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white">฿{{ $price }}</span>
                                @if($term)<span class="text-gray-300">/ {{ $term }}</span>@endif
                            </div>
                            <x-tpix-price variant="detail" :thb="$product->price" :tpix="$product->price_tpix" class="-mt-4 mb-6" />
                        @endif
                        <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="array_values(array_filter([
                            'Dashboard บนเว็บ + บรรทัดคำสั่ง syncctl',
                            'สำรอง · ย้อนกลับ · health check · แจ้งเตือน 5 ช่องทาง',
                            'ผู้ช่วย AI (Ollama, Claude หรือ OpenAI ของคุณเอง)',
                            $term ? 'อายุ License: ' . $term : null,
                        ]))" />
                        @if($cs)
                            <span class="xp-btn-main xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย{{ $opensOn ? ' · เปิด ' . $opensOn : '' }}</span>
                        @elseif($product->is_custom)
                            <a href="{{ route('quote.index') }}" class="xp-btn-pro block w-full py-3.5 text-center rounded-xl font-extrabold transition">ติดต่อสอบถามราคา</a>
                        @else
                            {{-- ราคามาจากแถวสินค้า ไม่ใช่จากฟอร์ม — ตะกร้าคิดราคาเอง --}}
                            <form action="{{ route('cart.add', $product) }}" method="POST" class="flex items-stretch gap-3">
                                @csrf
                                <div class="flex items-center xp-btn-ghost rounded-xl overflow-hidden">
                                    <button type="button" onclick="xcaQty(-1)" class="px-3 py-3 text-gray-300 hover:text-white" aria-label="ลดจำนวน">−</button>
                                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="99" aria-label="จำนวน License"
                                           class="w-12 text-center bg-transparent text-white focus:outline-none">
                                    <button type="button" onclick="xcaQty(1)" class="px-3 py-3 text-gray-300 hover:text-white" aria-label="เพิ่มจำนวน">+</button>
                                </div>
                                <button type="submit" class="xp-btn-pro flex-1 py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                    {{ $owns ? 'ซื้อ License เพิ่ม' : 'ซื้อ License' }} — ฿{{ $price }}
                                </button>
                            </form>
                        @endif
                        @if($product->sku)<p class="mt-4 text-xs text-gray-500">รหัสสินค้า {{ $product->sku }}</p>@endif
                    </div>
                </div>

                <div class="xp-card p-7 flex flex-col">
                    <h3 class="text-xl font-black text-white mb-4">เกี่ยวกับ License</h3>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-6 flex-1" :items="[
                        'ได้ License key ทางอีเมล และดูได้ในหน้า License ของฉัน',
                        'ต่ออายุคีย์เดิมได้จากหน้า License ของฉัน',
                        'จำนวนโปรเจกต์และผู้ใช้ที่ License ปลดในโปรแกรม ประกาศพร้อมเปิดขาย',
                    ]" />
                    @if($cs)
                        <a href="{{ route('quote.index') }}" class="xp-btn-ghost block w-full py-3.5 text-center rounded-xl font-bold transition">แจ้งเตือนเมื่อเปิดขาย</a>
                    @elseif(auth()->check())
                        <a href="{{ route('customer.licenses') }}" class="xp-btn-ghost block w-full py-3.5 text-center rounded-xl font-bold transition">ดู License ของฉัน</a>
                    @else
                        <a href="{{ route('quote.index') }}" class="xp-btn-ghost block w-full py-3.5 text-center rounded-xl font-bold transition">ติดต่อสอบถาม</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ========================== DOWNLOAD ========================== --}}
    @if($latest)
        <section class="relative py-16 scroll-mt-20" id="download">
            <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="xp-card p-7 text-center">
                    <p class="xp-eyebrow mb-3">Download</p>
                    <h2 class="text-3xl font-black text-white mb-2">ดาวน์โหลด XcluadeAgent</h2>
                    <p class="text-gray-400 mb-6">{{ $cs ? 'ยังเป็น Coming Soon — เปิดให้ดาวน์โหลดเมื่อเปิดขาย' : 'สำหรับผู้ที่มี License · ไฟล์จาก xman4289.com โดยตรง' }}</p>
                    <div class="flex items-center justify-between text-left mb-6">
                        <div><p class="text-gray-400 text-sm">เวอร์ชันล่าสุด</p><p class="text-2xl font-bold text-white">v{{ ltrim($latest->version, 'v') }}</p></div>
                        @if($latest->file_size)<div class="text-right"><p class="text-gray-400 text-sm">ขนาดไฟล์</p><p class="text-lg font-semibold text-white">{{ $latest->file_size_formatted }}</p></div>@endif
                    </div>
                    @if($latest->changelog)
                        <div class="text-left mb-6 bg-white/[.03] rounded-lg p-4">
                            <p class="text-gray-400 text-sm mb-2">Changelog</p>
                            <p class="text-gray-300 text-sm whitespace-pre-wrap">{{ Str::limit($latest->changelog, 200) }}</p>
                        </div>
                    @endif
                    @if($cs)
                        <x-xp.download block :href="route('customer.downloads')" :coming-soon="true" />
                    @elseif($owns)
                        <x-xp.download block :href="route('customer.downloads')" th="ไปที่ศูนย์ดาวน์โหลด" en="Download center" />
                    @elseif(! $product->is_custom)
                        <a href="#pricing" class="xp-btn-pro block w-full py-3.5 text-center rounded-xl font-extrabold transition">ซื้อ License เพื่อดาวน์โหลด</a>
                    @endif
                    <p class="text-gray-500 text-sm mt-5">อัปเดตเมื่อ {{ $latest->synced_at?->format('d/m/Y') ?? '—' }} · ติดตั้งบนเซิร์ฟเวอร์ Linux</p>
                </div>
            </div>
        </section>
    @endif

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Linux — ตัวติดตั้งรองรับ Ubuntu 20.04+ และ Debian 10+ หรือรันใน Docker / Docker Compose', 'Ubuntu or Debian, or Docker'],
            ['.NET 8', 'ตัวติดตั้งลง .NET 8 Runtime ให้เองถ้ายังไม่มี', '.NET 8, installed for you'],
            ['สิทธิ์ root', 'ตัวติดตั้งสร้างบริการ systemd ตั้ง reverse proxy ของ Nginx และขอ SSL ด้วย certbot ถ้าคุณใส่โดเมน', 'Run the installer with sudo'],
            ['GitHub', 'Personal Access Token หรือเชื่อมบัญชี GitHub จากหน้า Dashboard เพื่ออ่าน release ของ repo', 'A token or a linked GitHub account'],
        ]"
        :specs-note="$cs ? 'ยังไม่เปิดให้ดาวน์โหลด — ขั้นตอนนี้ใช้ได้เมื่อเปิดขายแล้ว' : 'Dashboard เปิดที่พอร์ต 5000 เป็นค่าตั้งต้น เปลี่ยนได้ตอนติดตั้ง'"
        :steps="[
            ['รันตัวติดตั้งบนเซิร์ฟเวอร์', 'ตรวจระบบ ลง .NET 8 และสร้างบริการ systemd ให้ — หรือใช้ Docker Compose', 'Run the installer, or Docker Compose'],
            ['ตอบคำถามไม่กี่ข้อ', 'พอร์ต โดเมน SSL GitHub token บัญชีผู้ดูแล และจะเปิด AI หรือไม่', 'Port, domain, SSL, token, admin, AI'],
            ['เปิด Dashboard แล้วเพิ่มโปรเจกต์', 'เลือก repo กับโฟลเดอร์เว็บ หรือให้ตัวช่วยสแกนเว็บที่มีอยู่แล้วนำเข้า', 'Add projects, or import the sites found'],
        ]" />

    <x-xp.faq :items="$faq" />

    {{-- ======================= RELATED PRODUCTS ======================= --}}
    @if($relatedProducts && $relatedProducts->count() > 0)
        <section class="relative py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-black text-white text-center mb-10">ผลิตภัณฑ์ที่เกี่ยวข้อง</h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $related)
                        <a href="{{ route('products.show', $related->slug) }}" class="xp-card group overflow-hidden transition">
                            <div class="aspect-video bg-white/[.03] overflow-hidden">
                                @if($related->artwork_url)
                                    <img src="{{ $related->artwork_url }}" alt="{{ $related->name }}" loading="lazy" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-white mb-2">{{ $related->name }}</h3>
                                @if($related->short_description)
                                    <p class="text-gray-400 text-sm mb-2 line-clamp-2">{{ $related->short_description }}</p>
                                @endif
                                @if(! $related->is_custom)
                                    <p class="xp-link font-bold text-lg">฿{{ number_format($related->price, 0) }}</p>
                                    <x-tpix-price :thb="$related->price" :tpix="$related->price_tpix" />
                                @else
                                    <p class="text-gray-400">สอบถามราคา</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$artHero" title="ออก release แล้ว" glow="ปล่อยให้มันขึ้นเอง"
              :sub="$cs ? 'XcluadeAgent ยังเป็น Coming Soon — ยังไม่เปิดขายและดาวน์โหลด ทิ้งชื่อไว้ให้เราแจ้งเมื่อเปิด' : 'สำรองก่อนทุกครั้ง ย้อนกลับได้เสมอ และมี AI ช่วยบอกว่าพังเพราะอะไร'"
              en="Ship the release; let it land by itself.">
        @if($cs)
            <x-xp.download :href="route('customer.downloads')" :coming-soon="true" class="!px-8" />
            <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">แจ้งเตือนเมื่อเปิดขาย</a>
        @else
            @if($canBuy)
                <form action="{{ route('cart.add', $product) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition cursor-pointer">ซื้อ License — ฿{{ $price }}</button>
                </form>
            @endif
            <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ติดต่อสอบถาม</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">เซิร์ฟเวอร์ Linux (Ubuntu / Debian) หรือ Docker · .NET 8 · Dashboard บนเว็บ + syncctl</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

@if($canBuy)
    @push('scripts')
    <script>
    function xcaQty(step) {
        var input = document.getElementById('quantity');
        var next = (parseInt(input.value, 10) || 1) + step;
        input.value = Math.min(parseInt(input.max, 10), Math.max(parseInt(input.min, 10), next));
    }
    </script>
    @endpush
@endif

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
