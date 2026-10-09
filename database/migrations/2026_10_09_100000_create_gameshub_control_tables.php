<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * XGamesHub control: in-game items given for approved donations (with redeem codes the
 * games check), hub announcements, the hub hero order, and a note on comment moderation.
 * Game reviews reuse the existing polymorphic `reviews` table (reviewable = GameCampaign).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('game_campaign_id')->constrained()->restrictOnDelete();
            // the stable id the game's own code looks for, e.g. "founder-badge"
            $t->string('key', 80);
            $t->string('name', 120);
            $t->text('description')->nullable();
            $t->string('kind', 20)->default('cosmetic');
            $t->string('image_url', 500)->nullable();
            // how many devices / browsers one code can be redeemed on
            $t->unsignedTinyInteger('max_devices')->default(3);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['game_campaign_id', 'key']);
        });

        Schema::create('game_entitlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('game_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('game_donation_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('source', 20);
            $t->string('status', 20)->default('granted')->index();
            $t->char('code_hash', 64)->unique();
            // encrypted; shown only to the owner and admins
            $t->text('code');
            $t->unsignedSmallInteger('redeem_count')->default(0);
            $t->timestamp('first_redeemed_at')->nullable();
            $t->timestamp('last_redeemed_at')->nullable();
            $t->foreignId('granted_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('revoked_at')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
            // approving a donation twice can never hand out a second copy
            $t->unique(['game_donation_id', 'game_item_id']);
        });

        Schema::create('game_item_redemptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('game_entitlement_id')->constrained()->cascadeOnDelete();
            $t->char('device_hash', 64);
            $t->char('ip_hash', 64)->nullable();
            $t->timestamps();
            $t->unique(['game_entitlement_id', 'device_hash']);
        });

        Schema::create('gameshub_announcements', function (Blueprint $t) {
            $t->id();
            $t->string('message', 300);
            $t->string('link_url', 500)->nullable();
            $t->string('link_label', 60)->nullable();
            $t->string('tone', 20)->default('info');
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('active')->default(true);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::table('game_campaigns', function (Blueprint $t) {
            // null = the hub's own order; lower numbers come first in the hub's hero
            $t->unsignedSmallInteger('hero_rank')->nullable()->after('active');
            $t->boolean('hero_hidden')->default(false)->after('hero_rank');
        });

        Schema::table('game_comments', function (Blueprint $t) {
            $t->string('moderation_note', 500)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('game_comments', fn (Blueprint $t) => $t->dropColumn('moderation_note'));
        Schema::table('game_campaigns', fn (Blueprint $t) => $t->dropColumn(['hero_rank', 'hero_hidden']));
        foreach (['gameshub_announcements', 'game_item_redemptions', 'game_entitlements', 'game_items'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
