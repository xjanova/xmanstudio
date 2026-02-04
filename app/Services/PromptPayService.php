<?php

namespace App\Services;

use App\Models\PaymentSetting;

class PromptPayService
{
    /**
     * Generate PromptPay QR Code payload (EMVCo format)
     */
    public function generateQrPayload(float $amount, ?string $promptPayId = null): string
    {
        $promptPayId = $promptPayId ?? PaymentSetting::get('promptpay_number', '');

        if (empty($promptPayId)) {
            return '';
        }

        // Clean the ID (remove dashes and spaces)
        $promptPayId = preg_replace('/[^0-9]/', '', $promptPayId);

        // Determine ID type (phone or national ID)
        $idType = strlen($promptPayId) === 13 ? '02' : '01';

        // Format merchant account info
        $merchantInfo = $this->formatTLV('00', 'A000000677010111'); // AID for PromptPay
        $merchantInfo .= $this->formatTLV($idType, $this->formatPromptPayId($promptPayId));

        // Build EMVCo QR payload
        $payload = '';
        $payload .= $this->formatTLV('00', '01'); // Payload Format Indicator
        $payload .= $this->formatTLV('01', '12'); // Point of Initiation (12 = Dynamic)
        $payload .= $this->formatTLV('29', $merchantInfo); // Merchant Account Info (PromptPay)
        $payload .= $this->formatTLV('52', '0000'); // Merchant Category Code
        $payload .= $this->formatTLV('53', '764'); // Transaction Currency (THB)

        if ($amount > 0) {
            $payload .= $this->formatTLV('54', number_format($amount, 2, '.', '')); // Transaction Amount
        }

        $payload .= $this->formatTLV('58', 'TH'); // Country Code
        $payload .= $this->formatTLV('63', '0000'); // CRC placeholder

        // Calculate and append CRC
        $crc = $this->calculateCRC16($payload);
        $payload = substr($payload, 0, -4).$crc;

        return $payload;
    }

    /**
     * Generate QR Code SVG from payload
     */
    public function generateQrCodeSvg(float $amount, int $size = 300): string
    {
        $payload = $this->generateQrPayload($amount);

        if (empty($payload)) {
            return '';
        }

        // Use SimpleSoftwareIO/QrCode if available
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size($size)
                ->errorCorrection('M')
                ->generate($payload);
        }

        // Fallback: Return payload for client-side QR generation
        return $payload;
    }

    /**
     * Get PromptPay display info
     */
    public function getDisplayInfo(): array
    {
        $number = PaymentSetting::get('promptpay_number', '');
        $name = PaymentSetting::get('promptpay_name', '');
        $enabled = PaymentSetting::get('promptpay_enabled', false);

        return [
            'enabled' => $enabled,
            'number' => $number,
            'name' => $name,
            'formatted_number' => $this->formatDisplayNumber($number),
        ];
    }

    /**
     * Format PromptPay ID for QR code
     */
    private function formatPromptPayId(string $id): string
    {
        if (strlen($id) === 10) {
            // Phone number: add country code
            return '0066'.substr($id, 1);
        }

        return $id;
    }

    /**
     * Format display number (mask middle digits)
     */
    private function formatDisplayNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        $length = strlen($number);

        if ($length === 10) {
            // Phone: 08X-XXX-XXXX -> 08X-XXX-XX12
            return substr($number, 0, 3).'-'.substr($number, 3, 3).'-'.substr($number, 6, 4);
        }

        if ($length === 13) {
            // National ID: X-XXXX-XXXXX-XX-X
            return substr($number, 0, 1).'-'.substr($number, 1, 4).'-'.substr($number, 5, 5).'-'.substr($number, 10, 2).'-'.substr($number, 12, 1);
        }

        return $number;
    }

    /**
     * Format TLV (Tag-Length-Value) string
     */
    private function formatTLV(string $tag, string $value): string
    {
        $length = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);

        return $tag.$length.$value;
    }

    /**
     * Calculate CRC16-CCITT checksum
     */
    private function calculateCRC16(string $data): string
    {
        $crc = 0xFFFF;
        $polynomial = 0x1021;

        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);

            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
