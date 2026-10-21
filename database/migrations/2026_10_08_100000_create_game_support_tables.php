<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_campaigns', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->unsignedBigInteger('goal_satang')->default(0);
            $t->json('tiers');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('game_donations', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('game_campaign_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('amount_satang');
            $t->string('display_name', 80);
            $t->boolean('publish_name')->default(false);
            $t->text('comment')->nullable();
            $t->string('slip_path');
            $t->string('slip_mime', 40);
            $t->char('slip_hash', 64)->unique();
            $t->string('status', 20)->default('pending')->index();
            $t->string('bank_reference', 120)->nullable()->unique();
            $t->json('bank_snapshot');
            $t->json('reward_snapshot');
            $t->string('reward_status', 20)->default('pending');
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('review_note')->nullable();
            $t->json('audit')->nullable();
            $t->timestamps();
        });
        Schema::create('game_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('game_campaign_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('game_donation_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $t->string('display_name', 80);
            $t->text('body');
            $t->string('status', 20)->default('pending')->index();
            $t->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('moderated_at')->nullable();
            $t->timestamps();
        });
        Schema::create('game_votes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('game_campaign_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
        });
        Schema::create('game_ratings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('game_campaign_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('stars');
            $t->unique(['user_id', 'game_campaign_id']);
            $t->timestamps();
        });
        foreach (json_decode(file_get_contents(database_path('game-support-catalog.json')), true, 512, JSON_THROW_ON_ERROR) as $game) {
            DB::table('game_campaigns')->insert(['slug' => $game['slug'], 'name' => $game['name'], 'description' => $game['description'], 'goal_satang' => $game['goal'] * 100, 'tiers' => json_encode(config('game-support.tiers')), 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (['game_ratings', 'game_votes', 'game_comments', 'game_donations', 'game_campaigns'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
