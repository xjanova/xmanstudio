<?php

namespace App\Services;

use App\Models\MetalXAutomationLog;
use App\Models\MetalXContentPlan;
use App\Models\MetalXMediaLibrary;
use App\Models\MetalXMusicLibrary;
use App\Models\MetalXVideoProject;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentPlanService
{
    /**
     * Get all content plans that are due for generation.
     */
    public function getDuePlans()
    {
        return MetalXContentPlan::enabled()
            ->due()
            ->with('channel')
            ->get()
            ->filter(fn ($plan) => $plan->canGenerate());
    }

    /**
     * Generate a video project from a content plan.
     */
    public function generateProject(MetalXContentPlan $plan): ?MetalXVideoProject
    {
        Log::info("[Content Plan] Generating project from plan: {$plan->name} (ID: {$plan->id})");

        // Select media items from library
        $mediaItems = $this->selectMedia($plan);
        if ($mediaItems->isEmpty()) {
            Log::warning("[Content Plan] No media available for plan: {$plan->name}");
            MetalXAutomationLog::log('auto_generate', 'failed', [
                'plan_id' => $plan->id,
                'error_message' => 'No media available in library matching tags',
            ]);

            return null;
        }

        // Separate images and video clips
        $images = $mediaItems->where('type', 'image')->pluck('file_path')->values()->toArray();
        $videoClips = $mediaItems->where('type', 'video_clip')->pluck('file_path')->values()->toArray();

        // Select or generate music
        $musicData = $this->resolveMusic($plan);

        // Generate AI metadata
        $metadata = $this->generateMetadata($plan);

        // Calculate publish time
        $scheduledAt = $plan->calculatePublishTime();

        // Determine initial status
        $status = 'draft';
        if ($musicData['source'] === 'library') {
            $status = 'music_ready';
        }

        // Create project
        $project = MetalXVideoProject::create([
            'channel_id' => $plan->channel_id,
            'content_plan_id' => $plan->id,
            'title' => $metadata['title'] ?? "Auto: {$plan->name} #" . ($plan->total_generated + 1),
            'description' => $metadata['description'] ?? '',
            'tags' => $metadata['tags'] ?? [],
            'privacy_status' => $plan->privacy_status,
            'template' => $plan->template,
            'template_settings' => $plan->template_settings,
            'eq_settings' => $plan->eq_settings,
            'images' => $images ?: null,
            'video_clips' => $videoClips ?: null,
            'media_mode' => $plan->media_mode,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'auto_generated' => true,
        ]);

        // Copy music file if from library
        if ($musicData['source'] === 'library' && ! empty($musicData['file_path'])) {
            $audioDir = "metal-x/projects/{$project->id}";
            $audioFilename = 'audio_' . Str::random(8) . '.mp3';
            $audioPath = $audioDir . '/' . $audioFilename;

            Storage::disk('local')->makeDirectory($audioDir);

            if (Storage::disk('local')->exists($musicData['file_path'])) {
                Storage::disk('local')->copy($musicData['file_path'], $audioPath);
            }

            $project->update(['video_file_path' => null]); // Will be set by render job
            // Store audio path - the render job reads from a known path
            $project->update(['status' => 'music_ready']);

            // Store audio path in a way RenderVideoJob can find it
            // RenderVideoJob looks for audio in project storage
            $this->storeAudioPath($project, $audioPath);
        }

        // Increment media usage counts
        $mediaItems->each(fn ($item) => $item->incrementUsage());

        // Update plan generation tracking
        $plan->calculateNextGeneration();

        // Dispatch pipeline
        if ($status === 'music_ready') {
            \App\Jobs\RenderVideoJob::dispatch($project);
            Log::info("[Content Plan] Dispatched RenderVideoJob for project {$project->id}");
        } else {
            // Need music generation first (Suno API mode)
            \App\Jobs\GenerateMusicJob::dispatch($project);
            $project->update(['status' => 'generating_music']);
            Log::info("[Content Plan] Dispatched GenerateMusicJob for project {$project->id}");
        }

        MetalXAutomationLog::log('auto_generate', 'success', [
            'plan_id' => $plan->id,
            'project_id' => $project->id,
            'details' => [
                'media_count' => $mediaItems->count(),
                'music_source' => $musicData['source'],
                'scheduled_at' => $scheduledAt->toDateTimeString(),
            ],
        ]);

        Log::info("[Content Plan] Project {$project->id} created from plan {$plan->name}, scheduled at {$scheduledAt}");

        return $project;
    }

    /**
     * Select media items from the library based on plan settings.
     */
    protected function selectMedia(MetalXContentPlan $plan)
    {
        $query = MetalXMediaLibrary::active()
            ->forMediaMode($plan->media_mode)
            ->leastUsed();

        // Filter by tags if set
        $tags = $plan->media_pool_tags;
        if (! empty($tags)) {
            $query->byTags($tags);
        }

        // Get more than needed and shuffle for variety
        $items = $query->limit($plan->media_count * 2)->get();

        if ($items->count() < $plan->media_count) {
            // Not enough with tags filter, try without
            if (! empty($tags)) {
                $remaining = $plan->media_count - $items->count();
                $additional = MetalXMediaLibrary::active()
                    ->forMediaMode($plan->media_mode)
                    ->leastUsed()
                    ->whereNotIn('id', $items->pluck('id'))
                    ->limit($remaining)
                    ->get();
                $items = $items->merge($additional);
            }
        }

        return $items->shuffle()->take($plan->media_count);
    }

    /**
     * Resolve music source: library or Suno API.
     */
    protected function resolveMusic(MetalXContentPlan $plan): array
    {
        $sunoMode = Setting::getValue('suno_mode', 'onsite');

        // Try music library first (for any mode except explicit API-only)
        if ($sunoMode !== 'api') {
            $music = MetalXMusicLibrary::active()
                ->byStyle($plan->music_style)
                ->leastUsed()
                ->first();

            if ($music) {
                $music->incrementUsage();

                return [
                    'source' => 'library',
                    'file_path' => $music->file_path,
                    'title' => $music->title,
                    'duration' => $music->duration_seconds,
                ];
            }

            // Try any style if specific style not available
            $music = MetalXMusicLibrary::active()
                ->leastUsed()
                ->first();

            if ($music) {
                $music->incrementUsage();

                return [
                    'source' => 'library',
                    'file_path' => $music->file_path,
                    'title' => $music->title,
                    'duration' => $music->duration_seconds,
                ];
            }
        }

        // Use Suno API mode
        if ($sunoMode === 'api') {
            return [
                'source' => 'suno_api',
                'file_path' => null,
                'prompt' => $plan->music_prompt,
                'style' => $plan->music_style,
                'duration' => $plan->music_duration,
            ];
        }

        // No music available
        Log::warning("[Content Plan] No music available for plan: {$plan->name}");

        return [
            'source' => 'none',
            'file_path' => null,
        ];
    }

    /**
     * Generate AI metadata for the video.
     * Note: YouTubeMetadataAiService::generateMetadata() expects a MetalXVideo model,
     * so we generate a simple metadata set from the plan's topic prompt instead.
     */
    protected function generateMetadata(MetalXContentPlan $plan): array
    {
        $channelName = $plan->channel?->name ?? 'Metal-X';
        $topicPrompt = $plan->topic_prompt;
        $style = $plan->music_style;
        $number = ($plan->total_generated ?? 0) + 1;

        // Generate a descriptive title and description from the topic prompt
        $title = Str::limit($topicPrompt, 80) . " #{$number}";
        $description = "{$topicPrompt}\n\n🎵 {$channelName} | Style: {$style}\n#metal #music #{$style}";
        $tags = [$channelName, $style, 'music', 'metal', 'visualizer', 'thai'];

        // Try AI generation if available
        try {
            $aiService = app(YouTubeMetadataAiService::class);
            $prompt = "สร้างชื่อวิดีโอ YouTube เป็นภาษาไทย สำหรับช่อง {$channelName}\n"
                . "หัวข้อ: {$topicPrompt}\nสไตล์เพลง: {$style}\n"
                . "ตอบเป็น JSON: {\"title_th\": \"...\", \"description_th\": \"...\", \"tags\": [\"...\"]}\n"
                . 'ตอบ JSON เท่านั้น ไม่ต้องมีข้อความอื่น';

            $response = $aiService->callCustomPrompt($prompt);

            $parsed = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && ! empty($parsed['title_th'])) {
                return [
                    'title' => $parsed['title_th'],
                    'description' => $parsed['description_th'] ?? $description,
                    'tags' => $parsed['tags'] ?? $tags,
                ];
            }
        } catch (\Exception $e) {
            Log::warning("[Content Plan] AI metadata generation failed: {$e->getMessage()}");
        }

        return [
            'title' => $title,
            'description' => $description,
            'tags' => $tags,
        ];
    }

    /**
     * Store audio path for the project (in a field the render job can find).
     */
    protected function storeAudioPath(MetalXVideoProject $project, string $audioPath): void
    {
        // The RenderVideoJob looks for audio in template_settings or a dedicated field
        // Store it in a way compatible with the existing render pipeline
        $settings = $project->template_settings ?? [];
        $settings['audio_file'] = $audioPath;
        $project->update(['template_settings' => $settings]);
    }
}
