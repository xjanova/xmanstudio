<?php

namespace App\Console\Commands;

use App\Services\RentalService;
use Illuminate\Console\Command;

/**
 * Mark rentals whose time ran out as expired.
 *
 * RentalService::processExpiredRentals existed but nothing ever called it, so a rental stayed
 * "active" in the database after its expiry date (pages that compared dates still showed it as
 * over). For XMAN MLM rentals this run is also what tells mlm.xman4289.com (rental.expired).
 */
class ExpireRentals extends Command
{
    protected $signature = 'rentals:expire';

    protected $description = 'Mark rentals past their expiry date as expired (and notify XMAN MLM)';

    public function handle(RentalService $rentals): int
    {
        $count = $rentals->processExpiredRentals();

        $this->info("Expired {$count} rental(s).");

        return self::SUCCESS;
    }
}
