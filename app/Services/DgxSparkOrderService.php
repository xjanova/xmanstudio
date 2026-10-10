<?php

namespace App\Services;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\DgxSparkCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Taking an order for the DGX Spark bundle, inside the 20-set cap.
 *
 * The order is an ordinary Order, so everything the site already does with orders applies to it:
 * the payment page (bank accounts / PromptPay QR) and slip upload, the admin order pages and the
 * Telegram card with approve buttons, the receipt PDF, the customer's order list. Approving the
 * payment (OrderPaymentService::approve) issues both lifetime licenses through LicenseService,
 * because the order carries one ฿0 line per licensed product with license_type "lifetime".
 *
 * What is different from the cart checkout, on purpose:
 *   — bank transfer / PromptPay only: a card fee on ฿265,000 is ~฿8,000 of a ฿20,000 margin
 *   — no coupon, no wallet, no affiliate commission: any of them would cut into that margin
 *   — the price includes VAT (config price_includes_vat) — it is quoted against JIB's VAT-inclusive price
 *   — no unique SMS amount: a ฿265,000 transfer is matched by the owner, not automatically
 *   — one set per order, one open reservation per customer, held for config hold_hours
 */
class DgxSparkOrderService
{
    /**
     * @param  array<string, mixed>  $input  validated by DgxSparkCampaignController::order
     * @return array{0: Order, 1: bool} the order, and whether this call created it (false: the
     *                                  customer's own open reservation — a second press of the button)
     *
     * @throws DgxSparkOrderException sold out / closed, with a reason the customer may read
     */
    public function place(User $user, array $input): array
    {
        $product = DgxSparkCampaign::product();

        if (! $product || ! DgxSparkCampaign::enabled()) {
            throw new DgxSparkOrderException(DgxSparkCampaign::closedReason() ?? 'ขณะนี้ยังไม่เปิดรับคำสั่งซื้อ');
        }

        return DB::transaction(function () use ($user, $input, $product) {
            // Every order of the campaign queues on the bundle's product row, so two customers
            // pressing the button for the last set cannot both get it (MySQL row lock).
            Product::whereKey($product->id)->lockForUpdate()->first();

            DgxSparkCampaign::expireStaleHolds();

            if ($open = $this->openOrderFor($user)) {
                return [$open, false];
            }

            $availability = DgxSparkCampaign::availability();
            if ($reason = DgxSparkCampaign::closedReason($availability)) {
                throw new DgxSparkOrderException($reason);
            }

            return [$this->create($user, $product, $input), true];
        });
    }

    /** The customer's reservation that is still waiting for their money, if any. */
    public function openOrderFor(User $user): ?Order
    {
        return DgxSparkCampaign::holdingOrders()
            ->where('user_id', $user->id)
            ->where('payment_status', '!=', 'paid')
            ->latest('id')
            ->first();
    }

    /**
     * A slip for a reservation whose hold has run out: taken if a set is still free (its set is
     * held again from the moment the slip arrives), refused — and the reservation cancelled —
     * if the sets went to other customers meanwhile. Returns the refusal, or null to go ahead.
     */
    public function refuseLateSlip(Order $order): ?string
    {
        if (! DgxSparkCampaign::isOrder($order) || ! DgxSparkCampaign::holdExpired($order)) {
            return null;
        }

        return DB::transaction(function () use ($order) {
            if ($product = DgxSparkCampaign::product()) {
                Product::whereKey($product->id)->lockForUpdate()->first();
            }

            // availability() already leaves this order out: its hold has run out.
            if (DgxSparkCampaign::availability()['remaining'] > 0) {
                return null;
            }

            DgxSparkCampaign::expire($order);

            return 'การจองนี้หมดเวลาแล้ว และชุดแคมเปญถูกจองครบในระหว่างนั้น '
                . 'หากคุณโอนเงินไปแล้ว กรุณาติดต่อเราพร้อมเลขคำสั่งซื้อ #' . $order->order_number
                . ' เราจะคืนเงินเต็มจำนวน';
        });
    }

    /**
     * The hardware side, set by the owner at /admin/campaigns/dgx-spark: ordered from the
     * distributor, shipped (with a tracking number), delivered.
     */
    public function updateFulfillment(Order $order, string $status, ?string $tracking, ?string $note, ?User $by = null): void
    {
        DB::transaction(function () use ($order, $status, $tracking, $note, $by) {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $metadata = DgxSparkCampaign::metadataOf($locked);
            $previous = $metadata['fulfillment'] ?? [];

            $history = $previous['history'] ?? [];
            $history[] = ['status' => $status, 'at' => now()->toIso8601String(), 'by' => $by?->id];

            $metadata['fulfillment'] = [
                'status' => $status,
                'tracking' => $tracking !== null && $tracking !== '' ? $tracking : ($previous['tracking'] ?? null),
                'note' => $note !== null && $note !== '' ? $note : ($previous['note'] ?? null),
                'updated_at' => now()->toIso8601String(),
                'history' => array_slice($history, -20),
            ];

            $locked->update(['metadata' => $metadata]);
            $order->setRawAttributes($locked->getAttributes(), true);
        });
    }

