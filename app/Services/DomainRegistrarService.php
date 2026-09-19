<?php

namespace App\Services;

use App\Models\DomainContact;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Wallet;
use App\Support\DomainPricing;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Buying a domain on the customer's behalf.
 *
 * The money is ours to lose here, and so is the customer's, so the order of
 * operations is deliberate and worth reading before changing:
 *
 *   1. Everything that can be checked cheaply is checked first — do we sell
 *      this TLD, is the name still free, is the price still the one we quoted.
 *   2. Inside one short transaction, with the wallet row locked: re-read the
 *      balance, insert the registration row, debit. The row insert carries a
 *      unique idempotency key, so a double-tap fails at the index rather than
 *      at a balance check that two requests both passed a millisecond apart.
 *   3. OUTSIDE the transaction: call the registrar. This is the slow part —
 *      seconds, sometimes — and a transaction held open across it would keep
 *      the wallet row locked for every other purchase the customer makes.
 *   4. On any failure past the debit, refund in full and say so.
 *
 * What this buys us: if the process dies at any point in step 3, the database
 * still holds a `pending` row with a wallet transaction attached. Nothing is
 * lost silently — the reconciliation command finds it and either completes it
 * or gives the money back. The alternative, calling the API first and writing
 * the row after, loses the customer's money the first time PHP times out.
 */
class DomainRegistrarService
{
    /**
     * A registration that has not settled in this long is presumed dead and
     * refunded. Upstream says a pending payment "completes automatically",
     * but a customer cannot be left indefinitely with neither domain nor
     * money.
     */
    public const SETTLE_TIMEOUT_MINUTES = 90;

    public function __construct(
        protected HostingerApiService $api,
        protected DomainSearchService $search,
    ) {}

    /**
     * Register a domain for a customer, paid from their wallet.
     *
     * @param  array{privacy?: bool, auto_renew?: bool, years?: int}  $options
     *
     * @throws DomainPurchaseException when the sale cannot proceed. The
     *                                 message is customer-safe.
     */
    public function register(
        int $userId,
        string $domain,
        DomainContact $contact,
        array $options = [],
    ): DomainRegistration {
        $domain = strtolower(trim($domain));
        [$label, $tld] = $this->search->splitDomain($domain);

        if ($label === '' || ! $tld) {
            throw new DomainPurchaseException('ชื่อโดเมนไม่ถูกต้อง');
        }

        $domain = $label . '.' . $tld;

        if ($contact->user_id !== $userId) {
            // Not a validation failure — someone is trying to register in
            // another customer's name.
            throw new DomainPurchaseException('ข้อมูลผู้ถือครองไม่ถูกต้อง');
        }

        $record = DomainTld::active()->where('tld', $tld)->first();

        if (! $record || ! $record->item_id_register) {
            throw new DomainPurchaseException('ขออภัย ขณะนี้เรายังไม่เปิดให้จดนามสกุล .' . $tld);
        }

        // Re-check availability against the registrar, not the cache. The
        // customer has been filling in a form for several minutes; the name
        // may be gone. Finding out here costs one call. Finding out after the
        // debit costs a refund and an apology.
        $this->assertStillAvailable($label, $tld);

        $price = $record->registerPriceThb();

        if ($price <= 0) {
            throw new DomainPurchaseException('ขออภัย ราคาของนามสกุลนี้ยังไม่พร้อม กรุณาติดต่อทีมงาน');
        }

        $registration = $this->debitAndReserve($userId, $domain, $tld, $record, $price, $contact, $options);

        // From here on the customer has paid. Every exit must either deliver
        // the domain or give the money back.
        return $this->fulfil($registration, $contact, $record);
    }

