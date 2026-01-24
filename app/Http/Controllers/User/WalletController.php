<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletBonusTier;
use App\Models\WalletTopup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
    public function submitTopup(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:100000',
            'payment_method' => 'required|in:bank_transfer,promptpay,truemoney',
            'payment_reference' => 'nullable|string|max:100',
            'payment_proof' => 'nullable|image|max:5120', // 5MB
        ]);

        $wallet = Wallet::getOrCreateForUser(auth()->id());

        // Calculate bonus
        $bonusAmount = WalletTopup::calculateBonus($validated['amount']);
        $totalAmount = $validated['amount'] + $bonusAmount;

        // Handle file upload
        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')->store('wallet/proofs', 'public');
        }

        // Create topup request
        $topup = WalletTopup::create([
            'wallet_id' => $wallet->id,
            'user_id' => auth()->id(),
            'topup_id' => WalletTopup::generateTopupId(),
            'amount' => $validated['amount'],
            'bonus_amount' => $bonusAmount,
            'total_amount' => $totalAmount,
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'],
            'payment_proof' => $proofPath,
            'status' => WalletTopup::STATUS_PENDING,
            'expires_at' => now()->addHours(24),
        ]);

        return redirect()
            ->route('user.wallet.index')
            ->with('success', "ส่งคำขอเติมเงิน #{$topup->topup_id} สำเร็จ รอการตรวจสอบ");
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

        // Delete proof file if exists
        if ($topup->payment_proof) {
            Storage::disk('public')->delete($topup->payment_proof);
        }

        $topup->delete();

        return redirect()
            ->back()
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
