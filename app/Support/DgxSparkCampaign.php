<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Services\ThaiPaymentService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * The DGX Spark bundle campaign (/dgx-spark): its price, how many of the 20 sets are left, and
 * which orders are part of it. Configuration lives in config/campaigns.php 'dgx_spark'; the three
 * numbers the owner changes when JIB moves its price are Settings edited at /admin/campaigns/dgx-spark.
 *
 * "Remaining" is counted from real orders, never set by hand:
 *   sold      — paid orders
 *   reserved  — orders waiting for the money: a slip under review, or a fresh reservation still
 *               inside its hold (config hold_hours) — the customer is transferring
 *   remaining — cap − sold − reserved
 * A reservation that runs out of time, an order rejected or cancelled (a refund) gives its set back.
 */
class DgxSparkCampaign
{
    /** orders.metadata['campaign'] on every order this campaign creates */
    public const KEY = 'dgx-spark';

    public const ORDER_PREFIX = 'DGX';

    public const SETTING_ENABLED = 'dgx_campaign_enabled';

    public const SETTING_REFERENCE_PRICE = 'dgx_campaign_reference_price';

    public const SETTING_REFERENCE_DATE = 'dgx_campaign_reference_checked_at';

    public const SETTING_MARKUP = 'dgx_campaign_markup';

    /** A payment in one of these states holds no set. */
    public const RELEASED_PAYMENT_STATUSES = ['rejected', 'expired', 'failed', 'refunded', 'cancelled'];

    /** The hardware side of the order, kept in orders.metadata['fulfillment']['status']. */
    public const FULFILLMENT = [
        'awaiting_payment' => 'รอชำระเงิน',
        'paid' => 'ชำระแล้ว — กำลังสั่งเครื่องจากผู้จัดจำหน่าย',
        'ordered' => 'สั่งเครื่องกับผู้จัดจำหน่ายแล้ว',
        'shipped' => 'จัดส่งแล้ว',
        'delivered' => 'ส่งมอบเรียบร้อย',
        'cancelled' => 'ยกเลิก',
    ];

    /** The same steps, short enough for the progress bar on the order page. */
    public const FULFILLMENT_SHORT = [
        'awaiting_payment' => 'รอชำระเงิน',
        'paid' => 'ชำระแล้ว',
        'ordered' => 'สั่งเครื่องแล้ว',
        'shipped' => 'จัดส่งแล้ว',
        'delivered' => 'ได้รับเครื่อง',
    ];

    private const TZ = 'Asia/Bangkok';

    private const MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    public static function config(string $key, mixed $default = null): mixed
    {
        return config('campaigns.dgx_spark.' . $key, $default);
    }

    // ── price ─────────────────────────────────────────────────────────

    /** The owner's on/off switch at /admin/campaigns/dgx-spark. On until switched off. */
    public static function enabled(): bool
    {
        return (bool) Setting::getValue(self::SETTING_ENABLED, true);
    }

    /** The JIB price the bundle is priced from (THB, VAT included). */
    public static function referencePrice(): int
    {
        return self::positiveInt(Setting::getValue(self::SETTING_REFERENCE_PRICE), (int) self::config('reference_price'));
    }

    public static function markup(): int
    {
        $stored = Setting::getValue(self::SETTING_MARKUP);

        return is_numeric($stored) && (int) $stored >= 0 ? (int) $stored : (int) self::config('markup');
    }

    /** The bundle price: the JIB price + the markup. Nothing else sets it. */
    public static function price(): int
    {
        return self::referencePrice() + self::markup();
    }

    public static function referenceCheckedAt(): CarbonImmutable
    {
        $stored = (string) Setting::getValue(self::SETTING_REFERENCE_DATE, '');

        try {
            return CarbonImmutable::parse($stored !== '' ? $stored : (string) self::config('reference_checked_at'), self::TZ);
        } catch (\Throwable) {
            return CarbonImmutable::parse((string) self::config('reference_checked_at'), self::TZ);
        }
    }

    public static function priceIncludesVat(): bool
    {
        return (bool) self::config('price_includes_vat', true);
    }

    public static function vatRate(): float
    {
        return (float) config('app.vat_rate', 0.07);
    }

    /**
     * Split a price into the order's subtotal / VAT / total the way every order on this site is
     * stored (subtotal + tax = total), so receipts and the order page add up.
     *
     * @return array{subtotal: float, tax: float, total: float}
     */
    public static function totals(int|float $price): array
    {
        $rate = self::vatRate();

        if (self::priceIncludesVat()) {
            $total = round((float) $price, 2);
            $subtotal = round($total / (1 + $rate), 2);

            return ['subtotal' => $subtotal, 'tax' => round($total - $subtotal, 2), 'total' => $total];
        }

        $subtotal = round((float) $price, 2);
        $tax = round($subtotal * $rate, 2);

        return ['subtotal' => $subtotal, 'tax' => $tax, 'total' => round($subtotal + $tax, 2)];
    }

    // ── the 20 sets ───────────────────────────────────────────────────

