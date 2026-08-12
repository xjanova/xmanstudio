<?php

use App\Models\TeamMember;
use Database\Seeders\TeamMemberSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Puts the two founders into team_members.
 *
 * This is a data migration on purpose: deploy runs `php artisan migrate --force`
 * but never runs seeders, so /team would keep rendering an empty leadership
 * section in production no matter how many times the seeder was committed.
 * TeamMemberSeeder uses updateOrCreate, so re-running is harmless — and edits
 * made later through the admin UI are preserved for any field the seeder does
 * not set.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('team_members')) {
            return;
        }

        (new TeamMemberSeeder)->run();
    }

    public function down(): void
    {
        if (! Schema::hasTable('team_members')) {
            return;
        }

        TeamMember::whereIn('name', ['Entony', 'Koranipa'])->delete();
    }
};
