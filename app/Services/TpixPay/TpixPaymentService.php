<?php

namespace App\Services\TpixPay;

use App\Models\Order;
use App\Models\TpixPayment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Services\LineNotifyService;
use App\Services\OrderPaymentService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Taking payment in TPIX: quote an exact amount, find the transfer on the chain, check it, settle.
 *
 * Nothing the browser says is believed. A customer (or MetaMask) only points at a transaction hash;
 * the chain's own RPC decides: the transfer went to the shop's address, on chain 4289, for exactly
 * the quoted wei, it succeeded, it is buried under enough blocks, and it happened inside the quote's
 * window. One transfer pays one quote (unique tx_hash). No two open quotes ask for the same wei
 * (unique open_key), so a transfer found by amount belongs to exactly one quote.
 *
 * Money is never silently lost:
 * - the chain cannot be reached → the quote stays where it was and is checked again next minute;
 * - a transfer after the quote expired → `late`, held for the admin (never auto-fulfilled);
 * - an order already paid or cancelled by the time its transfer lands → the THB goes to the
 *   customer's wallet (`credited_wallet`).
 * Settling takes the quote's row lock, so the scanner, the status poll and an admin re-check
 * arriving together fulfil once.
 */
class TpixPaymentService
{
    private const TAG_UNIT = '0.00000001';

    private const MAX_TAG = 9999;

    public function __construct(
        private TpixRpcClient $rpc,
        private TpixPriceService $prices,
        private TpixPaySettings $settings,
    ) {}

