<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TpixPayment;
use App\Models\User;
use App\Services\AiCreditDelivery;
use App\Services\AiCreditOrders;
use App\Services\AixmanService;
use App\Services\TpixPay\TpixCheckout;
use App\Services\TpixPay\TpixMath;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use App\Services\TpixPay\TpixQuoteException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * /api/aixman/tpix/* — ai.xman4289.com sells AI credit packages for TPIX on its own /tpix page, and
 * calls these server-to-server (signed, App\Http\Middleware\VerifyAixmanSignature). The money stays
 * here: TPIX Pay quotes, finds and verifies the transfer and pays the order; AiCreditDelivery then
 * sends the credits to aixman's webhook as for every AI-credit order.
 *
 * Every call carries the user it acts for (`user_id`, from aixman's own session — never from the
 * browser). Rate limits are per user, not per address: every call comes from aixman's one server.
 * A reference that is not that user's own AI-credit quote is answered 404, as if it did not exist.
 *
 * Input is read from the signed raw JSON body only — the query string is not covered by the
 * signature, so nothing is taken from it.
 */
class AixmanTpixController extends Controller
{
    public const ORDERS_PER_MINUTE = 10;

    public const STATUS_PER_MINUTE = 40;

    public const TX_PER_MINUTE = 10;

    public function __construct(
        private AixmanService $aixman,
        private AiCreditOrders $orders,
        private TpixPaySettings $settings,
    ) {}

