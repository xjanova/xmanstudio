<?php

namespace App\Services\TpixPay;

use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\TpixPayment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Services\LineNotifyService;
use App\Services\OrderPaymentService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Taking payment in TPIX: quote an exact amount, find the transfer on the chain, check it, settle.
 *
 * Nothing the browser says is believed. A customer (or MetaMask) only points at a transaction hash;
 * the chain's own RPC decides: the transfer went to the shop's address, on chain 4289, for exactly
 * the quoted wei, it is MINED with a successful receipt, buried under enough blocks, and inside the
 * quote's window. Only then is the hash bound to the quote (unique tx_hash: one transfer pays one
 * quote). A hash that is not mined yet is only remembered and re-checked — binding a mempool hash
 * would let a dropped, replaced or deliberately stuck transaction lock the quote for good.
 * No two open quotes ask for the same wei (unique open_key), so a transfer found by amount belongs
 * to exactly one quote.
 *
 * Money is never silently lost, and never more than arrived:
 * - the chain cannot be reached → the quote stays where it was and is checked again next minute;
 * - a transfer after the quote expired → `late`, held for the admin (never auto-fulfilled);
 * - coins for an order already paid or cancelled (or a second quote on one top-up) → the wallet,
 *   but only the coins' value now (capped at the quote's THB) and within the customer's daily
 *   auto-credit cap; otherwise `held` for the admin.
 * Settling takes the quote's row lock (no chain or FX calls inside it), so the scanner, the status
 * poll and an admin re-check arriving together fulfil once.
 */
class TpixPaymentService
{
    private const TAG_UNIT = '0.00000001';

    private const MAX_TAG = 9999;

    public const CURSOR_KEY = 'tpix_scan_cursor';

    public function __construct(
        private TpixRpcClient $rpc,
        private TpixPriceService $prices,
        private TpixPaySettings $settings,
    ) {}

    /**
     * Make a quote. $fixedTpixTotal (from TpixPriceService::fixedTotalForOrder) is honoured only where
     * it is not below the market value of the THB amount.
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

        $spot = $this->prices->spotForQuote();
        if ($spot === null) {
            throw new TpixQuoteException('ราคา TPIX ไม่พร้อมใช้งานในขณะนี้ กรุณาลองใหม่อีกครั้งหรือเลือกช่องทางอื่น');
        }
        if (($spot['quote_rate_thb'] ?? null) === null) {
            throw new TpixQuoteException('ระบบกำลังเก็บราคาเฉลี่ยของ TPIX — กรุณาลองใหม่ในอีกไม่กี่นาที หรือเลือกช่องทางอื่น');
        }
        if (TpixMath::dec($thb)->isGreaterThan($spot['max_quote_thb'])) {
            throw new TpixQuoteException(TpixCheckout::overPoolMessage($spot['max_quote_thb']));
        }

        $rate = $spot['quote_rate_thb'];
        $base = TpixMath::dec($this->prices->thbToTpix($thb, $rate));
        $meta['spot_price_thb'] = $spot['price_thb'];

        if ($fixedTpixTotal !== null && TpixMath::dec($fixedTpixTotal)->isPositive()) {
            $fixed = TpixMath::dec($fixedTpixTotal)->toScale(4, RoundingMode::Ceiling);
            if ($fixed->isGreaterThan($base)) {
                $base = $fixed;
                $rate = (string) TpixMath::dec($thb)->dividedBy($base, 12, RoundingMode::HalfUp);
                $meta['fixed_price'] = true;
            }
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
                    'price_usd' => $spot['price_usd'],
                    'usd_thb' => $spot['usd_thb'],
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
     * at once; until it is mined it is only remembered (at most 5) and the scanner keeps checking it.
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
            $hashes = array_values(array_unique(array_merge($meta['submitted_hashes'] ?? [], [$hash])));
            $meta['submitted_hashes'] = array_slice($hashes, -5);
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

        if ($payment->isFinal()) {
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

            // Not mined (or no receipt yet): nothing is bound. A dropped or replaced transaction must
            // not hold the quote; the hash is re-checked until it is mined.
            $receipt = empty($tx['blockNumber']) ? null : $this->rpc->getTransactionReceipt($hash);
            if ($receipt === null) {
                return $this->outcome('unknown', 'not_mined');
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

        $chain = [
            'tx_hash' => $hash,
            'from_address' => strtolower((string) ($tx['from'] ?? '')) ?: null,
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

        return $this->settle($payment, $chain, byAdmin: $acceptLate);
    }

    /**
     * The scanner: walk the new blocks for transfers to the shop's address(es) that match open quotes
     * (RPC still decides), re-check the confirming ones and the hashes customers pasted before they
     * were mined. Each transfer and each quote is isolated: one bad row cannot stop the minute's run.
     *
     * @return array{checked:int, settled:int, blocks:int}
     */
    public function detectPending(): array
    {
        $stats = ['checked' => 0, 'settled' => 0, 'blocks' => 0];

        try {
            $this->walkBlocks($stats);
        } catch (Throwable $e) {
            Log::warning('TPIX block walk stopped, will resume from its cursor', ['error' => $e->getMessage()]);
        }

        foreach (TpixPayment::where('status', TpixPayment::STATUS_CONFIRMING)->whereNotNull('tx_hash')->get() as $payment) {
            $this->isolated(fn () => $this->count($this->verify($payment, $payment->tx_hash), $stats), $payment);
        }

        foreach ($this->openQuotes()->get() as $payment) {
            foreach ($payment->meta['submitted_hashes'] ?? [] as $hash) {
                $this->isolated(fn () => $this->count($this->verify($payment, $hash), $stats), $payment);
            }
        }

        return $stats;
    }

