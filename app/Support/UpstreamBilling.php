<?php

namespace App\Support;

use App\Models\Setting;
use App\Services\DomainRegistrarService;
use App\Services\HostingerApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Whether we can currently pay our supplier, and what to do when we cannot.
 *
 * Every domain and VPS we sell is bought upstream with the one card saved on
 * our Hostinger account, after the customer has paid us from their wallet.
 * When that card is declined, EVERY sale fails the same way until somebody
 * fixes it. Two things follow from that:
 *
 *   1. Stop taking money. Each customer who reaches the buy button in the
 *      meantime would be debited, refused upstream and refunded — a wallet
 *      history full of charge-and-reverse pairs, and a string of declined
 *      charges on our card, which is how a bank decides to block it. So after
 *      the first payment refusal, sales pause for a while and the page says
 *      "try again shortly" BEFORE any money moves.
 *
 *   2. Look before the customer does. The payment methods are readable over
 *      the API — expiry, suspension, which one is the default — so a card
 *      running out next week is something the admin can hear about next week
 *      minus a few days, not from the first refused order.
 *
 * The API has no balance or credit figure, so "insufficient funds" on the card
 * behind the default method can only ever be learned from a refused charge.
 * That is what the pause is for.
 */
class UpstreamBilling
{
    /** How long one refused charge pauses sales. Short: a transient decline should not close the shop for the day. */
    public const PAUSE_MINUTES = 30;

    /** A pause set by the health check lasts until the next check has a chance to lift it. */
    public const HEALTH_PAUSE_MINUTES = 400;

    /** Warn this many days before the default card expires. */
    public const EXPIRY_WARNING_DAYS = 30;

    protected const PAUSE_KEY = 'upstream-billing:paused';

    protected const HEALTH_KEY = 'upstream-billing:health';

