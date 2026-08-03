<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->setLicenseTypeEnum(['demo', 'daily', 'weekly', 'monthly', 'yearly', 'lifetime', 'product', 'free']);
    }

    public function down(): void
    {
        $this->setLicenseTypeEnum(['demo', 'daily', 'weekly', 'monthly', 'yearly', 'lifetime', 'product']);
    }

    /**
     * @param  list<string>  $types
     */
    private function setLicenseTypeEnum(array $types): void
    {
        if ($this->usesMySql()) {
            $values = collect($types)->map(fn (string $type) => "'{$type}'")->implode(', ');

            DB::statement("ALTER TABLE license_keys MODIFY COLUMN license_type ENUM({$values}) DEFAULT 'product'");

            return;
        }

        // SQLite has no ENUM type: Laravel renders it as `varchar check (col in (...))`,
        // so the column must be redefined for the added values to pass the CHECK constraint.
        // nullable() mirrors MySQL: MODIFY COLUMN restates the whole definition, and omitting
        // NOT NULL above drops the original NOT NULL, so both drivers end up nullable.
        Schema::table('license_keys', function (Blueprint $table) use ($types) {
            $table->enum('license_type', $types)->nullable()->default('product')->change();
        });
    }

    private function usesMySql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
