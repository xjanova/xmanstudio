<?php

namespace App\Services;

use App\Models\LicenseKey;
use App\Models\LicenseRenewal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Wallet;
use App\Support\LicensePlans;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Renewing a license the customer already holds — the same key gets one more term, so nothing is
 * re-entered in the app.
 *
 * Two ways to pay, both from /my-account/licenses/{id}: the wallet in one click (here), or the
 * cart with any payment method (CartController, renew_license). Automatic renewal
 * (licenses:renew) is the wallet way, run for the customer before the key runs out.
 *
 * A wallet renewal is a real order: it shows in the order history, gets a receipt and the
 * payment-confirmed e-mail, and the key is extended by LicenseService like any paid renewal —
 * the license_renewals row on its order item is what stops it from extending twice.
 */
class LicenseRenewalService
{
    /** Auto-renew: the e-mail saying what will be charged goes out this many days before expiry. */
    public const NOTICE_DAYS = 7;

    /** Auto-renew: the wallet is charged once the key is this close to its expiry. */
    public const CHARGE_DAYS = 2;

    /** Auto-renew: a key that ran out is still tried this many days after, then left alone. */
    public const GRACE_DAYS = 7;

    /** Keys the customer did not set to renew: reminded at these days before expiry. */
    public const REMINDER_DAYS = [7, 1];

    /** Terms a key can be renewed by — the ones with an end. */
    private const TERM_TYPES = [
        LicenseKey::TYPE_DAILY,
        LicenseKey::TYPE_WEEKLY,
        LicenseKey::TYPE_MONTHLY,
        LicenseKey::TYPE_YEARLY,
    ];

    public function __construct(private LicenseService $licenses) {}

    /**
     * What renewing this key costs and what it gives, or null when it cannot be renewed online:
     * not this customer's, revoked, lifetime or trial, a product off sale or Coming Soon (unless
     * its renew_while_coming_soon switch is on), or a
     * term the price table (config/licenses.php) does not sell.
     *
     * One more term of the key's own type, priced like the cart: the plan price plus VAT.
     *
     * @return array{term:string, price:float, tax:float, total:float, new_expires_at:CarbonInterface}|null
     */
    public function offerFor(LicenseKey $license, ?int $userId): ?array
    {
        if (! $userId || (int) $license->user_id !== $userId) {
            return null;
        }

        if (! in_array($license->status, [LicenseKey::STATUS_ACTIVE, LicenseKey::STATUS_EXPIRED], true)
            || ! in_array($license->license_type, self::TERM_TYPES, true)) {
            return null;
        }

        $product = $license->product;

        // Off sale, or Coming Soon unless the admin lets current customers renew meanwhile
        if (! $product || ! $product->requires_license || ! $product->acceptsRenewals()) {
            return null;
        }

        $price = LicensePlans::price($product->slug, $license->license_type);

        if (! $price || $price <= 0) {
            return null;
        }

        // The same test the payment applies, so the page never offers a renewal that the
        // payment would turn into a new key
        if (! $this->licenses->renewalTargetFor($userId, $product, $license->id)?->is($license)) {
            return null;
        }

        $tax = round($price * (float) config('app.vat_rate', 0.07), 2);
        $from = $license->expires_at !== null && $license->expires_at->isFuture() ? $license->expires_at : now();

        return [
            'term' => $license->license_type,
            'price' => (float) $price,
            'tax' => $tax,
            'total' => round($price + $tax, 2),
            'new_expires_at' => match ($license->license_type) {
                LicenseKey::TYPE_DAILY => $from->copy()->addDay(),
                LicenseKey::TYPE_WEEKLY => $from->copy()->addDays(7),
                LicenseKey::TYPE_MONTHLY => $from->copy()->addDays(30),
                LicenseKey::TYPE_YEARLY => $from->copy()->addYear(),
            },
        ];
    }

    /**
     * The expiry the renew form was drawn for, sent back with it. A second click (a double tap,
     * a resubmitted form, a second tab) arrives with the old expiry and is refused instead of
     * paying for a second term.
     */
    public static function expiryStamp(LicenseKey $license): string
    {
        return (string) ($license->expires_at?->getTimestamp() ?? 0);
    }