    /** POST /api/aixman/tpix/orders {user_id, package_slug} — a pending TPIX order (new or reused) and a payable quote. */
    public function createOrder(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $validator = Validator::make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'package_slug' => ['required', 'string', 'max:100'],
        ]);
        if ($validator->fails()) {
            return $this->refuse(422, 'คำขอไม่ครบถ้วน (ต้องมี user_id และ package_slug)');
        }

        $userId = (int) $data['user_id'];
        if ($limited = $this->throttle('orders', $userId, self::ORDERS_PER_MINUTE)) {
            return $limited;
        }

        $user = User::find($userId);
        if (! $user || $user->is_active === false) {
            return $this->refuse(422, 'ไม่พบบัญชีผู้ใช้นี้ หรือบัญชีถูกระงับการใช้งาน');
        }

        $package = $this->aixman->getPackage((string) $data['package_slug']);
        if (! $package || (int) $package['price_thb'] <= 0) {
            return $this->refuse(422, 'ไม่พบแพ็กเกจนี้ หรือเป็นแพ็กเกจที่ไม่ต้องชำระเงิน');
        }

        $pricing = $this->orders->pricing($package, TpixCheckout::METHOD);

        // TPIX off, no quote rate yet, or more than the TPIX market can carry: nothing is written
        if ($reason = TpixCheckout::refusal($pricing['total_thb'])) {
            return $this->refuse(422, $reason);
        }

        // One order per user at a time: a double click on aixman's page must not make two orders
        // (or two quotes on one order). The second call waits for the first and then reuses its order.
        $lock = Cache::lock('aixman-tpix:order:' . $user->id, 30);
        try {
            $lock->block(10);
        } catch (LockTimeoutException) {
            return $this->refuse(429, 'กำลังสร้างคำสั่งซื้อก่อนหน้าอยู่ กรุณาลองใหม่อีกครั้งในไม่กี่วินาที');
        }

        try {
            $order = $this->orders->reusableTpixOrder($user, $package)
                ?? $this->orders->create(
                    $user,
                    $package,
                    TpixCheckout::METHOD,
                    ['name' => (string) $user->name, 'email' => (string) $user->email, 'phone' => $user->phone],
                    'aixman-tpix',
                    // No referral travels with a server-to-server call (the referral cookie lives on
                    // the customer's browser at xman4289.com), so an API order carries no commission.
                    null,
                );

            $payment = TpixCheckout::openQuoteFor($order)
                ?? TpixCheckout::quoteForOrder($order, route('xdreamer.checkout.success', $order->id));
        } catch (TpixQuoteException $e) {
            return $this->refuse(422, $e->getMessage());
        } finally {
            $lock->release();
        }

        $metadata = AiCreditDelivery::metadata($order);

        return response()->json([
            'success' => true,
            'data' => [
                'reference' => $payment->reference,
                'order_number' => $order->order_number,
                'status' => $payment->status,
                'package' => [
                    'slug' => (string) ($metadata['package_slug'] ?? $package['slug']),
                    'name' => (string) ($metadata['package_name'] ?? $package['name']),
                    'credits' => (int) ($metadata['credits'] ?? 0),
                    'bonus_credits' => (int) ($metadata['bonus_credits'] ?? 0),
                ],
                'price_thb' => TpixMath::thb($order->subtotal ?? 0),
                'discount_thb' => TpixMath::thb($order->discount ?? 0),
                'total_thb' => TpixMath::thb($order->total ?? 0),
                'discount_percent' => (int) ($metadata['tpix_discount_percent'] ?? 0),
                // Exactly the wei, as a decimal: what the customer sends, every digit
                'amount_tpix' => $payment->tpixDisplay(),
                'amount_wei' => (string) $payment->amount_wei,
                'receive_address' => $payment->receive_address,
                'chain_id' => (int) config('tpix_pay.chain_id'),
                'rate_thb' => (string) $payment->rate_thb,
                'expires_at' => $payment->expires_at?->toIso8601String(),
                'seconds_left' => $this->secondsLeft($payment),
                'payment_uri' => $payment->paymentUri(),
                'pay_url' => route('tpix-pay.show', $payment),
            ],
        ]);
    }

    /** POST /api/aixman/tpix/payments/status {user_id, reference} — the quote's state; delivers the credits once paid. */
    public function status(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        if (! $this->hasUserAndReference($data)) {
            return $this->refuse(422, 'คำขอไม่ครบถ้วน (ต้องมี user_id และ reference)');
        }

        $userId = (int) $data['user_id'];
        if ($limited = $this->throttle('status', $userId, self::STATUS_PER_MINUTE)) {
            return $limited;
        }

        $payment = $this->ownedPayment($userId, $data['reference']);
        if (! $payment) {
            return $this->notFound();
        }

        // Shares the pay page's window: at most one chain look per quote per 8 seconds
        if (! $payment->isSettled() && Cache::add('tpix_pay:poll:' . $payment->id, 1, 8)) {
            try {
                app(TpixPaymentService::class)->detectFor($payment);
            } catch (Throwable $e) {
                Log::info('aixman TPIX status could not check the chain', ['payment' => $payment->reference, 'error' => $e->getMessage()]);
            }
        }

        $payment->refresh();
        $order = $this->orderOf($payment);

        // Paid and its credits not yet at aixman: send them now (once-only, AiCreditDelivery)
        if ($order && AiCreditDelivery::isOwed($order)) {
            try {
                app(AiCreditDelivery::class)->deliver($order);
            } catch (Throwable $e) {
                Log::error('aixman TPIX status could not deliver the credits', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
            $order->refresh();
        }

        return response()->json(['success' => true, 'data' => $this->statusPayload($payment, $order)]);
    }

    /** POST /api/aixman/tpix/payments/tx {user_id, reference, tx_hash} — "I paid with this transaction". */
    public function submitTx(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        if (! $this->hasUserAndReference($data)) {
            return $this->refuse(422, 'คำขอไม่ครบถ้วน (ต้องมี user_id และ reference)');
        }

        $userId = (int) $data['user_id'];
        if ($limited = $this->throttle('tx', $userId, self::TX_PER_MINUTE)) {
            return $limited;
        }

        $payment = $this->ownedPayment($userId, $data['reference']);
        if (! $payment) {
            return $this->notFound();
        }

        $hash = $data['tx_hash'] ?? null;
        if (! is_string($hash) || trim($hash) === '' || strlen($hash) > 80) {
            return $this->refuse(422, 'กรุณาวางเลขธุรกรรม (tx hash)');
        }

        $outcome = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $payment->refresh();

        return response()->json([
            'success' => true,
            'data' => $outcome + $this->statusPayload($payment, $this->orderOf($payment)),
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────────

    /**
     * The signed raw body as JSON — never $request->input(), which would also take keys from the
     * query string the signature does not cover.
     *
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }

    /** @param  array<string, mixed>  $data */
    private function hasUserAndReference(array $data): bool
    {
        return ! Validator::make($data, [
            'user_id' => ['required', 'integer', 'min:1'],
            'reference' => ['required', 'string', 'max:32'],
        ])->fails();
    }

    /** Per user and endpoint, per minute — the caller is always the same server. */
    private function throttle(string $bucket, int $userId, int $perMinute): ?JsonResponse
    {
        $key = 'aixman-tpix:' . $bucket . ':' . $userId;

        if (RateLimiter::tooManyAttempts($key, $perMinute)) {
            return $this->refuse(429, 'ส่งคำขอถี่เกินไป กรุณารอสักครู่แล้วลองใหม่')
                ->header('Retry-After', (string) max(1, RateLimiter::availableIn($key)));
        }

        RateLimiter::hit($key, 60);

        return null;
    }

    /** The user's own quote for an AI-credit order, or null (answered 404 — another user's reference does not exist). */
    private function ownedPayment(int $userId, mixed $reference): ?TpixPayment
    {
        if (! is_string($reference) || ! preg_match('/^[A-Za-z0-9]{1,32}$/', $reference)) {
            return null;
        }

        $payment = TpixPayment::where('reference', $reference)->where('user_id', $userId)->first();
        if (! $payment || $payment->purpose !== TpixPayment::PURPOSE_ORDER) {
            return null;
        }

        $order = $this->orderOf($payment);

        return $order && (int) $order->user_id === $userId && AiCreditDelivery::isAiCreditOrder($order) ? $payment : null;
    }

    private function orderOf(TpixPayment $payment): ?Order
    {
        $payable = $payment->payable()->first();

        return $payable instanceof Order ? $payable : null;
    }

    /** @return array<string, mixed> */
    private function statusPayload(TpixPayment $payment, ?Order $order): array
    {
        return [
            'reference' => $payment->reference,
            'status' => $payment->status,
            'label' => $payment->statusLabel(),
            'settled' => $payment->isSettled(),
            'payable' => $payment->isPayable(),
            'confirmations' => (int) $payment->confirmations,
            'required_confirmations' => $this->settings->confirmations(),
            'tx_hash' => $payment->tx_hash,
            'explorer_url' => $payment->explorerTxUrl(),
            'expires_at' => $payment->expires_at?->toIso8601String(),
            'seconds_left' => $this->secondsLeft($payment),
            'order_number' => $order?->order_number,
            'order_status' => $order?->status,
            'credits_delivered' => $order !== null && ! empty(AiCreditDelivery::metadata($order)['aixman_notified_at']),
        ];
    }

    private function secondsLeft(TpixPayment $payment): int
    {
        return $payment->expires_at ? max(0, (int) floor(now()->diffInSeconds($payment->expires_at, false))) : 0;
    }

    private function notFound(): JsonResponse
    {
        return $this->refuse(404, 'ไม่พบรายการชำระนี้');
    }

    private function refuse(int $status, string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
