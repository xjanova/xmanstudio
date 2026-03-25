<?php

namespace App\Services;

use App\Models\AiprayDonation;
use App\Models\Product;

class AiprayDonationService
{
    private PromptPayService $promptPay;

    public function __construct(PromptPayService $promptPay)
    {
        $this->promptPay = $promptPay;
    }

    public function createDonation(array $data): AiprayDonation
    {
        $product = Product::where('slug', 'aipray')->firstOrFail();

        return AiprayDonation::create([
            'product_id' => $product->id,
            'donor_name' => $data['is_anonymous'] ?? false ? null : ($data['donor_name'] ?? null),
            'donor_email' => $data['donor_email'] ?? null,
            'amount' => $data['amount'],
            'message' => $data['message'] ?? null,
            'is_anonymous' => $data['is_anonymous'] ?? false,
            'payment_method' => $data['payment_method'] ?? 'promptpay',
            'status' => 'pending',
            'display_on_page' => true,
        ]);
    }

    public function generateQr(float $amount): ?string
    {
        try {
            return $this->promptPay->generateQrCodeSvg($amount);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function completeDonation(AiprayDonation $donation, ?string $reference = null): void
    {
        $donation->update([
            'status' => 'completed',
            'payment_reference' => $reference,
        ]);
    }

    public function getPublicDonations(int $limit = 20)
    {
        $product = Product::where('slug', 'aipray')->first();
        if (! $product) {
            return collect();
        }

        return AiprayDonation::where('product_id', $product->id)
            ->publicDisplay()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getStats(): array
    {
        $product = Product::where('slug', 'aipray')->first();
        if (! $product) {
            return ['total_amount' => 0, 'total_donors' => 0];
        }

        $donations = AiprayDonation::where('product_id', $product->id)->completed();

        return [
            'total_amount' => $donations->sum('amount'),
            'total_donors' => $donations->distinct('donor_name')->count('donor_name')
                + $donations->where('is_anonymous', true)->count(),
        ];
    }
}
