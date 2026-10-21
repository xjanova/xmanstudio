<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vpn_network_members', function (Blueprint $table) {
            // LocalVPN app's public key for end-to-end encrypted P2P traffic,
            // "v<wire version>:<base64 X25519 key>". Null = app predates it.
            $table->string('p2p_key', 64)->nullable()->after('vpn_gateway_hostname');
        });
    }

    public function down(): void
    {
        Schema::table('vpn_network_members', function (Blueprint $table) {
            $table->dropColumn('p2p_key');
        });
    }
};
