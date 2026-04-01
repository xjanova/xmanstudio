<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wireguard_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country_code', 2);
            $table->string('country_name');
            $table->string('endpoint'); // hostname:port
            $table->string('public_key');
            $table->text('private_key'); // encrypted via model cast
            $table->string('address'); // e.g. 10.20.0.1/24
            $table->string('dns')->default('1.1.1.1, 8.8.8.8');
            $table->integer('listen_port')->default(51820);
            $table->integer('max_clients')->default(250);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_healthy')->default(true);
            $table->timestamp('last_health_check_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wireguard_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('wireguard_servers')->cascadeOnDelete();
            $table->string('machine_id');
            $table->string('public_key');
            $table->string('assigned_ip');
            $table->boolean('is_connected')->default(false);
            $table->unsignedBigInteger('bytes_rx')->default(0);
            $table->unsignedBigInteger('bytes_tx')->default(0);
            $table->timestamp('last_handshake_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'machine_id']);
            $table->unique(['server_id', 'assigned_ip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wireguard_clients');
        Schema::dropIfExists('wireguard_servers');
    }
};
