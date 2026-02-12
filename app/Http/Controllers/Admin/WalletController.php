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
     * Debug: ตรวจสอบว่าทำไม topup approve() ไม่ทำงาน
     * GET /admin/wallets/topups/{topup}/debug
     */
    public function debugTopup(WalletTopup $topup)
    {
        $topup->load(['wallet', 'uniquePaymentAmount', 'smsNotification', 'user']);

        $debug = [
            'timestamp' => now()->toDateTimeString(),
            'topup' => [
                'id' => $topup->id,
                'topup_id' => $topup->topup_id,
                'wallet_id' => $topup->wallet_id,
                'user_id' => $topup->user_id,
                'amount' => $topup->amount,
                'total_amount' => $topup->total_amount,
                'total_amount_raw' => $topup->getRawOriginal('total_amount'),
                'status' => $topup->status,
                'sms_verification_status' => $topup->sms_verification_status,
                'sms_notification_id' => $topup->sms_notification_id,
                'unique_payment_amount_id' => $topup->unique_payment_amount_id,
                'payment_display_amount' => $topup->payment_display_amount,
                'created_at' => $topup->created_at?->toDateTimeString(),
                'expires_at' => $topup->expires_at?->toDateTimeString(),
            ],
            'wallet' => $topup->wallet ? [
                'id' => $topup->wallet->id,
                'user_id' => $topup->wallet->user_id,
                'balance' => $topup->wallet->balance,
                'is_active' => $topup->wallet->is_active,
            ] : 'NULL — wallet ไม่มี!',
            'unique_payment_amount' => $topup->uniquePaymentAmount ? [
                'id' => $topup->uniquePaymentAmount->id,
                'unique_amount' => $topup->uniquePaymentAmount->unique_amount,
                'status' => $topup->uniquePaymentAmount->status,
                'expires_at' => $topup->uniquePaymentAmount->expires_at?->toDateTimeString(),
                'matched_at' => $topup->uniquePaymentAmount->matched_at?->toDateTimeString(),
            ] : 'NULL',
            'sms_notification' => $topup->smsNotification ? [
                'id' => $topup->smsNotification->id,
                'status' => $topup->smsNotification->status,
                'amount' => $topup->smsNotification->amount,
                'bank' => $topup->smsNotification->bank,
                'type' => $topup->smsNotification->type,
                'device_id' => $topup->smsNotification->device_id,
            ] : 'NULL',
        ];

        // ลอง simulate approve
        $debug['approve_simulation'] = [];
        $debug['approve_simulation']['status_check'] = $topup->status === WalletTopup::STATUS_PENDING
            ? 'PASS — status is pending' : 'FAIL — status is "' . $topup->status . '"';

        if ($topup->wallet) {
            $debug['approve_simulation']['wallet_check'] = 'PASS — wallet exists (id=' . $topup->wallet->id . ')';

            // ลอง approve จริงๆ ถ้า ?do_approve=1
            if (request()->has('do_approve') && $topup->status === WalletTopup::STATUS_PENDING) {
                try {
                    $result = $topup->approve(0); // system approve
                    $topup->refresh();
                    $debug['approve_result'] = [
                        'success' => $result,
                        'new_status' => $topup->status,
                        'wallet_balance_after' => $topup->wallet->fresh()->balance,
                    ];
                } catch (\Exception $e) {
                    $debug['approve_exception'] = [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile() . ':' . $e->getLine(),
                        'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10),
                    ];
                }
            }
        } else {
            $debug['approve_simulation']['wallet_check'] = 'FAIL — wallet is NULL!';
            // ลองหา wallet ด้วย user_id
            $walletByUser = Wallet::where('user_id', $topup->user_id)->first();
            $debug['approve_simulation']['wallet_by_user'] = $walletByUser
                ? 'Found wallet id=' . $walletByUser->id . ' (balance=' . $walletByUser->balance . ')'
                : 'No wallet found for user_id=' . $topup->user_id;
        }

        // เช็ค recent log entries
        try {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $lines = file($logFile);
                $relevantLines = [];
                $keywords = ['approve', 'topup', 'matchWalletTopup', 'matchOrderByAmount', 'deposit', 'wallet'];
                foreach (array_slice($lines, -300) as $line) {
                    foreach ($keywords as $keyword) {
                        if (stripos($line, $keyword) !== false) {
                            $relevantLines[] = trim($line);
                            break;
                        }
                    }
                }
                $debug['recent_logs'] = array_slice($relevantLines, -30);
            }
        } catch (\Exception $e) {
            $debug['log_error'] = $e->getMessage();
        }

        return response()->json($debug, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