    /** The pay page's status poll: re-check this quote's confirming transaction or pasted hashes. */
    public function detectFor(TpixPayment $payment): void
    {
        $payment->refresh();

        if ($payment->status === TpixPayment::STATUS_CONFIRMING && $payment->tx_hash) {
            $this->verify($payment, $payment->tx_hash);

            return;
        }

        if (! in_array($payment->status, [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED], true)) {
            return;
        }

        foreach ($payment->meta['submitted_hashes'] ?? [] as $hash) {
            if ($payment->fresh()->isFinal()) {
                return;
            }
            $this->verify($payment, $hash);
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

    /**
     * Admin: credit a `held` payment to the wallet — the coins' value now, capped at the quote's THB
     * (the daily auto-credit cap is what the admin is overriding). Stays held while there is no price.
     *
     * @return array{result:string, reason?:string, message:string}
     */
    public function approveHeld(TpixPayment $payment): array
    {
        $rate = $this->prices->quoteRateThb();

        $status = DB::transaction(function () use ($payment, $rate) {
            $locked = TpixPayment::whereKey($payment->getKey())->lockForUpdate()->first();
            if (! $locked || $locked->status !== TpixPayment::STATUS_HELD) {
                return null;
            }

            $status = $this->creditOrHold($locked, 'แอดมินอนุมัติ', $rate, byAdmin: true);
            $locked->update(['status' => $status]);

            return $status;
        });

        return match ($status) {
            TpixPayment::STATUS_CREDITED_WALLET => $this->outcome('credited_wallet'),
            TpixPayment::STATUS_HELD => $this->outcome('held', 'no_price'),
            default => $this->outcome('rejected', 'not_held'),
        };
    }

    /** Quotes the scanner still looks for by amount. */
    public function openQuotes()
    {
        return TpixPayment::whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED])
            ->whereNotNull('open_key')
            ->where('expires_at', '>', now()->subMinutes($this->graceMinutes()));
    }

    /** Is this order still waiting for its money? (Otherwise a TPIX transfer for it goes to the wallet.) */
    public static function orderIsPayable(Order $order): bool
    {
        return in_array($order->payment_status, ['pending', 'verifying'], true)
            && ! in_array($order->status, ['cancelled', 'completed'], true);
    }

