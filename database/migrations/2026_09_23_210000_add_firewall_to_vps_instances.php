<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The firewall made for this rental.
 *
 * Firewalls live on our supplier account, next to every other customer's —
 * the API has no notion of whose is whose. This column is that notion: a
 * customer's rule edits reach the one firewall recorded here and no other,
 * whatever id a form might carry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            if (! Schema::hasColumn('vps_instances', 'remote_firewall_id')) {
                $table->unsignedBigInteger('remote_firewall_id')->nullable()->after('remote_subscription_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            if (Schema::hasColumn('vps_instances', 'remote_firewall_id')) {
                $table->dropColumn('remote_firewall_id');
            }
        });
    }
};
