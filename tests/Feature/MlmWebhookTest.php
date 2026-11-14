<?php

namespace Tests\Feature;

use App\Jobs\SendMlmWebhook;
use App\Models\RentalPackage;
use App\Models\User;
use App\Models\UserRental;
use App\Services\RentalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

/**
 * What xmanstudio tells mlm.xman4289.com when an MLM rental changes.
 *
 *   POST MLM_WEBHOOK_URL
 *   X-Xman-Event: rental.activated | rental.renewed | rental.expired | rental.cancelled | addon.activated
 *   X-Xman-Delivery: <uuid, the same on every retry>
 *   X-Xman-Signature: t=<unix>,v1=<hex hmac_sha256(t . "." . rawBody, MLM_WEBHOOK_SECRET)>
 */
class MlmWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://mlm.example.test/api/xman/webhook';

    private const SECRET = 'whsec-test-0123456789';

    protected function setUp(): void
    {
        parent::setUp();

        RentalPackage::where('product_key', 'mlm')->update(['is_active' => true]);
        config(['services.mlm.webhook_url' => self::URL, 'services.mlm.webhook_secret' => self::SECRET]);
        Http::preventStrayRequests();
    }

    private function service(): RentalService
    {
        return app(RentalService::class);
    }

    private function rent(User $user, RentalPackage $package, array $extras = []): UserRental
    {
        $result = $this->service()->createRental($user, $package, null, 'bank_transfer', $extras);
        $this->assertTrue($result['success'], $result['error'] ?? '');
        $this->service()->processPaymentCompletion($result['payment']);

        return $result['rental']->fresh();
    }

    private function mlmJob(string $event = 'rental.activated'): SendMlmWebhook
    {
        Queue::fake();
        $this->rent(User::factory()->create(['name' => 'สมชาย', 'email' => 'somchai@example.test']),
            RentalPackage::findForCheckout('mlm-core', 'monthly'),
            ['addons' => ['mlm-gateway' => 2], 'reservation' => 'resv_42']);

        $job = Queue::pushed(SendMlmWebhook::class, fn (SendMlmWebhook $job) => $job->event === $event)->first();
        $this->assertNotNull($job, "no {$event} queued");

        return $job;
    }

    /** @return array{t:int,v1:string} */
    private function parseSignature(string $header): array
    {
        $this->assertMatchesRegularExpression('/^t=\d+,v1=[0-9a-f]{64}$/', $header);
        parse_str(str_replace(',', '&', $header), $parts);

        return ['t' => (int) $parts['t'], 'v1' => $parts['v1']];
    }

    public function test_the_signature_is_hmac_sha256_of_timestamp_dot_raw_body(): void
    {
        $body = '{"id":"d1","event":"rental.activated"}';

        $this->assertSame(
            't=1767225600,v1=' . hash_hmac('sha256', '1767225600.' . $body, 'k'),
            SendMlmWebhook::signature($body, 'k', 1767225600)
        );
    }

    public function test_a_delivery_is_signed_over_the_exact_bytes_sent_with_the_spec_headers(): void
    {
        $job = $this->mlmJob();
        Http::fake([self::URL => Http::response(['ok' => true])]);

        $job->handle();

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($job) {
            $sig = $this->parseSignature($request->header('X-Xman-Signature')[0]);

            // What the receiver does: recompute over the raw body it got.
            $this->assertSame(hash_hmac('sha256', $sig['t'] . '.' . $request->body(), self::SECRET), $sig['v1']);
            $this->assertEqualsWithDelta(time(), $sig['t'], 5);

            return $request->url() === self::URL
                && $request->method() === 'POST'
                && str_starts_with($request->header('Content-Type')[0], 'application/json')
                && $request->header('X-Xman-Event')[0] === 'rental.activated'
                && $request->header('X-Xman-Delivery')[0] === $job->deliveryId
                && json_decode($request->body(), true)['id'] === $job->deliveryId;
        });
    }

    public function test_a_retry_keeps_the_delivery_id_and_body(): void
    {
        $job = $this->mlmJob();
        Http::fake([self::URL => Http::sequence()->push('busy', 503)->push(['ok' => true], 200)]);

        // The queue rebuilds the job from its serialized payload on every attempt.
        $first = unserialize(serialize($job));
        try {
            $first->handle();
            $this->fail('a 503 must be retried');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('503', $e->getMessage());
        }
        unserialize(serialize($job))->handle();

        $sent = Http::recorded();
        $this->assertCount(2, $sent);
        [[$a], [$b]] = [$sent[0], $sent[1]];
        $this->assertSame($job->deliveryId, $a->header('X-Xman-Delivery')[0]);
        $this->assertSame($a->header('X-Xman-Delivery'), $b->header('X-Xman-Delivery'));
        $this->assertSame($a->body(), $b->body());
    }

    public function test_the_job_retries_with_backoff(): void
    {
        $job = new SendMlmWebhook('d', 'rental.activated', []);

        $this->assertSame(8, $job->tries);
        $this->assertCount(7, $job->backoff);
        $this->assertSame($job->backoff, array_values(array_filter($job->backoff, fn ($s) => $s > 0)));
    }

    public function test_the_payload_has_the_agreed_shape(): void
    {
        $job = $this->mlmJob();
        $rental = UserRental::firstOrFail();
        $body = json_decode(SendMlmWebhook::encode($job->payload), true);

        $this->assertSame(['id', 'event', 'occurred_at', 'rental', 'user', 'tenant'], array_keys($body));
        $this->assertSame(['id', 'uuid', 'package_code', 'billing_cycle', 'starts_at', 'expires_at', 'addons'], array_keys($body['rental']));
        $this->assertSame($rental->id, $body['rental']['id']);
        $this->assertSame($rental->uuid, $body['rental']['uuid']);
        $this->assertSame('mlm-core', $body['rental']['package_code']);
        $this->assertSame('monthly', $body['rental']['billing_cycle']);
        $this->assertSame($rental->expires_at->toIso8601String(), $body['rental']['expires_at']);
        $this->assertSame([['code' => 'mlm-gateway', 'quantity' => 2, 'expires_at' => $rental->expires_at->toIso8601String()]], $body['rental']['addons']);
        $this->assertSame(['id' => $rental->user_id, 'email' => 'somchai@example.test', 'name' => 'สมชาย'], $body['user']);
        $this->assertSame(['reservation' => 'resv_42'], $body['tenant']);
    }

    public function test_only_mlm_rentals_are_announced(): void
    {
        Queue::fake();
        $legacy = RentalPackage::create([
            'name' => 'Pro Monthly', 'name_th' => 'โปรรายเดือน', 'price' => 990,
            'duration_type' => 'monthly', 'duration_value' => 1, 'is_active' => true,
        ])->fresh();
        $rental = $this->rent(User::factory()->create(), $legacy);
        $this->service()->cancelRental($rental);

        Queue::assertNotPushed(SendMlmWebhook::class);
    }

    public function test_every_lifecycle_event_of_an_mlm_rental_is_sent(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $rental = $this->rent($user, RentalPackage::findForCheckout('mlm-core', 'monthly'));

        $addon = $this->service()->createAddonPurchase($user, $rental, ['mlm-custom-domain' => 1], 'bank_transfer');
        $this->service()->processPaymentCompletion($addon['payment']);

        $renewal = $this->service()->createRenewal($user, $rental->fresh(), 'bank_transfer');
        $this->service()->processPaymentCompletion($renewal['payment']);

        $this->travel(3)->months();
        $this->artisan('rentals:expire')->assertSuccessful();

        $this->service()->cancelRental(
            $this->rent(User::factory()->create(), RentalPackage::findForCheckout('mlm-core', 'yearly'))
        );

        $events = Queue::pushed(SendMlmWebhook::class)->map(fn (SendMlmWebhook $job) => $job->event)->all();
        $this->assertSame(
            ['rental.activated', 'addon.activated', 'rental.renewed', 'rental.expired', 'rental.activated', 'rental.cancelled'],
            $events
        );
        $ids = Queue::pushed(SendMlmWebhook::class)->map(fn (SendMlmWebhook $job) => $job->deliveryId);
        $this->assertSame($ids->count(), $ids->unique()->count(), 'one delivery id per notification');
    }

    public function test_nothing_is_queued_while_the_webhook_is_not_configured(): void
    {
        Queue::fake();
        config(['services.mlm.webhook_secret' => null]);

        $this->rent(User::factory()->create(), RentalPackage::findForCheckout('mlm-core', 'monthly'));

        Queue::assertNotPushed(SendMlmWebhook::class);
    }

    public function test_a_rolled_back_payment_announces_nothing_and_a_committed_one_is_delivered(): void
    {
        // The real (sync) queue here: the job is released only when the transaction commits.
        Http::fake([self::URL => Http::response(['ok' => true])]);
        $package = RentalPackage::findForCheckout('mlm-core', 'monthly');

        $rolledBack = $this->service()->createRental(User::factory()->create(), $package, null, 'bank_transfer');
        DB::beginTransaction();
        $rolledBack['payment']->markAsCompleted();
        DB::rollBack();
        Http::assertNothingSent();

        $this->rent(User::factory()->create(), $package);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->header('X-Xman-Event')[0] === 'rental.activated');
    }
}
