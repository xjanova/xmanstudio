<?php

use App\Models\SeoSetting;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * The new XMAN STUDIO logo (owner, 2026-10-04: the old one could not be read on the 3D universe,
 * its wordmark being black ink — "ต้องเปลี่ยนทั้งเว็บทุกจุด"), in both variants, with its favicon
 * and the image shown when the site is shared. All four live in settings and on the public disk,
 * so files in the repo alone would not reach production: this copies resources/brand/* there
 * and points the settings at them.
 *
 * The previous uploads stay on the disk (their paths are logged) and can be put back on the
 * Branding and SEO pages.
 */
return new class extends Migration
{
    /** setting => [file in resources/brand, path on the public disk, description] */
    private const FILES = [
        'site_logo' => ['xman-logo-light.webp', 'branding/xman-logo-light-2026-10.webp', 'เส้นทางของโลโก้เว็บไซต์'],
        'site_logo_dark' => ['xman-logo-dark.webp', 'branding/xman-logo-dark-2026-10.webp', 'เส้นทางของโลโก้สำหรับพื้นหลังมืด'],
        'site_favicon' => ['xman-mark.png', 'branding/xman-mark-2026-10.png', 'เส้นทางของ favicon'],
    ];

    private const SHARE_IMAGE = ['xman-og.jpg', 'seo/xman-og-2026-10.jpg'];

    public function up(): void
    {
        // The test suite brings its own uploads on a fake disk.
        if (app()->runningUnitTests()) {
            return;
        }

        $disk = Storage::disk('public');
        $previous = [];

        foreach (self::FILES as $key => [$source, $target, $description]) {
            $previous[$key] = Setting::getValue($key);
            $disk->put($target, file_get_contents(resource_path('brand/' . $source)));
            Setting::setValue($key, $target, 'string', 'branding', $description, true);
        }

        if (Schema::hasTable('seo_settings')) {
            [$source, $target] = self::SHARE_IMAGE;
            $seo = SeoSetting::getInstance();
            $previous['og_image'] = $seo->og_image;
            $disk->put($target, file_get_contents(resource_path('brand/' . $source)));
            $seo->update(['og_image' => $target]);
        }

        Cache::forget('og_image_default_v7');
        Log::info('Brand 2026-10 installed: logo (light + dark), favicon, share image', ['previous' => $previous]);
    }

    public function down(): void
    {
        // Nothing to undo automatically: the previous uploads are still on the public disk,
        // at the paths logged by up().
    }
};
