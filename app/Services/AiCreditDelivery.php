<?php

namespace App\Services;

use App\Models\Order;
use App\Support\Alerts\BusinessAlerts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Delivers a paid AI-credit order's credits to AIXMAN (ai.xman4289.com) — exactly once.
 *
 * Every way an AI-credit order can become paid ends here: the customer's success page, the Stripe
 * webhook, and (through AiCreditOrderObserver → DeliverAiCreditsJob) an admin approving the slip on
 * the Orders or SMS Payment page, the SMS matcher, or the Telegram bot. Before this, only the first
 * two delivered, so a bank-transfer customer approved by an admin got nothing until they happened
 * to reopen the success page.
 *
 * Exactly once: the delivery is recorded as `aixman_notified_at` in the order's metadata, and the
 * check-send-record runs under a lock per order. AIXMAN's own duplicate check (by orderId) is a
 * read-then-insert, so two of our callers racing would each pass it; the lock is what stops that.
 */
class AiCreditDelivery
{
    public function __construct(private AixmanService $aixman) {}

    /**
     * Send the credits if this is a paid AI-credit order that has not had them yet.
     *
     * @return bool true when the credits are delivered — now, or already before
     */
    public function deliver(Order $order): bool
    {
        if (! self::isAiCreditOrder($order)) {
            return false;
        }

        // Someone else (the success page, the webhook, a queued retry) is delivering this order
        // right now — let them; the caller can retry and will then find it done.
        $lock = Cache::lock('ai-credits:deliver:' . $order->getKey(), 60);
        if (! $lock->get()) {
            return false;
        }

        try {
            $order->refresh();
            $metadata = self::metadata($order);

            if (! empty($metadata['aixman_notified_at'])) {
                return true;
            }
            if (! in_array($order->payment_status, ['paid', 'confirmed'], true)) {
                return false;
            }
            if (! $order->user_id) {
                // The checkout requires a login, so this should not happen — but if it does, the
                // money is in and the credits have nowhere to go: a person has to sort it out.
                Log::warning('AIXMAN credit delivery skipped — order has no user_id', ['order_id' => $order->id]);
                BusinessAlerts::aixmanCreditFailed((int) $order->id, (string) ($metadata['package_slug'] ?? ''), (int) ($metadata['credits'] ?? 0), 'ออเดอร์ไม่มีบัญชีผู้ใช้ผูกอยู่');

                return false;
            }

            $ok = $this->aixman->notifyCreditPurchase(
                (int) $order->user_id,
                (string) ($metadata['package_slug'] ?? ''),
                (int) $order->id,
                (int) ($metadata['credits'] ?? 0),
                (int) ($metadata['bonus_credits'] ?? 0),
            );

            if ($ok) {
                $metadata['aixman_notified_at'] = now()->toISOString();
                // Same storage shape the checkout uses (JSON text in the array-cast column), and
                // quietly — recording the delivery is not a change anything should react to.
                $order->forceFill(['metadata' => json_encode($metadata)])->saveQuietly();
            }

            return $ok;
        } finally {
            $lock->release();
        }
    }

    /** An order made by the AI-credit checkout (it tags its metadata with source "xdreamer"). */
    public static function isAiCreditOrder(Order $order): bool
    {
        return (self::metadata($order)['source'] ?? null) === 'xdreamer';
    }

    /** Paid, an AI-credit order, and not yet delivered — i.e. there is work for deliver(). */
    public static function isOwed(Order $order): bool
    {
        return in_array($order->payment_status, ['paid', 'confirmed'], true)
            && self::isAiCreditOrder($order)
            && empty(self::metadata($order)['aixman_notified_at']);
    }

    /**
     * The order's metadata as an array. The checkout saves json_encode()d text into the array-cast
     * column, so the cast hands back a JSON string rather than an array — decode both shapes.
     */
    public static function metadata(Order $order): array
    {
        $metadata = $order->metadata;
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        return is_array($metadata) ? $metadata : [];
    }
}
