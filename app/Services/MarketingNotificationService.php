<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MarketingNotificationService
{
    /**
     * Send marketing notification to users.
     */
    public function sendMarketingNotification(
        string $subject,
        string $message,
        string $channel = 'all',
        ?array $userIds = null,
        ?array $filters = null
    ): array {
        $results = [
            'email' => ['sent' => 0, 'failed' => 0],
            'line' => ['sent' => 0, 'failed' => 0],
        ];

        $users = $this->getTargetUsers($userIds, $filters);

        foreach ($users as $user) {
            // Send via Email
            if (in_array($channel, ['all', 'email']) && $user->wantsMarketingEmail()) {
                try {
                    $this->sendEmail($user, $subject, $message);
                    $results['email']['sent']++;
                } catch (\Exception $e) {
                    Log::error('Marketing email failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                    $results['email']['failed']++;
                }
            }

            // Send via LINE
            if (in_array($channel, ['all', 'line']) && $user->wantsMarketingLine()) {
                try {
                    $this->sendLineMessage($user, $message);
                    $results['line']['sent']++;
                } catch (\Exception $e) {
                    Log::error('Marketing LINE message failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                    $results['line']['failed']++;
                }
            }
        }

        return $results;
    }

    /**
     * Send license expiry reminder.
     */
    public function sendLicenseExpiryReminder(User $user, $license): bool
    {
        $daysLeft = now()->diffInDays($license->expires_at, false);
        $productName = $license->product->name ?? 'Unknown Product';

        $subject = 'แจ้งเตือน: License ของคุณกำลังจะหมดอายุ';
        $message = "สวัสดีคุณ {$user->name}\n\n";
        $message .= "License สำหรับ {$productName} ของคุณจะหมดอายุใน {$daysLeft} วัน\n";
        $message .= 'วันหมดอายุ: ' . $license->expires_at->format('d/m/Y') . "\n\n";
        $message .= 'กรุณาต่ออายุ License เพื่อใช้งานต่อเนื่องได้ที่เว็บไซต์ของเรา';

        $sent = false;

        // Send via preferred channel
        if ($user->getNotificationPreference('license_expiry', 'email')) {
            try {
                $this->sendEmail($user, $subject, $message);
                $sent = true;
            } catch (\Exception $e) {
                Log::error('License expiry email failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        if ($user->getNotificationPreference('license_expiry', 'line') && $user->hasLineUid()) {
            try {
                $this->sendLineMessage($user, $message);
                $sent = true;
            } catch (\Exception $e) {
                Log::error('License expiry LINE failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    /**
     * Send new product announcement.
     */
    public function sendNewProductAnnouncement($product): array
    {
        $subject = "สินค้าใหม่: {$product->name}";
        $message = "สินค้าใหม่มาแล้ว!\n\n";
        $message .= "{$product->name}\n";
        $message .= strip_tags($product->description ?? '') . "\n\n";
        $message .= 'ราคา: ฿' . number_format($product->price, 0) . "\n";
        $message .= 'ดูรายละเอียดเพิ่มเติมได้ที่เว็บไซต์ของเรา';

        return $this->sendMarketingNotification($subject, $message);
    }

    /**
     * Send promotion notification.
     */
    public function sendPromotionNotification(
        string $title,
        string $description,
        ?string $promoCode = null,
        ?string $validUntil = null
    ): array {
        $subject = "โปรโมชั่น: {$title}";
        $message = "{$title}\n\n";
        $message .= "{$description}\n\n";

        if ($promoCode) {
            $message .= "ใช้โค้ด: {$promoCode}\n";
        }

        if ($validUntil) {
            $message .= "ใช้ได้ถึง: {$validUntil}\n";
        }

        return $this->sendMarketingNotification($subject, $message);
    }

    /**
     * Send order status update.
     */
    public function sendOrderStatusUpdate(User $user, $order, string $status): bool
    {
        $statusText = match ($status) {
            'confirmed' => 'ได้รับการยืนยัน',
            'processing' => 'กำลังดำเนินการ',
            'shipped' => 'จัดส่งแล้ว',
            'delivered' => 'ส่งมอบเรียบร้อย',
            'cancelled' => 'ถูกยกเลิก',
            default => $status,
        };

        $subject = "อัปเดตสถานะคำสั่งซื้อ #{$order->order_number}";
        $message = "สวัสดีคุณ {$user->name}\n\n";
        $message .= "คำสั่งซื้อ #{$order->order_number} ของคุณ{$statusText}\n";
        $message .= 'ยอดรวม: ฿' . number_format($order->total, 0) . "\n\n";
        $message .= 'ดูรายละเอียดเพิ่มเติมได้ที่เว็บไซต์ของเรา';

        $sent = false;

        if ($user->getNotificationPreference('order_status', 'email')) {
            try {
                $this->sendEmail($user, $subject, $message);
                $sent = true;
            } catch (\Exception $e) {
                Log::error('Order status email failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        if ($user->getNotificationPreference('order_status', 'line') && $user->hasLineUid()) {
            try {
                $this->sendLineMessage($user, $message);
                $sent = true;
            } catch (\Exception $e) {
                Log::error('Order status LINE failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    /**
     * Get target users based on filters.
     */
    private function getTargetUsers(?array $userIds = null, ?array $filters = null)
    {
        $query = User::query()->where('is_active', true);

        if ($userIds) {
            $query->whereIn('id', $userIds);
        }

        if ($filters) {
            // Filter by has active license
            if (! empty($filters['has_active_license'])) {
                $query->whereHas('orders.items.licenseKeys', function ($q) {
                    $q->where('status', 'active')
                        ->where(function ($sq) {
                            $sq->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                });
            }

            // Filter by has expiring license
            if (! empty($filters['has_expiring_license'])) {
                $days = $filters['expiring_days'] ?? 7;
                $query->whereHas('orders.items.licenseKeys', function ($q) use ($days) {
                    $q->where('status', 'active')
                        ->whereBetween('expires_at', [now(), now()->addDays($days)]);
                });
            }

            // Filter by LINE connected
            if (! empty($filters['has_line'])) {
                $query->withLineUid();
            }

            // Filter by last login
            if (! empty($filters['inactive_days'])) {
                $query->where('last_login_at', '<', now()->subDays($filters['inactive_days']));
            }
        }

        return $query->get();
    }

    /**
     * Send email to user.
     */
    private function sendEmail(User $user, string $subject, string $message): void
    {
        Mail::raw($message, function ($mail) use ($user, $subject) {
            $mail->to($user->email)
                ->subject($subject);
        });
    }

    /**
     * Send LINE message to user.
     */
    private function sendLineMessage(User $user, string $message): void
    {
        if (empty($user->line_uid)) {
            throw new \Exception('User does not have LINE UID');
        }

        $token = Setting::getValue('line_channel_access_token');
        if (empty($token)) {
            throw new \Exception('LINE Channel Access Token not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post('https://api.line.me/v2/bot/message/push', [
            'to' => $user->line_uid,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new \Exception('LINE API error: ' . $response->status());
        }
    }

    /**
     * Send LINE Flex Message for rich content.
     */
    public function sendLineFlexMessage(User $user, array $flexContent): void
    {
        if (empty($user->line_uid)) {
            throw new \Exception('User does not have LINE UID');
        }

        $token = Setting::getValue('line_channel_access_token');
        if (empty($token)) {
            throw new \Exception('LINE Channel Access Token not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post('https://api.line.me/v2/bot/message/push', [
            'to' => $user->line_uid,
            'messages' => [
                [
                    'type' => 'flex',
                    'altText' => $flexContent['altText'] ?? 'New notification',
                    'contents' => $flexContent['contents'],
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new \Exception('LINE API error: ' . $response->status());
        }
    }

    /**
     * Build product announcement Flex Message.
     */
    public function buildProductFlexMessage($product): array
    {
        return [
            'altText' => "สินค้าใหม่: {$product->name}",
            'contents' => [
                'type' => 'bubble',
                'hero' => $product->image ? [
                    'type' => 'image',
                    'url' => asset('storage/' . $product->image),
                    'size' => 'full',
                    'aspectRatio' => '20:13',
                    'aspectMode' => 'cover',
                ] : null,
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'สินค้าใหม่',
                            'weight' => 'bold',
                            'color' => '#1DB446',
                            'size' => 'sm',
                        ],
                        [
                            'type' => 'text',
                            'text' => $product->name,
                            'weight' => 'bold',
                            'size' => 'xl',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => '฿' . number_format($product->price, 0),
                            'size' => 'xl',
                            'color' => '#FF6B00',
                            'weight' => 'bold',
                            'margin' => 'md',
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'action' => [
                                'type' => 'uri',
                                'label' => 'ดูรายละเอียด',
                                'uri' => route('products.show', $product->slug),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
