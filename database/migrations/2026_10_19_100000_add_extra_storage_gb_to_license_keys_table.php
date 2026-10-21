<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cloud space the admin added to one key on top of its plan's base
 * (App\Support\CloudStorage). 0 = the plan's base only, so every existing key
 * keeps exactly what it had.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('license_keys', 'extra_storage_gb')) {
            return;
        }

        Schema::table('license_keys', function (Blueprint $table) {
            $table->unsignedInteger('extra_storage_gb')->default(0)->after('max_activations');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('license_keys', 'extra_storage_gb')) {
            Schema::table('license_keys', function (Blueprint $table) {
                $table->dropColumn('extra_storage_gb');
            });
        }
    }
};
