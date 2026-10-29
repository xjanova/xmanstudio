@extends($publicLayout ?? 'layouts.app')
{{-- BrainX / BrainX Cloud — the BrainX app links here to buy and to renew (ProductController passes $renewalKey).
     Coming Soon (owner 2026-10-12): production lists BrainX as `brainx-cloud`; while any BrainX row is Coming Soon
     there is no download and nothing to buy here, and no text may call the app free. --}}
@php
    $csBrainx = \App\Models\Product::brainxComingSoonRow() !== null || $product->isComingSoon();
    // ราคามาจาก config/licenses.php 'plans' ที่เดียว (ตะกร้าคิดตามนี้) — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $headPrice = number_format(\App\Support\LicensePlans::price('brainx', 'monthly') ?? 0);
@endphp

@section('title', 'BrainX — สมองที่สองที่ AI ทุกตัวใช้ร่วมกัน · Pro + Cloud ฿' . $headPrice . '/เดือน | XMAN Studio')
@section('meta_description', 'BrainX เปลี่ยนโน้ต Markdown ให้เป็นกราฟความรู้ 3 มิติ ค้นด้วยความหมาย และเป็น MCP server ให้ Claude, Codex และ CluadeX ใช้สมองก้อนเดียวกัน · คีย์ Pro + BrainX Cloud ฿' . $headPrice . ' ต่อเดือน: ห้องทำงานร่วมของเอเจนต์ ผู้ช่วยเสียง Mind และ Remote MCP ให้ Claude ใช้ได้ทุกเครื่อง · A visible second brain every agent shares, with BrainX Cloud for any Claude, anywhere.')
@section('og_image', asset('images/products/brainx/v2/og.jpg'))

