<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Files shared within a network (the "torrent" registry)
        Schema::create('vpn_shared_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained('vpn_networks')->cascadeOnDelete();
            $table->foreignId('owner_member_id')->constrained('vpn_network_members')->cascadeOnDelete();
            $table->string('file_hash', 64)->comment('SHA256 of file content');
            $table->string('file_name', 255);
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('chunk_size')->default(32768);
            $table->unsignedInteger('total_chunks');
            $table->timestamps();

            $table->unique(['network_id', 'file_hash']);
            $table->index('network_id');
        });

        // Which members have (seed) which files
        Schema::create('vpn_file_seeders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_file_id')->constrained('vpn_shared_files')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('vpn_network_members')->cascadeOnDelete();
            $table->string('chunks_bitmap')->default('')->comment('Comma-separated chunk indices or "all"');
            $table->timestamps();

            $table->unique(['shared_file_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpn_file_seeders');
        Schema::dropIfExists('vpn_shared_files');
    }
};
