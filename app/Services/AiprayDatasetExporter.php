<?php

namespace App\Services;

use App\Models\AiprayAudioSample;
use App\Models\AiprayTrainingJob;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Packs the trainable samples into one zip a rented GPU downloads through a
 * signed URL (see the Aipray repo's training/ml-service/app/remote.py):
 *
 *     manifest.jsonl   {"id", "file", "transcript", "chant_id", "line_index", "duration", "format"}
 *     audio/<id>.<format>
 *
 * Audio is already compressed (AAC), so entries are stored, not deflated.
 * The archive is a copy of personal data: it is deleted when the job ends,
 * and samples erased later never reach the next export.
 */
class AiprayDatasetExporter
{
    public const DIR = 'aipray_datasets';

    public function __construct(private AiprayAudioStorage $storage) {}

    /** @return array{path: string, samples: int, seconds: float, bytes: int} */
    public function export(AiprayTrainingJob $job): array
    {
        $disk = Storage::disk(AiprayAudioStorage::DISK);
        $path = self::DIR . "/job-{$job->id}.zip";
        $disk->makeDirectory(self::DIR);

        $zip = new ZipArchive;
        if ($zip->open($disk->path($path), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create {$path}");
        }

        $manifest = [];
        $seconds = 0.0;
        AiprayAudioSample::trainable()->chunkById(500, function ($samples) use ($zip, &$manifest, &$seconds) {
            foreach ($samples as $sample) {
                $source = $this->storage->absolutePath($sample->file_path);
                if ($source === null) {
                    continue;
                }
                $format = in_array($sample->format, ['m4a', 'wav'], true) ? $sample->format : 'wav';
                $name = "audio/{$sample->id}.{$format}";
                $zip->addFile($source, $name);
                $zip->setCompressionName($name, ZipArchive::CM_STORE);
                $manifest[] = json_encode([
                    'id' => $sample->id,
                    'file' => $name,
                    'transcript' => $sample->transcript,
                    'chant_id' => $sample->chant_id,
                    'line_index' => $sample->line_index,
                    'duration' => $sample->duration,
                    'format' => $format,
                ], JSON_UNESCAPED_UNICODE);
                $seconds += (float) $sample->duration;
            }
        });

        $zip->addFromString('manifest.jsonl', implode("\n", $manifest));
        // Files are read here, not at addFile().
        if (! $zip->close()) {
            throw new RuntimeException("Cannot write {$path}");
        }

        return [
            'path' => $path,
            'samples' => count($manifest),
            'seconds' => round($seconds, 1),
            'bytes' => $disk->size($path),
        ];
    }

    /** How big a run would be, without writing anything. */
    public function preview(): array
    {
        $query = AiprayAudioSample::trainable();

        return [
            'samples' => (clone $query)->count(),
            'seconds' => (float) (clone $query)->sum('duration'),
        ];
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(AiprayAudioStorage::DISK)->delete($path);
        }
    }
}
