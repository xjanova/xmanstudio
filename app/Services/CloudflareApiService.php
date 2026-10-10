<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare's API, called with a CUSTOMER's token on the customer's account.
 *
 * Every call answers null/false on failure and leaves the reason in
 * lastError(), already worded for the customer. Writes are sent once — a
 * retried POST is a second zone or a duplicate record. The token is never
 * logged and never put in a message.
 */
class CloudflareApiService
{
    public const BASE = 'https://api.cloudflare.com/client/v4';

    /**
     * What the customer's token must be allowed to do. Pre-filled into the
     * token form by tokenTemplateUrl(), so nobody has to pick them by hand.
     */
    public const PERMISSIONS = [
        ['key' => 'account_settings', 'type' => 'read'],
        ['key' => 'zone', 'type' => 'edit'],
        ['key' => 'dns', 'type' => 'edit'],
        ['key' => 'zone_settings', 'type' => 'edit'],
    ];

    private ?string $lastError = null;

    private bool $lastWasAuth = false;

    public function __construct(private string $token) {}

    /**
     * Cloudflare's "create token" page with our permissions already ticked.
     */
    public static function tokenTemplateUrl(): string
    {
        return 'https://dash.cloudflare.com/profile/api-tokens?' . http_build_query([
            'permissionGroupKeys' => json_encode(self::PERMISSIONS),
            'accountId' => '*',
            'zoneId' => 'all',
            'name' => 'XMAN Studio',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /** The last failure was the token itself: revoked, expired, or short of a permission. */
    public function lastWasAuth(): bool
    {
        return $this->lastWasAuth;
    }

    // ───────────────────────────────────────────── token & account

    public function verifyToken(): bool
    {
        $result = $this->call('get', '/user/tokens/verify');

        return is_array($result) && ($result['status'] ?? null) === 'active';
    }

    /**
     * @return array<int,array{id:string,name:string}>|null
     */
    public function accounts(): ?array
    {
        $result = $this->call('get', '/accounts', ['per_page' => 50]);

        if (! is_array($result)) {
            return null;
        }

        return collect($result)
            ->filter(fn ($a) => is_array($a) && ! empty($a['id']))
            ->map(fn ($a) => ['id' => (string) $a['id'], 'name' => (string) ($a['name'] ?? $a['id'])])
            ->values()
            ->all();
    }

    // ───────────────────────────────────────────── zones

    /**
     * The customer's zone for this exact name, or null if they have none.
     * A failed lookup is also null — check lastError() to tell them apart.
     *
     * @return array<string,mixed>|null
     */
    public function findZone(string $domain): ?array
    {
        $this->lastError = null;
        $result = $this->call('get', '/zones', ['name' => $domain, 'per_page' => 5]);

        if (! is_array($result)) {
            return null;
        }

        foreach ($result as $zone) {
            if (is_array($zone) && strtolower((string) ($zone['name'] ?? '')) === strtolower($domain)) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function createZone(string $domain, string $accountId): ?array
    {
        $result = $this->call('post', '/zones', [
            'name' => $domain,
            'account' => ['id' => $accountId],
            'type' => 'full',
        ]);

        return is_array($result) ? $result : null;
    }

    /** Ask Cloudflare to look for the new nameservers now rather than on its own schedule. */
    public function activationCheck(string $zoneId): bool
    {
        return $this->call('put', "/zones/{$zoneId}/activation_check") !== null;
    }

    // ───────────────────────────────────────────── dns records

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function dnsRecords(string $zoneId): ?array
    {
        $result = $this->call('get', "/zones/{$zoneId}/dns_records", ['per_page' => 1000]);

        return is_array($result) ? array_values(array_filter($result, 'is_array')) : null;
    }

    /**
     * @param  array<string,mixed>  $record
     */
    public function createDnsRecord(string $zoneId, array $record): bool
    {
        return $this->call('post', "/zones/{$zoneId}/dns_records", $record) !== null;
    }

    public function deleteDnsRecord(string $zoneId, string $recordId): bool
    {
        return $this->call('delete', "/zones/{$zoneId}/dns_records/{$recordId}") !== null;
    }

    // ───────────────────────────────────────────── zone settings

    public function setSetting(string $zoneId, string $setting, string $value): bool
    {
        return $this->call('patch', "/zones/{$zoneId}/settings/{$setting}", ['value' => $value]) !== null;
    }

    // ───────────────────────────────────────────── transport

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE)
            ->withToken($this->token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20);
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function call(string $method, string $path, array $data = []): mixed
    {
        $this->lastError = null;
        $this->lastWasAuth = false;

        try {
            $response = $method === 'get'
                ? $this->client()->get($path, $data)
                : $this->client()->{$method}($path, $data);
        } catch (ConnectionException) {
            $this->lastError = 'ติดต่อ Cloudflare ไม่ได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง';

            return null;
        }

        $body = $response->json();

        if ($response->successful() && is_array($body) && ($body['success'] ?? false) === true) {
            // Some writes answer with a null result; success is what counts.
            return $body['result'] ?? true;
        }

        $errors = is_array($body['errors'] ?? null) ? $body['errors'] : [];
        $codes = array_map(fn ($e) => (int) ($e['code'] ?? 0), array_filter($errors, 'is_array'));
        $message = (string) ($errors[0]['message'] ?? ('HTTP ' . $response->status()));

        Log::warning('[Cloudflare] request failed', [
            'method' => $method,
            'path' => preg_replace('/[a-f0-9]{32}/', ':id', $path),
            'status' => $response->status(),
            'codes' => $codes,
        ]);

        if (in_array($response->status(), [401, 403], true) || array_intersect($codes, [6003, 9106, 9109, 10000]) !== []) {
            $this->lastWasAuth = true;
            $this->lastError = 'token ของ Cloudflare ใช้ไม่ได้ หรือสิทธิ์ไม่พอ — สร้าง token ใหม่จากลิงก์ในหน้านี้แล้วเชื่อมต่ออีกครั้ง';

            return null;
        }

        $this->lastError = 'Cloudflare ตอบกลับว่า: ' . mb_substr($message, 0, 200);

        return null;
    }
}
