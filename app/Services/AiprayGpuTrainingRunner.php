<?php

namespace App\Services;

use App\Models\AiprayAiModel;
use App\Models\AiprayTrainingJob;
use App\Services\Gpu\SimplePodClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fine-tunes the Aipray Whisper model on a rented GPU, one minute at a time.
 *
 * `aipray:gpu-tick` calls tick() every minute and each job moves through
 *     queued -> booting -> training -> collecting -> done | failed | cancelled
 *
 * The machine is driven from here, the way aixman drives its workers: it
 * gets a random per-job token and a signed, expiring dataset URL, and we poll
 * it and download the model from it. It never receives a credential of ours.
 *
 * Every way out of a job terminates the machine: success, failure, cancel,
 * the time limit and the budget cap. A termination that fails is retried on
 * every tick, and instances named `aipray-train-*` that no job knows are
 * swept.
 */
class AiprayGpuTrainingRunner
{
    public const NAME_PREFIX = 'aipray-train-';

    public function __construct(
        private SimplePodClient $pod,
        private AiprayDatasetExporter $exporter,
        private AiprayTrainingCostEstimator $estimator,
        private AiprayDatasetReadiness $readiness,
    ) {}

    public function enabled(): bool
    {
        return config('services.simplepod.enabled') && config('services.simplepod.api_key');
    }

    public function cheapestOffer(string $baseModel): ?array
    {
        $config = config('services.aipray_gpu');

        return $this->pod->offers([
            'min_vram_mb' => $config['min_vram_mb'][$baseModel] ?? $config['min_vram_mb']['default'],
            'max_price' => $config['max_price_per_hour'],
            'gpu_models' => $config['gpu_models'],
            'min_cuda' => $config['min_cuda'],
            'min_download_mbps' => 500,
        ])[0] ?? null;
    }

    /** The dataset as it stands, what training it would cost, and the machine it would get. */
    public function estimate(string $baseModel, int $epochs): array
    {
        $preview = $this->exporter->preview();
        $offer = null;
        if ($this->enabled()) {
            try {
                $offer = $this->cheapestOffer($baseModel);
            } catch (\Throwable $e) {
                Log::warning('[Aipray GPU] market unavailable for estimate: ' . $e->getMessage());
            }
        }

        return $preview + $this->estimator->estimate(
            $preview['samples'], $preview['seconds'], $baseModel, $epochs, $offer['price'] ?? null,
        ) + ['offer' => $offer];
    }

    /**
     * Creates a queued run and exports its dataset. Nothing is rented here;
     * the next tick rents, so an admin request never waits on the vendor.
     *
     * A real run needs AiprayDatasetReadiness to pass. A trial run (checking
     * the pipeline end to end, or calibrating the estimate) skips it but
     * still needs the min_samples floor, and must be asked for explicitly.
     */
    public function queue(array $params, float $maxCostUsd, bool $trial = false): AiprayTrainingJob
    {
        if (! $this->enabled()) {
            throw new RuntimeException('GPU rental is off (SIMPLEPOD_ENABLED / SIMPLEPOD_API_KEY).');
        }
        if (AiprayTrainingJob::gpuActive()->exists()) {
            throw new RuntimeException('Another GPU training run is still active.');
        }
        if (! $trial && ! ($readiness = $this->readiness->check())['ready']) {
            throw new RuntimeException('ข้อมูลเสียงยังไม่พอ: ' . implode(' · ', $readiness['missing']));
        }

        $estimate = $this->estimate($params['base_model'], (int) $params['epochs']);
        if ($estimate['samples'] < config('services.aipray_gpu.min_samples')) {
            throw new RuntimeException("Only {$estimate['samples']} trainable samples; not worth renting a GPU yet.");
        }
        if ($estimate['expected_usd'] > $maxCostUsd) {
            throw new RuntimeException("Estimated \${$estimate['expected_usd']} is above the \${$maxCostUsd} cap.");
        }

        $job = AiprayTrainingJob::create($params + [
            'dataset_filter' => $trial ? 'trial' : 'all',
            'runner' => 'gpu',
            'status' => 'pending',
            'gpu_state' => 'queued',
            'gpu_token' => Str::random(48),
            'estimated_cost_usd' => $estimate['expected_usd'],
            'max_cost_usd' => $maxCostUsd,
        ]);

        $dataset = $this->exporter->export($job);
        $job->update([
            'dataset_path' => $dataset['path'],
            'dataset_samples' => $dataset['samples'],
            'dataset_seconds' => $dataset['seconds'],
        ]);

        return $job;
    }

