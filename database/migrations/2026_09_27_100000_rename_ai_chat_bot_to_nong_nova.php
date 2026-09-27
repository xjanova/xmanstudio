<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The site's AI assistant is น้อง Nova, the 3D home page's guide (owner,
 * 2026-09-27: "เปลี่ยนจาก AI Assistant เป็น น้อง Nova"). The name lives in the
 * settings table, so a new code default alone would not reach production,
 * where the AI settings form had saved the old default. A name an admin
 * typed in stays.
 */
return new class extends Migration
{
    private const OLD = 'AI Assistant';

    private const NEW = 'น้อง Nova';

    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'ai_bot_name')->first();

        if ($row === null || ! in_array(trim((string) $row->value), ['', self::OLD], true)) {
            return;
        }

        DB::table('settings')->where('id', $row->id)->update(['value' => self::NEW, 'updated_at' => now()]);
        Cache::forget('setting.ai_bot_name');
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'ai_bot_name')->where('value', self::NEW)->update(['value' => self::OLD, 'updated_at' => now()]);
        Cache::forget('setting.ai_bot_name');
    }
};
