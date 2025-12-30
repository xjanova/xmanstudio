<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่มฟิลด์ให้ license_keys table สำหรับ desktop app licensing
     */
    public function up(): void
    {
        Schema::table('license_keys', function (Blueprint $table) {
            // Machine binding for desktop apps
            $table->string('machine_id')->nullable()->after('device_id');
            $table->string('machine_fingerprint', 1024)->nullable()->after('machine_id');

            // License type
            $table->enum('license_type', ['demo', 'monthly', 'yearly', 'lifetime', 'product'])
                ->default('product')->after('status');

            // Validation tracking
            $table->timestamp('last_validated_at')->nullable()->after('expires_at');

            // Metadata
            $table->json('metadata')->nullable()->after('activations');

            // Soft delete
            $table->softDeletes();

            // Additional indexes
            $table->index(['license_key', 'machine_id']);
            $table->index(['license_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_keys', function (Blueprint $table) {
            $table->dropIndex(['license_key', 'machine_id']);
            $table->dropIndex(['license_type', 'status']);
            $table->dropColumn([
                'machine_id',
                'machine_fingerprint',
                'license_type',
                'last_validated_at',
                'metadata',
                'deleted_at',
            ]);
        });
    }
};
