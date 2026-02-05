<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
            'created_by' => $createdBy,
            'status' => WalletTransaction::STATUS_COMPLETED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Deduct money from wallet (for payments)
     */
    public function pay(
        float $amount,
        string $description = 'ชำระเงิน',
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = []
    ): ?WalletTransaction {
        if (! $this->hasSufficientBalance($amount)) {
            return null;
        }

        $balanceBefore = $this->balance;

        $this->decrement('balance', $amount);
        $this->increment('total_spent', $amount);

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'transaction_id' => $this->generateTransactionId(),
            'type' => WalletTransaction::TYPE_PAYMENT,
            'amount' => -$amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->fresh()->balance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'status' => WalletTransaction::STATUS_COMPLETED,
            'metadata' => $metadata,
        ]);
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
