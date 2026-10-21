<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Starter supporter items, so an approved slip issues redeem codes from day one:
 * every game gets one badge per default reward tier (SPARK, SALVAGER, WINGMATE,
 * PATHFINDER) plus a title for PATHFINDER, and each default tier names its items.
 * Also registers ขุนศึกกรุงศรี, which joined the hub after the catalogue migration.
 * Existing items, edited tiers and anything already in use are left alone.
 */
return new class extends Migration
{
    private const BADGES = [
        'SPARK' => ['supporter-spark', 'ตรา SPARK', 'ตราผู้สนับสนุนรุ่นแรก ระดับ SPARK'],
        'SALVAGER' => ['supporter-salvager', 'ตรา SALVAGER', 'ตราผู้สนับสนุนรุ่นแรก ระดับ SALVAGER'],
        'WINGMATE' => ['supporter-wingmate', 'ตรา WINGMATE', 'ตราผู้สนับสนุนรุ่นแรก ระดับ WINGMATE'],
        'PATHFINDER' => ['supporter-pathfinder', 'ตรา PATHFINDER', 'ตราผู้สนับสนุนรุ่นแรก ระดับ PATHFINDER'],
    ];

    private const TITLE = ['title-pathfinder', 'ฉายา ผู้บุกเบิก', 'ฉายาของผู้สนับสนุนระดับ PATHFINDER'];

    public function up(): void
    {
        $now = now();
        if (! DB::table('game_campaigns')->where('slug', 'krungsri')->exists()) {
            DB::table('game_campaigns')->insert([
                'slug' => 'krungsri', 'name' => 'ขุนศึกกรุงศรี',
                'description' => 'RPG เปิดหีบแบบ idle ในกรุงศรีแฟนตาซี เกมแรกของ XMAN Studio ที่เปิด Closed Beta เล่นแบบ guest ได้ทันที แล้วผูก XMAN ID เพื่อเก็บความคืบหน้า',
                'goal_satang' => 0, 'tiers' => json_encode(config('game-support.tiers'), JSON_UNESCAPED_UNICODE), 'active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach (DB::table('game_campaigns')->get(['id', 'name', 'tiers']) as $campaign) {
            $items = [];
            foreach ([...array_values(self::BADGES), self::TITLE] as [$key, $name, $description]) {
                $items[$key] = ['key' => $key, 'name' => $name, 'kind' => $key === self::TITLE[0] ? 'title' : 'badge', 'description' => $description . ' · ' . $campaign->name];
            }
            foreach ($items as $key => $item) {
                if (! DB::table('game_items')->where('game_campaign_id', $campaign->id)->where('key', $key)->exists()) {
                    DB::table('game_items')->insert($item + ['game_campaign_id' => $campaign->id, 'max_devices' => 3, 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
                }
            }

            // name the items in the default tiers; a tier an admin already gave items keeps them
            $tiers = json_decode($campaign->tiers, true) ?: [];
            $changed = false;
            foreach ($tiers as &$tier) {
                $badge = self::BADGES[$tier['name'] ?? ''] ?? null;
                if ($badge && empty($tier['items'])) {
                    $tier['items'] = $tier['name'] === 'PATHFINDER' ? [$badge[0], self::TITLE[0]] : [$badge[0]];
                    $changed = true;
                }
            }
            unset($tier);
            if ($changed) {
                DB::table('game_campaigns')->where('id', $campaign->id)->update(['tiers' => json_encode($tiers, JSON_UNESCAPED_UNICODE), 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $keys = [...array_column(self::BADGES, 0), self::TITLE[0]];
        foreach (DB::table('game_campaigns')->get(['id', 'tiers']) as $campaign) {
            $tiers = json_decode($campaign->tiers, true) ?: [];
            foreach ($tiers as &$tier) {
                if (isset($tier['items'])) {
                    $tier['items'] = array_values(array_diff($tier['items'], $keys));
                    if (! $tier['items']) {
                        unset($tier['items']);
                    }
                }
            }
            unset($tier);
            DB::table('game_campaigns')->where('id', $campaign->id)->update(['tiers' => json_encode($tiers, JSON_UNESCAPED_UNICODE)]);
        }
        // items that were handed out stay: their codes still belong to someone
        DB::table('game_items')->whereIn('key', $keys)->whereNotIn('id', DB::table('game_entitlements')->select('game_item_id'))->delete();
        $krungsri = DB::table('game_campaigns')->where('slug', 'krungsri')->value('id');
        if ($krungsri && ! DB::table('game_donations')->where('game_campaign_id', $krungsri)->exists() && ! DB::table('game_items')->where('game_campaign_id', $krungsri)->exists()) {
            DB::table('game_campaigns')->where('id', $krungsri)->delete();
        }
    }
};
