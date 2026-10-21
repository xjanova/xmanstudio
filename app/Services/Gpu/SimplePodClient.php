<?php

namespace App\Services\Gpu;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;

/**
 * SimplePod GPU rental API, ported from aixman's src/lib/gpu/simplepod.ts,
 * which has rented real machines in production. The quirks below were learned
 * there the hard way:
 *
 * - List endpoints return plain JSON arrays; POST /instances and
 *   POST /instances/templates return an EMPTY body, so ids are recovered by
 *   listing again.
 * - `gpuCudaVer[gte]` does not compare versions (asking for >= 12.8 returned
 *   nothing); CUDA is filtered here on the host-reported value instead.
 * - `pricePerGpu[lte]` accepts whole dollars only, so it is a coarse
 *   pre-filter and the exact ceiling is applied here.
 * - The start script runs one line at a time; see asSingleLine().
 * - /instances/list fails intermittently with 502; one bad poll must not
 *   abandon a machine that is already billing.
 */
class SimplePodClient
{
    public const RENT_CONFIRM_SECONDS = 90;

    public const RENT_POLL_SECONDS = 3;

    private const STATUS_MAP = [
        'active' => 'running', 'running' => 'running', 'ready' => 'running',
        'created' => 'provisioning', 'creating' => 'provisioning', 'pending' => 'provisioning',
        'starting' => 'provisioning', 'provisioning' => 'provisioning', 'queued' => 'provisioning',
        'error' => 'error', 'failed' => 'error', 'unavailable' => 'error',
        'paused' => 'stopped', 'stopped' => 'stopped', 'deleted' => 'stopped',
        'removed' => 'stopped', 'expired' => 'stopped',
    ];

    public function __construct(
        private ?string $apiKey = null,
        private ?string $baseUrl = null,
    ) {
        $this->apiKey ??= config('services.simplepod.api_key');
        $this->baseUrl = rtrim($this->baseUrl ?? config('services.simplepod.base_url'), '/');
    }

    /**
     * Available machines, cheapest first.
     *
     * @param  array{min_vram_mb?: int, max_price?: float, gpu_models?: string[], min_cuda?: string, min_download_mbps?: int}  $filter
     * @return array<int, array{market: string, gpu: string, vram_mb: int, price: float, cuda: ?string}>
     */
    public function offers(array $filter): array
    {
        $query = ['rentalStatus' => 'active', 'order[pricePerGpu]' => 'asc'];
        if (! empty($filter['min_vram_mb'])) {
            $query['gpuMemorySize[gte]'] = (int) $filter['min_vram_mb'];
        }
        if (! empty($filter['min_download_mbps'])) {
            $query['downloadSpeedtest[gte]'] = (int) $filter['min_download_mbps'];
        }
        if (! empty($filter['max_price'])) {
            $query['pricePerGpu[lte]'] = max(1, (int) ceil($filter['max_price']));
        }

        $rows = $this->json($this->http()->get($this->baseUrl . '/instances/market/list', $query));
        $models = array_map('strtolower', $filter['gpu_models'] ?? []);

        $offers = [];
        foreach ($rows as $row) {
            if (empty($row['instanceMarket']) || ($row['isAvailableForDemand'] ?? true) === false) {
                continue;
            }
            $offer = [
                'market' => (string) $row['instanceMarket'],
                'gpu' => (string) ($row['gpuModel'] ?? 'unknown'),
                'vram_mb' => (int) ($row['gpuMemorySize'] ?? 0),
                'price' => (float) ($row['pricePerGpu'] ?? 0),
                'cuda' => is_string($row['gpuCudaVer'] ?? null) ? $row['gpuCudaVer'] : null,
            ];
            if ($offer['price'] <= 0
                || (! empty($filter['max_price']) && $offer['price'] > $filter['max_price'])
                || (! empty($filter['min_vram_mb']) && $offer['vram_mb'] < $filter['min_vram_mb'])
                || ($models && ! $this->modelMatches($offer['gpu'], $models))
                || (! empty($filter['min_cuda']) && $offer['cuda'] !== null
                    && version_compare($offer['cuda'], $filter['min_cuda'], '<'))) {
                continue;
            }
            $offers[] = $offer;
        }

        usort($offers, fn ($a, $b) => $a['price'] <=> $b['price']);

        return $offers;
    }

