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

    /** Public URL of a portfolio picture. */
    public static function img(string $file): string
    {
        return asset('artwork/portfolio/'.$file).'?v='.self::IMG_V;
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

        return $files;
    }
}
