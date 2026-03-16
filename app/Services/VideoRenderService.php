<?php

namespace App\Services;

use App\Models\MetalXVideoProject;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class VideoRenderService
{
    protected string $ffmpeg;

    protected string $ffprobe;

    protected string $resolution;

    protected int $fps;

    public function __construct()
    {
        $this->ffmpeg = config('metalx.ffmpeg.binary', 'ffmpeg');
        $this->ffprobe = config('metalx.ffmpeg.ffprobe_binary', 'ffprobe');
        $this->resolution = config('metalx.ffmpeg.default_resolution', '1920x1080');
        $this->fps = config('metalx.ffmpeg.default_fps', 30);
    }

    /**
     * Render a video project using FFmpeg.
     */
    public function render(MetalXVideoProject $project): string
    {
        $images = $project->images ?? [];
        $audioPath = $project->musicGeneration?->audio_path;

        if (empty($images)) {
            throw new \RuntimeException('No images provided for rendering');
        }

        if (empty($audioPath) || ! Storage::disk('local')->exists($audioPath)) {
            throw new \RuntimeException('Audio file not found');
        }

        $outputDir = 'metal-x/videos';
        Storage::disk('local')->makeDirectory($outputDir);

        $outputPath = "{$outputDir}/{$project->id}.mp4";
        $outputFullPath = Storage::disk('local')->path($outputPath);
        $audioFullPath = Storage::disk('local')->path($audioPath);

        // Get template settings
        $slideDuration = $project->getTemplateSetting('slide_duration', 5);
        $transition = $project->getTemplateSetting('transition', 'crossfade');
        $transitionDuration = $project->getTemplateSetting('transition_duration', 1);
        $effect = $project->getTemplateSetting('effect', 'ken_burns');
        $bgColor = $project->getTemplateSetting('background_color', '#000000');

        [$width, $height] = explode('x', $this->resolution);

        // Get audio duration to calculate proper timing
        $audioDuration = $this->getAudioDuration($audioFullPath);

        // Build FFmpeg command
        $command = $this->buildFfmpegCommand(
            $images,
            $audioFullPath,
            $outputFullPath,
            (int) $width,
            (int) $height,
            $slideDuration,
            $transition,
            $transitionDuration,
            $effect,
            $bgColor,
            $audioDuration,
        );

        Log::info("[VideoRender] Starting render for project {$project->id}");

        $result = Process::timeout(config('metalx.ffmpeg.timeout', 600))->run($command);

        if (! $result->successful()) {
            $error = \Illuminate\Support\Str::limit($result->errorOutput(), 500);
            Log::error("[VideoRender] FFmpeg failed: {$error}");

            throw new \RuntimeException("FFmpeg rendering failed: {$error}");
        }

        // Get video duration
        $videoDuration = $this->getAudioDuration($outputFullPath);

        $project->update([
            'video_file_path' => $outputPath,
            'video_duration_seconds' => (int) $videoDuration,
        ]);

        Log::info("[VideoRender] Render complete for project {$project->id}: {$outputPath}");

        return $outputPath;
    }

    /**
     * Build the FFmpeg command for rendering.
     */
    protected function buildFfmpegCommand(
        array $images,
        string $audioPath,
        string $outputPath,
        int $width,
        int $height,
        float $slideDuration,
        string $transition,
        float $transitionDuration,
        string $effect,
        string $bgColor,
        ?float $audioDuration,
    ): string {
        $imageCount = count($images);

        // If we have audio duration, adjust slide timing to fill
        if ($audioDuration && $audioDuration > 0) {
            $slideDuration = $audioDuration / $imageCount;
        }

        // Build input arguments
        $inputs = '';
        $filterParts = [];

        foreach ($images as $i => $imagePath) {
            $fullPath = $this->resolveImagePath($imagePath);
            $escapedPath = escapeshellarg($fullPath);
            $inputs .= " -loop 1 -t {$slideDuration} -i {$escapedPath}";

            // Scale and pad each image to target resolution
            $filterParts[] = "[{$i}:v]scale={$width}:{$height}:force_original_aspect_ratio=decrease," .
                "pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color={$bgColor}," .
                "setsar=1,fps={$this->fps}" .
                $this->getEffectFilter($effect, $width, $height, $slideDuration) .
                "[v{$i}]";
        }

        // Add audio input
        $escapedAudio = escapeshellarg($audioPath);
        $inputs .= " -i {$escapedAudio}";
        $audioIndex = $imageCount;

        // Build concat or xfade filter
        $filter = implode('; ', $filterParts);

        if ($imageCount === 1) {
            $filter .= '; [v0]null[outv]';
        } elseif ($transition === 'crossfade' && $imageCount > 1) {
            // Chain xfade transitions
            $prev = 'v0';
            for ($i = 1; $i < $imageCount; $i++) {
                $offset = ($i * $slideDuration) - $transitionDuration;
                $offset = max(0, $offset);
                $out = ($i < $imageCount - 1) ? "xf{$i}" : 'outv';
                $filter .= "; [{$prev}][v{$i}]xfade=transition=fade:duration={$transitionDuration}:offset={$offset}[{$out}]";
                $prev = $out;
            }
        } else {
            // Simple concat
            $concatInputs = '';
            for ($i = 0; $i < $imageCount; $i++) {
                $concatInputs .= "[v{$i}]";
            }
            $filter .= "; {$concatInputs}concat=n={$imageCount}:v=1:a=0[outv]";
        }

        $escapedOutput = escapeshellarg($outputPath);
        $codec = config('metalx.ffmpeg.codec', 'libx264');
        $audioCodec = config('metalx.ffmpeg.audio_codec', 'aac');
        $threads = config('metalx.ffmpeg.threads', 2);

        return "{$this->ffmpeg}{$inputs} -filter_complex \"{$filter}\" " .
            "-map \"[outv]\" -map {$audioIndex}:a " .
            "-c:v {$codec} -preset medium -crf 23 " .
            "-c:a {$audioCodec} -b:a 192k " .
            '-shortest -movflags +faststart ' .
            "-threads {$threads} " .
            "-y {$escapedOutput}";
    }

    /**
     * Get the effect filter string for a single image.
     */
    protected function getEffectFilter(string $effect, int $width, int $height, float $duration): string
    {
        return match ($effect) {
            'ken_burns' => ",zoompan=z='min(zoom+0.0015,1.3)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=" . ($duration * $this->fps) . ":s={$width}x{$height}:fps={$this->fps}",
            'slide_left' => ",zoompan=z='1':x='iw*(on/({$duration}*{$this->fps}))':y='0':d=" . ($duration * $this->fps) . ":s={$width}x{$height}:fps={$this->fps}",
            'zoom' => ",zoompan=z='if(lte(zoom,1.0),1.3,max(1.001,zoom-0.003))':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=" . ($duration * $this->fps) . ":s={$width}x{$height}:fps={$this->fps}",
            default => '',
        };
    }

    /**
     * Get duration of a media file in seconds.
     */
    public function getAudioDuration(string $filePath): ?float
    {
        try {
            $escaped = escapeshellarg($filePath);
            $result = Process::timeout(10)->run(
                "{$this->ffprobe} -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$escaped}"
            );

            if ($result->successful()) {
                return (float) trim($result->output());
            }
        } catch (\Exception $e) {
            Log::warning("[VideoRender] Could not get duration: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Resolve image path to full filesystem path.
     */
    protected function resolveImagePath(string $path): string
    {
        // If it's already an absolute path
        if (str_starts_with($path, '/') || preg_match('/^[A-Z]:\\\\/i', $path)) {
            return $path;
        }

        // Try storage path
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        // Try public path
        if (file_exists(public_path($path))) {
            return public_path($path);
        }

        return $path;
    }

    /**
     * Get available video templates with their default settings.
     */
    public static function getTemplates(): array
    {
        return config('metalx.video_templates', [
            'visualizer' => [
                'name' => 'Visualizer',
                'slide_duration' => 5,
                'transition' => 'crossfade',
                'transition_duration' => 1,
                'effect' => 'ken_burns',
                'background_color' => '#000000',
            ],
            'slideshow' => [
                'name' => 'Slideshow',
                'slide_duration' => 4,
                'transition' => 'fade',
                'transition_duration' => 0.5,
                'effect' => 'none',
                'background_color' => '#1a1a1a',
            ],
        ]);
    }
}
