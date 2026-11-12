@extends($publicLayout ?? 'layouts.app')
{{-- หน้าสินค้า CluadeX (/products/cluadex-ai-coding-assistant) — ชื่อไฟล์เก่า แต่เป็นหน้า CluadeX (ProductController::$customViews)
     แอปส่งลูกค้ามาหน้านี้เพื่อซื้อคีย์ (XmanLicenseService.DefaultPurchaseUrl) · หน้า /cluadex เป็นหน้า landing แยกอีกหน้า --}}
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย และซื้อไม่ได้ (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('cluadex-ai-coding-assistant'); @endphp

@section('title', 'CluadeX — ผู้ช่วยเขียนโค้ด AI บน Windows · มีรุ่นฟรี · Pro ' . collect(\App\Support\LicensePlans::for('cluadex-ai-coding-assistant'))->map(fn ($p, $t) => '฿' . number_format($p) . ($t === 'yearly' ? '/ปี' : ($t === 'monthly' ? '/เดือน' : ' ตลอดชีพ')))->implode(' หรือ ') . ' | XMAN Studio')
@section('meta_description', 'CluadeX ผู้ช่วยเขียนโค้ด AI บน Windows ที่อ่าน แก้ไฟล์ รันคำสั่ง และใช้ Git ในโปรเจกต์ของคุณ ใช้โมเดล GGUF ในเครื่องหรือ Ollama ได้ฟรี · Pro ต่อ Claude, GPT, Gemini, Grok ด้วย API key ของคุณ พร้อม GitHub, Web fetch และ Dev Preview · ถามก่อนลงมือทุกครั้ง · An AI coding agent for Windows.')
@section('og_image', asset('images/products/cluadex/v2/og.jpg'))

@section('content')
@php
    // ราคาทุกแผนมาจาก config/licenses.php 'plans' ที่เดียว (ตะกร้าคิดตามนี้) — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    // CluadeX ขายรายเดือน รายปี และตลอดชีพ (เจ้าของ 2026-11-12) — ถ้า config เพิ่ม/ลดแผน การ์ดตามเอง
    $prices = \App\Support\LicensePlans::for('cluadex-ai-coding-assistant');
    // ตัวเลขดอลลาร์ปัดเศษที่เจ้าของตั้งคู่กับราคา (config/licenses.php usd_hints) — แสดงอย่างเดียว ไม่ได้คิดเงินเป็นดอลลาร์
    $usdHints = config('licenses.usd_hints.cluadex-ai-coding-assistant', []);
    $firstTerm = array_key_first($prices);

    // ปุ่มซื้อต้องมีเสมอ — แอปส่งลูกค้ามาหน้านี้เพื่อซื้อ และ 1 คีย์ใช้ได้ 1 เครื่อง คนที่ซื้อแล้วก็ซื้อให้เครื่องอื่นได้
    $ownsCluadex = auth()->check() && $hasPurchased;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว — เปิดหน้านี้ต้องไม่ถามแหล่งไฟล์ภายนอก)
    $latest = $product->latestVersion();
    // ไฟล์ที่ส่งให้ขึ้นกับ asset_pattern ในหน้า admin (ตัวติดตั้ง Setup.exe หรือ Portable .zip) — อ่านจากชื่อไฟล์จริง
    $isZip = str_ends_with(strtolower((string) $latest?->download_filename), '.zip');

    // ภาพหน้าจอ: ภาพจริงที่แอปเรนเดอร์เองด้วยข้อมูลตัวอย่าง (tests/themetest ของ CluadeX) — เบลอป้ายเวอร์ชันทดสอบและพาธแล้ว
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py cluadex
    $shot = fn (string $file) => asset('images/products/cluadex/v2/' . $file);
    // ชุด v3 (2026-11-12): เรนเดอร์จาก CluadeX 3.0.319 ตัวจริงด้วย tests/themetest — ข้อมูลในภาพเป็นตัวอย่าง ไม่มีของลูกค้า
    $shot3 = fn (string $file) => asset('images/products/cluadex/v3/' . $file);

    // คลิปโฆษณา v2b (9:16, 3:12) อยู่ใน storage ของเซิร์ฟเวอร์ — ไฟล์ใหญ่เกินจะเก็บใน git (แบบเดียวกับคลิป WinXTools)
    $clip = [
        'hd' => asset('storage/videos/cluadex/cluadex-ad-1080p.mp4'),
        'sd' => asset('storage/videos/cluadex/cluadex-ad-720p.mp4'),
        'poster' => $shot3('clip/poster.webp'),
    ];
    // วินาทีที่แต่ละช่วงในคลิปเริ่ม (ตาราง shot ใน xmanstudio-campaign-media/cluadex-ad/README.md) — ภาพย่อคือภาพนิ่งจากคลิป
    // ไม่มีช่วง 2:02 "เครือข่ายสมองทั่วโลก": แอปยังไม่มีฟีเจอร์นั้น หน้านี้จึงไม่พาไปที่ช่วงนั้น
    $chapters = [
        [53, '01', 'ประหยัด token ตั้งแต่ราก', 'Prompt cache'],
        [64, '02', 'งานง่ายโมเดลเล็ก งานยากตัวท็อป', 'Routing · Batch'],
        [73, '03', 'Skill · Auto · Effort', 'Skills'],
        [86, '04', 'เห็นโค้ดถูกเขียนสด ๆ', 'Live edits'],
        [92, '05', 'เอเจนต์หลายตัวทำพร้อมกัน', 'Agent map'],
        [100, '06', 'วางแผน → ตรวจจริง → สรุป', 'Plan · verify'],
        [109, '07', 'คำสั่งเสี่ยงต้องอนุมัติ', 'Approvals'],
        [117, '08', 'สมอง BrainX จำทุกบทเรียน', 'BrainX'],
        [132, '10', 'สลับผู้ให้บริการคลิกเดียว', 'Providers'],
        [138, '11', 'Local AI · DGX Spark', 'Local AI'],
        [145, '12', 'สาว ๆ อ่านคำตอบให้ฟัง', 'Voice'],
        [150, '13', 'ตั้งเวลางาน · Telegram', 'Routines'],
    ];

    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'Monthly', 'unit' => '/ เดือน', 'note' => 'ซื้อทีละ 30 วัน ไม่ตัดเงินอัตโนมัติ', 'days' => '30 วัน'],
        'yearly' => ['name' => 'รายปี', 'en' => 'Yearly', 'unit' => '/ ปี', 'note' => 'จ่ายครั้งเดียวใช้ทั้งปี ไม่ตัดเงินอัตโนมัติ', 'days' => '365 วัน'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'Lifetime', 'unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ'],
    ];
    $fromLabel = $firstTerm ? '฿' . number_format($prices[$firstTerm]) . ($plans[$firstTerm]['unit'] === 'ครั้งเดียว' ? '' : str_replace(' ', '', $plans[$firstTerm]['unit'])) : null;

    // License ที่ใกล้หมด/หมดแล้ว (เดิมเป็นแถบบนหน้า) — รายปีต้องต่ออายุ
    $expiringLicense = null;
    if (isset($userLicense) && $userLicense && $userLicense->license_type !== 'lifetime') {
        if ($userLicense->isValid() && $userLicense->daysRemaining() <= 7) {
            $expiringLicense = ['เหลืออีก ' . $userLicense->daysRemaining() . ' วัน (' . $userLicense->expires_at->format('d/m/Y') . ')', $userLicense];
        } elseif ($userLicense->isExpired()) {
            $expiringLicense = ['หมดอายุเมื่อ ' . $userLicense->expires_at->format('d/m/Y'), $userLicense];
        }
    }

    // ใช้ได้ในรุ่นฟรี — ข้อเท็จจริงจากโค้ดแอป (ActivationService.IsFeatureUnlocked, FeaturesViewModel.GatedFeatures)
    $freeRows = [
        [
            'img' => $shot('editor.webp'), 'name' => 'อีดิเตอร์โค้ด มี AI อยู่ข้าง ๆ', 'eyebrow' => 'Code editor + agent', 'tier' => 'free',
            'th' => 'เปิดโฟลเดอร์โปรเจกต์ แล้วสั่งงานเป็นภาษาไทยหรืออังกฤษ เอเจนต์อ่านโค้ด แก้ไฟล์ รันคำสั่ง และใช้ Git ในโปรเจกต์นั้นให้ ติ๊ก "Follow edits" แล้วเห็นทุกบรรทัดที่ AI แก้ในอีดิเตอร์ทันที',
            'en' => 'Open a project folder and ask in Thai or English: the agent reads, edits, runs commands and uses Git there. Follow its edits live in the editor.',
            'points' => ['อีดิเตอร์ในตัว: ค้นหา/แทนที่ minimap เทอร์มินัล และไฟล์ใหญ่ก็เปิดได้', 'Git ในเครื่องครบ: commit, branch, merge, diff, stash — สั่งจากแชทได้'],
            'alt' => 'หน้าอีดิเตอร์โค้ดของ CluadeX มีรายการไฟล์ทางซ้าย โค้ดตรงกลาง และแชทกับเอเจนต์ทางขวา',
        ],
        [
            'img' => $shot('chat.webp'), 'name' => 'ถามก่อนลงมือ', 'eyebrow' => 'You stay in charge', 'tier' => 'free',
            'th' => 'เอเจนต์ถามเมื่อไม่แน่ใจ และขออนุญาตก่อนรันคำสั่งหรือแก้อะไรในเครื่อง เลือกได้ 3 ระดับ: ถามก่อนทุกครั้ง · แก้ไฟล์ในโปรเจกต์ได้เลย · อนุญาตทั้งหมดในแชทนี้',
            'en' => 'The agent asks when unsure and asks before it changes anything: ask first, accept edits in the project, or allow all for this chat.',
            'points' => ['คำสั่งที่ลบไดรฟ์ โฟลเดอร์ home หรือโฟลเดอร์ระบบ ขึ้นกล่องแดงทุกครั้ง ไม่มีกฎหรือโหมดไหนกดผ่านแทนคุณได้', 'บล็อกคำสั่งอันตรายและระบบสิทธิ์ ใช้ได้ในรุ่นฟรีเสมอ'],
            'alt' => 'หน้าแชทของ CluadeX: เอเจนต์ถามว่าจะแก้ไฟล์ไหน ขออนุญาตรันคำสั่ง และกล่องเตือนอันตรายสีแดงก่อนลบโฟลเดอร์',
        ],
        [
            'img' => $shot('models.webp'), 'name' => 'โมเดลในเครื่อง ฟรีและเป็นส่วนตัว', 'eyebrow' => 'Local models', 'tier' => 'free',
            'th' => 'Model Manager ดูการ์ดจอและ RAM ของเครื่อง แล้วแนะนำโมเดล GGUF ที่เหมาะ บอกว่าแต่ละตัวรันได้เร็วแค่ไหน แล้วดาวน์โหลดจาก Hugging Face ให้ หรือใช้โมเดลผ่าน Ollama ก็ได้ — รันในเครื่อง โค้ดไม่ออกนอกเครื่อง',
            'en' => 'Picks GGUF models that fit your GPU and RAM, estimates their speed and downloads them from Hugging Face — or use Ollama. Nothing leaves your PC.',
            'points' => ['ใช้การ์ดจอ NVIDIA (CUDA 12) ถ้ามี ไม่มีก็รันด้วย CPU ได้', 'โหลดไฟล์ GGUF ที่มีอยู่แล้วเข้ามาเองได้', 'ต่อเซิร์ฟเวอร์ในเครื่องได้ด้วย: llama.cpp server · LM Studio · vLLM', 'รองรับ NVIDIA DGX Spark: เลือก ดาวน์โหลด และใช้โมเดลบน Ollama ของ Spark ได้จากหน้าต่างในแอป'],
            'alt' => 'หน้า Model Manager ของ CluadeX แสดงสเปกเครื่อง และโมเดลที่แนะนำพร้อมความเร็วโดยประมาณ',
        ],
        [
            'img' => $shot3('skills.webp'), 'name' => 'Skill เยอะ แต่เลือกให้เองตามงาน', 'eyebrow' => 'Skills · Auto pick · Effort', 'tier' => 'free',
            'th' => 'Skill คือคำสั่งลัดแบบ /ชื่อ มีในตัวพร้อมใช้ กด “Get more skills” ดาวน์โหลดเพิ่มจาก GitHub (เช่นคลัง anthropics/skills) หรือเขียนเองก็ได้ — เปิด “เลือก skill ให้แต่ละงาน” แล้วแต่ละรอบจะได้เฉพาะ skill ที่เข้ากับงานนั้น ไม่เปลือง token ไปกับรายการทั้งหมด',
            'en' => 'Built-in skills, more from GitHub (e.g. anthropics/skills) or your own — and Auto pick hands each turn only the skills that fit.',
            'points' => ['ตั้งระดับความพยายาม (Effort) เองได้ตั้งแต่ low ถึง max หรือปล่อยเป็น Auto', 'ต่อเซิร์ฟเวอร์ MCP ติดตั้งปลั๊กอิน และตั้งฮุกได้จากในแอป', 'สแกนความปลอดภัยโค้ดแบบ OWASP ในตัว'],
            'alt' => 'หน้า Skills ของ CluadeX: รายการ skill สวิตช์เลือก skill ให้แต่ละงาน และแถบ token ที่ skill ใช้',
        ],
    ];

    // ทำงานเป็นทีม เห็นทุกขั้น (ฟรี) — คลิปช่วง 1:26–1:49 · เช็กกับโค้ดแอปแล้ว (AgentGraphView, plan card, auto-build, change marks)
    $teamRows = [
        [
            'img' => $shot3('agents.webp'), 'name' => 'เอเจนต์ผู้เชี่ยวชาญ ทำงานพร้อมกัน', 'eyebrow' => 'Plan · agents in parallel', 'tier' => 'free',
            'th' => 'งานใหญ่ถูกวางแผนเป็นเช็กลิสต์ก่อนลงมือ แล้วแบ่งให้เอเจนต์ย่อยเฉพาะทาง (สำรวจโค้ด เขียนหน้าเว็บ เขียนเทสต์ ตรวจความปลอดภัย) ทำพร้อมกัน เห็นเป็นแผนผังโหนดสด ๆ กดที่โหนดไหนก็เปิดดูได้ว่าตัวนั้นกำลังทำอะไร',
            'en' => 'A plan first, then specialist agents working in parallel on a live node map — click a node to see what it is doing.',
            'points' => ['เอเจนต์ย่อยแต่ละตัวใช้โมเดลที่เหมาะกับงาน — อ่าน/ค้นหาใช้รุ่นเล็ก งานยากใช้รุ่นท็อป', 'ลาก ซูม และจัดแผนผังเองได้'],
            'alt' => 'แชทของ CluadeX: แผนงาน 5 ขั้นกำลังทำขั้นที่ 3 และแผนผังเอเจนต์ 4 ตัวที่ทำงานพร้อมกัน (ข้อมูลตัวอย่าง)',
        ],
        [
            'img' => $shot3('live-code.webp'), 'name' => 'เห็นโค้ดถูกเขียนสด ๆ', 'eyebrow' => 'Live edits', 'tier' => 'free',
            'th' => 'ติ๊ก Follow edits แล้วอีดิเตอร์เปิดไฟล์ที่ AI กำลังแก้ให้เอง บรรทัดที่เพิ่มหรือแก้มีแถบสีกำกับที่ขอบ เห็นทุกบรรทัดทุกไฟล์ตอนที่มันเกิดขึ้น ไม่ต้องรอดู diff ตอนจบ',
            'en' => 'Follow edits opens each file as the AI changes it, with every added or changed line marked.',
            'points' => [],
            'alt' => 'อีดิเตอร์ของ CluadeX: บรรทัดที่ AI เพิ่มและแก้มีแถบสีที่ขอบซ้าย',
        ],
    ];

    // ประหยัด token และเงิน — ใช้กับ Claude / OpenAI API จึงเป็น Pro · คลิปช่วง 0:53–1:13
    $smartRows = [
        [
            'img' => $shot3('prompt-cache.webp'), 'name' => 'ประหยัด token ตั้งแต่ราก', 'eyebrow' => 'Prompt cache · tools on demand', 'tier' => 'pro',
            'th' => 'ส่วนที่ส่งซ้ำทุกรอบ (คำสั่งระบบและรายการเครื่องมือ) ถูกแคชไว้ที่ Claude API ส่วนที่อ่านจากแคชคิดราคาราว 10% ของปกติ — และเครื่องมือ MCP ไม่ถูกยัดใส่ทุกรอบ โหลดมาเฉพาะตัวที่ต้องใช้จริง',
            'en' => 'Repeated parts come from the Claude prompt cache (about 90% cheaper to read), and MCP tools load only when they are needed.',
            'points' => ['สวิตช์ แคชพรอมต์ อยู่ในหน้า การเชื่อมต่อ AI'],
            'alt' => 'หน้าการเชื่อมต่อ AI ของ CluadeX: เลือก Anthropic (Claude API) และติ๊กแคชพรอมต์ (Prompt caching)',
            'phone' => true,
        ],
        [
            'img' => $shot3('agent-settings.webp'), 'name' => 'งานง่ายใช้โมเดลเล็ก งานยากใช้ตัวท็อป', 'eyebrow' => 'Model routing · Batch', 'tier' => 'pro',
            'th' => 'เอเจนต์ย่อยเลือกโมเดลเองตามงาน ถ้าผลงานอ่อนหรือหาไม่เจอ จะส่งขึ้นไปทำใหม่ในระดับที่เก่งกว่าเอง — ส่วนงานที่ไม่รีบ ส่งผ่าน Batch API ได้ในราคาครึ่งเดียว แล้วคำตอบกลับเข้าแชทเดิมเมื่อเสร็จ',
            'en' => 'Sub-agents pick the model for the job and escalate weak work to a stronger tier; non-urgent work goes through the Batch API at half price.',
            'points' => ['Batch ใช้ได้กับ Anthropic และ OpenAI — ส่วนลด 50% ตามราคาของผู้ให้บริการ', 'build อัตโนมัติหลังแก้ไข แล้วส่ง error กลับให้ AI แก้ต่อ ไม่มีคำว่า “เสร็จ” ลอย ๆ'],
            'alt' => 'หน้าตั้งค่าเอเจนต์ของ CluadeX: เลือกโมเดลเอเจนต์ย่อยอัตโนมัติ ผลงานอ่อนให้ขึ้นไปทำใหม่ระดับบน และ build อัตโนมัติหลังแก้ไข',
        ],
    ];

    // ยิ่งใช้ยิ่งเก่ง และทำงานแม้คุณไม่อยู่ (ฟรี — ไม่มีด่าน IsFeatureUnlocked) · คลิปช่วง 1:57 และ 2:25–2:30
    $moreRows = [
        [
            'img' => $shot3('brainx-memory.webp'), 'name' => 'สมอง BrainX จำทุกบทเรียน', 'eyebrow' => 'BrainX memory · MCP', 'tier' => 'free',
            'th' => 'ต่อ BrainX ผ่าน MCP แล้วก่อนตอบ CluadeX จะค้นสิ่งที่เคยจดไว้ — วิธีแก้บั๊ก การตัดสินใจ ข้อควรระวังของแต่ละโปรเจกต์ — และเก็บบทเรียนจากแชทที่จบแล้วไว้เอง ยิ่งใช้ยิ่งรู้จักงานของคุณ',
            'en' => 'Connect BrainX over MCP: CluadeX recalls past fixes and decisions before it answers, and keeps the lessons of each finished chat.',
            'points' => ['ค้นหาเชิงความหมายด้วยโมเดล embedding ใน Ollama บนเครื่องคุณ', 'ใช้สมองเดียวกันได้ทุกเครื่องด้วย BrainX Cloud (แยกขาย)'],
            'alt' => 'หน้าตั้งค่าความจำของ CluadeX: ดึงความรู้จาก BrainX ก่อนตอบอัตโนมัติ และเรียนรู้จากเซสชันที่จบแล้ว',
        ],
        [
            'img' => $shot3('voice.webp'), 'name' => 'สาว ๆ ในแอปอ่านคำตอบให้ฟัง', 'eyebrow' => 'Read-aloud voices', 'tier' => 'free',
            'th' => 'ผู้ช่วยแต่ละคนอ่านคำตอบออกเสียงด้วยน้ำเสียงของตัวเอง เลือกเอนจินได้ 5 แบบ: เสียง Edge (ฟรี ไม่ต้องใช้คีย์ มีเสียงไทยธรรมชาติ) · เสียงของเบราว์เซอร์ (ฟรี) · เสียง Windows (ฟรี ใช้ออฟไลน์ได้) · ElevenLabs และ OpenAI (ใช้คีย์ของคุณ)',
            'en' => 'Each assistant reads her answers aloud: Edge voices (free, natural Thai), browser and Windows voices (free), or ElevenLabs and OpenAI with your key.',
            'points' => [],
            'alt' => 'หน้าตั้งค่าเสียงของ CluadeX: เลือกเอนจินเสียงและเสียงของผู้ช่วยแต่ละคน',
            'phone' => true,
        ],
        [
            'img' => $shot3('routines.webp'), 'name' => 'ตั้งเวลาให้ทำงานเอง แจ้งเตือนทาง Telegram', 'eyebrow' => 'Routines · Telegram', 'tier' => 'free',
            'th' => 'ตั้งงานประจำให้รันเองทุกวัน ทุกกี่ชั่วโมง ครั้งเดียว หรือเมื่อเกิดเหตุการณ์ (CI ล้ม มีคอมเมนต์ใน pull request มี issue ใหม่ ไฟล์เปลี่ยน) แล้วผูก Telegram bot ของคุณ — งานจบส่งสรุปมาให้ งานที่ต้องอนุมัติมีปุ่ม Allow / Deny ในโทรศัพท์',
            'en' => 'Routines run on a schedule or on events (CI failed, PR comment, new issue, file changed); your Telegram bot gets the summary and Allow / Deny for approvals.',
            'points' => ['สั่งงานจาก Telegram ได้ด้วย: ข้อความที่พิมพ์เข้าแชท CluadeX และคำตอบส่งกลับมา', 'จับคู่ bot กับแชทส่วนตัวของคุณด้วยรหัส 6 หลัก คนอื่นที่ทักมาไม่มีผล'],
            'alt' => 'หน้า Routines ของ CluadeX: รายการงานตั้งเวลา และการเชื่อม Telegram (ข้อมูลตัวอย่าง)',
        ],
    ];

    // Pro — ทุกข้อเช็กกับด่าน IsFeatureUnlocked / IsActivated ในโค้ดแอป
    $proTools = [
        [
            'key' => 'cloud', 'name' => 'Claude · GPT · Gemini · Grok', 'short' => 'ใช้ API key ของคุณเอง', 'tier' => 'pro',
            'img' => $shot('features.webp'),
            'th' => 'ต่อโมเดลบนคลาวด์ของ Anthropic, OpenAI, Google Gemini และ xAI Grok ด้วย API key ของคุณเอง รวมถึง OpenRouter, DeepSeek, Mistral, Groq และผู้ให้บริการที่เข้ากันได้กับ OpenAI อีกหลายเจ้า ค่าใช้งานโมเดลจ่ายกับผู้ให้บริการโดยตรง CluadeX ไม่ได้รวมค่า AI ไว้ในคีย์',
            'en' => 'Anthropic, OpenAI, Google Gemini, xAI Grok and many OpenAI-compatible services with your own API key. Model usage is billed by the provider.',
            'points' => ['สลับผู้ให้บริการได้ในคลิกเดียวจากช่องโมเดลในแชท — แม้งานกำลังทำอยู่ ก็หยุดแล้วทำต่อด้วยโมเดลใหม่ทันที', 'เห็นค่าใช้จ่ายต่อขั้นก่อนส่ง ตั้งงบต่อรอบและต่อวันได้ เกินงบแล้วถามก่อนทำต่อ', 'API key เก็บเข้ารหัสด้วย Windows (DPAPI) เฉพาะผู้ใช้ Windows ของคุณ'],
            'alt' => 'หน้า Features ของ CluadeX: ฟีเจอร์ฟรีเปิดอยู่ และ Multi-Provider AI ที่ต้องใช้คีย์',
        ],
        [
            'key' => 'cli', 'name' => 'ต่อ CLI ที่ล็อกอินไว้', 'short' => 'Claude Code · Codex · Gemini · Grok', 'tier' => 'pro',
            'th' => 'มี Claude Code, Codex, Gemini CLI, Antigravity หรือ Grok Build ติดตั้งและล็อกอินไว้แล้ว? เลือกเป็นโมเดลของแชทได้เลย ไม่ต้องใส่ API key ใช้สิทธิ์จากบัญชีที่คุณล็อกอินอยู่',
            'en' => 'Already signed in to Claude Code, Codex, Gemini CLI, Antigravity or Grok Build? Pick it as the chat model — no API key needed.',
            'points' => [],
        ],
        [
            'key' => 'github', 'name' => 'GitHub ให้เอเจนต์', 'short' => 'push · pull request · issues', 'tier' => 'pro',
            'th' => 'เอเจนต์ push โค้ดขึ้น GitHub เปิด pull request สร้าง issue ค้นหาและ clone repo ผ่าน GitHub CLI ได้จากแชท ส่วน Git ในเครื่อง (commit, branch, merge) ใช้ได้ในรุ่นฟรี',
            'en' => 'Push, open pull requests, create issues and search or clone repositories through the GitHub CLI. Local Git stays free.',
            'points' => [],
        ],
        [
            'key' => 'web', 'name' => 'Web fetch', 'short' => 'อ่านหน้าเว็บและ API', 'tier' => 'pro',
            'th' => 'เอเจนต์ดึงหน้าเว็บหรือ API มาอ่านเป็นข้อความ เช่น เอกสารของไลบรารี หรือผลตอบกลับจาก API ที่กำลังเขียน',
            'en' => 'Fetches web pages and APIs as text — library docs, or the API you are building.',
            'points' => [],
        ],
        [
            'key' => 'preview', 'name' => 'Dev Preview', 'short' => 'รันเว็บ แล้วเปิดดูเอง', 'tier' => 'pro',
            'th' => 'เอเจนต์รันเซิร์ฟเวอร์ dev เบื้องหลัง แล้วเปิดหน้าเว็บในเบราว์เซอร์ในตัวของ CluadeX อ่าน console ถ่ายภาพหน้าจอ คลิก และกดแป้นเพื่อทดสอบงานที่เพิ่งแก้',
            'en' => 'Runs your dev server in the background, opens the page in a built-in browser, reads the console, takes screenshots, clicks and types.',
            'points' => [],
        ],
    ];

    // ตารางเทียบ — ตรงกับ ActivationService.IsFeatureUnlocked และด่านเลือกผู้ให้บริการใน ChatViewModel
    $compare = [
        ['โมเดล AI · Models', [
            ['โมเดล GGUF ในเครื่อง (GPU / CPU) + ดาวน์โหลดจาก Hugging Face', false],
            ['Ollama · llama.cpp server · LM Studio · vLLM', false],
            ['NVIDIA DGX Spark (โมเดลบน Ollama ของ Spark)', false],
            ['Anthropic Claude · OpenAI · Google Gemini · xAI Grok (API key ของคุณ)', true],
            ['OpenRouter · DeepSeek · Mistral · Groq และบริการที่เข้ากันได้กับ OpenAI', true],
            ['Claude Code · Codex · Gemini CLI · Antigravity · Grok Build ที่ล็อกอินไว้', true],
            ['แคชพรอมต์ · Batch API ครึ่งราคา (Claude / OpenAI)', true],
        ]],
        ['เอเจนต์และเครื่องมือ · Agent & tools', [
            ['อ่าน/แก้ไฟล์ · รันคำสั่ง · อีดิเตอร์โค้ด', false],
            ['Git ในเครื่อง (commit, branch, merge, diff, stash)', false],
            ['Skill · Subagent · MCP · ปลั๊กอิน · ฮุก', false],
            ['แผนงาน · แผนผังเอเจนต์ · build อัตโนมัติหลังแก้ไข', false],
            ['สมอง BrainX ผ่าน MCP', false],
            ['อ่านคำตอบออกเสียง · ตั้งเวลางาน (Routines) · Telegram', false],
            ['บันทึกแชท · ย้อนเวลาโปรเจกต์ (Time Machine)', false],
            ['ระบบสิทธิ์ · บล็อกคำสั่งอันตราย', false],
            ['GitHub: push · pull request · issues', true],
            ['Web fetch', true],
            ['Dev Preview (รันเว็บ + เบราว์เซอร์ในตัว)', true],
        ]],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'CluadeX',
        'operatingSystem' => 'Windows 10/11 (64-bit)',
        'applicationCategory' => 'DeveloperApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'ผู้ช่วยเขียนโค้ด AI บน Windows ที่อ่าน แก้ไฟล์ รันคำสั่ง และใช้ Git ในโปรเจกต์ของคุณ ใช้โมเดลในเครื่องได้ฟรี หรือต่อ Claude, GPT, Gemini ด้วยรุ่น Pro',
        'image' => asset('images/products/cluadex/v2/og.jpg'),
        'url' => route('products.show', 'cluadex-ai-coding-assistant'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => array_values(array_merge(
            [['@type' => 'Offer', 'name' => 'Free', 'price' => '0', 'priceCurrency' => 'THB']],
            collect($prices)->map(fn ($price, $term) => ['@type' => 'Offer', 'name' => 'Pro (' . ($plans[$term]['en'] ?? $term) . ')', 'price' => (string) $price, 'priceCurrency' => 'THB'])->values()->all(),
        )),
    ];
    if ($latest) {
        $ld['softwareVersion'] = $latest->version;
    }
@endphp
<x-xp.page a="#c4b5fd" b="#818cf8" c="#67e8f9" glow="129 140 248" g1="#f472b6" g2="#a78bfa" g3="#67e8f9">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="ผู้ช่วยเขียนโค้ด AI ที่ลงมือทำในโปรเจกต์ของคุณ"
               tagline-en="An AI coding agent for Windows that works inside your project."
               lead="สั่งงานเป็นภาษาไทยหรืออังกฤษ เอเจนต์อ่านโค้ด แก้ไฟล์ รันคำสั่ง และใช้ Git ให้ — ใช้โมเดลในเครื่องได้ฟรี หรืออัป Pro เพื่อต่อ Claude, GPT และ Gemini ด้วย API key ของคุณเอง"
               lead-en="Ask in Thai or English: it reads your code, edits files, runs commands and uses Git. Free with local models; Pro adds Claude, GPT and Gemini.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'CluadeX ' . $latest->version : 'CluadeX' }}</span>
            <span class="xp-chip">Windows 10/11 · 64-bit</span>
        </x-slot:chips>
        <x-slot:title>Cluade<span class="xp-text-glow">X</span></x-slot:title>
        <x-slot:actions>
            <x-xp.download :href="route('cluadex.download')" :coming-soon="$csDownload" />
            @if($fromLabel)
                <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">
                    {{ $ownsCluadex ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} — เริ่ม {{ $fromLabel }}
                </a>
            @endif
        </x-slot:actions>
        <x-slot:notes>
            @if($latest && ! $csDownload)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · {{ $isZip ? 'ไฟล์ .zip' : 'ตัวติดตั้ง' }} จาก xman4289.com โดยตรง</p>
            @endif
            @if($expiringLicense)
                <p class="mt-3 text-sm text-amber-200">
                    License ของคุณ{{ $expiringLicense[0] }} ·
                    <a href="{{ route('customer.licenses.show', $expiringLicense[1]) }}" class="font-semibold underline hover:text-white">ต่ออายุ License</a>
                </p>
            @elseif($ownsCluadex)
                <p class="mt-3 text-sm text-emerald-200/90">
                    คุณมี License แล้ว · 1 คีย์ใช้ได้ 1 เครื่อง — ซื้อเพิ่มได้สำหรับเครื่องอื่น ·
                    <a href="{{ route('customer.licenses') }}" class="font-semibold underline decoration-emerald-300/50 hover:text-white">ดูคีย์ License ของฉัน</a>
                </p>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="free" /> โมเดลในเครื่อง ใช้ได้ตลอด</li>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="pro" /> ทดลองฟรี 24 ชม.</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่ต้องลง .NET</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('editor.webp')" :eager="true"
                             alt="หน้าอีดิเตอร์โค้ดของ CluadeX: รายการไฟล์ โค้ดที่กำลังแก้ และแชทกับเอเจนต์ที่วางแผนงานให้" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ค่าเริ่มต้น · Ask first</p>
                <p class="text-white font-extrabold text-lg leading-tight">ถาม<span class="text-violet-300">ก่อนทุกครั้ง</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">Follow edits</p>
                <p class="text-white font-bold leading-tight">เห็นทุกบรรทัดที่ AI แก้<br><span class="text-gray-400 text-sm font-normal">ในอีดิเตอร์ทันที</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ============================ CLIP ============================ --}}
    {{-- คลิปโฆษณา v2b แนวตั้ง เสียงพากย์ไทย + ซับไทยฝังในคลิป · preload=none: 68 MB ไม่โหลดจนกว่าจะกดดู · จอเล็กได้ไฟล์ 720p --}}
    <style>
        .cx-clip { position: relative; aspect-ratio: 9 / 16; width: 100%; max-width: 22rem; margin: 0 auto; border-radius: 1.75rem; padding: 1px;
            background: linear-gradient(160deg, rgb(var(--xp-glow) / .7), rgba(255, 255, 255, .08) 50%, color-mix(in srgb, var(--xp-a) 50%, transparent));
            box-shadow: 0 30px 80px -20px rgb(var(--xp-glow) / .45); }
        .cx-clip > div { position: relative; width: 100%; height: 100%; border-radius: calc(1.75rem - 1px); overflow: hidden; background: #000; }
        .cx-clip video { width: 100%; height: 100%; object-fit: cover; display: block; background: #000; }
        .cx-play { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: .75rem; padding-bottom: 4.5rem;
            border: 0; cursor: pointer; color: #fff; background: linear-gradient(180deg, rgba(4, 6, 13, 0) 40%, rgba(4, 6, 13, .85) 100%); }
        .cx-play[hidden] { display: none; }
        .cx-play .ring { width: 5rem; height: 5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(140deg, var(--xp-a), var(--xp-b)); box-shadow: 0 0 0 10px rgb(var(--xp-glow) / .18); transition: transform .2s; }
        .cx-play:hover .ring, .cx-play:focus-visible .ring { transform: scale(1.08); }
        .cx-chapters { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; }
        @media (min-width: 640px) { .cx-chapters { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .cx-chap { display: flex; align-items: center; gap: .7rem; text-align: left; padding: .45rem; border-radius: .9rem; cursor: pointer;
            background: rgba(255, 255, 255, .03); border: 1px solid rgba(255, 255, 255, .07); color: #d7dce5; transition: background .15s, border-color .15s; }
        .cx-chap:hover, .cx-chap:focus-visible { background: rgba(255, 255, 255, .07); border-color: rgb(var(--xp-glow) / .5); }
        .cx-chap[aria-current="true"] { border-color: var(--xp-a); background: rgb(var(--xp-glow) / .12); }
        .cx-chap img { width: 2.6rem; height: 3.65rem; object-fit: cover; border-radius: .5rem; flex: none; }
        .cx-chap b { display: block; font-size: .8rem; line-height: 1.25; color: #fff; }
        .cx-chap small { display: block; font-size: .7rem; color: var(--xp-c); font-variant-numeric: tabular-nums; }
    </style>
    <section class="relative py-20 scroll-mt-20" id="clip">
        <div class="xp-glow w-[30rem] h-[30rem] left-0 top-10" style="background: rgb(var(--xp-glow) / .14)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Watch · 3 นาที" title="ดู CluadeX" title2="ทำงานจริง"
                       sub="คลิปสั้นพาดูทุกอย่างที่อยู่ในหน้านี้ — กดหัวข้อด้านข้างเพื่อกระโดดไปช่วงที่สนใจ (เสียงพากย์และซับไตเติลภาษาไทย)"
                       en="A three-minute tour of everything on this page. Pick a chapter to jump to it (Thai voice-over and subtitles)." />
            <div class="grid lg:grid-cols-12 gap-10 lg:gap-14 items-center">
                <div class="lg:col-span-5">
                    <div class="cx-clip">
                        <div>
                            <video id="cx-clip-video" controls playsinline preload="none" poster="{{ $clip['poster'] }}" aria-label="คลิปแนะนำ CluadeX">
                                <source src="{{ $clip['sd'] }}" type="video/mp4" media="(max-width: 767px)">
                                <source src="{{ $clip['hd'] }}" type="video/mp4">
                            </video>
                            <button type="button" class="cx-play" id="cx-clip-play" aria-label="เล่นคลิปแนะนำ CluadeX">
                                <span class="ring"><svg class="w-8 h-8 ml-1 text-gray-950" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></span>
                                <span class="font-bold text-sm tracking-wide">เล่นคลิป · 3:12</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-7">
                    <p class="xp-eyebrow mb-4">ไปที่ช่วง · Chapters</p>
                    <div class="cx-chapters" id="cx-clip-chapters">
                        @foreach($chapters as [$sec, $still, $label, $labelEn])
                            <button type="button" class="cx-chap" data-seek="{{ $sec }}">
                                <img src="{{ $shot3('clip/' . $still . '.webp') }}" alt="" width="240" height="338" loading="lazy">
                                <span><small>{{ intdiv($sec, 60) }}:{{ str_pad($sec % 60, 2, '0', STR_PAD_LEFT) }} · {{ $labelEn }}</small><b>{{ $label }}</b></span>
                            </button>
                        @endforeach
                    </div>
                    <p class="mt-5 text-xs text-gray-500">ตัวเลขค่า token ค่าใช้จ่าย และหน้าจอในคลิปเป็นภาพประกอบ · ภาพหน้าจอในหน้านี้เป็นภาพจากโปรแกรมจริง (ข้อมูลตัวอย่าง)</p>
                </div>
            </div>
        </div>
    </section>
    <script>
        (() => {
            const video = document.getElementById('cx-clip-video');
            const play = document.getElementById('cx-clip-play');
            const chaps = document.querySelectorAll('#cx-clip-chapters [data-seek]');
            if (!video || !play) return;
            const start = (at) => {
                play.hidden = true;
                if (at !== undefined) {
                    // preload=none: the time can only be set once the file's metadata is in
                    if (video.readyState >= 1) video.currentTime = at;
                    else video.addEventListener('loadedmetadata', () => { video.currentTime = at; }, { once: true });
                }
                video.play().catch(() => {});
            };
            play.addEventListener('click', () => start());
            video.addEventListener('play', () => { play.hidden = true; });
            chaps.forEach((b) => b.addEventListener('click', () => {
                start(Number(b.dataset.seek));
                if (window.innerWidth < 1024) video.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }));
            // the chapter being watched is outlined
            video.addEventListener('timeupdate', () => {
                let current = null;
                chaps.forEach((b) => { if (video.currentTime >= Number(b.dataset.seek)) current = b; });
                chaps.forEach((b) => b.setAttribute('aria-current', b === current ? 'true' : 'false'));
            });
        })();
    </script>

    {{-- ========================== FREE ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Free · works forever" title="เอเจนต์เขียนโค้ด" title2="ที่รันบนเครื่องคุณ"
                       sub="รุ่นฟรีใช้โมเดลในเครื่องหรือ Ollama ได้ไม่จำกัดเวลา พร้อมอีดิเตอร์ Git ในเครื่อง Skill MCP ปลั๊กอิน และระบบสิทธิ์ครบ"
                       en="The free edition runs on local models or Ollama, with the editor, local Git, skills, MCP, plugins and permissions — no time limit." />
            <x-xp.rows :items="$freeRows" />
        </div>
    </section>

    {{-- ========================== TEAM ========================== --}}
    <section class="relative py-20">
        <div class="xp-glow w-[34rem] h-[34rem] -left-40 top-20" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Plan · work · verify" title="ทำงานเป็นทีม" title2="เห็นทุกขั้น ไม่มีเสร็จปลอม"
                       sub="วางแผนก่อน แบ่งงานให้เอเจนต์เฉพาะทาง ตรวจด้วย build และเทสต์จริง แล้วสรุปว่าทำอะไรไป ยังขาดอะไร — ทั้งหมดนี้อยู่ในรุ่นฟรี"
                       en="A plan first, specialist agents in parallel, real builds and tests, then a summary of what is done and what is not — all in the free edition." />
            <x-xp.rows :items="$teamRows" />
        </div>
    </section>

    {{-- ========================== SMART ========================== --}}
    <section class="relative py-20">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Spend less per task" title="คุ้มทุก token" title2="ที่จ่ายให้โมเดลบนคลาวด์"
                       sub="ต่อ Claude หรือ OpenAI ด้วย Pro แล้ว CluadeX ช่วยลดค่าใช้จ่ายที่ต้นทาง: แคช เลือกโมเดลตามงาน และส่งงานไม่รีบเป็น Batch"
                       en="With Claude or OpenAI on Pro, CluadeX cuts the bill at the source: caching, the right model per job, and Batch for work that can wait." />
            <x-xp.rows :items="$smartRows" />
        </div>
    </section>

    {{-- =========================== PRO =========================== --}}
    <section class="relative py-20 isolate">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 left-[-30%] lg:left-[-14%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="right" class="mb-10" eyebrow="Pro · cloud models & more" title="ต่อโมเดลระดับท็อป" title2="แล้วปล่อยให้ทำงานจริง"
                       sub="Pro ปลดผู้ให้บริการ AI บนคลาวด์และ CLI (Claude · Codex · Gemini · Grok และอีกหลายเจ้า) สลับได้ในคลิกเดียว รวมถึง GitHub, Web fetch และ Dev Preview"
                       en="Pro unlocks the cloud and CLI providers — switch in one click — plus GitHub, web fetch and Dev Preview." />
            <x-xp.explorer id="pro" label="ฟีเจอร์ Pro ของ CluadeX" :tools="$proTools" />
        </div>
    </section>

    {{-- ========================== MORE ========================== --}}
    <section class="relative py-20">
        <div class="xp-glow w-[34rem] h-[34rem] -left-40 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Memory · voice · routines" title="ยิ่งใช้ยิ่งเก่ง" title2="และทำงานแม้คุณไม่อยู่"
                       sub="จำบทเรียนจากงานที่ผ่านมา อ่านคำตอบให้ฟัง และรันงานตามเวลาพร้อมส่งเรื่องที่ต้องตัดสินใจไปที่มือถือคุณ"
                       en="It remembers what past work taught it, reads answers aloud, and runs on a schedule — sending the decisions to your phone." />
            <x-xp.rows :items="$moreRows" />
        </div>
    </section>

    {{-- ============================ SAFETY ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Your code, your machine" title="ให้ AI แก้โค้ด" title2="โดยไม่เสียการควบคุม"
                       sub="เอเจนต์ที่แก้ไฟล์และรันคำสั่งได้ ต้องหยุดได้ ย้อนได้ และไม่ทำอะไรเกินที่คุณอนุญาต"
                       en="An agent that edits files and runs commands must be stoppable, reversible and never go past what you allowed." />
            <x-xp.cards :items="[
                ['icon' => 'user', 'title' => 'ถามก่อนทุกครั้ง', 'body' => 'ค่าเริ่มต้นคือทุกอย่างที่เปลี่ยนแปลงเครื่องต้องถามก่อน ผ่อนเป็น “แก้ไฟล์ในโปรเจกต์ได้เลย” ได้ โดยคำสั่งและไฟล์นอกโปรเจกต์ยังถาม', 'en' => 'Everything that changes your PC asks first'],
                ['icon' => 'bug', 'title' => 'กล่องแดงก่อนลบของสำคัญ', 'body' => 'คำสั่งที่ลบไดรฟ์ โฟลเดอร์ home หรือโฟลเดอร์ระบบ ต้องให้คุณกดเองทุกครั้ง แม้เปิดอนุญาตทั้งหมดไว้ก็ตาม และคำสั่งอันตรายที่รู้จักถูกบล็อกให้', 'en' => 'Deleting a drive or system folder always needs your click'],
                ['icon' => 'shield', 'title' => 'งานเสี่ยงทำในพื้นที่แยก', 'body' => 'งานระดับความพยายามสูง เอเจนต์ย่อยแต่ละตัวทำใน git worktree ของตัวเอง ไม่แตะโค้ดที่คุณเปิดอยู่ ตรวจก่อนแล้วค่อยรวมกลับ', 'en' => 'Risky work runs in a separate git worktree'],
                ['icon' => 'undo', 'title' => 'ย้อนเวลาได้', 'body' => 'Time Machine พาโปรเจกต์กลับไปที่ commit ไหนก็ได้ และเก็บงานที่ยังไม่ commit ไว้ให้ก่อนย้อน (auto-stash)', 'en' => 'Rewind to any commit, with auto-stash'],
                ['icon' => 'lock', 'title' => 'คีย์เข้ารหัสในเครื่อง', 'body' => 'API key ที่บันทึกไว้เข้ารหัสด้วย Windows (DPAPI) เปิดได้เฉพาะบัญชี Windows ของคุณบนเครื่องนี้', 'en' => 'Saved API keys are encrypted by Windows'],
                ['icon' => 'chip', 'title' => 'โมเดลในเครื่อง = โค้ดอยู่ในเครื่อง', 'body' => 'ใช้โมเดล GGUF หรือ Ollama โค้ดไม่ถูกส่งออกไปไหน ถ้าเลือกโมเดลบนคลาวด์ ข้อความจะส่งไปที่ผู้ให้บริการที่คุณเลือกเท่านั้น', 'en' => 'Local models keep your code on your PC'],
                ['icon' => 'chart', 'title' => 'คุมงบโมเดลบนคลาวด์', 'body' => 'เห็นค่าใช้จ่ายโดยประมาณต่อขั้นก่อนส่ง ตั้งงบต่อรอบและต่อวัน ถึงงบแล้วถามก่อนทำต่อ', 'en' => 'Per-run and daily spend limits'],
                ['icon' => 'bolt', 'title' => 'ไม่มีคำว่า “เสร็จ” ลอย ๆ', 'body' => 'หลังแก้ไฟล์ CluadeX build โปรเจกต์เอง error ที่เจอส่งกลับให้ AI แก้ต่อ และรายงานตอนจบบอกตรง ๆ ว่าอะไรยังไม่ได้ตรวจ', 'en' => 'Builds after edits and says what was not verified'],
            ]" :cols="4" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="เริ่มฟรี" title2="อยากต่อโมเดลบนคลาวด์ค่อยอัป Pro"
                       sub="ไฟล์เดียวกันทั้งรุ่นฟรีและ Pro · 1 คีย์ใช้ได้ 1 เครื่อง · เปิดครั้งแรกบนเครื่องใหม่ได้ทดลอง Pro ฟรี 24 ชั่วโมง"
                       en="One download. One key per PC. A new PC gets a free 24-hour Pro trial on first launch." />

            @if(session('success'))
                <p class="max-w-3xl mx-auto mb-8 xp-card px-5 py-3 text-center text-emerald-200">{{ session('success') }}</p>
            @endif

            <div class="grid gap-5 mx-auto {{ count($prices) >= 3 ? 'md:grid-cols-2 xl:grid-cols-4 max-w-6xl' : 'md:grid-cols-3 max-w-5xl' }}">
                <div class="xp-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">Free</h3>
                        <x-xp.badge tier="free" />
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="โมเดลในเครื่อง ไม่จำกัดเวลา" en="Local models, no time limit" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['โมเดล GGUF ในเครื่อง + Ollama', 'เอเจนต์แก้ไฟล์ รันคำสั่ง อีดิเตอร์', 'Git ในเครื่อง · Skill · MCP · ปลั๊กอิน', 'ระบบสิทธิ์ + บล็อกคำสั่งอันตราย', 'อัปเดตอัตโนมัติ']" />
                    {{-- ไฟล์เดียวกับ Pro — Pro ปลดล็อกในแอปด้วย license key --}}
                    <x-xp.download block :href="route('cluadex.download')" :coming-soon="$csDownload" />
                </div>

                @foreach($plans as $term => $plan)
                    @continue(! isset($prices[$term]))
                    @php($featured = $term === 'yearly')
                    <div class="{{ $featured ? 'xp-featured' : 'xp-card' }}">
                        <div class="p-7 flex flex-col h-full">
                            @if($featured)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2"><x-xp.badge tier="pro" label="แนะนำ" class="shadow-lg" /></div>
                            @endif
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-2xl font-black text-white">Pro {{ $plan['name'] }}</h3>
                                <x-xp.badge tier="pro" />
                            </div>
                            <p class="text-amber-100/80 text-sm mb-6">{{ $plan['note'] }}</p>
                            <div class="mb-6 flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white">฿{{ number_format($prices[$term]) }}</span>
                                <span class="text-gray-300">{{ $plan['unit'] }}</span>
                            </div>
                            @if(isset($usdHints[$term]))
                                <p class="-mt-4 mb-5 text-sm font-semibold text-emerald-300">≈ ${{ $usdHints[$term] }}{{ str_replace(' ', '', $plan['unit']) }} (ค่ากาแฟ)</p>
                            @elseif($term === 'yearly' && ($save = \App\Support\LicensePlans::yearlySaving('cluadex-ai-coding-assistant')))
                                <p class="-mt-4 mb-5 text-sm font-semibold text-emerald-300">เฉลี่ย ฿{{ number_format(round($prices[$term] / 12)) }}/เดือน · ถูกกว่ารายเดือน {{ $save }}%</p>
                            @endif
                            <x-tpix-price variant="detail" :thb="$prices[$term]" class="-mt-3 mb-6" />
                            <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ทุกอย่างในรุ่นฟรี', 'Claude · GPT · Gemini · Grok (API key ของคุณ)', 'Claude Code · Codex · Gemini CLI', 'แคชพรอมต์ · Batch ครึ่งราคา', 'GitHub · Web fetch · Dev Preview', 'อายุ License: ' . $plan['days']]" />
                            @if($csDownload)
                                {{-- Coming Soon: ซื้อไม่ได้ (ComingSoonPurchase) — ไม่มีฟอร์มให้กด --}}
                                <span class="xp-btn-ghost xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                            @else
                                {{-- ราคามาจาก config/licenses.php ไม่ใช่จากฟอร์ม — ตะกร้าคิดตามแผนที่ส่งไป --}}
                                <form action="{{ route('cart.add', $product) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="license_type" value="{{ $term }}">
                                    <input type="hidden" name="buy_now" value="1">
                                    <button type="submit" class="{{ $featured ? 'xp-btn-pro' : 'xp-btn-ghost' }} block w-full py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                        {{ $ownsCluadex ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} {{ $plan['name'] }} — ฿{{ number_format($prices[$term]) }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($ownsCluadex)
                <p class="text-center mt-6">
                    <a href="{{ route('customer.licenses') }}" class="xp-link text-sm font-semibold">มี License แล้ว? ดูคีย์ของคุณ →</a>
                </p>
            @endif
            <p class="text-center text-xs text-gray-500 mt-6">ซื้อแล้วได้ License key ทางอีเมลและในหน้า บัญชีของฉัน · ใส่คีย์ในโปรแกรมได้เลย ไม่ต้องลงใหม่ · ย้ายคีย์ไปเครื่องใหม่ได้ (เครื่องเดิมเลิกใช้คีย์นั้น) · คีย์ที่ยืนยันแล้วใช้ต่อได้ 30 วันแม้ติดต่อเซิร์ฟเวอร์ไม่ได้ · รองรับ PromptPay โอนธนาคาร Stripe และ Wallet</p>

            <x-xp.compare class="mt-14" :groups="$compare" note="ค่าใช้งานโมเดลบนคลาวด์จ่ายกับผู้ให้บริการโดยตรงด้วย API key ของคุณ ไม่รวมในคีย์ CluadeX · หมดอายุหรือหมดทดลองแล้ว ทุกอย่างในรุ่นฟรียังใช้ได้ตามปกติ" />
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows 10 หรือ 11 แบบ 64-bit', 'Windows 10/11, x64'],
            ['ไม่ต้องติดตั้ง .NET', 'ทุกอย่างที่โปรแกรมต้องใช้มากับไฟล์ที่ดาวน์โหลด', 'Self-contained'],
            ['การ์ดจอ (ไม่บังคับ)', 'NVIDIA ที่รองรับ CUDA 12 ช่วยให้โมเดลในเครื่องเร็วขึ้น ไม่มีก็รันด้วย CPU ได้', 'NVIDIA CUDA 12 optional'],
            ['อินเทอร์เน็ต', 'ใช้ตอนดาวน์โหลดโมเดล ใช้โมเดลบนคลาวด์ ทดลองและใส่คีย์ — โมเดลในเครื่องที่โหลดแล้วไม่ต้องใช้เน็ต', 'Online for downloads, cloud models and keys'],
        ]"
        specs-note="ขนาดโมเดลที่รันได้ขึ้นกับ VRAM และ RAM ของเครื่อง — Model Manager ในโปรแกรมคำนวณให้ว่าโมเดลไหนพอดีกับเครื่องคุณ"
        :steps="[
            ['ดาวน์โหลดจาก xman4289.com', 'ไฟล์เดียวกันทั้งรุ่นฟรีและ Pro', 'Download from xman4289.com'],
            [$isZip ? 'แตกไฟล์แล้วเปิด CluadeX.exe' : 'เปิดตัวติดตั้ง', 'ครั้งแรกที่เปิดบนเครื่องใหม่ (ต่อเน็ตอยู่) ได้ทดลอง Pro ฟรี 24 ชั่วโมงอัตโนมัติ', $isZip ? 'Extract and run CluadeX.exe' : 'Run the installer'],
            ['เลือกโมเดล แล้วเปิดโฟลเดอร์โปรเจกต์', 'โหลดโมเดลที่แนะนำในหน้า โมเดล หรือใส่ API key ของผู้ให้บริการ แล้วเริ่มสั่งงานในแชท', 'Pick a model, open your project'],
        ]" />

    <x-xp.faq :items="[
        ['ฟรีจริงไหม?', 'ฟรีจริง ใช้โมเดล GGUF ในเครื่องหรือ Ollama กับเอเจนต์ อีดิเตอร์ Git ในเครื่อง Skill MCP และปลั๊กอินได้ไม่จำกัดเวลา ดาวน์โหลดได้เลยโดยไม่ต้องล็อกอิน'],
        ['Pro ได้อะไรเพิ่ม?', 'ต่อโมเดลบนคลาวด์ (Anthropic Claude, OpenAI, Google Gemini, xAI Grok และบริการที่เข้ากันได้กับ OpenAI) ด้วย API key ของคุณ หรือใช้ Claude Code, Codex, Gemini CLI, Antigravity, Grok Build ที่ล็อกอินไว้ บวกแคชพรอมต์ งาน Batch ครึ่งราคา GitHub (push, pull request, issues), Web fetch และ Dev Preview'],
        ['ราคาเท่าไร ใช้ได้กี่เครื่อง?', collect($prices)->map(fn ($p, $t) => ($plans[$t]['name'] ?? $t) . ' ฿' . number_format($p))->implode(' · ') . ' — 1 คีย์ใช้ได้ 1 เครื่อง มีหลายเครื่องซื้อคีย์เพิ่มได้ และย้ายคีย์ไปเครื่องใหม่ได้'],
        ['ค่า Claude / GPT รวมในคีย์ไหม?', 'ไม่รวม คีย์ CluadeX ปลดให้ต่อผู้ให้บริการได้ ส่วนค่าใช้งานโมเดลจ่ายกับผู้ให้บริการโดยตรงด้วย API key ของคุณ (หรือสิทธิ์ของบัญชีที่ล็อกอิน CLI ไว้) ตั้งงบต่อรอบและต่อวันในโปรแกรมได้'],
        ['ทดลอง Pro ทำอย่างไร?', 'เปิดโปรแกรมครั้งแรกบนเครื่องใหม่ตอนต่อเน็ต จะได้คีย์ทดลอง Pro 24 ชั่วโมงอัตโนมัติ หมดแล้วทุกอย่างในรุ่นฟรียังใช้ต่อได้ตามปกติ'],
        ['AI จะแก้หรือลบไฟล์โดยไม่ถามไหม?', 'ค่าเริ่มต้นคือถามก่อนทุกครั้ง คุณเลือกผ่อนเป็นแก้ไฟล์ในโปรเจกต์ได้เลยหรืออนุญาตทั้งหมดในแชทนั้นได้ แต่คำสั่งที่ลบไดรฟ์ โฟลเดอร์ home หรือโฟลเดอร์ระบบ ต้องให้คุณกดยืนยันเองทุกครั้ง และย้อนโปรเจกต์ด้วย Time Machine ได้'],
        ['โค้ดของฉันถูกส่งไปไหนบ้าง?', 'ใช้โมเดลในเครื่อง โค้ดอยู่ในเครื่องคุณ ถ้าเลือกโมเดลบนคลาวด์ ข้อความจะส่งไปที่ผู้ให้บริการนั้นเท่านั้น โปรแกรมติดต่อ xman4289.com เพื่อตรวจคีย์และเช็กอัปเดต'],
        ['ไม่มีการ์ดจอใช้ได้ไหม?', 'ได้ โมเดลในเครื่องรันด้วย CPU ได้ (ช้ากว่า) หรือใช้โมเดลบนคลาวด์ด้วยรุ่น Pro — Model Manager บอกให้ว่าโมเดลไหนเหมาะกับเครื่องคุณและเร็วประมาณเท่าไร'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="ให้ AI ช่วยเขียน" glow="โดยคุณคุมเอง"
              :sub="'ดาวน์โหลดรุ่นฟรีได้ทันที' . ($fromLabel ? ' หรือต่อ Claude, GPT และ Gemini ด้วย Pro เริ่ม ' . $fromLabel : '')"
              en="An AI that writes the code, while you stay in charge.">
        <x-xp.download :href="route('cluadex.download')" :coming-soon="$csDownload" class="!px-8" />
        <a href="#pricing" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูแผน Pro</a>
        @if($ownsCluadex)
            <a href="{{ route('customer.licenses') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดูคีย์ License ของฉัน</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10/11 (64-bit) · ไม่ต้องลง .NET · ทดลอง Pro ฟรี 24 ชม. · 1 คีย์ = 1 เครื่อง</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
