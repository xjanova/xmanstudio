<?php

namespace App\Services;

use App\Models\DomainContact;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Wallet;
use App\Support\Alerts\BusinessAlerts;
use App\Support\DomainPricing;
use App\Support\UpstreamBilling;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
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

    /**
     * The same, for an order upstream acknowledged with a 202 ("payment still
     * processing"): the domain only turns up once that charge clears.
     */
    public const ACCEPTED_TIMEOUT_MINUTES = 1440;

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

        // Our card at the registrar was just refused. Every order would fail
        // the same way — say so before taking the money, not after.
        if (UpstreamBilling::isPaused()) {
            throw new DomainPurchaseException(UpstreamBilling::customerMessage());
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

        [$registration, $created] = $this->debitAndReserve($userId, $domain, $tld, $record, $price, $contact, $options);

        // A second submit of the same order. The request that created the row
        // owns the purchase: buying again from here would either register the
        // domain twice or — worse — get "not available" back (the first
        // request just took it) and refund a customer whose domain is live.
        if (! $created) {
            return $registration;
        }

        // From here on the customer has paid. Every exit must either deliver
        // the domain or give the money back.
        return $this->fulfil($registration, $contact, $record);
    }

    /**
     * Step 2 — the only part that touches money, kept as short as possible.
     *
     * @return array{0:DomainRegistration,1:bool} the row, and whether THIS call created it
     */
    protected function debitAndReserve(
        int $userId,
        string $domain,
        string $tld,
        DomainTld $record,
        float $price,
        DomainContact $contact,
        array $options,
    ): array {
        $key = DomainRegistration::makeIdempotencyKey(
            $userId,
            $domain,
            DomainRegistration::KIND_REGISTER,
            // A refunded attempt must not block a fresh one: the key is per
            // day, and without this a customer refused while our card was
            // down could not buy the name again until tomorrow.
            DomainRegistration::refundedAttemptsToday($userId, $domain, DomainRegistration::KIND_REGISTER),
        );

        try {
            return [DB::transaction(function () use ($userId, $domain, $tld, $record, $price, $contact, $options, $key) {
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
                    // Which money that figure is in, so the margin report does
                    // not convert a baht cost as though it were dollars.
                    'cost_currency' => $record->costCurrency(),
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
            }, 3), true];
        } catch (QueryException $e) {
            // The unique index on idempotency_key fired: this is the second
            // tap of a double-tap, or a retry of a request that already went
            // through. Hand back the original rather than charging twice.
            $existing = DomainRegistration::where('idempotency_key', $key)->first();

            if ($existing) {
                return [$existing, false];
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

        // Set the moment the order may have reached the registrar. From there
        // on, a crash in our own code is not a reason to refund: upstream may
        // have registered the domain before we fell over.
        $purchaseSent = false;

        try {
            $whoisId = $this->ensureWhoisProfile($contact, $registration->tld);

            if (! $whoisId) {
                return $this->failAndRefund($registration, 'whois profile could not be created upstream');
            }

            $purchaseSent = true;
            $result = $this->api->purchaseDomain(
                $registration->domain,
                $record->item_id_register,
                $whoisId,
                $this->extraDetailsFor($contact, $registration->tld),
            );

            $status = (int) ($result['status_code'] ?? 0);
            $body = $result['body'] ?? [];

            if ($this->api->lastOutcomeUnknown()) {
                // Timed out in transit, or upstream fell over mid-order: the
                // registrar may well have registered it with our card.
                // Refunding on a guess hands out a free domain; the
                // reconciliation job looks it up in the portfolio and settles
                // it either way.
                UpstreamBilling::pauseIfPaymentTrouble($status, is_array($body) ? $body : [], 'domain purchase');

                $registration->update([
                    'status' => DomainRegistration::STATUS_REGISTERING,
                    'last_error' => 'purchase outcome unknown: ' . ($result === null
                        ? 'the request timed out in transit'
                        : 'HTTP ' . $status . ' ' . mb_substr((string) json_encode($body), 0, 300)),
                    'last_polled_at' => now(),
                ]);

                return $registration;
            }

            if ($result === null) {
                return $this->failAndRefund($registration, 'purchase call did not go out (' . ($this->api->lastFailure() ?? 'unknown') . ')');
            }

            if ($status >= 400) {
                return $this->failAndRefund(
                    $registration,
                    'upstream rejected the order: HTTP ' . $status . ' ' . json_encode($body),
                    self::looksLikePaymentProblem($status, $body),
                );
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

            if ($purchaseSent) {
                $fresh = $registration->fresh() ?? $registration;

                // Only a row still waiting on the answer: one already refunded
                // or activated before the crash keeps what it is.
                if ($fresh->status === DomainRegistration::STATUS_PENDING) {
                    $fresh->update([
                        'status' => DomainRegistration::STATUS_REGISTERING,
                        'last_error' => 'purchase outcome unknown: we crashed after sending it: ' . mb_substr($e->getMessage(), 0, 300),
                        'last_polled_at' => now(),
                    ]);
                }

                return $fresh;
            }

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
    /**
     * Is this refusal about money on our side?
     *
     * The distinction matters because the two need opposite responses. A
     * domain that is taken, or a name the registry will not accept, is one
     * order and the customer should try another name. A card that was
     * declined, or an account with no credit, refuses EVERY order until
     * somebody fixes it — and nobody finds out unless we say so, because the
     * customer just sees a refund and walks away.
     *
     * Matched on the status code first (402 is unambiguous) and then on the
     * words upstream uses, because the API does not carry a machine-readable
     * reason. Over-matching is the safe direction: a false alarm costs one
     * message, a miss costs every sale until someone notices.
     *
     * @param  array<mixed>  $body
     */
    public static function looksLikePaymentProblem(int $status, array $body): bool
    {
        if ($status === 402) {
            return true;
        }

        $haystack = strtolower(json_encode($body) ?: '');

        foreach (['payment', 'insufficient', 'balance', 'funds', 'card', 'billing', 'charge failed', 'declined'] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function markActive(DomainRegistration $registration): DomainRegistration
    {
        $registration->status = DomainRegistration::STATUS_ACTIVE;
        $registration->registered_at = $registration->registered_at ?? now();
        $registration->expires_at = $registration->expires_at ?? now()->addYear();
        $registration->save();

        if ($registration->privacy_protection) {
            $this->api->enablePrivacyProtection($registration->domain);
        }

        if (! $registration->remote_subscription_id) {
            // A 202 purchase does not always say which subscription it made,
            // and without one the domain can never be renewed from the wallet.
            $this->linkSubscription($registration);
        }

        if ($registration->remote_subscription_id) {
            // Upstream's own auto-renewal goes OFF for every domain we sell —
            // not only the ones whose owner ticked auto-renew. Left on, it
            // renews at expiry with OUR card: for a customer who chose to let
            // the domain go, we pay for a year nobody paid us for; for one who
            // renews with us, we pay twice.
            $this->api->setAutoRenewal($registration->remote_subscription_id, false);
        }

        $this->refreshFromUpstream($registration);

        return $registration;
    }

    /**
     * Find this domain's billing subscription upstream, when the purchase did
     * not tell us.
     *
     * The subscription list names each entry but carries no domain field, so
     * the match is on the domain appearing in the name — and only a single
     * unclaimed match counts. Two candidates is a question for a person, not a
     * guess: linking the wrong subscription would renew somebody else's domain.
     */
    public function linkSubscription(DomainRegistration $registration): ?string
    {
        $subscriptions = $this->api->getSubscriptions();

        if (! is_array($subscriptions)) {
            return null;
        }

        $taken = DomainRegistration::whereNotNull('remote_subscription_id')
            ->where('id', '!=', $registration->id)
            ->pluck('remote_subscription_id')
            ->all();

        // The name has to stand on its own: "ample.com" must not claim the
        // subscription of "example.com", nor "example.com" that of
        // "example.com.au".
        $pattern = '/(?<![a-z0-9.-])' . preg_quote(strtolower($registration->domain), '/') . '(?![a-z0-9.-])/';

        $matches = array_values(array_filter($subscriptions, fn ($row) => is_array($row)
            && ! empty($row['id'])
            && ! in_array((string) $row['id'], $taken, true)
            && preg_match($pattern, strtolower((string) ($row['name'] ?? ''))) === 1));

        if (count($matches) !== 1) {
            return null;
        }

        $id = (string) $matches[0]['id'];
        $registration->update(['remote_subscription_id' => $id]);

        return $id;
    }

    /**
     * Move an order the registrar accepted (or might have) towards "active".
     *
     * The registrar's answer to a purchase is not the end of it: a 202 means
     * payment was still clearing, and once it clears the domain lands in our
     * portfolio as `pending_setup` — PAID FOR, and registered to nobody,
     * until somebody calls setup. The reconciliation job used to wait for
     * "active", never saw it, and refunded the customer at the timeout while
     * the domain sat bought in our account.
     *
     * Only 'missing' and 'gone' can lead to a refund, and 'missing' means the
     * portfolio list was read and the name is not in it — never that a lookup
     * failed. A timeout, our own limiter or a 5xx says nothing about whether
     * the domain was bought; refunding on it gives the domain away.
     *
     * @return 'active'|'setup-sent'|'setup-failed'|'waiting'|'missing'|'gone'|'unknown'|'ambiguous'
     */
    public function settle(DomainRegistration $registration): string
    {
        // The portfolio holds a name once. With another order for it live or
        // still settling, "it is there" cannot say whose it is — activating
        // this one could hand one customer's domain to another.
        if ($this->rivalOrderFor($registration)) {
            return 'ambiguous';
        }

        $details = $this->api->getDomain($registration->domain);

        if (! is_array($details) || $details === []) {
            return match ($this->inPortfolio($registration->domain)) {
                false => 'missing',
                // Listed, but its details would not load: it exists, so it
                // was bought. Try again next run.
                true => 'waiting',
                null => 'unknown',
            };
        }

        $status = strtolower((string) ($details['status'] ?? ''));

        if ($status === 'active') {
            $this->markActive($registration);

            return 'active';
        }

        if (in_array($status, ['pending_setup', 'failed'], true)) {
            // The registrar can go on reporting pending_setup for a while
            // after accepting a setup; sending it again only earns a refusal
            // that reads like a problem.
            if (Cache::has('domain:setup-sent:' . $registration->id)) {
                return 'waiting';
            }

            return $this->completeSetup($registration) ? 'setup-sent' : 'setup-failed';
        }

        if (in_array($status, ['deleted', 'expired'], true)) {
            return 'gone';
        }

        // requested, pending_verification, suspended: the registry is working
        // on it, or waiting on the registrant's e-mail confirmation.
        return 'waiting';
    }

    /**
     * Is the name in our portfolio? Null when the list could not be read.
     */
    public function inPortfolio(string $domain): ?bool
    {
        $list = $this->api->listDomains();

        if (! is_array($list)) {
            return null;
        }

        foreach ($list as $row) {
            if (is_array($row) && strcasecmp((string) ($row['domain'] ?? ''), $domain) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Another registration of the same name that is live or still settling.
     */
    public function rivalOrderFor(DomainRegistration $registration): ?DomainRegistration
    {
        return DomainRegistration::registrations()
            ->where('id', '!=', $registration->id)
            ->where('domain', strtolower($registration->domain))
            ->whereIn('status', [
                DomainRegistration::STATUS_ACTIVE,
                DomainRegistration::STATUS_PENDING,
                DomainRegistration::STATUS_REGISTERING,
            ])
            ->first();
    }

    /**
     * How long an unsettled order may stay unfound before it is refunded.
     *
     * An order upstream acknowledged (it gave an order id — a 202 whose
     * payment is still clearing) gets a day: the domain only appears once the
     * charge settles, and refunding in the meantime gives it away if it then
     * does. An order whose answer we never heard gets the short window.
     */
    public static function settleWindowMinutes(DomainRegistration $row): int
    {
        return $row->remote_order_id ? self::ACCEPTED_TIMEOUT_MINUTES : self::SETTLE_TIMEOUT_MINUTES;
    }

    /**
     * Register a domain that is paid for but was never registered.
     *
     * Uses the customer's own registrant profile — the whole point of the
     * shop is that the domain is theirs — and never places a new order.
     */
    public function completeSetup(DomainRegistration $registration): bool
    {
        $contact = $registration->contact;

        if (! $contact) {
            $registration->update(['last_error' => 'setup impossible: the registrant record is missing']);

            return false;
        }

        $whoisId = $this->ensureWhoisProfile($contact, $registration->tld);

        if (! $whoisId) {
            $registration->update(['last_error' => 'setup impossible: whois profile could not be created upstream']);

            return false;
        }

        $result = $this->api->completeDomainSetup(
            $registration->domain,
            $whoisId,
            $this->extraDetailsFor($contact, $registration->tld),
        );

        if ($result !== null && (int) $result['status_code'] < 300) {
            $registration->update([
                'status' => DomainRegistration::STATUS_REGISTERING,
                'last_error' => null,
                'last_polled_at' => now(),
            ]);

            try {
                Cache::put('domain:setup-sent:' . $registration->id, now()->toIso8601String(), now()->addMinutes(20));
            } catch (\Throwable) {
                // Without the marker the worst case is one repeated setup call.
            }

            return true;
        }

        $registration->update([
            'last_error' => 'setup rejected: ' . mb_substr((string) json_encode($result['body'] ?? $this->api->lastFailure()), 0, 500),
        ]);

        return false;
    }

    /**
     * Buy the domain another year.
     *
     * The customer page has promised since day one that we charge the wallet
     * before expiry and warn first; nothing did either. This is the half that
     * moves money, and it is deliberately the same shape as register(): take
     * the money inside a lock with a row already written, then talk upstream,
     * then either extend the domain or give the money back. A renewal that
     * charges and does not renew is worse than one that never ran.
     *
     * The charge is its own row (kind = renew) so the customer sees what they
     * paid and when, and so a failed attempt can be refunded independently of
     * the registration that came before it.
     */
    public function renew(DomainRegistration $domain): DomainRegistration
    {
        if ($domain->kind !== DomainRegistration::KIND_REGISTER) {
            throw new DomainPurchaseException('ต่ออายุได้จากรายการโดเมน ไม่ใช่จากรายการชำระเงิน');
        }

        // An expired domain is still renewable during the registry's grace
        // period — that is exactly when a customer who missed the e-mails
        // comes looking. If it is past saving, the registrar says no and the
        // money goes straight back.
        if (! in_array($domain->status, [DomainRegistration::STATUS_ACTIVE, DomainRegistration::STATUS_EXPIRED], true)) {
            throw new DomainPurchaseException('โดเมนนี้ยังใช้งานไม่ได้ จึงยังต่ออายุไม่ได้');
        }

        // Past the grace period a registry charges a restore fee on top — to
        // our card, while the customer paid the ordinary renewal price. That
        // one is a conversation with the team, not a button.
        if (! $domain->withinRenewalGrace()) {
            throw new DomainPurchaseException(sprintf(
                'โดเมนนี้หมดอายุเกิน %d วันแล้ว ต่ออายุด้วยตัวเองไม่ได้ — กรุณาติดต่อทีมงานเพื่อกู้คืน (อาจมีค่าธรรมเนียมเพิ่ม)',
                DomainRegistration::RENEW_GRACE_DAYS,
            ));
        }

        if (! $domain->remote_subscription_id) {
            // Nothing to renew upstream. Taking the money anyway would leave us
            // holding it with no way to deliver.
            throw new DomainPurchaseException('โดเมนนี้ยังเชื่อมกับผู้ให้บริการไม่สมบูรณ์ กรุณาติดต่อทีมงาน');
        }

        $record = DomainTld::where('tld', $domain->tld)->first();
        $price = $record?->renewPriceThb() ?? 0.0;

        if (! $record || $price <= 0) {
            throw new DomainPurchaseException('ขออภัย ราคาต่ออายุของนามสกุลนี้ยังไม่พร้อม กรุณาติดต่อทีมงาน');
        }

        if (UpstreamBilling::isPaused()) {
            throw new DomainPurchaseException(UpstreamBilling::customerMessage());
        }

        [$renewal, $created] = $this->debitForRenewal($domain, $record, $price);

        // Handed back by the idempotency guard: another request owns this
        // renewal — finished, or still talking to the registrar. Calling
        // upstream from here too would renew twice, or refund a renewal that
        // is about to succeed.
        if (! $created) {
            return $renewal;
        }

        return $this->fulfilRenewal($domain, $renewal);
    }

    /**
     * The money half. Same lock, same order, same idempotency key shape as a
     * registration — a renewal is a purchase and gets the same protections.
     */
    /**
     * @return array{0:DomainRegistration,1:bool} the renewal row, and whether THIS call created it
     */
    protected function debitForRenewal(
        DomainRegistration $domain,
        DomainTld $record,
        float $price,
    ): array {
        $key = DomainRegistration::makeIdempotencyKey(
            $domain->user_id,
            $domain->domain,
            DomainRegistration::KIND_RENEW,
            DomainRegistration::refundedAttemptsToday($domain->user_id, $domain->domain, DomainRegistration::KIND_RENEW),
        );

        try {
            return [DB::transaction(function () use ($domain, $record, $price, $key) {
                $wallet = Wallet::getOrCreateForUser($domain->user_id);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                if (! $wallet || ! $wallet->is_active) {
                    throw new DomainPurchaseException('กระเป๋าเงินของคุณถูกระงับ กรุณาติดต่อทีมงาน');
                }

                if (! $wallet->hasSufficientBalance($price)) {
                    throw new DomainPurchaseException(sprintf(
                        'ยอดเงินไม่พอต่ออายุ %s ต้องใช้ %s แต่มี %s',
                        $domain->domain,
                        DomainPricing::format($price),
                        DomainPricing::format((float) $wallet->balance),
                    ));
                }

                $renewal = DomainRegistration::create([
                    'user_id' => $domain->user_id,
                    'domain' => $domain->domain,
                    'tld' => $domain->tld,
                    'status' => DomainRegistration::STATUS_PENDING,
                    'kind' => DomainRegistration::KIND_RENEW,
                    'renewal_of' => $domain->id,
                    'domain_contact_id' => $domain->domain_contact_id,
                    'remote_subscription_id' => $domain->remote_subscription_id,
                    'price_thb' => $price,
                    'cost_usd_cents' => $record->renew_cost_usd_cents ?: $record->cost_usd_cents,
                    'cost_currency' => $record->costCurrency(),
                    'fx_rate' => DomainPricing::fxRate(),
                    'years' => 1,
                    'idempotency_key' => $key,
                    // Cast: a parent row whose flag was never loaded would
                    // otherwise write NULL into a NOT NULL column and kill a
                    // renewal the customer has already been charged for.
                    'privacy_protection' => (bool) $domain->privacy_protection,
                    'auto_renew' => false,
                    // What "the date moved" is measured against if the answer
                    // never comes back (see settleRenewal).
                    'previous_expires_at' => $domain->expires_at,
                ]);

                $transaction = $wallet->pay(
                    $price,
                    'ต่ออายุโดเมน ' . $domain->domain,
                    DomainRegistration::class,
                    $renewal->id,
                    ['domain' => $domain->domain, 'kind' => 'renew'],
                );

                if (! $transaction) {
                    throw new DomainPurchaseException('ตัดเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                }

                $renewal->update(['wallet_transaction_id' => $transaction->id]);

                return $renewal;
            }, 3), true];
        } catch (QueryException $e) {
            $existing = DomainRegistration::where('idempotency_key', $key)->first();

            if ($existing) {
                return [$existing, false];
            }

            throw $e;
        }
    }

    /**
     * Tell the registrar, then move the expiry date.
     *
     * The date comes from upstream when they give one — our +1 year is a
     * fallback, and a guess about somebody else's billing period is the kind
     * of thing that quietly drifts a day per renewal.
     */
    protected function fulfilRenewal(
        DomainRegistration $domain,
        DomainRegistration $renewal,
    ): DomainRegistration {
        try {
            $result = $this->api->renewSubscription($domain->remote_subscription_id);

            $status = (int) ($result['status_code'] ?? 0);
            $body = (array) ($result['body'] ?? []);

            if ($this->api->lastOutcomeUnknown()) {
                // May have gone through. The reconciliation job compares the
                // registrar's expiry date with the one this was paid against.
                UpstreamBilling::pauseIfPaymentTrouble($status, $body, 'domain renewal');

                $renewal->update([
                    'last_error' => 'renewal outcome unknown: ' . ($result === null
                        ? 'the request timed out in transit'
                        : 'HTTP ' . $status . ' ' . mb_substr((string) json_encode($body), 0, 300)),
                    'last_polled_at' => now(),
                ]);

                return $renewal;
            }

            if ($result === null) {
                return $this->failAndRefundRenewal($renewal, 'renew call did not go out (' . ($this->api->lastFailure() ?? 'unknown') . ')');
            }

            if ($status >= 400) {
                return $this->failAndRefundRenewal(
                    $renewal,
                    'upstream rejected the renewal: HTTP ' . $status . ' ' . json_encode($body),
                    self::looksLikePaymentProblem($status, $body),
                );
            }

            if ($status === 202) {
                // Accepted, payment still clearing: NOT renewed yet. Moving
                // the date now would promise a year that a declined charge
                // later takes back. The reconciliation job confirms it when
                // the registrar's date moves.
                $renewal->update([
                    'remote_order_id' => isset($body['id']) ? (string) $body['id'] : null,
                    'last_error' => 'renewal accepted, payment still clearing (HTTP 202)',
                    'last_polled_at' => now(),
                ]);

                return $renewal;
            }

            $renewal->fill([
                'status' => DomainRegistration::STATUS_ACTIVE,
                'registered_at' => now(),
                'remote_order_id' => isset($result['body']['id']) ? (string) $result['body']['id'] : null,
            ]);
            $renewal->save();

            $domain->forceFill([
                'expires_at' => ($domain->expires_at ?? now())->copy()->addYear(),
                // A domain renewed in its grace period is live again.
                'status' => DomainRegistration::STATUS_ACTIVE,
                // Arm next year's warning, and next year's reminders. Leaving
                // the milestone list behind would mean a domain renewed once
                // is never reminded about again.
                'renewal_notice_sent_at' => null,
                'expiry_reminders_sent' => null,
                'last_error' => null,
            ])->save();

            // Ask upstream what the date really is; ours is only a guess until
            // they answer.
            $this->refreshFromUpstream($domain);

            $renewal->update(['expires_at' => $domain->fresh()?->expires_at]);

            return $renewal;
        } catch (\Throwable $e) {
            Log::error('[DomainRegistrar] renewal threw', [
                'registration_id' => $renewal->id,
                'domain' => $renewal->domain,
                'error' => $e->getMessage(),
            ]);

            // The API client never throws, so this came from our own code
            // AFTER the renewal was sent — upstream may have renewed before we
            // fell over. Settled by the reconciliation job, not refunded here.
            $renewal->update([
                'last_error' => 'renewal outcome unknown: we crashed after sending it: ' . mb_substr($e->getMessage(), 0, 300),
                'last_polled_at' => now(),
            ]);

            return $renewal;
        }
    }

    /**
     * Give back what we took for a renewal that did not happen.
     *
     * Separate from failAndRefund() only for the wording: a customer reading
     * their wallet history needs to see which of the two charges came back.
     */
    /**
     * @param  bool  $paymentProblem  our card at the registrar, not this domain
     */
    public function failAndRefundRenewal(DomainRegistration $renewal, string $reason, bool $paymentProblem = false): DomainRegistration
    {
        Log::warning('[DomainRegistrar] refunding failed renewal', [
            'registration_id' => $renewal->id,
            'domain' => $renewal->domain,
            'reason' => $reason,
        ]);

        if ($renewal->isRefunded()) {
            $renewal->update(['status' => DomainRegistration::STATUS_REFUNDED, 'last_error' => $reason]);

            return $renewal;
        }

        $alert = fn () => BusinessAlerts::domainPurchaseRefused($renewal->fresh() ?? $renewal, $reason, $paymentProblem);

        DB::transaction(function () use ($renewal, $reason) {
            $fresh = DomainRegistration::where('id', $renewal->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->refund_transaction_id) {
                return;
            }

            $wallet = Wallet::getOrCreateForUser($fresh->user_id);
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $refund = $wallet->refund(
                (float) $fresh->price_thb,
                'คืนเงินค่าต่ออายุโดเมน ' . $fresh->domain,
                DomainRegistration::class,
                $fresh->id,
                null,
                ['domain' => $fresh->domain, 'reason' => 'renewal_failed'],
            );

            $fresh->update([
                'status' => DomainRegistration::STATUS_REFUNDED,
                'refund_transaction_id' => $refund->id,
                'last_error' => $reason,
            ]);
        }, 3);

        // After the transaction: a pause or an alert that throws must not
        // undo a refund.
        if ($paymentProblem) {
            UpstreamBilling::pause('domain renewal refused: ' . $reason);
        }

        $alert();

        return $renewal->fresh() ?? $renewal;
    }

    /**
     * A renewal whose answer never came back (or came back "payment still
     * clearing"): did the registrar's expiry date move? Called by the
     * reconciliation job.
     *
     * 'unknown' is "could not ask" — never a reason to refund.
     *
     * @return 'confirmed'|'refunded'|'waiting'|'unknown'|'skip'
     */
    public function settleRenewal(DomainRegistration $renewal): string
    {
        $domain = $renewal->renewalOf;

        if (! $domain || $renewal->kind !== DomainRegistration::KIND_RENEW || $renewal->status !== DomainRegistration::STATUS_PENDING) {
            return 'skip';
        }

        // Measured against the date this renewal was paid against, not the
        // domain's current one: the daily sync copies the registrar's date
        // over as soon as the renewal lands, and a date is never later than
        // itself — which used to refund renewals that had gone through.
        $paidAgainst = $renewal->previous_expires_at ?? $domain->expires_at;

        $details = $this->api->getDomain($domain->domain);

        if (! is_array($details) || $details === []) {
            return 'unknown';
        }

        $expires = null;

        try {
            $expires = isset($details['expires_at']) ? Carbon::parse($details['expires_at']) : null;
        } catch (\Throwable) {
        }

        // Moved past the date it was paid against by more than a day: it went through.
        if ($expires && $paidAgainst && $expires->gt($paidAgainst->copy()->addDay())) {
            $renewal->update([
                'status' => DomainRegistration::STATUS_ACTIVE,
                'registered_at' => now(),
                'expires_at' => $expires,
                'last_error' => null,
            ]);

            $domain->forceFill([
                'expires_at' => $expires,
                'status' => DomainRegistration::STATUS_ACTIVE,
                'renewal_notice_sent_at' => null,
                'expiry_reminders_sent' => null,
                'last_error' => null,
            ])->save();

            return 'confirmed';
        }

        if ($renewal->created_at->diffInMinutes(now()) >= self::settleWindowMinutes($renewal)) {
            $this->failAndRefundRenewal($renewal, 'renewal outcome unknown and the expiry date never moved');

            return 'refunded';
        }

        return 'waiting';
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
    /**
     * @param  bool  $paymentProblem  the refusal was about OUR ability to pay
     *                                the registrar, not about this domain
     */
    public function failAndRefund(DomainRegistration $registration, string $reason, bool $paymentProblem = false): DomainRegistration
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

        $registration = $registration->fresh() ?? $registration;

        // Our card was refused: every order after this one would be too.
        // Stop taking money for a while instead of debiting and refunding
        // customer after customer (see UpstreamBilling).
        if ($paymentProblem) {
            UpstreamBilling::pause('domain purchase refused: ' . $reason);
        }

        // Outside the transaction: an alert that fails must not roll back a
        // refund that succeeded. Raised here rather than at the call sites so
        // every path that refunds a customer is heard about — the reason this
        // was invisible was that it only ever wrote to a log.
        BusinessAlerts::domainPurchaseRefused($registration, $reason, $paymentProblem);

        return $registration;
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
