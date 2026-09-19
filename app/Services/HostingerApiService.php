<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Low-level client for the upstream registrar's API.
 *
 * This class knows about HTTP and nothing about our business. It does not
 * price anything, does not touch wallets and does not decide what to show a
 * customer — see DomainRegistrarService for that. Keeping the split means the
 * day we buy from somewhere else, only this file and one service are rewritten.
 *
 * Two things it does take responsibility for:
 *
 * The rate limit is 90 requests per minute for the whole account, shared
 * across every machine using the token. Blowing through it returns 429 and,
 * repeated, gets the server's IP temporarily blocked — which would take the
 * whole site's domain features down, not just one request. So the limiter
 * here is deliberately stricter than the documented ceiling and refuses
 * locally rather than finding out from upstream.
 *
 * Nothing it logs may contain the token, and nothing it returns to a caller
 * is safe to show a customer verbatim: upstream error strings name the
 * registrar. Callers translate; they never pass the body through.
 */
class HostingerApiService
{
    protected string $baseUrl = 'https://developers.hostinger.com';

    /**
     * Ours, not theirs. Their ceiling is 90/min; we stop at 70 so that a burst
     * from a background job cannot starve a customer who is mid-checkout.
     */
    protected const RATE_LIMIT_PER_MINUTE = 70;

    protected const RATE_LIMIT_KEY = 'hostinger-api';

    protected string $apiToken;

    public function __construct()
    {
        $this->apiToken = (string) Setting::getValue('hostinger_api_token', '');
    }

    public function isConfigured(): bool
    {
        return $this->apiToken !== '';
    }

    // ---------------------------------------------------------------- domains

    /**
     * Which of these TLDs is the name still free on?
     *
     * @param  array<int,string>  $tlds  without leading dots
     * @return array<int,array<string,mixed>>|null null on failure
     */
    public function checkAvailability(string $name, array $tlds, bool $withAlternatives = false): ?array
    {
        return $this->post('/api/domains/v1/availability', [
            'domain' => $name,
            'tlds' => array_values($tlds),
            'with_alternatives' => $withAlternatives,
        ]);
    }

    /**
     * Names suggested from a plain-language description of the business.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function suggestFromDescription(string $description, array $tlds = ['com']): ?array
    {
        return $this->post('/api/domains/v1/availability/alternatives-from-description', [
            'description' => $description,
            'tlds' => array_values($tlds),
        ]);
    }

    /**
     * Names in the neighbourhood of one the customer already likes.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function suggestFromDomain(string $domain, array $tlds = ['com']): ?array
    {
        return $this->post('/api/domains/v1/availability/alternatives-from-domain', [
            'domain' => $domain,
            'tlds' => array_values($tlds),
        ]);
    }

    /**
     * Register a domain. The registrant is whoever owns $whoisId.
     *
     * A 202 here means the payment is still clearing and the domain is NOT
     * registered yet — the caller must poll, and must not resend.
     *
     * @param  array<string,mixed>  $additionalDetails  TLD-specific registrant data
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function purchaseDomain(
        string $domain,
        string $itemId,
        int $whoisId,
        array $additionalDetails = [],
        ?int $paymentMethodId = null,
    ): ?array {
        $payload = [
            'domain' => $domain,
            'item_id' => $itemId,
            'domain_contacts' => [
                'owner_id' => $whoisId,
                'admin_id' => $whoisId,
                'billing_id' => $whoisId,
                'tech_id' => $whoisId,
            ],
        ];

        if ($additionalDetails !== []) {
            $payload['additional_details'] = $additionalDetails;
        }

        if ($paymentMethodId) {
            $payload['payment_method_id'] = $paymentMethodId;
        }

        return $this->request('post', '/api/domains/v1/portfolio', $payload, withStatus: true);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getDomain(string $domain): ?array
    {
        return $this->get("/api/domains/v1/portfolio/{$domain}");
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function listDomains(): ?array
    {
        return $this->get('/api/domains/v1/portfolio');
    }

    /**
     * @param  array<int,string>  $nameservers
     */
    public function updateNameservers(string $domain, array $nameservers): bool
    {
        $payload = [];
        foreach (array_values($nameservers) as $i => $ns) {
            $payload['ns' . ($i + 1)] = $ns;
        }

        return $this->put("/api/domains/v1/portfolio/{$domain}/nameservers", $payload) !== null;
    }

    public function enablePrivacyProtection(string $domain): bool
    {
        return $this->put("/api/domains/v1/portfolio/{$domain}/privacy-protection", []) !== null;
    }

    public function disablePrivacyProtection(string $domain): bool
    {
        return $this->delete("/api/domains/v1/portfolio/{$domain}/privacy-protection") !== null;
    }

