<?php

namespace App\Console\Commands;

use App\Models\AiprayAudioSample;
use App\Models\AiprayChant;
use App\Services\AiprayAutoLabeler;
use Illuminate\Console\Command;

/**
 * Loads the chants the Aipray app displays, so uploaded voice can be labelled
 * with the text of the line it was recorded for. The file comes from the app
 * repo: `dart run tool/export_chants.dart chants.json`.
 *
 * The app does not download chants from here; it ships its own copy. If the
 * two disagree, auto labels are wrong, so re-import after every app change.
 */
class AiprayImportChantsCommand extends Command
{
    protected $signature = 'aipray:import-chants
        {file=database/data/aipray_chants.json : JSON written by the app\'s tool/export_chants.dart}
        {--relabel : Rewrite auto labels of chants whose lines changed}
        {--dry-run}';

    protected $description = 'นำเข้าบทสวดจากแอป Aipray เพื่อใช้ติดข้อความกำกับเสียงสวดอัตโนมัติ';

    public function handle(AiprayAutoLabeler $labeler): int
    {
        $path = $this->argument('file');
        $chants = is_file(base_path($path)) ? json_decode(file_get_contents(base_path($path)), true)
            : (is_file($path) ? json_decode(file_get_contents($path), true) : null);
        if (! is_array($chants)) {
            $this->error("Cannot read chants from {$path}");

            return self::FAILURE;
        }

        $created = $updated = 0;
        $changed = [];
        foreach ($chants as $row) {
            if (! is_string($row['chant_id'] ?? null) || ! preg_match('/^[A-Za-z0-9_-]+$/', $row['chant_id'])
                || ! is_array($row['lines'] ?? null)) {
                $this->warn('Skipping a malformed entry.');

                continue;
            }
            $lines = array_values(array_map('strval', $row['lines']));
            $chant = AiprayChant::where('chant_id', $row['chant_id'])->first();

            if ($chant && array_values((array) $chant->lines) !== $lines) {
                $changed[] = $row['chant_id'];
            }
            if ($this->option('dry-run')) {
                $chant ? $updated++ : $created++;

                continue;
            }

            $attributes = [
                'title_th' => $row['title_th'] ?? $row['chant_id'],
                'title_en' => $row['title_en'] ?? null,
                'category' => $row['category'] ?? 'daily',
                'lines' => $lines,
                'sort_order' => $row['sort_order'] ?? 0,
            ];
            if ($chant) {
                $chant->update($attributes);
                $updated++;
            } else {
                AiprayChant::create($attributes + ['chant_id' => $row['chant_id'], 'is_active' => true, 'is_community' => false]);
                $created++;
            }
        }

        $this->info("Created {$created}, updated {$updated} chants.");
        if ($changed) {
            $this->warn('Lines changed for: ' . implode(', ', $changed));
            if ($this->option('relabel') && ! $this->option('dry-run')) {
                $this->info('Relabeled ' . $this->relabel($changed, $labeler) . ' auto-labeled samples.');
            } else {
                $this->warn('Their auto-labeled samples still carry the old text; run again with --relabel.');
            }
        }

        return self::SUCCESS;
    }

    private function relabel(array $chantIds, AiprayAutoLabeler $labeler): int
    {
        $count = 0;
        AiprayAudioSample::whereIn('chant_id', $chantIds)
            ->where('status', AiprayAutoLabeler::STATUS)
            ->chunkById(500, function ($samples) use ($labeler, &$count) {
                foreach ($samples as $sample) {
                    $text = $labeler->transcriptFor($sample->chant_id, $sample->line_index);
                    $sample->update($text
                        ? ['transcript' => $text]
                        : ['transcript' => null, 'status' => 'unlabeled']);
                    $count++;
                }
            });

        return $count;
    }
}
