<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\RentalAddon;
use App\Models\RentalInvoice;
use App\Models\RentalPackage;
use App\Models\RentalPayment;
use App\Models\User;
use App\Models\UserRental;
use App\Models\UserRentalAddon;
use App\Services\Rentals\MlmWebhookNotifier;
use App\Services\Rentals\RentalAddonPricing;
use App\Services\Rentals\RentalAddons;
use App\Services\Rentals\RentalLifecycle;
use App\Support\Satang;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RentalService
{
    public function __construct(
        protected RentalLifecycle $lifecycle,
        protected RentalAddons $addons,
    ) {}

    /**
     * Get available packages for display
     */
    public function getAvailablePackages(bool $includeTrial = true): Collection
    {
        $query = RentalPackage::active()
            ->orderBy('sort_order')
            ->orderBy('price');

        if (! $includeTrial) {
            $query->where('price', '>', 0);
        }

        return $query->get();
    }

    /**
     * The user's active rental of one product — the generic rental when $productKey is null.
     *
     * One active rental per product: an XMAN MLM rental does not block a generic package, nor the
     * other way round. Among the generic (product-less) packages the old rule stands — one at a time.
     */
    public function getUserActiveRental(User $user, ?string $productKey = null): ?UserRental
    {
        return $user->rentals()
            ->active()
            ->whereHas('rentalPackage', function ($query) use ($productKey) {
                $query->withTrashed();
                $productKey === null ? $query->whereNull('product_key') : $query->where('product_key', $productKey);
            })
            ->with('rentalPackage')
            ->first();
    }

    /** Every active rental the user has, across products. */
    public function getUserActiveRentals(User $user): Collection
    {
        return $user->rentals()->active()->with('rentalPackage')->get();
    }

    /**
     * The user's product rentals worth showing on the status page (running or lapsed, not
     * cancelled), newest first, with their add-ons.
     */
    public function getUserProductRentals(User $user): Collection
    {
        return $user->rentals()
            ->whereIn('status', [UserRental::STATUS_ACTIVE, UserRental::STATUS_EXPIRED, UserRental::STATUS_SUSPENDED])
            ->whereHas('rentalPackage', fn ($q) => $q->withTrashed()->whereNotNull('product_key'))
            ->with(['rentalPackage', 'addons' => fn ($q) => $q->where('status', UserRentalAddon::STATUS_ACTIVE)->with('addon')])
            ->orderByDesc('expires_at')
            ->get();
    }

    /**
     * Get user's rental history
     */
    public function getUserRentalHistory(User $user, int $limit = 10)
    {
        return $user->rentals()
            ->with('rentalPackage')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new rental (pending payment)
     *
     * $extras (products only):
     *   addons      => [code => quantity]  bought with the package, each at its full cycle price
     *   reservation => the MLM subdomain-reservation token, kept on the rental and sent in its webhooks
     */
    public function createRental(
        User $user,
        RentalPackage $package,
        ?string $promoCode = null,
        string $paymentMethod = 'promptpay',
        array $extras = []
    ): array {
        // Check if user already has an active rental of this product
        $existingRental = $this->getUserActiveRental($user, $package->product_key);
        if ($existingRental) {
            return [
                'success' => false,
                'error' => 'คุณมีแพ็กเกจที่ยังใช้งานอยู่',
            ];
        }

        // Check if free trial and user has used trial before
        if ($package->has_trial && $package->price == 0) {
            $hasUsedTrial = $user->rentals()
                ->whereHas('rentalPackage', fn ($q) => $q->where('has_trial', true))
                ->exists();

            if ($hasUsedTrial) {
                return [
                    'success' => false,
                    'error' => 'คุณใช้ช่วงทดลองฟรีไปแล้ว',
                ];
            }
        }

        // Add-ons ride with a product's monthly/yearly package only.
        $cycle = $package->billingCycle();
        $addonItems = [];
        $selection = is_array($extras['addons'] ?? null) ? $extras['addons'] : [];
        if ($selection !== []) {
            if (! $package->product_key || ! $cycle) {
                return ['success' => false, 'error' => 'แพ็กเกจนี้ไม่มีตัวเลือกเสริม'];
            }
            $resolved = $this->addons->resolve($package->product_key, $selection);
            if ($resolved['errors'] !== []) {
                return ['success' => false, 'error' => implode(' · ', $resolved['errors'])];
            }
            $addonItems = $resolved['items'];
        }

        // Money in satang (integers) from here on.
        $lines = $package->product_key
            ? array_merge([RentalAddons::packageLine($package)], RentalAddons::fullCycleLines($addonItems, $cycle ?? 'monthly'))
            : [];
        $originalSatang = $lines !== [] ? RentalAddons::total($lines) : Satang::fromBaht((string) $package->price);
        $discountSatang = 0;
        $appliedPromoCode = null;

        // Apply promo code if provided
        if ($promoCode) {
            $promo = PromoCode::active()->byCode($promoCode)->first();
            if ($promo) {
                $validation = $promo->canBeUsedBy($user);
                if ($validation['valid']) {
                    // PromoCode speaks baht; bring its answer straight back to satang.
                    $discount = $promo->calculateDiscount((float) Satang::toBaht($originalSatang), $package->id);
                    $discountSatang = min($originalSatang, Satang::fromBaht(number_format($discount, 2, '.', '')));
                    $appliedPromoCode = $promo;
                }
            }
        }

        $finalSatang = max(0, $originalSatang - $discountSatang);
        $originalAmount = Satang::toBaht($originalSatang);
        $discountAmount = Satang::toBaht($discountSatang);
        $finalAmount = Satang::toBaht($finalSatang);
        $isFree = $finalSatang === 0;

        $rentalMetadata = [
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'promo_code' => $promoCode,
        ];
        if ($package->product_key) {
            $rentalMetadata['billing_cycle'] = $cycle;
        }
        if (! empty($extras['reservation']) && $package->product_key === MlmWebhookNotifier::productKey()) {
            $rentalMetadata['mlm_reservation'] = (string) $extras['reservation'];
        }

        DB::beginTransaction();
        try {
            // Create rental
            $rental = UserRental::create([
                'user_id' => $user->id,
                'rental_package_id' => $package->id,
                'status' => $isFree ? UserRental::STATUS_ACTIVE : UserRental::STATUS_PENDING,
                'starts_at' => $isFree ? now() : null,
                'period_starts_at' => $isFree ? now() : null,
                'expires_at' => $isFree ? $package->calculateExpiryDate(now()) : null,
                'amount_paid' => $finalAmount,
                'currency' => $package->currency,
                'payment_method' => $paymentMethod,
                'metadata' => $rentalMetadata,
            ]);

            // If free (trial or fully discounted), no payment needed
            if ($isFree) {
                $this->createAddonRows($rental, $addonItems, $lines, null);
                $this->lifecycle->startedFree($rental);

                DB::commit();

                return [
                    'success' => true,
                    'rental' => $rental,
                    'requires_payment' => false,
                    'message' => 'เปิดใช้งานแพ็กเกจสำเร็จ',
                ];
            }

            $paymentMetadata = [
                'kind' => RentalLifecycle::KIND_INITIAL,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'discount_amount' => $discountAmount,
                'promo_code_id' => $appliedPromoCode?->id,
            ];
            if ($lines !== []) {
                $paymentMetadata['billing_cycle'] = $cycle;
                $paymentMetadata['lines'] = $lines;
            }

            // Create pending payment
            $payment = RentalPayment::create([
                'user_id' => $user->id,
                'user_rental_id' => $rental->id,
                'amount' => $finalAmount,
                'net_amount' => $finalAmount,
                'currency' => $package->currency,
                'status' => RentalPayment::STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'description' => "ชำระค่าแพ็กเกจ {$package->display_name}"
                    . ($addonItems !== [] ? ' + ตัวเลือกเสริม ' . count($addonItems) . ' รายการ' : ''),
                'metadata' => $paymentMetadata,
            ]);

            $this->createAddonRows($rental, $addonItems, $lines, $payment);

            // Update rental with payment reference
            $rental->update(['payment_reference' => $payment->payment_reference]);

            // Affiliate commission tracking
            $affiliateService = app(AffiliateCommissionService::class);
            $affiliate = $affiliateService->resolveAffiliate($user->id);
            if ($affiliate && $finalSatang > 0) {
                $payment->update([
                    'affiliate_id' => $affiliate->id,
                    'referral_code' => $affiliate->referral_code,
                ]);
                $affiliateService->recordCommission(
                    $affiliate, (float) $finalAmount, null, $user->id,
                    'rental_payment', $payment->id,
                    "Rental: {$package->display_name}"
                );
            }

            // Record promo code usage if applicable
            if ($appliedPromoCode && $discountSatang > 0) {
                $appliedPromoCode->recordUsage($user, $payment, (float) $discountAmount);
            }

            DB::commit();

            return [
                'success' => true,
                'rental' => $rental,
                'payment' => $payment,
                'requires_payment' => true,
                'amount' => $finalAmount,
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create rental', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการสร้างการเช่า',
            ];
        }
    }

    /**
     * Renew a product rental for one more cycle: the package (or its other-cycle row — monthly ↔
     * yearly) plus every add-on in force, each at its full price. Paid early, nothing is lost: the
     * new cycle starts at the current expiry (UserRental::renew).
     *
     * An unpaid renewal of the same rental is replaced; one with a slip waiting for a check is not.
     */
    public function createRenewal(User $user, UserRental $rental, string $paymentMethod, ?string $cycle = null): array
    {
        $current = $rental->rentalPackage()->withTrashed()->first();
        if ($rental->user_id !== $user->id || ! $current || ! $current->product_key) {
            return ['success' => false, 'error' => 'ต่ออายุรายการนี้ไม่ได้'];
        }
        if (! in_array($rental->status, [UserRental::STATUS_ACTIVE, UserRental::STATUS_EXPIRED], true)) {
            return ['success' => false, 'error' => 'การเช่านี้' . $rental->status_label . ' ต่ออายุไม่ได้ — ติดต่อทีมงาน'];
        }

        $target = $current;
        if ($cycle && $cycle !== $current->billingCycle()) {
            $target = $current->sibling($cycle);
            if (! $target || ! $target->is_active) {
                return ['success' => false, 'error' => 'ยังไม่มีแพ็กเกจรอบนี้ให้เลือก'];
            }
        }
        $targetCycle = $target->billingCycle() ?? 'monthly';

        if ($this->hasPaymentAwaitingCheck($rental, RentalLifecycle::KIND_RENEWAL)) {
            return ['success' => false, 'error' => 'มีรายการต่ออายุที่แนบสลิปแล้วกำลังรอตรวจสอบอยู่'];
        }

        $items = $rental->addons()->where('status', UserRentalAddon::STATUS_ACTIVE)->with('addon')->get()
            ->filter(fn ($row) => $row->addon !== null)
            ->map(fn ($row) => ['addon' => $row->addon, 'quantity' => (int) $row->quantity])
            ->values()->all();

        $lines = array_merge([RentalAddons::packageLine($target)], RentalAddons::fullCycleLines($items, $targetCycle));
        $total = RentalAddons::total($lines);

        return DB::transaction(function () use ($user, $rental, $target, $targetCycle, $lines, $total, $paymentMethod) {
            $this->replaceUnpaid($rental, RentalLifecycle::KIND_RENEWAL);

            $payment = RentalPayment::create([
                'user_id' => $user->id,
                'user_rental_id' => $rental->id,
                'amount' => Satang::toBaht($total),
                'net_amount' => Satang::toBaht($total),
                'currency' => $target->currency,
                'status' => RentalPayment::STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'description' => "ต่ออายุ {$target->display_name}",
                'metadata' => [
                    'kind' => RentalLifecycle::KIND_RENEWAL,
                    'package_id' => $target->id,
                    'package_name' => $target->name,
                    'billing_cycle' => $targetCycle,
                    'lines' => $lines,
                ],
            ]);

            if ($total === 0) {
                $payment->markAsCompleted();
            }

            return ['success' => true, 'payment' => $payment, 'requires_payment' => $total > 0, 'amount' => Satang::toBaht($total)];
        });
    }

    /**
     * Buy add-ons for a running product rental, priced for today's remaining days. The quote holds
     * for today (Thai date) only: the payment page re-prices an unpaid one on a later day
     * (refreshAddonQuote). An earlier unpaid add-on purchase of the same rental is replaced.
     *
     * @param  array<string,int>  $selection
     */
    public function createAddonPurchase(User $user, UserRental $rental, array $selection, string $paymentMethod): array
    {
        $package = $rental->rentalPackage()->withTrashed()->first();
        if ($rental->user_id !== $user->id || ! $package || ! $package->product_key) {
            return ['success' => false, 'error' => 'เพิ่มตัวเลือกเสริมให้รายการนี้ไม่ได้'];
        }
        if (! $rental->is_active) {
            return ['success' => false, 'error' => 'แพ็กหลักไม่ได้ใช้งานอยู่ — ต่ออายุก่อนแล้วค่อยเพิ่มตัวเลือกเสริม'];
        }
        if (RentalAddonPricing::daysLeft($rental) <= 0) {
            return ['success' => false, 'error' => 'แพ็กหลักหมดอายุวันนี้ — ต่ออายุก่อนแล้วค่อยเพิ่มตัวเลือกเสริม'];
        }

        // held() counts add-ons in force or paid-and-waiting, not an unpaid earlier pick — that one
        // is replaced below, and only once this pick is known to be valid.
        $resolved = $this->addons->resolve($package->product_key, $selection, $this->addons->held($rental));
        if ($resolved['errors'] !== []) {
            return ['success' => false, 'error' => implode(' · ', $resolved['errors'])];
        }
        if ($resolved['items'] === []) {
            return ['success' => false, 'error' => 'ยังไม่ได้เลือกตัวเลือกเสริม'];
        }

        return DB::transaction(function () use ($user, $rental, $package, $resolved, $paymentMethod) {
            $this->replaceUnpaid($rental, RentalLifecycle::KIND_ADDON);

            $lines = RentalAddons::proratedLines($resolved['items'], $rental);
            $total = RentalAddons::total($lines);

            $payment = RentalPayment::create([
                'user_id' => $user->id,
                'user_rental_id' => $rental->id,
                'amount' => Satang::toBaht($total),
                'net_amount' => Satang::toBaht($total),
                'currency' => $package->currency,
                'status' => RentalPayment::STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'description' => 'ตัวเลือกเสริม ' . implode(', ', array_column($lines, 'name')),
                'metadata' => [
                    'kind' => RentalLifecycle::KIND_ADDON,
                    'billing_cycle' => $rental->billingCycle(),
                    'quote_date' => RentalAddonPricing::today(),
                    'lines' => $lines,
                ],
            ]);

            $this->createAddonRows($rental, $resolved['items'], $lines, $payment);

            if ($total === 0) {
                $payment->markAsCompleted();
            }

            return ['success' => true, 'payment' => $payment, 'requires_payment' => $total > 0, 'amount' => Satang::toBaht($total)];
        });
    }

    /**
     * An unpaid add-on purchase quoted on an earlier day is re-priced for today — the price is the
     * one for the day the money actually arrives. Called by the payment page before it shows the
     * amount (and the PromptPay QR built from it).
     *
     * @return string "unchanged" | "repriced" | "lapsed" (the package has no day left: cancelled)
     */
    public function refreshAddonQuote(RentalPayment $payment): string
    {
        $metadata = is_array($payment->metadata) ? $payment->metadata : [];
        if ($payment->status !== RentalPayment::STATUS_PENDING
            || RentalLifecycle::kindOf($payment) !== RentalLifecycle::KIND_ADDON
            || ($metadata['quote_date'] ?? null) === RentalAddonPricing::today()) {
            return 'unchanged';
        }

        $rental = $payment->userRental;
        $rows = UserRentalAddon::where('rental_payment_id', $payment->id)
            ->where('status', UserRentalAddon::STATUS_PENDING)->with('addon')->orderBy('id')->get();
        if (! $rental || $rows->isEmpty() || $rows->contains(fn ($row) => $row->addon === null)) {
            return 'unchanged';
        }

        if (! $rental->is_active || RentalAddonPricing::daysLeft($rental) <= 0) {
            DB::transaction(function () use ($payment) {
                $payment->update(['status' => RentalPayment::STATUS_CANCELLED, 'admin_notes' => 'แพ็กหลักหมดอายุก่อนชำระตัวเลือกเสริม']);
                $this->lifecycle->paymentFailed($payment);
            });

            return 'lapsed';
        }

        $items = $rows->map(fn ($row) => ['addon' => $row->addon, 'quantity' => (int) $row->quantity])->all();
        $lines = RentalAddons::proratedLines($items, $rental);
        $total = RentalAddons::total($lines);

        DB::transaction(function () use ($payment, $rows, $lines, $total, $metadata) {
            foreach ($rows->values() as $i => $row) {
                $row->update([
                    'price_satang' => $lines[$i]['amount_satang'],
                    'days_charged' => $lines[$i]['days'],
                    'cycle_days' => $lines[$i]['cycle_days'],
                ]);
            }

            // A Stripe intent made for yesterday's amount is not offered again (the payment page
            // makes a new one). It is remembered, so a customer who still pays it from an old tab
            // is found by the Stripe webhook instead of being charged for nothing.
            $superseded = array_values(array_filter(array_unique(array_merge(
                (array) ($metadata['stripe_superseded_intents'] ?? []),
                [$payment->stripe_payment_intent_id]
            ))));

            $payment->update([
                'amount' => Satang::toBaht($total),
                'net_amount' => Satang::toBaht($total),
                'stripe_payment_intent_id' => null,
                'metadata' => array_merge($metadata, [
                    'quote_date' => RentalAddonPricing::today(),
                    'lines' => $lines,
                ], $superseded !== [] ? ['stripe_superseded_intents' => $superseded] : []),
            ]);
        });

        return 'repriced';
    }

    /**
     * Process payment completion
     */
    public function processPaymentCompletion(RentalPayment $payment): array
    {
        if ($payment->status === RentalPayment::STATUS_COMPLETED) {
            return [
                'success' => false,
                'error' => 'การชำระเงินนี้ดำเนินการแล้ว',
            ];
        }

        DB::beginTransaction();
        try {
            $payment->markAsCompleted();

            // Create receipt
            $invoice = RentalInvoice::createFromPayment($payment, RentalInvoice::TYPE_RECEIPT);

            DB::commit();

            return [
                'success' => true,
                'payment' => $payment->fresh(),
                'rental' => $payment->userRental->fresh(),
                'invoice' => $invoice,
                'message' => 'ชำระเงินสำเร็จ เปิดใช้งานแพ็กเกจแล้ว',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการประมวลผลการชำระเงิน',
            ];
        }
    }

    /**
     * Verify bank transfer manually
     */
    public function verifyBankTransfer(
        RentalPayment $payment,
        int $adminId,
        ?string $notes = null
    ): array {
        if (! in_array($payment->status, [RentalPayment::STATUS_PENDING, RentalPayment::STATUS_PROCESSING])) {
            return [
                'success' => false,
                'error' => 'สถานะการชำระเงินไม่ถูกต้อง',
            ];
        }

        DB::beginTransaction();
        try {
            // Re-check under the row's lock: two admins, or an admin and the Telegram bot, can both
            // have seen "pending" above — the second must not activate the rental and issue a
            // receipt again.
            $locked = RentalPayment::whereKey($payment->getKey())->lockForUpdate()->first();
            if (! $locked || ! in_array($locked->status, [RentalPayment::STATUS_PENDING, RentalPayment::STATUS_PROCESSING])) {
                DB::rollBack();

                return [
                    'success' => false,
                    'error' => 'รายการนี้ถูกดำเนินการไปแล้ว',
                ];
            }
            $payment->setRawAttributes($locked->getAttributes(), true);

            $payment->verify($adminId, $notes);

            // Create receipt
            $invoice = RentalInvoice::createFromPayment($payment, RentalInvoice::TYPE_RECEIPT);

            DB::commit();

            return [
                'success' => true,
                'payment' => $payment->fresh(),
                'rental' => $payment->userRental->fresh(),
                'invoice' => $invoice,
                'message' => 'ยืนยันการชำระเงินสำเร็จ',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to verify bank transfer', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการยืนยันการชำระเงิน',
            ];
        }
    }

    /**
     * Reject a payment. Shared by the admin rental page and the admin Telegram bot's reject button.
     *
     * Only a payment still waiting (pending/processing) can be rejected: rejecting a completed one
     * would cancel a rental the customer has paid for and is using.
     *
     * A refused first payment cancels the rental it was for (as it always has). A refused renewal
     * or add-on purchase does not: the rental it belongs to is already paid for and running — only
     * the add-ons that payment was buying are dropped.
     *
     * @return bool false when the payment was no longer waiting (nothing changed)
     */
    public function rejectPayment(RentalPayment $payment, string $reason): bool
    {
        return DB::transaction(function () use ($payment, $reason) {
            $locked = RentalPayment::whereKey($payment->getKey())->lockForUpdate()->first();
            if (! $locked || ! in_array($locked->status, [RentalPayment::STATUS_PENDING, RentalPayment::STATUS_PROCESSING])) {
                return false;
            }
            $payment->setRawAttributes($locked->getAttributes(), true);

            $payment->update([
                'status' => RentalPayment::STATUS_FAILED,
                'admin_notes' => $reason,
            ]);
            $this->lifecycle->paymentFailed($payment);

            if (RentalLifecycle::kindOf($payment) === RentalLifecycle::KIND_INITIAL && $payment->userRental) {
                $payment->userRental->update([
                    'status' => UserRental::STATUS_CANCELLED,
                    'notes' => 'การชำระเงินถูกปฏิเสธ: ' . $reason,
                ]);
                $this->lifecycle->cancelled($payment->userRental);
            }

            return true;
        });
    }

    /**
     * Cancel a pending rental
     */
    public function cancelRental(UserRental $rental, ?string $reason = null): array
    {
        if (! in_array($rental->status, [UserRental::STATUS_PENDING, UserRental::STATUS_ACTIVE])) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถยกเลิกการเช่านี้ได้',
            ];
        }

        DB::beginTransaction();
        try {
            $rental->cancel($reason);

            // Cancel any pending payments
            $rental->payments()
                ->where('status', RentalPayment::STATUS_PENDING)
                ->update(['status' => RentalPayment::STATUS_CANCELLED]);

            $this->lifecycle->cancelled($rental);

            DB::commit();

            return [
                'success' => true,
                'rental' => $rental->fresh(),
                'message' => 'ยกเลิกการเช่าสำเร็จ',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'error' => 'เกิดข้อผิดพลาดในการยกเลิก',
            ];
        }
    }

    /**
     * Validate promo code
     */
    public function validatePromoCode(string $code, User $user, ?int $packageId = null): array
    {
        $promo = PromoCode::active()->byCode($code)->first();

        if (! $promo) {
            return [
                'valid' => false,
                'message' => 'ไม่พบโค้ดส่วนลดนี้',
            ];
        }

        $validation = $promo->canBeUsedBy($user);
        if (! $validation['valid']) {
            return $validation;
        }

        // Check package applicability
        if ($packageId && $promo->applicable_packages) {
            if (! in_array($packageId, $promo->applicable_packages)) {
                return [
                    'valid' => false,
                    'message' => 'โค้ดนี้ไม่สามารถใช้กับแพ็กเกจนี้ได้',
                ];
            }
        }

        return [
            'valid' => true,
            'promo' => [
                'code' => $promo->code,
                'name' => $promo->name,
                'discount_type' => $promo->discount_type,
                'discount_value' => $promo->discount_value,
                'discount_text' => $promo->getDiscountText(),
                'max_discount' => $promo->max_discount,
                'min_purchase' => $promo->min_purchase,
            ],
            'message' => 'โค้ดส่วนลดใช้งานได้',
        ];
    }

    /**
     * Process expired rentals (rentals:expire, hourly).
     */
    public function processExpiredRentals(): int
    {
        $ids = UserRental::where('status', UserRental::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->pluck('id');

        $processed = 0;
        foreach ($ids as $id) {
            // Re-read under a lock: a renewal paid since the list was taken has moved expires_at
            // forward and must not be overwritten (RentalLifecycle::settle holds the same lock).
            $expired = DB::transaction(function () use ($id) {
                $rental = UserRental::whereKey($id)->lockForUpdate()->first();
                if (! $rental
                    || $rental->status !== UserRental::STATUS_ACTIVE
                    || ! $rental->expires_at
                    || $rental->expires_at->isFuture()) {
                    return null;
                }

                $rental->expire();
                $this->lifecycle->expired($rental);

                return $rental;
            });

            if ($expired) {
                $processed++;
                Log::info('Rental expired', [
                    'rental_id' => $expired->id,
                    'user_id' => $expired->user_id,
                ]);
            }
        }

        return $processed;
    }

    /**
     * Pending add-on rows for what a payment buys (or bought, for a free rental).
     *
     * @param  array<int,array{addon:RentalAddon,quantity:int}>  $items
     */
    private function createAddonRows(UserRental $rental, array $items, array $lines, ?RentalPayment $payment): void
    {
        $addonLines = array_values(array_filter($lines, fn ($line) => ($line['type'] ?? null) === 'addon'));

        foreach (array_values($items) as $i => ['addon' => $addon, 'quantity' => $quantity]) {
            $line = $addonLines[$i] ?? [];
            UserRentalAddon::create([
                'user_rental_id' => $rental->id,
                'rental_addon_id' => $addon->id,
                'rental_payment_id' => $payment?->id,
                'quantity' => $quantity,
                'status' => UserRentalAddon::STATUS_PENDING,
                'price_satang' => (int) ($line['amount_satang'] ?? 0),
                'days_charged' => $line['days'] ?? null,
                'cycle_days' => $line['cycle_days'] ?? null,
            ]);
        }
    }

    /** Is a payment of this kind for the rental sitting with a slip, waiting for an admin? */
    private function hasPaymentAwaitingCheck(UserRental $rental, string $kind): bool
    {
        return $rental->payments()->where('status', RentalPayment::STATUS_PROCESSING)->get()
            ->contains(fn (RentalPayment $p) => RentalLifecycle::kindOf($p) === $kind);
    }

    /** Cancel unpaid (no slip yet) payments of this kind for the rental — a new pick replaces them. */
    private function replaceUnpaid(UserRental $rental, string $kind): void
    {
        $rental->payments()->where('status', RentalPayment::STATUS_PENDING)->get()
            ->filter(fn (RentalPayment $p) => RentalLifecycle::kindOf($p) === $kind)
            ->each(function (RentalPayment $p) {
                $p->update(['status' => RentalPayment::STATUS_CANCELLED, 'admin_notes' => 'ลูกค้าเลือกรายการใหม่แทน']);
                $this->lifecycle->paymentFailed($p);
            });
    }
}
