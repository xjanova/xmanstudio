<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('license_activities', function (Blueprint $table) {
            $table->id();

            // License reference
            $table->foreignId('license_id')->constrained('license_keys')->onDelete('cascade');

            // Activity type
            $table->enum('action', [
                'created',
                'activated',
                'deactivated',
                'validated',
                'expired',
                'revoked',
                'reactivated',
                'extended',
                'machine_reset',
                'failed_activation',
                'suspicious_activity',
            ])->index();

            // Actor information
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('actor_type')->default('system'); // system, admin, api, user

            // Device/Request info
            $table->string('machine_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();

            // Additional data
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['license_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_activities');
    }
};
