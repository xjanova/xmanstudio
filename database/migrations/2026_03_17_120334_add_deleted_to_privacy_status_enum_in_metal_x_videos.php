<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter enum to include 'deleted' value
        $this->setPrivacyStatusEnum(['public', 'private', 'unlisted', 'deleted']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any 'deleted' rows back to 'private'
        DB::table('metal_x_videos')->where('privacy_status', 'deleted')->update(['privacy_status' => 'private']);

        $this->setPrivacyStatusEnum(['public', 'private', 'unlisted']);
    }

    /**
     * @param  list<string>  $statuses
     */
    private function setPrivacyStatusEnum(array $statuses): void
    {
        if ($this->usesMySql()) {
            $values = collect($statuses)->map(fn (string $status) => "'{$status}'")->implode(', ');

            DB::statement("ALTER TABLE metal_x_videos MODIFY COLUMN privacy_status ENUM({$values}) DEFAULT 'public'");

            return;
        }

        // SQLite has no ENUM type: Laravel renders it as `varchar check (col in (...))`,
        // so the column must be redefined for the added values to pass the CHECK constraint.
        // nullable() mirrors MySQL: MODIFY COLUMN restates the whole definition, and omitting
        // NOT NULL above drops the original NOT NULL, so both drivers end up nullable.
        Schema::table('metal_x_videos', function (Blueprint $table) use ($statuses) {
            $table->enum('privacy_status', $statuses)->nullable()->default('public')->change();
        });
    }

    private function usesMySql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