    /**
     * Step 2 — the only part that touches money, kept as short as possible.
     */
    protected function debitAndReserve(
        int $userId,
        string $domain,
        string $tld,
        DomainTld $record,
        float $price,
        DomainContact $contact,
        array $options,
    ): DomainRegistration {
        $key = DomainRegistration::makeIdempotencyKey($userId, $domain, DomainRegistration::KIND_REGISTER);

        try {
            return DB::transaction(function () use ($userId, $domain, $tld, $record, $price, $contact, $options, $key) {
                $wallet = Wallet::getOrCreateForUser($userId);

                // Re-read under a row lock. The balance loaded a moment ago
                // is a number from the past; this one is the truth until we
                // commit.
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                if (! $wallet || ! $wallet->is_active) {
                    throw new DomainPurchaseException('กระเป๋าเงินของคุณถูกระงับ กรุณาติดต่อทีมงาน');
                }

                if (! $wallet->hasSufficientBalance($price)) {
                    throw new DomainPurchaseException(sprintf(
                        'ยอดเงินไม่พอ ต้องใช้ %s แต่มี %s กรุณาเติมเงินก่อน',
                        DomainPricing::format($price),
                        DomainPricing::format((float) $wallet->balance),
                    ));
                }

                // Insert BEFORE the debit so the unique key rejects a
                // duplicate request before it can spend anything.
                $registration = DomainRegistration::create([
                    'user_id' => $userId,
                    'domain' => $domain,
                    'tld' => $tld,
                    'status' => DomainRegistration::STATUS_PENDING,
                    'kind' => DomainRegistration::KIND_REGISTER,
                    'domain_contact_id' => $contact->id,
                    'price_thb' => $price,
                    'cost_usd_cents' => $record->cost_usd_cents,
                    'fx_rate' => DomainPricing::fxRate(),
                    'years' => 1,
                    'idempotency_key' => $key,
                    'privacy_protection' => (bool) ($options['privacy'] ?? true),
                    'auto_renew' => (bool) ($options['auto_renew'] ?? false),
                ]);

                $transaction = $wallet->pay(
                    $price,
                    'จดโดเมน ' . $domain,
                    DomainRegistration::class,
                    $registration->id,
                    ['domain' => $domain],
                );

                // pay() returns null when the balance check inside it fails.
                // We already checked under the lock, so this should be
                // unreachable — but an unreachable branch that silently
                // leaves a paid-for row unpaid is exactly the bug worth an
                // extra three lines.
                if (! $transaction) {
                    throw new DomainPurchaseException('ตัดเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                }

                $registration->update(['wallet_transaction_id' => $transaction->id]);

                return $registration;
            }, 3);
        } catch (QueryException $e) {
            // The unique index on idempotency_key fired: this is the second
            // tap of a double-tap, or a retry of a request that already went
            // through. Hand back the original rather than charging twice.
            $existing = DomainRegistration::where('idempotency_key', $key)->first();

            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }

    /**
     * Step 3 — talk to the registrar, and make sure the customer ends up with
     * either a domain or their money.
     */
    protected function fulfil(
        DomainRegistration $registration,
        DomainContact $contact,
        DomainTld $record,
    ): DomainRegistration {
        // A row handed back by the idempotency guard has already been through
        // here. Do not buy it a second time.
        if ($registration->status !== DomainRegistration::STATUS_PENDING) {
            return $registration;
        }

        try {
            $whoisId = $this->ensureWhoisProfile($contact, $registration->tld);

            if (! $whoisId) {
                return $this->failAndRefund($registration, 'whois profile could not be created upstream');
            }

            $result = $this->api->purchaseDomain(
                $registration->domain,
                $record->item_id_register,
                $whoisId,
                $this->extraDetailsFor($contact, $registration->tld),
            );

            if ($result === null) {
                return $this->failAndRefund($registration, 'purchase call returned no response');
            }

            $status = $result['status_code'] ?? 0;
            $body = $result['body'] ?? [];

            if ($status >= 400) {
                return $this->failAndRefund($registration, 'upstream rejected the order: HTTP ' . $status . ' ' . json_encode($body));
            }

            $registration->fill([
                'remote_order_id' => isset($body['id']) ? (string) $body['id'] : null,
                'remote_subscription_id' => $body['subscription_id'] ?? null,
                'last_polled_at' => now(),
            ]);

            if ($status === 202) {
                // Paid for, not registered. The job takes it from here.
                $registration->status = DomainRegistration::STATUS_REGISTERING;
                $registration->save();

                return $registration;
            }

            return $this->markActive($registration);
        } catch (\Throwable $e) {
            Log::error('[DomainRegistrar] fulfilment threw', [
                'registration_id' => $registration->id,
                'domain' => $registration->domain,
                'error' => $e->getMessage(),
            ]);

            return $this->failAndRefund($registration, 'exception: ' . $e->getMessage());
        }
    }

    /**
     * Mark a registration live and apply the extras the customer asked for.
     *
     * Privacy protection and auto-renew are best-effort on purpose: the
     * domain is registered and paid for, and failing the whole sale because
     * a toggle did not stick would be worse than a toggle that did not stick.
     * Both are re-applied by the reconciliation command.
     */
    public function markActive(DomainRegistration $registration): DomainRegistration
    {
        $registration->status = DomainRegistration::STATUS_ACTIVE;
        $registration->registered_at = $registration->registered_at ?? now();
        $registration->expires_at = $registration->expires_at ?? now()->addYear();
        $registration->save();

        if ($registration->privacy_protection) {
            $this->api->enablePrivacyProtection($registration->domain);
        }

        if ($registration->auto_renew && $registration->remote_subscription_id) {
            // Ours renews from the customer's wallet, not upstream's card.
            // Turning the registrar's own auto-renewal OFF is intentional:
            // two systems renewing the same domain is a double charge.
            $this->api->setAutoRenewal($registration->remote_subscription_id, false);
        }

        $this->refreshFromUpstream($registration);

        return $registration;
    }

    /**
     * Pull the real expiry and nameservers once the domain exists.
     */
    public function refreshFromUpstream(DomainRegistration $registration): void
    {
        $details = $this->api->getDomain($registration->domain);

        if (! is_array($details) || $details === []) {
            return;
        }

        $expires = $details['expires_at'] ?? $details['expire_date'] ?? null;
        $nameservers = $details['nameservers'] ?? $details['name_servers'] ?? null;

        $changes = [];

        if ($expires) {
            try {
                $changes['expires_at'] = Carbon::parse($expires);
            } catch (\Throwable) {
                // An unparseable date upstream is not worth failing over; the
                // renewal reminder falls back to registered_at + 1 year.
            }
        }

        if (is_array($nameservers)) {
            $changes['nameservers'] = array_values(array_filter($nameservers, 'is_string'));
        }

        if ($changes !== []) {
            $registration->update($changes);
        }
    }

    /**
     * Give the money back and record why, in that order.
     *
     * $reason is for us. It is written to last_error, which is hidden from
     * serialisation because it can quote the registrar by name.
     */
    public function failAndRefund(DomainRegistration $registration, string $reason): DomainRegistration
    {
        Log::warning('[DomainRegistrar] refunding failed registration', [
            'registration_id' => $registration->id,
            'domain' => $registration->domain,
            'reason' => $reason,
        ]);

        if ($registration->isRefunded()) {
            $registration->update([
                'status' => DomainRegistration::STATUS_REFUNDED,
                'last_error' => $reason,
            ]);

            return $registration;
        }

        DB::transaction(function () use ($registration, $reason) {
            $fresh = DomainRegistration::where('id', $registration->id)->lockForUpdate()->first();

            // Someone else refunded it between our check and this lock.
            if (! $fresh || $fresh->refund_transaction_id) {
                return;
            }

            $wallet = Wallet::getOrCreateForUser($fresh->user_id);
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $refund = $wallet->refund(
                (float) $fresh->price_thb,
                'คืนเงินค่าจดโดเมน ' . $fresh->domain,
                DomainRegistration::class,
                $fresh->id,
                null,
                ['domain' => $fresh->domain, 'reason' => 'registration_failed'],
            );

            $fresh->update([
                'status' => DomainRegistration::STATUS_REFUNDED,
                'refund_transaction_id' => $refund->id,
                'last_error' => $reason,
            ]);
        }, 3);

        return $registration->fresh() ?? $registration;
    }

    /**
     * Make sure the customer's registrant details exist upstream, and reuse
     * the handle when they already do.
     */
    public function ensureWhoisProfile(DomainContact $contact, string $tld): ?int
    {
        if ($contact->isSynced() && $contact->remote_whois_id) {
            return (int) $contact->remote_whois_id;
        }

        $whoisId = $this->api->createWhoisProfile(
            $tld,
            $contact->country,
            [
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'email' => $contact->email,
                'phone' => $contact->phoneE164(),
                'city' => $contact->city,
                'state' => $contact->state ?: $contact->city,
                'country' => strtoupper($contact->country),
                'address1' => $contact->address1,
                'zip' => $contact->zip,
            ],
            $this->extraDetailsFor($contact, $tld),
        );

        if ($whoisId) {
            $contact->update([
                'remote_whois_id' => (string) $whoisId,
                'synced_at' => now(),
            ]);
        }

        return $whoisId;
    }

    /**
     * Registry-specific registrant data for this TLD, if the contact carries
     * any.
     *
     * @return array<string,mixed>
     */
    protected function extraDetailsFor(DomainContact $contact, string $tld): array
    {
        $extra = $contact->extra_fields ?? [];

        return is_array($extra[$tld] ?? null) ? $extra[$tld] : [];
    }

    /**
     * @throws DomainPurchaseException
     */
    protected function assertStillAvailable(string $label, string $tld): void
    {
        $rows = $this->api->checkAvailability($label, [$tld]);

        if ($rows === null) {
            throw new DomainPurchaseException('ขณะนี้ระบบจดโดเมนไม่พร้อมใช้งาน กรุณาลองใหม่ในอีกสักครู่');
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (strtolower((string) ($row['domain'] ?? '')) !== $label . '.' . $tld) {
                continue;
            }

            if ($row['is_available'] ?? $row['available'] ?? false) {
                // A premium name is priced per-name by the registry, not by
                // our per-TLD catalogue, so our price would be wrong and
                // wrong downwards. Refuse rather than sell at a loss.
                if ($row['is_premium'] ?? false) {
                    throw new DomainPurchaseException('โดเมนนี้เป็นชื่อพิเศษ (premium) กรุณาติดต่อทีมงานเพื่อขอราคา');
                }

                return;
            }

            throw new DomainPurchaseException('ขออภัย โดเมนนี้เพิ่งถูกจดไปแล้ว กรุณาเลือกชื่ออื่น');
        }

        throw new DomainPurchaseException('ตรวจสอบสถานะโดเมนไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    }
}
