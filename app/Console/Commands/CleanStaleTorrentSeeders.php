<?php

namespace App\Console\Commands;

use App\Models\BtFileSeeder;
use Illuminate\Console\Command;

class CleanStaleTorrentSeeders extends Command
{
    protected $signature = 'torrent:cleanup-seeders';

    protected $description = 'Mark stale torrent seeders as offline';

    public function handle(): int
    {
        $count = BtFileSeeder::where('is_online', true)
            ->where('last_seen_at', '<', now()->subMinutes(5))
            ->update(['is_online' => false]);

        $this->info("Marked {$count} stale seeders as offline.");

        return self::SUCCESS;
    }
}
