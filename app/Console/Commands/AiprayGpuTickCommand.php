<?php

namespace App\Console\Commands;

use App\Services\AiprayGpuTrainingRunner;
use Illuminate\Console\Command;

/**
 * Moves rented-GPU training runs forward and makes sure machines stop.
 *
 * With rental off (the default) and no run left over, this does nothing: one
 * small query, no call to SimplePod. A machine rented before rental was
 * switched off is still driven to the end and returned.
 */
class AiprayGpuTickCommand extends Command
{
    protected $signature = 'aipray:gpu-tick';

    protected $description = 'ขับเคลื่อนงานเทรน Aipray บน GPU เช่า และคืนเครื่องเมื่อจบ';

    public function handle(AiprayGpuTrainingRunner $runner): int
    {
        if (! $runner->shouldTick()) {
            return self::SUCCESS;
        }

        if (! config('services.simplepod.api_key')) {
            // Only reachable with a run left over: it may be billing.
            $this->error('SIMPLEPOD_API_KEY is missing while GPU jobs are active; machines cannot be stopped.');

            return self::FAILURE;
        }

        $runner->tick();

        return self::SUCCESS;
    }
}
