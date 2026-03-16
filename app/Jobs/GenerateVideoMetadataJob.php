<?php

namespace App\Jobs;

use App\Models\MetalXVideoProject;
use App\Services\YouTubeEngagementAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateVideoMetadataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public function __construct(
        protected MetalXVideoProject $project,
    ) {}

    public function handle(YouTubeEngagementAiService $aiService): void
    {
        $musicPrompt = $this->project->getTemplateSetting('music_prompt', '');
        $musicStyle = $this->project->musicGeneration?->style ?? '';
        $channelName = $this->project->channel?->name ?? 'Channel';

        $prompt = <<<PROMPT
You are a YouTube content creator for the channel "{$channelName}".

Generate metadata for a music video being uploaded to YouTube:

Music Style: {$musicStyle}
Music Theme/Prompt: {$musicPrompt}
Video Type: Visualizer with sliding images

Generate an engaging, SEO-optimized metadata in Thai language:

Guidelines:
1. Title should be catchy and include the music style (max 70 characters)
2. Description should be 3-5 paragraphs with relevant keywords
3. Include a call to action (subscribe, like, share)
4. Generate 10-15 relevant tags
5. All text in Thai primarily, with English music terms where appropriate

Respond with JSON only:
{
  "title": "video title in Thai",
  "description": "full description in Thai",
  "tags": ["tag1", "tag2", ...]
}
PROMPT;

        try {
            $result = $aiService->generateFromPrompt($prompt);

            $this->project->update([
                'title' => $result['title'] ?? $this->project->title,
                'description' => $result['description'] ?? $this->project->description,
                'tags' => $result['tags'] ?? $this->project->tags,
                'ai_metadata_generated' => true,
            ]);

            Log::info("[GenerateMetadata] AI metadata generated for project {$this->project->id}");
        } catch (\Exception $e) {
            Log::error("[GenerateMetadata] Failed for project {$this->project->id}: {$e->getMessage()}");
        }
    }
}
