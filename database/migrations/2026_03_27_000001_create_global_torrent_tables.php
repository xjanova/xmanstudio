<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categories for organizing torrents
        Schema::create('bt_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('icon', 50)->comment('Material icon name');
            $table->string('description', 255)->nullable();
            $table->boolean('is_adult')->default(false)->comment('18+ category');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Global torrent file registry
        Schema::create('bt_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('bt_categories');
            $table->string('uploader_machine_id', 255);
            $table->string('uploader_display_name', 100);
            $table->string('file_hash', 64)->unique()->comment('SHA256 of file content');
            $table->string('file_name', 255);
            $table->unsignedBigInteger('file_size');
            $table->text('description')->nullable();
            $table->string('thumbnail_url', 500)->nullable()->comment('base64 or URL');
            $table->unsignedInteger('chunk_size')->default(32768);
            $table->unsignedInteger('total_chunks');
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category_id');
            $table->index('created_at');
            $table->index('download_count');
        });

        // Seeders for each file
        Schema::create('bt_file_seeders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bt_file_id')->constrained('bt_files')->cascadeOnDelete();
            $table->string('machine_id', 255);
            $table->string('display_name', 100);
            $table->string('public_ip', 45)->nullable();
            $table->integer('public_port')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->string('chunks_bitmap')->default('')->comment('Comma-separated chunk indices or "all"');
            $table->timestamps();

            $table->unique(['bt_file_id', 'machine_id']);
        });

        // User statistics and leaderboard
        Schema::create('bt_user_stats', function (Blueprint $table) {
            $table->id();
            $table->string('machine_id', 255)->unique();
            $table->string('display_name', 100);
            $table->unsignedBigInteger('total_uploaded_bytes')->default(0);
            $table->unsignedBigInteger('total_downloaded_bytes')->default(0);
            $table->unsignedInteger('total_files_shared')->default(0);
            $table->unsignedInteger('total_files_downloaded')->default(0);
            $table->unsignedBigInteger('seed_time_seconds')->default(0)->comment('Total seeding time');
            $table->unsignedBigInteger('score')->default(0)->comment('Calculated score');
            $table->unsignedInteger('rank_position')->default(0);
            $table->timestamps();

            $table->index(['score', 'rank_position']);
        });

        // Trophy definitions
        Schema::create('bt_trophies', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 100);
            $table->string('description', 255);
            $table->string('icon', 50)->comment('Emoji or icon name');
            $table->string('badge_text', 20)->comment('Shown after username');
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->string('requirement_type', 50)->comment('What to check');
            $table->unsignedInteger('requirement_value')->comment('Threshold');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Trophies awarded to users
        Schema::create('bt_user_trophies', function (Blueprint $table) {
            $table->id();
            $table->string('machine_id', 255);
            $table->foreignId('trophy_id')->constrained('bt_trophies')->cascadeOnDelete();
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->unique(['machine_id', 'trophy_id']);
            $table->index('machine_id');
        });

        // KYC verification requests for adult content access
        Schema::create('bt_kyc_requests', function (Blueprint $table) {
            $table->id();
            $table->string('machine_id', 255);
            $table->string('display_name', 100);
            $table->string('id_card_front_path', 500)->comment('File storage path');
            $table->string('id_card_back_path', 500)->nullable();
            $table->string('selfie_path', 500)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('Admin user id');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['machine_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bt_kyc_requests');
        Schema::dropIfExists('bt_user_trophies');
        Schema::dropIfExists('bt_trophies');
        Schema::dropIfExists('bt_user_stats');
        Schema::dropIfExists('bt_file_seeders');
        Schema::dropIfExists('bt_files');
        Schema::dropIfExists('bt_categories');
    }
};