    public static function cap(): int
    {
        return max(0, (int) self::config('cap', 20));
    }

    public static function holdHours(): int
    {
        return max(1, (int) self::config('hold_hours', 48));
    }

    /**
     * Orders that hold a set right now (paid, being verified, or reserved inside the hold).
     * $exceptOrderId leaves one order out — the late payer asking whether a set is still free.
     */
    public static function holdingOrders(?int $exceptOrderId = null): Builder
    {
        $product = self::product();

        return Order::query()
            ->whereIn('id', OrderItem::select('order_id')->where('product_id', $product?->id ?? 0))
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('payment_status', self::RELEASED_PAYMENT_STATUSES)
            ->where(function (Builder $q) {
                $q->where('payment_status', '!=', 'pending')
                    ->orWhere('created_at', '>=', now()->subHours(self::holdHours()));
            })
            ->when($exceptOrderId, fn (Builder $q) => $q->whereKeyNot($exceptOrderId));
    }

    /**
     * @return array{cap: int, sold: int, reserved: int, remaining: int}
     */
    public static function availability(?int $exceptOrderId = null): array
    {
        $cap = self::cap();
        $product = self::product();

        if (! $product) {
            return ['cap' => $cap, 'sold' => 0, 'reserved' => 0, 'remaining' => $cap];
        }

        $rows = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereIn('order_id', self::holdingOrders($exceptOrderId)->select('id'))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->selectRaw("SUM(CASE WHEN orders.payment_status = 'paid' THEN order_items.quantity ELSE 0 END) AS sold")
            ->selectRaw("SUM(CASE WHEN orders.payment_status = 'paid' THEN 0 ELSE order_items.quantity END) AS reserved")
            ->first();

        $sold = (int) ($rows->sold ?? 0);
        $reserved = (int) ($rows->reserved ?? 0);

        return [
            'cap' => $cap,
            'sold' => $sold,
            'reserved' => $reserved,
            'remaining' => max(0, $cap - $sold - $reserved),
        ];
    }

    /**
     * Cancel reservations whose hold ran out without a slip, so their sets go back on offer and
     * the admin lists stop showing them as waiting. Saved one by one so the order observer
     * refreshes the owner's Telegram card for each.
     */
    public static function expireStaleHolds(): int
    {
        $product = self::product();
        if (! $product) {
            return 0;
        }

        $stale = Order::query()
            ->whereIn('id', OrderItem::select('order_id')->where('product_id', $product->id))
            ->where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '<', now()->subHours(self::holdHours()))
            ->get();

        foreach ($stale as $order) {
            self::expire($order);
        }

