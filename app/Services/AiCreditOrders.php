<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Order;
use App\Models\User;
use App\Services\TpixPay\TpixCheckout;
use App\Services\TpixPay\TpixMath;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The one place an AI-credit order (an ai.xman4289.com credit package) is priced and written —
 * by the AI credit checkout (AiCreditCheckoutController::processCheckout) for every payment method,
 * and by aixman's signed TPIX API (Api\AixmanTpixController) for its own /tpix page.
 *
 * Paid in TPIX, a package is TpixPaySettings::aiCreditDiscountPercent() cheaper (owner, 2026-10-29):
 * subtotal = the package price, discount = price × percent / 100 (2 decimals, half-up, exact decimal
 * maths), total = price − discount. The TPIX quote is made for the total, and the affiliate
 * commission is on the total — what the customer actually pays.
 */
class AiCreditOrders
{
    /** The order reuse window of the aixman API: a pending TPIX order this young is paid instead of a new one. */
    public const REUSE_HOURS = 24;

    public function __construct(
        private AffiliateCommissionService $affiliates,
        private TpixPaySettings $settings,
    ) {}

    /**
     * What a package costs by this payment method. Money as 2-decimal strings.
     *
     * @param  array<string, mixed>  $package  as AixmanService::getPackage() gives it
     * @return array{price_thb:string, discount_percent:int, discount_thb:string, total_thb:string}
     */
    public function pricing(array $package, ?string $paymentMethod): array
    {
        $price = TpixMath::dec(TpixMath::thb((int) $package['price_thb']));
        $percent = $paymentMethod === TpixCheckout::METHOD ? $this->settings->aiCreditDiscountPercent() : 0;
        $discount = $price->multipliedBy($percent)->dividedBy(100, 2, RoundingMode::HalfUp);

        return [
            'price_thb' => (string) $price,
            'discount_percent' => $percent,
            'discount_thb' => (string) $discount,
            'total_thb' => (string) $price->minus($discount)->toScale(2),
        ];
    }

    /**
     * Write the pending order, its line and the affiliate commission (on the total), together.
     *
     * @param  array<string, mixed>  $package
     * @param  array{name:string, email:string, phone:?string}  $customer
     */
    public function create(User $user, array $package, string $paymentMethod, array $customer, ?string $ref, ?Affiliate $affiliate): Order
    {
        $pricing = $this->pricing($package, $paymentMethod);

        $metadata = [
            'source' => 'xdreamer',
            'package_slug' => $package['slug'],
            'package_name' => $package['name'],
            'credits' => (int) ($package['credits'] ?? 0),
            'bonus_credits' => (int) ($package['bonus_credits'] ?? 0),
            'ref' => $ref,
        ];
        if ($pricing['discount_percent'] > 0) {
            $metadata['tpix_discount_percent'] = $pricing['discount_percent'];
        }

        return DB::transaction(function () use ($user, $package, $paymentMethod, $customer, $affiliate, $pricing, $metadata) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => self::orderNumber(),
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'] ?? null,
                'subtotal' => $pricing['price_thb'],
                'discount' => $pricing['discount_thb'],
                'total' => $pricing['total_thb'],
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'notes' => "AI Credits · {$package['name']} · {$metadata['credits']} credits"
                    . ($metadata['bonus_credits'] ? " +{$metadata['bonus_credits']} bonus" : '')
                    . ($pricing['discount_percent'] > 0 ? " · ส่วนลดชำระด้วย TPIX {$pricing['discount_percent']}%" : ''),
                // JSON text in the array-cast column, as every AI-credit order has always been stored
                // (AiCreditDelivery::metadata() reads both shapes)
                'metadata' => json_encode($metadata),
            ]);

            $order->items()->create([
                'product_id' => null, // virtual product — no row in products table
                'product_name' => 'AI Credits · ' . $package['name'],
                'quantity' => 1,
                'price' => $pricing['price_thb'],
                'subtotal' => $pricing['price_thb'],
            ]);

            // Affiliate commission (standard pipeline) on what is actually paid
            if ($affiliate) {
                $order->update([
                    'affiliate_id' => $affiliate->id,
                    'referral_code' => $affiliate->referral_code,
                ]);
                $this->affiliates->recordCommission(
                    $affiliate, (float) $pricing['total_thb'], $order->id, $user->id,
                    'ai_credits', $order->id, "AI Credits {$package['name']}"
                );
            }

            return $order;
        });
    }

    /**
     * The aixman API's order reuse: the user's newest AI-credit order for this package, paid with
     * TPIX, still pending, made within REUSE_HOURS and still payable — and only while it is priced
     * as the package would be priced now (a price or discount changed since → a new order).
     *
     * @param  array<string, mixed>  $package
     */
    public function reusableTpixOrder(User $user, array $package): ?Order
    {
        $pricing = $this->pricing($package, TpixCheckout::METHOD);

        // The metadata is JSON text inside a JSON column, so it cannot be filtered in SQL
        $order = Order::where('user_id', $user->id)
            ->where('payment_method', TpixCheckout::METHOD)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(self::REUSE_HOURS))
            ->latest('id')
            ->limit(20)
            ->get()
            ->first(function (Order $order) use ($package) {
                $metadata = AiCreditDelivery::metadata($order);

                return ($metadata['source'] ?? null) === 'xdreamer'
                    && ($metadata['package_slug'] ?? null) === $package['slug'];
            });

        if (! $order || ! TpixPaymentService::orderIsPayable($order)) {
            return null;
        }

        $samePrice = TpixMath::thb($order->subtotal ?? 0) === $pricing['price_thb']
            && TpixMath::thb($order->discount ?? 0) === $pricing['discount_thb']
            && TpixMath::thb($order->total ?? 0) === $pricing['total_thb'];

        return $samePrice ? $order : null;
    }

    public static function orderNumber(): string
    {
        return 'AIC-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
    }
}
