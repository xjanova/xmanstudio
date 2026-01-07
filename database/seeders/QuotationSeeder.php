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
            ['key' => 'music_basic', 'name' => 'AI Background Music', 'name_th' => 'เพลงประกอบ AI (Basic)', 'price' => 50000, 'order' => 1],
            ['key' => 'music_custom', 'name' => 'Custom AI Music Track', 'name_th' => 'สร้างเพลง AI แบบกำหนดเอง', 'price' => 80000, 'order' => 2],
            ['key' => 'music_album', 'name' => 'AI Music Album (10 tracks)', 'name_th' => 'อัลบั้มเพลง AI (10 เพลง)', 'price' => 500000, 'order' => 3],
            ['key' => 'music_voice', 'name' => 'AI Voice Synthesis', 'name_th' => 'สังเคราะห์เสียงร้อง AI', 'price' => 100000, 'order' => 4],
            ['key' => 'music_cover', 'name' => 'AI Music Cover/Remix', 'name_th' => 'ปรับแต่งเพลงด้วย AI', 'price' => 60000, 'order' => 5],
            ['key' => 'music_genre', 'name' => 'Multi-Genre AI Music', 'name_th' => 'เพลง AI หลายแนว', 'price' => 90000, 'order' => 6],
            ['key' => 'music_commercial', 'name' => 'Commercial Music License', 'name_th' => 'ลิขสิทธิ์เพลงเชิงพาณิชย์', 'price' => 150000, 'order' => 7],
            ['key' => 'music_compose', 'name' => 'AI Music Composition System', 'name_th' => 'ระบบแต่งเพลง AI', 'price' => 300000, 'order' => 8],
            ['key' => 'music_mastering', 'name' => 'AI Audio Mastering', 'name_th' => 'มาสเตอร์เสียงด้วย AI', 'price' => 40000, 'order' => 9],
            ['key' => 'music_stem', 'name' => 'AI Stem Separation', 'name_th' => 'แยกแทร็กเพลงด้วย AI', 'price' => 35000, 'order' => 10],
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
            ['key' => 'gen_image', 'name' => 'AI Image Generation', 'name_th' => 'สร้างภาพด้วย AI', 'price' => 80000, 'order' => 1],
            ['key' => 'gen_video', 'name' => 'AI Video Generation', 'name_th' => 'สร้างวิดีโอด้วย AI', 'price' => 150000, 'order' => 2],
            ['key' => 'gen_text', 'name' => 'AI Content Writing', 'name_th' => 'เขียนเนื้อหาด้วย AI', 'price' => 60000, 'order' => 3],
            ['key' => 'gen_avatar', 'name' => 'AI Avatar/Character', 'name_th' => 'สร้าง Avatar ด้วย AI', 'price' => 100000, 'order' => 4],
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
            ['key' => 'chat_basic', 'name' => 'Basic Chatbot', 'name_th' => 'Chatbot พื้นฐาน', 'price' => 50000, 'order' => 1],
            ['key' => 'chat_gpt', 'name' => 'GPT-powered Chatbot', 'name_th' => 'Chatbot ด้วย GPT', 'price' => 100000, 'order' => 2],
            ['key' => 'chat_voice', 'name' => 'Voice Assistant', 'name_th' => 'ผู้ช่วยเสียง AI', 'price' => 120000, 'order' => 3],
            ['key' => 'chat_multi', 'name' => 'Multi-channel Bot', 'name_th' => 'Bot หลายช่องทาง', 'price' => 150000, 'order' => 4],
            ['key' => 'chat_custom', 'name' => 'Custom AI Agent', 'name_th' => 'AI Agent แบบกำหนดเอง', 'price' => 200000, 'order' => 5],
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
            ['key' => 'sc_erc20', 'name' => 'ERC-20 Token Contract', 'name_th' => 'Smart Contract ERC-20 Token', 'price' => 50000, 'order' => 1],
            ['key' => 'sc_erc721', 'name' => 'ERC-721 NFT Contract', 'name_th' => 'Smart Contract NFT ERC-721', 'price' => 80000, 'order' => 2],
            ['key' => 'sc_erc1155', 'name' => 'ERC-1155 Multi-Token', 'name_th' => 'Smart Contract Multi-Token ERC-1155', 'price' => 100000, 'order' => 3],
            ['key' => 'sc_staking', 'name' => 'Staking Contract', 'name_th' => 'Smart Contract Staking', 'price' => 120000, 'order' => 4],
            ['key' => 'nft_marketplace', 'name' => 'NFT Marketplace', 'name_th' => 'ตลาด NFT Marketplace', 'price' => 350000, 'order' => 5],
            ['key' => 'defi_dex', 'name' => 'DEX (Decentralized Exchange)', 'name_th' => 'DEX ระบบแลกเปลี่ยนกระจายศูนย์', 'price' => 500000, 'order' => 6],
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
            ['key' => 'web_landing', 'name' => 'Landing Page', 'name_th' => 'Landing Page (1-5 หน้า)', 'price' => 15000, 'order' => 1],
            ['key' => 'web_corporate', 'name' => 'Corporate Website', 'name_th' => 'เว็บไซต์องค์กร', 'price' => 45000, 'order' => 2],
            ['key' => 'web_ecommerce', 'name' => 'E-commerce Website', 'name_th' => 'เว็บไซต์อีคอมเมิร์ซ', 'price' => 80000, 'order' => 3],
            ['key' => 'web_custom', 'name' => 'Custom Web Application', 'name_th' => 'เว็บแอพพลิเคชั่นแบบกำหนดเอง', 'price' => 100000, 'order' => 4],
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
            ['key' => 'home_automation', 'name' => 'Home Automation System', 'name_th' => 'ระบบอัตโนมัติในบ้าน', 'price' => 150000, 'order' => 1],
            ['key' => 'farm_monitoring', 'name' => 'Smart Farm Monitoring', 'name_th' => 'ระบบติดตามฟาร์มอัจฉริยะ', 'price' => 180000, 'order' => 2],
            ['key' => 'iiot_monitoring', 'name' => 'Industrial Monitoring', 'name_th' => 'ระบบติดตามโรงงาน', 'price' => 350000, 'order' => 3],
            ['key' => 'platform_dashboard', 'name' => 'IoT Dashboard', 'name_th' => 'Dashboard แสดงผล IoT', 'price' => 80000, 'order' => 4],
        ];

        foreach ($iotOptions as $option) {
            QuotationOption::create(array_merge($option, ['quotation_category_id' => $iot->id]));
        }

        $this->command->info('Quotation categories and options seeded successfully!');
    }
}