        return $stale->count();
    }

    public static function expire(Order $order): void
    {
        $metadata = self::metadataOf($order);
        $metadata['fulfillment'] = ['status' => 'cancelled', 'updated_at' => now()->toIso8601String()]
            + ($metadata['fulfillment'] ?? []);

        $order->update([
            'status' => 'cancelled',
            'payment_status' => 'expired',
            'metadata' => $metadata,
            'notes' => ($order->notes ? $order->notes . "\n" : '')
                . 'หมดเวลาจอง ' . self::holdHours() . ' ชม. — ระบบยกเลิกอัตโนมัติ ' . now(self::TZ)->format('d/m/Y H:i'),
        ]);
    }

    // ── what the campaign needs to take an order ──────────────────────

    /** The product row the order line points at — by slug, or its SKU if the slug was edited. */
    public static function product(): ?Product
    {
        return Product::query()
            ->where('slug', self::config('product_slug'))
            ->orWhere('sku', self::config('product_sku'))
            ->orderByRaw('CASE WHEN slug = ? THEN 0 ELSE 1 END', [self::config('product_slug')])
            ->first();
    }

    public static function isCampaignProduct(Product $product): bool
    {
        return $product->slug === self::config('product_slug')
            || ($product->sku !== null && $product->sku === self::config('product_sku'));
    }

    /** @return array<string, string> slug => license type, from config */
    public static function licensePlan(): array
    {
        return (array) self::config('licenses', []);
    }

    /** @return Collection<string, Product> the licensed products the bundle includes, keyed by slug */
    public static function licenseProducts(): Collection
    {
        return Product::query()
            ->whereIn('slug', array_keys(self::licensePlan()))
            ->where('requires_license', true)
            ->get()
            ->keyBy('slug');
    }

    /**
     * The site's payment methods this bundle accepts and the admin has switched on.
     *
     * @return list<array<string, mixed>>
     */
    public static function paymentMethods(): array
    {
        $allowed = (array) self::config('payment_methods', []);

        return array_values(array_filter(
            app(ThaiPaymentService::class)->getSupportedMethods(),
            fn (array $method) => in_array($method['id'], $allowed, true) && ! empty($method['is_active'])
        ));
    }

    /**
     * Whatever stops the campaign from taking an order right now, worded for the owner.
     * Empty = ready.
     *
     * @return list<string>
     */
    public static function problems(): array
    {
        $problems = [];

        if (! self::product()) {
            $problems[] = 'ไม่พบสินค้า "' . self::config('product_slug') . '" (migration ลงทะเบียนสินค้ายังไม่ได้รัน?)';
        }

        $found = self::licenseProducts();
        foreach (array_keys(self::licensePlan()) as $slug) {
            if (! $found->has($slug)) {
                $problems[] = "ไม่พบสินค้า license \"{$slug}\" — ออก license ให้ลูกค้าไม่ได้";
            }
        }

        if (self::paymentMethods() === []) {
            $problems[] = 'ไม่มีช่องทางโอนเงิน/พร้อมเพย์ที่เปิดอยู่ (ตั้งค่าที่หน้า ตั้งค่าการชำระเงิน)';
        }

        return $problems;
    }

    /** Why a customer cannot order right now, in Thai, or null when they can. */
    public static function closedReason(?array $availability = null): ?string
    {
        if (! self::enabled()) {
            return 'แคมเปญนี้ปิดรับคำสั่งซื้อแล้ว';
        }

        if (self::problems() !== []) {
            return 'ขณะนี้ยังไม่เปิดรับคำสั่งซื้อ กรุณาติดต่อเราเพื่อสอบถาม';
        }

        $availability ??= self::availability();
        if ($availability['remaining'] <= 0) {
            return $availability['reserved'] > 0
                ? 'ชุดแคมเปญถูกจองครบแล้ว — หากมีการจองที่ไม่ชำระภายในเวลาที่กำหนด ชุดนั้นจะกลับมาเปิดให้สั่งอีกครั้ง'
                : 'ชุดแคมเปญจำหน่ายครบ ' . self::cap() . ' ชุดแล้ว ขอบคุณที่ให้ความสนใจ';
        }

        return null;
    }

    // ── one order ─────────────────────────────────────────────────────

    public static function isOrder(?Order $order): bool
    {
        if (! $order) {
            return false;
        }

        $metadata = $order->metadata;

        return is_array($metadata) && ($metadata['campaign'] ?? null) === self::KEY;
    }

    public static function holdExpiresAt(Order $order): CarbonInterface
    {
        return $order->created_at->copy()->addHours(self::holdHours());
    }

    /** Still waiting for the money and past its hold. */
    public static function holdExpired(Order $order): bool
    {
        return $order->payment_status === 'pending'
            && $order->status !== 'cancelled'
            && self::holdExpiresAt($order)->isPast();
    }

    /** @return array<string, mixed> */
    public static function metadataOf(Order $order): array
    {
        $metadata = $order->metadata;

        return is_array($metadata) ? $metadata : [];
    }

    /** @return array{status: string, label: string, tracking: ?string, note: ?string, updated_at: ?string} */
    public static function fulfillment(Order $order): array
    {
        $stored = self::metadataOf($order)['fulfillment'] ?? [];

        $status = match (true) {
            $order->status === 'cancelled' || in_array($order->payment_status, self::RELEASED_PAYMENT_STATUSES, true) => 'cancelled',
            isset($stored['status']) && array_key_exists($stored['status'], self::FULFILLMENT)
                && ! in_array($stored['status'], ['awaiting_payment', 'cancelled'], true) => $stored['status'],
            $order->payment_status === 'paid' => 'paid',
            default => 'awaiting_payment',
        };

        return [
            'status' => $status,
            'label' => self::FULFILLMENT[$status],
            'tracking' => $stored['tracking'] ?? null,
            'note' => $stored['note'] ?? null,
            'updated_at' => $stored['updated_at'] ?? null,
        ];
    }

    // ── presentation ──────────────────────────────────────────────────

    /**
     * URL of a media file under the web root, with its mtime as a cache stamp (the site sits
     * behind Cloudflare), or null when the file is not there yet.
     */
    public static function mediaUrl(string $slot): ?string
    {
        $path = self::config('media.' . $slot);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $file = public_path($path);
        if (! is_file($file)) {
            return null;
        }

        return asset($path) . '?v=' . filemtime($file);
    }

    /** "9 ต.ค. 2569" */
    public static function thaiDate(CarbonInterface $date): string
    {
        $local = $date->copy()->setTimezone(self::TZ);

        return $local->day . ' ' . self::MONTHS[$local->month - 1] . ' ' . ($local->year + 543);
    }

    /** "9 ต.ค. 2569 14:05 น." */
    public static function thaiDateTime(CarbonInterface $date): string
    {
        return self::thaiDate($date) . ' ' . $date->copy()->setTimezone(self::TZ)->format('H:i') . ' น.';
    }

    public static function baht(int|float $amount): string
    {
        return '฿' . number_format($amount, fmod((float) $amount, 1.0) === 0.0 ? 0 : 2);
    }

    private static function positiveInt(mixed $value, int $default): int
    {
        if (is_numeric($value) && (int) $value > 0) {
            return (int) $value;
        }

        if ($value !== null && $value !== '') {
            Log::warning('DGX campaign: ignoring a non-positive price setting', ['value' => $value]);
        }

        return $default;
    }
}