    public function cancel(AiprayTrainingJob $job): void
    {
        if (in_array($job->gpu_state, AiprayTrainingJob::GPU_ACTIVE_STATES, true)) {
            $job->update(['gpu_state' => 'cancelling']);
        }
    }

    public function tick(): void
    {
        foreach (AiprayTrainingJob::gpuActive()->get() as $job) {
            try {
                $this->step($job);
            } catch (\Throwable $e) {
                report($e);
                $this->fail($job, 'Runner error: ' . $e->getMessage());
            }
        }

        // Terminations that failed earlier: keep trying until the vendor agrees.
        AiprayTrainingJob::where('runner', 'gpu')
            ->whereNotNull('gpu_instance_id')
            ->whereNull('gpu_terminated_at')
            ->whereNotIn('gpu_state', AiprayTrainingJob::GPU_ACTIVE_STATES)
            ->each(fn ($job) => $this->terminate($job));

        $this->sweepOrphans();
    }

    public function step(AiprayTrainingJob $job): void
    {
        if ($job->gpu_state === 'cancelling') {
            $this->finish($job, 'cancelled', 'cancelled', 'Cancelled by an admin.');

            return;
        }

        if ($job->gpu_instance_id) {
            if ($job->gpu_deadline_at && now()->gte($job->gpu_deadline_at)) {
                $this->fail($job, 'Time limit reached.');

                return;
            }
            if ($job->max_cost_usd && $job->gpuCostSoFar() >= $job->max_cost_usd) {
                $this->fail($job, "Budget cap of \${$job->max_cost_usd} reached.");

                return;
            }
        }

        match ($job->gpu_state) {
            'queued' => $this->rent($job),
            'booting' => $this->boot($job),
            'training' => $this->watch($job),
            'collecting' => $this->collect($job),
            default => null,
        };
    }

