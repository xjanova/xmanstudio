<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Chanthra Studio — ลงทะเบียนเป็นสินค้า "เร็ว ๆ นี้" (ยังซื้อไม่ได้) พร้อมผูก GitHub release
 *
 * หน้า /chanthra-studio มีปุ่ม "ดาวน์โหลดฟรี" มานานแล้ว แต่ production ไม่มีสินค้า slug นี้เลย ปุ่มจึงทำได้แค่
 * 302 ไปหน้า releases บน GitHub = บอกลูกค้าว่า repo อยู่ไหน (กฎเจ้าของ 2026-09-24 ห้ามเด็ดขาด)
 * มีแถวนี้ + GitHub setting แล้ว /chanthra-studio/download ส่งไฟล์ zip จาก xman4289.com เองได้ทันทีหลัง deploy
 *
 * เจ้าของเลือก (2026-09-24): ลงทะเบียนแบบ coming soon — ยังไม่เปิดขาย (หน้าราคายังต้องแก้ปุ่มซื้อ
 * และคีย์ CHS-… อีกรอบ) เปิดขายเมื่อไหร่ก็ปิด "เร็ว ๆ นี้" ในหน้า admin ได้เลย ไม่ต้อง deploy
 * ค่าในแถวเดียวกับ ChanthraStudioSeeder (ราคา = รายปี ฿2,500 ซึ่งเป็น license ตั้งต้นของสินค้านี้)
 *
 * ทำเป็น migration เพราะ deploy รันแค่ `migrate --force` ไม่รัน seeder (เหมือน gpuxmine/brainx)
 * มีแถวอยู่แล้ว = ไม่แตะแถวสินค้า แค่เติม GitHub setting ถ้ายังไม่มี · รันซ้ำกี่รอบก็ได้ผลเท่าเดิม
 * repo xjanova/chanthra-studio เป็น public ไม่ต้องมี token (token ที่ตายแล้วแย่กว่าไม่มี — ดู 2026_09_18_000002)
 */
