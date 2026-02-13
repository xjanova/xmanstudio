<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\PaymentSetting;

class ThaiPaymentService
{
    protected string $promptpayNumber;

    protected array $bankAccounts;

    public function __construct()
    {
        // Get PromptPay number from database, fallback to config
        $this->promptpayNumber = PaymentSetting::get('promptpay_number')
            ?? config('payment.promptpay.number', '0812345678');

        // Get bank accounts from database
        $this->bankAccounts = $this->loadBankAccounts();
    }

    /**
     * Load bank accounts from database
     */
    protected function loadBankAccounts(): array
    {
        $accounts = BankAccount::active()->ordered()->get();

        if ($accounts->isEmpty()) {
            // Fallback to config if no accounts in database
            return config('payment.bank_accounts', []);
        }

        return $accounts->map(function ($account) {
            return [
                'bank' => $account->bank_name,
                'bank_code' => $account->bank_code,
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'branch' => $account->branch,
            ];
        })->toArray();
    }

    /**
     * Get supported payment methods
     */
    public function getSupportedMethods(): array
    {
        // Get settings from database
        $promptpayEnabled = PaymentSetting::get('promptpay_enabled', true);
        $bankTransferEnabled = PaymentSetting::get('bank_transfer_enabled', true);
        $cardEnabled = PaymentSetting::get('card_payment_enabled')
            ?? config('payment.card.enabled', false);

        return [
            [
                'id' => 'promptpay',
                'name' => 'พร้อมเพย์',
                'name_en' => 'PromptPay',
                'icon' => 'promptpay',
                'description' => 'สแกน QR Code เพื่อชำระเงิน',
                'is_active' => $promptpayEnabled,
            ],
            [
                'id' => 'bank_transfer',
                'name' => 'โอนเงินธนาคาร',
                'name_en' => 'Bank Transfer',
                'icon' => 'bank',
                'description' => 'โอนเงินผ่านธนาคาร',
                'is_active' => $bankTransferEnabled,
            ],
            [
                'id' => 'credit_card',
                'name' => 'บัตรเครดิต/เดบิต',
                'name_en' => 'Credit/Debit Card',
                'icon' => 'card',
                'description' => 'ชำระด้วยบัตรเครดิตหรือเดบิต',
                'is_active' => $cardEnabled,
            ],
        ];
    }

    /**
     * Generate PromptPay QR Code
     */
    public function generatePromptPayQR(float $amount, string $reference): array
    {
        // Simple PromptPay payload generation
        // In production, use proper PromptPay library
        $qrPayload = $this->generatePromptPayPayload($amount);
        $type = $this->detectPromptPayType($this->promptpayNumber);

        return [
            'qr_code' => $qrPayload,
            'qr_image_url' => $this->generateQRImageUrl($qrPayload),
            'promptpay_number' => $this->formatDisplayNumber($this->promptpayNumber),
            'promptpay_type' => $type,
            'promptpay_type_label' => $this->getPromptPayTypeLabel($type),
            'promptpay_name' => PaymentSetting::get('promptpay_name', ''),
            'amount' => $amount,
            'reference' => $reference,
        ];
    }

    /**
     * Get bank transfer info
     */
    public function getBankTransferInfo(): array
    {
        // Return bank accounts loaded from database
        // If no accounts in database, will fallback to config in loadBankAccounts()
        return $this->bankAccounts;
    }

    /**
     * Generate PromptPay payload
     */
    protected function generatePromptPayPayload(float $amount): string
    {
        // Simplified PromptPay EMV QR Code generation
        // Format: 00020101021129370016A000000677010111<phone>5303764<amount>6304<checksum>

        $phone = preg_replace('/[^0-9]/', '', $this->promptpayNumber);
        if (substr($phone, 0, 1) === '0') {
            $phone = '66' . substr($phone, 1);
        }

        // Basic payload structure
        $payload = '000201'; // Payload Format Indicator
        $payload .= '010212'; // Point of Initiation Method (Dynamic)
        $payload .= '29370016A000000677010111' . sprintf('%02d', strlen($phone)) . $phone;
        $payload .= '5303764'; // Transaction Currency (THB)

        if ($amount > 0) {
            $amountStr = number_format($amount, 2, '.', '');
            $payload .= '54' . sprintf('%02d', strlen($amountStr)) . $amountStr;
        }

        $payload .= '5802TH'; // Country Code
        $payload .= '6304'; // CRC placeholder

        // Calculate CRC16
        $crc = $this->calculateCRC16($payload);
        $payload .= strtoupper(dechex($crc));

        return $payload;
    }

    /**
     * Calculate CRC16 checksum
     */
    protected function calculateCRC16(string $data): int
    {
        $crc = 0xFFFF;
        $polynomial = 0x1021;

        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) != 0) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return $crc;
    }

    /**
     * Generate QR image URL
     */
    protected function generateQRImageUrl(string $payload): string
    {
        // Use public QR code API
        return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($payload);
    }

    /**
     * Format PromptPay number for display
     */
    protected function formatDisplayNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        if (strlen($number) === 10) {
            return substr($number, 0, 3) . '-XXX-' . substr($number, -4);
        }

        if (strlen($number) === 13) {
            return substr($number, 0, 1) . '-XXXX-XXXXX-' . substr($number, 10, 2) . '-' . substr($number, 12, 1);
        }

        return $number;
    }

    /**
     * Detect PromptPay number type
     */
    protected function detectPromptPayType(string $number): string
    {
        $digits = preg_replace('/[^0-9]/', '', $number);

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return 'phone';
        }

        if (strlen($digits) === 13) {
            return 'national_id';
        }

        return 'other';
    }

    /**
     * Get Thai label for PromptPay type
     */
    protected function getPromptPayTypeLabel(string $type): string
    {
        return match ($type) {
            'phone' => 'เบอร์โทรศัพท์',
            'national_id' => 'เลขบัตรประชาชน',
            default => 'พร้อมเพย์',
        };
    }

    /**
     * Verify payment slip (placeholder for OCR integration)
     */
    public function verifyTransferSlip(string $slipUrl, float $expectedAmount): array
    {
        // In production, integrate with slip verification API
        // For now, return pending for manual verification
        return [
            'verified' => false,
            'requires_manual_verification' => true,
            'message' => 'รอตรวจสอบสลิปโดยแอดมิน',
        ];
    }
}
