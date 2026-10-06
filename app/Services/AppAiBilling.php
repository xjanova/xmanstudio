<?php

namespace App\Services;

use App\Exceptions\AppAiBillingException;
use App\Models\AppAiUsage;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * What GigGok AI costs, and taking the money for it.
 *
 * Every message is paid from the user's wallet (THB) - there is no free quota.
 * The admin decides which models the app may use and what each costs per
 * message at /admin/ai-settings; a model that is not listed (or is switched
 * off) is never shown to the app and never used for it.
 *
 * Why charge BEFORE calling the model, then refund on failure: two messages
 * sent at once must not both pass a balance check that only one of them can
 * afford. Taking the money under a row lock first makes the second one see the
 * real balance. A failed answer gets its money back.
 */
class AppAiBilling
{
    /** wallet_transactions.reference_type for these charges */
    public const REFERENCE = 'app_ai_usage';

    public const CURRENCY = 'THB';

    /** Settings keys (group "ai") */
    public const KEY_MODELS = 'appai_models';

    public const KEY_ENABLED = 'appai_enabled';

    public const KEY_DAILY_CAP = 'appai_daily_cap';

    /**
     * Every configured model, including switched-off ones (admin page).
     *
     * @return list<array{id: string, label: string, price: float, enabled: bool}>
     */
    public function allModels(): array
    {
        $raw = Setting::getValue(self::KEY_MODELS, []);
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $m) {
            $id = trim((string) ($m['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $out[] = [
                'id' => $id,
                'label' => trim((string) ($m['label'] ?? '')) ?: $id,
                'price' => round(max(0, (float) ($m['price'] ?? 0)), 2),
                'enabled' => (bool) ($m['enabled'] ?? false),
            ];
        }

        return $out;
    }

    /**
     * The models the app may show and use, in the admin's order.
     *
     * @return list<array{id: string, label: string, price: float}>
     */
    public function models(): array
    {
        return array_values(array_map(
            fn ($m) => ['id' => $m['id'], 'label' => $m['label'], 'price' => $m['price']],
            array_filter($this->allModels(), fn ($m) => $m['enabled']),
        ));
    }

    /**
     * The model a request gets: the one it asked for if we offer it, otherwise
     * our first offered model. null = nothing is offered at all.
     *
     * Falling back rather than refusing: an older app may still ask for a model
     * we dropped, and failing every message over that would be worse than
     * answering with one we do offer (the response says which one it was, and
     * what it cost).
     *
     * @return array{id: string, label: string, price: float}|null
     */
    public function pick(?string $requested): ?array
    {
        $offered = $this->models();
        if ($offered === []) {
            return null;
        }
        $requested = trim((string) $requested);
        foreach ($offered as $m) {
            if ($m['id'] === $requested) {
                return $m;
            }
        }

        return $offered[0];
    }

    public function enabled(): bool
    {
        return (bool) Setting::getValue(self::KEY_ENABLED, config('appai.enabled', true))
            && (bool) config('appai.enabled', true);
    }

    /** Most a single user may spend per day (THB). 0 = no cap. */
    public function dailyCap(): float
    {
        return round(max(0, (float) Setting::getValue(self::KEY_DAILY_CAP, 0)), 2);
    }

    /** What this user has actually paid today (refunds excluded). */
    public function spentToday(int $userId): float
    {
        return round((float) AppAiUsage::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfDay())
            ->where('refunded', false)
            ->sum('price'), 2);
    }

    public function messagesToday(int $userId): int
    {
        return AppAiUsage::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfDay())
            ->where('ok', true)
            ->count();
    }

    public function balance(int $userId): float
    {
        $wallet = Wallet::where('user_id', $userId)->first();

        return $wallet && $wallet->is_active ? round((float) $wallet->balance, 2) : 0.0;
    }

    public function topupUrl(): string
    {
        return url('/wallet/topup');
    }

    /**
     * Take the price of one message. Throws instead of returning null so a
     * caller cannot forget to check - an unchecked null here is a free message.
     *
     * @throws AppAiBillingException
     */
    public function charge(int $userId, float $price, string $model): ?WalletTransaction
    {
        if ($price <= 0) {
            return null; // the admin made this model free
        }

        return DB::transaction(function () use ($userId, $price, $model) {
            $wallet = Wallet::getOrCreateForUser($userId);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if (! $wallet || ! $wallet->is_active) {
                throw new AppAiBillingException(AppAiBillingException::WALLET_INACTIVE);
            }

            $cap = $this->dailyCap();
            if ($cap > 0 && $this->spentToday($userId) + $price > $cap + 0.001) {
                throw new AppAiBillingException(AppAiBillingException::DAILY_CAP);
            }

            if (! $wallet->hasSufficientBalance($price)) {
                throw new AppAiBillingException(AppAiBillingException::INSUFFICIENT);
            }

            $txn = $wallet->pay($price, 'GigGok AI · ' . $model, self::REFERENCE, null, [
                'source' => 'giggok',
                'model' => $model,
            ]);

            if (! $txn) {
                throw new AppAiBillingException(AppAiBillingException::INSUFFICIENT);
            }

            return $txn;
        }, 3);
    }

    /** Give back a charge whose answer never arrived. */
    public function refund(WalletTransaction $payment, ?int $usageId): void
    {
        DB::transaction(function () use ($payment, $usageId) {
            $wallet = Wallet::whereKey($payment->wallet_id)->lockForUpdate()->first();
            if (! $wallet) {
                return;
            }
            $wallet->refund(
                abs((float) $payment->amount),
                'คืนเครดิต GigGok AI (ตอบไม่สำเร็จ)',
                self::REFERENCE,
                $usageId,
                null,
                ['source' => 'giggok', 'payment' => $payment->transaction_id],
            );
        }, 3);
    }
}
