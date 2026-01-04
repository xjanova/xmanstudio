<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineNotifyService
{
    protected ?string $accessToken;

    public function __construct()
    {
        $this->accessToken = config('services.line_notify.token');
    }

    /**
     * Send notification to Line Notify
     */
    public function send(string $message, ?string $imageUrl = null): bool
    {
        if (empty($this->accessToken)) {
            Log::warning('Line Notify token not configured');
            return false;
        }

        try {
            $data = ['message' => $message];

            if ($imageUrl) {
                $data['imageThumbnail'] = $imageUrl;
                $data['imageFullsize'] = $imageUrl;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
            ])->asForm()->post('https://notify-api.line.me/api/notify', $data);

            if ($response->successful()) {
                Log::info('Line Notify sent successfully');
                return true;
            }

            Log::error('Line Notify failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Line Notify exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send new quotation notification
     */
    public function notifyNewQuotation(array $quotation): bool
    {
        $items = collect($quotation['items'])->pluck('name_th')->implode(', ');

        $message = "\n📋 ใบเสนอราคาใหม่!\n"
            . "━━━━━━━━━━━━━━━\n"
            . "🔢 เลขที่: {$quotation['quote_number']}\n"
            . "👤 ลูกค้า: {$quotation['customer']['name']}\n"
            . "🏢 บริษัท: " . ($quotation['customer']['company'] ?: '-') . "\n"
            . "📧 อีเมล: {$quotation['customer']['email']}\n"
            . "📱 โทร: {$quotation['customer']['phone']}\n"
            . "━━━━━━━━━━━━━━━\n"
            . "🛠️ บริการ: {$quotation['service']['name_th']}\n"
            . "📝 รายการ: {$items}\n"
            . "━━━━━━━━━━━━━━━\n"
            . "💰 รวมทั้งสิ้น: ฿" . number_format($quotation['grand_total'], 2) . "\n"
            . "📅 Timeline: " . $this->getTimelineText($quotation['timeline']) . "\n"
            . "━━━━━━━━━━━━━━━\n"
            . "⏰ " . now()->format('d/m/Y H:i');

        return $this->send($message);
    }

    /**
     * Send new order notification (when customer chooses to pay)
     */
    public function notifyNewOrder(array $quotation, string $paymentMethod): bool
    {
        $message = "\n🎉 คำสั่งซื้อใหม่!\n"
            . "━━━━━━━━━━━━━━━\n"
            . "🔢 เลขที่: {$quotation['quote_number']}\n"
            . "👤 ลูกค้า: {$quotation['customer']['name']}\n"
            . "📧 อีเมล: {$quotation['customer']['email']}\n"
            . "📱 โทร: {$quotation['customer']['phone']}\n"
            . "━━━━━━━━━━━━━━━\n"
            . "🛠️ บริการ: {$quotation['service']['name_th']}\n"
            . "💳 ชำระผ่าน: {$this->getPaymentMethodText($paymentMethod)}\n"
            . "💰 ยอดชำระ: ฿" . number_format($quotation['grand_total'], 2) . "\n"
            . "━━━━━━━━━━━━━━━\n"
            . "🔔 กรุณาติดต่อลูกค้าภายใน 24 ชม.\n"
            . "⏰ " . now()->format('d/m/Y H:i');

        return $this->send($message);
    }

    protected function getTimelineText(string $timeline): string
    {
        return match ($timeline) {
            'urgent' => '🔴 เร่งด่วน',
            'normal' => '🟡 ปกติ (2-3 เดือน)',
            'flexible' => '🟢 ยืดหยุ่น',
            default => $timeline,
        };
    }

    protected function getPaymentMethodText(string $method): string
    {
        return match ($method) {
            'promptpay' => 'PromptPay QR',
            'bank_transfer' => 'โอนเงินธนาคาร',
            'credit_card' => 'บัตรเครดิต',
            default => $method,
        };
    }
}