    /** The last block the scanner has looked at, or null before its first run. */
    public static function scanCursor(): ?int
    {
        $value = PaymentSetting::get(self::CURSOR_KEY);

        return is_numeric($value) ? (int) $value : null;
    }

    // ── internals ────────────────────────────────────────────────────

    /**
     * From the cursor up to the chain's head (at most scan_max_blocks per run), fetching blocks with
     * their transactions in JSON-RPC batches; native transfers with a value to a receive address are
     * matched against open quotes by exact wei. A block the node did not return stops the walk with
     * the cursor just before it. With no open quotes the cursor just follows the head.
     *
     * @param  array{checked:int, settled:int, blocks:int}  $stats
     */
    private function walkBlocks(array &$stats): void
    {
        $latest = $this->rpc->blockNumber();
        $open = $this->openQuotes()->get();

        if ($open->isEmpty()) {
            $this->saveCursor($latest);

            return;
        }

        $maxBlocks = max(1, (int) config('tpix_pay.scan_max_blocks', 900));
        $batchSize = max(1, (int) config('tpix_pay.scan_batch_size', 40));
        $cursor = self::scanCursor();
        if ($cursor === null || $cursor > $latest) {
            $cursor = max(0, $latest - $maxBlocks);
        }

        $to = min($latest, $cursor + $maxBlocks);
        $addresses = $open->pluck('receive_address')->map(fn ($a) => strtolower((string) $a))->unique()->all();

        for ($start = $cursor + 1; $start <= $to; $start += $batchSize) {
            $end = min($to, $start + $batchSize - 1);
            $calls = [];
            for ($n = $start; $n <= $end; $n++) {
                $calls[] = ['eth_getBlockByNumber', ['0x' . dechex($n), true]];
            }

            $blocks = $this->rpc->batch($calls);

            foreach (range($start, $end) as $i => $number) {
                $block = $blocks[$i] ?? null;
                if (! is_array($block) || ! isset($block['timestamp'])) {
                    $this->saveCursor($number - 1);

                    return;
                }

                $minedAt = Carbon::createFromTimestamp(TpixRpcClient::hexToInt($block['timestamp']));
                foreach ((array) ($block['transactions'] ?? []) as $tx) {
                    if (! is_array($tx) || ! in_array(strtolower((string) ($tx['to'] ?? '')), $addresses, true)) {
                        continue;
                    }

                    $this->isolated(function () use ($open, $tx, $minedAt, &$stats) {
                        $value = TpixMath::hexToDecimal((string) ($tx['value'] ?? '0x0'));
                        if ($value === '0') {
                            return; // zero-value spam costs nothing on a gas-0 chain
                        }
                        $this->matchTransfer($open, strtolower((string) $tx['to']), $value, strtolower((string) ($tx['hash'] ?? '')), $minedAt, $stats);
                    });
                }
                $stats['blocks']++;
            }

            $this->saveCursor($end);
        }
    }

    /**
     * @param  EloquentCollection<int, TpixPayment>  $open
     * @param  array{checked:int, settled:int, blocks:int}  $stats
     */
    private function matchTransfer(EloquentCollection $open, string $to, string $value, string $hash, Carbon $minedAt, array &$stats): void
    {
        if (! preg_match('/^0x[0-9a-f]{64}$/', $hash) || TpixPayment::where('tx_hash', $hash)->exists()) {
            return;
        }

        $match = $open->first(fn (TpixPayment $p) => strtolower($p->receive_address) === $to
            && TpixMath::equal($p->amount_wei, $value)
            && $minedAt->gte($p->created_at->copy()->subSeconds(30))
            && $minedAt->lte($p->expires_at->copy()->addMinutes($this->graceMinutes())));

        if ($match) {
            $this->count($this->verify($match, $hash), $stats);
        }
    }

    private function saveCursor(int $block): void
    {
        PaymentSetting::set(self::CURSOR_KEY, max(0, $block), [
            'group' => 'tpix_scan',
            'type' => 'integer',
            'label' => 'บล็อกล่าสุดที่ตัวสแกน TPIX ตรวจแล้ว',
        ]);
    }

