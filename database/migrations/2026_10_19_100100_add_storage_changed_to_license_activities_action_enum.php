<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LicenseActivity::ACTION_STORAGE_CHANGED — the admin changed the cloud space of
 * one key (admin ▸ License ▸ พื้นที่คลาวด์). Same approach as the 'deleted'
 * migration of 2026-03-12: MySQL gets its ENUM rewritten, SQLite its CHECK.
 */
return new class extends Migration
{
    private const ACTIONS = [
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
        'deleted',
    ];

    public function up(): void
    {
        $this->setActionEnum([...self::ACTIONS, 'storage_changed']);
    }

    public function down(): void
    {
        // Rows logged with the new value would break the narrower ENUM. Keep them
        // (their notes + metadata still say what changed) under the nearest old action.
        DB::table('license_activities')->where('action', 'storage_changed')->update(['action' => 'extended']);
        $this->setActionEnum(self::ACTIONS);
    }

    /**
     * @param  list<string>  $actions
     */
    private function setActionEnum(array $actions): void
    {
        if ($this->usesMySql()) {
            $values = collect($actions)->map(fn (string $action) => "'{$action}'")->implode(', ');

            DB::statement("ALTER TABLE `license_activities` MODIFY `action` ENUM({$values}) NOT NULL");

            return;
        }

        // SQLite has no ENUM type: Laravel renders it as `varchar check (col in (...))`,
        // so the column must be redefined for the added value to pass the CHECK constraint.
        Schema::table('license_activities', function (Blueprint $table) use ($actions) {
            $table->enum('action', $actions)->change();
        });
    }

    private function usesMySql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
