<?php

namespace App\Services;

use App\Mail\VpsReadyMail;
use App\Models\User;
use App\Models\VpsInstance;
use App\Models\VpsPayment;
use App\Models\VpsPlan;
use App\Models\Wallet;
use App\Support\Alerts\BusinessAlerts;
use App\Support\UpstreamBilling;
use App\Support\VpsCatalog;
use App\Support\VpsPricing;
use App\Support\VpsSettings;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Renting a server out: taking the customer's money, buying the machine
 * upstream with ours, and keeping the two in step for as long as it runs.
 *
 * Same order of operations as DomainRegistrarService, for the same reasons:
 *
 *   1. Everything cheap is checked first — plan, period, OS, location, and
 *      whether we can currently pay our supplier at all.
 *   2. One short transaction with the wallet row locked: re-read the balance,
 *      write the instance and its payment, debit. The payment's unique key is
 *      what a double-tap loses on.
 *   3. OUTSIDE the transaction, the purchase. Only the request that created
 *      the row makes it — a duplicate submit gets the row back and stops.
 *   4. The customer ends up with a server or with their money. The one
 *      exception is deliberate: once the supplier has taken OUR money, a
 *      machine that will not build is not refunded automatically. The admin
 *      is told, because the fix is usually "finish the install", not "give
 *      the money back and eat the cost".
 *
 * A purchase that timed out in transit may still have gone through. That one
 * is neither refunded nor retried on the spot: the reconciliation job looks
 * for the machine and decides.
 */
class VpsProvisioningService
{
    /** A pending row that never reached the supplier is refunded after this. */
    public const PENDING_TIMEOUT_MINUTES = 30;

    /** Leave a pending row alone this long: its own request may still be talking to the supplier. */
    public const PENDING_GRACE_MINUTES = 5;

    /** A purchase whose answer we never heard is looked for upstream this long before we give up and refund. */
    public const UNKNOWN_OUTCOME_MINUTES = 45;

    /** A machine still not running after this long is a person's problem. */
    public const ATTENTION_AFTER_MINUTES = 120;

    public const MAX_SETUP_ATTEMPTS = 3;

    /** How long an accepted setup is waited for before it may be sent again. */
    public const SETUP_WAIT_MINUTES = 20;

    /** An order upstream acknowledged with a 202 — payment still clearing — is waited for this long. */
    public const ACCEPTED_ORDER_MINUTES = 1440;

    /** The same, for a renewal. */
    public const ACCEPTED_RENEWAL_MINUTES = 1440;

    /** How long a chosen root password may wait, encrypted in the cache, for a delayed install. */
    public const SECRET_TTL_HOURS = 24;

    public function __construct(
        protected HostingerApiService $api,
        protected VpsCatalog $catalog,
    ) {}

    // ================================================================ ordering

    /**
     * Rent a server, paid from the wallet.
     *
     * @param  array{template_id:int,data_center_id:int,hostname:string,password:string,public_key?:?string,auto_renew?:bool}  $options
     * @param  string  $orderToken  printed into the form; one token = one order
     *
     * @throws VpsOrderException with a customer-safe message
     */
    public function order(User $user, VpsPlan $plan, string $period, array $options, string $orderToken): VpsInstance
    {
        if (! VpsSettings::salesEnabled()) {
            throw new VpsOrderException('ขณะนี้ยังไม่เปิดรับคำสั่งเช่า VPS กรุณาติดต่อทีมงาน');
        }

        // Before any money moves. See UpstreamBilling for why this matters.
        if (UpstreamBilling::isPaused()) {
            throw new VpsOrderException(UpstreamBilling::customerMessage());
        }

        if (! $plan->isSellable()) {
            throw new VpsOrderException('แพ็กเกจนี้ยังไม่เปิดให้เช่า');
        }

        $price = $plan->price($period);
        $amount = $plan->firstPriceThb($period);

        if (! $price || $amount <= 0) {
            throw new VpsOrderException('แพ็กเกจนี้ไม่มีรอบบิลที่เลือก กรุณาเลือกใหม่');
        }

        // The form carries the figure the customer was shown. If the nightly
        // catalogue sync or the admin moved the price while the page sat open,
        // say so instead of charging a number they never saw.
        if (isset($options['expected_amount']) && abs((float) $options['expected_amount'] - $amount) > 0.009) {
            throw new VpsOrderException(sprintf(
                'ราคาแพ็กเกจนี้เพิ่งปรับเป็น %s กรุณาตรวจสอบแล้วกดยืนยันอีกครั้ง',
                VpsPricing::format($amount),
            ));
        }

        $template = $this->catalog->template((int) $options['template_id']);

        if (! $template) {
            throw new VpsOrderException('ไม่พบระบบปฏิบัติการที่เลือก กรุณาเลือกใหม่');
        }

        $dc = $this->catalog->dataCenter((int) $options['data_center_id']);

        if (! $dc) {
            throw new VpsOrderException('ไม่พบศูนย์ข้อมูลที่เลือก กรุณาเลือกใหม่');
        }

        [$instance, $payment, $created] = $this->debitAndReserve($user, $plan, $period, $price, $amount, $template, $dc, $options, $orderToken);

        // A second submit of the same form. The first request owns the
        // purchase — calling upstream again from here would buy a second
        // machine, or refund the first one on the "already exists" error.
        if (! $created) {
            return $instance;
        }

        return $this->fulfil($instance, $payment, $options);
    }

