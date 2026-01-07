<?php

namespace Database\Seeders;

use App\Models\QuotationCategory;
use App\Models\QuotationOption;
use Illuminate\Database\Seeder;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // AI Music Generation
        $musicAi = QuotationCategory::create([
            'key' => 'music_ai',
            'name' => 'AI Music Generation',
            'name_th' => 'สร้างเพลงด้วย AI',
            'icon' => '🎵',
            'description' => 'Professional AI-powered music creation services',
            'description_th' => 'บริการสร้างเพลงด้วย AI อย่างมืออาชีพ',
            'order' => 1,
            'is_active' => true,
        ]);

        $musicOptions = [
            [
                'key' => 'music_basic',
                'name' => 'AI Background Music',
                'name_th' => 'เพลงประกอบ AI (Basic)',
                'description' => 'Basic AI-generated background music for videos, presentations, and content. Includes 3 variations, royalty-free license, and delivery in MP3/WAV formats.',
                'description_th' => 'เพลงประกอบสร้างด้วย AI สำหรับวิดีโอ งานนำเสนอ และคอนเทนต์ทั่วไป รวม: เพลง 3 เวอร์ชัน (สั้น/กลาง/ยาว), ลิขสิทธิ์ใช้งานได้ไม่จำกัด, ไฟล์ MP3 และ WAV คุณภาพสูง, สามารถเลือกอารมณ์เพลงได้ (สนุกสนาน/ผ่อนคลาย/ดราม่า)',
                'price' => 50000,
                'order' => 1,
            ],
            [
                'key' => 'music_custom',
                'name' => 'Custom AI Music Track',
                'name_th' => 'สร้างเพลง AI แบบกำหนดเอง',
                'description' => 'Fully customized AI music track tailored to your brand. Includes mood selection, tempo control, instrument preferences, and 5 revisions.',
                'description_th' => 'สร้างเพลงด้วย AI ตามความต้องการเฉพาะของแบรนด์คุณ รวม: กำหนดอารมณ์เพลงได้ละเอียด, เลือกจังหวะ (BPM) ตามต้องการ, เลือกเครื่องดนตรีที่ต้องการ, แก้ไขได้ 5 รอบ, ความยาว 2-5 นาที, ไฟล์ต้นฉบับทุกรูปแบบ',
                'price' => 80000,
                'order' => 2,
            ],
            [
                'key' => 'music_album',
                'name' => 'AI Music Album (10 tracks)',
                'name_th' => 'อัลบั้มเพลง AI (10 เพลง)',
                'description' => 'Complete AI-generated music album with 10 cohesive tracks. Perfect for brands, games, or content creators needing a full soundtrack.',
                'description_th' => 'อัลบั้มเพลงครบชุด 10 เพลง สร้างด้วย AI ในธีมเดียวกัน รวม: เพลง 10 เพลง ความยาว 3-5 นาที/เพลง, ธีมเพลงสอดคล้องกันทั้งอัลบั้ม, ออกแบบ Artwork ปกอัลบั้ม, ลิขสิทธิ์เชิงพาณิชย์เต็มรูปแบบ, เหมาะสำหรับเกม/แบรนด์/ครีเอเตอร์',
                'price' => 500000,
                'order' => 3,
            ],
            [
                'key' => 'music_voice',
                'name' => 'AI Voice Synthesis',
                'name_th' => 'สังเคราะห์เสียงร้อง AI',
                'description' => 'AI-powered vocal synthesis for songs. Create realistic singing voices in multiple languages and styles.',
                'description_th' => 'สร้างเสียงร้องด้วย AI สมจริงระดับมืออาชีพ รวม: เสียงร้องภาษาไทย/อังกฤษ/จีน/ญี่ปุ่น, เลือกสไตล์เสียงได้ (ชาย/หญิง/เด็ก), ปรับโทนเสียงและอารมณ์ได้, รวม Harmony และ Backing Vocal, ไฟล์เสียงร้องแยกจากดนตรี',
                'price' => 100000,
                'order' => 4,
            ],
            [
                'key' => 'music_cover',
                'name' => 'AI Music Cover/Remix',
                'name_th' => 'ปรับแต่งเพลงด้วย AI',
                'description' => 'Transform existing music with AI-powered remixing. Change genre, tempo, or create entirely new versions.',
                'description_th' => 'แปลงเพลงเดิมให้เป็นเวอร์ชันใหม่ด้วย AI รวม: เปลี่ยนแนวเพลง (Pop เป็น Jazz, Rock เป็น EDM), ปรับจังหวะให้เร็ว/ช้าลง, สร้าง Remix เวอร์ชันใหม่, คงคุณภาพเสียงต้นฉบับ, เหมาะสำหรับ DJ และครีเอเตอร์, *ต้องมีลิขสิทธิ์เพลงต้นฉบับ',
                'price' => 60000,
                'order' => 5,
            ],
            [
                'key' => 'music_genre',
                'name' => 'Multi-Genre AI Music',
                'name_th' => 'เพลง AI หลายแนว',
                'description' => 'Single composition available in multiple genre versions. Get your track in Pop, Jazz, Electronic, Classical, and more.',
                'description_th' => 'สร้างเพลงเดียวกันในหลายแนวเพลง รวม: เพลง 1 เพลงใน 5 แนวเพลง (Pop/Jazz/EDM/Classical/Lo-Fi), เหมาะสำหรับทดสอบตลาด, ใช้งานได้หลากหลายโอกาส, ไฟล์แยกทุกแนวเพลง, ลิขสิทธิ์ครอบคลุมทุกเวอร์ชัน',
                'price' => 90000,
                'order' => 6,
            ],
            [
                'key' => 'music_commercial',
                'name' => 'Commercial Music License',
                'name_th' => 'ลิขสิทธิ์เพลงเชิงพาณิชย์',
                'description' => 'Full commercial licensing for AI-generated music. Use in ads, products, broadcasts, and monetized content worldwide.',
                'description_th' => 'ลิขสิทธิ์เพลงเต็มรูปแบบสำหรับใช้เชิงพาณิชย์ รวม: ใช้ในโฆษณา TV/วิทยุ/ออนไลน์, ใช้ในสินค้าและบริการ, เผยแพร่ทั่วโลกไม่จำกัด, ใช้ใน YouTube/TikTok แบบ Monetize ได้, ไม่มีค่า Royalty เพิ่มเติม, สัญญาลิขสิทธิ์ถูกต้องตามกฎหมาย',
                'price' => 150000,
                'order' => 7,
            ],
            [
                'key' => 'music_compose',
                'name' => 'AI Music Composition System',
                'name_th' => 'ระบบแต่งเพลง AI',
                'description' => 'Custom AI system for your organization to generate unlimited music. Includes training, integration, and ongoing support.',
                'description_th' => 'ระบบ AI สำหรับองค์กรของคุณในการสร้างเพลงได้ไม่จำกัด รวม: ติดตั้งระบบ AI แต่งเพลงในองค์กร, ฝึกอบรมทีมงาน 2 วัน, สร้างเพลงได้ไม่จำกัดจำนวน, รองรับหลายแนวเพลง, API เชื่อมต่อระบบอื่น, ซัพพอร์ต 1 ปี, อัพเดท Model ฟรี',
                'price' => 300000,
                'order' => 8,
            ],
            [
                'key' => 'music_mastering',
                'name' => 'AI Audio Mastering',
                'name_th' => 'มาสเตอร์เสียงด้วย AI',
                'description' => 'Professional AI audio mastering service. Enhance loudness, clarity, and polish for streaming-ready quality.',
                'description_th' => 'บริการมาสเตอร์เสียงด้วย AI ระดับมืออาชีพ รวม: ปรับ Loudness ให้ได้มาตรฐาน Spotify/Apple Music, เพิ่มความชัดเจนของเสียง, ลด Noise และเสียงรบกวน, ปรับ EQ และ Compression อัตโนมัติ, ไฟล์คุณภาพ 24-bit WAV, เปรียบเทียบก่อน/หลัง',
                'price' => 40000,
                'order' => 9,
            ],
            [
                'key' => 'music_stem',
                'name' => 'AI Stem Separation',
                'name_th' => 'แยกแทร็กเพลงด้วย AI',
                'description' => 'Separate any song into individual stems (vocals, drums, bass, instruments) using advanced AI technology.',
                'description_th' => 'แยกเพลงออกเป็นแทร็กย่อยด้วย AI รวม: แยกเสียงร้องออกจากดนตรี, แยกกลอง/เบส/เครื่องดนตรี, คุณภาพเสียงสูง (ไม่เพี้ยน), เหมาะสำหรับ Remix/Karaoke/DJ, รองรับไฟล์ทุกรูปแบบ, ส่งมอบภายใน 24 ชั่วโมง',
                'price' => 35000,
                'order' => 10,
            ],
        ];

        foreach ($musicOptions as $option) {
            QuotationOption::create(array_merge($option, ['quotation_category_id' => $musicAi->id]));
        }

        // AI Image Generation
        $aiImage = QuotationCategory::create([
            'key' => 'ai_image',
            'name' => 'AI Image Generation',
            'name_th' => 'สร้างภาพด้วย AI',
            'icon' => '🎨',
            'description' => 'Advanced AI image generation and editing services',
            'description_th' => 'บริการสร้างและแก้ไขภาพด้วย AI',
            'order' => 2,
            'is_active' => true,
        ]);

        $imageOptions = [
            [
                'key' => 'gen_image',
                'name' => 'AI Image Generation',
                'name_th' => 'สร้างภาพด้วย AI',
                'description' => 'Professional AI image generation for marketing, social media, and branding. Includes multiple styles and high-resolution outputs.',
                'description_th' => 'บริการสร้างภาพด้วย AI สำหรับการตลาดและแบรนด์ดิ้ง รวม: สร้างภาพได้ 50 ภาพ, ความละเอียดสูงสุด 4K, หลายสไตล์ (Realistic/Anime/Art/3D), แก้ไขและปรับแต่งได้, ลิขสิทธิ์ใช้งานเชิงพาณิชย์, ไฟล์ PNG/JPG/WebP',
                'price' => 80000,
                'order' => 1,
            ],
            [
                'key' => 'gen_video',
                'name' => 'AI Video Generation',
                'name_th' => 'สร้างวิดีโอด้วย AI',
                'description' => 'AI-powered video creation from text prompts or images. Perfect for ads, social media, and product showcases.',
                'description_th' => 'สร้างวิดีโอด้วย AI จากข้อความหรือภาพ รวม: วิดีโอ 10 คลิป (15-60 วินาที/คลิป), คุณภาพ Full HD หรือ 4K, เพิ่มเสียงและเพลงประกอบได้, เหมาะสำหรับโฆษณา/Social Media, แก้ไขได้ 3 รอบ, รองรับ Vertical/Horizontal/Square',
                'price' => 150000,
                'order' => 2,
            ],
            [
                'key' => 'gen_text',
                'name' => 'AI Content Writing',
                'name_th' => 'เขียนเนื้อหาด้วย AI',
                'description' => 'AI content writing for blogs, marketing copy, product descriptions, and social media posts in Thai and English.',
                'description_th' => 'บริการเขียนเนื้อหาด้วย AI สำหรับธุรกิจ รวม: บทความ 20 ชิ้น (500-1500 คำ/ชิ้น), รองรับภาษาไทยและอังกฤษ, SEO Optimized, เนื้อหาไม่ซ้ำ 100%, โทนเสียงตามแบรนด์, แก้ไขได้ไม่จำกัด, เหมาะสำหรับ Blog/Website/Social Media',
                'price' => 60000,
                'order' => 3,
            ],
            [
                'key' => 'gen_avatar',
                'name' => 'AI Avatar/Character',
                'name_th' => 'สร้าง Avatar ด้วย AI',
                'description' => 'Custom AI-generated avatars and characters for branding, games, or virtual presence. Multiple styles and expressions.',
                'description_th' => 'สร้าง Avatar และตัวละครด้วย AI สำหรับแบรนด์ รวม: ออกแบบ Avatar 1 ตัว, ท่าทางและอารมณ์ 10 แบบ, สไตล์ตามต้องการ (Realistic/Cartoon/Anime/3D), ไฟล์ Vector และ PNG, พื้นหลังโปร่งใส, ใช้สำหรับ VTuber/Game/Marketing ได้, ลิขสิทธิ์เต็มรูปแบบ',
                'price' => 100000,
                'order' => 4,
            ],
        ];

        foreach ($imageOptions as $option) {
            QuotationOption::create(array_merge($option, ['quotation_category_id' => $aiImage->id]));
        }

        // AI Chatbot
        $chatbot = QuotationCategory::create([
            'key' => 'ai_chatbot',
            'name' => 'AI Chatbot',
            'name_th' => 'Chatbot อัจฉริยะ',
            'icon' => '💬',
            'description' => 'Intelligent chatbot solutions powered by AI',
            'description_th' => 'โซลูชัน Chatbot อัจฉริยะด้วย AI',
            'order' => 3,
            'is_active' => true,
        ]);

        $chatbotOptions = [
            [
                'key' => 'chat_basic',
                'name' => 'Basic Chatbot',
                'name_th' => 'Chatbot พื้นฐาน',
                'description' => 'Rule-based chatbot for FAQ and basic customer support. Easy to set up with predefined responses.',
                'description_th' => 'Chatbot แบบกฎเกณฑ์สำหรับตอบคำถามพื้นฐาน รวม: ตั้งค่าคำถาม-คำตอบได้ 100 ชุด, รองรับภาษาไทยและอังกฤษ, เชื่อมต่อ LINE OA หรือ Facebook Messenger, Dashboard ดูสถิติการใช้งาน, ปรับแต่งได้ง่ายผ่าน Admin Panel, ซัพพอร์ต 3 เดือน',
                'price' => 50000,
                'order' => 1,
            ],
            [
                'key' => 'chat_gpt',
                'name' => 'GPT-powered Chatbot',
                'name_th' => 'Chatbot ด้วย GPT',
                'description' => 'Advanced AI chatbot powered by GPT for natural conversations. Understands context and provides intelligent responses.',
                'description_th' => 'Chatbot อัจฉริยะด้วย GPT สามารถสนทนาได้เหมือนมนุษย์ รวม: เข้าใจบริบทและความหมาย, ตอบคำถามซับซ้อนได้, เรียนรู้ข้อมูลธุรกิจของคุณ, รองรับภาษาไทยอย่างเต็มรูปแบบ, เชื่อมต่อ LINE/Facebook/Website, ค่า API รวม 6 เดือน, ซัพพอร์ต 6 เดือน',
                'price' => 100000,
                'order' => 2,
            ],
            [
                'key' => 'chat_voice',
                'name' => 'Voice Assistant',
                'name_th' => 'ผู้ช่วยเสียง AI',
                'description' => 'AI voice assistant for phone systems and smart devices. Speech-to-text and text-to-speech capabilities.',
                'description_th' => 'ระบบผู้ช่วยเสียง AI สำหรับโทรศัพท์และอุปกรณ์อัจฉริยะ รวม: รับสายและตอบด้วยเสียงอัตโนมัติ, แปลงเสียงเป็นข้อความ (STT), แปลงข้อความเป็นเสียง (TTS), เสียงพูดภาษาไทยธรรมชาติ, บันทึกและสรุปการสนทนา, เชื่อมต่อระบบ IVR, ซัพพอร์ต 6 เดือน',
                'price' => 120000,
                'order' => 3,
            ],
            [
                'key' => 'chat_multi',
                'name' => 'Multi-channel Bot',
                'name_th' => 'Bot หลายช่องทาง',
                'description' => 'Unified chatbot across LINE, Facebook, Instagram, Website, and more. Single dashboard to manage all channels.',
                'description_th' => 'Chatbot เชื่อมต่อทุกช่องทางในที่เดียว รวม: LINE Official Account, Facebook Messenger, Instagram DM, Website Live Chat, ระบบ Omnichannel รวมแชททุกช่องทาง, Dashboard จัดการจากที่เดียว, ประวัติการสนทนาข้ามช่องทาง, แจ้งเตือน Admin เมื่อต้องการคน, ซัพพอร์ต 6 เดือน',
                'price' => 150000,
                'order' => 4,
            ],
            [
                'key' => 'chat_custom',
                'name' => 'Custom AI Agent',
                'name_th' => 'AI Agent แบบกำหนดเอง',
                'description' => 'Fully customized AI agent with specific capabilities. Can perform actions, access databases, and integrate with your systems.',
                'description_th' => 'AI Agent ที่ทำงานได้มากกว่าแค่แชท รวม: เชื่อมต่อฐานข้อมูลและระบบภายใน, ทำรายการได้อัตโนมัติ (จองคิว/สั่งซื้อ/ตรวจสอบสถานะ), เรียนรู้จากเอกสารบริษัท, ตอบคำถามเฉพาะทางได้, Function Calling สำหรับงานซับซ้อน, ออกแบบ Workflow ตามต้องการ, Training และ Fine-tuning, ซัพพอร์ต 1 ปี',
                'price' => 200000,
                'order' => 5,
            ],
        ];

        foreach ($chatbotOptions as $option) {
            QuotationOption::create(array_merge($option, ['quotation_category_id' => $chatbot->id]));
        }

        // Blockchain Development
        $blockchain = QuotationCategory::create([
            'key' => 'blockchain',
            'name' => 'Blockchain Development',
            'name_th' => 'พัฒนา Blockchain',
            'icon' => '🔗',
            'description' => 'Comprehensive blockchain and smart contract development',
            'description_th' => 'บริการพัฒนา Blockchain และ Smart Contract',
            'order' => 4,
            'is_active' => true,
        ]);

        $blockchainOptions = [
            [
                'key' => 'sc_erc20',
                'name' => 'ERC-20 Token Contract',
                'name_th' => 'Smart Contract ERC-20 Token',
                'description' => 'Standard ERC-20 fungible token contract for cryptocurrencies, utility tokens, or reward points.',
                'description_th' => 'Smart Contract มาตรฐาน ERC-20 สำหรับสร้าง Token รวม: Token ใช้งานบน Ethereum/Polygon/BSC, กำหนดชื่อ/สัญลักษณ์/จำนวน, ฟังก์ชัน Mint/Burn/Transfer, Owner Controls, ตรวจสอบและ Audit โค้ด, Deploy บน Mainnet/Testnet, Documentation และคู่มือการใช้งาน',
                'price' => 50000,
                'order' => 1,
            ],
            [
                'key' => 'sc_erc721',
                'name' => 'ERC-721 NFT Contract',
                'name_th' => 'Smart Contract NFT ERC-721',
                'description' => 'ERC-721 NFT contract for unique digital collectibles, art, or membership tokens.',
                'description_th' => 'Smart Contract มาตรฐาน ERC-721 สำหรับ NFT รวม: NFT แบบ Unique (1 ชิ้นต่อ Token ID), Metadata บน IPFS, ฟังก์ชัน Mint/Transfer/Burn, Royalty สำหรับ Creator, Whitelist และ Public Sale, Gas Optimized, Deploy และ Verify บน Etherscan, เอกสารครบถ้วน',
                'price' => 80000,
                'order' => 2,
            ],
            [
                'key' => 'sc_erc1155',
                'name' => 'ERC-1155 Multi-Token',
                'name_th' => 'Smart Contract Multi-Token ERC-1155',
                'description' => 'ERC-1155 multi-token standard supporting both fungible and non-fungible tokens in one contract.',
                'description_th' => 'Smart Contract มาตรฐาน ERC-1155 รองรับทั้ง Fungible และ NFT รวม: สร้าง Token หลายประเภทใน Contract เดียว, เหมาะสำหรับ Game Items, Batch Transfer ประหยัด Gas, Supply Management, Metadata Flexible, รองรับ Marketplace มาตรฐาน, Deploy Multi-chain, เอกสารและคู่มือ',
                'price' => 100000,
                'order' => 3,
            ],
            [
                'key' => 'sc_staking',
                'name' => 'Staking Contract',
                'name_th' => 'Smart Contract Staking',
                'description' => 'Staking contract for token holders to earn rewards. Configurable lock periods and reward rates.',
                'description_th' => 'Smart Contract สำหรับระบบ Staking รวม: ฝาก Token เพื่อรับ Reward, กำหนดอัตราดอกเบี้ย (APY) ได้, ตั้งระยะเวลา Lock ได้, Compound Interest Option, Emergency Withdraw, Admin Dashboard, รองรับหลาย Pool, Audit Ready Code',
                'price' => 120000,
                'order' => 4,
            ],
            [
                'key' => 'nft_marketplace',
                'name' => 'NFT Marketplace',
                'name_th' => 'ตลาด NFT Marketplace',
                'description' => 'Complete NFT marketplace platform for buying, selling, and auctioning digital assets.',
                'description_th' => 'แพลตฟอร์มตลาด NFT ครบวงจร รวม: ซื้อ-ขาย NFT แบบ Fixed Price, ระบบประมูล (Auction), รองรับ ERC-721 และ ERC-1155, Lazy Minting (ประหยัด Gas), Collection Management, Creator Royalties, Search และ Filter, Profile ผู้ใช้, Wallet Connect, Admin Panel, รองรับ Multi-chain',
                'price' => 350000,
                'order' => 5,
            ],
            [
                'key' => 'defi_dex',
                'name' => 'DEX (Decentralized Exchange)',
                'name_th' => 'DEX ระบบแลกเปลี่ยนกระจายศูนย์',
                'description' => 'Decentralized exchange with AMM (Automated Market Maker) for token swaps and liquidity pools.',
                'description_th' => 'ระบบแลกเปลี่ยน Token แบบกระจายศูนย์ รวม: Swap Token ทันที (AMM Model), สร้าง Liquidity Pool, ระบบ LP Token และ Reward, Price Oracle Integration, Slippage Protection, Multi-hop Routing, Analytics Dashboard, Farm และ Yield Features, Gas Optimized, Security Audit, Multi-chain Support',
                'price' => 500000,
                'order' => 6,
            ],
        ];

        foreach ($blockchainOptions as $option) {
            QuotationOption::create(array_merge($option, ['quotation_category_id' => $blockchain->id]));
        }

        // Web Development
        $web = QuotationCategory::create([
            'key' => 'web_development',
            'name' => 'Web Development',
            'name_th' => 'พัฒนาเว็บไซต์',
            'icon' => '🌐',
            'description' => 'Professional web development services',
            'description_th' => 'บริการพัฒนาเว็บไซต์มืออาชีพ',
            'order' => 5,
            'is_active' => true,
        ]);

        $webOptions = [
            [
                'key' => 'web_landing',
                'name' => 'Landing Page',
                'name_th' => 'Landing Page (1-5 หน้า)',
                'description' => 'Professional landing page for marketing campaigns, product launches, or lead generation.',
                'description_th' => 'Landing Page สำหรับการตลาดและโปรโมชัน รวม: ออกแบบ 1-5 หน้า, Responsive ทุกอุปกรณ์, SEO พื้นฐาน, ฟอร์มติดต่อ/สมัครสมาชิก, เชื่อมต่อ Google Analytics, ภาพและไอคอนฟรี, SSL Certificate, โฮสติ้ง 1 ปี (ถ้าต้องการ), ส่งมอบภายใน 7 วัน',
                'price' => 15000,
                'order' => 1,
            ],
            [
                'key' => 'web_corporate',
                'name' => 'Corporate Website',
                'name_th' => 'เว็บไซต์องค์กร',
                'description' => 'Complete corporate website with company profile, services, team, and contact sections.',
                'description_th' => 'เว็บไซต์องค์กรครบวงจร รวม: 10-15 หน้า (หน้าแรก/เกี่ยวกับเรา/บริการ/ทีมงาน/ข่าวสาร/ติดต่อ), ระบบจัดการเนื้อหา (CMS), Responsive Design, SEO Friendly, Multi-language (TH/EN), Google Maps Integration, Social Media Links, Contact Form พร้อม Email แจ้งเตือน, Blog/News Section, ซัพพอร์ต 3 เดือน',
                'price' => 45000,
                'order' => 2,
            ],
            [
                'key' => 'web_ecommerce',
                'name' => 'E-commerce Website',
                'name_th' => 'เว็บไซต์อีคอมเมิร์ซ',
                'description' => 'Full-featured e-commerce website with product management, cart, checkout, and payment integration.',
                'description_th' => 'เว็บไซต์ขายของออนไลน์ครบวงจร รวม: ระบบจัดการสินค้า (ไม่จำกัดจำนวน), ตะกร้าสินค้าและ Checkout, ชำระเงินผ่าน PromptPay/บัตรเครดิต/โอนเงิน, ระบบสมาชิกและประวัติการสั่งซื้อ, คูปองส่วนลด, คำนวณค่าจัดส่ง, Stock Management, รายงานยอดขาย, แจ้งเตือน LINE/Email, Admin Dashboard, ซัพพอร์ต 6 เดือน',
                'price' => 80000,
                'order' => 3,
            ],
            [
                'key' => 'web_custom',
                'name' => 'Custom Web Application',
                'name_th' => 'เว็บแอพพลิเคชั่นแบบกำหนดเอง',
                'description' => 'Tailor-made web application built to your exact specifications with custom features and integrations.',
                'description_th' => 'พัฒนาเว็บแอพพลิเคชันตามความต้องการ รวม: วิเคราะห์และออกแบบระบบ, UI/UX Design, พัฒนาฟีเจอร์ตามต้องการ, ระบบ Login และจัดการผู้ใช้, Database Design, API Development, Third-party Integration, Responsive Design, Testing และ QA, Documentation, Training, ซัพพอร์ต 6 เดือน, *ราคาเริ่มต้น ขึ้นอยู่กับความซับซ้อน',
                'price' => 100000,
                'order' => 4,
            ],
        ];

        foreach ($webOptions as $option) {
            QuotationOption::create(array_merge($option, ['quotation_category_id' => $web->id]));
        }

        // IoT Solutions
        $iot = QuotationCategory::create([
            'key' => 'iot',
            'name' => 'IoT Solutions',
            'name_th' => 'โซลูชัน IoT',
            'icon' => '⚡',
            'description' => 'Internet of Things solutions for smart devices',
            'description_th' => 'โซลูชัน IoT สำหรับอุปกรณ์อัจฉริยะ',
            'order' => 6,
            'is_active' => true,
        ]);

        $iotOptions = [
            [
                'key' => 'home_automation',
                'name' => 'Home Automation System',
                'name_th' => 'ระบบอัตโนมัติในบ้าน',
                'description' => 'Smart home automation system for lighting, climate, security, and appliance control via mobile app.',
                'description_th' => 'ระบบ Smart Home ควบคุมบ้านผ่านมือถือ รวม: ควบคุมไฟ/แอร์/เครื่องใช้ไฟฟ้า, ตั้งเวลาและ Automation, เซ็นเซอร์ตรวจจับการเคลื่อนไหว, ควบคุมผ่าน App iOS/Android, สั่งงานด้วยเสียง (Google/Alexa), กล้องวงจรปิดเชื่อมต่อ, แจ้งเตือนเมื่อมีเหตุผิดปกติ, รวมอุปกรณ์พื้นฐาน, ติดตั้งและ Training',
                'price' => 150000,
                'order' => 1,
            ],
            [
                'key' => 'farm_monitoring',
                'name' => 'Smart Farm Monitoring',
                'name_th' => 'ระบบติดตามฟาร์มอัจฉริยะ',
                'description' => 'Agricultural IoT system for monitoring soil, weather, irrigation, and crop conditions.',
                'description_th' => 'ระบบ IoT สำหรับเกษตรอัจฉริยะ รวม: เซ็นเซอร์วัดความชื้นดิน/อากาศ/แสง, ระบบรดน้ำอัตโนมัติ, สถานีวัดอากาศ, Dashboard แสดงผลแบบ Real-time, แจ้งเตือนผ่าน LINE/App, ประวัติข้อมูลย้อนหลัง, พลังงานแสงอาทิตย์ (Solar), รวมอุปกรณ์และติดตั้ง, ซัพพอร์ต 1 ปี',
                'price' => 180000,
                'order' => 2,
            ],
            [
                'key' => 'iiot_monitoring',
                'name' => 'Industrial Monitoring',
                'name_th' => 'ระบบติดตามโรงงาน',
                'description' => 'Industrial IoT (IIoT) solution for monitoring machinery, production lines, and predictive maintenance.',
                'description_th' => 'ระบบ IIoT สำหรับโรงงานอุตสาหกรรม รวม: ติดตามสถานะเครื่องจักร Real-time, วัดค่าพลังงาน/อุณหภูมิ/ความสั่นสะเทือน, OEE Dashboard, Predictive Maintenance Alert, เชื่อมต่อ PLC/SCADA, รายงานการผลิต, ประวัติข้อมูลย้อนหลัง, API เชื่อมต่อ ERP, ติดตั้งและ Training, ซัพพอร์ต 1 ปี',
                'price' => 350000,
                'order' => 3,
            ],
            [
                'key' => 'platform_dashboard',
                'name' => 'IoT Dashboard',
                'name_th' => 'Dashboard แสดงผล IoT',
                'description' => 'Custom IoT dashboard platform for visualizing sensor data, alerts, and device management.',
                'description_th' => 'แพลตฟอร์ม Dashboard สำหรับแสดงผลข้อมูล IoT รวม: Dashboard แบบ Real-time, กราฟและ Widget หลากหลาย, จัดการอุปกรณ์ IoT, ตั้ง Alert และ Notification, รายงานและ Export ข้อมูล, User Management, Responsive Design, API รับข้อมูลจากอุปกรณ์, รองรับ MQTT/HTTP, White-label ได้, ซัพพอร์ต 6 เดือน',
                'price' => 80000,
                'order' => 4,
            ],
        ];

        foreach ($iotOptions as $option) {
            QuotationOption::create(array_merge($option, ['quotation_category_id' => $iot->id]));
        }

        $this->command->info('Quotation categories and options seeded successfully!');
    }
}
