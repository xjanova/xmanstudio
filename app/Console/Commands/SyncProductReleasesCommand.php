<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\GithubReleaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ดึง release ล่าสุดจาก GitHub เข้าตาราง product_versions อัตโนมัติ
 *
 * 2026-07-28 — เดิม syncLatestRelease() เรียกได้ทางเดียวคือปุ่ม Sync ในหน้า admin
 * (POST /admin/products/{product}/versions/sync) ไม่มี webhook ไม่มี cron
 * ถ้าไม่มีใครกดปุ่ม API เช็คอัพเดทจะโฆษณาเวอร์ชันเก่าค้างไว้ตลอด แอปลูกค้าก็จะ
 * ตอบ "ไม่มีอัพเดท" อย่างถูกต้องตามข้อมูลที่ได้รับ ทั้งที่ GitHub มีของใหม่แล้ว
 * — ตรงกับอาการที่ลูกค้าแจ้งว่า "เช็คเวอร์ชันไม่เจอแต่เงียบ ทั้งที่มีอัพเดท"
 */
class SyncProductReleasesCommand extends Command
{
    protected $signature = 'products:sync-releases
                            {--product= : sync เฉพาะ slug ที่ระบุ}
                            {--force : sync แม้ผลิตภัณฑ์นั้นจะปิด auto sync ไว้}';

    protected $description = 'ดึง release ล่าสุดจาก GitHub มาอัพเดท product_versions ของทุกผลิตภัณฑ์';

    public function handle(GithubReleaseService $github): int
    {
        $slug = $this->option('product');

        // สั่งมือ (--force หรือระบุ --product) = ข้ามการกรอง auto_sync
        // ปล่อยให้ cron เท่านั้นที่เคารพติ๊กในหน้า admin
        $manual = $this->option('force') || $slug;

        $query = Product::whereHas('githubSetting', function ($q) use ($manual) {
            $q->where('is_active', true);

            if (! $manual) {
                $q->where('auto_sync', true);
            }
        });

        if ($slug) {
            $query->where('slug', $slug);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->info('ไม่มีผลิตภัณฑ์ที่ตั้งค่า GitHub ไว้');

            return self::SUCCESS;
        }

        $synced = 0;
        $failed = 0;

        foreach ($products as $product) {
            // ค่าเดิมก่อน sync — ใช้ดูว่ารอบนี้มีของใหม่จริงไหม
            $before = optional($product->latestVersion())->version;

            try {
                $version = $github->syncLatestRelease($product);
                $synced++;

                if ($version && $version->version !== $before) {
                    $this->info("{$product->slug}: {$before} → {$version->version}");
                    Log::info('product release synced', [
                        'product' => $product->slug,
                        'from' => $before,
                        'to' => $version->version,
                    ]);
                } else {
                    $this->line("{$product->slug}: ไม่มีของใหม่ ({$before})");
                }
            } catch (\Exception $e) {
                // ผลิตภัณฑ์เดียวพังต้องไม่ทำให้ตัวอื่นไม่ได้ sync
                $failed++;
                $this->error("{$product->slug}: {$e->getMessage()}");
                Log::warning('product release sync failed', [
                    'product' => $product->slug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("เสร็จ — sync {$synced} ผลิตภัณฑ์, ล้มเหลว {$failed}");

        return self::SUCCESS;
    }
}
