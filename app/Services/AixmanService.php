<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * AixmanService — bridges xmanstudio with AIXMAN (ai.xman4289.com)
 *
 * Strategy (graceful degradation):
 *   1. Read from local DB if `ai_*` tables exist (production: shared DB)
 *   2. Fall back to AIXMAN HTTP API (local dev / cross-region)
 *   3. Fall back to hardcoded defaults (offline / both fail)
 *
 * Webhook delivery uses Http::retry to be resilient to transient failures.
 *
 * @see CLAUDE.md "Cross-Project Relationship with AIXMAN"
 */
class AixmanService
{
    /**
     * Hardcoded defaults — used when DB + HTTP both unavailable.
     * Mirror AIXMAN's expected schema so callers don't have to handle nulls.
     *
     * @var array<int, array<string, mixed>>
     */
    private const DEFAULT_PACKAGES = [
        [
            'slug'           => 'starter',
            'name'           => 'ผู้เริ่มฝัน',
            'price_thb'      => 0,
            'note'           => 'ฟรีตลอดชีพ',
            'credits'        => 50,
            'bonus_credits'  => 0,
            'features'       => ['50 งาน/เดือน', 'ความละเอียด 1K', 'ชุมชนสาธารณะ', 'รุ่น loom-mini'],
            'is_popular'     => false,
            'hue'            => 160,
        ],
        [
            'slug'           => 'weaver',
            'name'           => 'นักทอ',
            'price_thb'      => 490,
            'note'           => '/ เดือน',
            'credits'        => 5000,
            'bonus_credits'  => 500,
            'features'       => ['ไม่จำกัดจำนวน', '8K resolution', 'ปราสาทส่วนตัว 500 ชิ้น', 'รุ่น loom-v4.2', 'Video สูงสุด 30 วินาที'],
            'is_popular'     => true,
            'hue'            => 220,
        ],
        [
            'slug'           => 'studio',
            'name'           => 'สตูดิโอ',
            'price_thb'      => 2490,
            'note'           => '/ เดือน',
            'credits'        => 30000,
            'bonus_credits'  => 5000,
            'features'       => ['ทุกอย่างใน นักทอ', 'API + webhooks', 'ทีมสูงสุด 10 คน', 'รุ่น loom-pro', 'Commercial license', 'Priority queue'],
            'is_popular'     => false,
            'hue'            => 280,
        ],
    ];