    /**
     * Make a quote. $fixedTpixTotal (from TpixPriceService::fixedTotalForOrder) replaces the market
     * conversion when the admin priced every line in TPIX.
     *
     * @param  array<string, mixed>  $meta
     *
     * @throws TpixQuoteException
     */
    public function createQuote(string $purpose, ?Model $payable, User $user, string|int|float $amountThb, ?string $fixedTpixTotal = null, array $meta = []): TpixPayment
    {
        if (! in_array($purpose, TpixPayment::PURPOSES, true)) {
            throw new \InvalidArgumentException("unknown purpose {$purpose}");
        }

        if (! $this->settings->acceptsPayments()) {
            throw new TpixQuoteException('ขณะนี้ยังไม่เปิดรับชำระด้วยเหรียญ TPIX');
        }

        $thb = TpixMath::thb($amountThb);
        if (! TpixMath::dec($thb)->isPositive()) {
            throw new TpixQuoteException('ยอดชำระต้องมากกว่า 0 บาท');
        }

        $spot = $this->prices->spot();

        if ($fixedTpixTotal !== null && TpixMath::dec($fixedTpixTotal)->isPositive()) {
            $base = TpixMath::dec($fixedTpixTotal)->toScale(4, RoundingMode::Ceiling);
            $rate = (string) TpixMath::dec($thb)->dividedBy($base, 12, RoundingMode::HalfUp);
            $meta['fixed_price'] = true;
        } else {
            if ($spot === null) {
                throw new TpixQuoteException('ราคา TPIX ไม่พร้อมใช้งานในขณะนี้ กรุณาลองใหม่อีกครั้งหรือเลือกช่องทางอื่น');
            }
            $rate = $spot['quote_rate_thb'];
            $base = TpixMath::dec($this->prices->thbToTpix($thb, $rate));
            $meta['spot_price_thb'] = $spot['price_thb'];
        }

        if (! $base->isPositive()) {
            throw new TpixQuoteException('ยอดชำระน้อยเกินกว่าจะชำระเป็น TPIX ได้');
        }

        $address = $this->settings->receiveAddress();
        $expiresAt = now()->addMinutes($this->settings->quoteTtlMinutes());

        for ($attempt = 0; $attempt < 25; $attempt++) {
            // A tag of 1–9999 units of 1e-8 TPIX: always below the 4th decimal the base was
            // rounded up to, so it never pushes the amount into another rounding step
            $tpix = $base->plus(BigDecimal::of(self::TAG_UNIT)->multipliedBy(random_int(1, self::MAX_TAG)))->toScale(8);
            $wei = TpixMath::toWei($tpix);
            $openKey = $address . ':' . $wei;

            try {
                return TpixPayment::create([
                    'reference' => 'TPX' . strtoupper(Str::random(13)),
                    'user_id' => $user->id,
                    'payable_type' => $payable ? $payable->getMorphClass() : null,
                    'payable_id' => $payable?->getKey(),
                    'purpose' => $purpose,
                    'amount_thb' => $thb,
                    'rate_thb' => $rate,
                    'price_usd' => $spot['price_usd'] ?? null,
                    'usd_thb' => $spot['usd_thb'] ?? null,
                    'amount_wei' => $wei,
                    'amount_tpix' => (string) $tpix,
                    'receive_address' => $address,
                    'status' => TpixPayment::STATUS_PENDING,
                    'open_key' => $openKey,
                    'expires_at' => $expiresAt,
                    'meta' => $meta,
                ]);
            } catch (UniqueConstraintViolationException) {
                // That exact amount is still reserved. If its quote is long dead, free it and retry.
                $this->releaseIfStale($openKey);
            }
        }

        throw new TpixQuoteException('ระบบสร้างยอดชำระไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    }

    /**
     * The customer (or their wallet) says "I paid with this transaction". Checked against the chain
     * at once; when the node does not know it yet, kept as a candidate the scanner keeps checking.
     *
     * @return array{result:string, reason?:string, message:string}
     */
    public function submitTx(TpixPayment $payment, string $txHash): array
    {
        $hash = strtolower(trim($txHash));

        if (! preg_match('/^0x[0-9a-f]{64}$/', $hash)) {
            return $this->outcome('rejected', 'bad_hash');
        }

        $payment->forceFill(['submitted_at' => $payment->submitted_at ?? now()])->save();

        $outcome = $this->verify($payment, $hash);

        if ($outcome['result'] === 'unknown') {
            $meta = $payment->fresh()->meta ?? [];
            $candidates = array_values(array_unique(array_merge($meta['candidate_txs'] ?? [], [$hash])));
            $meta['candidate_txs'] = array_slice($candidates, -5);
            $payment->forceFill(['meta' => $meta])->save();
        }

        return $outcome;
    }

    /**
     * Check one transaction against one quote, and settle when everything holds.
     * $acceptLate: the admin accepting a transfer that arrived after the quote expired.
     *
     * @return array{result:string, reason?:string, message:string}
     */
    public function verify(TpixPayment $payment, string $txHash, bool $acceptLate = false): array
    {
        $hash = strtolower(trim($txHash));
        if (! preg_match('/^0x[0-9a-f]{64}$/', $hash)) {
            return $this->outcome('rejected', 'bad_hash');
        }

        $payment->refresh();

        if ($payment->isSettled()) {
            return $this->outcome('settled');
        }

        if ($payment->tx_hash && $payment->tx_hash !== $hash) {
            return $this->outcome('rejected', 'other_tx');
        }

        if (TpixPayment::where('tx_hash', $hash)->whereKeyNot($payment->getKey())->exists()) {
            return $this->outcome('rejected', 'tx_used');
        }

        try {
            $tx = $this->rpc->getTransactionByHash($hash);
            if ($tx === null) {
                return $this->outcome('unknown', 'not_found');
            }

            if (strtolower((string) ($tx['to'] ?? '')) !== strtolower($payment->receive_address)) {
                return $this->outcome('rejected', 'wrong_to');
            }

            // Read from the TPIX node itself; a chain id it carries must still be ours. A legacy
            // transaction without one (v = 27/28) was nonetheless mined on this chain.
            $chainId = $this->chainIdOf($tx);
            if ($chainId !== null && $chainId !== (int) config('tpix_pay.chain_id')) {
                return $this->outcome('rejected', 'wrong_chain');
            }

            $value = TpixMath::hexToDecimal((string) ($tx['value'] ?? '0x0'));
            if (! TpixMath::equal($value, $payment->amount_wei)) {
                $this->noteMismatch($payment, $hash, $value);

                return $this->outcome('rejected', 'wrong_amount');
            }

            $chain = [
                'tx_hash' => $hash,
                'from_address' => strtolower((string) ($tx['from'] ?? '')) ?: null,
            ];

            $receipt = empty($tx['blockNumber']) ? null : $this->rpc->getTransactionReceipt($hash);
            if ($receipt === null) {
                // In the mempool: claim the transaction for this quote and wait for it to be mined
                return $this->hold($payment, $chain + ['confirmations' => 0], TpixPayment::STATUS_CONFIRMING);
            }

            if (strtolower((string) ($receipt['status'] ?? '')) !== '0x1') {
                return $this->outcome('rejected', 'reverted');
            }

            $blockNumber = TpixRpcClient::hexToInt($tx['blockNumber']);
            $latest = $this->rpc->blockNumber();
            $block = $this->rpc->getBlockByNumber($blockNumber);
            if ($block === null) {
                return $this->outcome('unknown', 'rpc');
            }
            $minedAt = Carbon::createFromTimestamp(TpixRpcClient::hexToInt($block['timestamp'] ?? null));
        } catch (TpixRpcException $e) {
            Log::info('TPIX verify deferred: chain unreachable', ['payment' => $payment->reference, 'error' => $e->getMessage()]);

            return $this->outcome('unknown', 'rpc');
        }

        $chain += [
            'block_number' => $blockNumber,
            'confirmations' => max(0, $latest - $blockNumber + 1),
        ];

        if ($minedAt->lt($payment->created_at->copy()->subSeconds(30))) {
            return $this->outcome('rejected', 'before_quote');
        }

        $late = $minedAt->gt($payment->expires_at)
            || in_array($payment->status, [TpixPayment::STATUS_CANCELLED, TpixPayment::STATUS_FAILED, TpixPayment::STATUS_LATE], true);

        if ($late && ! $acceptLate) {
            return $this->hold($payment, $chain + ['failure_reason' => 'transfer after the quote expired'], TpixPayment::STATUS_LATE);
        }

        if ($chain['confirmations'] < $this->settings->confirmations()) {
            return $this->hold($payment, $chain, $late ? TpixPayment::STATUS_LATE : TpixPayment::STATUS_CONFIRMING);
        }

        return $this->settle($payment, $chain);
    }

    /**
     * The scanner: look on the explorer for transfers matching open quotes (RPC still decides),
     * re-check the confirming ones and the hashes customers pasted before the node knew them.
     *
     * @return array{checked:int, settled:int}
     */
    public function detectPending(): array
    {
        $stats = ['checked' => 0, 'settled' => 0];

        $open = $this->openQuotes()->get();
        foreach ($open->groupBy('receive_address') as $address => $payments) {
            $this->matchFromExplorer((string) $address, $payments, $stats);
        }

        $followUps = TpixPayment::where('status', TpixPayment::STATUS_CONFIRMING)->whereNotNull('tx_hash')->get();
        foreach ($followUps as $payment) {
            $this->count($this->verify($payment, $payment->tx_hash), $stats);
        }

        foreach ($open as $payment) {
            foreach (($payment->fresh()?->meta['candidate_txs'] ?? []) as $hash) {
                $this->count($this->verify($payment, $hash), $stats);
            }
        }

        return $stats;
    }

    /** The same search for one quote — the pay page's status poll asks this every few seconds. */
    public function detectFor(TpixPayment $payment): void
    {
        $payment->refresh();

        if ($payment->status === TpixPayment::STATUS_CONFIRMING && $payment->tx_hash) {
            $this->verify($payment, $payment->tx_hash);

            return;
        }

        if (! in_array($payment->status, [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED], true) || ! $payment->open_key) {
            return;
        }

        $stats = ['checked' => 0, 'settled' => 0];
        foreach ($payment->meta['candidate_txs'] ?? [] as $hash) {
            $this->count($this->verify($payment, $hash), $stats);
        }

        if (! $payment->fresh()->isSettled()) {
            $this->matchFromExplorer($payment->receive_address, collect([$payment]), $stats);
        }
    }

    /**
     * Quotes past expiry become `expired` (still matched — a transfer made in time but seen late is
     * honoured); past expiry + grace their exact amount is released for new quotes.
     *
     * @return array{expired:int, released:int}
     */
    public function expireStale(): array
    {
        $expired = TpixPayment::where('status', TpixPayment::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->update(['status' => TpixPayment::STATUS_EXPIRED, 'updated_at' => now()]);

        $released = TpixPayment::whereNotNull('open_key')
            ->whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED, TpixPayment::STATUS_CANCELLED])
            ->where('expires_at', '<', now()->subMinutes($this->graceMinutes()))
            ->update(['open_key' => null, 'updated_at' => now()]);

        return ['expired' => $expired, 'released' => $released];
    }

    /** Admin: cancel a quote that was never paid (its amount is released at once). */
    public function cancel(TpixPayment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $locked = TpixPayment::whereKey($payment->getKey())->lockForUpdate()->first();
            if (! $locked || ! in_array($locked->status, [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED], true)) {
                return false;
            }

            $locked->update(['status' => TpixPayment::STATUS_CANCELLED, 'open_key' => null]);

            return true;
        });
    }

    /** Quotes the scanner still looks for by amount. */
    public function openQuotes()
    {
        return TpixPayment::whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED])
            ->whereNotNull('open_key')
            ->where('expires_at', '>', now()->subMinutes($this->graceMinutes()));
    }

    /**
     * Is this order still waiting for its money? (Otherwise a TPIX transfer for it goes to the wallet.)
     */
    public static function orderIsPayable(Order $order): bool
    {
        return in_array($order->payment_status, ['pending', 'verifying'], true)
            && ! in_array($order->status, ['cancelled', 'completed'], true);
    }

    // ── internals ────────────────────────────────────────────────────

    /**
     * Claim the transaction for this quote without settling it yet (confirming / late).
     *
     * @param  array<string, mixed>  $chain
     * @return array{result:string, reason?:string, message:string}
     */
    private function hold(TpixPayment $payment, array $chain, string $status): array
    {
        try {
            $held = DB::transaction(function () use ($payment, $chain, $status) {
                $locked = TpixPayment::whereKey($payment->getKey())->lockForUpdate()->first();
                if (! $locked || $locked->isSettled()) {
                    return false;
                }

                $locked->fill($chain + ['status' => $status]);
                if ($status === TpixPayment::STATUS_LATE) {
                    // Matched to a transfer: its amount no longer needs reserving
                    $locked->open_key = null;
                }
                $locked->save();

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->outcome('rejected', 'tx_used');
        }

        if (! $held) {
            return $this->outcome('settled');
        }

        if ($status === TpixPayment::STATUS_LATE) {
            Log::warning('TPIX transfer arrived after its quote expired — waiting for the admin', ['payment' => $payment->reference]);
            if ($payment->status !== TpixPayment::STATUS_LATE) {
                // Told once, when it first turns late: the money is in, someone has to decide
                $this->notifyAdmin("⏰ TPIX โอนหลังหมดเวลา — รอแอดมินตัดสิน\n"
                    . '🧾 ' . $payment->fresh()->purposeLabel() . ' · #' . $payment->reference . "\n"
                    . '💰 ' . $payment->tpixDisplay() . " TPIX\n"
                    . '👉 ' . route('admin.tpix-payments.index', ['status' => TpixPayment::STATUS_LATE]));
            }

            return $this->outcome('late');
        }

        return $this->outcome('confirming');
    }

    /**
     * Mark paid and deliver, under the quote's row lock (idempotent).
     *
     * @param  array<string, mixed>  $chain
     * @return array{result:string, reason?:string, message:string}
     */
    private function settle(TpixPayment $payment, array $chain): array
    {
        try {
            $status = DB::transaction(function () use ($payment, $chain) {
                $locked = TpixPayment::whereKey($payment->getKey())->lockForUpdate()->first();
                if (! $locked || $locked->isSettled()) {
                    return null;
                }

                // Claim the transaction first: the unique index refuses it if another quote has it
                $locked->fill($chain + ['paid_at' => now(), 'open_key' => null, 'failure_reason' => null]);
                $locked->save();

                $status = $this->fulfil($locked);
                $locked->update(['status' => $status]);

                return $status;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->outcome('rejected', 'tx_used');
        }

        if ($status === null) {
            return $this->outcome('settled');
        }

        $payment->refresh();
        $this->announce($payment);

        return $this->outcome($status === TpixPayment::STATUS_PAID ? 'paid' : 'credited_wallet');
    }

    /** Deliver what was paid for; returns the final status. Runs inside settle()'s transaction. */
    private function fulfil(TpixPayment $payment): string
    {
        $payable = $payment->payable;
        $note = "[TPIX] ชำระ {$payment->tpixDisplay()} TPIX · tx {$payment->tx_hash}";

        if ($payment->purpose === TpixPayment::PURPOSE_ORDER && $payable instanceof Order) {
            $order = Order::whereKey($payable->getKey())->lockForUpdate()->first();

            if ($order && self::orderIsPayable($order)) {
                app(OrderPaymentService::class)->approve($order);
                $order->refresh();
                $order->update(['notes' => ($order->notes ? $order->notes . "\n" : '') . $note . ' — ' . now()->format('d/m/Y H:i')]);

                return TpixPayment::STATUS_PAID;
            }

            $this->creditWallet($payment, "คำสั่งซื้อ #{$payable->order_number} ไม่อยู่ในสถานะรอชำระแล้ว");

            return TpixPayment::STATUS_CREDITED_WALLET;
        }

        if ($payment->purpose === TpixPayment::PURPOSE_WALLET_TOPUP && $payable instanceof WalletTopup) {
            if ($payable->status === WalletTopup::STATUS_PENDING && $payable->approve(0)) {
                return TpixPayment::STATUS_PAID;
            }
        }

        // The order/top-up is gone or already settled another way: the money still belongs to the customer
        $this->creditWallet($payment, $payment->purpose === TpixPayment::PURPOSE_WALLET_TOPUP
            ? 'รายการเติมเงินถูกยกเลิกหรือดำเนินการไปแล้ว'
            : 'ไม่พบคำสั่งซื้อที่รอชำระ');

        return TpixPayment::STATUS_CREDITED_WALLET;
    }

    private function creditWallet(TpixPayment $payment, string $why): void
    {
        Wallet::getOrCreateForUser($payment->user_id)->deposit(
            (float) $payment->amount_thb,
            "เงินจากการชำระ TPIX #{$payment->reference} ({$why})",
            'tpix',
            $payment->reference,
            null,
            ['tpix_payment_id' => $payment->id, 'tx_hash' => $payment->tx_hash, 'amount_tpix' => $payment->tpixDisplay()],
        );
    }

    /**
     * @param  Collection<int, TpixPayment>  $payments
     * @param  array{checked:int, settled:int}  $stats
     */
    private function matchFromExplorer(string $address, Collection $payments, array &$stats): void
    {
        $since = $payments->min('created_at')->copy()->subSeconds(30);

        try {
            $transfers = $this->explorerTransfers($address, $since);
        } catch (Throwable $e) {
            Log::info('TPIX explorer unreachable, will retry', ['error' => $e->getMessage()]);

            return;
        }

        foreach ($transfers as $transfer) {
            $match = $payments->first(fn (TpixPayment $p) => TpixMath::equal($p->amount_wei, $transfer['value'])
                && $transfer['at']->gte($p->created_at->copy()->subSeconds(30))
                && $transfer['at']->lte($p->expires_at->copy()->addMinutes($this->graceMinutes())));

            if ($match && ! TpixPayment::where('tx_hash', $transfer['hash'])->exists()) {
                $this->count($this->verify($match, $transfer['hash']), $stats);
            }
        }
    }

    /**
     * Transfers into $address on the explorer (Blockscout API v2), newest first, back to $since.
     *
     * @return list<array{hash:string, value:string, at:Carbon}>
     */
    private function explorerTransfers(string $address, Carbon $since): array
    {
        $url = rtrim(config('tpix_pay.explorer_url'), '/') . '/api/v2/addresses/' . $address . '/transactions';
        $query = ['filter' => 'to'];
        $found = [];

        for ($page = 0; $page < 5; $page++) {
            $response = Http::withHeaders(['User-Agent' => config('tpix_pay.user_agent')])
                ->acceptJson()
                ->timeout(8)
                ->get($url, $query);

            if (! $response->successful()) {
                throw new TpixRpcException('explorer HTTP ' . $response->status());
            }

            $reachedSince = false;
            foreach ((array) $response->json('items', []) as $item) {
                $at = isset($item['timestamp']) ? Carbon::parse($item['timestamp']) : null;
                if ($at === null) {
                    continue;
                }
                if ($at->lt($since)) {
                    $reachedSince = true;

                    continue;
                }

                $hash = strtolower((string) ($item['hash'] ?? ''));
                $value = (string) ($item['value'] ?? '');
                $ok = ($item['status'] ?? null) === 'ok' || ($item['result'] ?? null) === 'success';

                if ($ok && preg_match('/^0x[0-9a-f]{64}$/', $hash) && ctype_digit($value)) {
                    $found[] = ['hash' => $hash, 'value' => $value, 'at' => $at];
                }
            }

            $next = $response->json('next_page_params');
            if ($reachedSince || ! is_array($next) || $next === []) {
                break;
            }
            $query = ['filter' => 'to'] + $next;
        }

        return $found;
    }

    /** @param  array<string, mixed>  $tx */
    private function chainIdOf(array $tx): ?int
    {
        if (! empty($tx['chainId'])) {
            return TpixRpcClient::hexToInt($tx['chainId']);
        }

        if (! empty($tx['v'])) {
            $v = TpixRpcClient::hexToInt($tx['v']);

            return $v >= 35 ? intdiv($v - 35, 2) : null;
        }

        return null;
    }

    /** A transfer to the shop for the wrong amount: recorded on the quote so the admin can see it. */
    private function noteMismatch(TpixPayment $payment, string $hash, string $valueWei): void
    {
        $payment->forceFill([
            'failure_reason' => Str::limit('wrong amount: tx ' . $hash . ' sent ' . rtrim(rtrim(TpixMath::fromWei($valueWei), '0'), '.') . ' TPIX', 250),
        ])->save();
    }

    private function releaseIfStale(string $openKey): void
    {
        TpixPayment::where('open_key', $openKey)
            ->whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED, TpixPayment::STATUS_CANCELLED])
            ->where('expires_at', '<', now()->subMinutes($this->graceMinutes()))
            ->update(['open_key' => null, 'updated_at' => now()]);
    }

    private function graceMinutes(): int
    {
        return (int) config('tpix_pay.late_grace_minutes', 120);
    }

    /**
     * @param  array{result:string}  $outcome
     * @param  array{checked:int, settled:int}  $stats
     */
    private function count(array $outcome, array &$stats): void
    {
        $stats['checked']++;
        if (in_array($outcome['result'], ['paid', 'credited_wallet'], true)) {
            $stats['settled']++;
        }
    }

    /** Tell the admin (LINE), as a card payment does. */
    private function announce(TpixPayment $payment): void
    {
        $this->notifyAdmin("🪙 ชำระด้วย TPIX สำเร็จ!\n"
            . "━━━━━━━━━━━━━━━\n"
            . '🧾 ' . $payment->purposeLabel() . "\n"
            . '💰 ฿' . number_format((float) $payment->amount_thb, 2) . ' = ' . $payment->tpixDisplay() . " TPIX\n"
            . '📌 ' . $payment->statusLabel() . "\n"
            . '⏰ ' . now()->timezone('Asia/Bangkok')->format('d/m/Y H:i'));
    }

    /** Never lets a notification failure surface — the payment itself is already recorded. */
    private function notifyAdmin(string $message): void
    {
        try {
            (new LineNotifyService)->send($message);
        } catch (Throwable $e) {
            Log::warning('TPIX admin notification failed', ['error' => $e->getMessage()]);
        }
    }

    /** @return array{result:string, reason?:string, message:string} */
    private function outcome(string $result, ?string $reason = null): array
    {
        $message = match ($reason ?? $result) {
            'paid' => 'ชำระเงินสำเร็จ ขอบคุณครับ',
            'credited_wallet' => 'ได้รับเหรียญแล้ว — รายการนี้ชำระ/ยกเลิกไปก่อนหน้า ระบบจึงเติมยอดเป็นเงินเข้า Wallet ของคุณ',
            'settled' => 'รายการนี้ชำระเรียบร้อยแล้ว',
            'confirming' => 'พบธุรกรรมแล้ว กำลังรอการยืนยันบนเชน',
            'late' => 'ได้รับธุรกรรมหลังหมดเวลาชำระ — ทีมงานจะตรวจสอบและดำเนินการให้',
            'bad_hash' => 'เลขธุรกรรมไม่ถูกต้อง (ต้องขึ้นต้นด้วย 0x ตามด้วยอักษร 64 ตัว)',
            'tx_used' => 'ธุรกรรมนี้ถูกใช้ชำระรายการอื่นไปแล้ว',
            'other_tx' => 'รายการนี้ผูกกับธุรกรรมอื่นอยู่แล้ว',
            'not_found' => 'ยังไม่พบธุรกรรมนี้บน TPIX Chain — ระบบจะตรวจซ้ำให้อัตโนมัติ',
            'rpc' => 'ตรวจสอบกับเชนไม่ได้ชั่วคราว ระบบจะลองใหม่ให้อัตโนมัติ',
            'wrong_to' => 'ธุรกรรมนี้ไม่ได้โอนเข้าที่อยู่รับเงินของร้าน',
            'wrong_chain' => 'ธุรกรรมนี้ไม่ได้อยู่บน TPIX Chain (chain id 4289)',
            'wrong_amount' => 'ยอดที่โอนไม่ตรงกับยอดที่ต้องชำระ (ต้องตรงทุกหลัก) — หากโอนผิดยอด กรุณาติดต่อทีมงาน',
            'reverted' => 'ธุรกรรมนี้ล้มเหลวบนเชน เหรียญไม่ได้ถูกโอน',
            'before_quote' => 'ธุรกรรมนี้เกิดขึ้นก่อนสร้างรายการชำระนี้',
            default => 'ยังตรวจสอบไม่ได้ กรุณาลองใหม่อีกครั้ง',
        };

        return array_filter(['result' => $result, 'reason' => $reason, 'message' => $message], fn ($v) => $v !== null);
    }
}
