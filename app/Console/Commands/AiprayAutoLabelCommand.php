<?php

namespace App\Console\Commands;

use App\Models\AiprayAudioSample;
use App\Services\AiprayAutoLabeler;
use Illuminate\Console\Command;

/**
 * Labels app uploads that arrived before auto-labelling existed, or before
 * their chant was imported (aipray:import-chants).
 */
class AiprayAutoLabelCommand extends Command
{
    protected $signature = 'aipray:auto-label {--dry-run}';

    protected $description = 'เติมข้อความกำกับให้เสียงสวดที่ยังไม่มี จากบทสวดและลำดับบรรทัด';

    public function handle(AiprayAutoLabeler $labeler): int
    {
        $labeled = 0;
        $missing = 0;

        AiprayAudioSample::where('status', 'unlabeled')
            ->whereNull('transcript')
            ->whereNotNull('chant_id')
            ->whereNotNull('line_index')
            ->chunkById(500, function ($samples) use ($labeler, &$labeled, &$missing) {
                foreach ($samples as $sample) {
                    $text = $labeler->transcriptFor($sample->chant_id, $sample->line_index);
                    if ($text === null) {
                        $missing++;

                        continue;
                    }
                    if (! $this->option('dry-run')) {
                        $sample->update(['transcript' => $text, 'status' => AiprayAutoLabeler::STATUS]);
                    }
                    $labeled++;
                }
            });

        $this->info(($this->option('dry-run') ? 'Would label ' : 'Labeled ') . "{$labeled} samples; {$missing} have no matching chant line.");

        return self::SUCCESS;
    }
}
