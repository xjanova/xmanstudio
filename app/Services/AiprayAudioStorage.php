<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Where Aipray voice samples live on disk.
 *
 * Voice is personal data, so every new file goes to the private `local` disk
 * and admins hear it through an authenticated route. Samples recorded in the
 * admin panel before 2026-09 were written to the `public` disk (reachable by
 * URL); reads fall back to it and deletes clear both, until
 * `aipray:privatize-audio` has moved those files.
 */
class AiprayAudioStorage
{
    public const DISK = 'local';

    public const LEGACY_DISK = 'public';

    public const MIME_TYPES = [
        'm4a' => 'audio/mp4',
        'wav' => 'audio/wav',
    ];

    public function put(string $path, string $bytes): void
    {
        Storage::disk(self::DISK)->put($path, $bytes);
    }

    /** Absolute path of the file, or null if it is on neither disk. */
    public function absolutePath(string $path): ?string
    {
        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->path($path);
            }
        }

        return null;
    }

    public function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
        Storage::disk(self::LEGACY_DISK)->delete($path);
    }

    public static function mimeType(?string $format): string
    {
        return self::MIME_TYPES[$format ?? 'wav'] ?? 'application/octet-stream';
    }
}
