<?php

namespace App\Jobs;

use App\Models\MetalXVideo;
use App\Services\YouTubeMetadataAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateVideoMetadataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The video to generate metadata for.
     *
     * @var MetalXVideo
     */
    protected $video;

    /**
     * Auto-approve if confidence is high enough.
     *
     * @var bool
     */
    protected $autoApprove;

    /**
     * Minimum confidence score for auto-approval.
     *
     * @var float
     */
    protected $minConfidence;

    /**
     * Create a new job instance.
     */
    public function __construct(MetalXVideo $video, bool $autoApprove = false, float $minConfidence = 80.0)
    {
        $this->video = $video;
        $this->autoApprove = $autoApprove;
        $this->minConfidence = $minConfidence;
    }

    /**
     * Execute the job.
     */
    public function handle(YouTubeMetadataAiService $aiService): void
    {
        Log::info("Generating AI metadata for video: {$this->video->title_en} (ID: {$this->video->id})");

        try {
            $result = $aiService->generateMetadata($this->video);

            if (! $result['success']) {
                Log::error("Failed to generate metadata for video {$this->video->id}: {$result['error']}");
                $this->fail(new \Exception($result['error']));

                return;
            }

            // Save metadata
            $aiService->saveMetadata($this->video, $result['metadata'], $result['confidence']);

            Log::info("Generated AI metadata for video {$this->video->id} with confidence score: {$result['confidence']}");

            // Auto-approve if enabled and confidence is high enough
            if ($this->autoApprove && $result['confidence'] >= $this->minConfidence) {
                $aiService->approveMetadata($this->video->fresh(), 0); // 0 = system auto-approved
                Log::info("Auto-approved AI metadata for video {$this->video->id} (confidence: {$result['confidence']})");
            }
        } catch (\Exception $e) {
            Log::error("Exception while generating metadata for video {$this->video->id}: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateVideoMetadataJob failed for video {$this->video->id}: " . $exception->getMessage());
    }
}
