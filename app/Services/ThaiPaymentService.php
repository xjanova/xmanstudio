<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ThaiPaymentService
{
    protected string $promptpayNumber;
    protected array $bankAccounts;

    public function __construct()
    {
        $this->promptpayNumber = config('payment.promptpay.number', '0812345678');
        $this->bankAccounts = config('payment.bank_accounts', []);
    }

    /**
     * Get supported payment methods
     */
    public function getSupportedMethods(): array
    {
        return [
            [
                'id' => 'promptpay',
                'name' => 'พร้อมเพย์',
                'name_en' => 'PromptPay',
                'icon' => 'promptpay',
                'description' => 'สแกน QR Code เพื่อชำระเงิน',
                'is_active' => true,
            ],
            [
                'id' => 'bank_transfer',
                'name' => 'โอนเงินธนาคาร',
                'name_en' => 'Bank Transfer',
                'icon' => 'bank',
                'description' => 'โอนเงินผ่านธนาคาร',
                'is_active' => true,
            ],
            [
                'id' => 'credit_card',
                'name' => 'บัตรเครดิต/เดบิต',
                'name_en' => 'Credit/Debit Card',
                'icon' => 'card',
                'description' => 'ชำระด้วยบัตรเครดิตหรือเดบิต',
                'is_active' => config('payment.card.enabled', false),
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

        return [
            'qr_code' => $qrPayload,
            'qr_image_url' => $this->generateQRImageUrl($qrPayload),
            'promptpay_number' => $this->maskPhoneNumber($this->promptpayNumber),
            'amount' => $amount,
            'reference' => $reference,
        ];
    }

    /**
     * Get bank transfer info
     */
    public function getBankTransferInfo(): array
    {
        return $this->bankAccounts ?: [
            [
                'bank' => 'ธนาคารกสิกรไทย',
                'bank_code' => 'KBANK',
                'account_number' => 'XXX-X-XXXXX-X',
                'account_name' => 'XMAN Studio Co., Ltd.',
                'branch' => 'สาขาสยามพารากอน',
            ],
            [
                'bank' => 'ธนาคารไทยพาณิชย์',
                'bank_code' => 'SCB',
                'account_number' => 'XXX-X-XXXXX-X',
                'account_name' => 'XMAN Studio Co., Ltd.',
                'branch' => 'สาขาเซ็นทรัลเวิลด์',
            ],
        ];
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
     * Mask phone number for display
     */
    protected function maskPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10) {
            return substr($phone, 0, 3) . '-XXX-' . substr($phone, -4);
        }
        return $phone;
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
