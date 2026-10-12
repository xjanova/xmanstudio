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

  var IMG_V = '202610121835';
  var SHOP = 'https://xman4289.com';
  var art = function (id) { return '/assets/art/' + id + '.webp?v=' + IMG_V; };
  var img = function (path) { return path + '?v=' + IMG_V; };

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
     the live catalogue is matched on. null = the shop does not list it (BrainX
     the desktop app is free and not a shop row; `brainx-cloud` is another thing). */
  var PRODUCTS = [
    {
      id: 'brainx', name: 'BrainX', sub: 'NEURAL KNOWLEDGE ENGINE', cat: 'ai',
      status: 'live', free: true, price: 'แอปฟรี[[ · Cloud {from}{addonSoon}]]',
      plansKey: 'brainx', plans: { monthly: 399 }, addon: { shop: 'brainx-cloud', soon: true },
      platforms: ['Windows', 'Cloud'], tags: ['3D knowledge graph', 'Semantic search', 'MCP hub', 'Cloud sync'],
      palette: ['#8b5cf6', '#1a1240', '#67e8f9'], planet: 2,
      art: art('brainx'), shots: [img('/assets/img/hero-hud.jpg'), img('/assets/img/crop-galaxy.jpg')],
      tagline: 'สมองที่สองที่มองเห็นได้ ให้ AI ทุกตัวใช้ความรู้ของคุณ',
      pitch: ['กราฟความรู้ 3 มิติ ค้นด้วยความหมาย', 'และเป็นศูนย์กลาง MCP ให้เอเจนต์ AI ทุกตัว'],
      desc: 'สมองที่สองที่มองเห็นได้ เก็บโน้ตเป็นกราฟความรู้ 3 มิติ ค้นด้วยความหมาย และเป็นศูนย์กลาง MCP ให้ Claude และเอเจนต์ AI ทุกตัวดึงความรู้ของคุณไปใช้ แอปบน Windows ใช้ฟรี[[ ส่วน BrainX Cloud {terms}{addonSoon}]] ใช้กับ Claude ได้ทุกที่',
      features: ['กราฟความรู้ 3 มิติ', 'ค้นหาด้วยความหมาย', 'ศูนย์กลาง MCP ให้เอเจนต์ AI', 'Remote MCP ผ่าน BrainX Cloud', 'พื้นที่ส่วนตัว 1 GB ต่อบัญชี', 'เลือกเองว่าจะอัปโหลดโฟลเดอร์ไหน'],
      href: '/brainx.html', hrefLabel: 'ดูหน้า BrainX', external: false,
      download: SHOP + '/brainx/download', downloadLabel: 'ดาวน์โหลดแอปฟรี',
      shop: null, api: null, version: null, released: '2026-09-24',
      nova: [
        'BrainX คือสมองที่สองของเราเอง~ โน้ตทุกอันกลายเป็นดาวในกราฟ 3 มิติเลยนะ ✦',
        'อยากให้ Claude จำทุกอย่างที่คุณจดไว้? BrainX ทำได้ แอปบนเครื่องใช้ฟรีด้วยค่ะ!',
        'ภาพหน้าจอตรงนี้เป็นของจริงจากแอปเลยนะ ไม่ได้แต่งขึ้นมา ✌',
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
      platforms: ['Windows'], tags: ['Local GGUF', '5 AI providers', '28 agent tools', 'Private by design'],
      palette: ['#6366f1', '#121436', '#a5b4fc'], planet: 3,
      art: art('cluadex'), shots: [],
      tagline: 'ผู้ช่วยเขียนโค้ดที่รันบนเครื่องคุณ ข้อมูลไม่ออกนอกเครื่อง',
      pitch: ['ผู้ช่วยเขียนโค้ด AI ที่รันบนเครื่องคุณ', '5 ผู้ให้บริการ AI · 28 เครื่องมือ · 22+ โมเดลในเครื่อง'],
      desc: 'ผู้ช่วยเขียนโค้ด AI ที่ทำงานบนเครื่องคุณ ข้อมูลเป็นส่วนตัว 100% ใช้ได้ทั้งโมเดล GGUF ในเครื่อง Ollama, OpenAI, Anthropic และ Gemini มี 28 เครื่องมือแบบเอเจนต์ Plan mode, TODO tracking และรีวิวโค้ด ภาษาไทย/อังกฤษ มีรุ่นฟรี[[ ส่วน License {terms}]]',
      features: ['Local GGUF + Ollama', 'OpenAI / Anthropic / Gemini', '28 agent tools', 'Plan mode + TODO tracking', 'Code review', 'อัปเดตตัวเองแบบ delta'],
      href: SHOP + '/cluadex', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: SHOP + '/cluadex/download', downloadLabel: 'ดาวน์โหลดฟรี',
      shop: 'cluadex-ai-coding-assistant', api: 'cluadex-ai-coding-assistant', version: '3.0.54', released: '2026-08-31',
      nova: [
        'CluadeX เขียนโค้ดเป็นเพื่อนคุณได้ทั้งคืน แถมไม่ส่งโค้ดออกนอกเครื่องเลยนะ',
        'ใช้โมเดลในเครื่องก็ได้ ต่อ Claude หรือ GPT ก็ได้ เลือกตามใจเลยค่ะ!',
      ],
    },
    {
      id: 'chanthra-studio', name: 'Chanthra Studio', sub: 'AI VIDEO ATELIER', cat: 'creative',
      status: 'soon', free: false, price: '{from}', plans: { monthly: 399, yearly: 2500, lifetime: 7500 },
      platforms: ['Windows'], tags: ['ComfyUI', 'Node flow', 'TTS + LLM script', 'Auto-post'],
      palette: ['#f5c56b', '#2a1846', '#c084fc'], planet: 0,
      art: art('chanthra-studio'), shots: [],
      tagline: 'สตูดิโอวิดีโอ AI — เขียนบท ถ่ายช็อต ใส่เสียง ตัดต่อ ในแอปเดียว',
      pitch: ['script · shot · score · stitch ครบในแอปเดียว', 'ใช้ ComfyUI ในเครื่อง หรือ provider บนคลาวด์'],
      desc: 'สตูดิโอวิดีโอ AI บน Windows ดีไซน์แบบ lunar atelier — เขียนบทด้วย LLM สร้างช็อตด้วย ComfyUI ในเครื่องหรือ provider บนคลาวด์ ใส่เสียงพากย์ TTS เรนเดอร์ด้วย ffmpeg แล้วโพสต์อัตโนมัติ ต่อโหนดได้ด้วย node-flow editor[[ ราคา {terms}]]',
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
      platforms: ['Windows'], tags: ['AI jobs', 'Power cap', 'Schedule', 'Auto-update'],
      palette: ['#f59e0b', '#1d1530', '#60a5fa'], planet: 3,
      art: art('gpuxmine'), shots: [],
      tagline: 'ให้การ์ดจอทำงาน AI ตอนคุณไม่ได้ใช้ แล้วรับรายได้',
      pitch: ['รับงาน AI อัตโนมัติตามสเปกการ์ดจอของคุณ', 'คืนการ์ดทันทีเมื่อเปิดเกมหรือกลับมาใช้เครื่อง'],
      desc: 'โปรแกรมแชร์การ์ดจอรับงาน AI อัตโนมัติตามสเปกเครื่อง กำหนดเพดานกำลังไฟและตารางเวลาแชร์ได้เอง คืนการ์ดให้ทันทีเมื่อเปิดเกมหรือกลับมาใช้เครื่อง ดูรายได้เทียบค่าไฟแบบเรียลไทม์ และอัปเดตตัวเองอัตโนมัติ',
      features: ['รับงาน AI ตามสเปกการ์ด', 'กำหนดเพดานกำลังไฟ', 'ตั้งตารางเวลาแชร์', 'คืนการ์ดทันทีเมื่อเปิดเกม', 'รายได้เทียบค่าไฟเรียลไทม์', 'อัปเดตตัวเองอัตโนมัติ'],
      href: SHOP + '/products/gpuxmine', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null,
      shop: 'gpuxmine', api: 'gpuxmine', version: '0.1.18', released: '2026-09-25',
      nova: [
        'การ์ดจอว่างอยู่เฉย ๆ เหรอ? ให้ GPUxMINE พามันไปทำงาน AI แล้วรับเงินกลับมาเลย!',
        'เปิดเกมเมื่อไหร่ มันคืนการ์ดให้ทันทีนะ ไม่แย่งเฟรมเรตแน่นอนค่ะ',
      ],
    },
    {
      id: 'autotradex', name: 'AutoTradeX', sub: 'CRYPTO ARBITRAGE BOT', cat: 'commerce',
      status: 'live', free: false, price: 'ทดลองฟรี 7 วัน[[ · {from}]]', plans: { monthly: 299, yearly: 1990, lifetime: 19900 },
      platforms: ['Windows'], tags: ['.NET 8', '6 exchanges', 'Simulation mode', 'Risk controls'],
      palette: ['#34d399', '#0b2420', '#a3e635'], planet: 1,
      art: art('autotradex'), shots: [],
      tagline: 'บอทเทรด arbitrage อัตโนมัติ 6 exchange พร้อมโหมดทดลอง',
      pitch: ['ไล่ส่วนต่างราคาข้าม 6 exchange แบบเรียลไทม์', 'ทดลองเทรดโดยไม่ใช้เงินจริงก่อนได้'],
      desc: 'บอทเทรด crypto arbitrage อัตโนมัติ ดูราคาเรียลไทม์จาก 6 exchange ชั้นนำ มีโหมด simulation ให้ทดลองโดยไม่เสียเงินจริง โหมด live trading, ติดตามกำไรขาดทุนพร้อมกราฟ บันทึกประวัติการเทรด และระบบจัดการความเสี่ยง ทดลองฟรี 7 วัน[[ แล้วเลือกแพ็กเกจ {terms}]]',
      features: ['ราคาเรียลไทม์ 6 exchange', 'Simulation mode', 'Live trading', 'P&L tracking + charts', 'Trade history', 'Risk management'],
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
      platforms: ['Android'], tags: ['Floating overlay', 'Record & replay', 'Game mode', 'Data profiles'],
      palette: ['#22d3ee', '#0b2236', '#5eead4'], planet: 1,
      art: art('tping'), shots: [],
      tagline: 'บันทึกขั้นตอนแล้วเล่นซ้ำได้ถึง 999 รอบ ใช้ได้ทุกแอป',
      pitch: ['บันทึกขั้นตอนครั้งเดียว เล่นซ้ำได้ถึง 999 รอบ', 'มีโหมดเกมพร้อม crosshair overlay'],
      desc: 'แอปช่วยพิมพ์และกดอัตโนมัติบน Android บันทึกขั้นตอนครั้งเดียวแล้วเล่นซ้ำได้ 1–999 รอบ จัดการชุดข้อมูลหลายชุด มีโหมดเกมพร้อม crosshair overlay และหน้าต่างลอยที่ใช้ได้ทุกแอป ทดลองฟรี 24 ชั่วโมง[[ แล้ว {terms}]]',
      features: ['บันทึกขั้นตอนอัตโนมัติ', 'เล่นซ้ำ 1–999 รอบ', 'Data profiles หลายชุด', 'โหมดเกม + crosshair', 'Floating overlay', 'Export / Import workflow'],
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
      platforms: ['Android', 'Windows'], tags: ['WireGuard', 'NAT traversal', 'LAN games', 'Private networks'],
      palette: ['#60a5fa', '#0d1b3d', '#c084fc'], planet: 1,
      art: art('localvpn'), shots: [],
      tagline: 'สร้างวง LAN เสมือนข้ามอินเทอร์เน็ต เข้ารหัส WireGuard',
      pitch: ['คอม Windows กับมือถือ Android อยู่วงแลนเดียวกัน แม้อยู่คนละที่', 'เข้ารหัส WireGuard ทะลุ NAT ได้ทุกเครือข่าย'],
      desc: 'สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต ให้คอม Windows และมือถือ Android เชื่อมกันเหมือนอยู่วงแลนเดียว สแกนหาเครือข่ายที่เปิดให้เข้าร่วม ตั้งรหัสเพื่อความส่วนตัว ส่งข้อมูลถึงกันตรง ๆ เข้ารหัสด้วย WireGuard และทะลุ NAT ได้ เครื่องใหม่ทดลอง Pro ฟรี 30 วัน ครบแล้วใช้รุ่นฟรีต่อได้สูงสุด 5 คนต่อวง ส่วน Pro รองรับ 50 คนต่อวง 1 คีย์ใช้ได้ 2 เครื่อง[[ ราคา {terms}]]',
      features: ['วง LAN เสมือนข้ามเน็ต', 'สแกนหาเครือข่าย', 'ตั้งรหัสเครือข่าย', 'เห็นทุกอุปกรณ์ในวง', 'WireGuard', 'NAT traversal'],
      href: SHOP + '/localvpn', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: SHOP + '/localvpn/download', dlPage: true, downloadLabel: 'ดาวน์โหลดฟรี',
      shop: 'localvpn', api: 'localvpn', version: '1.0.39', released: '2026-04-01',
      nova: [
        'อยากเล่นเกม LAN กับเพื่อนที่อยู่คนละจังหวัด? LocalVPN จัดให้ค่ะ!',
        'เข้ารหัสด้วย WireGuard ด้วยนะ ปลอดภัย แถมใช้ฟรีได้ถึง 5 คน ✌',
      ],
    },
    {
      id: 'anti-x', name: 'Anti X', sub: 'WINDOWS SERVER INTRUSION GUARD', cat: 'system',
      status: 'live', free: true, price: 'ฟรีพื้นฐาน[[ · Pro {from}]]', plans: { monthly: 199, yearly: 1990, lifetime: 9900 },
      platforms: ['Windows', 'Server'], tags: ['Windows service', 'RDP · SSH · DB guard', 'Per-IP firewall', 'Threat feeds'],
      palette: ['#38bdf8', '#0a1838', '#f5c56b'], planet: 2,
      art: art('anti-x'), shots: [],
      tagline: 'กันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์ บล็อกทีละ IP ไม่ล็อกตัวเอง',
      pitch: ['บล็อกการเดารหัส RDP · SSH · ฐานข้อมูล และการสแกนพอร์ตแบบเรียลไทม์', 'บล็อกทีละ IP ไม่ปิดพอร์ต RDP คนที่ต่ออยู่ไม่โดนบล็อก'],
      desc: 'ระบบกันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์ ทำงานเป็นบริการ Windows ตลอดเวลาแม้ปิดหน้าจอหรือตัด RDP ไปแล้ว อ่าน event log และทราฟฟิกของเครื่องสด ๆ แล้วบล็อก IP ที่เดารหัส RDP / SSH / ฐานข้อมูล สแกนพอร์ต แตะพอร์ตอันตราย ยิงหาช่องโหว่เว็บ หรืออยู่ในรายชื่อ IP อันตราย ด้วย Windows Firewall ทีละ IP — ไม่ปิดพอร์ต RDP ทั้งพอร์ต และคนที่กำลังต่อ RDP อยู่ไม่โดนบล็อก รุ่นฟรีมีตัวจับพื้นฐานใช้ได้ตลอด ส่วน Pro ปลดตัวจับขั้นสูงทั้งหมดพร้อมจับเครื่องที่ถูกเจาะ ทดลอง Pro ฟรี 14 วัน[[ แล้ว {terms}]]',
      features: ['บล็อกเดารหัส RDP / SSH / ฐานข้อมูล', 'บล็อกสแกนพอร์ต + พอร์ตอันตราย', 'ไม่ปิดพอร์ต RDP ไม่ล็อกผู้ดูแล', 'ยิงหาช่องโหว่เว็บ · IP อันตราย (Pro)', 'จับเครื่องที่ถูกเจาะ (Pro)', 'อัปเดตในตัว ตรวจลายเซ็นก่อนติดตั้ง'],
      href: SHOP + '/products/anti-x', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: SHOP + '/anti-x/download', downloadLabel: 'ดาวน์โหลดฟรี',
      shop: 'anti-x', api: 'anti-x', version: '1.4.0', released: null,
      nova: [
        'Anti X เฝ้าเซิร์ฟเวอร์ Windows ให้ทั้งคืน ใครมาเดารหัส RDP โดนบล็อกทีละ IP เลยค่ะ!',
        'ไม่ต้องกลัวล็อกตัวเองนะ Anti X ไม่ปิดพอร์ต RDP และคนที่ต่ออยู่ไม่มีวันโดนบล็อกค่ะ',
        'รุ่นฟรีใช้ได้ตลอดเลย อยากได้ตัวจับขั้นสูงค่อยลอง Pro ฟรี 14 วันก่อนก็ได้ ✦',
      ],
    },
    {
      id: 'giggok', name: 'GigGok', sub: '3D AI SECRETARY', cat: 'mobile',
      status: 'live', free: true, price: 'ใช้ฟรี · ไม่ต้องสมัคร',
      platforms: ['Android'], tags: ['3D avatar', 'On-device Gemma', 'Voice chat', 'Answers calls'],
      palette: ['#e879f9', '#1c0f33', '#2dd4bf'], planet: 0,
      art: art('giggok'), shots: [],
      tagline: 'เลขาสาว 3D บนมือถือ คุยได้ พูดได้ รับสายแทน ข้อมูลไม่ออกนอกเครื่อง',
      pitch: ['เลขาสาว 3D คุยด้วยเสียง ตอบเป็นเสียง รับสายแทนได้', 'สมองในเครื่อง (Gemma) บทสนทนาไม่ออกนอกเครื่อง'],
      desc: 'เลขาสาว 3D บนมือถือ Android ผู้ช่วยส่วนตัวที่มีตัวตนจริงบนหน้าจอ คุยด้วยเสียงได้และตอบกลับเป็นเสียง ช่วยรับสายและรับฝากเรื่องแทนคุณ สมองหลักทำงานในเครื่อง (Gemma) ใช้ GPU ของมือถือได้ บทสนทนาและข้อมูลส่วนตัวไม่ต้องส่งออกนอกเครื่อง ใช้ฟรี ไม่ต้องสมัครสมาชิก ทุกเครื่องได้ License ฟรีอัตโนมัติ ส่วนชุดตัวมายด์ซื้อแยกได้ในร้านชุดในแอป',
      features: ['อวาตาร์ 3D ขยับปากตามเสียง', 'สมองในเครื่อง (Gemma) ใช้ GPU ได้', 'ไมค์ถอดเสียงในเครื่อง', 'รับสายแทน รับฝากเรื่อง', 'อ่านปฏิทินของเครื่อง', 'อัปเดตในตัว ตรวจ SHA-256'],
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
      platforms: ['Windows', 'Android'], tags: ['9 platforms', 'Self-learning automation', 'AI content', 'Account pool'],
      palette: ['#a78bfa', '#1a1440', '#f0abfc'], planet: 2,
      art: art('postx-agent'), shots: [],
      tagline: 'โพสต์อัตโนมัติ 9 แพลตฟอร์ม เรียนรู้และซ่อมตัวเองได้',
      pitch: ['โพสต์อัตโนมัติ 9 แพลตฟอร์มพร้อมกัน', 'web automation ที่เรียนรู้เองและซ่อมตัวเองได้'],
      desc: 'ระบบโปรโมตแบรนด์ด้วย AI โพสต์อัตโนมัติ 9 แพลตฟอร์ม จัดการบัญชีเป็นพูลพร้อมหมุนเวียนและสลับเมื่อโดนจำกัด web automation แบบเรียนรู้เอง มีโหมดสอน และซ่อมขั้นตอนเองเมื่อหน้าเว็บเปลี่ยน สร้างคอนเทนต์ด้วย Ollama, Gemini, GPT-4 หรือ Claude',
      features: ['โพสต์ 9 แพลตฟอร์ม', 'Account pool + rotation', 'Auto-failover', 'Teaching mode', 'Self-repair workflow', 'AI content generation'],
      href: SHOP + '/products/postx-agent', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'postx-agent', api: null, version: null, released: null,
      nova: ['PostXAgent จะโพสต์ให้แบรนด์คุณทั้ง 9 แพลตฟอร์มโดยไม่ต้องนั่งกดเอง กำลังเก็บงานค่ะ!'],
    },
    {
      id: 'livexshop-pro', name: 'Live x Shop Pro', sub: 'LIVE COMMERCE', cat: 'commerce',
      status: 'soon', free: false, price: '{price}', amount: 5990,
      platforms: ['Windows', 'Mobile'], tags: ['WPF + MAUI', 'SignalR', 'AI slip OCR', 'Courier links'],
      palette: ['#fb7185', '#2a0f24', '#fbbf24'], planet: 0,
      art: art('livexshop-pro'), shots: [],
      tagline: 'รวมแชทไลฟ์ อ่านสลิปด้วย AI จับสลิปปลอม เชื่อมขนส่งครบ',
      pitch: ['รวมแชท Facebook · TikTok · LINE ไว้ที่เดียว', 'อ่านสลิปด้วย AI จับสลิปปลอม ส่งของต่อได้ทันที'],
      desc: 'แพลตฟอร์มไลฟ์ขายของครบวงจร รวมแชทจาก Facebook Live, TikTok Live และ LINE OA อ่านสลิปโอนเงินด้วย AI OCR จับสลิปปลอม ยืนยันยอดจาก SMS ธนาคาร มี POS หน้าร้าน สต็อก บาร์โค้ด และเชื่อมขนส่ง Kerry, Flash, J&T พร้อม OBS overlay ระหว่างไลฟ์',
      features: ['รวมแชท 3 แพลตฟอร์ม', 'AI OCR อ่านสลิป', 'จับสลิปปลอม', 'POS + สต็อก', 'เชื่อมขนส่ง', 'OBS overlay'],
      href: SHOP + '/products/livexshop-pro', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'livexshop-pro', api: null, version: null, released: null,
      nova: ['แม่ค้าไลฟ์สดต้องชอบ Live x Shop Pro แน่ ๆ สลิปปลอมผ่านไม่ได้เลยค่ะ!'],
    },
    {
      id: 'xcluade-agent', name: 'XcluadeAgent', sub: 'RELEASE SYNC + AI AUTO-FIX', cat: 'ai',
      status: 'soon', free: false, price: '{price}', amount: 3490,
      platforms: ['Server', 'Web'], tags: ['6 AI modes', 'Auto-rollback', 'Multi-project', '5 alert channels'],
      palette: ['#7dd3fc', '#0c1a33', '#a78bfa'], planet: 3,
      art: art('xcluade-agent'), shots: [],
      tagline: 'ซิงก์ release อัตโนมัติ AI แก้ให้ ย้อนกลับเองเมื่อพัง',
      pitch: ['ดึง release ใหม่ขึ้นเซิร์ฟเวอร์ให้อัตโนมัติ', 'AI ช่วยแก้ และย้อนกลับเองเมื่อมีปัญหา'],
      desc: 'บริการซิงก์ release ขึ้นเซิร์ฟเวอร์อัตโนมัติ พร้อมผู้ช่วย AI 6 โหมด (Ollama, Claude, OpenAI) จัดการได้ไม่จำกัดโปรเจกต์ สำรองข้อมูลก่อนทุกครั้ง ย้อนกลับเองเมื่อตรวจพบปัญหา แจ้งเตือน 5 ช่องทาง Discord, Telegram, LINE, Slack และอีเมล รองรับ Laravel, Node.js, React/Vue, Django, .NET',
      features: ['Release sync อัตโนมัติ', 'AI 6 โหมด', 'Auto-rollback', 'Backup ก่อนทุก sync', 'แจ้งเตือน 5 ช่องทาง', 'CLI + web dashboard'],
      href: SHOP + '/products/xcluade-agent', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'xcluade-agent', api: null, version: null, released: null,
      nova: ['XcluadeAgent ดูแลการ deploy แทนคุณ ถ้าพังก็ย้อนกลับเองนะ นอนหลับสบายเลย~'],
    },
    {
      id: 'spiderx', name: 'SpiderX', sub: 'P2P MESH NETWORK', cat: 'network',
      status: 'soon', free: false, price: '{price}', amount: 2990,
      platforms: ['Windows', 'Mac', 'Mobile'], tags: ['No central server', 'X25519 + AES-256-GCM', 'Encrypted calls', 'P2P files'],
      palette: ['#2dd4bf', '#08201f', '#818cf8'], planet: 2,
      art: art('spiderx'), shots: [],
      tagline: 'เครือข่ายกระจายศูนย์ เข้ารหัสปลายทางถึงปลายทาง ไม่ต้องลงทะเบียน',
      pitch: ['เครือข่าย mesh ไม่มีเซิร์ฟเวอร์กลาง', 'แชท โทร แชร์ไฟล์ เข้ารหัสปลายทางถึงปลายทาง'],
      desc: 'เครือข่าย P2P mesh แบบกระจายศูนย์ ไม่มีเซิร์ฟเวอร์กลาง ไม่ต้องลงทะเบียน เข้ารหัสปลายทางถึงปลายทางด้วย X25519 + AES-256-GCM พร้อม perfect forward secrecy มีแชท โทรเสียง กลุ่ม แชร์ไฟล์แบบ P2P และ virtual LAN สำหรับเล่นเกม ทะลุ NAT ได้',
      features: ['ไม่มีเซิร์ฟเวอร์กลาง', 'E2E X25519 + AES-GCM', 'Perfect forward secrecy', 'แชท + โทรเสียง', 'แชร์ไฟล์ P2P', 'Virtual LAN'],
      href: SHOP + '/products/spiderx', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'spiderx', api: null, version: null, released: null,
      nova: ['SpiderX คุยกันได้โดยไม่ผ่านเซิร์ฟเวอร์ใครเลย ส่วนตัวสุด ๆ ค่ะ 🕸'],
    },
    {
      id: 'phonex-manager', name: 'PhoneX Manager', sub: 'ANDROID SERVICE SUITE', cat: 'mobile',
      status: 'soon', free: false, price: '{price}', amount: 1990,
      platforms: ['Windows'], tags: ['ADB / Fastboot', 'Qualcomm · MTK', 'Samsung · Xiaomi', 'Scrcpy mirror'],
      palette: ['#818cf8', '#141a3a', '#67e8f9'], planet: 3,
      art: art('phonex-manager'), shots: [],
      tagline: 'Flash ROM, Backup, IMEI tools สำหรับร้านซ่อมมือถือ',
      pitch: ['เครื่องมือร้านซ่อมมือถือ Android ครบในตัวเดียว', 'รองรับ Qualcomm, MTK, Samsung, Xiaomi'],
      desc: 'ชุดเครื่องมือจัดการอุปกรณ์ Android ครบวงจร ตรวจเครื่องอัตโนมัติผ่าน ADB/Fastboot แฟลชรอมหลายโหมด สำรองแบบเลือกพาร์ทิชัน แก้ boot image จัดการ GPT/MBR เครื่องมือ IMEI ลบแอปขยะ มี hex editor, ADB terminal, logcat และ screen mirroring',
      features: ['Auto-detect ผ่าน ADB/Fastboot', 'Flash ROM หลายโหมด', 'Partition backup', 'Boot image editor', 'IMEI tools', 'Screen mirroring'],
      href: SHOP + '/products/phonex-manager', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'phonex-manager', api: null, version: null, released: null,
      nova: ['ร้านซ่อมมือถือต้องมี PhoneX Manager ไว้สักตัว ทำได้ครบจบในโปรแกรมเดียวค่ะ'],
    },
    {
      id: 'skidrow-killer', name: 'Skidrow Killer', sub: 'MALWARE SCANNER', cat: 'system',
      status: 'soon', free: false, price: '{price}', amount: 299,
      platforms: ['Windows'], tags: ['Real-time', 'Behaviour analysis', 'Registry watch', 'C2 blocking'],
      palette: ['#f43f5e', '#250a14', '#c084fc'], planet: 2,
      art: art('skidrow-killer'), shots: [],
      tagline: 'สแกนมัลแวร์เรียลไทม์ วิเคราะห์พฤติกรรม บล็อกเซิร์ฟเวอร์ C2',
      pitch: ['ป้องกันเครื่องจากแครก โทรจัน และมัลแวร์', 'วิเคราะห์พฤติกรรม เฝ้า registry บล็อก C2'],
      desc: 'สแกนมัลแวร์พร้อมป้องกันแบบเรียลไทม์ วิเคราะห์พฤติกรรมที่น่าสงสัย สแกนลึกหลายระดับด้วย heuristics เฝ้าการแก้ไข registry และบล็อกการเชื่อมต่อไปยังเซิร์ฟเวอร์ควบคุม (C2) ปกป้องเครื่องจากไฟล์แครก โทรจัน และโปรแกรมอันตราย',
      features: ['Real-time protection', 'Behavioural analysis', 'Deep scan + heuristics', 'Registry monitoring', 'Network protection', 'C2 blocking'],
      href: SHOP + '/products/skidrow-killer', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'skidrow-killer', api: null, version: null, released: null,
      nova: ['โหลดของแครกมาแล้วใจไม่ดี? Skidrow Killer ช่วยตรวจให้ค่ะ แต่ซื้อของแท้ดีที่สุดนะ~'],
    },
    {
      id: 'gpusharx', name: 'GPUsharX', sub: 'DECENTRALISED GPU SHARING', cat: 'cloud',
      status: 'soon', free: true, price: 'ผู้แชร์รับ 90%',
      platforms: ['Web', 'Windows'], tags: ['Laravel 12', 'PyQt6 client', '90% payout', 'Anti-cheat'],
      palette: ['#38bdf8', '#0b1a2e', '#34d399'], planet: 3,
      art: art('gpusharx'), shots: [],
      tagline: 'แพลตฟอร์มแชร์การ์ดจอ ผู้แชร์รับ 90% กันโกงหลายชั้น',
      pitch: ['แพลตฟอร์มให้เช่าพลังการ์ดจอแบบกระจายศูนย์', 'ผู้แชร์รับ 90% ของรายได้ กันโกงหลายชั้น'],
      desc: 'แพลตฟอร์มแชร์ GPU แบบกระจายศูนย์ ผู้แชร์ได้ 90% ของรายได้ ค่าแพลตฟอร์ม 10% มีเว็บแพลตฟอร์มพร้อมแดชบอร์ด กระจายงานตามคิวอัจฉริยะ คำนวณรายได้ตามการมีส่วนร่วม ถอนเงินได้ และระบบกันโกงด้วย hardware fingerprint, งานตรวจสุ่ม และ proof-of-work',
      features: ['ผู้แชร์รับ 90%', 'Job queue อัจฉริยะ', 'Benchmark อัตโนมัติ', 'ระบบถอนเงิน', 'Hardware fingerprint', 'Proof-of-work challenges'],
      href: SHOP + '/products/gpusharx', hrefLabel: 'ดูหน้าสินค้า', external: true,
      download: null, downloadLabel: null, shop: 'gpusharx', api: null, version: null, released: null,
      nova: ['GPUsharX คือตลาดเช่าการ์ดจอของเรา ผู้แชร์ได้ส่วนแบ่งตั้ง 90% เลยค่ะ!'],
    },
  ];

  var FEATURED = [
    { id: 'brainx', hook: 'สมองที่สองแบบ 3 มิติ', sector: 'BUILD · LIVE', node: 'NEURAL CORE' },
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
    { id: 'brainx', v: null, date: '2026-09-24', title: 'เปิดตัว BrainX Cloud', items: ['Remote MCP ใช้กับ Claude ได้ทุกที่', 'แอปบนเครื่องดาวน์โหลดฟรีจาก xman4289.com'] },
    { id: 'autotradex', v: '0.3.0', date: '2026-09-24', title: 'รุ่น Portable', items: ['รุ่น Portable ไม่ต้องติดตั้ง .NET', 'รองรับ Windows 10/11 x64'] },
    { id: 'cluadex', v: '3.0.54', date: '2026-08-31', title: 'ตัวติดตั้งใหม่ อัปเดตเองเบื้องหลัง', items: ['อัปเดตแบบ delta เบื้องหลัง ใช้ตอนเปิดใหม่', 'ยังมีรุ่น Portable แตกไฟล์แล้วใช้ได้เลย'] },
    { id: 'localvpn', v: '1.0.39', date: '2026-04-01', title: 'License + Auto Update', items: ['ระบบ License และอัปเดตอัตโนมัติ', 'WireGuard + NAT traversal'] },
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
      status: p.status, shots: p.shots.slice(), version: p.version, released: p.released,
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
      var real = realImage(row);
      var shots = real ? [real].concat(b.shots.filter(function (s) { return s !== real; })) : b.shots.slice();
      var version = liveVersion(row) || b.version;
      // a download written here stays; the shop's only fills a gap, and only while it is on sale
      var shopDl = !b.download && status === 'live' ? liveDownload(row) : null;
      var download = b.download || shopDl;
      var price = row && row.price != null && row.price !== '' && isFinite(Number(row.price)) && Number(row.price) >= 0 ? Number(row.price) : b.amount;
      var sig = function (q) {
        return [q.status, q.shots.join('\n'), q.version, q.download, q.price, q.desc, q.features.join('\n'), q.nova.join('\n')].join('|');
      };
      var before = sig(p);
      p.status = status;
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
