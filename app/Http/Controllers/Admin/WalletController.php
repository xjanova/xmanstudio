<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletBonusTier;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Wallet overview dashboard
     */
    public function index(Request $request)
    {
        // Stats
        $stats = [
            'total_balance' => Wallet::sum('balance'),
            'total_deposited' => Wallet::sum('total_deposited'),
            'total_spent' => Wallet::sum('total_spent'),
            'total_wallets' => Wallet::count(),
            'pending_topups' => WalletTopup::pending()->count(),
        ];

        // Recent transactions
        $recentTransactions = WalletTransaction::with('user')
            ->completed()
            ->latest()
            ->take(10)
            ->get();

        // Pending topups
        $pendingTopups = WalletTopup::with('user')
            ->pending()
            ->latest()
            ->take(10)
            ->get();

        return view('admin.wallets.index', compact('stats', 'recentTransactions', 'pendingTopups'));
    }

    /**
     * List all wallets
     */
    public function wallets(Request $request)
    {
        $query = Wallet::with('user')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('min_balance')) {
            $query->where('balance', '>=', $request->min_balance);
        }

        $wallets = $query->paginate(20)->withQueryString();

        return view('admin.wallets.wallets', compact('wallets'));
    }

    /**
     * View wallet details
     */
    public function show(Wallet $wallet)
    {
        $wallet->load('user');

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(20);

        return view('admin.wallets.show', compact('wallet', 'transactions'));
    }

    /**
     * Adjust wallet balance
     */
    public function adjust(Request $request, Wallet $wallet)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'required|string|max:255',
            'admin_note' => 'nullable|string|max:500',
        ]);

        $wallet->adjust(
            $validated['amount'],
            $validated['description'],
            $validated['admin_note'],
            auth()->id()
        );

        return redirect()
            ->back()
            ->with('success', 'ปรับยอดเงินในกระเป๋าสำเร็จ');
    }

    /**
     * List all top-up requests
     */
    public function topups(Request $request)
    {
        $query = WalletTopup::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('topup_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $topups = $query->paginate(20)->withQueryString();

        $stats = [
            'pending' => WalletTopup::pending()->count(),
            'approved' => WalletTopup::approved()->count(),
            'total_approved' => WalletTopup::approved()->sum('total_amount'),
        ];

        return view('admin.wallets.topups', compact('topups', 'stats'));
    }

    /**
     * Show top-up details
     */
    public function showTopup(WalletTopup $topup)
    {
        $topup->load(['user', 'wallet', 'approvedBy']);

        return view('admin.wallets.topup-show', compact('topup'));
    }

    /**
     * Approve top-up
     */
    public function approveTopup(WalletTopup $topup)
    {
        if ($topup->status !== WalletTopup::STATUS_PENDING) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถอนุมัติรายการนี้ได้');
        }

        $topup->approve(auth()->id());

        return redirect()
            ->back()
            ->with('success', "อนุมัติการเติมเงิน #{$topup->topup_id} สำเร็จ");
    }

    /**
     * Reject top-up
     */
    public function rejectTopup(Request $request, WalletTopup $topup)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($topup->status !== WalletTopup::STATUS_PENDING) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถปฏิเสธรายการนี้ได้');
        }

        $topup->reject(auth()->id(), $validated['reason']);

        return redirect()
            ->back()
            ->with('success', "ปฏิเสธการเติมเงิน #{$topup->topup_id}");
    }

    /**
     * Transactions list
     */
    public function transactions(Request $request)
    {
        $query = WalletTransaction::with('user')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        return view('admin.wallets.transactions', compact('transactions'));
    }

    /**
     * Bonus tiers management
     */
    public function bonusTiers()
    {
        $tiers = WalletBonusTier::orderBy('min_amount')->get();

        return view('admin.wallets.bonus-tiers', compact('tiers'));
    }

    /**
     * Store bonus tier
     */
    public function storeBonusTier(Request $request)
    {
        $validated = $request->validate([
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|gt:min_amount',
            'bonus_type' => 'required|in:percentage,fixed',
            'bonus_value' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        WalletBonusTier::create($validated);

        return redirect()
            ->back()
            ->with('success', 'เพิ่มโบนัสเติมเงินสำเร็จ');
    }

    /**
     * Update bonus tier
     */
    public function updateBonusTier(Request $request, WalletBonusTier $tier)
    {
        $validated = $request->validate([
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|gt:min_amount',
            'bonus_type' => 'required|in:percentage,fixed',
            'bonus_value' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $tier->update($validated);

        return redirect()
            ->back()
            ->with('success', 'อัพเดทโบนัสเติมเงินสำเร็จ');
    }

    /**
     * Delete bonus tier
     */
    public function destroyBonusTier(WalletBonusTier $tier)
    {
        $tier->delete();

        return redirect()
            ->back()
            ->with('success', 'ลบโบนัสเติมเงินสำเร็จ');
    }

    /**
     * Wallet settings page
     */
    public function settings()
    {
        $settings = [
            'wallet_topup_min_amount' => Setting::getValue('wallet_topup_min_amount', 100),
            'wallet_topup_max_amount' => Setting::getValue('wallet_topup_max_amount', 100000),
            'wallet_topup_expiry_minutes' => Setting::getValue('wallet_topup_expiry_minutes', 30),
            'wallet_enabled' => Setting::getValue('wallet_enabled', true),
            'wallet_quick_amounts' => Setting::getValue('wallet_quick_amounts', '100,300,500,1000,2000,5000'),
            'wallet_payment_bank_transfer' => Setting::getValue('wallet_payment_bank_transfer', true),
            'wallet_payment_promptpay' => Setting::getValue('wallet_payment_promptpay', true),
            'wallet_payment_truemoney' => Setting::getValue('wallet_payment_truemoney', true),
        ];

        return view('admin.wallets.settings', compact('settings'));
    }

    /**
     * Update wallet settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'wallet_topup_min_amount' => 'required|numeric|min:1',
            'wallet_topup_max_amount' => 'required|numeric|min:1|gt:wallet_topup_min_amount',
            'wallet_topup_expiry_minutes' => 'required|integer|min:5|max:1440',
            'wallet_quick_amounts' => 'required|string',
        ]);

        // Save settings
        Setting::setValue('wallet_topup_min_amount', $validated['wallet_topup_min_amount']);
        Setting::setValue('wallet_topup_max_amount', $validated['wallet_topup_max_amount']);
        Setting::setValue('wallet_topup_expiry_minutes', $validated['wallet_topup_expiry_minutes']);
        Setting::setValue('wallet_quick_amounts', $validated['wallet_quick_amounts']);
        Setting::setValue('wallet_enabled', $request->boolean('wallet_enabled'));
        Setting::setValue('wallet_payment_bank_transfer', $request->boolean('wallet_payment_bank_transfer'));
        Setting::setValue('wallet_payment_promptpay', $request->boolean('wallet_payment_promptpay'));
        Setting::setValue('wallet_payment_truemoney', $request->boolean('wallet_payment_truemoney'));

        return redirect()
            ->back()
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }
}