    /**
     * The only part that touches money, as short as it can be.
     *
     * @return array{0:VpsInstance,1:VpsPayment,2:bool} instance, its order payment, and whether THIS call created them
     */
    protected function debitAndReserve(
        User $user,
        VpsPlan $plan,
        string $period,
        array $price,
        float $amount,
        array $template,
        array $dc,
        array $options,
        string $orderToken,
    ): array {
        $key = substr(hash('sha256', 'vps-order|' . $user->id . '|' . $orderToken), 0, 64);

        try {
            return DB::transaction(function () use ($user, $plan, $period, $price, $amount, $template, $dc, $options, $key) {
                $wallet = Wallet::getOrCreateForUser($user->id);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                if (! $wallet || ! $wallet->is_active) {
                    throw new VpsOrderException('กระเป๋าเงินของคุณถูกระงับ กรุณาติดต่อทีมงาน');
                }

                if (! $wallet->hasSufficientBalance($amount)) {
                    throw new VpsOrderException(sprintf(
                        'ยอดเงินไม่พอ ต้องใช้ %s แต่มี %s กรุณาเติมเงินก่อน',
                        VpsPricing::format($amount),
                        VpsPricing::format((float) $wallet->balance),
                    ));
                }

                $instance = VpsInstance::create([
                    'user_id' => $user->id,
                    'vps_plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'specs' => $plan->specSnapshot(),
                    'period' => $period,
                    'months' => $price['months'],
                    'status' => VpsInstance::STATUS_PENDING,
                    'hostname' => strtolower(trim($options['hostname'])),
                    'template_id' => $template['id'],
                    'template_name' => $template['name'],
                    'data_center_id' => $dc['id'],
                    'data_center_name' => VpsCatalog::describeDataCenter($dc),
                    'auto_renew' => (bool) ($options['auto_renew'] ?? true),
                ]);

                // Written before the debit: the unique key rejects a duplicate
                // before it can spend anything.
                $payment = VpsPayment::create([
                    'vps_instance_id' => $instance->id,
                    'user_id' => $user->id,
                    'kind' => VpsPayment::KIND_ORDER,
                    'status' => VpsPayment::STATUS_PENDING,
                    'amount_thb' => $amount,
                    'cost_cents' => $price['first'],
                    'cost_currency' => $price['currency'],
                    'months' => $price['months'],
                    'item_id' => $price['item_id'],
                    'idempotency_key' => $key,
                ]);

                $transaction = $wallet->pay(
                    $amount,
                    'เช่าเซิร์ฟเวอร์ ' . $plan->name . ' (' . VpsPricing::periodLabel($period) . ')',
                    VpsPayment::class,
                    $payment->id,
                    ['vps_instance_id' => $instance->id],
                );

                if (! $transaction) {
                    throw new VpsOrderException('ตัดเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                }

                $payment->update(['wallet_transaction_id' => $transaction->id]);

                return [$instance, $payment, true];
            }, 3);
        } catch (QueryException $e) {
            $existing = VpsPayment::where('idempotency_key', $key)->first();

            if ($existing && $existing->instance) {
                return [$existing->instance, $existing, false];
            }

            throw $e;
        }
    }

    /**
     * Buy the machine upstream. From here the customer has paid: every exit
     * delivers, refunds, or hands the question to the reconciliation job.
     */
    protected function fulfil(VpsInstance $instance, VpsPayment $payment, array $options): VpsInstance
    {
        $result = $this->api->purchaseVirtualMachine((string) $payment->item_id, $this->setupPayload($instance, $options));

        $status = (int) ($result['status_code'] ?? 0);
        $body = (array) ($result['body'] ?? []);

        if ($this->api->lastOutcomeUnknown()) {
            // Timed out in transit, or upstream fell over mid-order: it may
            // have gone through with our card. Refunding now could give away a
            // server; buying again could buy two. Wait, and let the
            // reconciliation job look for it.
            UpstreamBilling::pauseIfPaymentTrouble($status, $body, 'VPS order');
            $this->rememberSecret($instance, $options);
            $instance->update([
                'status' => VpsInstance::STATUS_PROVISIONING,
                'last_error' => 'purchase outcome unknown: ' . ($result === null
                    ? 'the request timed out in transit'
                    : 'HTTP ' . $status . ' ' . mb_substr((string) json_encode($body), 0, 300)),
                'last_polled_at' => now(),
            ]);

            return $instance;
        }

        if ($result === null) {
            // It never left: no token, our own rate limiter, upstream's 429,
            // or a connection that was refused before a byte went out.
            // Nothing was bought.
            return $this->failAndRefund($instance, 'purchase call did not go out (' . ($this->api->lastFailure() ?? 'unknown') . ')');
        }

        if ($status >= 400) {
            return $this->failAndRefund(
                $instance,
                'upstream rejected the order: HTTP ' . $status . ' ' . mb_substr((string) json_encode($body), 0, 500),
                DomainRegistrarService::looksLikePaymentProblem($status, $body),
            );
        }

        // Upstream took the order. On a 200 our money is spent; on a 202 the
        // charge is still clearing and the machine only appears once it has —
        // the payment stays pending until then, so an order whose charge
        // fails upstream can still be refunded (see refundWindowMinutes).
        $order = is_array($body['order'] ?? null) ? $body['order'] : $body;
        $vm = is_array($body['virtual_machine'] ?? null) ? $body['virtual_machine'] : [];

        $payment->update([
            'status' => $status === 202 ? VpsPayment::STATUS_PENDING : VpsPayment::STATUS_PAID,
            'remote_order_id' => isset($order['id']) ? (string) $order['id'] : null,
        ]);

        $instance->fill([
            'status' => VpsInstance::STATUS_PROVISIONING,
            'remote_order_id' => isset($order['id']) ? (string) $order['id'] : null,
            'remote_subscription_id' => $order['subscription_id'] ?? $vm['subscription_id'] ?? null,
            'last_polled_at' => now(),
        ]);

        if ($vm !== []) {
            $this->applyVmDetails($instance, $vm);
        }

        $instance->save();

        BusinessAlerts::vpsOrdered($instance, (float) $payment->amount_thb);

        // 202: paid, not built. The machine turns up later in `initial` and
        // is installed by the reconciliation job — with the customer's
        // password, which has to wait somewhere safe until then.
        if ($status === 202 || ($vm['state'] ?? null) === 'initial') {
            $this->rememberSecret($instance, $options);

            return $instance;
        }

        if (($vm['state'] ?? null) === 'running' && ($vm['actions_lock'] ?? 'unlocked') === 'unlocked') {
            return $this->markActive($instance);
        }

        return $instance;
    }

    /**
     * What the supplier needs to install the machine.
     *
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>
     */
    protected function setupPayload(VpsInstance $instance, array $options): array
    {
        $setup = [
            'template_id' => (int) $instance->template_id,
            'data_center_id' => (int) $instance->data_center_id,
            'hostname' => $instance->hostname,
            // Weekly backups are free upstream and the one thing a customer
            // who deleted the wrong folder will thank us for.
            'enable_backups' => true,
        ];

        if (! empty($options['password'])) {
            $setup['password'] = (string) $options['password'];
        }

        if (! empty($options['public_key'])) {
            $setup['public_key'] = [
                'name' => 'key-' . $instance->id,
                'key' => trim((string) $options['public_key']),
            ];
        }

        return $setup;
    }

    // ============================================================ activation

    /**
     * The machine is running. Tell the customer, and make sure it renews from
     * THEIR wallet rather than our card.
     */
    public function markActive(VpsInstance $instance): VpsInstance
    {
        $firstActivation = $instance->activated_at === null;

        $instance->status = VpsInstance::STATUS_ACTIVE;
        $instance->activated_at ??= now();
        $instance->expires_at ??= now()->addMonths(max(1, (int) $instance->months));
        $instance->last_error = null;
        $instance->save();

        if ($instance->remote_subscription_id) {
            // Two systems renewing the same machine is two charges: ours from
            // the customer's wallet, and upstream's from our card. Ours wins —
            // upstream's is switched off, always, whatever the customer chose.
            $this->api->setAutoRenewal($instance->remote_subscription_id, false);
            $this->refreshSubscription($instance);
        }

        $this->forgetSecret($instance);

        if ($firstActivation) {
            try {
                if ($instance->user?->email) {
                    Mail::to($instance->user->email)->send(new VpsReadyMail($instance->fresh() ?? $instance));
                }
            } catch (\Throwable $e) {
                Log::error('[VPS] ready mail failed', ['instance' => $instance->id, 'error' => $e->getMessage()]);
            }

            BusinessAlerts::vpsReady($instance);
        }

        return $instance;
    }

    /**
     * Pull the machine's current state, addresses and install into our row.
     *
     * @param  array<string,mixed>  $vm
     */
    public function applyVmDetails(VpsInstance $instance, array $vm): void
    {
        if (isset($vm['id']) && is_numeric($vm['id'])) {
            $instance->remote_vm_id = (int) $vm['id'];
        }

        if (! empty($vm['subscription_id']) && ! $instance->remote_subscription_id) {
            $instance->remote_subscription_id = (string) $vm['subscription_id'];
        }

        if (isset($vm['state'])) {
            $instance->state = (string) $vm['state'];
        }

        $ipv4 = $vm['ipv4'][0]['address'] ?? null;
        $ipv6 = $vm['ipv6'][0]['address'] ?? null;

        if (is_string($ipv4) && $ipv4 !== '') {
            $instance->ipv4 = $ipv4;
        }

        if (is_string($ipv6) && $ipv6 !== '') {
            $instance->ipv6 = $ipv6;
        }

        // The supplier's default hostname names the supplier. Ours was sent at
        // setup; only take theirs back once it is no longer the default.
        $hostname = (string) ($vm['hostname'] ?? '');

        if ($hostname !== '' && ! str_contains($hostname, 'hstgr') && ! str_contains($hostname, 'hostinger')) {
            $instance->hostname = $hostname;
        }

        $templateName = (string) ($vm['template']['name'] ?? '');

        if ($templateName !== '' && stripos($templateName, 'hostinger') === false) {
            $instance->template_name = $templateName;
        }

        if (isset($vm['template']['id']) && is_numeric($vm['template']['id'])) {
            $instance->template_id = (int) $vm['template']['id'];
        }
    }

    /** Fetch the machine and store what it says. False when upstream would not answer. */
    public function refreshVm(VpsInstance $instance): bool
    {
        if (! $instance->remote_vm_id) {
            return false;
        }

        $vm = $this->api->getVirtualMachine((int) $instance->remote_vm_id);

        if (! is_array($vm) || $vm === []) {
            return false;
        }

        $this->applyVmDetails($instance, $vm);
        $instance->save();

        return true;
    }

    /**
     * Read the paid-until date from the subscription — the machine itself has
     * no expiry field. Returns the subscription row, or null.
     *
     * @return array<string,mixed>|null
     */
    public function refreshSubscription(VpsInstance $instance): ?array
    {
        if (! $instance->remote_subscription_id) {
            return null;
        }

        $subscription = $this->findSubscription((string) $instance->remote_subscription_id);

        if (! $subscription) {
            return null;
        }

        $expires = $subscription['expires_at'] ?? $subscription['next_billing_at'] ?? null;

        if ($expires) {
            try {
                $instance->update(['expires_at' => Carbon::parse($expires)]);
            } catch (\Throwable) {
                // An unreadable date is not worth failing over; ours stands.
            }
        }

        return $subscription;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findSubscription(string $subscriptionId): ?array
    {
        foreach ($this->api->getSubscriptions() ?? [] as $row) {
            if (is_array($row) && (string) ($row['id'] ?? '') === $subscriptionId) {
                return $row;
            }
        }

        return null;
    }

    // ========================================================= reconciliation

    /**
     * Move one unsettled order forward. Safe to call every few minutes.
     *
     * Two callers can reach the same order at once — the scheduled job, and
     * the customer's own page polling while they wait. Each step here calls
     * upstream (a setup, an activation), so only one of them runs it.
     *
     * @return string what happened, for the command's output
     */
    public function reconcile(VpsInstance $instance): string
    {
        $lock = Cache::lock('vps:reconcile:' . $instance->id, 120);

        if (! $lock->get()) {
            return 'busy';
        }

        try {
            $instance = $instance->fresh() ?? $instance;

            if (! in_array($instance->status, [VpsInstance::STATUS_PENDING, VpsInstance::STATUS_PROVISIONING], true)) {
                return 'settled';
            }

            return $this->reconcileLocked($instance);
        } finally {
            $lock->release();
        }
    }

    protected function reconcileLocked(VpsInstance $instance): string
    {
        $instance->forceFill([
            'last_polled_at' => now(),
            'poll_attempts' => (int) $instance->poll_attempts + 1,
        ])->save();

        $age = (int) $instance->created_at->diffInMinutes(now());
        $payment = $instance->orderPayment()->first();

        if ($instance->status === VpsInstance::STATUS_PENDING) {
            // A young pending row is most likely a purchase still in flight in
            // the request that took the money. Claiming its machine from here
            // as well would run activation twice (two "ready" e-mails, two
            // alerts). The request answers within a minute or two; after that,
            // pending means PHP died on the way.
            if ($age < self::PENDING_GRACE_MINUTES) {
                return 'waiting';
            }

            // PHP may have died between our debit and the supplier's answer —
            // so look before refunding. A machine built from this order that
            // we then refunded would be a free server.
            [$found, $vm] = $this->findUnclaimedVm($instance);

            if ($found === 'found') {
                return $this->adopt($instance, $vm, $payment);
            }

            // Only "the list was read and it is not there" refunds. A lookup
            // that failed says nothing about whether it was bought.
            if ($found === 'none' && $age >= self::PENDING_TIMEOUT_MINUTES) {
                $this->failAndRefund($instance, 'pending past timeout; no machine for this order appeared upstream');

                return 'refunded';
            }

            return $this->undecided($instance, $found, $age);
        }

        if (! $instance->remote_vm_id) {
            [$found, $vm] = $this->findUnclaimedVm($instance);

            if ($found === 'found') {
                return $this->adopt($instance, $vm, $payment);
            }

            // The machine list, read in full, has nothing for this order after
            // its window: the purchase never happened, or its charge failed.
            if ($found === 'none' && $payment?->status === VpsPayment::STATUS_PENDING && $age >= $this->refundWindowMinutes($instance)) {
                $this->failAndRefund($instance, $instance->remote_order_id
                    ? 'order accepted (202) but no machine appeared upstream within the window'
                    : 'purchase outcome unknown and no machine appeared upstream');

                return 'refunded';
            }

            return $this->undecided($instance, $found, $age);
        }

        $vm = $this->api->getVirtualMachine((int) $instance->remote_vm_id);

        if (! is_array($vm) || $vm === []) {
            $this->flagIfStuck($instance, $age, 'อ่านสถานะเครื่องจากผู้ให้บริการไม่ได้');

            return 'waiting';
        }

        $this->applyVmDetails($instance, $vm);
        $instance->save();

        $state = (string) ($vm['state'] ?? '');

        if ($state === 'initial') {
            // Upstream can go on reporting `initial` for a while after
            // accepting a setup. Sending it again gets "already in progress",
            // which used to count as a failure and fail a healthy build.
            if ($this->setupRecentlySent($instance)) {
                return 'waiting';
            }

            if ($this->completeSetup($instance)) {
                return 'setup-sent';
            }

            // Setup calls that get no answer at all never reach the attempt
            // limit, so they would never be heard about either.
            if ($instance->status === VpsInstance::STATUS_PROVISIONING) {
                $this->flagIfStuck($instance, $age, 'สั่งติดตั้งเครื่องไม่สำเร็จ');
            }

            return 'setup-failed';
        }

        if ($state === 'running' && ($vm['actions_lock'] ?? 'unlocked') === 'unlocked') {
            $this->markActive($instance);

            return 'active';
        }

        if ($state === 'error') {
            $this->markFailed($instance, 'the machine reported state "error" while being built');

            return 'failed';
        }

        $this->flagIfStuck($instance, $age, 'เครื่องยังไม่พร้อมใช้งาน (สถานะ ' . $state . ')');

        return 'waiting';
    }

    /**
     * Install a machine that was bought but never set up — what a 202 leaves.
     */
    public function completeSetup(VpsInstance $instance): bool
    {
        if ((int) $instance->setup_attempts >= self::MAX_SETUP_ATTEMPTS) {
            $this->markFailed($instance, 'setup failed ' . self::MAX_SETUP_ATTEMPTS . ' times');

            return false;
        }

        $secret = $this->rememberedSecret($instance);
        $setup = $this->setupPayload($instance, $secret ?? []);

        $result = $this->api->setupVirtualMachine((int) $instance->remote_vm_id, $setup);

        if ($result !== null && (int) $result['status_code'] < 300) {
            // Only refusals count towards the limit: an accepted setup is
            // remembered instead, so the next run waits for it rather than
            // sending it again.
            $this->markSetupSent($instance);

            if ($secret === null) {
                // The password the customer chose is gone (the cache forgot
                // it, or the order came from a timed-out purchase): upstream
                // generated one nobody knows. Ask for a new one on the page.
                $instance->needs_password_reset = true;
            }

            $this->applyVmDetails($instance, (array) ($result['body'] ?? []));
            $instance->last_error = null;
            $instance->save();

            if ($instance->state === 'running') {
                $this->markActive($instance);
            }

            return true;
        }

        $instance->update(['last_error' => 'setup rejected: ' . mb_substr((string) json_encode($result['body'] ?? $this->api->lastFailure()), 0, 500)]);

        // A call that never got an answer (timeout, our limiter, 5xx) is not
        // a refusal of the setup; only an actual "no" uses up an attempt.
        if ($result !== null && ! $this->api->lastOutcomeUnknown()) {
            $instance->increment('setup_attempts');
        }

        if ((int) $instance->setup_attempts >= self::MAX_SETUP_ATTEMPTS) {
            $this->markFailed($instance, 'setup failed ' . self::MAX_SETUP_ATTEMPTS . ' times');
        }

        return false;
    }

    /**
     * How long an order with no machine is looked for before it is refunded.
     *
     * An order upstream acknowledged (a 202, payment clearing) gets a day —
     * the machine turns up when the charge settles, and refunding before
     * that gives the server away. One whose answer we never heard gets the
     * short window.
     */
    public function refundWindowMinutes(VpsInstance $instance): int
    {
        return $instance->remote_order_id ? self::ACCEPTED_ORDER_MINUTES : self::UNKNOWN_OUTCOME_MINUTES;
    }

    protected function markSetupSent(VpsInstance $instance): void
    {
        try {
            Cache::put('vps:setup-sent:' . $instance->id, now()->toIso8601String(), now()->addMinutes(self::SETUP_WAIT_MINUTES));
        } catch (\Throwable) {
            // Worst case without the marker: one repeated setup call, which
            // upstream refuses as already in progress.
        }
    }

    protected function setupRecentlySent(VpsInstance $instance): bool
    {
        try {
            return Cache::has('vps:setup-sent:' . $instance->id);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Nothing could be decided about an order this run.
     *
     * 'unknown' (the machine list would not load) and 'ambiguous' (more than
     * one machine could be this order's, or another order wants the same
     * one) never refund and never adopt. Ambiguity is a person's call at once;
     * silence becomes one once it has gone on too long.
     */
    protected function undecided(VpsInstance $instance, string $found, int $age): string
    {
        if ($found === 'ambiguous') {
            // On the row too, so the admin list says why this one is stuck.
            $instance->update(['last_error' => 'ambiguous: more than one machine, or more than one waiting order, fits this order — match it by hand']);

            BusinessAlerts::vpsNeedsAttention($instance, 'จับคู่เครื่องในบัญชีผู้ให้บริการกับคำสั่งเช่านี้ไม่ได้แน่ชัด (มีหลายเครื่องหรือหลายคำสั่งที่เข้าเงื่อนไข) — ระบบไม่เลือกเองและไม่คืนเงินเอง', false);

            return 'ambiguous';
        }

        $this->flagIfStuck(
            $instance,
            $age,
            $found === 'unknown' ? 'อ่านรายการเครื่องจากผู้ให้บริการไม่ได้' : 'ยังหาเครื่องที่ซื้อไม่เจอในบัญชีผู้ให้บริการ',
        );

        return $found === 'unknown' ? 'unknown' : 'waiting';
    }

    /**
     * Find the machine this order bought, among the ones nobody has claimed.
     *
     * In order of certainty:
     *   1. the subscription upstream gave us — exact;
     *   2. the hostname the customer chose, on a machine made since the order;
     *   3. an uninstalled machine of this plan made since the order — what a
     *      202 leaves behind, still wearing the supplier's default hostname.
     *
     * The owner's own servers live in the same account and are older, so the
     * time window keeps them out. A match is only taken when it is the ONLY
     * one and no other waiting order could claim it too: two customers who
     * picked the same hostname, or two orders of the same plan in flight,
     * are 'ambiguous' — a person decides, because adopting the wrong machine
     * hands one customer's server (and root password) to another.
     *
     * 'none' means the list was read in full and nothing matches; 'unknown'
     * means it could not be read, and must never be taken for 'none'.
     *
     * @return array{0:'found'|'none'|'unknown'|'ambiguous',1:array<string,mixed>|null}
     */
    public function findUnclaimedVm(VpsInstance $instance): array
    {
        $vms = $this->api->listVirtualMachines();

        if (! is_array($vms)) {
            return ['unknown', null];
        }

        $others = VpsInstance::where('id', '!=', $instance->id);

        $claimedVms = (clone $others)->whereNotNull('remote_vm_id')
            ->pluck('remote_vm_id')->map(fn ($id) => (int) $id)->all();

        $claimedSubscriptions = (clone $others)->whereNotNull('remote_subscription_id')
            ->pluck('remote_subscription_id')->map(fn ($id) => (string) $id)->all();

        $candidates = array_values(array_filter($vms, fn ($vm) => is_array($vm)
            && isset($vm['id'])
            && ! in_array((int) $vm['id'], $claimedVms, true)
            && ! (! empty($vm['subscription_id']) && in_array((string) $vm['subscription_id'], $claimedSubscriptions, true))));

        if ($instance->remote_subscription_id) {
            foreach ($candidates as $vm) {
                if ((string) ($vm['subscription_id'] ?? '') === (string) $instance->remote_subscription_id) {
                    return ['found', $vm];
                }
            }
        }

        $notBefore = $instance->created_at->copy()->subMinutes(10);

        $recent = array_values(array_filter($candidates, function ($vm) use ($notBefore) {
            try {
                return isset($vm['created_at']) && Carbon::parse($vm['created_at'])->gte($notBefore);
            } catch (\Throwable) {
                return false;
            }
        }));

        // Other orders still waiting for a machine could be claiming the same one.
        $waiting = (clone $others)
            ->whereIn('status', [VpsInstance::STATUS_PENDING, VpsInstance::STATUS_PROVISIONING])
            ->whereNull('remote_vm_id')
            ->get(['id', 'hostname', 'vps_plan_id']);

        $byName = array_values(array_filter($recent, fn ($vm) => strcasecmp((string) ($vm['hostname'] ?? ''), (string) $instance->hostname) === 0));

        if ($byName !== []) {
            $rivals = $waiting->filter(fn ($o) => strcasecmp((string) $o->hostname, (string) $instance->hostname) === 0)->count();

            return count($byName) === 1 && $rivals === 0 ? ['found', $byName[0]] : ['ambiguous', null];
        }

        $planName = strtolower(trim((string) $instance->plan?->remote_name));
        $planOf = fn (array $vm) => strtolower(trim((string) ($vm['plan'] ?? '')));

        // A machine whose plan upstream does not name could be any order's.
        $blank = array_values(array_filter($recent, fn ($vm) => ($vm['state'] ?? null) === 'initial'
            && ($planName === '' || $planOf($vm) === '' || $planOf($vm) === $planName)));

        if ($blank === []) {
            return ['none', null];
        }

        if (count($blank) > 1) {
            return ['ambiguous', null];
        }

        $rivals = $planOf($blank[0]) === ''
            ? $waiting->count()
            : $waiting->where('vps_plan_id', $instance->vps_plan_id)->count();

        return $rivals === 0 ? ['found', $blank[0]] : ['ambiguous', null];
    }

    /**
     * Claim a machine found upstream for this order.
     *
     * @param  array<string,mixed>  $vm
     */
    protected function adopt(VpsInstance $instance, array $vm, ?VpsPayment $payment): string
    {
        // Finding it proves the purchase went through: our money is spent.
        $payment?->update(['status' => VpsPayment::STATUS_PAID]);

        $instance->status = VpsInstance::STATUS_PROVISIONING;
        $this->applyVmDetails($instance, $vm);
        $instance->last_error = null;
        $instance->save();

        $state = (string) ($vm['state'] ?? '');

        if ($state === 'initial') {
            $this->completeSetup($instance);

            return 'linked + setup';
        }

        if ($state === 'running') {
            $this->markActive($instance);

            return 'linked + active';
        }

        return 'linked';
    }

    /**
     * Paid for upstream, not buildable — a person decides what happens next.
     * No automatic refund: the machine exists and cost us money.
     */
    public function markFailed(VpsInstance $instance, string $reason): void
    {
        $alreadyFailed = $instance->status === VpsInstance::STATUS_FAILED;

        $instance->update([
            'status' => VpsInstance::STATUS_FAILED,
            'last_error' => mb_substr($reason, 0, 1000),
        ]);

        if (! $alreadyFailed) {
            BusinessAlerts::vpsNeedsAttention($instance, $reason, true);
        }
    }

    protected function flagIfStuck(VpsInstance $instance, int $ageMinutes, string $what): void
    {
        if ($ageMinutes >= self::ATTENTION_AFTER_MINUTES) {
            BusinessAlerts::vpsNeedsAttention($instance, $what . ' — ผ่านมา ' . $ageMinutes . ' นาทีแล้ว', false);
        }
    }

    // ================================================================= money back

    /**
     * Give the order's money back and close the order.
     *
     * $reason is for us (it can name the supplier). $paymentProblem is about
     * OUR card upstream — it pauses all sales and pages the admin.
     */
    public function failAndRefund(VpsInstance $instance, string $reason, bool $paymentProblem = false): VpsInstance
    {
        Log::warning('[VPS] refunding order', ['instance' => $instance->id, 'reason' => $reason]);

        DB::transaction(function () use ($instance, $reason) {
            $payment = VpsPayment::where('vps_instance_id', $instance->id)
                ->where('kind', VpsPayment::KIND_ORDER)
                ->lockForUpdate()
                ->first();

            if ($payment && ! $payment->refund_transaction_id && $payment->wallet_transaction_id) {
                $wallet = Wallet::getOrCreateForUser($payment->user_id);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                $refund = $wallet->refund(
                    (float) $payment->amount_thb,
                    'คืนเงินค่าเช่า VPS ' . $instance->plan_name,
                    VpsPayment::class,
                    $payment->id,
                    null,
                    ['vps_instance_id' => $instance->id, 'reason' => 'order_failed'],
                );

                $payment->update([
                    'status' => VpsPayment::STATUS_REFUNDED,
                    'refund_transaction_id' => $refund->id,
                    'last_error' => mb_substr($reason, 0, 1000),
                ]);
            }

            VpsInstance::where('id', $instance->id)->update([
                'status' => VpsInstance::STATUS_REFUNDED,
                'last_error' => mb_substr($reason, 0, 1000),
                'updated_at' => now(),
            ]);
        }, 3);

        $this->forgetSecret($instance);

        // After the transaction: a pause or an alert that throws must not undo
        // a refund that went through.
        if ($paymentProblem) {
            UpstreamBilling::pause('VPS order refused: ' . $reason);
        }

        $instance = $instance->fresh() ?? $instance;
        BusinessAlerts::vpsPurchaseRefused($instance, $reason, $paymentProblem);

        return $instance;
    }

    // ================================================================= renewals

    /**
     * Buy the next period, from the wallet.
     *
     * Priced from the RENEWAL cost of the plan and period the customer is on.
     * One renewal per paid-until date: the key includes it, so a double press
     * — or the daily job running twice — cannot buy two periods.
     *
     * @throws VpsOrderException
     */
    public function renew(VpsInstance $instance): VpsPayment
    {
        if (! $instance->canRenew()) {
            throw new VpsOrderException('ตอนนี้ยังต่ออายุเซิร์ฟเวอร์นี้ไม่ได้ — อาจมีรายการต่ออายุค้างอยู่ หรือเซิร์ฟเวอร์ยังไม่พร้อม');
        }

        if (UpstreamBilling::isPaused()) {
            throw new VpsOrderException(UpstreamBilling::customerMessage());
        }

        $plan = $instance->plan;
        $price = $plan?->price((string) $instance->period);
        $amount = $plan?->renewPriceThb((string) $instance->period) ?? 0.0;

        if (! $plan || ! $price || $amount <= 0) {
            throw new VpsOrderException('ราคาต่ออายุของแพ็กเกจนี้ยังไม่พร้อม กรุณาติดต่อทีมงาน');
        }

        // One renewal per paid-until date — but a refunded attempt must not
        // block the next one, or a customer whose renewal failed on a network
        // blip could never pay again. Counting the refunded attempts gives a
        // retry its own key while two simultaneous presses still share one.
        $failedAttempts = VpsPayment::where('vps_instance_id', $instance->id)
            ->where('kind', VpsPayment::KIND_RENEW)
            ->where('status', VpsPayment::STATUS_REFUNDED)
            ->count();

        $key = substr(hash('sha256', implode('|', [
            'vps-renew',
            $instance->id,
            $instance->expires_at?->format('Y-m-d') ?? 'none',
            $failedAttempts,
        ])), 0, 64);

        [$payment, $created] = $this->debitForRenewal($instance, $price, $amount, $key);

        if (! $created) {
            return $payment;
        }

        return $this->fulfilRenewal($instance, $payment);
    }

    /**
     * @param  array{item_id:string,currency:string,first:int,renew:int,months:int}  $price
     * @return array{0:VpsPayment,1:bool}
     */
    protected function debitForRenewal(VpsInstance $instance, array $price, float $amount, string $key): array
    {
        try {
            return DB::transaction(function () use ($instance, $price, $amount, $key) {
                $wallet = Wallet::getOrCreateForUser($instance->user_id);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                if (! $wallet || ! $wallet->is_active) {
                    throw new VpsOrderException('กระเป๋าเงินของคุณถูกระงับ กรุณาติดต่อทีมงาน');
                }

                if (! $wallet->hasSufficientBalance($amount)) {
                    throw new VpsOrderException(sprintf(
                        'ยอดเงินไม่พอต่ออายุ %s ต้องใช้ %s แต่มี %s',
                        $instance->hostname,
                        VpsPricing::format($amount),
                        VpsPricing::format((float) $wallet->balance),
                    ));
                }

                $payment = VpsPayment::create([
                    'vps_instance_id' => $instance->id,
                    'user_id' => $instance->user_id,
                    'kind' => VpsPayment::KIND_RENEW,
                    'status' => VpsPayment::STATUS_PENDING,
                    'amount_thb' => $amount,
                    'cost_cents' => $price['renew'],
                    'cost_currency' => $price['currency'],
                    'months' => $price['months'],
                    'item_id' => $price['item_id'],
                    'idempotency_key' => $key,
                    // What "the date moved" is measured against if the answer
                    // never comes back (see settleRenewal).
                    'previous_expires_at' => $instance->expires_at,
                ]);

                $transaction = $wallet->pay(
                    $amount,
                    'ต่ออายุ VPS ' . $instance->hostname,
                    VpsPayment::class,
                    $payment->id,
                    ['vps_instance_id' => $instance->id, 'kind' => 'renew'],
                );

                if (! $transaction) {
                    throw new VpsOrderException('ตัดเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                }

                $payment->update(['wallet_transaction_id' => $transaction->id]);

                return [$payment, true];
            }, 3);
        } catch (QueryException $e) {
            $existing = VpsPayment::where('idempotency_key', $key)->first();

            if ($existing) {
                return [$existing, false];
            }

            throw $e;
        }
    }

    protected function fulfilRenewal(VpsInstance $instance, VpsPayment $payment): VpsPayment
    {
        $previousExpiry = $instance->expires_at?->copy();

        $result = $this->api->renewSubscription((string) $instance->remote_subscription_id);

        $status = (int) ($result['status_code'] ?? 0);
        $body = (array) ($result['body'] ?? []);

        if ($this->api->lastOutcomeUnknown()) {
            // May have gone through. settleRenewal() checks the paid-until
            // date on the next reconciliation run and decides.
            UpstreamBilling::pauseIfPaymentTrouble($status, $body, 'VPS renewal');
            $payment->update(['last_error' => 'renewal outcome unknown: ' . ($result === null
                ? 'the request timed out in transit'
                : 'HTTP ' . $status . ' ' . mb_substr((string) json_encode($body), 0, 300))]);

            return $payment;
        }

        if ($result === null) {
            return $this->refundRenewal($payment, 'renew call did not go out (' . ($this->api->lastFailure() ?? 'unknown') . ')');
        }

        if ($status >= 400) {
            return $this->refundRenewal(
                $payment,
                'upstream rejected the renewal: HTTP ' . $status . ' ' . mb_substr((string) json_encode($body), 0, 500),
                DomainRegistrarService::looksLikePaymentProblem($status, $body),
            );
        }

        if ($status === 202) {
            // Accepted, payment still clearing: NOT renewed yet. Extending now
            // would promise a month that a declined charge later takes back —
            // the machine suspended while our page says it is paid for.
            // settleRenewal() confirms it once the paid-until date moves.
            $payment->update([
                'remote_order_id' => isset($body['id']) ? (string) $body['id'] : null,
                'last_error' => 'renewal accepted, payment still clearing (HTTP 202)',
            ]);

            return $payment;
        }

        $payment->update([
            'status' => VpsPayment::STATUS_PAID,
            'remote_order_id' => isset($body['id']) ? (string) $body['id'] : null,
        ]);

        $this->extendAfterRenewal($instance, $previousExpiry, (int) $payment->months);

        BusinessAlerts::vpsRenewed($instance->fresh() ?? $instance, (float) $payment->amount_thb);

        return $payment->fresh() ?? $payment;
    }

    /**
     * Move the paid-until date and re-arm next period's reminders. The date
     * comes from upstream when it has moved there; our +months is only the
     * fallback, because a guess about somebody else's billing drifts.
     */
    protected function extendAfterRenewal(VpsInstance $instance, ?Carbon $previousExpiry, int $months): void
    {
        $base = $previousExpiry && $previousExpiry->isFuture() ? $previousExpiry : now();

        $instance->forceFill([
            'expires_at' => $base->copy()->addMonths(max(1, $months)),
            'status' => VpsInstance::STATUS_ACTIVE,
            'renewal_notice_sent_at' => null,
            'reminders_sent' => null,
        ])->save();

        $subscription = $this->refreshSubscription($instance);

        // A renewal must never make the rental look shorter than we just sold
        // it: if upstream still shows the old date (a 202 still clearing), keep ours.
        if ($subscription && $previousExpiry && $instance->fresh()?->expires_at?->lte($previousExpiry)) {
            $instance->update(['expires_at' => $base->copy()->addMonths(max(1, $months))]);
        }

        // An expired machine is suspended upstream; renewing is what brings it back.
        $this->refreshVm($instance);
    }

    /**
     * A renewal whose answer we never heard (or heard as "payment still
     * clearing"): see whether the paid-until date moved upstream. Called by
     * the reconciliation job for renewals left pending.
     *
     * 'unknown' — the subscription list would not load — decides nothing.
     *
     * @return 'confirmed'|'refunded'|'waiting'|'unknown'|'skip'
     */
    public function settleRenewal(VpsPayment $payment): string
    {
        $instance = $payment->instance;

        if (! $instance || $payment->status !== VpsPayment::STATUS_PENDING) {
            return 'skip';
        }

        // The date it was paid against — not the machine's current one, which
        // a refresh may already have moved to the new date.
        $previous = ($payment->previous_expires_at ?? $instance->expires_at)?->copy();
        $subscription = null;

        if ($instance->remote_subscription_id) {
            $subscriptions = $this->api->getSubscriptions();

            if (! is_array($subscriptions)) {
                return 'unknown';
            }

            foreach ($subscriptions as $row) {
                if (is_array($row) && (string) ($row['id'] ?? '') === (string) $instance->remote_subscription_id) {
                    $subscription = $row;
                    break;
                }
            }
        }

        $expires = null;

        try {
            $expires = isset($subscription['expires_at']) ? Carbon::parse($subscription['expires_at']) : null;
        } catch (\Throwable) {
        }

        if ($expires && (! $previous || $expires->gt($previous->copy()->addDays(1)))) {
            $payment->update(['status' => VpsPayment::STATUS_PAID]);
            $instance->forceFill([
                'expires_at' => $expires,
                'status' => VpsInstance::STATUS_ACTIVE,
                'renewal_notice_sent_at' => null,
                'reminders_sent' => null,
            ])->save();

            return 'confirmed';
        }

        $window = $payment->remote_order_id ? self::ACCEPTED_RENEWAL_MINUTES : self::UNKNOWN_OUTCOME_MINUTES;

        if ($payment->created_at->diffInMinutes(now()) >= $window) {
            $this->refundRenewal($payment, 'renewal outcome unknown and the paid-until date never moved');

            return 'refunded';
        }

        return 'waiting';
    }

    public function refundRenewal(VpsPayment $payment, string $reason, bool $paymentProblem = false): VpsPayment
    {
        Log::warning('[VPS] refunding renewal', ['payment' => $payment->id, 'reason' => $reason]);

        DB::transaction(function () use ($payment, $reason) {
            $fresh = VpsPayment::where('id', $payment->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->refund_transaction_id || ! $fresh->wallet_transaction_id) {
                return;
            }

            $wallet = Wallet::getOrCreateForUser($fresh->user_id);
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $refund = $wallet->refund(
                (float) $fresh->amount_thb,
                'คืนเงินค่าต่ออายุ VPS',
                VpsPayment::class,
                $fresh->id,
                null,
                ['vps_instance_id' => $fresh->vps_instance_id, 'reason' => 'renewal_failed'],
            );

            $fresh->update([
                'status' => VpsPayment::STATUS_REFUNDED,
                'refund_transaction_id' => $refund->id,
                'last_error' => mb_substr($reason, 0, 1000),
            ]);
        }, 3);

        if ($paymentProblem) {
            UpstreamBilling::pause('VPS renewal refused: ' . $reason);
        }

        $payment = $payment->fresh() ?? $payment;

        if ($payment->instance) {
            BusinessAlerts::vpsRenewalFailed($payment->instance, $reason, $paymentProblem);
        }

        return $payment;
    }

    /**
     * An admin's decision: refund an order that never became a working
     * server. Only for rentals that are not running — a live server is not
     * refunded by accident from a list.
     */
    public function adminRefund(VpsInstance $instance, string $note): VpsInstance
    {
        if (in_array($instance->status, [VpsInstance::STATUS_ACTIVE, VpsInstance::STATUS_EXPIRED, VpsInstance::STATUS_REFUNDED], true)) {
            throw new VpsOrderException('คืนเงินได้เฉพาะรายการที่ยังติดตั้งไม่สำเร็จ');
        }

        // Whatever was bought upstream must not renew itself on our card.
        if ($instance->remote_subscription_id) {
            $this->api->setAutoRenewal((string) $instance->remote_subscription_id, false);
        }

        return $this->failAndRefund($instance, 'refunded by admin: ' . $note);
    }

    // ============================================================ the password

    /**
     * Keep the customer's chosen root password for a delayed install —
     * encrypted with the app key, in the cache, for a day at most. Never in
     * the database.
     *
     * @param  array<string,mixed>  $options
     */
    protected function rememberSecret(VpsInstance $instance, array $options): void
    {
        if (empty($options['password']) && empty($options['public_key'])) {
            return;
        }

        try {
            Cache::put(
                'vps:secret:' . $instance->id,
                Crypt::encryptString(json_encode([
                    'password' => $options['password'] ?? null,
                    'public_key' => $options['public_key'] ?? null,
                ])),
                now()->addHours(self::SECRET_TTL_HOURS),
            );
        } catch (\Throwable $e) {
            Log::warning('[VPS] could not keep the setup secret', ['instance' => $instance->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @return array{password:?string,public_key:?string}|null
     */
    protected function rememberedSecret(VpsInstance $instance): ?array
    {
        try {
            $blob = Cache::get('vps:secret:' . $instance->id);

            if (! is_string($blob)) {
                return null;
            }

            $data = json_decode(Crypt::decryptString($blob), true);

            return is_array($data) ? $data : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function forgetSecret(VpsInstance $instance): void
    {
        try {
            Cache::forget('vps:secret:' . $instance->id);
        } catch (\Throwable) {
        }
    }

    // =================================================== upstream error wording

    /**
     * An upstream refusal of a customer action, rewritten for the customer.
     *
     * Validation messages are about the customer's own input (the password,
     * the hostname) and are safe to relay in substance; anything else becomes
     * a generic sentence, because it can name the supplier.
     *
     * @param  array{status_code:int,body:array<string,mixed>}|null  $result
     */
    public static function explain(?array $result, string $fallback = 'ทำรายการไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'): string
    {
        if ($result === null) {
            return 'ติดต่อระบบเซิร์ฟเวอร์ไม่ได้ในขณะนี้ กรุณาลองใหม่ในอีกสักครู่';
        }

        $body = $result['body'] ?? [];
        $text = strtolower((string) json_encode($body));

        return match (true) {
            str_contains($text, 'leak') || str_contains($text, 'compromis') => 'รหัสผ่านนี้เคยหลุดสู่สาธารณะ กรุณาตั้งรหัสผ่านใหม่ที่ไม่เคยใช้ที่ไหนมาก่อน',
            str_contains($text, 'password') => 'รหัสผ่านไม่ผ่านเงื่อนไข: อย่างน้อย 12 ตัวอักษร มีตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข',
            str_contains($text, 'hostname') => 'ชื่อโฮสต์ไม่ถูกต้อง ใช้ได้เฉพาะ a-z 0-9 ขีดกลาง และจุด เช่น server.example.com',
            str_contains($text, 'lock') || str_contains($text, 'in progress') || str_contains($text, 'another action') => 'เซิร์ฟเวอร์กำลังทำงานอื่นอยู่ กรุณารอสักครู่แล้วลองใหม่',
            str_contains($text, 'snapshot') => 'ทำรายการสแนปช็อตไม่สำเร็จ — อาจยังไม่มีสแนปช็อต หรือกำลังสร้างอยู่',
            default => $fallback,
        };
    }
}