    /** Mail the customer the order and tell the owner (LINE, or its e-mail fallback). Never throws. */
    public function announce(Order $order): void
    {
        $order->loadMissing('items.product', 'user');

        try {
            Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            Log::error('DGX campaign: order confirmation e-mail failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        try {
            $availability = DgxSparkCampaign::availability();
            $deadline = DgxSparkCampaign::thaiDateTime(DgxSparkCampaign::holdExpiresAt($order));

            (new LineNotifyService)->send(
                "🖥️ จองชุด DGX Spark ใหม่!\n"
                . "━━━━━━━━━━━━━━━\n"
                . "🔢 เลขที่: {$order->order_number}\n"
                . "👤 ลูกค้า: {$order->customer_name}\n"
                . '📱 โทร: ' . ($order->customer_phone ?: '-') . "\n"
                . "📧 อีเมล: {$order->customer_email}\n"
                . '💳 ชำระผ่าน: ' . ($order->payment_method === 'promptpay' ? 'พร้อมเพย์' : 'โอนเงินธนาคาร') . "\n"
                . '💰 ยอด: ฿' . number_format((float) $order->total, 2) . "\n"
                . "⏳ ต้องโอนภายใน: {$deadline}\n"
                . "📦 เหลือ {$availability['remaining']}/{$availability['cap']} ชุด (ขายแล้ว {$availability['sold']} · รอชำระ {$availability['reserved']})\n"
                . "━━━━━━━━━━━━━━━\n"
                . 'สั่งเครื่องจากผู้จัดจำหน่ายหลังยืนยันยอดเงินเข้าแล้วเท่านั้น'
            );
        } catch (\Throwable $e) {
            Log::error('DGX campaign: owner notification failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /** @param array<string, mixed> $input */
    private function create(User $user, Product $product, array $input): Order
    {
        $price = DgxSparkCampaign::price();
        $totals = DgxSparkCampaign::totals($price);
        $licenseProducts = DgxSparkCampaign::licenseProducts();

        $address = trim((string) $input['shipping_address']);
        $province = trim((string) $input['shipping_province']);
        $postcode = trim((string) $input['shipping_postcode']);

        $company = null;
        if (! empty($input['company_name'])) {
            $company = [
                'name' => trim((string) $input['company_name']),
                'tax_id' => preg_replace('/\D/', '', (string) ($input['company_tax_id'] ?? '')),
                'branch' => trim((string) ($input['company_branch'] ?? '')) ?: 'สำนักงานใหญ่',
                'address' => trim((string) ($input['company_address'] ?? '')) ?: null,
            ];
        }

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => $this->orderNumber(),
            'customer_name' => trim((string) $input['customer_name']),
            'customer_email' => trim((string) $input['customer_email']),
            'customer_phone' => preg_replace('/[^\d+]/', '', (string) $input['customer_phone']),
            'customer_address' => "{$address}\n{$province} {$postcode}",
            'subtotal' => $totals['subtotal'],
            'tax' => $totals['tax'],
            'discount' => 0,
            'total' => $totals['total'],
            'payment_method' => $input['payment_method'],
            'payment_status' => 'pending',
            'status' => 'pending',
            'notes' => isset($input['notes']) && trim((string) $input['notes']) !== '' ? trim((string) $input['notes']) : null,
            'metadata' => [
                'campaign' => DgxSparkCampaign::KEY,
                'price' => [
                    'bundle' => $price,
                    'reference_price' => DgxSparkCampaign::referencePrice(),
                    'reference_checked_at' => DgxSparkCampaign::referenceCheckedAt()->toDateString(),
                    'markup' => DgxSparkCampaign::markup(),
                    'includes_vat' => DgxSparkCampaign::priceIncludesVat(),
                ],
                'shipping' => [
                    'name' => trim((string) $input['customer_name']),
                    'phone' => (string) $input['customer_phone'],
                    'address' => $address,
                    'province' => $province,
                    'postcode' => $postcode,
                ],
                'company' => $company,
                'hold_hours' => DgxSparkCampaign::holdHours(),
                'terms_accepted_at' => now()->toIso8601String(),
                'fulfillment' => ['status' => 'awaiting_payment', 'updated_at' => now()->toIso8601String()],
            ],
        ]);

        // The set itself. Its line carries the price before VAT, as every order line here does,
        // so the order page adds up: lines = subtotal, subtotal + VAT = total.
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $totals['subtotal'],
            'quantity' => 1,
            'subtotal' => $totals['subtotal'],
        ]);

        // One ฿0 line per included license: LicenseService issues them when the payment is approved.
        foreach (DgxSparkCampaign::licensePlan() as $slug => $type) {
            $licensed = $licenseProducts->get($slug);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $licensed->id,
                'product_name' => $licensed->name . ' — License ' . ($type === 'lifetime' ? 'ตลอดชีพ' : $type) . ' (รวมในชุด DGX Spark)',
                'price' => 0,
                'quantity' => 1,
                'subtotal' => 0,
                'custom_requirements' => json_encode(['license_type' => $type, 'bundle' => DgxSparkCampaign::KEY]),
            ]);
        }

        return $order;
    }

    private function orderNumber(): string
    {
        $prefix = DgxSparkCampaign::ORDER_PREFIX . now('Asia/Bangkok')->format('ymd') . '-';

        do {
            $number = $prefix . Str::upper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