    /**
     * Stop sales for a while because upstream refused our money.
     *
     * @param  'purchase'|'health'  $source  a health-check pause is lifted by the next healthy check;
     *                                       a purchase pause only expires, because the check cannot see a declined card
     */
    public static function pause(string $reason, string $source = 'purchase', ?int $minutes = null): void
    {
        $minutes ??= $source === 'health' ? self::HEALTH_PAUSE_MINUTES : self::PAUSE_MINUTES;

        try {
            Cache::put(self::PAUSE_KEY, [
                'reason' => mb_substr($reason, 0, 300),
                'source' => $source,
                'at' => now()->toIso8601String(),
                'until' => now()->addMinutes($minutes)->toIso8601String(),
            ], now()->addMinutes($minutes));
        } catch (\Throwable) {
            // A cache outage must not turn a refused order into a crash.
        }

        // Kept outside the cache so the admin page can still say "the last
        // refusal was on Tuesday" after the pause itself has expired.
        Setting::setValue('upstream_billing_last_failure', json_encode([
            'reason' => mb_substr($reason, 0, 300),
            'source' => $source,
            'at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE), 'string', 'billing');
    }

    /**
     * Upstream fell over (5xx) in the middle of an order, and what it said
     * mentions payment.
     *
     * Nothing is refunded on a 5xx — the order may have gone through before
     * upstream failed — but when the error reads like our card being refused,
     * the next customer should not be charged into the same wall while the
     * reconciliation job works out what happened to this one.
     *
     * @param  array<mixed>  $body
     */
    public static function pauseIfPaymentTrouble(int $status, array $body, string $what): void
    {
        if ($status >= 500 && DomainRegistrarService::looksLikePaymentProblem($status, $body)) {
            self::pause($what . ' failed upstream with HTTP ' . $status . ', outcome unknown: ' . mb_substr((string) json_encode($body), 0, 200));
        }
    }

    /**
     * The active pause, or null when sales are open.
     *
     * @return array{reason:string,source:string,at:string,until:string}|null
     */
    public static function paused(): ?array
    {
        try {
            $state = Cache::get(self::PAUSE_KEY);
        } catch (\Throwable) {
            return null;
        }

        return is_array($state) ? $state : null;
    }

    public static function isPaused(): bool
    {
        return self::paused() !== null;
    }

    /** Lift a pause — the admin fixed the card, or the health check found it fixed. */
    public static function resume(): void
    {
        try {
            Cache::forget(self::PAUSE_KEY);
        } catch (\Throwable) {
        }
    }

    /**
     * The customer-facing sentence while sales are paused. Says nothing about
     * whose card or which supplier — that is ours to fix, not theirs to know.
     */
    public static function customerMessage(): string
    {
        return 'ระบบสั่งซื้อปิดปรับปรุงชั่วคราว ยังไม่มีการตัดเงินใด ๆ — กรุณาลองใหม่อีกครั้งในภายหลัง ทีมงานได้รับแจ้งแล้ว';
    }

    /**
     * @return array{reason:string,source:string,at:string}|null
     */
    public static function lastFailure(): ?array
    {
        $raw = Setting::getValue('upstream_billing_last_failure', null);
        $data = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($data) ? $data : null;
    }

    /**
     * Read the saved payment methods and judge them.
     *
     * Healthy means a default that is neither expired nor suspended and does
     * not expire within the warning window. The verdict is cached so the admin
     * pages can show it without spending the 90-a-minute budget on every view.
     *
     * @return array{
     *     checked_at:string,
     *     ok:bool,
     *     level:'ok'|'warning'|'critical'|'unknown',
     *     problems:array<int,string>,
     *     methods:array<int,array{id:int|string,type:string,is_default:bool,is_expired:bool,is_suspended:bool,expires_at:?string}>
     * }
     */
    public static function check(HostingerApiService $api): array
    {
        $result = [
            'checked_at' => now()->toIso8601String(),
            'ok' => false,
            'level' => 'unknown',
            'problems' => [],
            'methods' => [],
        ];

        if (! $api->isConfigured()) {
            $result['problems'][] = 'ยังไม่ได้ใส่ API token';

            return self::remember($result);
        }

        $rows = $api->getPaymentMethods();

        if ($rows === null) {
            $result['problems'][] = 'อ่านวิธีชำระเงินจากผู้ให้บริการไม่ได้ (API ไม่ตอบ หรือ token หมดอายุ)';

            return self::remember($result);
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            // Card numbers and account names stay upstream: the admin needs
            // to know WHICH method is failing, not its digits.
            $result['methods'][] = [
                'id' => $row['id'] ?? '?',
                'type' => (string) ($row['payment_method'] ?? 'unknown'),
                'is_default' => (bool) ($row['is_default'] ?? false),
                'is_expired' => (bool) ($row['is_expired'] ?? false),
                'is_suspended' => (bool) ($row['is_suspended'] ?? false),
                'expires_at' => isset($row['expires_at']) ? (string) $row['expires_at'] : null,
            ];
        }

        $default = collect($result['methods'])->firstWhere('is_default', true);

        if ($result['methods'] === []) {
            $result['level'] = 'critical';
            $result['problems'][] = 'บัญชีผู้ให้บริการไม่มีวิธีชำระเงินเลย — ซื้อโดเมน/VPS ให้ลูกค้าไม่ได้';
        } elseif (! $default) {
            $result['level'] = 'critical';
            $result['problems'][] = 'ไม่มีวิธีชำระเงินที่ตั้งเป็นค่าเริ่มต้น — คำสั่งซื้อผ่าน API จะไม่มีบัตรให้ตัด';
        } elseif ($default['is_expired'] || $default['is_suspended']) {
            $result['level'] = 'critical';
            $result['problems'][] = sprintf(
                'วิธีชำระเงินหลัก (%s) %s — ทุกคำสั่งซื้อจะถูกปฏิเสธ',
                self::label($default['type']),
                $default['is_expired'] ? 'หมดอายุแล้ว' : 'ถูกระงับ',
            );
        } else {
            $expires = self::parseDate($default['expires_at']);

            if ($expires && $expires->lte(now()->addDays(self::EXPIRY_WARNING_DAYS))) {
                $result['level'] = 'warning';
                $result['problems'][] = sprintf(
                    'วิธีชำระเงินหลัก (%s) จะหมดอายุ %s — อีก %d วัน',
                    self::label($default['type']),
                    $expires->timezone('Asia/Bangkok')->format('d/m/Y'),
                    max(0, (int) now()->diffInDays($expires, false)),
                );
            } else {
                $result['level'] = 'ok';
                $result['ok'] = true;
            }
        }

        return self::remember($result);
    }

    /**
     * The last verdict, without calling upstream.
     *
     * @return array<string,mixed>|null
     */
    public static function lastCheck(): ?array
    {
        try {
            $data = Cache::get(self::HEALTH_KEY);
        } catch (\Throwable) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    public static function label(string $type): string
    {
        return match (strtolower($type)) {
            'card', 'credit_card', 'creditcard' => 'บัตรเครดิต/เดบิต',
            'googlepay', 'google_pay' => 'Google Pay',
            'applepay', 'apple_pay' => 'Apple Pay',
            'paypal' => 'PayPal',
            default => $type,
        };
    }

    protected static function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string,mixed>  $result
     * @return array<string,mixed>
     */
    protected static function remember(array $result): array
    {
        try {
            Cache::put(self::HEALTH_KEY, $result, now()->addDays(2));
        } catch (\Throwable) {
        }

        return $result;
    }
}
