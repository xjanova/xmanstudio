<?php

namespace App\Services;

use App\Models\PaymentSetting;

class PaymentFeeService
{
    /**
     * Calculate fee for a payment method
     *
     * @param  string  $method  Payment method: promptpay, bank_transfer, card
     * @param  float  $amount  Base amount
     * @return array  ['fee' => float, 'total' => float, 'fee_display' => string]
     */
    public function calculateFee(string $method, float $amount): array
    {
        $feeType = $this->getFeeType($method);
        $feeAmount = $this->getFeeAmount($method);

        $fee = 0;

        if ($feeType === 'percent') {
            $fee = round($amount * ($feeAmount / 100), 2);
        } else {
            $fee = $feeAmount;
        }

        return [
            'fee' => $fee,
            'total' => $amount + $fee,
            'fee_display' => $this->formatFeeDisplay($feeType, $feeAmount),
            'fee_type' => $feeType,
            'fee_rate' => $feeAmount,
        ];
    }

    /**
     * Get fee type for a payment method
     */
    public function getFeeType(string $method): string
    {
        return PaymentSetting::get("{$method}_fee_type", 'fixed');
    }

    /**
     * Get fee amount for a payment method
     */
    public function getFeeAmount(string $method): float
    {
        return (float) PaymentSetting::get("{$method}_fee_amount", 0);
    }

    /**
     * Check if a payment method has fees
     */
    public function hasFee(string $method): bool
    {
        return $this->getFeeAmount($method) > 0;
    }

    /**
     * Format fee display string
     */
    public function formatFeeDisplay(string $type, float $amount): string
    {
        if ($amount <= 0) {
            return 'ฟรี';
        }

        if ($type === 'percent') {
            return number_format($amount, 2).'%';
        }

        return '฿'.number_format($amount, 2);
    }

    /**
     * Get all payment methods with their fee info
     */
    public function getAllMethodFees(): array
    {
        $methods = ['promptpay', 'bank_transfer', 'card'];
        $result = [];

        foreach ($methods as $method) {
            $result[$method] = [
                'fee_type' => $this->getFeeType($method),
                'fee_amount' => $this->getFeeAmount($method),
                'fee_display' => $this->formatFeeDisplay(
                    $this->getFeeType($method),
                    $this->getFeeAmount($method)
                ),
                'has_fee' => $this->hasFee($method),
            ];
        }

        return $result;
    }

    /**
     * Get enabled payment methods with their settings
     */
    public function getEnabledMethods(): array
    {
        $methods = [];

        if (PaymentSetting::get('promptpay_enabled', false)) {
            $methods['promptpay'] = [
                'name' => 'พร้อมเพย์',
                'icon' => 'promptpay',
                ...$this->calculateFee('promptpay', 0),
            ];
        }

        if (PaymentSetting::get('bank_transfer_enabled', false)) {
            $methods['bank_transfer'] = [
                'name' => 'โอนเงินธนาคาร',
                'icon' => 'bank',
                ...$this->calculateFee('bank_transfer', 0),
            ];
        }

        if (PaymentSetting::get('card_payment_enabled', false)) {
            $methods['card'] = [
                'name' => 'บัตรเครดิต/เดบิต',
                'icon' => 'card',
                ...$this->calculateFee('card', 0),
            ];
        }

        return $methods;
    }
}