@section('content')
@php
    $prices = \App\Support\LicensePlans::for('brainx');
    $monthly = number_format($prices['monthly'] ?? 0);

    // คีย์ที่การซื้อครั้งนี้จะต่ออายุให้ (null = ยังไม่มี / ยังไม่ล็อกอิน / คีย์ทุกใบถูกยกเลิก → ได้คีย์ใหม่)
    $key = $renewalKey ?? null;
    $keyLapsed = $key && $key->isExpired();
    $maskedKey = $key ? substr($key->license_key, 0, 4) . '-••••-••••-' . substr($key->license_key, -4) : null;

    // ขายได้ไหม — กติกาเดียวกับ CartController::add: Coming Soon ไม่ขาย ยกเว้นต่ออายุคีย์ของตัวเองเมื่อแอดมินเปิด "ต่ออายุได้ระหว่าง Coming Soon"
    $canRenew = $key && $product->acceptsRenewals() && (! $csBrainx || $product->renew_while_coming_soon);
    $onSale = $key ? $canRenew : (! $csBrainx && $product->isAvailable());

    // ผู้ใช้เดิม (มีคีย์ BrainX หรือแอดมิน) ยังดาวน์โหลดได้ระหว่าง Coming Soon — จากหน้า License ไม่ใช่จากหน้านี้
    $existingUser = \App\Models\Product::isExistingBrainxUser(auth()->user());

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว)
    $latest = $product->latestVersion();

    // พื้นที่คลาวด์ต่อคีย์และแพ็กพื้นที่เพิ่ม — config/licenses.php 'cloud_storage'
    $baseGb = \App\Support\CloudStorage::baseGb('brainx');
    $packs = \App\Support\CloudStorage::addons('brainx');
    $packRow = \App\Models\Product::where('slug', \App\Support\CloudStorage::addonProductSlug('brainx'))->first();
    $packsOnSale = ! $csBrainx && $packs && $packRow?->isAvailable();

    $mcpCommand = 'claude mcp add --transport http brainx https://serverbrain.xman4289.com/mcp --header "Authorization: Bearer bxc_..."';

    // ภาพหน้าจอ: hud / hud-wide = ภาพจริงของแอป (เบลอชื่อสมอง ที่อยู่โหนด path และเวอร์ชันเก่าแล้ว) ที่เหลือเป็น mockup
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py brainx
    $shot = fn (string $file) => asset('images/products/brainx/v2/' . $file);

    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'Monthly', 'unit' => '/ เดือน', 'note' => '30 วันต่อเดือนที่ซื้อ ต่ออายุแล้วใช้คีย์เดิม', 'days' => '30 วันต่อเดือน'],
        'yearly' => ['name' => 'รายปี', 'en' => 'Yearly', 'unit' => '/ ปี', 'note' => 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'Lifetime', 'unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ'],
    ];

    // แกนของแอป: ทำงานโดยไม่ต้องมีคีย์ — ProGate.cs ("Free: the brain itself …")
    $coreRows = [
        [
            'img' => $shot('hud-wide.webp'), 'h' => 645, 'name' => 'สมองที่มองเห็นได้', 'eyebrow' => 'The Universe',
            'th' => 'ทุกโน้ตเป็นดาว ทุกลิงก์เป็นเส้นแรง โน้ตหมวดเดียวกันจับกลุ่มเป็นกาแล็กซีเอง หน้าจอเดียวเห็นทั้งความรู้ที่มี ความเชี่ยวชาญแต่ละด้าน สิ่งที่เอเจนต์กำลังทำ และสถานะเครื่อง',
            'en' => 'Every note a star, every link a thread, every topic its own galaxy — with your expertise, agent activity and system status around it.',
            'points' => ['หมุน ซูม คลิกดาวเพื่อเปิดโน้ต', 'แผงทุกแผงลากย้าย ยืดขยาย และรีเซ็ตได้', 'อ่าน Markdown และ Obsidian vault เดิมได้เลย ไม่ต้องย้ายไฟล์'],
            'alt' => 'หน้าจอจริงของ BrainX: กราฟความรู้ 3 มิติกลางจอ ล้อมด้วยแผงความเชี่ยวชาญ Agent Bus กิจกรรม MCP และสถานะเครื่อง',
        ],
        [
            'img' => $shot('search.webp'), 'mock' => true, 'name' => 'ค้นด้วยความหมาย', 'eyebrow' => 'Hybrid search',
            'th' => 'ไม่ต้องจำคำเป๊ะ ระบบรวมการค้นด้วยความหมายกับคำสำคัญแล้วจัดอันดับรวมกัน ถามเป็นภาษาไทยธรรมดาก็เจอโน้ตที่ไม่มีคำนั้นอยู่เลย และแสดงประโยคที่ตรงให้เห็นทันที',
            'en' => 'Meaning and keywords, fused by rank. Ask in plain Thai and find notes that never use your words, with the matching lines shown.',
            'points' => ['ค้นทั้งเนื้อโน้ต ไม่ใช่แค่หัวข้อ', 'ภาษาไทยไม่ต้องเว้นวรรคก็ค้นเจอ', 'ค้นด้วยความหมายทำบนเครื่องคุณเองผ่าน Ollama'],
            'alt' => 'ภาพตัวอย่างการค้นใน BrainX: ช่องค้นภาษาไทย และผลลัพธ์พร้อมหมวด ข้อความที่ตรง และคะแนน',
        ],
        [
            'img' => $shot('agents.webp'), 'mock' => true, 'name' => 'ศูนย์กลาง MCP ของเอเจนต์ทุกตัว', 'eyebrow' => 'One brain, every agent',
            'th' => 'Claude Desktop, Claude Code, Codex และ CluadeX ต่อเข้า BrainX ผ่าน MCP แล้วค้น อ่าน เขียน และเชื่อมโน้ตในสมองก้อนเดียวกัน ส่งข้อความหากันผ่าน Agent Bus และส่งสเปกงานให้กันเป็นโน้ตใน Tasks/',
            'en' => 'Claude, Codex and CluadeX read and write one brain over MCP, message each other on the Agent Bus and hand work over as task notes.',
            'points' => ['ทุกการเรียกถูกบันทึกเป็นบันทึกประจำวัน รวมถึงครั้งที่ล้มเหลว', 'ตั้งค่า MCP ให้ Claude Desktop และ Claude Code เองเมื่อเปิดโปรแกรม', 'ต่อ Unity, Unreal และ Roblox Studio ให้เอเจนต์ใช้ผ่านสมองได้'],
            'alt' => 'ภาพตัวอย่างหน้า Agent Bus ของ BrainX: เอเจนต์ที่ออนไลน์ กิจกรรมการเรียก MCP และคิวงาน',
        ],
    ];

    // Pro — ProFeature ใน BrainX.Core/Services/License/ProGate.cs (ด่าน ProGate.Allows ในแอป, brainx-mcp และ Mind)
    $proTools = [
        [
            'key' => 'cowork', 'name' => 'ห้องทำงานร่วม (Cowork)', 'short' => 'เอเจนต์ทำงานเป็นทีม คุณเป็นบอส', 'tier' => 'pro',
            'img' => $shot('cowork.webp'), 'mock' => true,
            'th' => 'ห้องที่เห็นว่าเอเจนต์ทุกตัวคุยอะไรกัน คุณพิมพ์สั่งทีเดียวทุกคนได้ยิน มาย (เลขาของห้อง) แบ่งงานและเรียกเอเจนต์เข้ามาทำ เรื่องที่ต้องให้คุณตัดสินขึ้นมารอที่มุมห้อง ส่งรูปและไฟล์ในห้องได้',
            'en' => 'A room where you see what your agents say to each other, speak to all of them at once, and decide what they stop on.',
            'points' => ['บอร์ดงาน: ใครทำอะไรอยู่ พัก เลิก หรือโฟกัสงานได้', 'ประวัติงานย้อนหลังแยกตามโปรเจค', 'เลือกโมเดลที่มายใช้เรียกเอเจนต์'],
            'alt' => 'ภาพตัวอย่างห้องทำงานร่วมของ BrainX: บทสนทนาของเจ้าของกับเอเจนต์ บอร์ดงาน และเรื่องที่รอตัดสิน',
        ],
        [
            'key' => 'mind', 'name' => 'Mind ผู้ช่วยเสียง', 'short' => 'คุยกับสมองของคุณ', 'tier' => 'pro',
            'th' => 'ผู้ช่วยที่ตอบจากสมองของคุณ เปิดเป็นมุมหนึ่งในแดชบอร์ดหรือแยกเป็นหน้าต่างของตัวเองก็ได้ ทำงานได้แม้ไม่ได้เปิดหน้าต่างหลักของ BrainX',
            'en' => 'An assistant that answers from your own brain, docked in the dashboard or in a window of its own.',
            'points' => [],
        ],
        [
            'key' => 'media', 'name' => 'สร้างภาพและวิดีโอ', 'short' => 'สั่งจากเอเจนต์ได้เลย', 'tier' => 'pro',
            'th' => 'เอเจนต์ที่ต่อสมองอยู่สั่งสร้างภาพหรือวิดีโอสั้นได้ด้วยเครื่องมือ media_generate โดยไม่ต้องเปิดเบราว์เซอร์ ใช้บัญชี Grok หรือคีย์ Google AI ของคุณเองบนเครื่องนี้',
            'en' => 'Agents make images and short videos through media_generate, on your own Grok login or Google AI key.',
            'points' => ['ไฟล์ที่สร้างส่งต่อเข้าห้องทำงานร่วมได้'],
        ],
    ];

    $compare = [
        ['สมอง · The brain', [
            ['กราฟความรู้ 3 มิติ (Universe) · Editor · Vault Explorer', false],
            ['ค้นแบบ hybrid ความหมาย + คำสำคัญ (ภาษาไทยได้)', false],
            ['MCP server ให้ Claude, Codex, CluadeX — ค้น อ่าน เขียน จำ', false],
            ['Agent Bus · ส่งงานเป็นโน้ต (Tasks/) · บันทึกทุกการเรียก', false],
            ['ตัวสวนดูแลสุขภาพสมอง · อัปเดตที่ตรวจลายเซ็น', false],
        ]],
        ['Pro + Cloud', [
            ['ห้องทำงานร่วม (Cowork) + มายจัดสรรงาน', true],
            ['Mind ผู้ช่วยเสียง', true],
            ['สร้างภาพ/วิดีโอด้วย media_generate', true],
            ['BrainX Cloud: Remote MCP + พื้นที่ ' . $baseGb . ' GB ต่อคีย์', true],
        ]],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'BrainX',
        'operatingSystem' => 'Windows 10/11 (64-bit)',
        'applicationCategory' => 'ProductivityApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'สมองที่สองที่มองเห็นได้: กราฟความรู้ 3 มิติ ค้นด้วยความหมาย และ MCP server ให้เอเจนต์ AI ทุกตัวใช้ความรู้ก้อนเดียวกัน พร้อม BrainX Cloud ให้ Claude ใช้ได้ทุกเครื่อง',
        'image' => asset('images/products/brainx/v2/og.jpg'),
        'url' => route('products.show', 'brainx'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => collect($prices)->map(fn ($price, $term) => ['@type' => 'Offer', 'name' => 'BrainX Pro + Cloud (' . ($plans[$term]['en'] ?? $term) . ')', 'price' => (string) $price, 'priceCurrency' => 'THB'])->values()->all(),
    ];
@endphp
<x-xp.page a="#c4b5fd" b="#67e8f9" c="#a78bfa" glow="139 92 246" g1="#ff5fa8" g2="#9d7bff" g3="#5be9e9">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="สมองที่สองที่ AI ทุกตัวใช้ร่วมกัน"
               tagline-en="A second brain you can see, shared by every agent you use."
               lead="BrainX เปลี่ยนโน้ต Markdown ของคุณให้เป็นกราฟความรู้ 3 มิติ ค้นได้ด้วยความหมาย และเปิดเป็น MCP server ให้ Claude, Codex และ CluadeX ทำงานบนความรู้ก้อนเดียวกัน — โน้ตทั้งหมดยังอยู่บนเครื่องคุณ"
               lead-en="Your Markdown vault as a living 3D knowledge graph, searchable by meaning and served over MCP to every agent. Your notes never leave your disk unless you choose.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'BrainX ' . $latest->version : 'BrainX' }}</span>
            <span class="xp-chip">Windows 10/11 · 64-bit</span>
            <span class="xp-chip">BrainX Cloud · Remote MCP</span>
        </x-slot:chips>
        <x-slot:title>Brain<span class="xp-text-glow">X</span></x-slot:title>
        <x-slot:actions>
            <x-xp.download :href="route('brainx.download')" :coming-soon="$csBrainx" th="ดาวน์โหลดสำหรับ Windows" en="Download for Windows" />
            @if($onSale)
                @include('products.partials.brainx-buy-button', ['key' => $key, 'label' => $key ? 'ต่ออายุ +30 วัน — ฿' . $monthly : 'ซื้อ Pro + Cloud — ฿' . $monthly . '/เดือน'])
            @else
                <a href="#pricing" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">ดูแผน Pro + Cloud</a>
            @endif
        </x-slot:actions>
        <x-slot:notes>
            @if($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ตัวติดตั้ง .exe จาก xman4289.com โดยตรง</p>
            @endif
            @if($csBrainx && $existingUser && $key)
                <p class="mt-3 text-sm text-violet-200/90">ผู้ใช้เดิม: ดาวน์โหลดตัวติดตั้งได้ที่ <a href="{{ route('customer.licenses.show', $key) }}" class="font-semibold underline decoration-violet-300/50 hover:text-white">หน้า License ของคุณ</a></p>
            @endif
            @if($key)
                <div class="mt-6 max-w-md xp-card p-5" @if($keyLapsed) style="border-color: rgba(245, 158, 11, .5)" @endif>
                    <p class="text-sm text-gray-400 mb-1"><x-bi th="คีย์ของคุณ" en="Your key" /></p>
                    <p class="font-mono text-lg text-white tracking-wider">{{ $maskedKey }}</p>
                    <p class="text-sm mt-2 {{ $keyLapsed ? 'text-amber-300' : 'text-emerald-300' }}">
                        @if(! $key->expires_at)
                            <x-bi th="ยังไม่มีวันหมดอายุ" en="No expiry set" />
                        @elseif($keyLapsed)
                            <x-bi th="หมดอายุเมื่อ" en="Expired on" /> {{ $key->expires_at->timezone('Asia/Bangkok')->format('d/m/Y') }}
                            · <x-bi th="ต่ออายุแล้วได้ 30 วันนับจากวันที่ชำระ" en="renewing gives 30 days from the day you pay" />
                        @else
                            <x-bi th="ใช้ได้ถึง" en="Valid until" /> {{ $key->expires_at->timezone('Asia/Bangkok')->format('d/m/Y') }}
                            · <x-bi th="ต่ออายุแล้วบวกต่อจากวันนี้" en="renewing adds on from this date" />
                        @endif
                    </p>
                    <a href="{{ route('customer.licenses.show', $key) }}" class="xp-link inline-block mt-3 text-sm font-semibold">
                        <x-bi th="ดูคีย์เต็มและรายละเอียด" en="Full key and details" /> →
                    </a>
                </div>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="pro" /> ทดลอง Pro 7 วัน</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> คีย์เดียวได้ทั้ง Pro และ Cloud</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> โน้ตยังเป็น Markdown บนเครื่องคุณ</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('hud.webp')" :eager="true" :width="1120" :height="685"
                             alt="หน้าจอจริงของ BrainX: สถิติสมอง ความเชี่ยวชาญ Agent Bus และกราฟความรู้ 3 มิติ" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">MCP · ทุกเอเจนต์</p>
                <p class="text-white font-extrabold text-lg leading-tight">Claude · Codex · <span class="text-cyan-300">CluadeX</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">BrainX Cloud</p>
                <p class="text-white font-bold leading-tight">Claude ทุกเครื่อง<br><span class="text-gray-400 text-sm font-normal">ใช้สมองก้อนเดียวกัน</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ============================ THE BRAIN ============================ --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="The brain" title="ความรู้ที่คุณสะสมมา" title2="กลายเป็นสมองที่ใช้งานได้"
                       sub="โน้ตกระจายอยู่หลายที่ แชทกับ AI อีกนับไม่ถ้วน — BrainX รวมเป็นสมองก้อนเดียวที่มองเห็นได้ ค้นได้ด้วยความหมาย และเอเจนต์ทุกตัวเข้ามาใช้ได้"
                       en="Scattered notes and endless chats become one brain you can see, search by meaning, and share with every agent." />
            <x-xp.rows :items="$coreRows" />
        </div>
    </section>

    {{-- =========================== PRO TOOLS =========================== --}}
    <section class="relative py-20 isolate" id="pro">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="Pro · built on the brain" title="ให้เอเจนต์ทำงาน" title2="เป็นทีมเดียวกัน"
                       sub="Pro เพิ่มสิ่งที่สร้างบนสมอง: ห้องทำงานร่วมที่คุณสั่งเอเจนต์ทุกตัวได้ในที่เดียว ผู้ช่วยเสียง Mind และการสร้างภาพ/วิดีโอจากเอเจนต์ — ทดลองได้ 7 วัน"
                       en="Pro adds the cowork room and its broker, Mind the voice assistant, and image/video generation. Try it for 7 days." />
            <x-xp.explorer id="pro-tools" label="ฟีเจอร์ Pro ของ BrainX" :tools="$proTools" />
        </div>
    </section>

    {{-- ============================ CLOUD ============================ --}}
    <section class="relative py-20 scroll-mt-20" id="cloud">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-5">
                    <p class="xp-eyebrow mb-3">BrainX Cloud · Remote MCP</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-4 flex flex-wrap items-center gap-3">สมองบนคลาวด์ <x-xp.badge tier="pro" label="คีย์ที่ชำระแล้ว" /></h2>
                    <p class="text-lg text-gray-300 leading-relaxed mb-2">
                        ติ๊กโฟลเดอร์โน้ตที่จะอัปโหลดขึ้นพื้นที่ส่วนตัวของคุณ แล้ว Claude บนเครื่องไหนก็ได้ใช้ความรู้ก้อนเดียวกัน — ผ่าน Remote MCP โดยไม่ต้องติดตั้งอะไร หรือ brainx-mcp โหมดคลาวด์ที่เก็บโน้ตไว้ในเครื่องและทำงานต่อได้ตอนเน็ตหลุด
                    </p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-6">Tick the folders to upload, and any Claude on any machine can use them — over Remote MCP, or brainx-mcp in cloud mode that keeps working offline.</p>
                    <x-xp.ticks class="mb-8" :items="[
                        'พื้นที่ ' . $baseGb . ' GB ต่อคีย์ รวมรูปและไฟล์ที่โน้ตแนบไว้',
                        'สร้างโทเค็นแยกให้แต่ละเครื่อง อ่านอย่างเดียวหรืออ่าน-เขียน ยกเลิกทีละใบได้',
                        'ซิงก์เองหลังโน้ตเปลี่ยน และทุก ๆ ช่วงเวลาที่ตั้งไว้',
                        'ลงเครื่องใหม่ vault ว่าง — ดึงสมองทั้งก้อนกลับมาให้ก่อน',
                        'เครื่องของคุณยังเป็นต้นฉบับ คลาวด์เก็บเฉพาะโฟลเดอร์ที่ติ๊ก',
                    ]" />
                    <div class="xp-card p-5">
                        <p class="text-xs text-gray-400 mb-2"><x-bi th="ต่อ Claude Code ในคำสั่งเดียว" en="Connect Claude Code in one command" /></p>
                        <pre class="text-sm text-emerald-300 whitespace-pre-wrap break-all font-mono leading-relaxed">{{ $mcpCommand }}</pre>
                        <p class="text-xs text-gray-500 mt-3"><x-bi th="สร้างโทเค็น bxc_ ในแอป BrainX หลังล็อกอินด้วย License key" en="Create a bxc_ token in the BrainX app after signing in with your licence key" /></p>
                    </div>
                </div>
                <div class="lg:col-span-7">
                    <x-xp.screen :src="$shot('cloud.webp')" :mock="true"
                                 alt="ภาพตัวอย่างหน้า ตั้งค่า ▸ BrainX Cloud: พื้นที่ที่ใช้ โฟลเดอร์ที่อัปโหลด ซิงก์อัตโนมัติ และโทเค็นของแต่ละเครื่อง" />
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ TRUST ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Your notes stay yours" title="ความรู้ของคุณ" title2="อยู่ในมือคุณ"
                       sub="สมองที่ดีต้องไม่ขังความรู้ไว้ในรูปแบบที่คุณเอาออกมาไม่ได้"
                       en="A second brain must never lock your knowledge away." />
            <x-xp.cards :items="[
                ['icon' => 'doc', 'title' => 'ยังเป็น Markdown บนดิสก์', 'body' => 'โน้ตทุกไฟล์ยังเป็น .md ในโฟลเดอร์ของคุณ เปิดด้วยโปรแกรมอื่นได้ตามเดิม BrainX แค่อ่านและสร้างดัชนี', 'en' => 'Plain Markdown in your own folders'],
                ['icon' => 'cloud', 'title' => 'ขึ้นคลาวด์เฉพาะที่เลือก', 'body' => 'ข้อมูลออกจากเครื่องก็ต่อเมื่อคุณติ๊กโฟลเดอร์ใน BrainX Cloud เอง เอาติ๊กออกแล้วเลือกได้ว่าจะลบจากคลาวด์ด้วยไหม', 'en' => 'Only ticked folders leave the machine'],
                ['icon' => 'key', 'title' => 'ไม่เก็บคีย์ ไม่ส่งคีย์', 'body' => 'แอปเก็บแค่ 4 ตัวท้ายของคีย์ เครื่องอื่นใช้โทเค็นของตัวเองที่แสดงครั้งเดียวและยกเลิกได้ทีละใบ', 'en' => 'Per-machine tokens, never the key'],
                ['icon' => 'eye', 'title' => 'หมดอายุ โน้ตยังอ่านได้', 'body' => 'คีย์หมดอายุแล้วการเขียนและ MCP บนคลาวด์หยุดจนกว่าจะต่ออายุ แต่โน้ตของคุณยังอ่านและดึงกลับได้', 'en' => 'A lapsed key never locks your notes'],
                ['icon' => 'sparkles', 'title' => 'ตัวสวนดูแลสมอง', 'body' => 'ตอนเครื่องว่าง BrainX เติม embedding ที่ค้าง ตรวจลิงก์เสีย หาโน้ตที่ขัดแย้งกัน แล้วเขียนรายงานสุขภาพสมองไว้ให้', 'en' => 'An idle-time gardener keeps the brain healthy'],
                ['icon' => 'lock', 'title' => 'อัปเดตที่ตรวจลายเซ็น', 'body' => 'อัปเดตมาจาก xman4289.com ผ่าน feed ที่มีลายเซ็นดิจิทัล ตรวจก่อนติดตั้งทุกครั้ง', 'en' => 'Every update comes through a signed feed'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="คีย์เดียว" title2="ได้ทั้ง Pro และ Cloud"
                       :sub="$csBrainx ? 'BrainX ยังเป็น Coming Soon — ยังไม่เปิดให้ดาวน์โหลดและยังไม่เปิดขาย เปิดเมื่อไหร่จะแจ้งที่หน้านี้' : 'ไฟล์เดียวกันทุกแบบ · คีย์ที่ชำระแล้วปลด Pro บนเครื่องนี้และเข้า BrainX Cloud ได้จากทุกเครื่อง · ทดลอง Pro 7 วันก่อนตัดสินใจ (ครั้งเดียวต่อเครื่อง)'"
                       :en="$csBrainx ? 'BrainX is coming soon: not downloadable and not on sale yet.' : 'One download. A paid key unlocks Pro here and BrainX Cloud everywhere. Try Pro for 7 days first.'" />

            <div class="grid md:grid-cols-2 gap-5 max-w-4xl mx-auto">
                <div class="xp-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">แอป BrainX</h3>
                        @if($csBrainx)<x-xp.badge tier="soon" />@endif
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="สมองที่สองบนเครื่องคุณเอง" en="Your second brain, on your own machine" /></p>
                    <div class="mb-6"><span class="text-3xl font-black text-white">{{ $csBrainx ? 'เร็ว ๆ นี้' : 'ไม่ต้องมีคีย์' }}</span></div>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['กราฟความรู้ 3 มิติ + ค้นแบบ hybrid', 'MCP server ให้เอเจนต์ทุกตัว', 'Agent Bus · ส่งงานเป็นโน้ต', 'ตัวสวน + อัปเดตอัตโนมัติ']" />
                    {{-- ไฟล์เดียวกับ Pro — Pro ปลดล็อกในแอปด้วย license key --}}
                    <x-xp.download block :href="route('brainx.download')" :coming-soon="$csBrainx" th="ดาวน์โหลดสำหรับ Windows" en="Download for Windows" />
                </div>

                @foreach($plans as $term => $plan)
                    @continue(! isset($prices[$term]))
                    <div class="xp-featured">
                        <div class="p-7 flex flex-col h-full">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-2xl font-black text-white">Pro + Cloud {{ $plan['name'] }}</h3>
                                <x-xp.badge tier="pro" />
                            </div>
                            <p class="text-amber-100/80 text-sm mb-6">{{ $plan['note'] }}</p>
                            <div class="mb-6 flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white">฿{{ number_format($prices[$term]) }}</span>
                                <span class="text-gray-300">{{ $plan['unit'] }}</span>
                            </div>
                            <x-tpix-price variant="detail" :thb="$prices[$term]" class="-mt-4 mb-6" />
                            <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ทุกอย่างในแอป BrainX', 'ห้องทำงานร่วม · Mind · สร้างภาพ/วิดีโอ', 'BrainX Cloud: Remote MCP + พื้นที่ ' . $baseGb . ' GB', 'อายุ License: ' . $plan['days']]" />
                            @if($onSale)
                                {{-- ราคามาจาก config/licenses.php ไม่ใช่จากฟอร์ม — ตะกร้าคิดตามแผนที่ส่งไป --}}
                                @include('products.partials.brainx-buy-button', ['key' => $key, 'term' => $term, 'block' => true, 'label' => ($key ? 'ต่ออายุ ' . $plan['name'] : 'ซื้อ Pro + Cloud ' . $plan['name']) . ' — ฿' . number_format($prices[$term])])
                            @else
                                <span class="xp-btn-pro xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-center text-xs text-gray-500 mt-6">
                ซื้อแล้วได้ License key ทางอีเมลและในหน้า บัญชีของฉัน · ใส่คีย์ในโปรแกรม ตั้งค่า ▸ BrainX Pro / BrainX Cloud · Pro ใช้ได้ 1 เครื่องต่อคีย์ ย้ายเครื่องได้ · ยืนยันกับเซิร์ฟเวอร์อย่างน้อยทุก 30 วัน · ต่ออายุแล้วคีย์เดิมถูกยืดออกไป บัญชีคลาวด์ไม่ต้องทำใหม่
            </p>
            @if($key)
                <p class="text-center mt-4">
                    <a href="{{ route('customer.licenses.show', $key) }}" class="xp-link text-sm font-semibold">ดูคีย์ของคุณ →</a>
                </p>
            @endif

            <x-xp.compare class="mt-14" :groups="$compare" free-label="BrainX" pro-label="PRO + Cloud"
                          note="ทดลอง 7 วันปลดฟีเจอร์ Pro ทั้งหมดบนเครื่องนั้น แต่ BrainX Cloud ใช้ได้เฉพาะคีย์ที่ชำระแล้ว · ค้นด้วยความหมายต้องมี Ollama บนเครื่อง" />
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows 10 หรือ 11 แบบ 64-bit', 'Windows 10/11, 64-bit'],
            ['ไม่ต้องติดตั้ง .NET', 'ทุกอย่างมากับตัวติดตั้งแล้ว', 'Self-contained installer'],
            ['อินเทอร์เน็ต', 'ต่อเน็ตตอนเปิดครั้งแรกเพื่อลงทะเบียนเครื่อง หลังจากนั้นใช้ออฟไลน์ได้', 'Online once to register this PC'],
            ['เอเจนต์ที่ใช้ MCP', 'Claude Desktop, Claude Code, Codex หรือ CluadeX — BrainX ตั้งค่าให้ Claude เอง', 'Any MCP client'],
        ]"
        specs-note="ค้นด้วยความหมายใช้โมเดล embedding ผ่าน Ollama บนเครื่องของคุณ (ติดตั้งแยก) — ไม่มี Ollama ก็ยังค้นด้วยคำได้ตามปกติ"
        :steps="[
            [$csBrainx ? 'ดาวน์โหลดตัวติดตั้งเมื่อเปิดให้ใช้' : 'ดาวน์โหลดตัวติดตั้ง', 'ไฟล์ .exe จาก xman4289.com โดยตรง ใช้ไฟล์เดียวกันทั้งแอปและ Pro', 'Download the installer'],
            ['เปิด BrainX แล้วเลือกโฟลเดอร์โน้ต', 'ใช้ Markdown หรือ Obsidian vault ที่มีอยู่ได้เลย BrainX ลงทะเบียนเครื่องและต่อ MCP ให้ Claude เอง', 'Open BrainX and point it at your notes'],
            ['ลอง Pro 7 วัน แล้วใส่คีย์เมื่อพร้อม', 'เปิดครั้งแรกตอนต่อเน็ตได้ทดลอง Pro เอง ซื้อคีย์แล้วใส่ใน ตั้งค่า ▸ BrainX Pro และล็อกอิน BrainX Cloud ด้วยคีย์เดียวกัน', 'Try Pro, then add your key'],
        ]" />

    <x-xp.faq :items="array_values(array_filter([
        ['BrainX ต่างจากแอปจดโน้ตทั่วไปอย่างไร?', 'BrainX ไม่ได้แทนที่วิธีเขียนของคุณ โน้ตยังเป็น Markdown ในโฟลเดอร์เดิม แต่ทำให้มองเห็นเป็นกราฟ ค้นได้ด้วยความหมาย และเปิดให้เอเจนต์ AI ที่คุณใช้อ่าน เขียน และจำสิ่งที่เรียนรู้ลงสมองก้อนเดียวกันผ่าน MCP'],
        [$csBrainx ? 'ตอนนี้ดาวน์โหลดได้หรือยัง?' : 'ดาวน์โหลดได้ที่ไหน?', $csBrainx
            ? 'ยังไม่ได้ BrainX ยังเป็น Coming Soon สำหรับผู้ใช้ใหม่ ผู้ที่มีคีย์ BrainX อยู่แล้วเข้าสู่ระบบแล้วดาวน์โหลดได้จากหน้า License ของตัวเอง และแอปที่ติดตั้งไว้แล้วยังอัปเดตต่อได้ตามปกติ'
            : 'กดปุ่มดาวน์โหลดในหน้านี้ ได้ตัวติดตั้ง .exe จาก xman4289.com โดยตรง ติดตั้งแล้วอัปเดตตัวเองได้'],
        ['คีย์เดียวได้อะไรบ้าง?', 'คีย์ที่ชำระแล้วปลดฟีเจอร์ Pro (ห้องทำงานร่วม Mind สร้างภาพ/วิดีโอ) บนเครื่องที่ใส่คีย์ 1 เครื่อง ย้ายไปเครื่องใหม่ได้ และใช้ล็อกอิน BrainX Cloud ได้ ส่วนเครื่องอื่นต่อคลาวด์ด้วยโทเค็นที่สร้างจากแอป'],
        ['ทดลอง Pro ทำอย่างไร ได้ Cloud ด้วยไหม?', 'เปิด BrainX ครั้งแรกตอนต่อเน็ตจะได้ทดลอง Pro 7 วัน ครั้งเดียวต่อเครื่อง (ลง Windows ใหม่ก็ไม่ได้สิทธิ์ซ้ำ) ระหว่างทดลองใช้ฟีเจอร์ Pro ได้ครบ แต่ BrainX Cloud ใช้ได้เฉพาะคีย์ที่ชำระแล้ว'],
        ['ต่ออายุแล้วได้คีย์ใหม่ไหม?', 'ไม่ได้ และไม่ต้อง คีย์เดิมของคุณถูกยืดออกไป 30 วันต่อหนึ่งเดือนที่ซื้อ นับจากวันหมดอายุเดิม หรือนับจากวันที่ชำระถ้าหมดอายุไปแล้ว บัญชีคลาวด์และการตั้งค่าอยู่ครบ'],
        ['ซื้อหลายเดือนทีเดียวได้ไหม?', 'ได้ ปรับจำนวนในตะกร้า จำนวนคือจำนวนเดือนที่ต่อให้คีย์เดียวกัน'],
        ['ถ้าหมดอายุ โน้ตบนคลาวด์เป็นอย่างไร?', 'การอัปโหลดและการใช้ผ่าน MCP บนคลาวด์จะหยุดจนกว่าจะต่ออายุ แต่โน้ตของคุณยังอ่านและดาวน์โหลดกลับได้ ส่วนแอปบนเครื่องยังทำงานต่อ'],
        $packs ? ['พื้นที่ ' . $baseGb . ' GB ไม่พอทำอย่างไร?', 'ซื้อพื้นที่เพิ่มให้คีย์ได้ จ่ายครั้งเดียว พื้นที่อยู่กับคีย์ตลอดรวมถึงตอนต่ออายุ: ' . collect($packs)->map(fn ($price, $gb) => '+' . $gb . ' GB ฿' . number_format($price))->implode(' · ') . ($packsOnSale ? ' — ซื้อได้จากหน้า License ของคีย์นั้น' : ' — เปิดขายพร้อม BrainX')] : null,
        ['ใช้บนมือถือได้ไหม?', 'แอป GigGok บนมือถือที่ผูกกับบัญชีเดียวกันใช้สมองบน BrainX Cloud ก้อนเดียวกันได้ ไม่ต้องจ่ายเพิ่ม ต้องมีคีย์ BrainX ที่ชำระแล้วและยังไม่หมดอายุ'],
        ['ชำระเงินช่องทางไหนได้บ้าง?', 'ทุกช่องทางที่หน้าชำระเงินของ XMAN Studio รองรับ ต้องเข้าสู่ระบบตอนชำระ คีย์จะผูกกับบัญชีของคุณ'],
    ]))" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" :title="$key ? 'ต่ออายุ' : 'ให้ทุกเอเจนต์ใช้'" :glow="$key ? 'BrainX Cloud' : 'สมองเดียวกัน'"
              :sub="$csBrainx ? 'BrainX กำลังจะเปิดให้ใช้ — เปิดเมื่อไหร่จะแจ้งที่หน้านี้' : ($key ? 'คีย์เดิม เพิ่ม 30 วัน ฿' . $monthly : 'Pro + BrainX Cloud ฿' . $monthly . ' ต่อเดือน · ทดลอง Pro 7 วันก่อนได้')"
              :en="$key ? 'Renew BrainX Cloud — the same key, 30 more days.' : 'Give every agent you use the same brain.'">
        <x-xp.download :href="route('brainx.download')" :coming-soon="$csBrainx" th="ดาวน์โหลดสำหรับ Windows" en="Download for Windows" class="!px-8" />
        <a href="#pricing" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">{{ $key ? 'ต่ออายุ' : 'ดูแผน Pro + Cloud' }}</a>
        @if($key)
            <a href="{{ route('customer.licenses.show', $key) }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดูคีย์ License ของฉัน</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10/11 (64-bit) · ไม่ต้องลง .NET · ต้องเข้าสู่ระบบตอนชำระเงิน คีย์ผูกกับบัญชีของคุณ</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
