<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Skidrow Killer's admin image was its logo, so everywhere — product.xman4289.com included,
 * where an admin image counts as a real capture — showed the logo as if it were the program.
 * The owner (2026-10-12): use the hub's picture instead ("ใช้ภาพ Hub"). Clearing the image lets
 * Product::artwork_url fall back to artwork/hub/skidrow-killer.webp. The logo file stays on the
 * public disk, and the screenshots in its gallery are untouched.
 */
return new class extends Migration
{
    private const LOGO = 'products/skidrow-killer/logo.png';

    public function up(): void
    {
        $cleared = DB::table('products')
            ->where('slug', 'skidrow-killer')
            ->where('image', self::LOGO)
            ->update(['image' => null, 'updated_at' => now()]);

        if ($cleared) {
            Log::info('Skidrow Killer now shows the hub picture', ['previous_image' => self::LOGO]);
        }
    }

    public function down(): void
    {
        DB::table('products')->where('slug', 'skidrow-killer')->whereNull('image')->update(['image' => self::LOGO]);
    }
};