    /**
     * Find-or-create a private template. Its name carries a hash of what it
     * pins, so changing the image or script gets a new template rather than
     * silently reusing an old one. Returns the IRI POST /instances expects.
     */
    public function ensureTemplate(string $nameTag, string $image, string $tag, int $diskGb, array $ports, string $script): string
    {
        $name = $nameTag . '-' . substr(hash('sha256', json_encode([$image, $tag, $diskGb, $ports, $script])), 0, 10);

        $id = $this->findTemplate($name);
        if ($id === null) {
            $this->ensureOk($this->http()->post($this->baseUrl . '/instances/templates', [
                'name' => $name,
                'imageName' => $image,
                'defaultTag' => $tag,
                'categoryName' => 'aipray',
                'diskSize' => $diskGb,
                'exposePorts' => implode(',', $ports),
                'startScript' => self::asSingleLine($script),
                'notes' => 'Managed by xmanstudio (Aipray training).',
                'isPasswordProtected' => false,
                'isRunSshServerOn' => false,
                'isRunJupyterOn' => false,
            ]), 'create template');
            $id = $this->findTemplate($name)
                ?? throw new RuntimeException("SimplePod template {$name} was created but cannot be found");
        }

        return "/instances/templates/{$id}";
    }

    /**
     * Rents one machine and returns its instance id. POST /instances answers
     * with an empty body, so the new machine is found by diffing the instance
     * list; if it never shows up the caller must treat the rental as possibly
     * billing and alert a human.
     */
    public function rent(string $market, string $templateRef, string $startScript, array $env, string $name): string
    {
        $before = array_column($this->listInstances(), 'id');

        try {
            $response = $this->http()->post($this->baseUrl . '/instances', [
                'gpuCount' => 1,
                'instanceMarket' => $market,
                'instanceTemplate' => $templateRef,
                'startScript' => self::asSingleLine($startScript),
                'envVariables' => array_map(fn ($k, $v) => ['name' => $k, 'value' => (string) $v], array_keys($env), $env),
            ]);
            if ($response->clientError()) {
                // A 4xx is a refusal: nothing was rented.
                throw new RuntimeException('SimplePod refused the rental: ' . $this->errorText($response));
            }
        } catch (ConnectionException $e) {
            // A timeout may still have rented a machine; look for it below.
        }
        // Anything but a 4xx may have gone through.

        $deadline = now()->addSeconds(self::RENT_CONFIRM_SECONDS);
        do {
            try {
                foreach ($this->listInstances() as $row) {
                    if (! in_array($row['id'], $before, true)) {
                        $this->http()->put($this->baseUrl . "/instances/{$row['id']}", ['name' => $name]);

                        return $row['id'];
                    }
                }
            } catch (RuntimeException|ConnectionException $e) {
                // Intermittent 502 or timeout on the list endpoint; keep looking.
            }
            Sleep::for(self::RENT_POLL_SECONDS)->seconds();
        } while (now()->lt($deadline));

        throw new RuntimeException('SimplePod rental not confirmed within ' . self::RENT_CONFIRM_SECONDS . 's; a machine may be billing. Check the SimplePod console.');
    }

    /** @return array<int, array{id: string, name: ?string, status: string}> */
    public function listInstances(): array
    {
        $rows = $this->json($this->http()->get($this->baseUrl . '/instances/list', ['itemsPerPage' => 200]));

        return array_values(array_filter(array_map(fn ($row) => isset($row['id']) ? [
            'id' => (string) $row['id'],
            'name' => $row['name'] ?? null,
            'status' => self::mapStatus($row['status'] ?? null, $row['errors'] ?? []),
        ] : null, $rows)));
    }

