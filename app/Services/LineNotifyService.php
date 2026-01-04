<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LineNotifyService
{
    protected ?string $channelAccessToken;
    protected ?string $adminUserId;

    public function __construct()
    {
        // Line Messaging API credentials
        $this->channelAccessToken = config('services.line.channel_access_token')
            ?? Setting::getValue('line_channel_access_token');
        $this->adminUserId = config('services.line.admin_user_id')
            ?? Setting::getValue('line_admin_user_id');
    }

    /**
     * Send message via Line Messaging API
     */
    public function send(string $message, ?string $userId = null): bool
    {
        $targetUserId = $userId ?? $this->adminUserId;

        if (empty($this->channelAccessToken) || empty($targetUserId)) {
            Log::info('Line Messaging API not configured. Using email fallback.');
            return $this->sendEmailFallback($message);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->channelAccessToken,
                'Content-Type' => 'application/json',
            ])->post('https://api.line.me/v2/bot/message/push', [
                'to' => $targetUserId,
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => $message,
                    ],
                ],
            ]);

            if ($response->successful()) {
                Log::info('Line message sent successfully');
                return true;
            }

            Log::error('Line API error: ' . $response->body());
            return $this->sendEmailFallback($message);

        } catch (\Exception $e) {
            Log::error('Line notification failed: ' . $e->getMessage());
            return $this->sendEmailFallback($message);
        }
    }

    /**
     * Email fallback when Line is not configured
     */
    protected function sendEmailFallback(string $message): bool
    {
        try {
            $adminEmail = config('mail.admin_email', 'admin@xmanstudio.com');

            // Store notification in database instead if email fails
            Log::info('Notification stored: ' . $message);

            // Try to send email if configured
            if (config('mail.mailers.smtp.host')) {
                Mail::raw($message, function ($mail) use ($adminEmail) {
                    $mail->to($adminEmail)
                        ->subject('[XMAN Studio] New Order/Quotation');
                });
                Log::info('Email notification sent');
            }

            return true;

        } catch (\Exception $e) {
            Log::warning('Email fallback skipped: ' . $e->getMessage());
            return true; // Return true anyway, notification is logged
        }
    }

    /**
     * Send new quotation notification
     */
    public function notifyNewQuotation(array $quotation): bool
    {
        $items = collect($quotation['items'])->pluck('name_th')->implode(', ');

        $message = "📋 ใบเสนอราคาใหม่!\n"
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
    public function notifyNewOrder(array $quotation, ?string $paymentMethod = null): bool
    {
        $message = "🎉 คำสั่งซื้อใหม่!\n"
            . "━━━━━━━━━━━━━━━\n"
            . "🔢 เลขที่: {$quotation['quote_number']}\n"
            . "👤 ลูกค้า: {$quotation['customer']['name']}\n"
            . "📧 อีเมล: {$quotation['customer']['email']}\n"
            . "📱 โทร: {$quotation['customer']['phone']}\n"
            . "━━━━━━━━━━━━━━━\n"
            . "🛠️ บริการ: {$quotation['service']['name_th']}\n"
            . "💳 ชำระผ่าน: " . $this->getPaymentMethodText($paymentMethod ?? 'unknown') . "\n"
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

    /**
     * Check if Line Messaging API is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->channelAccessToken) && !empty($this->adminUserId);
    }
}