    /** Run one row's work; an exception is logged and the run goes on with the next row. */
    private function isolated(callable $work, ?TpixPayment $payment = null): void
    {
        try {
            $work();
        } catch (Throwable $e) {
            Log::error('TPIX scan: one item failed, the rest go on', [
                'payment' => $payment?->reference,
                'error' => class_basename($e) . ': ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Bind a MINED, successful transaction to this quote without settling it yet (confirming / late).
     *
     * @param  array<string, mixed>  $chain
     * @return array{result:string, reason?:string, message:string}
     */
    private function hold(TpixPayment $payment, array $chain, string $status): array
    {
        try {
            $held = DB::transaction(function () use ($payment, $chain, $status) {
                $locked = TpixPayment::whereKey($payment->getKey())->lockForUpdate()->first();
                if (! $locked || $locked->isFinal()) {
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
     * Mark paid and deliver, under the quote's row lock (idempotent). The rate a possible wallet credit
     * needs is read BEFORE the lock is taken — no chain or FX call while a row is locked.
     *
     * @param  array<string, mixed>  $chain
     * @return array{result:string, reason?:string, message:string}
     */
    private function settle(TpixPayment $payment, array $chain, bool $byAdmin = false): array
    {
        $creditRate = $this->prices->quoteRateThb();

        try {
            $status = DB::transaction(function () use ($payment, $chain, $creditRate, $byAdmin) {
                $locked = TpixPayment::whereKey($payment->getKey())->lockForUpdate()->first();
                if (! $locked || $locked->isFinal()) {
                    return null;
                }

                // Claim the transaction first: the unique index refuses it if another quote has it
                $locked->fill($chain + ['paid_at' => now(), 'open_key' => null, 'failure_reason' => null]);
                $locked->save();

                $status = $this->fulfil($locked, $creditRate, $byAdmin);
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

        return $this->outcome(match ($status) {
            TpixPayment::STATUS_PAID => 'paid',
            TpixPayment::STATUS_HELD => 'held',
            default => 'credited_wallet',
        });
    }

    /** Deliver what was paid for; returns the final status. Runs inside settle()'s transaction. */
    private function fulfil(TpixPayment $payment, ?string $creditRate, bool $byAdmin): string
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

            return $this->creditOrHold($payment, "คำสั่งซื้อ #{$payable->order_number} ไม่อยู่ในสถานะรอชำระแล้ว", $creditRate, $byAdmin);
        }

        if ($payment->purpose === TpixPayment::PURPOSE_WALLET_TOPUP && $payable instanceof WalletTopup) {
            if ($payable->status === WalletTopup::STATUS_PENDING && $payable->approve(0)) {
                return TpixPayment::STATUS_PAID;
            }
        }

        // The order/top-up is gone or already settled another way: the coins still belong to the customer
        return $this->creditOrHold($payment, $payment->purpose === TpixPayment::PURPOSE_WALLET_TOPUP
            ? 'รายการเติมเงินถูกยกเลิกหรือดำเนินการไปแล้ว'
            : 'ไม่พบคำสั่งซื้อที่รอชำระ', $creditRate, $byAdmin);
    }

    /**
     * Put the coins' value in the wallet, or hold them for the admin.
     *
     * The credit is what actually arrived, valued now (amount_tpix × the current quote rate, rounded
     * down to the satang) and never more than the quote's THB: coins bought cheap must not come back
     * as more baht than they are worth. No price → held. Over the customer's daily auto-credit cap →
     * held (the admin can still approve it).
     */
    private function creditOrHold(TpixPayment $payment, string $why, ?string $rate, bool $byAdmin): string
    {
        if ($rate === null) {
            $payment->failure_reason = 'held: no TPIX price to value the coins';

            return TpixPayment::STATUS_HELD;
        }

        $value = TpixMath::dec(TpixMath::fromWei((string) $payment->amount_wei))->multipliedBy($rate)->toScale(2, RoundingMode::Down);
        $credit = $value->isLessThan(TpixMath::thb($payment->amount_thb)) ? $value : TpixMath::dec(TpixMath::thb($payment->amount_thb));

        if (! $credit->isPositive()) {
            $payment->failure_reason = 'held: the coins are worth less than 0.01 THB now';

            return TpixPayment::STATUS_HELD;
        }

        if (! $byAdmin) {
            $today = TpixPayment::where('user_id', $payment->user_id)
                ->where('status', TpixPayment::STATUS_CREDITED_WALLET)
                ->where('paid_at', '>=', now()->startOfDay())
                ->sum('credited_thb');
            $cap = (string) config('tpix_pay.auto_credit_daily_cap_thb', 5000);

            if (TpixMath::dec(TpixMath::thb((string) $today))->plus($credit)->isGreaterThan(TpixMath::thb($cap))) {
                $payment->failure_reason = 'held: over the daily automatic wallet-credit cap';

                return TpixPayment::STATUS_HELD;
            }
        }

        Wallet::getOrCreateForUser($payment->user_id)->deposit(
            (float) (string) $credit,
            "เงินจากการชำระ TPIX #{$payment->reference} ({$why})",
            'tpix',
            $payment->reference,
            null,
            ['tpix_payment_id' => $payment->id, 'tx_hash' => $payment->tx_hash, 'amount_tpix' => $payment->tpixDisplay(), 'rate_thb' => $rate],
        );

        $payment->credited_thb = (string) $credit;
        $payment->failure_reason = null;

        return TpixPayment::STATUS_CREDITED_WALLET;
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
     * @param  array<string, int>  $stats
     */
    private function count(array $outcome, array &$stats): void
    {
        $stats['checked']++;
        if (in_array($outcome['result'], ['paid', 'credited_wallet', 'held'], true)) {
            $stats['settled']++;
        }
    }

    /** Tell the admin (LINE), as a card payment does. */
    private function announce(TpixPayment $payment): void
    {
        $this->notifyAdmin(($payment->status === TpixPayment::STATUS_HELD ? "⚠️ ได้รับ TPIX — รอแอดมินอนุมัติเครดิต\n" : "🪙 ชำระด้วย TPIX สำเร็จ!\n")
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
            'credited_wallet' => 'ได้รับเหรียญแล้ว — รายการนี้ชำระ/ยกเลิกไปก่อนหน้า ระบบจึงเติมมูลค่าเหรียญเป็นเงินเข้า Wallet ของคุณ',
            'held' => 'ได้รับเหรียญแล้ว — รอทีมงานอนุมัติการเติมเงินเข้า Wallet',
            'no_price' => 'ยังไม่มีราคา TPIX สำหรับคิดมูลค่าเหรียญ — ลองอนุมัติใหม่อีกครั้งภายหลัง',
            'not_held' => 'รายการนี้ไม่ได้รอการอนุมัติ',
            'settled' => 'รายการนี้ชำระเรียบร้อยแล้ว',
            'confirming' => 'พบธุรกรรมแล้ว กำลังรอการยืนยันบนเชน',
            'late' => 'ได้รับธุรกรรมหลังหมดเวลาชำระ — ทีมงานจะตรวจสอบและดำเนินการให้',
            'bad_hash' => 'เลขธุรกรรมไม่ถูกต้อง (ต้องขึ้นต้นด้วย 0x ตามด้วยอักษร 64 ตัว)',
            'tx_used' => 'ธุรกรรมนี้ถูกใช้ชำระรายการอื่นไปแล้ว',
            'other_tx' => 'รายการนี้ผูกกับธุรกรรมอื่นอยู่แล้ว',
            'not_found' => 'ยังไม่พบธุรกรรมนี้บน TPIX Chain — ระบบจะตรวจซ้ำให้อัตโนมัติ',
            'not_mined' => 'ธุรกรรมนี้ยังไม่ถูกบันทึกลงบล็อก — ระบบจะตรวจซ้ำให้อัตโนมัติ',
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
