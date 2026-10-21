<?php

namespace App\Console\Commands;

use App\Models\AiprayTrainingJob;
use App\Services\AiprayGpuTrainingRunner;
use Illuminate\Console\Command;

/**
 * Moves rented-GPU training runs forward and makes sure machines stop.
 * Runs even with SIMPLEPOD_ENABLED off, because a machine rented before the
 * switch still has to be terminated.
 */
class AiprayGpuTickCommand extends Command
{
    protected $signature = 'aipray:gpu-tick';

    protected $description = 'ขับเคลื่อนงานเทรน Aipray บน GPU เช่า และคืนเครื่องเมื่อจบ';

    public function handle(AiprayGpuTrainingRunner $runner): int
    {
        if (! config('services.simplepod.api_key')) {
            if (AiprayTrainingJob::gpuActive()->exists()) {
                $this->error('SIMPLEPOD_API_KEY is missing while GPU jobs are active; machines cannot be stopped.');

                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        $runner->tick();

        return self::SUCCESS;
    }
}
