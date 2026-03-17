<?php

namespace App\Services;

use App\Models\MetalXVideoProject;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoRenderService
{
    protected string $ffmpeg;

    protected string $ffprobe;

    protected string $resolution;

    protected int $fps;

    public function __construct()
    {
        $this->ffmpeg = (string) Setting::getValue('ffmpeg_binary') ?: config('metalx.ffmpeg.binary', 'ffmpeg');
        $this->ffprobe = (string) Setting::getValue('ffprobe_binary') ?: config('metalx.ffmpeg.ffprobe_binary', 'ffprobe');
        $this->resolution = (string) Setting::getValue('metalx_video_resolution') ?: config('metalx.ffmpeg.default_resolution', '1920x1080');
        $this->fps = config('metalx.ffmpeg.default_fps', 30);
    }

    /**
     * Render a video project using FFmpeg.
     */
    public function render(MetalXVideoProject $project): string
    {
        $images = $project->images ?? [];
        $videoClips = $project->video_clips ?? [];
        $mediaMode = $project->media_mode ?? 'images';
        $eqSettings = $project->eq_settings ?? null;
        $audioPath = $project->musicGeneration?->audio_path
            ?? data_get($project->template_settings, 'audio_file');

        // Build media items array based on mode
        $mediaItems = [];

        if ($mediaMode === 'images' || $mediaMode === 'mixed') {
            foreach ($images as $img) {
                $mediaItems[] = ['path' => $img, 'type' => 'image'];
            }
        }
        if ($mediaMode === 'video_clips' || $mediaMode === 'mixed') {
            foreach ($videoClips as $clip) {
                $mediaItems[] = ['path' => $clip, 'type' => 'video'];
            }
        }
        // Fallback: if no media items resolved, use images
        if (empty($mediaItems) && ! empty($images)) {
            foreach ($images as $img) {
                $mediaItems[] = ['path' => $img, 'type' => 'image'];
            }
        }

        if (empty($mediaItems)) {
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
        // Validate color to prevent FFmpeg command injection
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $bgColor)) {
            $bgColor = '#000000';
        }

        [$width, $height] = explode('x', $this->resolution);

        // Get audio duration to calculate proper timing
        $audioDuration = $this->getAudioDuration($audioFullPath);

        // Build FFmpeg command
        $command = $this->buildFfmpegCommand(
            $mediaItems,
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
            $eqSettings,
        );

        Log::info("[VideoRender] Starting render for project {$project->id}");

        $result = Process::timeout(config('metalx.ffmpeg.timeout', 600))->run($command);

        if (! $result->successful()) {
            $error = Str::limit($result->errorOutput(), 500);
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
     *
     * @param  array  $mediaItems  Each item: ['path' => '...', 'type' => 'image'|'video']
     * @param  ?array  $eqSettings  EQ overlay configuration (null = disabled)
     */
    protected function buildFfmpegCommand(
        array $mediaItems,
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
        ?array $eqSettings = null,
    ): string {
        $mediaCount = count($mediaItems);

        // If we have audio duration, adjust slide timing to fill
        if ($audioDuration && $audioDuration > 0) {
            $slideDuration = $audioDuration / $mediaCount;
        }

        // Build input arguments
        $inputs = '';
        $filterParts = [];

        foreach ($mediaItems as $i => $media) {
            $fullPath = $this->resolveMediaPath($media['path']);
            $escapedPath = escapeshellarg($fullPath);
            $type = $media['type'] ?? 'image';

            if ($type === 'video') {
                // Video clip input: loop to fill slide duration
                $inputs .= " -stream_loop -1 -i {$escapedPath}";

                // Trim to slide duration, scale and pad (no zoompan effects)
                $filterParts[] = "[{$i}:v]trim=duration={$slideDuration},setpts=PTS-STARTPTS," .
                    "scale={$width}:{$height}:force_original_aspect_ratio=decrease," .
                    "pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color={$bgColor}," .
                    "setsar=1,fps={$this->fps}[v{$i}]";
            } else {
                // Image input: loop with duration
                $inputs .= " -loop 1 -t {$slideDuration} -i {$escapedPath}";

                // Scale and pad each image to target resolution with effects
                $filterParts[] = "[{$i}:v]scale={$width}:{$height}:force_original_aspect_ratio=decrease," .
                    "pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color={$bgColor}," .
                    "setsar=1,fps={$this->fps}" .
                    $this->getEffectFilter($effect, $width, $height, $slideDuration) .
                    "[v{$i}]";
            }
        }

        // Add audio input
        $escapedAudio = escapeshellarg($audioPath);
        $inputs .= " -i {$escapedAudio}";
        $audioIndex = $mediaCount;

        // Build concat or xfade filter
        $filter = implode('; ', $filterParts);

        if ($mediaCount === 1) {
            $filter .= '; [v0]null[outv]';
        } elseif ($transition === 'crossfade' && $mediaCount > 1) {
            // Chain xfade transitions
            $prev = 'v0';
            for ($i = 1; $i < $mediaCount; $i++) {
                $offset = ($i * $slideDuration) - $transitionDuration;
                $offset = max(0, $offset);
                $out = ($i < $mediaCount - 1) ? "xf{$i}" : 'outv';
                $filter .= "; [{$prev}][v{$i}]xfade=transition=fade:duration={$transitionDuration}:offset={$offset}[{$out}]";
                $prev = $out;
            }
        } else {
            // Simple concat
            $concatInputs = '';
            for ($i = 0; $i < $mediaCount; $i++) {
                $concatInputs .= "[v{$i}]";
            }
            $filter .= "; {$concatInputs}concat=n={$mediaCount}:v=1:a=0[outv]";
        }

        // Apply EQ overlay if enabled
        $finalVideoLabel = 'outv';
        if (! empty($eqSettings) && ($eqSettings['enabled'] ?? false)) {
            $eqFilter = $this->buildEqOverlayFilter($eqSettings, $width, $height, $audioIndex);
            if ($eqFilter) {
                $filter .= '; ' . $eqFilter;
                $finalVideoLabel = 'finalv';
            }
        }

        $escapedOutput = escapeshellarg($outputPath);
        $codec = config('metalx.ffmpeg.codec', 'libx264');
        $audioCodec = config('metalx.ffmpeg.audio_codec', 'aac');
        $threads = config('metalx.ffmpeg.threads', 2);

        return "{$this->ffmpeg}{$inputs} -filter_complex \"{$filter}\" " .
            "-map \"[{$finalVideoLabel}]\" -map {$audioIndex}:a " .
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
        return $this->resolveMediaPath($path);
    }

    /**
     * Resolve media path (image or video) to full filesystem path.
     */
    protected function resolveMediaPath(string $path): string
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
     * Build FFmpeg filter for EQ/audio visualization overlay.
     *
     * @param  array  $eqSettings  Configuration: style, color, opacity, position, height_percent
     * @param  int  $width  Video width
     * @param  int  $height  Video height
     * @param  int  $audioIndex  FFmpeg input index for the audio stream
     */
    protected function buildEqOverlayFilter(array $eqSettings, int $width, int $height, int $audioIndex): string
    {
        $style = $eqSettings['style'] ?? 'showcqt';
        $color = $eqSettings['color'] ?? 'white';
        $opacity = $eqSettings['opacity'] ?? 0.6;
        $position = $eqSettings['position'] ?? 'bottom';
        $heightPercent = $eqSettings['height_percent'] ?? 15;

        $eqHeight = max(40, (int) ($height * $heightPercent / 100));

        // Build the visualization filter based on style
        $vizFilter = match ($style) {
            'showcqt' => "[{$audioIndex}:a]showcqt=s={$width}x{$eqHeight}:sono_h=0:bar_h={$eqHeight}:sono_g=4:bar_g=4:fontcolor=white@0.0:tc=0.33:tlength=0.5:count=6[eq_raw]",
            'showwaves' => "[{$audioIndex}:a]showwaves=s={$width}x{$eqHeight}:mode=cline:colors={$color}@{$opacity}:rate={$this->fps}[eq_raw]",
            'showfreqs' => "[{$audioIndex}:a]showfreqs=s={$width}x{$eqHeight}:mode=bar:fscale=log:win_size=2048[eq_raw]",
            'bars' => "[{$audioIndex}:a]showcqt=s={$width}x{$eqHeight}:sono_h=0:bar_h={$eqHeight}:sono_g=6:bar_g=6:fontcolor=white@0.0:tc=0.25:tlength=0.4:count=4[eq_raw]",
            default => "[{$audioIndex}:a]showcqt=s={$width}x{$eqHeight}:sono_h=0:bar_h={$eqHeight}:sono_g=4:bar_g=4:fontcolor=white@0.0:tc=0.33:tlength=0.5:count=6[eq_raw]",
        };

        // Calculate Y position for overlay
        $yPosition = match ($position) {
            'top' => '0',
            'center' => (string) (($height - $eqHeight) / 2),
            'bottom' => (string) ($height - $eqHeight),
            default => (string) ($height - $eqHeight),
        };

        // Apply transparency and overlay onto video
        $transparencyFilter = "[eq_raw]colorchannelmixer=aa={$opacity}[eqtrans]";
        $overlayFilter = "[outv][eqtrans]overlay=0:{$yPosition}:format=auto,format=yuv420p[finalv]";

        return "{$vizFilter}; {$transparencyFilter}; {$overlayFilter}";
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
