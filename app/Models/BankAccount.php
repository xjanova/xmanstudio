<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'bank_name',
        'bank_code',
        'account_number',
        'account_name',
        'branch',
        'swift_code',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('bank_name');
    }

    /**
     * Get masked account number
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        $number = preg_replace('/[^0-9]/', '', $this->account_number);
        $length = strlen($number);

        if ($length <= 4) {
            return $this->account_number;
        }

        $visible = substr($number, -4);
        $masked = str_repeat('X', $length - 4);

        // Format as XXX-X-XXXXX-4
        return substr($masked, 0, 3) . '-' . substr($masked, 3, 1) . '-' . substr($masked, 4) . '-' . $visible;
    }

    /**
     * Get formatted display string
     */
    public function getDisplayStringAttribute(): string
    {
        return "{$this->bank_name} - {$this->account_number} ({$this->account_name})";
    }

    /**
     * Get bank logo path (can be extended with actual logos)
     */
    public function getBankLogoAttribute(): string
    {
        $logos = [
            'KBANK' => '/images/banks/kbank.png',
            'SCB' => '/images/banks/scb.png',
            'BBL' => '/images/banks/bbl.png',
            'KTB' => '/images/banks/ktb.png',
            'TMB' => '/images/banks/tmb.png',
            'BAY' => '/images/banks/bay.png',
        ];

        return $logos[$this->bank_code] ?? '/images/banks/default.png';
    }
}
