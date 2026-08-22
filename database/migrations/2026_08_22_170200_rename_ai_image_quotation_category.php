<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The 'ai_image' category is named "AI Image Generation" but also holds
     * video generation, content writing and 3D avatars, so the label misleads
     * anyone browsing or filtering by category name. Rename it to match what
     * is actually inside; the key stays put so existing quotations keep resolving.
     */
    protected const OLD_NAME = 'AI Image Generation';

    protected const NEW = [
        'name' => 'AI Content Generation',
        'name_th' => 'สร้างคอนเทนต์ด้วย AI',
        'description' => 'AI generation for images, video, copywriting and avatars',
        'description_th' => 'บริการสร้างภาพ วิดีโอ เนื้อหา และอวตารด้วย AI',
    ];

    public function up(): void
    {
        // Guarded on the old name so a later admin rename is never clobbered
        // if this migration is re-run against a restored database.
        DB::table('quotation_categories')
            ->where('key', 'ai_image')
            ->where('name', self::OLD_NAME)
            ->update(self::NEW + ['updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('quotation_categories')
            ->where('key', 'ai_image')
            ->where('name', self::NEW['name'])
            ->update([
                'name' => self::OLD_NAME,
                'name_th' => 'สร้างภาพด้วย AI',
                'description' => 'Advanced AI image generation and editing services',
                'description_th' => 'บริการสร้างและแก้ไขภาพด้วย AI',
                'updated_at' => now(),
            ]);
    }
};
