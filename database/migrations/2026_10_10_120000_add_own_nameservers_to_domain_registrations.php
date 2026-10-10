<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The nameservers a domain had when we registered it — our own DNS.
 *
 * A customer can point the domain at Cloudflare in one click, and must be
 * able to come back the same way. Our nameservers are assigned per account
 * (byte/pixel on ours), not a fixed pair anyone could type, so the only
 * reliable source is what the domain had on day one. `nameservers` cannot
 * hold it: the daily sync overwrites it with whatever is live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('domain_registrations', 'own_nameservers')) {
                $table->json('own_nameservers')->nullable()->after('nameservers');
            }
        });

        // Domains sold before this column existed: their live nameservers are
        // ours only while they still point at our DNS.
        DB::table('domain_registrations')
            ->whereNull('own_nameservers')
            ->whereNotNull('nameservers')
            ->orderBy('id')
            ->get(['id', 'nameservers'])
            ->each(function ($row) {
                $servers = json_decode((string) $row->nameservers, true);

                if (! is_array($servers) || count($servers) < 2) {
                    return;
                }

                foreach ($servers as $ns) {
                    if (! is_string($ns) || ! str_ends_with(strtolower($ns), '.dns-parking.com')) {
                        return;
                    }
                }

                DB::table('domain_registrations')
                    ->where('id', $row->id)
                    ->update(['own_nameservers' => json_encode(array_values($servers))]);
            });
    }

    public function down(): void
    {
        Schema::table('domain_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('domain_registrations', 'own_nameservers')) {
                $table->dropColumn('own_nameservers');
            }
        });
    }
};
