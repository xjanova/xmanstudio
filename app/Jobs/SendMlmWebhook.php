<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Tell mlm.xman4289.com that one of its rentals changed (App\Services\Rentals\MlmWebhookNotifier).
 *
 * The body is fixed when the event happens and the delivery id rides with it, so every retry sends
 * the same bytes under the same X-Xman-Delivery — the receiver dedupes on that id. Only the
 * signature's timestamp is fresh per attempt:
 *
 *     X-Xman-Signature: t=<unix seconds>,v1=<hex hmac_sha256(t . "." . rawBody, MLM_WEBHOOK_SECRET)>
 *
 * Anything but a 2xx — or no answer — is retried with backoff, 8 attempts over about 10.5 hours;
 * a job that gives up lands in failed_jobs, which the alerts watchdog reports.
 */
class SendMlmWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 8;

    /** @var array<int,int> seconds before attempts 2…8: 10 s, 1 min, 5 min, 15 min, 1 h, 3 h, 6 h */
    public array $backoff = [10, 60, 300, 900, 3600, 10800, 21600];

    public int $timeout = 30;

    public function __construct(
        public string $deliveryId,
        public string $event,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $url = (string) config('services.mlm.webhook_url');
        $secret = (string) config('services.mlm.webhook_secret');
        $context = ['delivery' => $this->deliveryId, 'event' => $this->event, 'attempt' => $this->attempts()];

        if ($url === '' || $secret === '') {
            // Unset after the event was queued. Retrying cannot fix configuration.
            Log::warning('[MLM webhook] MLM_WEBHOOK_URL / MLM_WEBHOOK_SECRET not set — not sent', $context);

            return;
        }

        $body = self::encode($this->payload);

        try {
            $response = Http::timeout(max(1, (int) config('services.mlm.webhook_timeout', 10)))
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'XMAN-Studio-Webhook/1.0',
                    'X-Xman-Event' => $this->event,
                    'X-Xman-Delivery' => $this->deliveryId,
                    'X-Xman-Signature' => self::signature($body, $secret, time()),
                ])
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (ConnectionException $e) {
            Log::warning('[MLM webhook] no answer — will retry', $context + ['error' => $e->getMessage()]);

            throw $e;
        }

        if ($response->successful()) {
            Log::info('[MLM webhook] delivered', $context + ['status' => $response->status()]);

            return;
        }

        Log::warning('[MLM webhook] refused — will retry', $context + [
            'status' => $response->status(),
            'body' => mb_substr((string) $response->body(), 0, 300),
        ]);

        throw new RuntimeException("MLM webhook {$this->event} ({$this->deliveryId}) answered HTTP {$response->status()}");
    }

    public function failed(?Throwable $e): void
    {
        Log::error('[MLM webhook] gave up after ' . $this->tries . ' attempts', [
            'delivery' => $this->deliveryId,
            'event' => $this->event,
            'rental_id' => $this->payload['rental']['id'] ?? null,
            'error' => $e?->getMessage(),
        ]);
    }

    /** "t=<unix>,v1=<hex>" over "<unix>.<raw body>". */
    public static function signature(string $body, string $secret, int $timestamp): string
    {
        return 't=' . $timestamp . ',v1=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    public static function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
