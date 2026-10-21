<?php

namespace App\Console\Commands;

use App\Models\AiprayAudioSample;
use App\Models\AiprayContributor;
use App\Services\AiprayAudioStorage;
use Illuminate\Console\Command;

class AiprayPruneAudioCommand extends Command
{
    protected $signature = 'aipray:prune-audio {--days= : Override services.aipray.audio_retention_days}';

    protected $description = 'ลบเสียงสวด Aipray ที่เก็บเกินระยะเวลาที่แจ้งผู้ใช้ไว้ พร้อมไฟล์เสียง';

    public function handle(AiprayAudioStorage $storage): int
    {
        $days = (int) ($this->option('days') ?? config('services.aipray.audio_retention_days'));
        if ($days <= 0) {
            $this->error('Retention must be at least 1 day.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;

        AiprayAudioSample::where('created_at', '<', $cutoff)
            ->chunkById(200, function ($samples) use ($storage, &$deleted) {
                foreach ($samples as $sample) {
                    $storage->delete($sample->file_path);
                    $sample->delete();
                    $deleted++;
                }
            });

        // A contributor with nothing left is just an id and a hash; drop it.
        $orphans = AiprayContributor::whereNotExists(function ($query) {
            $query->selectRaw('1')
                ->from('aipray_audio_samples')
                ->whereColumn('aipray_audio_samples.contributor_id', 'aipray_contributors.contributor_id');
        })->delete();

        $this->info("Deleted {$deleted} samples older than {$days} days and {$orphans} empty contributors.");

        return self::SUCCESS;
    }
}
