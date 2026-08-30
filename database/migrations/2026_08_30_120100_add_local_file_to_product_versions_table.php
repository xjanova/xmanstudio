<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let a version's file live on our own disk, and record its hash.
     *
     * Every existing version comes from a GitHub release. Avatar packs are
     * uploaded by an admin instead, so there is no release to point at -
     * storage_path carries the file and the GitHub columns stay null.
     *
     * sha256 is not pack-specific and is deliberately added for all products:
     * a download that arrives truncated or tampered with is otherwise
     * indistinguishable from a good one until the moment it is opened.
     */
    public function up(): void
    {
        Schema::table('product_versions', function (Blueprint $table) {
            $table->string('storage_path')->nullable()->after('download_filename');
            $table->string('sha256', 64)->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('product_versions', function (Blueprint $table) {
            $table->dropColumn(['storage_path', 'sha256']);
        });
    }
};
