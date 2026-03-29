<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vpn_network_members', function (Blueprint $table) {
            $table->string('vpn_gateway_hostname', 255)->nullable()->after('vpn_gateway_country');
        });
    }

    public function down(): void
    {
        Schema::table('vpn_network_members', function (Blueprint $table) {
            $table->dropColumn('vpn_gateway_hostname');
        });
    }
};
