<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\PaymentSetting;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletBonusTier;
use App\Models\WalletTopup;
use App\Services\SmsPaymentService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Show user wallet
     */
    public function index()
    {
        $wallet = Wallet::getOrCreateForUser(auth()->id());

        $transactions = $wallet->transactions()
            ->completed()
            ->latest()
            ->take(10)
            ->get();

        $pendingTopups = $wallet->topups()
            ->pending()
            ->latest()
            ->get();

        return view('user.wallet.index', compact('wallet', 'transactions', 'pendingTopups'));
    }

    /**
     * Show top-up page
     */
    public function topup()
    {
        // Check if wallet is enabled
        if (! Setting::getValue('wallet_enabled', true)) {
            return redirect()
                ->route('user.wallet.index')
                ->with('error', 'ระบบเติมเงินปิดปรับปรุงชั่วคราว');
        }

        $wallet = Wallet::getOrCreateForUser(auth()->id());
        $bonusTiers = WalletBonusTier::active()->orderBy('min_amount')->get();

        // Get wallet settings
        $settings = [
            'min_amount' => (int) Setting::getValue('wallet_topup_min_amount', 100),
            'max_amount' => (int) Setting::getValue('wallet_topup_max_amount', 100000),
            'quick_amounts' => array_map('intval', explode(',', Setting::getValue('wallet_quick_amounts', '100,300,500,1000,2000,5000'))),
            'payment_methods' => [
                'bank_transfer' => Setting::getValue('wallet_payment_bank_transfer', true),
                'promptpay' => Setting::getValue('wallet_payment_promptpay', true),
                'truemoney' => Setting::getValue('wallet_payment_truemoney', true),
            ],
        ];

        return view('user.wallet.topup', compact('wallet', 'bonusTiers', 'settings'));
    }

    /**
     * Submit top-up request
     */
    public function submitTopup(Request $request, SmsPaymentService $smsPaymentService)
    {
        // Check if wallet is enabled
        if (! Setting::getValue('wallet_enabled', true)) {
            return redirect()
                ->route('user.wallet.index')
                ->with('error', 'ระบบเติมเงินปิดปรับปรุงชั่วคราว');
        }

        $minAmount = (int) Setting::getValue('wallet_topup_min_amount', 100);
        $maxAmount = (int) Setting::getValue('wallet_topup_max_amount', 100000);
        $expiryMinutes = (int) Setting::getValue('wallet_topup_expiry_minutes', config('smschecker.unique_amount_expiry', 30));

        // Build allowed payment methods
        $allowedMethods = [];
        if (Setting::getValue('wallet_payment_bank_transfer', true)) {
            $allowedMethods[] = 'bank_transfer';
        }
        if (Setting::getValue('wallet_payment_promptpay', true)) {
            $allowedMethods[] = 'promptpay';
        }
        if (Setting::getValue('wallet_payment_truemoney', true)) {
            $allowedMethods[] = 'truemoney';
        }

        $validated = $request->validate([
            'amount' => "required|integer|min:{$minAmount}|max:{$maxAmount}",
            'payment_method' => 'required|in:' . implode(',', $allowedMethods),
        ]);

        $wallet = Wallet::getOrCreateForUser(auth()->id());

        // Calculate bonus
        $bonusAmount = WalletTopup::calculateBonus($validated['amount']);
        $totalAmount = $validated['amount'] + $bonusAmount;

        // Create topup request first (to get ID for unique amount)
        $topup = WalletTopup::create([
            'wallet_id' => $wallet->id,
            'user_id' => auth()->id(),
            'topup_id' => WalletTopup::generateTopupId(),
            'amount' => $validated['amount'],
            'bonus_amount' => $bonusAmount,
            'total_amount' => $totalAmount,
            'payment_method' => $validated['payment_method'],
            'status' => WalletTopup::STATUS_PENDING,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        // Generate unique payment amount for SMS matching
        $uniqueAmount = $smsPaymentService->generateUniqueAmount(
            $validated['amount'],
            $topup->id,
            'wallet_topup',
            $expiryMinutes
        );

        if ($uniqueAmount) {
            $topup->update([
                'unique_payment_amount_id' => $uniqueAmount->id,
                'payment_display_amount' => $uniqueAmount->unique_amount,
            ]);
        }

        return redirect()
            ->route('user.wallet.topup-status', $topup)
            ->with('success', "สร้างรายการเติมเงิน #{$topup->topup_id} สำเร็จ");
    }

    /**
     * Show transaction history
     */
    public function transactions()
    {
        $wallet = Wallet::getOrCreateForUser(auth()->id());

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(20);

        return view('user.wallet.transactions', compact('wallet', 'transactions'));
    }

    /**
     * Show topup status page with unique amount
     */
    public function topupStatus(WalletTopup $topup)
    {
        // Check ownership
        if ($topup->user_id !== auth()->id()) {
            abort(403);
        }

        $wallet = $topup->wallet;
        $uniqueAmount = $topup->uniquePaymentAmount;

        // Get payment info based on settings (respect enabled payment methods)
        $bankAccounts = Setting::getValue('wallet_payment_bank_transfer', true)
            ? BankAccount::active()->ordered()->get()
            : collect();
        $promptpayNumber = Setting::getValue('wallet_payment_promptpay', true)
            ? (PaymentSetting::get('promptpay_number') ?? config('payment.promptpay.number'))
            : null;
        $promptpayName = $promptpayNumber ? PaymentSetting::get('promptpay_name', '') : '';

        return view('user.wallet.topup-status', compact('topup', 'wallet', 'uniqueAmount', 'bankAccounts', 'promptpayNumber', 'promptpayName'));
    }

    /**
     * Regenerate unique amount for pending topup (pay later)
     */
    public function regenerateUniqueAmount(WalletTopup $topup, SmsPaymentService $smsPaymentService)
    {
        // Check ownership
        if ($topup->user_id !== auth()->id()) {
            abort(403);
        }

        if ($topup->status !== WalletTopup::STATUS_PENDING) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถสร้างยอดใหม่ได้ รายการนี้ไม่อยู่ในสถานะรอชำระ');
        }

        // Expire old unique amount if exists
        if ($topup->uniquePaymentAmount) {
            $topup->uniquePaymentAmount->update(['status' => 'expired']);
        }

        // Generate new unique amount
        $expiryMinutes = (int) Setting::getValue('wallet_topup_expiry_minutes', config('smschecker.unique_amount_expiry', 30));

        $uniqueAmount = $smsPaymentService->generateUniqueAmount(
            (float) $topup->amount,
            $topup->id,
            'wallet_topup',
            $expiryMinutes
        );

        if (! $uniqueAmount) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถสร้างยอดชำระใหม่ได้ กรุณาลองใหม่');
        }

        // Update topup with new unique amount
        $topup->update([
            'unique_payment_amount_id' => $uniqueAmount->id,
            'payment_display_amount' => $uniqueAmount->unique_amount,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        return redirect()
            ->route('user.wallet.topup-status', $topup)
            ->with('success', "สร้างยอดชำระใหม่สำเร็จ กรุณาโอนเงินภายใน {$expiryMinutes} นาที");
    }

    /**
     * AJAX: Check topup payment status (polling from frontend)
     * CRITICAL: Must refresh() to get latest DB state — the topup may have been
     * approved by a different process (e.g. matchOrderByAmount or notify endpoint)
     */
    public function checkTopupStatus(WalletTopup $topup)
    {
        if ($topup->user_id !== auth()->id()) {
            abort(403);
        }

        // Always refresh from DB — another process may have approved this topup
        $topup->refresh();

        return response()->json([
            'status' => $topup->status,
            'sms_verification_status' => $topup->sms_verification_status,
            'reject_reason' => $topup->reject_reason,
        ]);
    }

    /**
     * Cancel pending top-up
     */
    public function cancelTopup(WalletTopup $topup)
    {
        // Check ownership
        if ($topup->user_id !== auth()->id()) {
            abort(403);
        }

        if ($topup->status !== WalletTopup::STATUS_PENDING) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถยกเลิกรายการนี้ได้');
        }

        // Expire unique amount if exists
        if ($topup->uniquePaymentAmount) {
            $topup->uniquePaymentAmount->update(['status' => 'expired']);
        }

        $topup->delete();

        return redirect()
            ->route('user.wallet.index')
            ->with('success', 'ยกเลิกรายการเติมเงินสำเร็จ');
    }

    /**
     * Get bonus preview (AJAX)
     */
    public function bonusPreview(Request $request)
    {
        $amount = floatval($request->amount);

        if ($amount <= 0) {
            return response()->json([
                'bonus' => 0,
                'total' => 0,
            ]);
        }

        $bonus = WalletTopup::calculateBonus($amount);

        return response()->json([
            'bonus' => $bonus,
            'total' => $amount + $bonus,
        ]);
    }
}
