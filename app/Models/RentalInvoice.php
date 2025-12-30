<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RentalInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'rental_payment_id',
        'user_rental_id',
        'type',
        'status',
        'subtotal',
        'discount',
        'vat',
        'total',
        'currency',
        'tax_id',
        'company_name',
        'company_address',
        'branch_name',
        'line_items',
        'issue_date',
        'due_date',
        'paid_at',
        'pdf_url',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'total' => 'decimal:2',
        'line_items' => 'array',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    const TYPE_INVOICE = 'invoice';
    const TYPE_RECEIPT = 'receipt';
    const TYPE_TAX_INVOICE = 'tax_invoice';

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_VOID = 'void';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = self::generateNumber($invoice->type);
            }
        });
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(RentalPayment::class, 'rental_payment_id');
    }

    /**
     * Get the rental
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(UserRental::class, 'user_rental_id');
    }

    /**
     * Generate invoice number
     */
    public static function generateNumber(string $type): string
    {
        $prefix = match ($type) {
            self::TYPE_TAX_INVOICE => 'TAX',
            self::TYPE_RECEIPT => 'RCP',
            default => 'INV',
        };

        $year = now()->format('Y');
        $month = now()->format('m');
        $random = strtoupper(Str::random(4));

        return "{$prefix}{$year}{$month}-{$random}";
    }

    /**
     * Get type label (Thai)
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_TAX_INVOICE => 'ใบกำกับภาษี',
            self::TYPE_RECEIPT => 'ใบเสร็จรับเงิน',
            default => 'ใบแจ้งหนี้',
        };
    }

    /**
     * Get status label (Thai)
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'ร่าง',
            self::STATUS_SENT => 'ส่งแล้ว',
            self::STATUS_PAID => 'ชำระแล้ว',
            self::STATUS_VOID => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * Create invoice from payment
     */
    public static function createFromPayment(RentalPayment $payment, string $type = self::TYPE_RECEIPT): self
    {
        $rental = $payment->userRental;
        $package = $rental?->rentalPackage;

        $subtotal = $payment->amount;
        $vat = $type === self::TYPE_TAX_INVOICE ? round($subtotal * 0.07, 2) : 0;
        $total = $subtotal + $vat;

        $lineItems = [];
        if ($package) {
            $lineItems[] = [
                'description' => "แพ็กเกจ {$package->display_name} ({$package->duration_text})",
                'quantity' => 1,
                'unit_price' => $subtotal,
                'amount' => $subtotal,
            ];
        }

        return self::create([
            'user_id' => $payment->user_id,
            'rental_payment_id' => $payment->id,
            'user_rental_id' => $rental?->id,
            'type' => $type,
            'status' => self::STATUS_PAID,
            'subtotal' => $subtotal,
            'discount' => 0,
            'vat' => $vat,
            'total' => $total,
            'currency' => $payment->currency,
            'line_items' => $lineItems,
            'issue_date' => now()->toDateString(),
            'paid_at' => $payment->paid_at,
        ]);
    }
}
