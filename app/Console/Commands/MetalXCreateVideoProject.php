<?php

namespace App\Console\Commands;

use App\Jobs\RenderVideoJob;
use App\Models\MetalXChannel;
use App\Models\MetalXMediaLibrary;
use App\Models\MetalXMusicLibrary;
use App\Models\MetalXVideoProject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MetalXCreateVideoProject extends Command
{
    protected $signature = 'metalx:create-video-project
        {--mode=video_clips : Media mode: images, video_clips, mixed}
        {--title= : Project title}
        {--auto-upload : Auto-upload to YouTube after render}
        {--privacy=public : Privacy status: public, unlisted, private}';

    protected $description = 'Create a Metal-X video project from library content and dispatch render';

    public function handle(): int
    {
        $mode = $this->option('mode');
        $autoUpload = $this->option('auto-upload');

        // Get media based on mode
        $query = MetalXMediaLibrary::active();
        if ($mode === 'video_clips') {
            $query->videoClips();
        } elseif ($mode === 'images') {
            $query->images();
        }
        $media = $query->leastUsed()->get();

        $clips = $media->where('type', 'video_clip')->pluck('file_path')->values()->toArray();
        $images = $media->where('type', 'image')->pluck('file_path')->values()->toArray();

        if (empty($clips) && empty($images)) {
            $this->error('No media found in library!');

            return self::FAILURE;
        }

        $this->info('Media: ' . count($clips) . ' video clips, ' . count($images) . ' images');

        // Get music
        $music = MetalXMusicLibrary::active()->orderBy('usage_count')->first();
        if (! $music) {
            $this->error('No music found in library!');

            return self::FAILURE;
        }
        $this->info("Music: {$music->title} ({$music->duration_seconds}s)");

        // Get channel
        $channel = MetalXChannel::where('is_default', true)->first()
            ?? MetalXChannel::first();
        if (! $channel) {
            $this->error('No channel found!');

            return self::FAILURE;
        }

        // Build title
        $title = $this->option('title')
            ?: 'Cyberpunk Metal Apocalypse - AI Generated Video Clips [Metal-X]';

        // Create project
        $project = MetalXVideoProject::create([
            'channel_id' => $channel->id,
            'title' => $title,
            'description' => 'Epic cyberpunk metal music visualizer featuring AI-generated video clips. Dark cityscapes, neon lights, and metal warriors.',
            'tags' => ['metal', 'cyberpunk', 'ai-generated', 'metal-x', 'music-visualizer'],
            'privacy_status' => $this->option('privacy'),
            'template' => 'visualizer',
            'template_settings' => [
                'transition' => 'crossfade',
                'transition_duration' => 1,
                'effect' => $mode === 'video_clips' ? 'none' : 'ken_burns',
            ],
            'images' => $images ?: null,
            'video_clips' => $clips ?: null,
            'media_mode' => $mode,
            'status' => 'music_ready',
            'auto_generated' => true,
        ]);

        // Copy music to project dir
        $audioDir = "metal-x/projects/{$project->id}";
        $audioPath = $audioDir . '/audio_' . Str::random(8) . '.mp3';
        Storage::disk('local')->makeDirectory($audioDir);
        Storage::disk('local')->copy($music->file_path, $audioPath);

        $ts = $project->template_settings ?? [];
        $ts['audio_file'] = $audioPath;
        $project->update(['template_settings' => $ts]);
        $music->incrementUsage();

        // Increment media usage
        $media->each(fn ($item) => $item->incrementUsage());

        $this->info("Project #{$project->id} created: {$project->title}");
        $this->info("Mode: {$mode} | Clips: " . count($clips) . ' | Images: ' . count($images));
        $this->info("Audio: {$audioPath}");

        // Dispatch render
        RenderVideoJob::dispatch($project, $autoUpload);
        $this->info('RenderVideoJob dispatched!' . ($autoUpload ? ' (auto-upload enabled)' : ''));

        return self::SUCCESS;
    }
}