    private function rent(AiprayTrainingJob $job): void
    {
        $config = config('services.aipray_gpu');
        if (! $this->enabled()) {
            $this->fail($job, 'GPU rental was switched off before a machine was rented.');

            return;
        }
        if ($job->created_at->lt(now()->subMinutes($config['queue_timeout_minutes']))) {
            $this->fail($job, "No suitable GPU under \${$config['max_price_per_hour']}/h for {$config['queue_timeout_minutes']} minutes.");

            return;
        }

        $offer = $this->cheapestOffer($job->base_model);
        if (! $offer) {
            return; // Try again next minute.
        }

        $template = $this->pod->ensureTemplate(
            'aipray-train', $config['image'], $config['image_tag'], $config['disk_gb'],
            [$config['port']], $this->bootScript(null),
        );
        $instanceId = $this->pod->rent(
            $offer['market'], $template, $this->bootScript($job),
            ['WORKER_TOKEN' => $job->gpu_token], self::NAME_PREFIX . $job->id,
        );

        $hours = min($config['max_lifetime_minutes'] / 60, $job->max_cost_usd / max(0.01, $offer['price']));
        $job->update([
            'gpu_instance_id' => $instanceId,
            'gpu_offer' => "{$offer['gpu']} @ \${$offer['price']}/h",
            'gpu_price_per_hour' => $offer['price'],
            'gpu_rented_at' => now(),
            'gpu_deadline_at' => now()->addMinutes((int) floor($hours * 60)),
            'gpu_state' => 'booting',
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    private function boot(AiprayTrainingJob $job): void
    {
        $info = $this->pod->instance($job->gpu_instance_id, config('services.aipray_gpu.port'));
        if ($info === null || in_array($info['status'], ['error', 'stopped'], true)) {
            $this->fail($job, 'The machine failed to start: ' . json_encode($info['errors'] ?? 'gone'));

            return;
        }
        if ($job->gpu_rented_at->lt(now()->subMinutes(config('services.aipray_gpu.boot_timeout_minutes')))) {
            $this->fail($job, 'The machine did not become ready in time.');

            return;
        }
        if ($info['status'] !== 'running' || ! $info['endpoint']) {
            return;
        }

        try {
            if (! Http::timeout(10)->get($info['endpoint'] . '/health')->ok()) {
                return; // Still installing.
            }
        } catch (ConnectionException $e) {
            return;
        }

        $response = $this->worker($job)->post($info['endpoint'] . '/remote/train', [
            'job_id' => $job->id,
            'dataset_url' => URL::temporarySignedRoute(
                'aipray.ml.dataset', $job->gpu_deadline_at->copy()->addHour(), ['job' => $job->id],
            ),
            'base_model' => $job->base_model,
            'epochs' => $job->epochs,
            'batch_size' => $job->batch_size,
            'learning_rate' => (float) $job->learning_rate,
            'optimizer' => $job->optimizer ?? 'adamw',
            'train_split' => ($job->train_split ?: 90) / 100,
            'augmentation' => $job->augmentation ?? [],
        ]);
        if (! $response->successful()) {
            $this->fail($job, "The trainer refused the job ({$response->status()}).");

            return;
        }

        $job->update([
            'gpu_endpoint' => $info['endpoint'],
            'gpu_ready_at' => now(),
            'gpu_last_seen_at' => now(),
            'gpu_state' => 'training',
        ]);
    }

    private function watch(AiprayTrainingJob $job): void
    {
        try {
            $response = $this->worker($job)->get($job->gpu_endpoint . '/remote/status');
        } catch (ConnectionException $e) {
            $response = null;
        }
        if (! $response?->ok()) {
            $this->failIfUnreachable($job);

            return;
        }

        $status = $response->json();
        $metrics = $status['metrics'] ?? [];
        $job->update(array_filter([
            'gpu_last_seen_at' => now(),
            'current_epoch' => $status['epoch'] ?? null,
            'training_loss' => $metrics['training_loss'] ?? null,
            'validation_loss' => $metrics['validation_loss'] ?? null,
            'wer' => $metrics['wer'] ?? null,
            'cer' => $metrics['cer'] ?? null,
            'accuracy' => $metrics['accuracy'] ?? null,
            'log' => $status['log'] ?? null,
        ], fn ($v) => $v !== null));

        match ($status['stage'] ?? null) {
            'failed' => $this->fail($job, 'Training failed: ' . ($status['error'] ?? 'unknown error')),
            'completed' => $this->collect(tap($job)->update(['gpu_state' => 'collecting'])),
            default => null,
        };
    }

    private function collect(AiprayTrainingJob $job): void
    {
        $disk = Storage::disk(AiprayAudioStorage::DISK);
        $target = "aipray_models/job-{$job->id}.zip";
        $disk->makeDirectory('aipray_models');

        try {
            // Streamed to disk in chunks: a Whisper model is around a gigabyte.
            $response = $this->worker($job)->timeout(1800)->withOptions(['stream' => true])
                ->get($job->gpu_endpoint . '/remote/artifact');
            if ($response->successful()) {
                $body = $response->toPsrResponse()->getBody();
                $out = fopen($disk->path($target), 'wb');
                while (! $body->eof()) {
                    fwrite($out, $body->read(1 << 20));
                }
                fclose($out);
            }
        } catch (ConnectionException $e) {
            $response = null;
        }
        if (! $response?->successful() || ! $disk->exists($target) || $disk->size($target) === 0) {
            $disk->delete($target);
            $this->failIfUnreachable($job);

            return;
        }

        AiprayAiModel::create([
            'name' => "Aipray {$job->base_model} (GPU job #{$job->id})",
            'version' => now()->format('Y.m.d.His'),
            'base_model' => $job->base_model,
            'training_job_id' => $job->id,
            'file_path' => $target,
            'file_size' => $disk->size($target),
            'accuracy' => $job->accuracy,
            'wer' => $job->wer,
            'cer' => $job->cer,
            'total_samples_trained' => $job->dataset_samples,
            'total_hours_trained' => $job->dataset_seconds ? round($job->dataset_seconds / 3600, 2) : null,
            'status' => 'active',
        ]);
        $job->update(['artifact_path' => $target]);
        $this->finish($job, 'done', 'completed', 'Model collected.');
    }

    private function failIfUnreachable(AiprayTrainingJob $job): void
    {
        $since = $job->gpu_last_seen_at ?? $job->gpu_ready_at ?? $job->gpu_rented_at;
        if ($since && $since->lt(now()->subMinutes(config('services.aipray_gpu.unreachable_minutes')))) {
            $this->fail($job, 'The trainer stopped answering.');
        }
    }

    private function fail(AiprayTrainingJob $job, string $reason): void
    {
        Log::warning("[Aipray GPU] job {$job->id} failed: {$reason}");
        $this->finish($job, 'failed', 'failed', $reason);
    }

    private function finish(AiprayTrainingJob $job, string $state, string $status, string $note): void
    {
        $job->update([
            'gpu_state' => $state,
            'status' => $status,
            'completed_at' => now(),
            'log' => trim(($job->log ?? '') . "\n[runner] {$note}"),
        ]);
        $this->terminate($job);
        $this->exporter->delete($job->dataset_path);
        $job->update(['dataset_path' => null]);
    }

    private function terminate(AiprayTrainingJob $job): void
    {
        if (! $job->gpu_instance_id || $job->gpu_terminated_at) {
            return;
        }
        try {
            $gone = $this->pod->terminate($job->gpu_instance_id);
        } catch (\Throwable $e) {
            report($e);
            $gone = false;
        }
        if (! $gone) {
            Log::error("[Aipray GPU] could not terminate instance {$job->gpu_instance_id} (job {$job->id}); retrying next minute.");

            return;
        }
        $job->update(['gpu_terminated_at' => now()]);
        $job->update(['actual_cost_usd' => $job->gpuCostSoFar()]);
    }

    private function sweepOrphans(): void
    {
        try {
            $instances = $this->pod->listInstances();
        } catch (\Throwable $e) {
            return;
        }
        $known = AiprayTrainingJob::where('runner', 'gpu')
            ->whereNotNull('gpu_instance_id')
            ->whereNull('gpu_terminated_at')
            ->pluck('gpu_instance_id')
            ->all();

        foreach ($instances as $instance) {
            if (str_starts_with((string) $instance['name'], self::NAME_PREFIX)
                && ! in_array($instance['id'], $known, true)
                && $instance['status'] !== 'stopped') {
                Log::warning("[Aipray GPU] terminating orphan instance {$instance['id']} ({$instance['name']})");
                $this->pod->terminate($instance['id']);
            }
        }
    }

    private function worker(AiprayTrainingJob $job): PendingRequest
    {
        return Http::withToken($job->gpu_token)->acceptJson()->timeout(20);
    }

    /**
     * Boot script for the machine. The template's copy (no job) carries no
     * token; the per-rental copy exports it too, because SimplePod's handling
     * of envVariables is undocumented (aixman does the same).
     */
    public function bootScript(?AiprayTrainingJob $job): string
    {
        $config = config('services.aipray_gpu');
        $tarball = "https://codeload.github.com/{$config['trainer_repo']}/tar.gz/{$config['trainer_ref']}";
        $token = $job ? 'export WORKER_TOKEN=' . escapeshellarg($job->gpu_token) : '';

        return implode("\n", array_filter([
            '#!/bin/bash',
            'set -uo pipefail',
            'export DEBIAN_FRONTEND=noninteractive PIP_BREAK_SYSTEM_PACKAGES=1 PIP_ROOT_USER_ACTION=ignore PIP_DISABLE_PIP_VERSION_CHECK=1',
            $token,
            'export AIPRAY_WORK_DIR=/workspace/aipray HF_HOME=/workspace/hf ML_DEVICE=cuda',
            'mkdir -p /workspace && cd /workspace',
            'apt-get update -qq && apt-get install -y -qq --no-install-recommends ffmpeg curl ca-certificates > /workspace/apt.log 2>&1',
            "curl -fsSL '{$tarball}' | tar xz",
            'cd /workspace/Aipray-*/training/ml-service',
            'python -m pip install -q -r requirements.txt > /workspace/pip.log 2>&1',
            "exec python -m uvicorn app.main:app --host 0.0.0.0 --port {$config['port']}",
        ]));
    }
}
