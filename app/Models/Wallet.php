<?php

namespace App\Models;

use App\Support\Satang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'total_deposited',
        'total_spent',
        'total_refunded',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'total_refunded' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function topups()
    {
        return $this->hasMany(WalletTopup::class);
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->is_active && $this->balance >= $amount;
    }

    /**
     * Deposit money to wallet
     */
    public function deposit(
        float $amount,
        string $description = 'เติมเงิน',
        ?string $paymentMethod = null,
        ?string $paymentReference = null,
        ?int $createdBy = null,
        array $metadata = []
    ): WalletTransaction {
        $balanceBefore = $this->balance;

        $this->increment('balance', $amount);
        $this->increment('total_deposited', $amount);

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'transaction_id' => $this->generateTransactionId(),
            'type' => WalletTransaction::TYPE_DEPOSIT,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->fresh()->balance,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'description' => $description,
            'created_by' => $createdBy ?: null,
            'status' => WalletTransaction::STATUS_COMPLETED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Deduct money from wallet (for payments) — null when the wallet cannot cover it right now
     *
     * The deduction is one conditional UPDATE (balance >= amount, wallet active), not a check on
     * this model followed by an unconditional decrement. Callers load the wallet, build an order,
     * then pay; the balance they read may be stale by then. A withdrawal request that held the
     * same money in between (WalletWithdrawalService) used to be spent a second time and leave the
     * balance negative — the goods and the cash out of one baht. Callers must handle null.
     */
    public function pay(
        float $amount,
        string $description = 'ชำระเงิน',
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = []
    ): ?WalletTransaction {
        // Whole satang: the columns are DECIMAL(12,2), and a raw float is never bound into the SQL
        $satang = (int) round($amount * 100);
        if ($satang < 0) {
            return null;
        }
        $baht = Satang::toBaht($satang);

        return DB::transaction(function () use ($satang, $baht, $description, $referenceType, $referenceId, $metadata) {
            $payable = static::whereKey($this->id)->where('is_active', true)->where('balance', '>=', $baht);

            // A zero payment (fully discounted order) changes no column, and MySQL reports an UPDATE
            // that changes nothing as 0 rows — ask instead of updating
            $taken = $satang === 0
                ? $payable->exists()
                : $payable->decrement('balance', $baht, ['total_spent' => DB::raw('total_spent + ' . $baht)]) === 1;

            if (! $taken) {
                return null;
            }

            // The row is locked by the UPDATE until the caller commits: this is our own result
            $this->refresh();
            $after = Satang::fromBaht((string) $this->balance);

            return $this->transactions()->create([
                'user_id' => $this->user_id,
                'transaction_id' => $this->generateTransactionId(),
                'type' => WalletTransaction::TYPE_PAYMENT,
                'amount' => Satang::toBaht(-$satang),
                'balance_before' => Satang::toBaht($after + $satang),
                'balance_after' => Satang::toBaht($after),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'status' => WalletTransaction::STATUS_COMPLETED,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Refund money to wallet
     */
    public function refund(
        float $amount,
        string $description = 'คืนเงิน',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null,
        array $metadata = []
    ): WalletTransaction {
        $balanceBefore = $this->balance;

        $this->increment('balance', $amount);
        $this->increment('total_refunded', $amount);

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'transaction_id' => $this->generateTransactionId(),
            'type' => WalletTransaction::TYPE_REFUND,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->fresh()->balance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'created_by' => $createdBy,
            'status' => WalletTransaction::STATUS_COMPLETED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Add bonus
     */
    public function addBonus(
        float $amount,
        string $description = 'โบนัส',
        ?int $createdBy = null,
        array $metadata = []
    ): WalletTransaction {
        $balanceBefore = $this->balance;

        $this->increment('balance', $amount);
        $this->increment('total_deposited', $amount);

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'transaction_id' => $this->generateTransactionId(),
            'type' => WalletTransaction::TYPE_BONUS,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->fresh()->balance,
            'description' => $description,
            'created_by' => $createdBy,
            'status' => WalletTransaction::STATUS_COMPLETED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Admin adjustment
     */
    public function adjust(
        float $amount,
        string $description,
        ?string $adminNote = null,
        ?int $createdBy = null
    ): WalletTransaction {
        $balanceBefore = $this->balance;

        if ($amount > 0) {
            $this->increment('balance', $amount);
        } else {
            $this->decrement('balance', abs($amount));
        }

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'transaction_id' => $this->generateTransactionId(),
            'type' => WalletTransaction::TYPE_ADJUSTMENT,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->fresh()->balance,
            'description' => $description,
            'admin_note' => $adminNote,
            'created_by' => $createdBy,
            'status' => WalletTransaction::STATUS_COMPLETED,
        ]);
    }

    /**
     * Generate unique transaction ID
     */
    private function generateTransactionId(): string
    {
        return 'TXN' . now()->format('ymd') . strtoupper(Str::random(8));
    }

    /**
     * Get or create wallet for user
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'balance' => 0,
                'total_deposited' => 0,
                'total_spent' => 0,
                'total_refunded' => 0,
                'is_active' => true,
            ]
        );
    }
}
