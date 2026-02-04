<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
        $wallet = Wallet::getOrCreateForUser(auth()->id());
        $bonusTiers = WalletBonusTier::active()->orderBy('min_amount')->get();

        return view('user.wallet.topup', compact('wallet', 'bonusTiers'));
    }

    /**
     * Submit top-up request
     */
    public function submitTopup(Request $request, SmsPaymentService $smsPaymentService)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:100000',
            'payment_method' => 'required|in:bank_transfer,promptpay,truemoney',
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
            'expires_at' => now()->addMinutes(30),
        ]);

        // Generate unique payment amount for SMS matching
        $uniqueAmount = $smsPaymentService->generateUniqueAmount(
            $validated['amount'],
            $topup->id,
            'wallet_topup',
            30 // 30 minutes expiry
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

        return view('user.wallet.topup-status', compact('topup', 'wallet', 'uniqueAmount'));
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
        $uniqueAmount = $smsPaymentService->generateUniqueAmount(
            (float) $topup->amount,
            $topup->id,
            'wallet_topup',
            30 // 30 minutes
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
            'expires_at' => now()->addMinutes(30),
        ]);

        return redirect()
            ->route('user.wallet.topup-status', $topup)
            ->with('success', 'สร้างยอดชำระใหม่สำเร็จ กรุณาโอนเงินภายใน 30 นาที');
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