    /**
     * @return array{status: string, raw_status: ?string, endpoint: ?string, errors: array}|null null once the machine is gone
     */
    public function instance(string $id, int $port): ?array
    {
        $response = $this->http()->get($this->baseUrl . "/instances/{$id}");
        if ($response->status() === 404) {
            return null;
        }
        $row = $this->json($response);

        return [
            'status' => self::mapStatus($row['status'] ?? null, $row['errors'] ?? []),
            'raw_status' => $row['status'] ?? null,
            'endpoint' => self::endpointFor($row['ports'] ?? null, $port),
            'errors' => (array) ($row['errors'] ?? []),
        ];
    }

    /** True once the machine is gone; 404 counts as already terminated. */
    public function terminate(string $id): bool
    {
        $response = $this->http()->delete($this->baseUrl . "/instances/{$id}");

        return $response->successful() || $response->status() === 404;
    }

    /**
     * SimplePod executes the start script line by line, so a real script is
     * shipped as one line that unpacks and execs it.
     */
    public static function asSingleLine(string $script): string
    {
        if (! str_contains(trim($script), "\n")) {
            return trim($script);
        }

        return 'mkdir -p /workspace && echo ' . base64_encode($script)
            . ' | base64 -d > /workspace/aipray-boot.sh && exec bash /workspace/aipray-boot.sh';
    }

    public static function mapStatus(?string $status, array $errors = []): string
    {
        return self::STATUS_MAP[strtolower((string) $status)] ?? ($errors ? 'error' : 'unknown');
    }

    /**
     * Public URL of `$port`. Entries read "checking"/"closed" until something
     * listens, so only http(s) ones count; the HTTPS proxy wins over direct.
     */
    public static function endpointFor(mixed $ports, int $port): ?string
    {
        if (! is_array($ports)) {
            return null;
        }
        foreach (['proxy', 'direct'] as $kind) {
            foreach ((array) ($ports[$kind] ?? []) as $entry) {
                if ((int) ($entry['srcPort'] ?? 0) === $port
                    && in_array($entry['protocol'] ?? null, ['http', 'https'], true)
                    && ! empty($entry['url'])) {
                    return rtrim($entry['url'], '/');
                }
            }
        }

        return null;
    }

    private function findTemplate(string $name): ?int
    {
        $rows = $this->json($this->http()->get($this->baseUrl . '/instances/templates/list', [
            'itemsPerPage' => 100,
            'search' => $name,
        ]));
        foreach ($rows as $row) {
            if (($row['name'] ?? null) === $name && is_int($row['id'] ?? null)) {
                return $row['id'];
            }
        }

        return null;
    }

    private function modelMatches(string $gpu, array $models): bool
    {
        foreach ($models as $model) {
            if (str_contains(strtolower($gpu), $model)) {
                return true;
            }
        }

        return false;
    }

    private function http(): PendingRequest
    {
        if (! $this->apiKey) {
            throw new RuntimeException('SIMPLEPOD_API_KEY is not set');
        }

        return Http::withHeaders(['X-AUTH-TOKEN' => $this->apiKey])->acceptJson()->timeout(30);
    }

    private function json(Response $response): array
    {
        $this->ensureOk($response, 'request');
        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    private function ensureOk(Response $response, string $what): void
    {
        if (! $response->successful()) {
            throw new RuntimeException("SimplePod {$what} failed ({$response->status()}): " . $this->errorText($response));
        }
    }

    private function errorText(Response $response): string
    {
        $body = $response->json();

        return (string) (is_array($body) ? ($body['detail'] ?? $body['title'] ?? $body['message'] ?? '') : '') ?: substr($response->body(), 0, 200);
    }
}
