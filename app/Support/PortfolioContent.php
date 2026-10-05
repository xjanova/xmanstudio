<?php

namespace App\Support;

/**
 * The portfolio: work that is live right now, shown with real screenshots.
 *
 * Every entry is a site anyone can open today, and every picture under
 * public_html/artwork/portfolio/ is a capture of that site (desktop 1440×900,
 * phone 390×844 at 2×) — no mock-ups. Re-capture when a site changes its look,
 * and bump IMG_V so Cloudflare lets go of the old file.
 *
 * Claims stay to what the work actually is (see the BrainX notes per project):
 * no invented client counts, revenue or traffic. Tailwind classes live in the
 * Blade view only — Tailwind does not scan this file — so colours here are
 * plain hex values the view puts into CSS variables.
 */
class PortfolioContent
{
    public const IMG_V = '20261005';

    /** @return array<int, array<string, mixed>> The case studies, in the order the page shows them. */
    public static function featured(): array
    {
        return [
            [
                'id' => 'thaiprompt',
                'name' => 'Thai Prompt',
                'url' => 'https://main.thaiprompt.online',
                'domain' => 'thaiprompt.online',
                'accent' => '#f5c56b',
                'accent2' => '#1e2a6b',
                'type_th' => 'ซูเปอร์แอปของคนไทย',
                'type_en' => 'Super-app platform',
                'client_th' => 'แพลตฟอร์ม Thai Prompt — เว็บ + แอป Android',
                'client_en' => 'Thai Prompt platform — web + Android app',
                'summary_th' => 'ทุกเรื่องใกล้ตัว จบในที่เดียว: ช้อปออนไลน์ ตลาดสด ร้านใกล้บ้าน ไรเดอร์ส่งของ เปิดร้านขายของ ดูดวง และระบบชวนเพื่อนรับค่าคอมมิชชัน ในบัญชีเดียว',
                'summary_en' => 'Shopping, fresh market, local food, riders, seller shops, fortune telling and referral earnings — one account.',
                'built' => [
                    ['th' => 'ร้านค้าออนไลน์ ตะกร้า ชำระเงิน และกระเป๋าเงินในตัว', 'en' => 'Online store, cart, checkout and wallet'],
                    ['th' => 'ตลาดสด ร้านใกล้ฉัน และระบบไรเดอร์รับส่งของ', 'en' => 'Fresh market, nearby food and rider delivery'],
                    ['th' => 'ศูนย์ผู้ขาย: เปิดร้าน จัดการสินค้าและออเดอร์เอง', 'en' => 'Seller centre for shop owners'],
                    ['th' => 'ระบบแนะนำเพื่อนและค่าคอมมิชชันหลายชั้น', 'en' => 'Referral and multi-level commission'],
                    ['th' => 'แอป Android ของตัวเอง และ API สำหรับเครื่อง POS หน้าร้าน', 'en' => 'Own Android app and a POS terminal API'],
                ],
                'facts' => [
                    ['value' => 'Web + App', 'th' => 'ใช้บัญชีเดียวกันทั้งเว็บและแอป', 'en' => 'One account on web and app'],
                    ['value' => 'POS', 'th' => 'เชื่อมเครื่องขายหน้าร้าน', 'en' => 'Shop-front terminals'],
                    ['value' => 'AI', 'th' => 'แม่หมอจันทราดูดวงด้วย AI', 'en' => 'AI fortune teller'],
                ],
                'stack' => ['Laravel', 'MySQL', 'React Native (Expo)', 'REST API', 'AI chatbots'],
                'images' => [
                    ['file' => 'thaiprompt-desktop.webp', 'th' => 'หน้าแรก', 'en' => 'Home'],
                    ['file' => 'thaiprompt-app-desktop.webp', 'th' => 'แอป Thai Prompt', 'en' => 'The app'],
                ],
                'mobile' => 'thaiprompt-mobile.webp',
                'links' => [],
            ],
            [
                'id' => 'kyc-ginger',
                'name' => 'กอย่งเชียงกรุ๊ป',
                'name_en' => 'KYC Group · Ginger',
                'url' => 'https://kycgroups-ginger.com',
                'domain' => 'kycgroups-ginger.com',
                'accent' => '#f59e0b',
                'accent2' => '#14532d',
                'type_th' => 'เว็บไซต์บริษัทส่งออก',
                'type_en' => 'Corporate website',
                'client_th' => 'บริษัท กอย่งเชียงกรุ๊ป จำกัด — รับซื้อ-ขายและส่งออกขิงสด จ.เพชรบูรณ์',
                'client_en' => 'KYC Group Co., Ltd. — fresh ginger trader and exporter, Phetchabun',
                'summary_th' => 'ย้ายเว็บบริษัทออกจาก WordPress เดิมที่เสี่ยงเรื่องความปลอดภัย มาเป็นเว็บใหม่ที่เร็วและปลอดภัย โดยรักษาอันดับค้นหาคำว่า “ซื้อขายขิง” ไว้ได้',
                'summary_en' => 'Moved off a risky WordPress install to a fast, secure site — and kept its search ranking.',
                'built' => [
                    ['th' => 'ตรวจความปลอดภัยเว็บเดิม แล้วย้ายทั้งเว็บมาเป็นระบบใหม่', 'en' => 'Security audit, then a full migration'],
                    ['th' => 'หน้าแรกพร้อมวิดีโอโรงขิง และมาตรฐาน GHPs · HACCP · GLOBALG.A.P.', 'en' => 'Home with the factory video and certifications'],
                    ['th' => 'หน้าสินค้าแนะนำ Ginger World พร้อมเลข อย. สั่งซื้อผ่าน LINE หรือโทร', 'en' => 'Ginger World product page, order by LINE or phone'],
                    ['th' => 'ฟอร์มติดต่อกันสแปม และหน้าภาษาอังกฤษสำหรับลูกค้าต่างประเทศ', 'en' => 'Spam-protected contact form and an English site'],
                    ['th' => 'SEO ครบ: sitemap, structured data และ redirect ลิงก์เดิมไม่ให้หาย', 'en' => 'SEO: sitemap, structured data, old links redirected'],
                ],
                'facts' => [
                    ['value' => '~0.1 s', 'th' => 'เวลาตอบสนองหน้าแรก (TTFB)', 'en' => 'Time to first byte'],
                    ['value' => '18/18', 'th' => 'ผ่านการตรวจความปลอดภัยหลังย้าย', 'en' => 'Security checks passed'],
                    ['value' => 'TH / EN', 'th' => 'สองภาษา', 'en' => 'Bilingual'],
                ],
                'stack' => ['Astro', 'Cloudflare Workers', 'Turnstile', 'Schema.org SEO'],
                'images' => [
                    ['file' => 'kyc-desktop.webp', 'th' => 'หน้าแรก', 'en' => 'Home'],
                    ['file' => 'kyc-products-desktop.webp', 'th' => 'สินค้าแนะนำ', 'en' => 'Products'],
                ],
                'mobile' => 'kyc-mobile.webp',
                'links' => [],
            ],
            [
                'id' => 'atmos',
                'name' => 'ATMOS 3D',
                'url' => 'https://atmos.xman4289.com',
                'domain' => 'atmos.xman4289.com',
                'accent' => '#38bdf8',
                'accent2' => '#4c1d95',
                'type_th' => 'ลูกโลกสภาพอากาศ 3 มิติ',
                'type_en' => '3D weather globe',
                'client_th' => 'ผลงานของ XMAN Studio — ใช้ได้ฟรีบนเบราว์เซอร์',
                'client_en' => 'An XMAN Studio build — free in the browser',
                'summary_th' => 'ลูกโลก 3 มิติที่แสดงสภาพอากาศจริงทั่วโลกแบบเรียลไทม์ พร้อมพยากรณ์น้ำท่วม 7 วันรายจังหวัดของไทย จากสถานีวัดน้ำจริงและโมเดลแม่น้ำ',
                'summary_en' => 'Live global weather on a 3D globe, plus a 7-day flood outlook for every Thai province.',
                'built' => [
                    ['th' => 'ลูกโลก WebGL พร้อมชั้นข้อมูลลม ฝน เมฆ อุณหภูมิ และคุณภาพอากาศ', 'en' => 'WebGL globe with wind, rain, cloud, temperature and air quality'],
                    ['th' => 'ข้อมูลพยากรณ์จริงจาก Open-Meteo อัปเดตอัตโนมัติ', 'en' => 'Real forecast data, refreshed automatically'],
                    ['th' => 'พยากรณ์น้ำท่วม 7 วันรายจังหวัด จากสถานีวัดน้ำทั่วประเทศ + โมเดล GloFAS', 'en' => '7-day provincial flood outlook from river gauges + GloFAS'],
                    ['th' => 'หน้าเช็กน้ำท่วมแบบง่าย เลือกจังหวัดหรือกดหาตำแหน่งตัวเอง', 'en' => 'A simple flood page: pick a province or locate me'],
                    ['th' => 'ระบบหลังบ้านภาษาไทย และปรับความเร็วให้ลื่นบนเครื่องทั่วไป', 'en' => 'Thai admin console, tuned to run smoothly'],
                ],
                'facts' => [
                    ['value' => '7 วัน', 'th' => 'พยากรณ์น้ำท่วมล่วงหน้า', 'en' => 'Flood outlook ahead'],
                    ['value' => '700+', 'th' => 'สถานีวัดน้ำที่ใช้ข้อมูลจริง', 'en' => 'River gauges feeding it'],
                    ['value' => 'Live', 'th' => 'ข้อมูลอากาศจริง', 'en' => 'Real weather data'],
                ],
                'stack' => ['TypeScript', 'three.js / WebGL', 'Vite', 'PHP', 'MySQL', 'Open-Meteo'],
                'images' => [
                    ['file' => 'atmos-desktop.webp', 'th' => 'ลูกโลก 3 มิติ', 'en' => '3D globe'],
                    ['file' => 'atmos-flood-desktop.webp', 'th' => 'พยากรณ์น้ำท่วม', 'en' => 'Flood outlook'],
                ],
                'mobile' => 'atmos-mobile.webp',
                'links' => [
                    ['href' => 'https://atmos.xman4289.com/flood/', 'th' => 'เช็กน้ำท่วม 7 วัน', 'en' => 'Flood outlook'],
                ],
            ],
            [
                'id' => 'tpix',
                'name' => 'TPIX',
                'url' => 'https://tpix.online',
                'domain' => 'tpix.online',
                'accent' => '#22d3ee',
                'accent2' => '#1e3a8a',
                'type_th' => 'บล็อกเชนและกระดานเทรด',
                'type_en' => 'Blockchain & DEX',
                'client_th' => 'ระบบนิเวศ TPIX — เชนของตัวเอง กระดานเทรด และ Explorer',
                'client_en' => 'The TPIX ecosystem — own chain, exchange and explorer',
                'summary_th' => 'TPIX TRADE กระดานเทรดแบบกระจายศูนย์ เทรดจากกระเป๋าของผู้ใช้เองโดยไม่ต้องฝากเงินไว้กับใคร บนเชน TPIX ที่เราตั้งขึ้นเองและ BNB Smart Chain',
                'summary_en' => 'TPIX TRADE, a non-custodial DEX on our own TPIX Chain and BNB Smart Chain.',
                'built' => [
                    ['th' => 'TPIX Chain บล็อกเชนของตัวเอง พร้อม validator และ masternode', 'en' => 'Our own chain with validators and masternodes'],
                    ['th' => 'สัญญา DEX (AMM) ของตัวเอง สลับเหรียญผ่าน Router บนเชน', 'en' => 'Own AMM contracts: factory, pairs, router'],
                    ['th' => 'เชื่อมกระเป๋า MetaMask / WalletConnect และเทรดฝั่ง BSC', 'en' => 'MetaMask / WalletConnect, trading on BSC too'],
                    ['th' => 'Block explorer สาธารณะ ดูบล็อก ธุรกรรม และกระเป๋าคลัง', 'en' => 'Public block explorer for blocks, txs and treasury'],
                    ['th' => 'ผู้ช่วย AI มาสคอต “น้อง TPIX” ตอบคำถามบนเว็บ', 'en' => 'An AI mascot assistant on the site'],
                ],
                'facts' => [
                    ['value' => '4289', 'th' => 'Chain ID ของ TPIX Chain', 'en' => 'TPIX Chain ID'],
                    ['value' => '2.0 s', 'th' => 'เวลาต่อบล็อกโดยเฉลี่ย', 'en' => 'Average block time'],
                    ['value' => 'DEX', 'th' => 'ไม่ต้องฝากเงินไว้กับใคร', 'en' => 'Non-custodial'],
                ],
                'stack' => ['Laravel', 'Vue', 'Solidity', 'Polygon Edge', 'Blockscout', 'BNB Smart Chain'],
                'images' => [
                    ['file' => 'tpix-desktop.webp', 'th' => 'TPIX TRADE', 'en' => 'Exchange'],
                    ['file' => 'tpix-explorer-desktop.webp', 'th' => 'Explorer', 'en' => 'Block explorer'],
                ],
                'mobile' => 'tpix-mobile.webp',
                'links' => [
                    ['href' => 'https://explorer.tpix.online', 'th' => 'ดู Explorer', 'en' => 'Explorer'],
                ],
            ],
            [
                'id' => 'genlotto',
                'name' => 'GenLotto Lab',
                'url' => 'https://genlotto.xman4289.com',
                'domain' => 'genlotto.xman4289.com',
                'accent' => '#4ade80',
                'accent2' => '#1e3a8a',
                'type_th' => 'แลปสถิติสลากและโหราศาสตร์',
                'type_en' => 'Lottery statistics lab',
                'client_th' => 'ผลงานของ XMAN Studio — ใช้บัญชีและกระเป๋าเงินเดียวกับ xman4289.com',
                'client_en' => 'An XMAN Studio build — one account and wallet with xman4289.com',
                'summary_th' => 'ห้องแลปวิเคราะห์สลากกินแบ่งรัฐบาลจากสถิติทุกงวดและตำแหน่งดาว ณ เวลาออกรางวัล ทดสอบสูตรย้อนหลังทุกงวดแล้วบอกตรง ๆ ว่าแม่นแค่ไหนเทียบกับการสุ่ม',
                'summary_en' => 'Every past draw plus the sky at draw time — and every formula back-tested honestly against chance.',
                'built' => [
                    ['th' => 'ดึงผลสลากทุกงวดตั้งแต่ปี 2553 จากกองสลากอัตโนมัติ', 'en' => 'Every draw since 2010, fetched automatically'],
                    ['th' => 'คำนวณตำแหน่งดาว ลัคนา และฤกษ์ยามตามตำราโหรไทย', 'en' => 'Planet positions, ascendant and Thai auspicious times'],
                    ['th' => 'ทดสอบสูตรย้อนหลังทุกงวด แสดงผลเทียบโอกาสสุ่มอย่างตรงไปตรงมา', 'en' => 'Formulas back-tested on every draw, shown against chance'],
                    ['th' => 'โหมด VIP ดวงส่วนตัว ล็อกอินด้วย XMAN ID จ่ายด้วยกระเป๋าเงิน XMAN', 'en' => 'Personal VIP mode on XMAN ID and the XMAN wallet'],
                    ['th' => 'หน้าตาแบบ Windows XP ห้องแลปดาว 3 มิติ และติดตั้งเป็นแอปได้ (PWA)', 'en' => 'An XP-style desktop, a 3D star lab, installable as an app'],
                ],
                'facts' => [
                    ['value' => '15+ ปี', 'th' => 'สถิติสลากย้อนหลัง', 'en' => 'Years of draw history'],
                    ['value' => '10 ดาว', 'th' => 'คำนวณตำแหน่งดาวทุกงวด', 'en' => 'Planets computed per draw'],
                    ['value' => 'PWA', 'th' => 'ติดตั้งบนมือถือได้', 'en' => 'Installs on a phone'],
                ],
                'stack' => ['PHP 8.3', 'MariaDB', 'three.js', 'PWA', 'XMAN ID (SSO)'],
                'images' => [
                    ['file' => 'genlotto-desktop.webp', 'th' => 'เลขงวดถัดไป', 'en' => 'Next draw'],
                    ['file' => 'genlotto-stats-desktop.webp', 'th' => 'สถิติทุกงวด', 'en' => 'Statistics'],
                    ['file' => 'genlotto-ruek-desktop.webp', 'th' => 'ฤกษ์ยาม', 'en' => 'Auspicious times'],
                ],
                'mobile' => 'genlotto-mobile.webp',
                'links' => [],
            ],
            [
                'id' => 'aquachord',
                'name' => 'AquaChord',
                'url' => 'https://aquachord.online',
                'domain' => 'aquachord.online',
                'accent' => '#3df5d0',
                'accent2' => '#4c1d95',
                'type_th' => 'แกะคอร์ดเพลงด้วย AI',
                'type_en' => 'AI chord studio',
                'client_th' => 'ผลงานของ XMAN Studio — ใช้ฟรีบนเบราว์เซอร์ ติดตั้งเป็นแอปได้',
                'client_en' => 'An XMAN Studio build — free in the browser, installable as an app',
                'summary_th' => 'เลือกไฟล์เพลงแล้วให้ AI แกะคอร์ดและเนื้อร้องให้ วิเคราะห์ในเครื่องผู้ใช้ทั้งหมด ไม่อัปโหลดเสียงขึ้นเซิร์ฟเวอร์ ใช้ออฟไลน์ได้ มีน้อง Aqua เป็นไกด์',
                'summary_en' => 'Pick a song and AI writes out its chords and lyrics, entirely on your device: no upload, works offline.',
                'built' => [
                    ['th' => 'ถอดคอร์ดจากไฟล์เสียง จับ BPM ชดเชยจูนเพี้ยน แยกเบส และหาคีย์', 'en' => 'Chords from audio: tempo, tuning offset, bass, key'],
                    ['th' => 'ถอดเนื้อร้องด้วยโมเดล Whisper ที่รันในเบราว์เซอร์ แล้ววางคอร์ดเหนือคำร้อง', 'en' => 'In-browser Whisper lyrics with chords placed above'],
                    ['th' => 'แผ่นคอร์ดเปลี่ยนคีย์และคาโป้ได้ เลื่อนอัตโนมัติ ฟังเสียงคอร์ด', 'en' => 'Chord sheets with transpose, capo, autoscroll and playback'],
                    ['th' => 'ห้องคอร์ด: ท่าจับทุกคอร์ด 12 ราก × 15 ชนิด พร้อมคอร์ดในคีย์', 'en' => 'Chord Lab: every shape, 12 roots × 15 qualities'],
                    ['th' => 'ไกด์มาสคอตเคลื่อนไหว ลูกแก้วน้ำ WebGL และติดตั้งเป็นแอป (PWA)', 'en' => 'An animated guide, a WebGL water orb, installable PWA'],
                ],
                'facts' => [
                    ['value' => '100%', 'th' => 'วิเคราะห์ในเครื่อง ไม่อัปโหลดเสียง', 'en' => 'On-device, no upload'],
                    ['value' => '60', 'th' => 'ชนิดคอร์ดที่ถอดได้', 'en' => 'Chord types detected'],
                    ['value' => 'Offline', 'th' => 'ใช้ได้แม้ไม่มีเน็ต', 'en' => 'Works without a connection'],
                ],
                'stack' => ['JavaScript', 'Web Audio DSP', 'Whisper (transformers.js)', 'WebGL', 'PWA'],
                'images' => [
                    ['file' => 'aquachord-desktop.webp', 'th' => 'สตูดิโอแกะเพลง', 'en' => 'Studio'],
                    ['file' => 'aquachord-lab-desktop.webp', 'th' => 'ห้องคอร์ด', 'en' => 'Chord Lab'],
                ],
                'mobile' => 'aquachord-mobile.webp',
                'links' => [],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> More live work, shown as a grid under the case studies. */
    public static function more(): array
    {
        return [
            [
                'id' => 'juntra',
                'name' => 'จันทราพยากรณ์',
                'url' => 'https://xn--82c4af5bzdj.online',
                'domain' => 'จันทรา.online',
                'accent' => '#e9b55a',
                'type_th' => 'ดูดวงด้วย AI',
                'type_en' => 'AI fortune telling',
                'summary_th' => 'เลือกไพ่จาก 78 ใบ แล้วให้แม่หมอจันทรา AI อ่านไพ่และคุยต่อได้ทันที',
                'summary_en' => 'Pick tarot cards and talk them through with an AI fortune teller.',
                'stack' => ['Laravel', 'AI', 'Tarot engine'],
                'image' => 'juntra-desktop.webp',
            ],
            [
                'id' => 'x-dreamer',
                'name' => 'X-DREAMER',
                'url' => 'https://ai.xman4289.com',
                'domain' => 'ai.xman4289.com',
                'accent' => '#a78bfa',
                'type_th' => 'สตูดิโอสร้างภาพและวิดีโอ AI',
                'type_en' => 'AI image & video studio',
                'summary_th' => 'สร้างภาพและวิดีโอจากประโยคเดียว รวมโมเดลชั้นนำหลายผู้ให้บริการไว้ในระบบเครดิตเดียว ล็อกอินด้วยบัญชี XMAN',
                'summary_en' => 'Images and video from one prompt, many leading models, one credit balance.',
                'stack' => ['Next.js', 'Prisma', 'XMAN ID (SSO)'],
                'image' => 'aixman-desktop.webp',
            ],
            [
                'id' => 'xgameshub',
                'name' => 'XMAN GAMES HUB',
                'url' => 'https://xgameshub.xman4289.com',
                'domain' => 'xgameshub.xman4289.com',
                'accent' => '#c0ff4b',
                'type_th' => 'ฮับเกม 3 มิติ',
                'type_en' => '3D games hub',
                'summary_th' => 'จักรวาล 3 มิติรวมโลกเกมของสตูดิโอ เล่นเดโมบนเบราว์เซอร์ได้ทันที มีโนวาเป็นไกด์',
                'summary_en' => 'A 3D universe of our game worlds, playable in the browser, guided by Nova.',
                'stack' => ['Next.js', 'three.js', 'WebGL'],
                'image' => 'xgameshub-desktop.webp',
            ],
            [
                'id' => 'product-hub',
                'name' => 'XMAN Product Hub',
                'url' => 'https://product.xman4289.com',
                'domain' => 'product.xman4289.com',
                'accent' => '#67e8f9',
                'type_th' => 'โชว์รูมซอฟต์แวร์ 3 มิติ',
                'type_en' => '3D software showroom',
                'summary_th' => 'รวมโปรแกรมทุกตัวของสตูดิโอในจักรวาล 3 มิติ หน้าจอโฮโลแกรมโชว์ภาพจริงของโปรแกรม และเวอร์ชันล่าสุดสดจากร้าน',
                'summary_en' => 'Every product we ship, in 3D, with live screenshots and versions.',
                'stack' => ['three.js', 'WebGL', 'Vanilla JS'],
                'image' => 'product-hub-desktop.webp',
            ],
        ];
    }

    /**
     * Mobile apps we built. Every screen is captured from the app's own UI code
     * running (its Flutter / React Native build at phone size, 390×844 @2×) —
     * no mock-ups, no invented data.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function apps(): array
    {
        return [
            [
                'id' => 'thaiprompt-app',
                'name' => 'Thai Prompt APP',
                'accent' => '#f5c56b',
                'accent2' => '#1e2a6b',
                'type_th' => 'ซูเปอร์แอปชุมชน',
                'type_en' => 'Community super-app',
                'platforms' => ['Android'],
                'summary_th' => 'สั่งอาหารและของสดจากตลาดและร้านรถเข็นใกล้บ้าน ช้อปออนไลน์ มีไรเดอร์ในชุมชนส่งถึงมือ พร้อมกระเป๋าเงินและดูดวงฟรี ดีไซน์กรมท่าและทองลายกนก',
                'summary_en' => 'Food and fresh goods from nearby markets and carts, shopping, local riders, a wallet and free tarot.',
                'features' => [
                    'ตลาดสดและร้านรถเข็นใกล้คุณบนแผนที่',
                    'ไรเดอร์ในชุมชน ส่งมอบด้วย QR สองทาง',
                    'เปิดร้านและจัดการออเดอร์ในแอป',
                    'กระเป๋าเงิน เติมด้วย PromptPay QR',
                    'ยืนยันตัวตน eKYC ด้วย AI',
                    'ดูดวงไพ่ทาโรต์ 78 ใบ ฟรี',
                ],
                'stack' => ['React Native', 'Expo', 'TypeScript', 'Laravel API'],
                'screens' => [
                    ['file' => 'app-thaiprompt-1.webp', 'th' => 'หน้าแรก', 'en' => 'Home'],
                    ['file' => 'app-thaiprompt-2.webp', 'th' => 'หน้าต้อนรับ', 'en' => 'Welcome'],
                    ['file' => 'app-thaiprompt-3.webp', 'th' => 'เมนูจากร้านในตลาดสด', 'en' => 'Market menu'],
                ],
                'link' => ['href' => 'https://main.thaiprompt.online', 'th' => 'ดูเว็บ Thai Prompt', 'en' => 'Thai Prompt'],
            ],
            [
                'id' => 'x-dreamer-app',
                'name' => 'X-DREAMER',
                'accent' => '#5eead4',
                'accent2' => '#3b0764',
                'type_th' => 'สตูดิโอ AI บนมือถือ',
                'type_en' => 'Mobile AI studio',
                'platforms' => ['Android'],
                'summary_th' => 'สร้างภาพและวิดีโอด้วย AI บนมือถือ ห้าโหมดในสตูดิโอเดียว ล็อกอินด้วย XMAN ID ใช้บัญชี เครดิต และผลงานเดียวกับเว็บ ai.xman4289.com',
                'summary_en' => 'AI images and video on the phone, five modes, one XMAN ID account shared with the web studio.',
                'features' => [
                    'ภาพ · วิดีโอ · ภาพเป็นวิดีโอ · แก้ภาพ · อัปสเกล 4K',
                    'โมเดลชั้นนำ 40+ จาก 9 ผู้ให้บริการ',
                    'ล็อกอินปุ่มเดียวด้วย XMAN ID',
                    'จ่ายเท่าที่ใช้ ไม่มีรายเดือน',
                    'แกลเลอรีผลงาน ชุมชน และชวนเพื่อนด้วย QR',
                ],
                'stack' => ['Flutter', 'Riverpod', 'Next.js API', 'XMAN ID (SSO)'],
                'screens' => [
                    ['file' => 'app-xdreamer-1.webp', 'th' => 'หน้าต้อนรับ', 'en' => 'Welcome'],
                    ['file' => 'app-xdreamer-2.webp', 'th' => 'ห้าโหมดในสตูดิโอเดียว', 'en' => 'Five modes'],
                    ['file' => 'app-xdreamer-3.webp', 'th' => 'เข้าสู่ระบบด้วย XMAN ID', 'en' => 'Sign in'],
                ],
                'link' => ['href' => 'https://ai.xman4289.com', 'th' => 'ลองบนเว็บ', 'en' => 'Try on the web'],
            ],
            [
                'id' => 'tpix-wallet-app',
                'name' => 'TPIX Wallet',
                'accent' => '#e9b955',
                'accent2' => '#0f2a3d',
                'type_th' => 'กระเป๋าคริปโต',
                'type_en' => 'Crypto wallet',
                'platforms' => ['Android', 'iOS'],
                'summary_th' => 'กระเป๋าเงินทางการของ TPIX Chain ไม่มีค่าแก๊ส ยืนยันเร็ว 2 วินาที ถือหลายกระเป๋าในแอปเดียว เปลี่ยนธีมได้ 5 แบบ สองภาษา',
                'summary_en' => 'The official TPIX Chain wallet: no gas fees, 2-second blocks, many wallets, five themes, Thai and English.',
                'features' => [
                    'กระเป๋าแบบ HD สร้างหรือนำเข้าได้หลายกระเป๋า',
                    'กู้กระเป๋าคืนได้โดยไม่ต้องใช้ seed phrase',
                    'ส่ง/รับด้วย QR พร้อมประวัติธุรกรรม',
                    'สลับเหรียญ bridge และเชื่อม dApp (WalletConnect)',
                    'คีย์เข้ารหัส AES-256 ปลดล็อกด้วย PIN หรือลายนิ้วมือ',
                ],
                'stack' => ['Flutter', 'web3dart', 'WalletConnect v2', 'Solidity'],
                'screens' => [
                    ['file' => 'app-tpix-wallet-1.webp', 'th' => 'หน้าต้อนรับ', 'en' => 'Welcome'],
                    ['file' => 'app-tpix-wallet-2.webp', 'th' => 'หน้าเปิดแอป', 'en' => 'Splash'],
                    ['file' => 'app-tpix-wallet-3.webp', 'th' => 'ธีม Synthwave', 'en' => 'Synthwave theme'],
                ],
                'link' => ['href' => 'https://tpix.online/download', 'th' => 'ดาวน์โหลด', 'en' => 'Download'],
            ],
            [
                'id' => 'tpix-trade-app',
                'name' => 'TPIX TRADE',
                'accent' => '#d4a843',
                'accent2' => '#1c1917',
                'type_th' => 'แอปเทรดคริปโต',
                'type_en' => 'Trading app',
                'platforms' => ['Android'],
                'summary_th' => 'ดูตลาดเรียลไทม์และสลับเหรียญได้จากกระเป๋าของตัวเอง ทั้งบน BNB Smart Chain และ TPIX Chain โดยไม่ต้องฝากเงินไว้กับใคร',
                'summary_en' => 'Live markets and non-custodial swaps on BNB Smart Chain and TPIX Chain, from your own wallet.',
                'features' => [
                    'ราคาเรียลไทม์และกราฟย่อจากตลาดจริง',
                    'สลับเหรียญผ่าน PancakeSwap และ TPIX DEX',
                    'เชื่อมกระเป๋าภายนอกได้กว่า 100 แบบ',
                    'บอทเทรด AI บนคลาวด์ของ TPIX',
                    'ธีมโลหะทองหรือเงิน เลือกสีได้',
                ],
                'stack' => ['Flutter', 'WebSocket', 'web3dart', 'Reown AppKit'],
                'screens' => [
                    ['file' => 'app-tpix-trade-1.webp', 'th' => 'หน้าหลัก', 'en' => 'Home'],
                    ['file' => 'app-tpix-trade-2.webp', 'th' => 'ตลาด', 'en' => 'Markets'],
                    ['file' => 'app-tpix-trade-3.webp', 'th' => 'เลือกธีม', 'en' => 'Themes'],
                ],
                'link' => ['href' => 'https://tpix.online', 'th' => 'ดูเว็บ TPIX TRADE', 'en' => 'TPIX TRADE'],
            ],
            [
                'id' => 'aipray-app',
                'name' => 'Aipray',
                'accent' => '#fbbf24',
                'accent2' => '#422006',
                'type_th' => 'แอปสวดมนต์อัจฉริยะ',
                'type_en' => 'Chanting companion',
                'platforms' => ['Android', 'iOS'],
                'summary_th' => 'ฟังเสียงสวดแล้วเลื่อนบทตามให้อัตโนมัติ นับรอบให้ มีบทสวดมนต์ 21 บท ใช้งานออฟไลน์ได้ และใช้ฟรีตลอดไป',
                'summary_en' => 'Follows your chanting line by voice, counts rounds, 21 chants, works offline, free forever.',
                'features' => [
                    'AI ฟังเสียงแล้วจับบรรทัดที่กำลังสวด',
                    'นับรอบและจับเวลาพร้อมสั่นเตือน',
                    'บทสวดมนต์ 21 บท ค้นหาได้',
                    'สถิติการสวดและวันต่อเนื่อง',
                    'ร่วมพัฒนา AI แบบยินยอมตาม PDPA',
                ],
                'stack' => ['Flutter', 'Speech-to-text', 'Offline-first'],
                'screens' => [
                    ['file' => 'app-aipray-1.webp', 'th' => 'ติดตามบทสวดอัตโนมัติ', 'en' => 'Live chant tracking'],
                    ['file' => 'app-aipray-2.webp', 'th' => 'หน้าหลัก', 'en' => 'Home'],
                    ['file' => 'app-aipray-3.webp', 'th' => 'คลังบทสวดมนต์', 'en' => 'Chant library'],
                ],
                'link' => ['href' => 'https://xman4289.com/apps/aipray', 'th' => 'ดาวน์โหลดฟรี', 'en' => 'Free download'],
            ],
            [
                'id' => 'localvpn-app',
                'name' => 'LocalVPN',
                'accent' => '#22d3ee',
                'accent2' => '#0c4a6e',
                'type_th' => 'วง LAN เสมือนบนมือถือ',
                'type_en' => 'Virtual LAN',
                'platforms' => ['Android'],
                'summary_th' => 'สร้างวง LAN เสมือนข้ามอินเทอร์เน็ต ให้มือถือทุกเครื่องเห็นกันเหมือนอยู่วงเดียว ต่อแบบ P2P ทะลุ NAT และมี VPN WireGuard ให้เลือกประเทศ',
                'summary_en' => 'A virtual LAN across the internet: P2P through NAT, plus WireGuard VPN by country.',
                'features' => [
                    'สร้างหรือเข้าร่วมเครือข่าย ตั้งรหัสผ่านได้',
                    'ต่อตรง P2P ทะลุ NAT สำรองผ่าน relay',
                    'VPN WireGuard เลือกประเทศได้',
                    'แชร์ไฟล์ในวง ตรวจ SHA-256 ทุกชิ้น',
                    'License สแกน QR ได้ มีรุ่นฟรี',
                ],
                'stack' => ['Flutter', 'WireGuard', 'UDP hole punching', 'Laravel API'],
                'screens' => [
                    ['file' => 'app-localvpn-1.webp', 'th' => 'หน้าแรก', 'en' => 'Home'],
                    ['file' => 'app-localvpn-2.webp', 'th' => 'แพ็กเกจ', 'en' => 'Plans'],
                    ['file' => 'app-localvpn-3.webp', 'th' => 'VPN เลือกประเทศ', 'en' => 'VPN'],
                ],
                'link' => ['href' => 'https://xman4289.com/localvpn', 'th' => 'ดูหน้าแอป', 'en' => 'App page'],
            ],
        ];
    }

    /** Public URL of a portfolio picture. */
    public static function img(string $file): string
    {
        return asset('artwork/portfolio/' . $file) . '?v=' . self::IMG_V;
    }

    /** Every picture the page uses — the test checks they all exist. */
    public static function files(): array
    {
        $files = [];
        foreach (self::featured() as $p) {
            foreach ($p['images'] as $im) {
                $files[] = $im['file'];
            }
            $files[] = $p['mobile'];
        }
        foreach (self::more() as $p) {
            $files[] = $p['image'];
        }
        foreach (self::apps() as $a) {
            foreach ($a['screens'] as $sc) {
                $files[] = $sc['file'];
            }
        }

        return $files;
    }
}