    /**
     * Pay one term from the customer's wallet and extend the key.
     *
     * @param  ?string  $expectedExpiry  expiryStamp() as the customer saw it; null = do not check (auto-renew)
     *
     * @throws LicenseRenewalException
     */
    public function renewFromWallet(LicenseKey $license, User $user, bool $automatic = false, ?string $expectedExpiry = null): Order
    {
        $lock = Cache::lock('license-renew:' . $license->id, 30);

        if (! $lock->get()) {
            throw new LicenseRenewalException('กำลังต่ออายุคีย์นี้อยู่ กรุณารอสักครู่แล้วรีเฟรชหน้า', LicenseRenewalException::BUSY);
        }

        try {
            return DB::transaction(function () use ($license, $user, $automatic, $expectedExpiry) {
                $fresh = LicenseKey::whereKey($license->id)->lockForUpdate()->with('product')->first();

                if ($fresh && $expectedExpiry !== null && self::expiryStamp($fresh) !== $expectedExpiry) {
                    throw new LicenseRenewalException(
                        'คีย์นี้เพิ่งถูกต่ออายุไปแล้ว — ตรวจวันหมดอายุใหม่ก่อน ถ้าต้องการต่ออีกรอบให้กดอีกครั้ง',
                        LicenseRenewalException::STALE
                    );
                }

                $offer = $fresh ? $this->offerFor($fresh, $user->id) : null;

                if (! $offer) {
                    throw new LicenseRenewalException('คีย์นี้ต่ออายุออนไลน์ไม่ได้ กรุณาติดต่อทีมงาน', LicenseRenewalException::NOT_RENEWABLE);
                }

                Wallet::getOrCreateForUser($user->id);
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

                if (! $wallet->hasSufficientBalance($offer['total'])) {
                    throw new LicenseRenewalException(
                        'ยอดเงินใน Wallet ไม่พอ ต้องใช้ ฿' . number_format($offer['total'], 2)
                            . ' (คงเหลือ ฿' . number_format((float) $wallet->balance, 2) . ')',
                        LicenseRenewalException::SHORT
                    );
                }

                $product = $fresh->product;

                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => 'XM' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'subtotal' => $offer['price'],
                    'tax' => $offer['tax'],
                    'discount' => 0,
                    'total' => $offer['total'],
                    'payment_method' => 'wallet',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'status' => 'processing',
                    'notes' => $automatic ? 'ต่ออายุอัตโนมัติจาก Wallet' : 'ต่ออายุจากหน้าไลเซนส์',
                    'metadata' => [
                        'source' => 'license_renewal',
                        'license_key_id' => $fresh->id,
                        'automatic' => $automatic,
                    ],
                ]);

                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'price' => $offer['price'],
                    'subtotal' => $offer['price'],
                    'custom_requirements' => json_encode([
                        'license_type' => $offer['term'],
                        'renew_license_id' => $fresh->id,
                    ]),
                ]);

                $transaction = $wallet->pay(
                    $offer['total'],
                    ($automatic ? 'ต่ออายุอัตโนมัติ ' : 'ต่ออายุ ') . $product->name . " #{$order->order_number}",
                    Order::class,
                    $order->id,
                    ['license_key_id' => $fresh->id]
                );

                if (! $transaction) {
                    throw new LicenseRenewalException('ตัดเงินจาก Wallet ไม่สำเร็จ กรุณาลองใหม่', LicenseRenewalException::SHORT);
                }

                $order->update(['wallet_transaction_id' => $transaction->id]);

                // Extends the key, completes the order, sends the receipt e-mail
                $this->licenses->generateLicensesForOrder($order);

                // Money is taken only for a key that moved — anything else undoes the payment too
                if (! LicenseRenewal::where('order_item_id', $item->id)->where('license_key_id', $fresh->id)->exists()) {
                    throw new LicenseRenewalException('ต่ออายุไม่สำเร็จ ยังไม่มีการตัดเงิน กรุณาติดต่อทีมงาน', LicenseRenewalException::NOT_RENEWABLE);
                }

                return $order->fresh();
            });
        } finally {
            $lock->release();
        }
    }

    /** Whether a renewal e-mail of this kind already went out for the key's current expiry. */
    public static function mailSent(LicenseKey $license, string $kind): bool
    {
        return in_array(self::mailTag($license, $kind), (array) ($license->renewal_mails ?? []), true);
    }

    /** Record a renewal e-mail, dropping the tags of earlier periods. */
    public static function markMailSent(LicenseKey $license, string ...$kinds): void
    {
        $period = ':' . self::periodOf($license);
        $kept = array_filter((array) ($license->renewal_mails ?? []), fn ($tag) => str_ends_with((string) $tag, $period));

        foreach ($kinds as $kind) {
            $kept[] = self::mailTag($license, $kind);
        }

        $license->forceFill(['renewal_mails' => array_values(array_unique($kept))])->save();
    }

    private static function mailTag(LicenseKey $license, string $kind): string
    {
        return $kind . ':' . self::periodOf($license);
    }

    private static function periodOf(LicenseKey $license): string
    {
        return $license->expires_at?->format('Y-m-d') ?? 'none';
    }
}
