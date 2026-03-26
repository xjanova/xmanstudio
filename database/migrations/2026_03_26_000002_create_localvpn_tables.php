<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpn_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('password_hash')->nullable();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('owner_device_id')->nullable();
            $table->unsignedInteger('max_members')->default(10);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('virtual_subnet')->default('10.10.0.0/24');
            $table->timestamps();

            $table->index('owner_user_id');
            $table->index('is_active');
            $table->index('is_public');
        });

        Schema::create('vpn_network_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained('vpn_networks')->cascadeOnDelete();
            $table->string('device_id')->nullable();
            $table->string('machine_id');
            $table->string('display_name');
            $table->string('virtual_ip');
            $table->string('public_ip')->nullable();
            $table->unsignedInteger('public_port')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['network_id', 'machine_id']);
            $table->index('is_online');
            $table->index('network_id');
        });

        Schema::create('vpn_relay_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained('vpn_networks')->cascadeOnDelete();
            $table->foreignId('source_member_id')->constrained('vpn_network_members')->cascadeOnDelete();
            $table->foreignId('target_member_id')->constrained('vpn_network_members')->cascadeOnDelete();
            $table->bigInteger('bytes_relayed')->unsigned()->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('network_id');
            $table->index('is_active');
        });

        Schema::create('vpn_traffic_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained('vpn_networks')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('vpn_network_members')->nullOnDelete();
            $table->enum('action', ['join', 'leave', 'data_relay', 'heartbeat', 'network_create', 'network_delete']);
            $table->bigInteger('bytes')->unsigned()->default(0);
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('network_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpn_traffic_logs');
        Schema::dropIfExists('vpn_relay_sessions');
        Schema::dropIfExists('vpn_network_members');
        Schema::dropIfExists('vpn_networks');
    }
};