return new class extends Migration
{
    private const SLUG = 'chanthra-studio';

    private const SKU = 'CHS-001';

    public function up(): void
    {
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id')
            ?? $this->insertProduct();

        if (DB::table('github_settings')->where('product_id', $productId)->exists()) {
            return;
        }

        DB::table('github_settings')->insert([
            'product_id' => $productId,
            'github_owner' => 'xjanova',
            'github_repo' => 'chanthra-studio',
            'github_token' => '',
            // release มีไฟล์เดียว (ChanthraStudio-v<version>-win-x64.zip) — pattern กันไว้ถ้าวันหน้ามีไฟล์อื่นด้วย
            'asset_pattern' => 'ChanthraStudio-*-win-x64.zip',
            'is_active' => true,
            'auto_sync' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');

        // ลบได้เฉพาะเมื่อยังไม่เคยขาย — ลบสินค้า = คีย์และรายการในออเดอร์หายตาม
        if ($productId === null
            || DB::table('license_keys')->where('product_id', $productId)->exists()
            || DB::table('order_items')->where('product_id', $productId)->exists()) {
            return;
        }

        DB::table('product_versions')->where('product_id', $productId)->delete();
        DB::table('github_settings')->where('product_id', $productId)->delete();
        DB::table('products')->where('id', $productId)->delete();
    }

    private function insertProduct(): int
    {
        $categoryId = DB::table('categories')->where('slug', 'ai-automation')->value('id')
            ?? DB::table('categories')->insertGetId([
                // ค่าเดียวกับ XmanProductsSeeder / ChanthraStudioSeeder — production มีหมวดนี้อยู่แล้ว
                'name' => 'AI & Automation',
                'slug' => 'ai-automation',
                'description' => 'ซอฟต์แวร์ AI และระบบอัตโนมัติ',
                'icon' => 'robot',
                'order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Chanthra Studio',
            'slug' => self::SLUG,
            // sku ไม่บังคับแต่ต้องไม่ซ้ำ — ชนกับของที่มีอยู่ก็ปล่อยว่าง ดีกว่าให้ migration ล้มกลาง deploy
            'sku' => DB::table('products')->where('sku', self::SKU)->exists() ? null : self::SKU,
            'short_description' => 'AI video atelier บน Windows — script · shot · score · stitch ครบในแอปเดียว '
                . 'ดีไซน์แบบ lunar atelier · ทอง/ครามม่วง · ใช้ ComfyUI ในเครื่อง หรือ provider บนคลาวด์',
            'description' => <<<'HTML'
<div class="prose max-w-none">
<h2>Chanthra Studio — AI Video Atelier</h2>
<p class="text-lg"><em>"Lunar atelier for cinematic AI generation — script, shot, score, stitch."</em></p>
<p>Windows desktop app สำหรับสร้างคลิป/หนังสั้นด้วย AI ครบวงจรในโปรแกรมเดียว เริ่มจาก script ด้วย LLM → สร้างภาพ/วีดีโอผ่าน ComfyUI หรือ provider บนคลาวด์ → พากย์เสียง → ตัดต่อต่อร้อย → โพสต์ขึ้น Facebook</p>

<h3>Generate · Edit · Score · Stitch</h3>
<ul>
<li><strong>Generate</strong> — Storyboard รายชอต ตั้ง prompt/style/seed/aspect แล้วยิงเข้า ComfyUI หรือ Replicate / Runway / Pika</li>
<li><strong>Sound atelier</strong> — TTS ด้วย OpenAI / ElevenLabs และ "Write script" ด้วย OpenAI / Claude / Gemini / OpenRouter</li>
<li><strong>Library + Queue</strong> — เก็บคลิปทั้งหมดใน SQLite ใกล้ ๆ exe, มี queue ที่เห็นความคืบหน้าจริง</li>
<li><strong>Render film</strong> — ต่อคลิปเป็น MP4 ด้วย ffmpeg พร้อม audio track</li>
<li><strong>Node Flow</strong> — visual graph editor ในแอปสำหรับ ComfyUI workflow (drag-drop nodes, bezier wires, mini-map)</li>
<li><strong>Auto-update</strong> — เช็คเวอร์ชันใหม่อัตโนมัติ มี progress bar + release notes ให้อ่าน</li>
</ul>

<h3>Lunar atelier aesthetic</h3>
<ul>
<li>Frameless 1640×1000 window กับ Mica gradient ทอง/ม่วง/ครามเข้ม</li>
<li>Cormorant Garamond + IBM Plex font stack</li>
<li>Per-row save feedback ใน Settings · DPAPI encrypted API keys</li>
</ul>

<h3>Privacy & License</h3>
<ul>
<li>1 license key = 1 เครื่อง (HWID-bound) ย้ายเครื่องได้ผ่าน Deactivate</li>
<li>Database, settings, license key เก็บไว้ในเครื่องตัวเอง</li>
<li>API keys (LLM/TTS/Video providers) เข้ารหัสด้วย Windows DPAPI</li>
</ul>
</div>
HTML,
            'features' => json_encode([
                'multi-provider video generation',
                'real ComfyUI WebSocket integration',
                'visual node-flow graph editor',
                'TTS (OpenAI / ElevenLabs)',
                'LLM script writing (OpenAI / Claude / Gemini / OpenRouter)',
                'ffmpeg slideshow render with audio',
                'Facebook + webhook auto-posting',
                'in-app auto-update with progress bar',
                'DPAPI-encrypted API keys',
            ], JSON_UNESCAPED_UNICODE),
            'price' => 2500.00,
            'is_custom' => false,
            'requires_license' => true,
            'stock' => 999,
            'is_active' => true,
            // ยังไม่เปิดขาย — ไม่มีวันเปิดตายตัว เจ้าของปิดเองในหน้า admin
            'is_coming_soon' => true,
            'coming_soon_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
