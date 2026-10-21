<?php

namespace App\Console\Commands;

use App\Models\AiprayAudioSample;
use App\Services\AiprayAudioStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Samples recorded in the admin panel before 2026-09 sit on the public disk,
 * where anyone with the URL can download them. Run once after deploying.
 */
class AiprayPrivatizeAudioCommand extends Command
{
    protected $signature = 'aipray:privatize-audio {--dry-run}';

    protected $description = 'ย้ายไฟล์เสียง Aipray ที่อยู่บน public disk ไปไว้ใน disk ส่วนตัว';

    public function handle(): int
    {
        $public = Storage::disk(AiprayAudioStorage::LEGACY_DISK);
        $private = Storage::disk(AiprayAudioStorage::DISK);
        $moved = 0;

        AiprayAudioSample::select(['id', 'file_path'])->chunkById(500, function ($samples) use ($public, $private, &$moved) {
            foreach ($samples as $sample) {
                if (! $public->exists($sample->file_path)) {
                    continue;
                }
                if (! $this->option('dry-run')) {
                    if (! $private->exists($sample->file_path)) {
                        $private->writeStream($sample->file_path, $public->readStream($sample->file_path));
                    }
                    $public->delete($sample->file_path);
                }
                $moved++;
            }
        });

        $this->info(($this->option('dry-run') ? 'Would move ' : 'Moved ') . "{$moved} files to the private disk.");

        return self::SUCCESS;
    }
}
