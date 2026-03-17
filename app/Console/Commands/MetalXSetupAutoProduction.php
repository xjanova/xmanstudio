<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MetalXAutomationSchedule;
use App\Models\MetalXChannel;
use App\Models\MetalXContentPlan;
use App\Models\MetalXMediaLibrary;
use App\Models\MetalXMusicLibrary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MetalXSetupAutoProduction extends Command
{
    protected $signature = 'metalx:setup-auto-production
        {--generate-now : Trigger immediate generation after setup}
        {--skip-media : Skip downloading sample media}
        {--skip-music : Skip music library setup}';

    protected $description = 'Setup Metal-X automatic video production: seed media, music, content plan, and automation schedule';

    // Sample cyberpunk/metal themed images from free sources
    private array $sampleImages = [
        ['url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1920&h=1080&fit=crop', 'tags' => ['cyberpunk', 'neon', 'city']],
        ['url' => 'https://images.unsplash.com/photo-1515705576963-95cad62945b6?w=1920&h=1080&fit=crop', 'tags' => ['dark', 'night', 'urban']],
        ['url' => 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=1920&h=1080&fit=crop', 'tags' => ['cyberpunk', 'tech', 'future']],
        ['url' => 'https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=1920&h=1080&fit=crop', 'tags' => ['tech', 'dark', 'digital']],
        ['url' => 'https://images.unsplash.com/photo-1535223289827-42f1e9919769?w=1920&h=1080&fit=crop', 'tags' => ['cyberpunk', 'neon', 'street']],
        ['url' => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=1920&h=1080&fit=crop', 'tags' => ['neon', 'light', 'abstract']],
        ['url' => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=1920&h=1080&fit=crop', 'tags' => ['tech', 'office', 'modern']],
        ['url' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1920&h=1080&fit=crop', 'tags' => ['tech', 'circuit', 'digital']],
        ['url' => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=1920&h=1080&fit=crop', 'tags' => ['matrix', 'code', 'digital']],
        ['url' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1920&h=1080&fit=crop', 'tags' => ['space', 'earth', 'dark']],
        ['url' => 'https://images.unsplash.com/photo-1534996858221-380b92700493?w=1920&h=1080&fit=crop', 'tags' => ['cyberpunk', 'neon', 'rain']],
        ['url' => 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?w=1920&h=1080&fit=crop', 'tags' => ['gradient', 'abstract', 'color']],
    ];

    public function handle(): int
    {
        $this->info('🚀 Metal-X Auto Production Setup');
        $this->info('================================');

        // Step 1: Check channel
        $channel = $this->setupChannel();
        if (! $channel) {
            $this->error('❌ No active channel found. Please create a channel first.');

            return self::FAILURE;
        }
        $this->info("✅ Channel: {$channel->name} (ID: {$channel->id})");

        // Step 2: Seed media library
        if (! $this->option('skip-media')) {
            $this->seedMediaLibrary();
        } else {
            $this->info('⏭️ Skipping media download');
        }

        $mediaCount = MetalXMediaLibrary::active()->count();
        $this->info("📷 Media library: {$mediaCount} items");

        if ($mediaCount < 3) {
            $this->error('❌ Need at least 3 media items. Run without --skip-media');

            return self::FAILURE;
        }

        // Step 3: Check music
        if (! $this->option('skip-music')) {
            $this->checkMusicSetup();
        }

        // Step 4: Create content plan
        $plan = $this->createContentPlan($channel);
        $this->info("📋 Content Plan: {$plan->name} (ID: {$plan->id})");

        // Step 5: Setup auto_generate schedule
        $this->setupAutoGenerateSchedule();

        // Step 6: Summary
        $this->newLine();
        $this->info('🎉 Setup Complete!');
        $this->table(
            ['Item', 'Status'],
            [
                ['Channel', "{$channel->name}"],
                ['Media Items', MetalXMediaLibrary::active()->count()],
                ['Music Items', MetalXMusicLibrary::active()->count()],
                ['Content Plan', "{$plan->name} (enabled: " . ($plan->is_enabled ? 'YES' : 'NO') . ')'],
                ['Auto Generate Schedule', 'Active'],
                ['Next Generation', $plan->next_generation_at?->format('Y-m-d H:i:s') ?? 'NOW'],
            ]
        );

        // Step 7: Optional immediate generation
        if ($this->option('generate-now')) {
            $this->info('🔄 Triggering immediate generation...');
            try {
                $service = app(\App\Services\ContentPlanService::class);
                $project = $service->generateProject($plan);
                if ($project) {
                    $this->info("✅ Project created: {$project->title} (ID: {$project->id}, Status: {$project->status})");
                } else {
                    $this->warn('⚠️ Generation returned null — check logs for details');
                }
            } catch (\Exception $e) {
                $this->error("❌ Generation failed: {$e->getMessage()}");
            }
        } else {
            $this->info('💡 Tip: The scheduler will auto-generate at the next run (every 5 min)');
            $this->info('   Or run: php artisan metalx:setup-auto-production --generate-now');
        }

        return self::SUCCESS;
    }

    protected function setupChannel(): ?MetalXChannel
    {
        $channel = MetalXChannel::where('is_active', true)->first();

        if (! $channel) {
            // Try default
            $channel = MetalXChannel::first();
        }

        return $channel;
    }

    protected function seedMediaLibrary(): void
    {
        $this->info('📥 Downloading sample media...');

        $dir = 'metal-x/media-library';
        Storage::disk('local')->makeDirectory($dir);

        $downloaded = 0;
        $bar = $this->output->createProgressBar(count($this->sampleImages));
        $bar->start();

        foreach ($this->sampleImages as $i => $imageData) {
            $filename = 'cyberpunk_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT) . '_' . Str::random(6) . '.jpg';
            $filePath = "{$dir}/{$filename}";

            // Skip if we already have enough media
            if (MetalXMediaLibrary::active()->count() >= 12) {
                $bar->advance();

                continue;
            }

            try {
                $response = Http::timeout(30)->get($imageData['url']);

                if ($response->successful()) {
                    Storage::disk('local')->put($filePath, $response->body());

                    MetalXMediaLibrary::create([
                        'type' => 'image',
                        'file_path' => $filePath,
                        'filename' => $filename,
                        'tags' => $imageData['tags'],
                        'source' => 'custom',
                        'source_id' => 'unsplash_sample_' . ($i + 1),
                        'file_size' => strlen($response->body()),
                        'resolution' => '1920x1080',
                        'is_active' => true,
                    ]);

                    $downloaded++;
                }
            } catch (\Exception $e) {
                $this->warn("  ⚠️ Failed: {$filename} — {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Downloaded {$downloaded} images");
    }

    protected function checkMusicSetup(): void
    {
        $musicCount = MetalXMusicLibrary::active()->count();

        if ($musicCount > 0) {
            $this->info("🎵 Music library has {$musicCount} tracks");

            return;
        }

        // Check if Suno API is configured
        $sunoKey = config('metalx.suno.api_key');
        if (! empty($sunoKey)) {
            $this->info('🎵 No library music — Suno API is configured, will generate music');

            return;
        }

        $this->warn('⚠️ No music in library and Suno API not configured');
        $this->warn('   Music will be needed for video rendering.');
        $this->warn('   Options:');
        $this->warn('   1. Upload music via admin panel: /admin/metal-x/music-library');
        $this->warn('   2. Set SUNO_API_KEY in .env for AI music generation');
        $this->warn('   3. The system will try to generate without music (may fail)');
    }

    protected function createContentPlan(MetalXChannel $channel): MetalXContentPlan
    {
        // Check if a plan already exists
        $existing = MetalXContentPlan::where('channel_id', $channel->id)->first();
        if ($existing) {
            $this->info('📋 Content plan already exists, updating...');
            $existing->update([
                'is_enabled' => true,
                'next_generation_at' => now(),
            ]);

            return $existing->fresh();
        }

        return MetalXContentPlan::create([
            'channel_id' => $channel->id,
            'name' => 'Cyberpunk Metal Visualizer — Auto',
            'topic_prompt' => 'Metal music visualizer with cyberpunk aesthetics, neon lights, dark cityscapes, and futuristic AI art. สร้างวิดีโอ Metal ที่มีภาพ AI cyberpunk สวยงาม เหมาะสำหรับฟังเพลงเมทัล',
            'music_prompt' => 'Epic cyberpunk metal music, heavy guitar riffs, electronic synths, dark and powerful atmosphere, industrial metal fusion',
            'music_style' => 'metal',
            'music_duration' => 60,
            'template' => 'visualizer',
            'template_settings' => [
                'slide_duration' => 5,
                'transition' => 'crossfade',
                'transition_duration' => 1,
                'effect' => 'ken_burns',
                'background_color' => '#000000',
            ],
            'eq_settings' => [
                'enabled' => true,
                'style' => 'showcqt',
                'position' => 'bottom',
                'height_percent' => 20,
                'opacity' => 0.7,
                'color' => '#00ff88',
            ],
            'media_mode' => 'images',
            'privacy_status' => 'private',
            'media_pool_tags' => ['cyberpunk', 'neon', 'dark', 'tech'],
            'media_count' => 6,
            'schedule_frequency_hours' => 24,
            'preferred_publish_hour' => 18,
            'preferred_publish_days' => [1, 3, 5], // Mon, Wed, Fri
            'is_enabled' => true,
            'max_queue_size' => 3,
            'next_generation_at' => now(),
            'total_generated' => 0,
        ]);
    }

    protected function setupAutoGenerateSchedule(): void
    {
        $existing = MetalXAutomationSchedule::where('action_type', 'auto_generate')->first();

        if ($existing) {
            if (! $existing->is_enabled) {
                $existing->update([
                    'is_enabled' => true,
                    'next_run_at' => now(),
                ]);
                $this->info('✅ Auto-generate schedule re-enabled');
            } else {
                $this->info('✅ Auto-generate schedule already active');
            }

            return;
        }

        MetalXAutomationSchedule::create([
            'action_type' => 'auto_generate',
            'is_enabled' => true,
            'frequency_minutes' => 360, // Every 6 hours
            'max_actions_per_run' => 1,
            'next_run_at' => now(),
            'settings' => [
                'description' => 'สร้างวิดีโอจาก Content Plan อัตโนมัติ',
            ],
        ]);

        $this->info('✅ Auto-generate schedule created (every 6 hours)');
    }
}