    /**
     * Get all credit packages (cached).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPackages(): array
    {
        return Cache::remember('aixman:packages', config('services.aixman.cache_ttl', 600), function () {
            return $this->fetchPackages();
        });
    }

    /**
     * Get a single package by slug. Returns null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function getPackage(string $slug): ?array
    {
        foreach ($this->getPackages() as $pkg) {
            if (($pkg['slug'] ?? null) === $slug) {
                return $pkg;
            }
        }

        return null;
    }

    /**
     * Get user credit balance + monthly cap.
     * Returns null if no AIXMAN credit record (e.g. local dev or never used AIXMAN).
     *
     * @return array{balance:int, monthly_cap:int, used_this_month:int, reset_in_days:int}|null
     */
    public function getUserCredits(int $userId): ?array
    {
        if (! $this->hasTable('ai_user_credits')) {
            return null;
        }

        try {
            $row = DB::table('ai_user_credits')->where('user_id', $userId)->first();
            if (! $row) {
                return null;
            }
            $resetAt = $row->reset_at ?? null;

            return [
                'balance'         => (int) ($row->balance ?? 0),
                'monthly_cap'     => (int) ($row->monthly_cap ?? 0),
                'used_this_month' => (int) ($row->used_this_month ?? 0),
                'reset_in_days'   => $resetAt ? max(0, (int) ceil((strtotime($resetAt) - time()) / 86400)) : 0,
            ];
        } catch (\Throwable $e) {
            Log::warning('AixmanService::getUserCredits failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get user generation stats (total works, this week, etc).
     *
     * @return array{total:int, this_week:int, this_month:int, by_mode:array<string,int>}|null
     */
    public function getUserGenerationStats(int $userId): ?array
    {
        if (! $this->hasTable('ai_generations')) {
            return null;
        }

        try {
            $base = DB::table('ai_generations')->where('user_id', $userId);
            $total = (clone $base)->count();
            $thisWeek = (clone $base)->where('created_at', '>=', now()->startOfWeek())->count();
            $thisMonth = (clone $base)->where('created_at', '>=', now()->startOfMonth())->count();

            $byMode = (clone $base)
                ->where('created_at', '>=', now()->subDays(30))
                ->select('mode', DB::raw('count(*) as c'))
                ->groupBy('mode')
                ->pluck('c', 'mode')
                ->toArray();

            return [
                'total'      => $total,
                'this_week'  => $thisWeek,
                'this_month' => $thisMonth,
                'by_mode'    => array_map('intval', $byMode),
            ];
        } catch (\Throwable $e) {
            Log::warning('AixmanService::getUserGenerationStats failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get latest public generations for community gallery.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPublicGenerations(int $limit = 24): array
    {
        if (! $this->hasTable('ai_generations')) {
            return [];
        }

        try {
            return DB::table('ai_generations')
                ->where('is_public', true)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(['id', 'user_id', 'title', 'mode', 'thumb_url', 'likes_count', 'created_at'])
                ->map(fn ($r) => (array) $r)
                ->toArray();
        } catch (\Throwable $e) {
            Log::warning('AixmanService::getPublicGenerations failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Notify AIXMAN that a user just purchased credits.
     * Called from payment-success handlers (Stripe webhook, ThaiPayment confirm, etc).
     *
     * Returns true on success, false on permanent failure (logged).
     */
    public function notifyCreditPurchase(int $userId, string $packageSlug, int $orderId, int $credits, int $bonusCredits = 0): bool
    {
        $url = config('services.aixman.webhook_url');
        $secret = config('services.aixman.webhook_secret');

        if (! $url || ! $secret) {
            Log::warning('AIXMAN webhook not configured — skipping notification', [
                'user_id' => $userId, 'package' => $packageSlug, 'order_id' => $orderId,
            ]);

            return false;
        }

        try {
            $resp = Http::withHeaders(['x-webhook-secret' => $secret])
                ->timeout((int) config('services.aixman.timeout', 10))
                ->retry(2, 500, throw: false)
                ->post($url, [
                    'userId'        => $userId,
                    'packageId'     => $packageSlug,
                    'orderId'       => (string) $orderId,
                    'credits'       => $credits,
                    'bonusCredits'  => $bonusCredits,
                ]);

            if ($resp->successful()) {
                Log::info('AIXMAN credit webhook delivered', [
                    'user_id' => $userId, 'package' => $packageSlug, 'order_id' => $orderId,
                ]);

                return true;
            }

            Log::error('AIXMAN credit webhook returned non-2xx', [
                'status' => $resp->status(), 'body' => $resp->body(),
                'user_id' => $userId, 'order_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            Log::error('AIXMAN credit webhook threw exception', [
                'error' => $e->getMessage(),
                'user_id' => $userId, 'order_id' => $orderId,
            ]);
        }

        return false;
    }

    // ─── private ────────────────────────────────────────────────────────

    /**
     * Tier 1: shared DB. Tier 2: HTTP API. Tier 3: defaults.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchPackages(): array
    {
        // Tier 1: shared DB
        if ($this->hasTable('ai_credit_packages')) {
            try {
                $rows = DB::table('ai_credit_packages')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
                if ($rows->isNotEmpty()) {
                    return $rows->map(fn ($r) => $this->normalisePackage((array) $r))->toArray();
                }
            } catch (\Throwable $e) {
                Log::warning('AixmanService DB fetch failed, trying HTTP', ['error' => $e->getMessage()]);
            }
        }

        // Tier 2: HTTP API
        try {
            $base = rtrim((string) config('services.aixman.api_base'), '/');
            if ($base) {
                $resp = Http::timeout((int) config('services.aixman.timeout', 10))
                    ->get($base.'/api/packages');
                if ($resp->successful() && is_array($data = $resp->json('packages') ?? $resp->json())) {
                    return array_map([$this, 'normalisePackage'], $data);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AixmanService HTTP fetch failed, falling back to defaults', ['error' => $e->getMessage()]);
        }

        // Tier 3: hardcoded defaults
        return self::DEFAULT_PACKAGES;
    }

    /**
     * Coerce DB / API row into a consistent shape.
     * Accepts both snake_case (local DB) and camelCase (AIXMAN HTTP API).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalisePackage(array $row): array
    {
        $features = $row['features'] ?? [];
        if (is_string($features)) {
            $decoded = json_decode($features, true);
            $features = is_array($decoded) ? $decoded : [];
        }

        $slug = (string) ($row['slug'] ?? $row['id'] ?? '');
        $isPopular = (bool) ($row['is_popular'] ?? $row['isPopular'] ?? $row['isFeatured'] ?? false);

        // Default colour-palette mapping by sort order or slug, so the design stays consistent
        $hueMap = ['trial'=>140, 'starter'=>160, 'weaver'=>200, 'creator'=>220, 'pro'=>260, 'studio'=>280, 'enterprise'=>300];

        return [
            'slug'          => $slug,
            'name'          => (string) ($row['name'] ?? $row['title'] ?? ''),
            'price_thb'     => (int) ($row['price_thb'] ?? $row['priceThb'] ?? $row['price'] ?? 0),
            'note'          => (string) ($row['note'] ?? ($isPopular ? 'แนะนำ' : 'ครั้งเดียว')),
            'credits'       => (int) ($row['credits'] ?? 0),
            'bonus_credits' => (int) ($row['bonus_credits'] ?? $row['bonusCredits'] ?? 0),
            'features'      => array_values(array_map('strval', (array) $features)),
            'is_popular'    => $isPopular,
            'hue'           => (int) ($row['hue'] ?? $hueMap[$slug] ?? 220),
        ];
    }

    /**
     * Schema::hasTable wrapped in try/catch — survives broken DB connections.
     */
    private function hasTable(string $name): bool
    {
        try {
            return Schema::hasTable($name);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
