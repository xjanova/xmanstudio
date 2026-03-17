<?php

namespace App\Console\Commands;

use App\Models\MetalXMediaLibrary;
use App\Models\MetalXMusicLibrary;
use App\Models\Setting;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MetalXImportFromUrl extends Command
{
    protected $signature = 'metalx:import-url
        {url : URL to download from}
        {--type=auto : Type: music, image, video, auto (detect from extension)}
        {--title= : Title for music tracks}
        {--tags= : Comma-separated tags}
        {--style=metal : Music style (metal, rock, electronic, etc.)}
        {--source=custom : Source (freepik, suno, custom, ai_generated)}
        {--filename= : Override filename}';

    protected $description = 'Import media or music from a URL directly to the server';

    public function handle(): int
    {
        $url = $this->argument('url');
        $type = $this->option('type');
        $tags = $this->option('tags') ? array_map('trim', explode(',', $this->option('tags'))) : null;

        $this->info("Downloading from: {$url}");

        try {
            $response = Http::timeout(120)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; MetalX/1.0)',
            ])->get($url);

            if (! $response->successful()) {
                $this->error("Download failed: HTTP {$response->status()}");

                return self::FAILURE;
            }

            $content = $response->body();
            $contentType = $response->header('Content-Type', '');
            $fileSize = strlen($content);

            $this->info('Downloaded: ' . number_format($fileSize) . " bytes ({$contentType})");

            // Determine filename
            $filename = $this->option('filename');
            if (! $filename) {
                $filename = $this->guessFilename($url, $contentType);
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Auto-detect type
            if ($type === 'auto') {
                $type = $this->detectType($extension, $contentType);
            }

            if ($type === 'music') {
                return $this->importMusic($content, $filename, $extension, $fileSize, $tags);
            }

            return $this->importMedia($content, $filename, $extension, $fileSize, $type, $tags);
        } catch (Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function importMusic(string $content, string $filename, string $extension, int $fileSize, ?array $tags): int
    {
        $dir = 'metal-x/music-library';
        Storage::disk('local')->makeDirectory($dir);

        $storedName = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '_' . Str::random(6) . '.' . $extension;
        $path = "{$dir}/{$storedName}";

        Storage::disk('local')->put($path, $content);

        $duration = $this->getAudioDuration(Storage::disk('local')->path($path));
        $title = $this->option('title') ?: pathinfo($filename, PATHINFO_FILENAME);

        $track = MetalXMusicLibrary::create([
            'title' => $title,
            'file_path' => $path,
            'style' => $this->option('style'),
            'tags' => $tags,
            'duration_seconds' => $duration ?? 0,
            'source' => $this->option('source'),
            'is_active' => true,
        ]);

        $this->info("Music imported: {$track->title} (ID: {$track->id}, Duration: {$duration}s)");

        return self::SUCCESS;
    }

    protected function importMedia(string $content, string $filename, string $extension, int $fileSize, string $type, ?array $tags): int
    {
        $dir = 'metal-x/media-library';
        Storage::disk('local')->makeDirectory($dir);

        $storedName = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '_' . Str::random(6) . '.' . $extension;
        $path = "{$dir}/{$storedName}";

        Storage::disk('local')->put($path, $content);

        $isVideo = in_array($extension, ['mp4', 'webm', 'mov']);
        $mediaType = $isVideo ? 'video_clip' : 'image';
        $duration = $isVideo ? $this->getVideoDuration(Storage::disk('local')->path($path)) : null;

        $media = MetalXMediaLibrary::create([
            'type' => $mediaType,
            'file_path' => $path,
            'filename' => $filename,
            'tags' => $tags,
            'source' => $this->option('source'),
            'file_size' => $fileSize,
            'duration_seconds' => $duration,
            'is_active' => true,
        ]);

        $this->info("Media imported: {$media->filename} (ID: {$media->id}, Type: {$mediaType})");

        return self::SUCCESS;
    }

    protected function detectType(string $extension, string $contentType): string
    {
        $musicExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $videoExtensions = ['mp4', 'webm', 'mov', 'avi'];

        if (in_array($extension, $musicExtensions) || str_starts_with($contentType, 'audio/')) {
            return 'music';
        }
        if (in_array($extension, $videoExtensions) || str_starts_with($contentType, 'video/')) {
            return 'video';
        }
        if (in_array($extension, $imageExtensions) || str_starts_with($contentType, 'image/')) {
            return 'image';
        }

        return 'image'; // default
    }

    protected function guessFilename(string $url, string $contentType): string
    {
        // Try to extract filename from URL path
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        $basename = basename($path);

        if ($basename && str_contains($basename, '.')) {
            return $basename;
        }

        // Guess extension from content type
        $extMap = [
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'audio/mp4' => 'm4a',
            'audio/x-m4a' => 'm4a',
            'audio/wav' => 'wav',
            'audio/ogg' => 'ogg',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $ext = $extMap[$contentType] ?? $extMap[explode(';', $contentType)[0]] ?? 'bin';

        return 'import_' . Str::random(8) . '.' . $ext;
    }

    protected function getAudioDuration(string $path): ?int
    {
        try {
            $ffprobe = Setting::getValue('ffprobe_binary', 'ffprobe');
            $result = shell_exec("{$ffprobe} -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($path) . ' 2>/dev/null');
            if ($result && is_numeric(trim($result))) {
                return (int) round((float) trim($result));
            }
        } catch (Exception $e) {
            // Ignore
        }

        return null;
    }

    protected function getVideoDuration(string $path): ?int
    {
        return $this->getAudioDuration($path); // Same ffprobe command
    }
}
