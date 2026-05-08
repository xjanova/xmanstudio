<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Seeds the Chanthra Studio product entry — the desktop AI video atelier
 * with built-in license + auto-update wired to xman4289.com.
 *
 * Run with: php artisan db:seed --class=ChanthraStudioSeeder
 */
class ChanthraStudioSeeder extends Seeder
{
    public function run(): void
    {
        $aiCategory = Category::firstOrCreate(
            ['slug' => 'ai-automation'],
            [
                'name' => 'AI & Automation',
                'description' => 'ซอฟต์แวร์ AI และระบบอัตโนมัติ',
                'icon' => 'robot',
                'order' => 3,
                'is_active' => true,
            ]
        );

        $description = <<<'HTML'
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
<li><strong>Auto-update</strong> — เช็ค GitHub Releases อัตโนมัติ มี progress bar + release notes ให้อ่าน</li>
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
HTML;

        Product::updateOrCreate(
            ['slug' => 'chanthra-studio'],
            [
                'category_id' => $aiCategory->id,
                'name' => 'Chanthra Studio',
                'sku' => 'CHS-001',
                'short_description' => 'AI video atelier บน Windows — script · shot · score · stitch ครบในแอปเดียว ดีไซน์แบบ lunar atelier · ทอง/ครามม่วง · ใช้ ComfyUI ในเครื่อง หรือ provider บนคลาวด์',
                'description' => $description,
                'features' => [
                    'multi-provider video generation',
                    'real ComfyUI WebSocket integration',
                    'visual node-flow graph editor',
                    'TTS (OpenAI / ElevenLabs)',
                    'LLM script writing (OpenAI / Claude / Gemini / OpenRouter)',
                    'ffmpeg slideshow render with audio',
                    'Facebook + webhook auto-posting',
                    'GitHub Releases auto-update with progress bar',
                    'DPAPI-encrypted API keys',
                ],
                'price' => 2500.00,
                'requires_license' => true,
                'is_custom' => false,
                'is_active' => true,
                'is_coming_soon' => false,
                'stock' => null,
            ]
        );
    }
}
