<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Insert GitHub settings for LocalVPN product to enable auto-sync releases.
     * Token must be set manually via admin panel after migration.
     */
    public function up(): void
    {
        $product = DB::table('products')->where('slug', 'localvpn')->first();

        if (! $product) {
            return;
        }

        $exists = DB::table('github_settings')->where('product_id', $product->id)->exists();
        if (! $exists) {
            DB::table('github_settings')->insert([
                'product_id' => $product->id,
                'github_owner' => 'xjanova',
                'github_repo' => 'localvpn',
                'github_token' => '', // Set via admin panel (encrypted)
                'asset_pattern' => '*.apk',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $product = DB::table('products')->where('slug', 'localvpn')->first();
        if ($product) {
            DB::table('github_settings')->where('product_id', $product->id)->delete();
        }
    }
};
