/* XMAN Studio product hub — the catalogue.
 *
 * The one place the hub reads products from: the spotlight, the 3D scene, the
 * cards, the release log and Nova's lines all come from here. A new product
 * is one entry in PRODUCTS (+ its key art in assets/art/<id>.webp).
 *
 * Facts are the live shop's, not invented: status/price from the `products`
 * table on xman4289.com, versions and dates from `product_versions`
 * (checked 2026-10-05). Prices follow the shop page, not the pricing API
 * (that one returns 399/2500/5000 defaults for products without their own).
 * Features, platforms, free/paid and "เร็ว ๆ นี้" follow each product's page
 * in the Laravel app (resources/views/products/*, checked 2026-10-29): when
 * a page changes what it claims, change the entry here with it.
 *
 * The shop also answers live (GET /api/v1/catalog, matched on each product's
 * `shop` slug): its status wins over the one written here, and a real picture
 * set in its admin goes first in `shots`. See "live shop data" below.
 *
 * Images referenced from here carry IMG_V: bump it when you replace a file
 * under the same name (assets are cached `immutable` behind Cloudflare).
 */
(function () {
  'use strict';

  var IMG_V = '202610290900';
  var SHOP = 'https://xman4289.com';
  var art = function (id) { return '/assets/art/' + id + '.webp?v=' + IMG_V; };
  var img = function (path) { return path + '?v=' + IMG_V; };

  /* A product page's screens on the shop (public_html/images/products/<folder>/v2, listed in
     scripts/product-mockups/<folder>/manifest.json). `shot` = a capture of the app ("src" in the
     manifest); `mock` = a mock-up drawn to show the layout ("html"). The page labels every mock-up
     MOCKUP and never LIVE CAPTURE — ask isMock(url). */
  var MOCKS = {};
  var shot = function (folder, names) {
    return names.map(function (n) { return img(SHOP + '/images/products/' + folder + '/v2/' + n + '.webp'); });
  };
  var mock = function (folder, names) {
    return shot(folder, names).map(function (u) { MOCKS[u] = true; return u; });
  };

  var CATS = [
    { id: 'ai', dir: 'ai/', label: 'AI & Automation', th: 'AI และระบบอัตโนมัติ' },
    { id: 'creative', dir: 'creative/', label: 'Creative', th: 'งานสร้างสรรค์' },
    { id: 'cloud', dir: 'gpu-cloud/', label: 'GPU & Cloud', th: 'การ์ดจอและคลาวด์' },
    { id: 'system', dir: 'system/', label: 'System & Security', th: 'ระบบและความปลอดภัย' },
    { id: 'network', dir: 'network/', label: 'Network', th: 'เครือข่าย' },
    { id: 'mobile', dir: 'mobile/', label: 'Mobile', th: 'แอปมือถือ' },
    { id: 'commerce', dir: 'trade/', label: 'Trade & Commerce', th: 'เทรดและค้าขาย' },
  ];

  /* palette: [glow, deep, accent] — the 3D scene bakes the planet from it and
     the page tints the spotlight, card glow and timeline with it.
     planet: 0 banded giant · 1 continents · 2 crystal faults · 3 circuit world
     shop: the product's slug in the shop (xman4289.com/products/<shop>), the key
     the live catalogue is matched on. null = the shop does not list it. BrainX is
     production's `brainx-cloud` row: Coming Soon there = no download anywhere here,
     free or not, until the owner changes it (2026-10-12). */
  var PRODUCTS = [
    {
      id: 'brainx', name: 'BrainX', sub: 'NEURAL KNOWLEDGE ENGINE', cat: 'ai',
      status: 'soon', free: false, price: '[[BrainX Cloud {from}]]',
      plansKey: 'brainx', plans: { monthly: 399 }, addon: { shop: 'brainx-cloud', soon: true },
      platforms: ['Windows', 'Cloud'], tags: ['3D knowledge graph', 'Semantic search', 'MCP hub', 'Cloud sync'],
      palette: ['#8b5cf6', '#1a1240', '#67e8f9'], planet: 2,
      art: art('brainx'),
      shots: shot('brainx', ['hud', 'hud-wide']).concat(mock('brainx', ['search', 'agents', 'cowork', 'cloud'])),
      tagline: 'สมองที่สองที่มองเห็นได้ ให้ AI ทุกตัวใช้ความรู้ของคุณ',
      pitch: ['กราฟความรู้ 3 มิติ ค้นด้วยความหมาย', 'และเป็นศูนย์กลาง MCP ให้เอเจนต์ AI ทุกตัว'],
      desc: 'สมองที่สองที่มองเห็นได้ เก็บโน้ตเป็นกราฟความรู้ 3 มิติ ค้นด้วยความหมาย และเป็นศูนย์กลาง MCP ให้ Claude และเอเจนต์ AI ทุกตัวดึงความรู้ของคุณไปใช้ แอปบน Windows 10/11 ไม่ต้องติดตั้ง .NET คีย์เดียวได้ทั้ง Pro และ BrainX Cloud[[ {terms}]] ใช้กับ Claude ได้ทุกที่',
      features: ['กราฟความรู้ 3 มิติ', 'ค้นหาด้วยความหมาย', 'ศูนย์กลาง MCP ให้เอเจนต์ AI', 'Remote MCP ผ่าน BrainX Cloud', 'พื้นที่คลาวด์ 4 GB ต่อคีย์', 'เลือกเองว่าจะอัปโหลดโฟลเดอร์ไหน'],
      href: '/brainx.html', hrefLabel: 'ดูหน้า BrainX', external: false,
      download: SHOP + '/brainx/download', downloadLabel: 'ดาวน์โหลดแอป',
      shop: 'brainx-cloud', api: null, version: null, released: null,
      nova: [
        'BrainX คือสมองที่สองของเราเอง~ โน้ตทุกอันกลายเป็นดาวในกราฟ 3 มิติเลยนะ ✦',
        'อยากให้ Claude จำทุกอย่างที่คุณจดไว้? BrainX ทำได้ กำลังจะเปิดให้ใช้เร็ว ๆ นี้ รอติดตามนะคะ!',
        'สองภาพแรกเป็นหน้าจอจริงจากแอปเลยนะ ส่วนภาพที่มีป้าย MOCKUP เป็นภาพตัวอย่างค่ะ ✌',
      ],
    },
    {
      id: 'winx-tools', name: 'WinXTools', sub: 'NETWORK & SYSTEM CONTROL', cat: 'system',
      status: 'live', free: true, price: 'มีรุ่นฟรี[[ · Pro {from}]]', plans: { yearly: 199 },
      platforms: ['Windows'], tags: ['.NET', 'Kernel-level WFP', 'Per-app bandwidth', 'TH / EN'],
      palette: ['#22d3ee', '#0a1f3d', '#38bdf8'], planet: 3,
      art: art('winx-tools'),
      shots: [
        '01-dashboard', '02-bandwidth', '03-windows-optimizer-gamer-mode', '05-packet-monitor',
        '08-network-monitor', '07-cleaner-scanned', '06-ram-optimizer', '04-windows-tricks', '09-proxy-vpn',
      ].map(function (n) { return img('/assets/shots/winx/' + n + '.webp'); }),
      tagline: 'ดู bandwidth แยกโปรเซส คุมเครือข่ายถึงระดับ kernel',
      pitch: ['ดู bandwidth แยกทีละโปรแกรม คุมระดับ kernel ด้วย WFP', 'ล้างเครื่อง เร่ง RAM ถอนโปรแกรมลึกถึง registry'],
      desc: 'เครื่องมือคุมเครือข่ายและระบบ Windows ในตัวเดียว ดู bandwidth แยกตามโปรเซสแบบเรียลไทม์ จำกัดความเร็วรายโปรแกรมระดับ kernel ด้วย WFP มีตัวถอนโปรแกรมที่ตามลบถึง registry ตัวล้างเครื่อง ตัวเร่ง RAM และโหมดเกมเมอร์ มีรุ่นฟรีใช้ได้ตลอด ทดลอง Pro ฟรี 48 ชั่วโมง[[ ส่วน Pro {terms}]]',
      features: ['Bandwidth แยกตามโปรเซส', 'คุมระดับ kernel ด้วย WFP', 'จำกัดความเร็วรายโปรแกรม', 'ถอนโปรแกรม + ล้าง registry', 'System cleaner + RAM optimizer', 'Packet / network monitor'],
      href: SHOP + '/products/winx-tools', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: SHOP + '/winx-tools/download', downloadLabel: 'ดาวน์โหลดฟรี',
      shop: 'winx-tools', api: 'winx-tools', version: '1.0.2', released: '2026-09-24',
      nova: [
        'WinXTools ดูได้เลยว่าโปรแกรมไหนแอบใช้เน็ตเยอะ แล้วจำกัดได้ทีละตัวด้วยนะ!',
        'ภาพที่หมุนอยู่บนจอเป็นหน้าจอจริงของ WinXTools ทั้งหมดเลยค่ะ ดูได้ครบทุกหน้า',
        'มีรุ่นฟรีให้ลองก่อน ชอบแล้วค่อยอัป Pro {from}เองค่ะ ✦',
      ],
    },
    {
      id: 'cluadex', name: 'CluadeX', sub: 'AI CODING ASSISTANT', cat: 'ai',
      status: 'live', free: true, price: 'มีรุ่นฟรี[[ · License {from}]]', plans: { yearly: 199, lifetime: 1999 },
      platforms: ['Windows'], tags: ['Local GGUF + Ollama', 'Agent in your project', 'Skills · MCP · Subagents', 'TH / EN'],
      palette: ['#6366f1', '#121436', '#a5b4fc'], planet: 3,
      art: art('cluadex'),
      shots: shot('cluadex', ['editor', 'chat', 'models', 'skills', 'features']),
      tagline: 'ผู้ช่วยเขียนโค้ด AI ที่ลงมือทำในโปรเจกต์ของคุณ บน Windows',
      pitch: ['เอเจนต์เขียนโค้ดที่แก้ไฟล์ในโปรเจกต์ให้ ถามก่อนลงมือ', 'โมเดลในเครื่องใช้ฟรี โค้ดไม่ออกนอกเครื่อง'],
      desc: 'ผู้ช่วยเขียนโค้ด AI บน Windows 10/11 ที่ลงมือทำในโปรเจกต์ของคุณ มีอีดิเตอร์ เอเจนต์ที่ถามก่อนลงมือ Git ในเครื่อง ย้อนเวลาได้ Skill, Subagent และ MCP รุ่นฟรีใช้โมเดล GGUF ในเครื่องหรือ Ollama ได้ไม่จำกัดเวลา โค้ดไม่ออกนอกเครื่อง ส่วน Pro ต่อ Claude, GPT และ Gemini ด้วยคีย์ของคุณเอง หรือ CLI ที่ล็อกอินไว้ ทดลอง Pro ฟรี 24 ชั่วโมง ภาษาไทย/อังกฤษ[[ ส่วน License {terms}]]',
      features: ['Local GGUF + Ollama (ฟรี)', 'เอเจนต์ถามก่อนลงมือ 3 ระดับ', 'Git ในเครื่อง + ย้อนเวลา', 'Skill · Subagent · MCP', 'Claude / GPT / Gemini (Pro)', 'GitHub push / PR (Pro)'],
      href: SHOP + '/products/cluadex-ai-coding-assistant', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: SHOP + '/cluadex/download', downloadLabel: 'ดาวน์โหลดฟรี',
      shop: 'cluadex-ai-coding-assistant', api: 'cluadex-ai-coding-assistant', version: '3.0.54', released: '2026-08-31',
      nova: [
        'CluadeX เขียนโค้ดเป็นเพื่อนคุณได้ทั้งคืน ใช้โมเดลในเครื่องก็ไม่ส่งโค้ดออกนอกเครื่องเลยนะ',
        'รุ่นฟรีใช้โมเดลในเครื่องได้ อยากต่อ Claude หรือ GPT ค่อยอัป Pro ลองฟรี 24 ชั่วโมงก่อนก็ได้ค่ะ!',
      ],
    },
    {
      id: 'chanthra-studio', name: 'Chanthra Studio', sub: 'AI VIDEO ATELIER', cat: 'creative',
      status: 'soon', free: true, price: 'แอปใช้ฟรี[[ · อัปเดตอัตโนมัติ {from}]]', plans: { monthly: 399, yearly: 2500, lifetime: 7500 },
      platforms: ['Windows'], tags: ['ComfyUI', 'Node flow', 'TTS + LLM script', 'Auto-post'],
      palette: ['#f5c56b', '#2a1846', '#c084fc'], planet: 0,
      art: art('chanthra-studio'),
      shots: mock('chanthra-studio', ['generate', 'storyboard', 'editor', 'voice', 'nodeflow', 'gpu']),
      tagline: 'สตูดิโอวิดีโอ AI — เขียนบท ถ่ายช็อต ใส่เสียง ตัดต่อ ในแอปเดียว',
      pitch: ['script · shot · score · stitch ครบในแอปเดียว', 'ใช้ ComfyUI ในเครื่อง เช่า GPU หรือบริการบนคลาวด์'],
      desc: 'สตูดิโอวิดีโอ AI บน Windows 10/11 ดีไซน์แบบ lunar atelier — เขียนบทด้วย LLM สร้างช็อตด้วย ComfyUI ในเครื่อง เช่า GPU หรือบริการบนคลาวด์ ใส่เสียงพากย์ TTS เรนเดอร์ด้วย ffmpeg (ติดตั้งแยก) แล้วโพสต์อัตโนมัติ ต่อโหนดได้ด้วย node-flow editor ตัวแอปไม่ต้องมีคีย์ ใช้ได้ทุกหน้า ส่วน License ปลดการอัปเดตอัตโนมัติในแอป[[ ราคา {terms}]] ค่าบริการ AI จ่ายตรงกับผู้ให้บริการ',
      features: ['ComfyUI ในเครื่อง หรือเช่า GPU', 'Node-flow graph editor', 'เขียนบทด้วย LLM', 'เสียงพากย์ TTS', 'Storyboard Auto Pilot', 'โพสต์ Facebook อัตโนมัติ'],
      href: SHOP + '/chanthra-studio', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null,
      shop: 'chanthra-studio', api: 'chanthra-studio', version: '0.11.0', released: '2026-09-24',
      nova: [
        'Chanthra Studio คือสตูดิโอทำวิดีโอด้วย AI ธีมพระจันทร์สีทอง สวยเหมือนโนวาเลย~',
        'ใส่ไอเดียไปบรรทัดเดียว ได้ทั้งบท ภาพ เสียง แล้วตัดต่อเป็นคลิปให้ค่ะ ✦',
      ],
    },
    {
      id: 'gpuxmine', name: 'GPUxMINE', sub: 'GPU WORKER', cat: 'cloud',
      status: 'live', free: true, price: 'ใช้ฟรี · รับรายได้จากการ์ดจอ',
      platforms: ['Windows'], tags: ['AI jobs', 'Temp ceiling', 'Schedule', 'Auto-update'],
      palette: ['#f59e0b', '#1d1530', '#60a5fa'], planet: 3,
      art: art('gpuxmine'),
      shots: mock('gpuxmine', ['dashboard', 'power', 'benchmark', 'settings']),
      tagline: 'ให้การ์ดจอทำงาน AI ตอนคุณไม่ได้ใช้ แล้วรับรายได้',
      pitch: ['รับงาน AI อัตโนมัติเท่าที่การ์ดจอของคุณไหว', 'หยุดรับงานใหม่ทันทีเมื่อเปิดเกมหรือกลับมาใช้เครื่อง'],
      desc: 'โปรแกรมแชร์การ์ดจอรับงาน AI (ตอนนี้คือสร้างภาพ SDXL ผ่าน ComfyUI) ตามสเปกเครื่อง ตั้งเพดานอุณหภูมิและตารางเวลาแชร์ได้เอง หยุดรับงานใหม่ทันทีเมื่อเปิดเกมหรือกลับมาใช้เครื่อง งานที่รับไว้ทำต่อจนเสร็จ รายได้ขึ้นเมื่อ pool ตัดยอด พักไว้ 24 ชั่วโมงแล้วเข้ากระเป๋าเงิน XMAN และอัปเดตตัวเองอัตโนมัติ ต้องติดตั้ง ComfyUI กับ SDXL เอง การ์ด 6 GB ขึ้นไป',
      features: ['รับงาน AI ตามสเปกการ์ด', 'ตั้งเพดานอุณหภูมิได้', 'ตั้งตารางเวลาแชร์', 'หยุดรับงานใหม่เมื่อเปิดเกม', 'ค่าไฟต่อวันจากวัตต์จริง', 'อัปเดตตัวเองอัตโนมัติ'],
      href: SHOP + '/products/gpuxmine', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null,
      shop: 'gpuxmine', api: 'gpuxmine', version: '0.1.18', released: '2026-09-25',
      nova: [
        'การ์ดจอว่างอยู่เฉย ๆ เหรอ? ให้ GPUxMINE พามันไปทำงาน AI แล้วรับเงินกลับมาเลย!',
        'เปิดเกมเมื่อไหร่ มันหยุดรับงานใหม่ทันทีนะ งานที่รับไว้แล้วทำให้เสร็จก่อนค่ะ',
      ],
    },
    {
      id: 'autotradex', name: 'AutoTradeX', sub: 'CRYPTO ARBITRAGE BOT', cat: 'commerce',
      status: 'live', free: true, price: 'Demo ฟรี[[ · License {from}]]', plans: { monthly: 299, yearly: 1990, lifetime: 19900 },
      platforms: ['Windows'], tags: ['Portable', '6 exchanges', 'Demo mode', 'Risk controls'],
      palette: ['#34d399', '#0b2420', '#a3e635'], planet: 1,
      art: art('autotradex'),
      shots: mock('autotradex', ['dashboard', 'scanner', 'ai-trading', 'trading', 'analytics']),
      tagline: 'บอทเทรด arbitrage อัตโนมัติ 6 exchange พร้อมโหมดทดลอง',
      pitch: ['ไล่ส่วนต่างราคาข้าม 6 exchange แบบเรียลไทม์', 'โหมด Demo ใช้ฟรี ทดลองเทรดด้วยเงินเสมือนก่อนได้'],
      desc: 'บอทเทรด crypto arbitrage อัตโนมัติบน Windows 10/11 ดูราคาเรียลไทม์จาก 6 exchange (Binance, KuCoin, OKX, Bybit, Gate.io, Bitkub) มี Smart Scanner, AI Trading 6 กลยุทธ์ ติดตามกำไรขาดทุนพร้อมกราฟ บันทึกประวัติการเทรด และระบบจัดการความเสี่ยง รุ่น Portable ไม่ต้องติดตั้ง .NET ไม่มี License ก็ใช้โหมด Demo ได้ทุกหน้าจอด้วยเงินเสมือน ส่วนบอท arbitrage ทดลองฟรี 7 วันด้วยเงินเสมือน จะเทรดจริงต้องมี License[[ {terms}]]',
      features: ['ราคาเรียลไทม์ 6 exchange', 'Demo ด้วยเงินเสมือน', 'Smart Scanner', 'AI Trading 6 กลยุทธ์', 'P&L + ประวัติการเทรด', 'คุมความเสี่ยง + EMERGENCY STOP'],
      href: SHOP + '/products/autotradex', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null,
      shop: 'autotradex', api: 'autotradex', version: '0.3.0', released: '2026-09-24',
      nova: [
        'AutoTradeX จับส่วนต่างราคาข้าม exchange ให้อัตโนมัติ มีโหมดทดลองก่อนใช้เงินจริงด้วยนะ',
        'ลองโหมด simulation ก่อนเสมอนะคะ ใจเย็น ๆ ตลาดคริปโตไม่หนีไปไหน ✦',
      ],
    },
    {
      id: 'tping', name: 'Tping', sub: 'ANDROID AUTO-TYPER', cat: 'mobile',
      status: 'live', free: false, price: '[[{from} · ]]ทดลองฟรี 24 ชม.', plans: { monthly: 399, yearly: 2500, lifetime: 5000 },
      platforms: ['Android'], tags: ['Floating overlay', 'Record & replay', 'Game mode', 'Data sets'],
      palette: ['#22d3ee', '#0b2236', '#5eead4'], planet: 1,
      art: art('tping'),
      shots: mock('tping', ['home', 'record', 'play', 'game', 'data', 'cloud']),
      tagline: 'บันทึกขั้นตอนแล้วเล่นซ้ำได้ถึง 999 รอบ ใช้ได้ทุกแอป',
      pitch: ['บันทึกขั้นตอนครั้งเดียว เล่นซ้ำได้ถึง 999 รอบ', 'มีโหมดเกมพร้อม crosshair overlay'],
      desc: 'แอปช่วยพิมพ์และกดอัตโนมัติบน Android 8.0 ขึ้นไป ไม่ต้อง Root บันทึกขั้นตอนครั้งเดียวแล้วเล่นซ้ำได้ 1–999 รอบ จัดการชุดข้อมูลหลายชุด มีโหมดเกมพร้อม crosshair overlay กดรัว แก้ captcha แบบเลื่อน และปุ่มลอยที่ใช้ได้ทุกแอป ทดลองฟรี 24 ชั่วโมง ไม่ต้องสมัคร ไม่ต้องผูกบัตร[[ แล้ว {terms}]] Cloud Sync และ Export / Import ใช้ได้เมื่อมี License',
      features: ['บันทึกขั้นตอนครั้งเดียว', 'เล่นซ้ำ 1–999 รอบ', 'ชุดข้อมูลหลายชุด', 'โหมดเกม + crosshair', 'กดรัว + captcha แบบเลื่อน', 'Cloud Sync + Export / Import (License)'],
      href: SHOP + '/tping', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: SHOP + '/tping/download', dlPage: true, downloadLabel: 'ทดลองฟรี 24 ชม.',
      shop: 'tping', api: 'tping', version: '1.2.102', released: '2026-03-25',
      nova: [
        'งานกรอกซ้ำ ๆ ทั้งวัน ให้ Tping จำแล้วกดแทนได้เลย มือจะได้พักบ้างนะ',
        'โหมดเกมมี crosshair ลอยบนจอด้วยค่ะ ลองฟรี 24 ชั่วโมงก่อนก็ได้!',
      ],
    },
    {
      id: 'aipray', name: 'Aipray', sub: 'BUDDHIST CHANTING COMPANION', cat: 'mobile',
      status: 'live', free: true, price: 'ฟรีตลอดไป',
      platforms: ['Android'], tags: ['Speech AI', 'Works offline', '20+ chants', 'Free forever'],
      palette: ['#fbbf24', '#2b1a0c', '#fde68a'], planet: 0,
      art: art('aipray'), shots: [],
      tagline: 'บทสวด 20+ บท AI ฟังเสียงจับตำแหน่งให้ ฟรีตลอดไป',
      pitch: ['AI ฟังเสียงสวดแล้วเลื่อนตามให้อัตโนมัติ', 'นับรอบให้ ใช้ออฟไลน์ได้ ฟรีตลอดไป'],
      desc: 'เพื่อนสวดมนต์ที่ใช้ AI ฟังเสียงแล้วจับตำแหน่งบทสวดให้อัตโนมัติ มีบทสวดมนต์ 20+ บทพร้อมตัวอักษรไทย นับรอบให้พร้อมสั่นเตือน บันทึกประวัติการสวด ใช้งานออฟไลน์ได้ และอัปเดตในแอป ฟรีตลอดไป',
      features: ['บทสวด 20+ บท', 'AI จับตำแหน่งจากเสียง', 'นับรอบ + สั่นเตือน', 'บันทึกประวัติการสวด', 'ใช้ออฟไลน์ได้', 'อัปเดตในแอป'],
      href: SHOP + '/apps/aipray', hrefLabel: 'ดูหน้าแอป', external: true,
      download: SHOP + '/apps/aipray/download', downloadLabel: 'ดาวน์โหลด APK ฟรี',
      shop: 'aipray', api: 'aipray', version: '1.2.5', released: '2026-09-24',
      nova: [
        'Aipray ฟังเสียงสวดแล้วเลื่อนบทตามให้เอง ไม่ต้องคอยปัดจอเลยค่ะ 🙏',
        'แอปนี้ฟรีตลอดไปนะ เป็นของขวัญจากทีมเราเลย ✦',
      ],
    },
    {
      id: 'localvpn', name: 'LocalVPN', sub: 'VIRTUAL LAN OVER INTERNET', cat: 'network',
      status: 'live', free: true, price: 'ฟรี 5 คน[[ · Pro {from}]]', plans: { monthly: 199, yearly: 1990, lifetime: 4990 },
      platforms: ['Android', 'Windows'], tags: ['X25519 + ChaCha20', 'P2P + relay', 'LAN games', 'Private networks'],
      palette: ['#60a5fa', '#0d1b3d', '#c084fc'], planet: 1,
      art: art('localvpn'),
      shots: shot('localvpn', ['home', 'network', 'networks', 'files', 'boost', 'tuneup', 'vpn', 'phone-networks', 'phone-network']),
      tagline: 'สร้างวง LAN เสมือนข้ามอินเทอร์เน็ต เข้ารหัสระหว่างเครื่อง',
      pitch: ['คอม Windows กับมือถือ Android อยู่วงแลนเดียวกัน แม้อยู่คนละที่', 'ต่อตรง P2P ทะลุ NAT มีรีเลย์สำรองอัตโนมัติ'],
      desc: 'สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต ให้คอม Windows 10/11 และมือถือ Android เชื่อมกันเหมือนอยู่วงแลนเดียว หาวงสาธารณะในล็อบบี้ หรือตั้งรหัสเพื่อความส่วนตัว ส่งไฟล์ถึงกันในวง ต่อตรงแบบ P2P ทะลุ NAT และสลับไปรีเลย์เองเมื่อต่อตรงไม่ได้ ทุกแพ็กเก็ตระหว่างสมาชิกเข้ารหัสด้วย X25519 + ChaCha20-Poly1305 รวมถึงตอนผ่านรีเลย์ เครื่องใหม่ทดลอง Pro ฟรี 30 วัน ครบแล้วใช้รุ่นฟรีต่อได้สูงสุด 5 คนต่อวง ส่วน Pro รองรับ 50 คนต่อวง 1 คีย์ใช้ได้ 2 เครื่อง[[ ราคา {terms}]]',
      features: ['วง LAN เสมือนข้ามเน็ต', 'ล็อบบี้วงสาธารณะ', 'ตั้งรหัสเครือข่าย', 'ส่งไฟล์ถึงกันในวง', 'เข้ารหัส X25519 + ChaCha20-Poly1305', 'ต่อตรง P2P + รีเลย์สำรอง'],
      href: SHOP + '/products/localvpn', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: SHOP + '/localvpn/download', dlPage: true, downloadLabel: 'ดาวน์โหลดฟรี',
      shop: 'localvpn', api: 'localvpn', version: '1.0.39', released: '2026-04-01',
      nova: [
        'อยากเล่นเกม LAN กับเพื่อนที่อยู่คนละจังหวัด? LocalVPN จัดให้ค่ะ!',
        'ข้อมูลในวงเข้ารหัสระหว่างเครื่องด้วยนะ ปลอดภัย แถมใช้ฟรีได้ถึง 5 คนต่อวง ✌',
      ],
    },
    {
      id: 'anti-x', name: 'Anti X', sub: 'WINDOWS SERVER INTRUSION GUARD', cat: 'system',
      status: 'live', free: true, price: 'ฟรีพื้นฐาน[[ · Pro {from}]]', plans: { monthly: 199, yearly: 1990, lifetime: 9900 },
      platforms: ['Windows', 'Server'], tags: ['Windows service', 'RDP · SSH · DB guard', 'Per-IP firewall', 'Threat feeds'],
      palette: ['#38bdf8', '#0a1838', '#f5c56b'], planet: 2,
      art: art('anti-x'),
      shots: shot('anti-x', ['dashboard', 'live', 'blocks', 'events', 'machines']).concat(mock('anti-x', ['reports', 'suspects', 'honeypot'])),
      tagline: 'กันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์ บล็อกทีละ IP ไม่ล็อกตัวเอง',
      pitch: ['บล็อกการเดารหัส RDP และการสแกนพอร์ตแบบเรียลไทม์ · SSH และฐานข้อมูลใน Pro', 'บล็อกทีละ IP ไม่ปิดพอร์ต RDP ไม่ล็อกผู้ดูแลที่ต่ออยู่'],
      desc: 'ระบบกันการเจาะเซิร์ฟเวอร์ Windows Server 2016 ขึ้นไป หรือ Windows 10/11 แบบเรียลไทม์ ทำงานเป็นบริการ Windows ตลอดเวลาแม้ปิดหน้าจอหรือตัด RDP ไปแล้ว อ่าน event log และการเชื่อมต่อของเครื่องสด ๆ แล้วบล็อก IP ด้วย Windows Firewall ทีละ IP — ไม่ปิดพอร์ต RDP ทั้งพอร์ต และไม่ล็อกผู้ดูแลที่ต่ออยู่ รุ่นฟรีจับการเดารหัส RDP / ล็อกออน Windows การสแกนพอร์ต และพอร์ตอันตรายได้ตลอด ส่วน Pro เพิ่มการเดารหัส SSH / ฐานข้อมูล ยิงหาช่องโหว่เว็บ รายชื่อ IP อันตราย Honeypot และจับเครื่องที่ถูกเจาะ ทดลอง Pro ฟรี 14 วัน[[ แล้ว {terms}]]',
      features: ['บล็อกเดารหัส RDP + ล็อกออน Windows', 'บล็อกสแกนพอร์ต + พอร์ตอันตราย', 'ไม่ปิดพอร์ต RDP ไม่ล็อกผู้ดูแล', 'SSH · ฐานข้อมูล · ช่องโหว่เว็บ (Pro)', 'IP อันตราย + จับเครื่องที่ถูกเจาะ (Pro)', 'อัปเดตในตัว ตรวจลายเซ็นก่อนติดตั้ง'],
      href: SHOP + '/products/anti-x', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: SHOP + '/anti-x/download', downloadLabel: 'ดาวน์โหลดฟรี',
      shop: 'anti-x', api: 'anti-x', version: '1.4.0', released: null,
      nova: [
        'Anti X เฝ้าเซิร์ฟเวอร์ Windows ให้ทั้งคืน ใครมาเดารหัส RDP โดนบล็อกทีละ IP เลยค่ะ!',
        'ไม่ต้องกลัวล็อกตัวเองนะ Anti X ไม่ปิดพอร์ต RDP ทั้งพอร์ต และไม่ล็อกผู้ดูแลที่ต่ออยู่ค่ะ',
        'รุ่นฟรีใช้ได้ตลอดเลย อยากได้ตัวจับขั้นสูงค่อยลอง Pro ฟรี 14 วันก่อนก็ได้ ✦',
      ],
    },
    {
      id: 'giggok', name: 'GigGok', sub: '3D AI SECRETARY', cat: 'mobile',
      status: 'live', free: true, price: 'ใช้ฟรี · ไม่ต้องสมัคร',
      platforms: ['Android'], tags: ['3D avatar', 'On-device Gemma', 'Voice chat', 'Answers calls'],
      palette: ['#e879f9', '#1c0f33', '#2dd4bf'], planet: 0,
      art: art('giggok'),
      shots: mock('giggok', ['home', 'call', 'timeline', 'brain', 'shop', 'studio']),
      tagline: 'เลขาสาว 3D บนมือถือ คุยได้ พูดได้ รับสายแทน สมองคิดในเครื่อง',
      pitch: ['เลขาสาว 3D คุยด้วยเสียง ตอบเป็นเสียง รับสายแทนได้ (เบต้า)', 'สมองในเครื่อง (Gemma) เป็นค่าเริ่มต้น บทสนทนาไม่ออกนอกเครื่อง'],
      desc: 'เลขาสาว 3D บนมือถือ Android 8.0 ขึ้นไป (64-bit) คุยด้วยเสียงได้ ตอบกลับเป็นเสียงและขยับปากตาม ดูปฏิทิน จดบันทึกให้ และช่วยรับสายแทนคุณ (ฟีเจอร์ทดลอง) สมองหลักทำงานในเครื่อง (Gemma) เป็นค่าเริ่มต้น ลองใช้ GPU ของมือถือได้ บทสนทนาไม่ต้องส่งออกนอกเครื่อง หรือจะเลือกสมองผ่านบริการของเรา คีย์ OpenAI ของคุณ หรือเซิร์ฟเวอร์ในบ้านก็ได้ ใช้ฟรีทุกฟีเจอร์ ไม่ต้องสมัครสมาชิก ทุกเครื่องได้ License ฟรีอัตโนมัติ ส่วนชุดตัวมายด์เลือกดูได้ในแอปแล้วซื้อแยกบนเว็บ',
      features: ['อวาตาร์ 3D ขยับปากตามเสียง', 'สมองในเครื่อง (Gemma) ใช้ GPU ได้', 'ไมค์ถอดเสียงในเครื่อง', 'รับสายแทน รับฝากเรื่อง (เบต้า)', 'อ่านปฏิทินของเครื่อง', 'อัปเดตในตัว ตรวจ SHA-256'],
      href: SHOP + '/products/giggok', hrefLabel: 'ดูหน้าแอป', external: true,
      download: SHOP + '/giggok/download', downloadLabel: 'ดาวน์โหลด APK ฟรี',
      shop: 'giggok', api: 'giggok', version: '0.1.48', released: null,
      nova: [
        'GigGok คือเลขาสาว 3D บนมือถือ คุยด้วยเสียงได้ แถมรับสายแทนให้ด้วยนะคะ~',
        'สมองของเธอทำงานในเครื่องเลย บทสนทนาไม่ต้องส่งออกนอกมือถือค่ะ ✦',
        'โหลด APK ไปใช้ฟรีได้เลย ไม่ต้องสมัครสมาชิกด้วยนะ!',
      ],
    },
    {
      id: 'postx-agent', name: 'PostXAgent', sub: 'AI BRAND PROMOTION', cat: 'ai',
      status: 'soon', free: false, price: '{price}', amount: 7990,
      platforms: ['Windows'], tags: ['AI content', 'FB Page + IG', 'Scheduler', 'Local Ollama'],
      palette: ['#a78bfa', '#1a1440', '#f0abfc'], planet: 2,
      art: art('postx-agent'),
      shots: mock('postx-agent', ['dashboard', 'content', 'schedule']),
      tagline: 'AI ช่วยโปรโมตแบรนด์ เขียนโพสต์ ตั้งเวลา แล้วโพสต์ให้เอง',
      pitch: ['AI เขียนโพสต์ ตั้งเวลา แล้วโพสต์ลง Facebook Page และ Instagram ให้เอง', 'ใช้ Ollama ในเครื่องได้ฟรี หรือ Gemini · OpenAI · Claude ด้วยคีย์ของคุณ'],
      desc: 'ระบบโปรโมตแบรนด์ด้วย AI บน Windows 10/11 เขียนโพสต์ไทย อังกฤษ หรือผสมด้วย Ollama ที่รันในเครื่อง หรือ Gemini, OpenAI, Claude ด้วยคีย์ของคุณ สลับผู้ให้บริการ AI ให้เองเมื่อตัวหนึ่งใช้ไม่ได้ ตั้งเวลาแล้วโพสต์ลง Facebook Page และ Instagram Business ให้อัตโนมัติ ข้อมูลเก็บในเครื่อง 1 License ใช้ได้ 1 เครื่อง ส่วนการโพสต์อีก 7 แพลตฟอร์ม พูลบัญชี และโหมดสอนบนหน้าเว็บ เร็ว ๆ นี้ ตอนนี้กำลังพัฒนา ยังไม่เปิดขาย',
      features: ['เขียนโพสต์ด้วย AI', 'Ollama ในเครื่อง ไม่ต้องใช้คีย์', 'Gemini / OpenAI / Claude สำรองกัน', 'ตั้งเวลาโพสต์ FB Page + IG', 'อีก 7 แพลตฟอร์ม (เร็ว ๆ นี้)', 'พูลบัญชี + โหมดสอน (เร็ว ๆ นี้)'],
      href: SHOP + '/products/postx-agent', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'postx-agent', api: null, version: null, released: null,
      nova: ['PostXAgent ให้ AI เขียนโพสต์ ตั้งเวลา แล้วโพสต์ลง Facebook กับ Instagram ให้เอง กำลังเก็บงานค่ะ!'],
    },
    {
      id: 'livexshop-pro', name: 'Live x Shop Pro', sub: 'LIVE-SELLING BACK OFFICE', cat: 'commerce',
      status: 'soon', free: false, price: '{price}', amount: 5990,
      platforms: ['Windows'], tags: ['Back office', 'Chat → CF → order', 'Local data', 'In development'],
      palette: ['#fb7185', '#2a0f24', '#fbbf24'], planet: 0,
      art: art('livexshop-pro'),
      shots: mock('livexshop-pro', ['dashboard', 'live', 'cf', 'orders', 'connect']),
      tagline: 'ระบบหลังบ้านสำหรับไลฟ์ขายของ — กำลังพัฒนา',
      pitch: ['ดึงแชทไลฟ์มาไว้ที่เดียว จับ CF แล้วทำเป็นออเดอร์ (เร็ว ๆ นี้)', 'ไม่ได้สตรีมแทนคุณ ไลฟ์ด้วยแอปหรือ OBS ตามเดิม'],
      desc: 'ระบบหลังบ้านสำหรับไลฟ์ขายของ กำลังพัฒนาเป็นโปรแกรมบน Windows ไม่ได้สตรีมไลฟ์แทนคุณ คุณยังไลฟ์ด้วยแอปของแพลตฟอร์มหรือ OBS ตามเดิม ส่วนโปรแกรมจะทำงานหลังบ้าน: ดึงแชทจาก Facebook, TikTok และ LINE OA มาไว้ที่เดียว จับ CF เป็นออเดอร์ และรวมออเดอร์ทั้งร้านในตารางเดียว ตามแผนจะมีการอ่านสลิปด้วย OCR การเชื่อมขนส่ง POS + สต็อก และแอปมือถือคู่หู ทุกอย่างยังเป็น "เร็ว ๆ นี้" แผนพัฒนามี 5 ขั้น ตอนนี้อยู่ขั้นแรก ยังไม่เปิดขายและยังไม่มีไฟล์ให้ดาวน์โหลด เงื่อนไข License ยังไม่ประกาศ',
      features: ['รวมแชทไลฟ์ (เร็ว ๆ นี้)', 'จับ CF เป็นออเดอร์ (เร็ว ๆ นี้)', 'ออเดอร์ทั้งร้านในตารางเดียว (เร็ว ๆ นี้)', 'อ่านสลิปด้วย OCR (เร็ว ๆ นี้)', 'เชื่อมขนส่ง + POS (เร็ว ๆ นี้)', 'แอปมือถือคู่หู (เร็ว ๆ นี้)'],
      href: SHOP + '/products/livexshop-pro', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'livexshop-pro', api: null, version: null, released: null,
      nova: ['Live x Shop Pro คือระบบหลังบ้านของแม่ค้าไลฟ์สด ยังอยู่ขั้นแรกของการพัฒนา เปิดเมื่อไหร่โนวาจะบอกนะคะ!'],
    },
    {
      id: 'xcluade-agent', name: 'XcluadeAgent', sub: 'RELEASE SYNC + AI DIAGNOSIS', cat: 'ai',
      status: 'soon', free: false, price: '{price}', amount: 3490,
      platforms: ['Linux', 'Web'], tags: ['GitHub releases', 'Auto-rollback', 'AI error analysis', '5 alert channels'],
      palette: ['#7dd3fc', '#0c1a33', '#a78bfa'], planet: 3,
      art: art('xcluade-agent'),
      shots: mock('xcluade-agent', ['dashboard', 'projects', 'history', 'ai']),
      tagline: 'ดึง release ใหม่จาก GitHub ขึ้นเซิร์ฟเวอร์ให้เอง ย้อนกลับเองเมื่อพัง',
      pitch: ['ดึง release ใหม่ขึ้นเซิร์ฟเวอร์ให้อัตโนมัติ สำรองก่อนทุกครั้ง', 'AI วิเคราะห์ error เสนอวิธีแก้ ย้อนกลับเองเมื่อ health check ไม่ผ่าน'],
      desc: 'บริการซิงก์ GitHub release ขึ้นเซิร์ฟเวอร์ Linux อัตโนมัติ (ตัวติดตั้งรองรับ Ubuntu 20.04+ / Debian 10+ เป็น systemd service หรือรันใน Docker) ดูแลทุกโปรเจกต์จาก web dashboard เดียว สำรองข้อมูลก่อนทุก sync ย้อนกลับเองเมื่อ health check ไม่ผ่าน ผู้ช่วย AI (Ollama, Claude หรือ OpenAI) วิเคราะห์ error และเสนอวิธีแก้ให้คุณตัดสินใจเอง ยังไม่แก้ไฟล์บนเซิร์ฟเวอร์เอง แจ้งเตือน 5 ช่องทาง Discord, Telegram, LINE, Slack และอีเมล รองรับ Laravel, Node.js, React/Vue, Django, .NET และอื่น ๆ จำนวนโปรเจกต์ที่ License ปลดประกาศพร้อมเปิดขาย',
      features: ['Release sync อัตโนมัติ', 'AI วิเคราะห์ error + เสนอวิธีแก้', 'Auto-rollback เมื่อ health check ไม่ผ่าน', 'Backup ก่อนทุก sync', 'แจ้งเตือน 5 ช่องทาง', 'CLI + web dashboard'],
      href: SHOP + '/products/xcluade-agent', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'xcluade-agent', api: null, version: null, released: null,
      nova: ['XcluadeAgent ดูแลการ deploy แทนคุณ ถ้า health check ไม่ผ่านก็ย้อนกลับเองนะ นอนหลับสบายเลย~'],
    },
    {
      id: 'spiderx', name: 'SpiderX', sub: 'P2P MESH NETWORK', cat: 'network',
      status: 'soon', free: false, price: '{price}', amount: 2990,
      platforms: ['Windows'], tags: ['No central server', 'LAN discovery', 'Swarm file sharing', 'Virtual LAN'],
      palette: ['#2dd4bf', '#08201f', '#818cf8'], planet: 2,
      art: art('spiderx'),
      shots: mock('spiderx', ['dashboard', 'chat', 'nearby', 'files', 'lan']),
      tagline: 'คุย แชร์ไฟล์ ตรงถึงกัน ไม่มีตัวกลาง ไม่ต้องลงทะเบียน',
      pitch: ['เครือข่าย P2P mesh ไม่มีเซิร์ฟเวอร์กลาง', 'แชท แชร์ไฟล์ และ virtual LAN ตรงถึงเครื่องเพื่อน'],
      desc: 'เครือข่าย P2P mesh บน Windows 10/11 ไม่มีเซิร์ฟเวอร์กลาง ไม่ต้องลงทะเบียน ตัวตนคือกุญแจที่สร้างบนเครื่องคุณเอง แชทตรงถึงเครื่องเพื่อนด้วย SpiderX ID เจอเครื่องในวงแลนเองแม้เน็ตล่ม แชร์ไฟล์ดึงจากหลายเครื่องพร้อมตรวจ SHA-256 ทุกชิ้น และ virtual LAN สำหรับเล่นเกม ส่วนการเข้ารหัสปลายทางถึงปลายทาง โทรเสียง แชทกลุ่ม เพิ่มเพื่อนด้วย QR และการหากันข้ามอินเทอร์เน็ต เร็ว ๆ นี้ ตอนนี้กำลังพัฒนา ยังไม่เปิดขาย',
      features: ['ไม่มีเซิร์ฟเวอร์กลาง', 'แชทตรงถึงเครื่องเพื่อน', 'เจอเครื่องในวงแลนเอง', 'แชร์ไฟล์ดึงจากหลายเครื่อง', 'Virtual LAN สำหรับเกม', 'เข้ารหัส E2E + โทรเสียง (เร็ว ๆ นี้)'],
      href: SHOP + '/products/spiderx', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'spiderx', api: null, version: null, released: null,
      nova: ['SpiderX คุยกันได้โดยไม่ผ่านเซิร์ฟเวอร์ใครเลย ส่วนตัวสุด ๆ ค่ะ 🕸'],
    },
    {
      id: 'phonex-manager', name: 'PhoneX Manager', sub: 'ANDROID SERVICE SUITE', cat: 'mobile',
      status: 'soon', free: false, price: '{price}', amount: 1990,
      platforms: ['Windows'], tags: ['ADB / Fastboot', 'Qualcomm · MTK', 'Samsung · Xiaomi', 'Scrcpy mirror'],
      palette: ['#818cf8', '#141a3a', '#67e8f9'], planet: 3,
      art: art('phonex-manager'),
      shots: mock('phonex-manager', ['device', 'setup', 'romsearch', 'tools', 'mirror', 'hex']),
      tagline: 'แฟลชรอม สำรองพาร์ทิชัน ไดรเวอร์ครบ สำหรับร้านซ่อมมือถือ',
      pitch: ['ชุดเครื่องมือช่างมือถือ Android ในโปรแกรมเดียว', 'รองรับ Qualcomm, MTK, Samsung, Xiaomi'],
      desc: 'ชุดเครื่องมือช่างมือถือ Android บน Windows 10/11 (ต้องมี .NET 8 Desktop Runtime) ตรวจเครื่องผ่าน ADB/Fastboot ติดตั้งไดรเวอร์ครบในคลิกเดียว ค้นหารอมจากหลายแหล่ง แฟลช image ผ่าน Fastboot สำรองพาร์ทิชันพร้อม SHA-256 ติดตั้งและเปิดเครื่องมือ Qualcomm EDL / MediaTek / Samsung / Xiaomi ให้ ลบแอปขยะแบบคืนได้ มี screen mirroring, logcat, ADB terminal และ hex editor ราคาเดียวได้ทุกเครื่องมือ',
      features: ['ตรวจเครื่องผ่าน ADB/Fastboot', 'ติดตั้งไดรเวอร์ในคลิกเดียว', 'ค้นหารอม + แฟลชผ่าน Fastboot', 'สำรองพาร์ทิชัน + SHA-256', 'Qualcomm EDL · MTK · Samsung · Xiaomi', 'Screen mirroring + hex editor'],
      href: SHOP + '/products/phonex-manager', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'phonex-manager', api: null, version: null, released: null,
      nova: ['ร้านซ่อมมือถือต้องมี PhoneX Manager ไว้สักตัว ทำได้ครบจบในโปรแกรมเดียวค่ะ'],
    },
    {
      id: 'skidrow-killer', name: 'Skidrow Killer', sub: 'MALWARE SCANNER', cat: 'system',
      status: 'soon', free: false, price: '{price} · คีย์ 1 ปี', amount: 299,
      platforms: ['Windows'], tags: ['Real-time', 'Heuristics + ssdeep', 'Web / C2 blocking', 'Ransomware guard'],
      palette: ['#f43f5e', '#250a14', '#c084fc'], planet: 2,
      art: art('skidrow-killer'),
      shots: mock('skidrow-killer', ['home', 'scan', 'threats', 'intel', 'ransomware']),
      tagline: 'สแกนและกำจัดมัลแวร์ แครก โทรจัน บน Windows บล็อกเซิร์ฟเวอร์ C2',
      pitch: ['ป้องกันเครื่องจากแครก โทรจัน และมัลแวร์', 'ดูเนื้อไฟล์ ไม่ใช่แค่ชื่อ สำรองก่อนลบ บล็อก C2'],
      desc: 'สแกนและกำจัดมัลแวร์บน Windows 10/11 พร้อมป้องกันแบบเรียลไทม์ สแกน Quick / Deep / Custom ดูเนื้อไฟล์ด้วย hash, ssdeep และ heuristics ลบให้หมดทั้งโปรเซส ค่า Run, scheduled task และ service สำรองก่อนลบคืนได้ บล็อกโดเมนแครก มัลแวร์ และเซิร์ฟเวอร์ควบคุม (C2) กันแรนซัมแวร์ด้วยไฟล์ล่อ ป้องกัน USB และทำงานร่วมกับ Windows Defender ได้ ไม่มีรุ่นฟรี 1 คีย์ใช้ได้ 1 เครื่อง อายุ 1 ปี',
      features: ['Real-time protection', 'Smart Scan + heuristics', 'ลบหมดจด สำรองก่อนลบ', 'Web / C2 blocking', 'Ransomware guard', 'USB protection'],
      href: SHOP + '/products/skidrow-killer', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'skidrow-killer', api: null, version: null, released: null,
      nova: ['โหลดของแครกมาแล้วใจไม่ดี? Skidrow Killer ช่วยตรวจให้ค่ะ แต่ซื้อของแท้ดีที่สุดนะ~'],
    },
    {
      id: 'gpusharx', name: 'GPUsharX', sub: 'GPU SHARING PLATFORM', cat: 'cloud',
      status: 'soon', free: false, price: 'ยังไม่ประกาศราคา',
      platforms: ['Web', 'Windows'], tags: ['AI image + video jobs', 'Windows client', 'Revenue share', 'Anti-cheat'],
      palette: ['#38bdf8', '#0b1a2e', '#34d399'], planet: 3,
      art: art('gpusharx'), shots: [],
      tagline: 'แพลตฟอร์มแชร์พลังการ์ดจอ สำหรับงานภาพและวิดีโอ AI — กำลังพัฒนา',
      pitch: ['แชร์การ์ดจอเข้ากองกลาง รับงานภาพและวิดีโอ AI', 'ส่วนแบ่งตามงานที่ทำ เงื่อนไขประกาศพร้อมเปิดตัว'],
      desc: 'แผนของแพลตฟอร์มแชร์พลังการ์ดจอสำหรับงานภาพและวิดีโอ AI: โปรแกรมบน Windows สำหรับผู้แชร์ เว็บแพลตฟอร์มที่กระจายงานไปยังเครื่องที่รับไหว ส่วนแบ่งรายได้ตามงานที่ทำพร้อมระบบถอนเงิน และระบบกันโกง ตอนนี้กำลังพัฒนา ราคา เงื่อนไข และอัตราส่วนแบ่งรายได้ยังไม่ประกาศ ทุกข้ออาจเปลี่ยนก่อนเปิดตัว ถ้าอยากให้การ์ดจอทำงานตั้งแต่วันนี้ ดู GPUxMINE',
      features: ['โปรแกรมผู้แชร์บน Windows (เร็ว ๆ นี้)', 'เว็บแพลตฟอร์ม (เร็ว ๆ นี้)', 'กระจายงานตามกำลังเครื่อง (เร็ว ๆ นี้)', 'ส่วนแบ่ง + ถอนเงิน (เร็ว ๆ นี้)', 'ระบบกันโกง (เร็ว ๆ นี้)'],
      href: SHOP + '/products/gpusharx', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'gpusharx', api: null, version: null, released: null,
      nova: ['GPUsharX คือแพลตฟอร์มแชร์การ์ดจอที่เรากำลังสร้างอยู่ค่ะ อยากให้การ์ดทำงานตั้งแต่วันนี้ ลอง GPUxMINE ก่อนได้เลย!'],
    },
    {
      id: 'macdock', name: 'MacDock', sub: 'MACOS-STYLE DOCK FOR WINDOWS', cat: 'system',
      status: 'live', free: true, price: 'ฟรีทุกฟีเจอร์',
      platforms: ['Windows'], tags: ['Magnification', 'Auto-organize', 'Any edge · auto-hide', 'Clock widget'],
      palette: ['#5ef2ff', '#16123a', '#b57cff'], planet: 2,
      art: art('macdock'),
      shots: mock('macdock', ['dock', 'menu', 'side', 'clock', 'license']),
      tagline: 'Dock สไตล์ macOS สำหรับ Windows ไอคอนขยายตามเมาส์ ฟรีทุกฟีเจอร์',
      pitch: ['ไอคอนขยายตามเมาส์ จัดเป็นหมวดให้เอง วางได้ทั้ง 4 ขอบจอ', 'ซ่อนไอคอนเดสก์ท็อปและแถบงาน แล้วคืนให้ครบเมื่อปิด Dock'],
      desc: 'Dock สไตล์ macOS สำหรับ Windows 10/11 ไอคอนขยายตามเมาส์ เห็นแอปที่เปิดอยู่ จัดไอคอนเป็นหมวดให้เอง วางได้ทั้ง 4 ขอบจอพร้อมซ่อนอัตโนมัติ มีนาฬิกาตั้งโต๊ะ ซ่อนไอคอนเดสก์ท็อปและแถบงานให้หน้าจอโล่ง แล้วคืนทุกอย่างกลับเมื่อปิด Dock ไฟล์เดียว ไม่ต้องติดตั้ง ไม่ต้องลง .NET ไม่ต้องใช้สิทธิ์ผู้ดูแล ฟรีทุกฟีเจอร์ไม่จำกัดเวลา ไม่มีรุ่นเสียเงิน ส่วนไลเซ่นฟรีมีไว้เปิดการอัปเดตอัตโนมัติเท่านั้น',
      features: ['ไอคอนขยายตามเมาส์', 'จัดไอคอนเป็นหมวดให้เอง', 'วางได้ 4 ขอบจอ + ซ่อนอัตโนมัติ', 'นาฬิกาตั้งโต๊ะ', 'ซ่อนไอคอนเดสก์ท็อป + แถบงาน', 'ไม่ต้องติดตั้ง ไม่ต้องลง .NET'],
      href: SHOP + '/products/macdock', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: SHOP + '/macdock/download', downloadLabel: 'ดาวน์โหลดฟรี',
      shop: 'macdock', api: 'macdock', version: null, released: null,
      nova: [
        'MacDock ทำให้ Windows มี Dock แบบ Mac เลยนะ ไอคอนขยายตามเมาส์ น่ารักมากค่ะ~',
        'ฟรีทุกฟีเจอร์ ไม่มีรุ่นเสียเงิน โหลดไปใช้ได้เลย ไม่ต้องล็อกอินด้วยนะ ✦',
      ],
    },
    /* SmsChecker sells per term, but its numbers come only from the shop's live table
       (plansKey): nothing is seeded here, so its price shows once the shop answers.
       Sales are closed in the shop's admin (2026-09-23) and its pages answer 404 until
       they reopen: `closed` hides the links, the shop's status lifts it. */
    {
      id: 'smschecker', name: 'SmsChecker', sub: 'BANK TRANSFER AUTO-CONFIRM', cat: 'mobile',
      status: 'soon', closed: true, free: false, price: '[[{from} · ]]ทดลองฟรี 24 ชม.', plansKey: 'smschecker', plans: {},
      platforms: ['Android'], tags: ['14 Thai banks', 'Satang matching', 'No root', 'AES-256-GCM'],
      palette: ['#5ce98a', '#0a2a20', '#5ec8d6'], planet: 1,
      art: art('smschecker'),
      shots: mock('smschecker', ['dashboard', 'orders', 'sms', 'qr', 'wp-orders']),
      tagline: 'ยืนยันยอดโอนจาก SMS ธนาคารอัตโนมัติ จากมือถือของร้าน',
      pitch: ['อ่าน SMS และแจ้งเตือนธนาคาร จับคู่บิลด้วยเศษสตางค์', 'อนุมัติออเดอร์บนเว็บร้านให้เอง ไม่ต้อง Root'],
      desc: 'แอป Android 8.0 ขึ้นไปสำหรับมือถือที่ร้านใช้รับเงิน อ่าน SMS จากธนาคารไทย 14 แห่งและพร้อมเพย์ รวมถึงการแจ้งเตือนจากแอปธนาคาร เป๋าตัง ถุงเงิน และ LINE Pay แล้วส่งให้เว็บร้าน จับคู่บิลด้วยยอดเศษสตางค์เฉพาะของแต่ละบิล อนุมัติได้ 3 โหมด (อัตโนมัติ ตรวจเอง และโหมดอัจฉริยะ) จับคู่กับเว็บด้วยการสแกน QR ใช้ได้หลายเว็บจากมือถือเครื่องเดียว อ่านยอดออกเสียง เข้ารหัส AES-256-GCM ไม่ต้อง Root เว็บร้านต้องติดตั้งตัวเชื่อม SmsChecker ทดลองฟรี 24 ชั่วโมงทุกฟีเจอร์ ไม่ต้องสมัคร ไม่ต้องผูกบัตร[[ แล้ว {terms}]]',
      features: ['SMS 14 ธนาคาร + พร้อมเพย์', 'แจ้งเตือนแอปธนาคาร · เป๋าตัง · LINE Pay', 'จับคู่บิลด้วยเศษสตางค์', 'อนุมัติ 3 โหมด', 'จับคู่เว็บด้วย QR · หลายเว็บ', 'อ่านยอดออกเสียง'],
      href: SHOP + '/smschecker', hrefLabel: 'ดูหน้าแอป', external: true,
      download: SHOP + '/smschecker/download', dlPage: true, downloadLabel: 'ทดลองฟรี 24 ชม.',
      shop: 'smschecker', api: 'smschecker', version: null, released: null,
      nova: [
        'ร้านไหนเหนื่อยกับการเช็กสลิป SmsChecker อ่าน SMS ธนาคารแล้วอนุมัติออเดอร์ให้เองเลยค่ะ!',
        'จับคู่บิลด้วยเศษสตางค์ ยอดไหนของบิลไหนรู้ทันที ลองฟรี 24 ชั่วโมงก่อนได้นะ ✦',
      ],
    },
  ];

  var FEATURED = [
    { id: 'brainx', hook: 'สมองที่สองแบบ 3 มิติ', sector: 'BUILD · SOON', node: 'NEURAL CORE' },
    { id: 'winx-tools', hook: 'คุมเน็ตถึงระดับ kernel', sector: 'BUILD · v1.0.2', node: 'SYSTEM LAYER' },
    { id: 'cluadex', hook: 'AI โค้ดบนเครื่องคุณ', sector: 'BUILD · v3.0.54', node: 'CODE FORGE' },
    { id: 'chanthra-studio', hook: 'สตูดิโอวิดีโอ AI', sector: 'BUILD · v0.11.0', node: 'LUNAR ATELIER' },
    { id: 'gpuxmine', hook: 'การ์ดจอทำงานแทนคุณ', sector: 'BUILD · v0.1.18', node: 'GPU GRID' },
  ];

  /* Release log: real versions from `product_versions`, newest first. The
     dialog asks the live API for the current one, so this only seeds the page. */
  var RELEASES = [
    { id: 'gpuxmine', v: '0.1.18', date: '2026-09-25', title: 'ปล่อยเวอร์ชัน 0.1.18', items: ['ติดตั้งครั้งเดียว จากนั้นอัปเดตตัวเองอัตโนมัติ', 'ตัวติดตั้ง Windows 86 MB ส่งจาก xman4289.com'] },
    { id: 'chanthra-studio', v: '0.11.0', date: '2026-09-24', title: 'Node flow รันงานได้จริง', items: ['ติดตั้งและรัน ComfyUI ในแอปได้เอง', 'Node flow ครบทุกโหนด พร้อมเทมเพลต', 'สร้างเพลงบน GPU แทนการจ่ายรายเพลง', 'อัปเดตในแอปจาก xman4289.com'] },
    { id: 'aipray', v: '1.2.5', date: '2026-09-24', title: 'อัปเดตในแอปได้แล้ว', items: ['อัปเดตเวอร์ชันใหม่ได้จากในแอปเลย', 'บทสวด 20+ บท ใช้ออฟไลน์ได้'] },
    { id: 'winx-tools', v: '1.0.2', date: '2026-09-24', title: 'แพ็กเกจมีลายเซ็นดิจิทัล', items: ['ตรวจลายเซ็นก่อนติดตั้งอัปเดตทุกครั้ง', 'ผู้ใช้เดิมอัปเดตได้ใน Settings → Updates'] },
    { id: 'autotradex', v: '0.3.0', date: '2026-09-24', title: 'รุ่น Portable', items: ['รุ่น Portable ไม่ต้องติดตั้ง .NET', 'รองรับ Windows 10/11 x64'] },
    { id: 'cluadex', v: '3.0.54', date: '2026-08-31', title: 'ตัวติดตั้งใหม่ อัปเดตเองเบื้องหลัง', items: ['อัปเดตแบบ delta เบื้องหลัง ใช้ตอนเปิดใหม่', 'ยังมีรุ่น Portable แตกไฟล์แล้วใช้ได้เลย'] },
    { id: 'localvpn', v: '1.0.39', date: '2026-04-01', title: 'License + Auto Update', items: ['ระบบ License และอัปเดตอัตโนมัติ', 'NAT traversal'] },
    { id: 'tping', v: '1.2.102', date: '2026-03-25', title: 'Captcha puzzle อัตโนมัติ', items: ['แก้ captcha แบบเลื่อนชิ้นส่วนอัตโนมัติ', 'Export / Import workflow + Cloud sync'] },
  ];

  /* ---------------------------------------------------------------- prices
     A product's numbers live in ONE place: `plans` (THB per term, the shop's
     config/licenses.php → plans, keyed by `plansKey`, default `shop`) or
     `amount` (a single price, products.price). Every text that states a price is
     a template, rendered from those numbers here and again when the live shop
     data arrives:
       {monthly} {yearly} {lifetime} {price}  one number, "฿1,990"
       {terms}  every term sold, "฿299/เดือน, ฿1,990/ปี หรือ ฿19,900 ตลอดชีพ"
       {from}   "เริ่ม ฿299/เดือน" (just "฿199/ปี" when one term is sold)
       {addonSoon} " (เร็ว ๆ นี้)" while an add-on's own shop row is not on sale
     [[ … ]] is a clause that disappears when a number in it is missing, so a term
     the shop stops selling leaves no "฿0" or gap behind. A line whose number is
     missing outside [[ ]] is dropped (features, Nova's lines). */
  var TERMS = [
    { k: 'monthly', th: '/เดือน', en: '/month' },
    { k: 'yearly', th: '/ปี', en: '/year' },
    { k: 'lifetime', th: ' ตลอดชีพ', en: ' lifetime' },
  ];

  /** ฿1,990 — whole baht without decimals, else two. */
  function baht(n) {
    var s = Math.round(n) === n ? String(n) : n.toFixed(2);
    return '฿' + s.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  /** Only the terms the hub knows, as positive numbers. */
  function cleanTerms(o) {
    var t = {};
    if (o && typeof o === 'object') {
      TERMS.forEach(function (d) {
        var v = Number(o[d.k]);
        if (isFinite(v) && v > 0) t[d.k] = v;
      });
    }
    return t;
  }

  /** The values a template can ask for, in Thai or English. null = not sold. */
  function priceVals(p, lang) {
    var en = lang === 'en';
    var plans = p.plans || {};
    var list = TERMS.filter(function (d) { return plans[d.k]; }).map(function (d) { return baht(plans[d.k]) + (en ? d.en : d.th); });
    if (!list.length && p.amount > 0) list = [baht(p.amount)];
    var last = list[list.length - 1];
    var v = {
      price: p.amount > 0 ? baht(p.amount) : null,
      terms: !list.length ? null : list.length === 1 ? last : list.slice(0, -1).join(', ') + (en ? ' or ' : ' หรือ ') + last,
      from: !list.length ? null : list.length === 1 ? last : (en ? 'from ' : 'เริ่ม ') + list[0],
      addonSoon: p.addon && p.addon.soon ? (en ? ' (coming soon)' : ' (เร็ว ๆ นี้)') : '',
    };
    TERMS.forEach(function (d) { v[d.k] = plans[d.k] ? baht(plans[d.k]) : null; });
    return v;
  }

  /** Fill a template; null when a number outside [[ ]] is missing. */
  function render(tpl, vals) {
    if (typeof tpl !== 'string') return tpl;
    var missing = false;
    var fill = function (s) {
      return s.replace(/\{(\w+)\}/g, function (m, k) {
        var v = vals[k];
        if (v == null) { missing = true; return ''; }
        return v;
      });
    };
    var out = tpl.replace(/\[\[([\s\S]*?)\]\]/g, function (m, inner) {
      var outer = missing;
      missing = false;
      var r = fill(inner);
      var drop = missing;
      missing = outer;
      return drop ? '' : r;
    });
    out = fill(out);
    return missing ? null : out.replace(/ {2,}/g, ' ').trim();
  }

  /** A template with every number taken out (the last resort, never "undefined"). */
  function strip(tpl) {
    return String(tpl || '').replace(/\[\[[\s\S]*?\]\]/g, '').replace(/\{\w+\}/g, '').replace(/ {2,}/g, ' ').trim();
  }

  /** Every price-bearing text of a product, from its numbers and status as they are now. */
  function renderProduct(p) {
    var t = p.tpl;
    var v = priceVals(p, 'th');
    var sold = !!(v.terms);
    var label = render(t.price, v);
    // written as free, but the shop now asks a price: say the shop's price
    if (!/\{/.test(t.price) && !(p.plans && Object.keys(p.plans).length) && p.amount > 0 && !(p.base.amount > 0)) label = v.price;
    p.price = label || v.from || (sold ? strip(t.price) : 'ดูราคาที่หน้าสินค้า');
    p.tagline = render(t.tagline, v) || strip(t.tagline);
    p.desc = render(t.desc, v) || strip(t.desc);
    p.pitch = t.pitch.map(function (s) { return render(s, v) || strip(s); });
    p.features = t.features.map(function (s) { return render(s, v); }).filter(function (s) { return s; });
    var nova = t.nova.map(function (s) { return render(s, v); }).filter(function (s) { return s; });
    if (!nova.length) nova = [p.name + ' — ' + p.tagline];
    p.nova = p.status === p.base.status ? nova : flippedLines(p, p.status);
  }

  var byId = {};
  PRODUCTS.forEach(function (p, i) {
    p.index = i;
    byId[p.id] = p;
    p.plans = p.plans ? cleanTerms(p.plans) : null;
    p.amount = p.amount > 0 ? p.amount : 0;
    if (p.addon) p.addon = { shop: p.addon.shop, soon: !!p.addon.soon };
    // the authored texts are templates; p.price / desc / … hold them rendered
    p.tpl = {
      price: p.price, tagline: p.tagline, desc: p.desc,
      pitch: p.pitch.slice(), features: p.features.slice(), nova: p.nova.slice(),
    };
    // what this file says, so the live data is always applied to it and never on top of itself
    p.base = {
      status: p.status, closed: !!p.closed, shots: p.shots.slice(), version: p.version, released: p.released,
      download: p.download, downloadLabel: p.downloadLabel, dlPage: !!p.dlPage,
      plans: p.plans ? Object.assign({}, p.plans) : null, amount: p.amount, addonSoon: p.addon ? p.addon.soon : false,
    };
    renderProduct(p);
  });
  var catOf = {};
  CATS.forEach(function (c) { catOf[c.id] = c; c.count = PRODUCTS.filter(function (p) { return p.cat === c.id; }).length; });

  // one object for the whole page: the live data updates it in place
  var counts = {};
  function recount() {
    counts.all = PRODUCTS.length;
    counts.live = PRODUCTS.filter(function (p) { return p.status === 'live'; }).length;
    counts.free = PRODUCTS.filter(function (p) { return p.free; }).length;
    counts.soon = PRODUCTS.filter(function (p) { return p.status === 'soon'; }).length;
    counts.shots = PRODUCTS.reduce(function (n, p) { return n + p.shots.length; }, 0);
  }
  recount();

  /* ---------------------------------------------------------------- live shop data
     GET /api/v1/catalog (CORS *) → { success, products: [{ slug, status, image_url,
     real_image, version, download_url, url, … }] }, matched on `shop`:
     - status: on_sale → live; coming_soon / off_sale → soon. The shop wins.
       off_sale also sets `closed`: the shop answers 404 for that product's pages,
       so no link points there.
     - real_image: image_url is a real screenshot set in the shop's admin → it goes
       first in `shots` (LIVE CAPTURE badge, card hover, dialog gallery, 3D screen).
       real_image false = only the shop's fallback art: ignored, the hub's art stays.
     - version: the latest active version wins over the one written here (null =
       keep this file's). Its release date is not sent, so a newer one shows none.
     - download_url: fills a product that has no `download` here, only while it is
       live and only an https address on xman4289.com. A download written here is
       never swapped for another one.
     - prices: the top-level `plans` (the shop's whole terms table, so `brainx` is
       there without a product row) wins for each product's `plansKey`: a term it no
       longer lists goes, and a key it drops takes every term with it. A row's
       own `plans` is used when the table is missing; its
       `price` is the single price of a product sold without terms.
     - a product the shop does not list keeps what this file says.
     One request per page load at most, kept 10 minutes in sessionStorage; never
     waited on: the page renders from this file, then `xph:catalog` says what
     changed. Any failure (offline, 404, timeout, bad JSON) leaves this file as is. */
  var LIVE_URL = SHOP + '/api/v1/catalog';
  var LIVE_KEY = 'xph.catalog';
  var LIVE_TTL = 10 * 60 * 1000;
  var STATUS = { on_sale: 'live', coming_soon: 'soon', off_sale: 'soon' };

  /** A real picture's URL, only if it is one: an https address (relative ones are the shop's). */
  function realImage(row) {
    if (!row || row.real_image !== true || typeof row.image_url !== 'string' || !row.image_url) return null;
    try {
      var u = new URL(row.image_url, SHOP);
      return u.protocol === 'https:' ? u.href : null;
    } catch (e) {
      return null;
    }
  }

  /** A version string that reads as one ("1.4.0", "0.11.0-beta"); a leading "v" is dropped. */
  function liveVersion(row) {
    if (!row || typeof row.version !== 'string') return null;
    var v = row.version.trim().replace(/^v/i, '');
    return /^[0-9][0-9A-Za-z.+-]{0,31}$/.test(v) ? v : null;
  }

  /** The shop's public download, only if it is served by xman4289.com (owner's rule: never GitHub). */
  function liveDownload(row) {
    if (!row || typeof row.download_url !== 'string' || !row.download_url) return null;
    try {
      var u = new URL(row.download_url);
      var host = u.hostname.toLowerCase();
      var ours = host === 'xman4289.com' || /\.xman4289\.com$/.test(host);
      return u.protocol === 'https:' && ours && !/github/i.test(u.href) ? u.href : null;
    } catch (e) {
      return null;
    }
  }

  /** The button for a download the shop supplied, worded like the ones written above. */
  function shopDlLabel(p) {
    var apk = p.platforms.length === 1 && p.platforms[0] === 'Android';
    return (apk ? 'ดาวน์โหลด APK' : 'ดาวน์โหลด') + (p.free ? (apk ? ' ฟรี' : 'ฟรี') : '');
  }

  /** Nova's lines for a product the shop has moved off the status this file wrote it for. */
  function flippedLines(p, status) {
    return status === 'live'
      ? [p.name + ' เปิดให้ใช้แล้วนะคะ! ' + p.tagline, 'ดูรายละเอียดของ ' + p.name + ' ได้ที่หน้าสินค้าเลยค่ะ ข้อมูลสดจากร้านนะ ✦']
      : [p.name + ' ตอนนี้เป็น Coming Soon ค่ะ ยังดาวน์โหลดหรือซื้อไม่ได้นะ เปิดเมื่อไหร่โนวาจะบอกเลย~'];
  }

  /** The terms a product sells now, from the shop's table (or its row), else as written here. */
  function liveTerms(p, row, table) {
    var key = p.plansKey !== undefined ? p.plansKey : p.shop;
    if (key && table && Object.prototype.hasOwnProperty.call(table, key)) return cleanTerms(table[key]);
    if (row && row.plans && Object.keys(cleanTerms(row.plans)).length) return cleanTerms(row.plans);
    // the table is here and no longer lists the product: it sells no term any more
    if (key && table && p.base.plans) return {};
    return p.base.plans ? Object.assign({}, p.base.plans) : null;
  }

  /** Lay the shop's answer over this file. Returns the ids whose status, pictures, version, download or prices changed. */
  function applyLive(data) {
    var rows = data.products || [];
    var table = data.plans && typeof data.plans === 'object' && !Array.isArray(data.plans) ? data.plans : null;
    var bySlug = {};
    rows.forEach(function (r) { if (r && typeof r.slug === 'string') bySlug[r.slug] = r; });
    live.rows = bySlug;
    var changed = [];
    PRODUCTS.forEach(function (p) {
      var b = p.base;
      var row = p.shop ? bySlug[p.shop] : null;
      var status = row && STATUS[row.status] ? STATUS[row.status] : b.status;
      var closed = row && STATUS[row.status] ? row.status === 'off_sale' : b.closed;
      var real = realImage(row);
      var shots = real ? [real].concat(b.shots.filter(function (s) { return s !== real; })) : b.shots.slice();
      var version = liveVersion(row) || b.version;
      // a download written here stays; the shop's only fills a gap, and only while it is on sale
      var shopDl = !b.download && status === 'live' ? liveDownload(row) : null;
      var download = b.download || shopDl;
      var price = row && row.price != null && row.price !== '' && isFinite(Number(row.price)) && Number(row.price) >= 0 ? Number(row.price) : b.amount;
      var sig = function (q) {
        return [q.status, !!q.closed, q.shots.join('\n'), q.version, q.download, q.price, q.desc, q.features.join('\n'), q.nova.join('\n')].join('|');
      };
      var before = sig(p);
      p.status = status;
      p.closed = closed;
      p.shots = shots;
      p.version = version;
      p.released = version === b.version ? b.released : null;
      p.download = download;
      p.downloadLabel = shopDl ? shopDlLabel(p) : b.downloadLabel;
      p.dlPage = shopDl ? false : b.dlPage;
      p.plans = liveTerms(p, row, table);
      p.amount = price;
      if (p.addon) {
        var ar = bySlug[p.addon.shop];
        p.addon.soon = ar && STATUS[ar.status] ? STATUS[ar.status] !== 'live' : b.addonSoon;
      }
      renderProduct(p);
      if (sig(p) !== before) changed.push(p.id);
    });
    recount();
    return changed;
  }

  function readCache() {
    try {
      var c = JSON.parse(sessionStorage.getItem(LIVE_KEY) || 'null');
      var age = c ? Date.now() - c.at : -1;
      if (c && Array.isArray(c.products) && age >= 0 && age < LIVE_TTL) return { products: c.products, plans: c.plans || null };
    } catch (e) {}
    return null;
  }

  /** Only what the hub reads, so the cache stays small. */
  function slim(r) {
    return {
      slug: r.slug, status: r.status, image_url: r.image_url, real_image: r.real_image === true,
      version: typeof r.version === 'string' ? r.version : null, download_url: typeof r.download_url === 'string' ? r.download_url : null,
      price: r.price != null ? r.price : null, plans: r.plans && typeof r.plans === 'object' ? r.plans : null,
    };
  }

  var live = { source: 'static', changed: [], rows: {} };

  function fetchLive() {
    if (!window.fetch) return;
    var ctl = window.AbortController ? new AbortController() : null;
    var timer = setTimeout(function () { if (ctl) ctl.abort(); }, 8000);
    fetch(LIVE_URL, { headers: { Accept: 'application/json' }, credentials: 'omit', signal: ctl ? ctl.signal : undefined })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) {
        clearTimeout(timer);
        if (!j || j.success !== true || !Array.isArray(j.products)) return;
        var data = {
          products: j.products.filter(function (r) { return r && typeof r.slug === 'string'; }).map(slim),
          plans: j.plans && typeof j.plans === 'object' && !Array.isArray(j.plans) ? j.plans : null,
        };
        try { sessionStorage.setItem(LIVE_KEY, JSON.stringify({ at: Date.now(), products: data.products, plans: data.plans })); } catch (e) {}
        live.source = 'fresh';
        live.changed = applyLive(data);
        if (live.changed.length) window.dispatchEvent(new CustomEvent('xph:catalog', { detail: { changed: live.changed.slice() } }));
      })
      .catch(function () { clearTimeout(timer); });
  }

  // a fresh copy from this session is laid on before anything renders; else ask the shop once
  var cached = readCache();
  if (cached) {
    live.source = 'cache';
    live.changed = applyLive(cached);
  } else {
    fetchLive();
  }

  window.XPH = window.XPH || {};
  window.XPH.catalog = {
    SHOP: SHOP, CATS: CATS, PRODUCTS: PRODUCTS, FEATURED: FEATURED, RELEASES: RELEASES,
    byId: function (id) { return byId[id]; },
    cat: function (id) { return catOf[id]; },
    /** A mock-up drawn to show a screen's layout, not a capture of the app: label it MOCKUP. */
    isMock: function (url) { return !!MOCKS[url]; },
    /** How many of a product's pictures are real captures (the shop's own picture counts). */
    realShots: function (p) { return p.shots.filter(function (u) { return !MOCKS[u]; }).length; },
    counts: counts,
    live: live,
    /** For the other pages (classic.html, brainx.html): a text with price tokens, filled from a product's
        numbers in Thai or English — the same table, the same live data. */
    fill: function (tpl, id, lang) {
      var p = byId[id];
      if (!p) return strip(tpl);
      return render(tpl, priceVals(p, lang)) || strip(tpl);
    },
    /** The short price for a grid chip: "เริ่ม ฿299/เดือน", "฿199/ปี", "฿7,990"; null when nothing is sold. */
    short: function (id, lang) {
      var p = byId[id];
      return p ? priceVals(p, lang).from : null;
    },
  };
})();
