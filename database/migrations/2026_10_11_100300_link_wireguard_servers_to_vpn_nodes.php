<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A WireGuard exit can now live on a fleet node instead of the web server.
 *
 * vpn_node_id set  → the node agent owns wg0: it keeps the private key on its
 *                    own disk, reports only the public key, and syncs its peer
 *                    set from the database. No `sudo wg` runs here for it.
 * vpn_node_id null → unchanged: the web server's own wg0, driven by sudo.
 *
 * private_key becomes nullable because node-managed rows store none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wireguard_servers', function (Blueprint $table) {
            $table->text('private_key')->nullable()->change();
        });

        Schema::table('wireguard_servers', function (Blueprint $table) {
            $table->foreignId('vpn_node_id')->nullable()->after('id')
                ->constrained('vpn_nodes')->cascadeOnDelete();
            $table->unique('vpn_node_id');
        });
    }

    public function down(): void
    {
        // Node-managed rows have no private key. Without the node they are
        // useless, so switch them off and give them a decryptable empty key
        // instead of deleting anything.
        DB::table('wireguard_servers')->whereNull('private_key')->update([
            'private_key' => Crypt::encryptString(''),
            'is_active' => false,
        ]);

        Schema::table('wireguard_servers', function (Blueprint $table) {
            $table->dropForeign(['vpn_node_id']);
            $table->dropUnique(['vpn_node_id']);
            $table->dropColumn('vpn_node_id');
        });

        Schema::table('wireguard_servers', function (Blueprint $table) {
            $table->text('private_key')->nullable(false)->change();
        });
    }
};
