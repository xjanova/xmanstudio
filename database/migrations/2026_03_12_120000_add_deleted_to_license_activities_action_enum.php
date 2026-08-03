<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACTIONS_WITH_DELETED = [
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

    private const ACTIONS_WITHOUT_DELETED = [
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
    ];

    public function up(): void
    {
        $this->setActionEnum(self::ACTIONS_WITH_DELETED);
    }

    public function down(): void
    {
        $this->setActionEnum(self::ACTIONS_WITHOUT_DELETED);
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
        // so the column must be redefined for the added values to pass the CHECK constraint.
        Schema::table('license_activities', function (Blueprint $table) use ($actions) {
            $table->enum('action', $actions)->change();
        });
    }

    private function usesMySql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