    /**
     * The code the customer needs to move the domain to another registrar.
     * We hand it over on request, always — see DomainContact's migration.
     */
    public function getAuthCode(string $domain): ?string
    {
        $result = $this->get("/api/domains/v1/portfolio/{$domain}/auth-code");

        return $result['auth_code'] ?? $result['code'] ?? null;
    }

    public function enableDomainLock(string $domain): bool
    {
        return $this->put("/api/domains/v1/portfolio/{$domain}/domain-lock", []) !== null;
    }

    public function disableDomainLock(string $domain): bool
    {
        return $this->delete("/api/domains/v1/portfolio/{$domain}/domain-lock") !== null;
    }

    // ----------------------------------------------------------------- whois

    /**
     * Mirror a registrant upstream and get back the handle used at purchase.
     *
     * @param  array<string,mixed>  $whoisDetails
     * @return int|null the remote profile id
     */
    public function createWhoisProfile(string $tld, string $country, array $whoisDetails, array $tldDetails = []): ?int
    {
        $payload = [
            'tld' => $tld,
            'country' => strtoupper($country),
            'entity_type' => 'individual',
            'whois_details' => $whoisDetails,
        ];

        if ($tldDetails !== []) {
            $payload['tld_details'] = $tldDetails;
        }

        $result = $this->post('/api/domains/v1/whois', $payload);

        $id = $result['id'] ?? null;

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function listWhoisProfiles(): ?array
    {
        return $this->get('/api/domains/v1/whois');
    }

    public function deleteWhoisProfile(int $whoisId): bool
    {
        return $this->delete("/api/domains/v1/whois/{$whoisId}") !== null;
    }

    // --------------------------------------------------------------- billing

    /**
     * The upstream price list. Prices come back in cents.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function getCatalog(?string $category = null): ?array
    {
        $query = $category ? ['category' => $category] : [];

        return $this->get('/api/billing/v1/catalog', $query);
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function getPaymentMethods(): ?array
    {
        return $this->get('/api/billing/v1/payment-methods');
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function getSubscriptions(): ?array
    {
        return $this->get('/api/billing/v1/subscriptions');
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function renewSubscription(string $subscriptionId, ?int $paymentMethodId = null): ?array
    {
        $payload = $paymentMethodId ? ['payment_method_id' => $paymentMethodId] : [];

        return $this->request('post', "/api/billing/v1/subscriptions/{$subscriptionId}/renew", $payload, withStatus: true);
    }

    public function setAutoRenewal(string $subscriptionId, bool $enabled): bool
    {
        return $enabled
            ? $this->request('patch', "/api/billing/v1/subscriptions/{$subscriptionId}/auto-renewal/enable", []) !== null
            : $this->delete("/api/billing/v1/subscriptions/{$subscriptionId}/auto-renewal/disable") !== null;
    }

    // ------------------------------------------------------------------- dns

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function getDnsRecords(string $domain): ?array
    {
        return $this->get("/api/dns/v1/zones/{$domain}");
    }

    /**
     * @param  array<int,array<string,mixed>>  $zone
     */
    public function updateDnsRecords(string $domain, array $zone, bool $overwrite = true): bool
    {
        return $this->put("/api/dns/v1/zones/{$domain}", [
            'overwrite' => $overwrite,
            'zone' => array_values($zone),
        ]) !== null;
    }

    /**
     * Dry-run a zone before writing it. Cheap insurance against handing a
     * customer a broken mail setup because one MX row had a typo.
     *
     * @param  array<int,array<string,mixed>>  $zone
     * @return array{ok:bool,errors:array<int,mixed>}
     */
    public function validateDnsRecords(string $domain, array $zone): array
    {
        $result = $this->request('post', "/api/dns/v1/zones/{$domain}/validate", [
            'zone' => array_values($zone),
        ], withStatus: true);

        if ($result === null) {
            return ['ok' => false, 'errors' => []];
        }

        return [
            'ok' => $result['status_code'] >= 200 && $result['status_code'] < 300,
            'errors' => $result['body']['errors'] ?? $result['body'] ?? [],
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $filters  [{name, type}]
     */
    public function deleteDnsRecords(string $domain, array $filters): bool
    {
        return $this->request('delete', "/api/dns/v1/zones/{$domain}", [
            'filters' => array_values($filters),
        ]) !== null;
    }

    public function resetDnsZone(string $domain): bool
    {
        return $this->request('post', "/api/dns/v1/zones/{$domain}/reset", []) !== null;
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function getDnsSnapshots(string $domain): ?array
    {
        return $this->get("/api/dns/v1/snapshots/{$domain}");
    }

    public function restoreDnsSnapshot(string $domain, string $snapshotId): bool
    {
        return $this->request('post', "/api/dns/v1/snapshots/{$domain}/{$snapshotId}/restore", []) !== null;
    }

    // --------------------------------------------------------------- plumbing

    /**
     * @param  array<string,mixed>  $query
     * @return array<mixed>|null
     */
    protected function get(string $path, array $query = []): ?array
    {
        return $this->request('get', $path, $query);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<mixed>|null
     */
    protected function post(string $path, array $payload): ?array
    {
        return $this->request('post', $path, $payload);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<mixed>|null
     */
    protected function put(string $path, array $payload): ?array
    {
        return $this->request('put', $path, $payload);
    }

    /**
     * @return array<mixed>|null
     */
    protected function delete(string $path): ?array
    {
        return $this->request('delete', $path, []);
    }

    /**
     * The single exit point to the network.
     *
     * Returns null for every failure mode — unconfigured, rate-limited,
     * non-2xx, thrown. Callers that need to tell 202 from 200 (the purchase
     * paths, where the difference is "registered" versus "paid for but not
     * registered") pass withStatus and get the code back with the body.
     *
     * @param  array<string,mixed>  $payload
     * @return array<mixed>|null
     */
    protected function request(string $method, string $path, array $payload = [], bool $withStatus = false): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('[HostingerApi] no API token configured', ['path' => $path]);

            return null;
        }

        if (RateLimiter::tooManyAttempts(self::RATE_LIMIT_KEY, self::RATE_LIMIT_PER_MINUTE)) {
            Log::warning('[HostingerApi] local rate limit hit, refusing to call', [
                'path' => $path,
                'available_in' => RateLimiter::availableIn(self::RATE_LIMIT_KEY),
            ]);

            return null;
        }

        RateLimiter::hit(self::RATE_LIMIT_KEY, 60);

        try {
            $response = $this->client()->send(strtoupper($method), $this->baseUrl . $path, [
                strtolower($method) === 'get' ? 'query' : 'json' => $payload,
            ]);
        } catch (\Throwable $e) {
            // The message can contain the URL but never the Authorization
            // header, so this is safe to log as-is.
            Log::error('[HostingerApi] transport error', [
                'path' => $path,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($response->status() === 429) {
            Log::warning('[HostingerApi] upstream 429', [
                'path' => $path,
                'retry_after' => $response->header('Retry-After'),
            ]);

            // Burn the local budget so the next caller backs off too, rather
            // than each of them discovering the block separately.
            RateLimiter::hit(self::RATE_LIMIT_KEY, 60, self::RATE_LIMIT_PER_MINUTE);

            return null;
        }

        if (! $response->successful()) {
            Log::error('[HostingerApi] request failed', [
                'path' => $path,
                'method' => $method,
                'status' => $response->status(),
                'body' => $this->loggableBody($path, $response->body()),
            ]);

            return $withStatus
                ? ['status_code' => $response->status(), 'body' => $this->decode($response)]
                : null;
        }

        return $withStatus
            ? ['status_code' => $response->status(), 'body' => $this->decode($response)]
            : $this->decode($response);
    }

    /**
     * How much of a failed response is safe to write to the log.
     *
     * A validation failure typically echoes the rejected payload back, and on
     * the WHOIS endpoints that payload is the customer's name, address and
     * phone number. Logs are copied into alerts, shipped off the box and kept
     * for months, so those paths get the status only — the operator can
     * reproduce the call if they need the detail, whereas a home address in
     * last week's log cannot be un-written.
     */
    protected function loggableBody(string $path, string $body): string
    {
        foreach (['/whois', '/portfolio'] as $sensitive) {
            if (str_contains($path, $sensitive)) {
                return '[redacted: response on a path that carries registrant data]';
            }
        }

        return mb_substr($body, 0, 1000);
    }

    /**
     * List endpoints wrap their rows in `data`; single resources do not.
     * Callers should not have to care which.
     *
     * @return array<mixed>
     */
    protected function decode(Response $response): array
    {
        $json = $response->json();

        if (! is_array($json)) {
            return [];
        }

        if (array_key_exists('data', $json) && is_array($json['data'])) {
            return $json['data'];
        }

        return $json;
    }

    protected function client(): PendingRequest
    {
        return Http::withToken($this->apiToken)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10)
            // Retries are for the network dropping, not for a refusal. A 4xx
            // is never retried: replaying a purchase that was rejected for
            // business reasons is how you buy the same domain twice.
            ->retry(2, 500, function ($exception, $request) {
                return $exception instanceof ConnectionException;
            }, throw: false);
    }
}
