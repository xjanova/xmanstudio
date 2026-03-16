<?php

namespace App\Jobs;

use App\Models\MetalXAutomationLog;
use App\Services\ContentPlanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoGenerateVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 300;

    public function handle(ContentPlanService $service): void
    {
        if (! config('metalx.features.content_plans', true)) {
            Log::info('[Metal-X Auto Generate] Content plans feature is disabled');

            return;
        }

        $plans = $service->getDuePlans();

        if ($plans->isEmpty()) {
            return;
        }

        Log::info("[Metal-X Auto Generate] Processing {$plans->count()} due content plans");

        foreach ($plans as $plan) {
            try {
                $project = $service->generateProject($plan);

                if ($project) {
                    Log::info("[Metal-X Auto Generate] Created project {$project->id} from plan: {$plan->name}");
                }
            } catch (\Exception $e) {
                Log::error("[Metal-X Auto Generate] Plan {$plan->id} ({$plan->name}) failed: {$e->getMessage()}");
                MetalXAutomationLog::log('auto_generate', 'failed', [
                    'plan_id' => $plan->id,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Metal-X Auto Generate] AutoGenerateVideoJob failed: ' . $exception->getMessage());
    }
}
