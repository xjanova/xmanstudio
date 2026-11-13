<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SLUG = 'cluadex-ai-coding-assistant';

    private const INSTALLER = 'CluadeX-win-Setup.exe';

    /**
     * CluadeX — ปุ่มดาวน์โหลดส่งตัวติดตั้ง (CluadeX-win-Setup.exe) ไม่ใช่ zip แบบพกพา
     *
     * เจ้าของ 2026-11-13: "เราไม่ออกรุ่น portable แล้ว เอาออกจากดาวโหลดที่ xman studio เลย"
     *
     * ไฟล์ที่ /cluadex/download และ update/check ส่งให้ = ไฟล์ที่ตรง asset_pattern ของ GitHub setting ซึ่งตั้งในหน้า admin
     * ตอนนี้ตั้งไว้ให้ตรงกับ CluadeX-win-Portable.zip · ตั้งแต่ CluadeX 3.0.325 release ไม่มี zip นี้แล้ว ถ้า pattern
     * ยังชี้ zip อยู่ sync จะไม่เจอไฟล์ที่ตรง แล้วหยิบไฟล์แรกของ release (assets.win.json หรือ .nupkg) มาเป็น
     * "ตัวดาวน์โหลด" แทน — migration นี้จึงต้อง deploy ก่อน release แรกที่ไม่มี zip
     *
     * คนที่ใช้สำเนาจาก zip เดิมไม่กระทบ: zip นั้นเป็นชุดของ Velopack (มี Update.exe) อัปเดตผ่าน
     * /cluadex/download/releases.win.json เหมือนตัวที่ติดตั้ง ไม่ได้ใช้ไฟล์ของปุ่มนี้
     *
     * แถวเวอร์ชันที่ sync ไว้แล้วชี้ไฟล์ zip อยู่ — ย้ายไปชี้ตัวติดตั้งจากรายการไฟล์ของ release เดียวกัน (คอลัมน์
     * assets) ทันที ไม่ต้องรอ cron รอบถัดไป · แถวที่ไม่มีรายการไฟล์ (sync ไว้ก่อนมีคอลัมน์) ปล่อยไว้ให้ sync เขียนทับเอง
     */
    public function up(): void
    {
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');

        if ($productId === null) {
            return;
        }

        DB::table('github_settings')
            ->where('product_id', $productId)
            ->where('asset_pattern', '!=', self::INSTALLER)
            ->update([
                'asset_pattern' => self::INSTALLER,
                'updated_at' => now(),
            ]);

        $versions = DB::table('product_versions')
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->get(['id', 'download_filename', 'assets']);

        foreach ($versions as $version) {
            if ($version->download_filename === self::INSTALLER) {
                continue;
            }

            $installer = $this->installerIn($version->assets);

            if ($installer === null) {
                continue;
            }

            $row = [
                'download_filename' => $installer['name'],
                'file_size' => isset($installer['size']) ? (int) $installer['size'] : null,
                // sha256 ของ zip ใช้ตรวจตัวติดตั้งไม่ได้ — ไม่มีค่าของตัวติดตั้งก็ต้องว่าง ไม่ใช่ค้างค่าเดิม
                'sha256' => is_string($installer['sha256'] ?? null) ? $installer['sha256'] : null,
                'download_url' => is_string($installer['download_url'] ?? null) ? $installer['download_url'] : null,
                'updated_at' => now(),
            ];

            if (is_string($installer['api_url'] ?? null) && $installer['api_url'] !== '') {
                $row['github_release_url'] = $installer['api_url'];
            }

            DB::table('product_versions')->where('id', $version->id)->update($row);
        }
    }

    /**
     * ไม่ย้อนกลับ: zip แบบพกพาไม่มีใน release ใหม่แล้ว ชี้กลับไปก็ไม่มีไฟล์ให้ส่ง · ถ้าต้องเปลี่ยนไฟล์ ตั้ง asset_pattern
     * ในหน้า admin ▸ เวอร์ชันของสินค้า
     */
    public function down(): void {}

    /** ตัวติดตั้งในรายการไฟล์ของ release (JSON ของคอลัมน์ assets) — null ถ้าไม่มี */
    private function installerIn(mixed $assets): ?array
    {
        $list = is_string($assets) ? json_decode($assets, true) : $assets;

        if (! is_array($list)) {
            return null;
        }

        foreach ($list as $asset) {
            if (is_array($asset) && is_string($asset['name'] ?? null) && strcasecmp($asset['name'], self::INSTALLER) === 0) {
                return $asset;
            }
        }

        return null;
    }
};
