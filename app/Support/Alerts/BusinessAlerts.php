<?php

namespace App\Support\Alerts;

use App\Models\Order;
use App\Models\Quotation;
use App\Models\RentalPayment;
use App\Models\SmsPaymentNotification;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\WalletTopup;
use App\Support\AdminAlerts;
use App\Support\Telegram\BotActions;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * The business events the owner must never miss — an order, a payment slip, money arriving, a
 * customer writing to us — turned into alert cards, and the same cards re-drawn when the thing they
 * describe changes (paid, rejected, handled), so the chat always shows the current state.
 *
 * Called from the model observers in App\Observers\Alerts and from the few controllers whose events
 * leave no row behind (the contact form, the AI chat). Every entry point swallows its own errors:
 * an alert must never be the reason a checkout fails.
 */
final class BusinessAlerts
{
    /**
     * Who acted, when a change comes from the admin Telegram bot (there is no web session there).
     * Web admin actions are read from auth().
     */
    public static ?string $actor = null;

    /** Set while deferred work runs, carrying "was it a person?" from the moment of the change. */
    private static ?bool $humanOverride = null;

    private const METHODS = [
        'bank_transfer' => 'โอนธนาคาร',
        'promptpay' => 'พร้อมเพย์',
        'wallet' => 'Wallet',
        'stripe' => 'บัตร (Stripe)',
        'credit_card' => 'บัตรเครดิต',
        'truemoney' => 'TrueMoney',
        'linepay' => 'LINE Pay',
        'manual' => 'ชำระเอง',
    ];

    private const ORDER_STATUS = [
        'pending' => 'รอชำระ',
        'verifying' => 'รอตรวจสลิป',
        'processing' => 'เงินเข้าแล้ว รอยืนยัน',
        'paid' => 'ชำระแล้ว',
        'confirmed' => 'ชำระแล้ว',
        'rejected' => 'ปฏิเสธแล้ว',
        'failed' => 'ปฏิเสธแล้ว',
        'expired' => 'หมดเวลาชำระ',
        'cancelled' => 'ยกเลิก',
        'refunded' => 'คืนเงินแล้ว',
    ];

    // =============================================================================== orders

    /**
     * A new order. Drawn after the response, from a fresh read: checkouts create the order, then add
     * its items and often pay it (wallet) in the same request, and the card should show all of that —
     * not the half-built row the "created" event saw.
     */
    public static function orderCreated(Order $order): void
    {
        self::later(function () use ($order) {
            $order = $order->fresh(['items']);
            if (! $order) {
                return;
            }
            AdminAlerts::send(
                self::orderCard($order, self::isPaid($order) ? 'ออเดอร์ใหม่ ชำระแล้ว' : 'ใบสั่งซื้อใหม่'),
                1440, subject: 'order:' . $order->id,
            );
        });
    }

    /** @param array<string,mixed> $changes the attributes the last save changed */
    public static function orderUpdated(Order $order, array $changes): void
    {
        // Changes in the request that created the order are already on its "new order" card; and a
        // save that touched nothing about payment (a status note, a license bind) is not news.
        if ($order->wasRecentlyCreated || ! array_intersect(array_keys($changes), ['payment_status', 'payment_slip', 'metadata'])) {
            return;
        }
        self::later(function () use ($order, $changes) {
            $order = $order->fresh(['items']);
            if (! $order) {
                return;
            }
            $subject = 'order:' . $order->id;

            // A slip arrived — on the order itself (cart checkout) or in its metadata (product checkouts).
            // Keyed by the slip itself, so re-saving the same order never re-announces it.
            $slip = self::orderSlip($order);
            if ($slip !== null && self::isPending($order)
                && (array_key_exists('payment_slip', $changes) || array_key_exists('metadata', $changes) || ($changes['payment_status'] ?? null) === 'verifying')) {
                AdminAlerts::send(
                    self::orderCard($order, 'ลูกค้าแนบสลิปแล้ว', $slip)->withKey('order:' . $order->id . ':slip:' . substr(sha1($slip), 0, 10)),
                    1440, subject: $subject,
                );

                return;
            }

            if (! array_key_exists('payment_status', $changes)) {
                return;
            }

            // Whatever happened, every card about this order should now say so.
            AdminAlerts::refresh($subject, self::orderCard($order));

            // The SMS matcher found the transfer but its mode says a person confirms: the one state
            // that is nothing BUT waiting on the owner — a fresh card with the buttons.
            if ($order->payment_status === 'processing') {
                AdminAlerts::send(self::orderCard($order, 'เงินเข้าตรงยอดแล้ว รอคุณยืนยัน')->withKey('order:' . $order->id . ':matched'), 1440, subject: $subject);

                return;
            }

            // Money that arrived without a person approving it (SMS match, Stripe, wallet) is news.
            if (self::isPaid($order) && ! self::human()) {
                AdminAlerts::send(self::orderCard($order, 'เงินเข้า')->withKey('order:' . $order->id . ':paid'), 1440, subject: $subject);
            }
        });
    }

    /** The card for an order in its current state. $headline overrides the state's own title. */
    public static function orderCard(Order $order, ?string $headline = null, ?string $slip = null): Alert
    {
        $status = (string) $order->payment_status;
        $amount = self::baht((float) ($order->payment_display_amount ?: $order->total));
        $open = self::isPending($order);

        $level = match (true) {
            self::isPaid($order) => Alert::OK,
            in_array($status, ['rejected', 'failed', 'expired', 'cancelled', 'refunded'], true) => Alert::INFO,
            default => Alert::MONEY,
        };
        $title = ($headline ?? match (true) {
            self::isPaid($order) => 'ชำระแล้ว',
            default => self::ORDER_STATUS[$status] ?? 'ใบสั่งซื้อ',
        }) . ' ' . $amount;
        // A card or Stripe payment that is still pending has nothing for a person to check — the
        // gateway decides it. Offering "confirm paid" there invites approving money that never came.
        $decidable = $open && ($slip !== null || self::orderSlip($order) !== null
            || in_array($status, ['verifying', 'processing'], true)
            || ! in_array($order->payment_method, ['stripe', 'credit_card', 'wallet'], true));

        $lines = ['ลูกค้า: ' . self::person($order->customer_name, $order->customer_email, $order->customer_phone)];
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();
        foreach ($items->take(4) as $item) {
            $lines[] = '• ' . Str::limit((string) $item->product_name, 48) . ' ×' . (int) $item->quantity . ' — ' . self::baht((float) $item->subtotal);
        }
        if ($items->count() > 4) {
            $lines[] = '• … และอีก ' . ($items->count() - 4) . ' รายการ';
        }
        if ($items->isEmpty() && ($what = self::metadataProduct($order)) !== null) {
            $lines[] = '• ' . $what;
        }
        $pay = self::METHODS[$order->payment_method] ?? (string) $order->payment_method;
        if ($order->coupon_code) {
            $pay .= ' · คูปอง ' . $order->coupon_code;
        }
        $lines[] = 'ชำระผ่าน: ' . $pay;
        if ($order->referral_code) {
            $lines[] = 'แนะนำโดย: ' . $order->referral_code;
        }
        if ($open && $slip !== null) {
            $lines[] = 'สลิปแนบอยู่ในข้อความถัดไป — ตรวจยอดกับบัญชีก่อนกดยืนยัน';
        }
        if (! $open && ($who = self::resolvedBy()) !== null) {
            $lines[] = (self::isPaid($order) ? 'ยืนยันโดย: ' : 'ดำเนินการโดย: ') . $who . ' · ' . self::time(now());
        }

        return new Alert(
            key: 'order:' . $order->id . ':' . $status,
            level: $level,
            title: $title,
            body: implode("\n", $lines),
            facts: [
                'ยอดชำระ' => $amount,
                'เลขที่' => (string) $order->order_number,
                'สถานะ' => self::ORDER_STATUS[$status] ?? $status,
            ],
            url: self::adminUrl('admin.orders.show', $order),
            urlLabel: 'เปิดใบสั่งซื้อ',
            category: 'orders',
            buttons: $decidable ? BotActions::decisionButtons('o', $order->id) : [],
            photo: $open ? self::slipPath($slip) : null,
        );
    }

    private static function isPaid(Order $order): bool
    {
        return in_array($order->payment_status, ['paid', 'confirmed'], true);
    }

    /** Still waiting on money, or on a person to check a slip or confirm a matched transfer. */
    public static function isPending(Order $order): bool
    {
        return in_array($order->payment_status, ['pending', 'verifying', 'processing'], true);
    }

    /** The slip on an order — on the row (cart checkout) or in its metadata (product checkouts). */
    public static function orderSlip(Order $order): ?string
    {
        $slip = $order->payment_slip ?: (self::meta($order)['payment_slip'] ?? null);

        return is_string($slip) && $slip !== '' ? $slip : null;
    }

    /**
     * An order's metadata as an array. Several product checkouts save json_encode()d text into the
     * array-cast column, so the cast hands back a JSON string rather than an array — decode both.
     */
    private static function meta(Order $order): array
    {
        $m = $order->metadata;
        if (is_string($m)) {
            $m = json_decode($m, true);
        }

        return is_array($m) ? $m : [];
    }

    /** What a product checkout bought, from its metadata, when it has no order_items. */
    private static function metadataProduct(Order $order): ?string
    {
        $m = self::meta($order);
        foreach (['package_name', 'product_name', 'plan_name', 'plan', 'product', 'license_type'] as $k) {
            if (! empty($m[$k]) && is_scalar($m[$k])) {
                return Str::limit((string) $m[$k], 60);
            }
        }

        return null;
    }

    // =============================================================================== wallet

    /** Only top-ups a person has to check are news when created; the rest are news when paid. */
    public static function topupCreated(WalletTopup $topup): void
    {
        self::later(function () use ($topup) {
            $topup = $topup->fresh();
            if (! $topup || $topup->payment_method !== WalletTopup::METHOD_TRUEMONEY || $topup->status !== WalletTopup::STATUS_PENDING) {
                return;
            }
            AdminAlerts::send(self::topupCard($topup, 'ขอเติมเงิน (ต้องตรวจเอง)'), 1440, subject: 'topup:' . $topup->id);
        });
    }

    public static function topupUpdated(WalletTopup $topup, array $changes): void
    {
        if ($topup->wasRecentlyCreated || ! array_intersect(array_keys($changes), ['status', 'sms_verification_status'])) {
            return;
        }
        self::later(function () use ($topup, $changes) {
            $topup = $topup->fresh();
            if (! $topup) {
                return;
            }
            // The SMS matcher found the transfer but its mode says a person approves.
            if (($changes['sms_verification_status'] ?? null) === 'matched' && $topup->status === WalletTopup::STATUS_PENDING) {
                AdminAlerts::send(self::topupCard($topup, 'ยอดโอนเติมเงินตรงแล้ว รอคุณอนุมัติ')->withKey('topup:' . $topup->id . ':matched'), 1440, subject: 'topup:' . $topup->id);

                return;
            }
            if (! array_key_exists('status', $changes)) {
                return;
            }
            AdminAlerts::refresh('topup:' . $topup->id, self::topupCard($topup));
            if ($topup->status === WalletTopup::STATUS_APPROVED && ! self::human()) {
                AdminAlerts::send(self::topupCard($topup, 'เติมเงินเข้า Wallet')->withKey('topup:' . $topup->id . ':paid'), 1440, subject: 'topup:' . $topup->id);
            }
        });
    }

    public static function topupCard(WalletTopup $topup, ?string $headline = null): Alert
    {
        $topup->loadMissing('user', 'approvedBy');
        $open = $topup->status === WalletTopup::STATUS_PENDING;
        $amount = self::baht((float) $topup->amount);

        $lines = ['ลูกค้า: ' . self::person($topup->user?->name, $topup->user?->email, null)];
        if ((float) $topup->bonus_amount > 0) {
            $lines[] = 'โบนัส: ' . self::baht((float) $topup->bonus_amount) . ' → เข้า Wallet ' . self::baht((float) $topup->total_amount);
        }
        $lines[] = 'ชำระผ่าน: ' . (self::METHODS[$topup->payment_method] ?? $topup->payment_method);
        if ($topup->status === WalletTopup::STATUS_REJECTED && $topup->reject_reason) {
            $lines[] = 'เหตุผลที่ปฏิเสธ: ' . Str::limit((string) $topup->reject_reason, 120);
        }
        if (! $open) {
            $who = $topup->approvedBy?->name ?? self::resolvedBy() ?? 'ระบบอัตโนมัติ';
            $lines[] = 'ดำเนินการโดย: ' . $who . ' · ' . self::time($topup->approved_at ?? now());
        }

        return new Alert(
            key: 'topup:' . $topup->id . ':' . $topup->status,
            level: match ($topup->status) {
                WalletTopup::STATUS_APPROVED => Alert::OK,
                WalletTopup::STATUS_PENDING => Alert::MONEY,
                default => Alert::INFO,
            },
            title: ($headline ?? match ($topup->status) {
                WalletTopup::STATUS_APPROVED => 'เติมเงินสำเร็จ',
                WalletTopup::STATUS_REJECTED => 'ปฏิเสธการเติมเงิน',
                WalletTopup::STATUS_EXPIRED => 'คำขอเติมเงินหมดอายุ',
                default => 'ขอเติมเงิน',
            }) . ' ' . $amount,
            body: implode("\n", $lines),
            facts: ['ยอดเติม' => $amount, 'เลขที่' => (string) $topup->topup_id, 'สถานะ' => (string) $topup->status_label],
            url: self::adminUrl('admin.wallets.topups.show', $topup),
            urlLabel: 'เปิดรายการเติมเงิน',
            category: 'orders',
            buttons: $open ? BotActions::decisionButtons('t', $topup->id) : [],
        );
    }

    // =============================================================================== rentals

    public static function rentalPaymentCreated(RentalPayment $payment): void
    {
        self::later(function () use ($payment) {
            $payment = $payment->fresh();
            if (! $payment || $payment->status !== RentalPayment::STATUS_PENDING || (float) $payment->amount <= 0) {
                return;
            }
            AdminAlerts::send(self::rentalCard($payment, 'สั่งเช่าแพ็กเกจ'), 1440, subject: 'rental:' . $payment->id);
        });
    }

    public static function rentalPaymentUpdated(RentalPayment $payment, array $changes): void
    {
        if ($payment->wasRecentlyCreated) {
            return;
        }
        self::later(function () use ($payment, $changes) {
            $payment = $payment->fresh();
            if (! $payment) {
                return;
            }
            $subject = 'rental:' . $payment->id;
            if (array_key_exists('transfer_slip_url', $changes) && $payment->transfer_slip_url && self::rentalOpen($payment)) {
                AdminAlerts::send(
                    self::rentalCard($payment, 'ลูกค้าแนบสลิปค่าเช่า')->withKey('rental:' . $payment->id . ':slip:' . substr(sha1((string) $payment->transfer_slip_url), 0, 10)),
                    1440, subject: $subject,
                );

                return;
            }
            if (! array_key_exists('status', $changes)) {
                return;
            }
            AdminAlerts::refresh($subject, self::rentalCard($payment));
            if ($payment->status === RentalPayment::STATUS_COMPLETED && ! self::human()) {
                AdminAlerts::send(self::rentalCard($payment, 'เงินเข้า ค่าเช่า')->withKey('rental:' . $payment->id . ':paid'), 1440, subject: $subject);
            }
        });
    }

    public static function rentalCard(RentalPayment $payment, ?string $headline = null): Alert
    {
        $payment->loadMissing('user', 'userRental.rentalPackage', 'verifier');
        $open = self::rentalOpen($payment);
        $amount = self::baht((float) $payment->amount);
        $package = $payment->userRental?->rentalPackage;
        $packageName = $package ? ($package->name_th ?: $package->name) : ((string) ($payment->description ?: 'แพ็กเกจเช่า'));

        $lines = [
            'ลูกค้า: ' . self::person($payment->user?->name, $payment->user?->email, null),
            'แพ็กเกจ: ' . Str::limit((string) $packageName, 60),
            'ชำระผ่าน: ' . (self::METHODS[$payment->payment_method] ?? $payment->payment_method),
        ];
        if (! $open) {
            $lines[] = 'ดำเนินการโดย: ' . ($payment->verifier?->name ?? self::resolvedBy() ?? 'ระบบอัตโนมัติ') . ' · ' . self::time($payment->verified_at ?? now());
        }

        return new Alert(
            key: 'rental:' . $payment->id . ':' . $payment->status,
            level: match ($payment->status) {
                RentalPayment::STATUS_COMPLETED => Alert::OK,
                RentalPayment::STATUS_PENDING, RentalPayment::STATUS_PROCESSING => Alert::MONEY,
                default => Alert::INFO,
            },
            title: ($headline ?? match ($payment->status) {
                RentalPayment::STATUS_COMPLETED => 'ชำระค่าเช่าแล้ว',
                RentalPayment::STATUS_FAILED => 'ปฏิเสธค่าเช่า',
                default => 'ค่าเช่ารอตรวจ',
            }) . ' ' . $amount,
            body: implode("\n", $lines),
            facts: ['ยอดชำระ' => $amount, 'อ้างอิง' => (string) $payment->payment_reference, 'สถานะ' => (string) $payment->status],
            url: self::adminUrl('admin.rentals.payments'),
            urlLabel: 'เปิดรายการค่าเช่า',
            category: 'orders',
            buttons: $open ? BotActions::decisionButtons('r', $payment->id) : [],
            photo: $open ? self::slipPath($payment->transfer_slip_url) : null,
        );
    }

    public static function rentalOpen(RentalPayment $payment): bool
    {
        return in_array($payment->status, [RentalPayment::STATUS_PENDING, RentalPayment::STATUS_PROCESSING], true);
    }

    // =============================================================================== quotations

    public static function quotationCreated(Quotation $q): void
    {
        self::guard(function () use ($q) {
            $isOrder = $q->action_type === 'order';
            $lines = [
                'ลูกค้า: ' . self::person($q->customer_name, $q->customer_email, $q->customer_phone) . ($q->customer_company ? ' · ' . $q->customer_company : ''),
                'บริการ: ' . Str::limit((string) $q->service_name, 70),
            ];
            if ($q->timeline) {
                $lines[] = 'ระยะเวลา: ' . $q->timeline;
            }
            if ($q->project_description) {
                $lines[] = '"' . Str::limit(trim((string) $q->project_description), 220) . '"';
            }

            AdminAlerts::send(new Alert(
                key: 'quotation:' . $q->id,
                level: Alert::MONEY,
                title: ($isOrder ? 'ลูกค้าสั่งงาน ' : 'ขอใบเสนอราคา ') . self::baht((float) $q->grand_total),
                body: implode("\n", $lines),
                facts: ['มูลค่า' => self::baht((float) $q->grand_total), 'เลขที่' => (string) $q->quote_number, 'ประเภท' => $isOrder ? 'สั่งงาน' : 'ใบเสนอราคา'],
                url: self::adminUrl('admin.quotations.detail', $q),
                urlLabel: 'เปิดใบเสนอราคา',
                category: 'orders',
                buttons: [[BotActions::ackButton('q' . $q->id)]],
            ), 1440, subject: 'quotation:' . $q->id);
        });
    }

    /**
     * The customer opened the quotation we e-mailed them.
     *
     * Sent once, on the first open — the controller only calls this when
     * viewed_at was still null. Quiet on purpose: it is a signal to follow up,
     * not news that needs a buzz.
     */
    public static function quotationViewed(Quotation $q): void
    {
        self::guard(function () use ($q) {
            AdminAlerts::send(new Alert(
                key: 'quotation-viewed:' . $q->id,
                level: Alert::INFO,
                title: 'ลูกค้าเปิดดูใบเสนอราคาแล้ว',
                body: self::person($q->customer_name, $q->customer_email, $q->customer_phone)
                    . ($q->customer_company ? ' · ' . $q->customer_company : '')
                    . "\n" . Str::limit((string) $q->service_name, 70),
                facts: ['เลขที่' => (string) $q->quote_number, 'มูลค่า' => self::baht((float) $q->grand_total)],
                url: self::adminUrl('admin.quotations.detail', $q),
                urlLabel: 'เปิดใบเสนอราคา',
                category: 'orders',
            ), 1440, subject: 'quotation:' . $q->id);
        });
    }

    /**
     * The customer answered: accepted, asked to renegotiate, or declined.
     *
     * The card carries what they typed, because for a renegotiation that
     * sentence IS the work item — it says which line to cut or what number to
     * come back with.
     */
    public static function quotationAnswered(Quotation $q, string $action, ?string $message = null): void
    {
        self::guard(function () use ($q, $action, $message) {
            [$title, $level] = match ($action) {
                'accept' => ['ลูกค้าตอบรับใบเสนอราคา ' . self::baht((float) $q->grand_total), Alert::MONEY],
                'decline' => ['ลูกค้าไม่รับข้อเสนอ', Alert::WARNING],
                default => ['ลูกค้าขอต่อรองราคา', Alert::MONEY],
            };

            $lines = [
                self::person($q->customer_name, $q->customer_email, $q->customer_phone)
                    . ($q->customer_company ? ' · ' . $q->customer_company : ''),
                Str::limit((string) $q->service_name, 70),
            ];
            if (trim((string) $message) !== '') {
                $lines[] = '"' . Str::limit(trim((string) $message), 500) . '"';
            }

            AdminAlerts::send(new Alert(
                key: 'quotation-answer:' . $q->id . ':' . $action,
                level: $level,
                title: $title,
                body: implode("\n", $lines),
                facts: [
                    'เลขที่' => (string) $q->quote_number,
                    'มูลค่า' => self::baht((float) $q->grand_total),
                    'คำตอบ' => match ($action) {
                        'accept' => 'ตอบรับ',
                        'decline' => 'ไม่รับ',
                        default => 'ขอต่อรอง',
                    },
                ],
                url: self::adminUrl('admin.quotations.detail', $q),
                urlLabel: 'เปิดใบเสนอราคา',
                category: 'orders',
                buttons: [[BotActions::ackButton('q' . $q->id)]],
            ), 60, subject: 'quotation:' . $q->id);
        });
    }

    // =============================================================================== contact

    /** The "contact us" form. Stored nowhere else — if the e-mail failed, this card IS the message. */
    public static function contactMessage(array $data, bool $mailed, ?string $ip): void
    {
        self::guard(function () use ($data, $mailed) {
            $lines = ['หัวข้อ: ' . Str::limit((string) ($data['subject'] ?? ''), 120), '"' . Str::limit(trim((string) ($data['message'] ?? '')), 700) . '"'];
            if (! $mailed) {
                $lines[] = "\nส่งอีเมลเข้ากล่องไม่สำเร็จ — ข้อความนี้มีอยู่ที่ Telegram เท่านั้น";
            }

            AdminAlerts::send(new Alert(
                key: 'contact:' . sha1(($data['email'] ?? '') . '|' . ($data['message'] ?? '')),
                level: $mailed ? Alert::MONEY : Alert::WARNING,
                title: 'ลูกค้าติดต่อผ่านหน้าติดต่อเรา',
                body: implode("\n", $lines),
                facts: array_filter([
                    'ชื่อ' => Str::limit((string) ($data['name'] ?? ''), 40),
                    'อีเมล' => (string) ($data['email'] ?? ''),
                    'โทร' => (string) ($data['phone'] ?? ''),
                ]),
                category: 'contact',
                buttons: [[BotActions::ackButton('c')]],
            ), 60);
        });
    }

    public static function ticketCreated(SupportTicket $t): void
    {
        self::guard(function () use ($t) {
            AdminAlerts::send(self::ticketCard($t, 'ตั๋วซัพพอร์ตใหม่', (string) $t->message), 1440, subject: 'ticket:' . $t->id);
        });
    }

    public static function ticketReplied(TicketReply $reply): void
    {
        self::guard(function () use ($reply) {
            $t = $reply->ticket;
            // Only the customer's own words are news; staff replies and internal notes are not.
            if (! $t || $reply->is_internal || (int) $reply->user_id !== (int) $t->user_id) {
                return;
            }
            AdminAlerts::send(self::ticketCard($t, 'ลูกค้าตอบกลับตั๋ว', (string) $reply->message)->withKey('ticket-reply:' . $reply->id), 1, subject: 'ticket:' . $t->id);
        });
    }

    public static function ticketCard(SupportTicket $t, string $headline, string $message): Alert
    {
        $t->loadMissing('user', 'assignedTo');
        $lines = ['หัวข้อ: ' . Str::limit((string) $t->subject, 120), '"' . Str::limit(trim($message), 600) . '"'];
        if ($t->assignedTo) {
            $lines[] = 'ผู้รับเรื่อง: ' . $t->assignedTo->name;
        }

        return new Alert(
            key: 'ticket:' . $t->id . ':' . $headline,
            level: $t->isUrgent() ? Alert::WARNING : Alert::MONEY,
            title: $headline . ' ' . $t->ticket_number,
            body: implode("\n", $lines),
            facts: [
                'ลูกค้า' => Str::limit((string) ($t->name ?: $t->user?->name), 30),
                'หมวด' => (string) $t->category_label,
                'ความสำคัญ' => (string) $t->priority_label,
            ],
            url: self::adminUrl('admin.support.show', $t),
            urlLabel: 'เปิดตั๋ว',
            category: 'contact',
            buttons: $t->assigned_to ? [] : [[BotActions::takeButton($t->id)]],
        );
    }

    /**
     * The website's AI chat: a visitor who leaves a phone number, an e-mail or a LINE id — or asks
     * for a person — is a lead the AI cannot close. Hand it to a human with the conversation so far.
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     */
    public static function aiChatLead(array $messages, string $path, ?string $ip): void
    {
        self::guard(function () use ($messages, $path, $ip) {
            $last = (string) (collect($messages)->where('role', 'user')->last()['content'] ?? '');
            $contact = self::contactIn($last);
            $wantsHuman = (bool) preg_match('/(ติดต่อ\s*(แอดมิน|admin|เจ้าหน้าที่|ทีมงาน|คน)|คุยกับ\s*(แอดมิน|admin|คน|เจ้าหน้าที่|ทีมงาน)|ขอสาย|โทรกลับ|ติดต่อกลับ|ขอเบอร์|อยากสั่ง|ต้องการสั่ง|จะสั่งซื้อ|สนใจจ้าง|อยากจ้าง|ขอใบเสนอราคา|human|real person|talk to (an? )?(admin|agent|human))/iu', $last);
            if ($contact === [] && ! $wantsHuman) {
                return;
            }

            $transcript = [];
            foreach (array_slice($messages, -6) as $m) {
                $who = ($m['role'] ?? '') === 'user' ? 'ลูกค้า' : 'AI';
                $transcript[] = $who . ': ' . Str::limit(trim(preg_replace('/\s+/u', ' ', (string) ($m['content'] ?? ''))), 160);
            }

            AdminAlerts::send(new Alert(
                // One card per visitor per contact detail (or per "wants a human") — not one per message.
                key: 'ai-lead:' . sha1(($ip ?? '') . '|' . ($contact !== [] ? implode(',', $contact) : 'human')),
                level: Alert::MONEY,
                title: $contact !== [] ? 'ลูกค้าในแชท AI ทิ้งช่องทางติดต่อไว้' : 'ลูกค้าในแชท AI ขอคุยกับทีมงาน',
                body: implode("\n", $transcript),
                facts: array_filter([
                    'ติดต่อกลับที่' => $contact !== [] ? implode(' · ', $contact) : null,
                    'หน้า' => Str::limit($path ?: '/', 40),
                ]),
                category: 'contact',
                buttons: [[BotActions::ackButton('a')]],
            ), 30);
        });
    }

    /** Phone numbers, e-mails and LINE ids in a chat message. @return array<int,string> */
    public static function contactIn(string $text): array
    {
        $found = [];
        if (preg_match_all('/(?<!\d)(?:\+?66[\s-]?|0)(?:[689]\d[\s-]?\d{3}[\s-]?\d{4}|[2-7][\s-]?\d{3}[\s-]?\d{4})(?!\d)/u', $text, $m)) {
            foreach ($m[0] as $phone) {
                $found[] = preg_replace('/[\s-]/', '', $phone);
            }
        }
        if (preg_match_all('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/u', $text, $m)) {
            array_push($found, ...$m[0]);
        }
        if (preg_match('/(?:line|ไลน์|ไลน)\s*(?:id|ไอดี)?\s*[:：]?\s*@?([A-Za-z0-9._-]{3,30})/iu', $text, $m)) {
            $found[] = 'LINE ' . $m[1];
        }

        return array_values(array_unique(array_slice($found, 0, 3)));
    }

    // =============================================================================== money signals

    /** Money arrived at the bank and no open bill claims it: a late payer, a wrong amount, a refund due. */
    public static function unmatchedCredit(SmsPaymentNotification $n): void
    {
        self::guard(function () use ($n) {
            $amount = (float) $n->amount;
            $near = Order::whereIn('payment_status', ['pending', 'verifying', 'expired'])
                ->whereBetween('total', [floor($amount) - 1, ceil($amount) + 1])
                ->where('created_at', '>=', now()->subDays(3))
                ->latest('id')->limit(3)->pluck('order_number')->all();

            AdminAlerts::send(new Alert(
                key: 'sms-unmatched:' . $n->id,
                level: Alert::WARNING,
                title: 'เงินโอนเข้า ' . self::baht($amount) . ' แต่ไม่ตรงกับบิลไหน',
                body: 'อาจเป็นลูกค้าโอนไม่ตรงยอด (ไม่มีเศษสตางค์) โอนหลังบิลหมดอายุ หรือโอนซ้ำ'
                    . ($near !== [] ? "\nบิลที่ยอดใกล้เคียง: " . implode(', ', $near) : '')
                    . "\nตรวจแล้วจับคู่เองได้ในหน้า SMS Payment",
                facts: array_filter([
                    'ยอดเข้า' => self::baht($amount),
                    'ธนาคาร' => strtoupper((string) $n->bank),
                    'จาก' => Str::limit((string) $n->sender_or_receiver, 24) ?: null,
                ]),
                url: self::adminUrl('admin.sms-payment.index'),
                urlLabel: 'เปิด SMS Payment',
                category: 'orders',
                buttons: [[BotActions::ackButton('s')]],
            ), 1440);
        });
    }

    /** A customer paid for AI credits and ai.xman4289.com never heard about it. */
    public static function aixmanCreditFailed(int $orderId, string $package, int $credits, string $reason): void
    {
        self::guard(function () use ($orderId, $package, $credits, $reason) {
            $order = Order::find($orderId);
            AdminAlerts::send(new Alert(
                key: 'aixman-credit:' . $orderId,
                level: Alert::CRITICAL,
                title: 'ส่งเครดิต AI ให้ลูกค้าไม่สำเร็จ',
                body: 'ลูกค้าจ่ายเงินแล้วแต่ ai.xman4289.com ไม่ได้รับแจ้ง — เครดิตยังไม่เข้าบัญชีลูกค้า'
                    . "\nสาเหตุ: " . Str::limit(Redact::text($reason), 200)
                    . "\nเติมเครดิตให้ลูกค้าเองในหน้าแอดมิน AI หรือส่ง webhook ซ้ำ",
                facts: array_filter([
                    'ออเดอร์' => $order?->order_number ?? '#' . $orderId,
                    'แพ็กเกจ' => Str::limit($package, 30),
                    'เครดิต' => number_format($credits),
                ]),
                url: $order ? self::adminUrl('admin.orders.show', $order) : null,
                urlLabel: 'เปิดใบสั่งซื้อ',
                category: 'orders',
            ), 360);
        });
    }

    // =============================================================================== helpers

    /** Was this change made by a person (web admin or the Telegram bot), rather than the system? */
    public static function human(): bool
    {
        if (self::$humanOverride !== null) {
            return self::$humanOverride;
        }
        if (self::$actor !== null) {
            return true;
        }
        try {
            return (bool) auth()->user()?->isAdmin();
        } catch (Throwable) {
            return false;
        }
    }

    private static function resolvedBy(): ?string
    {
        if (self::$actor !== null) {
            return self::$actor;
        }
        try {
            $user = auth()->user();

            return $user?->isAdmin() ? (string) $user->name : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function person(?string $name, ?string $email, ?string $phone): string
    {
        return implode(' · ', array_filter([
            $name ? Str::limit(trim($name), 40) : null,
            $phone ? trim($phone) : null,
            $email ? trim($email) : null,
        ])) ?: '—';
    }

    public static function baht(float $v): string
    {
        return '฿' . (floor($v) == $v ? number_format($v) : number_format($v, 2));
    }

    private static function time(CarbonInterface $at): string
    {
        return $at->copy()->setTimezone('Asia/Bangkok')->format('d/m H:i') . ' น.';
    }

    /** A route when it exists — a renamed route must not cost the alert. */
    private static function adminUrl(string $name, mixed $param = null): ?string
    {
        try {
            return $param === null ? route($name) : route($name, $param);
        } catch (Throwable) {
            return url('/admin');
        }
    }

    /**
     * The slip image on the public disk, as a real path we are willing to upload — only inside the
     * public storage folder (the value comes from a row, and a row is not a place to trust paths from).
     */
    private static function slipPath(?string $stored): ?string
    {
        if (! $stored) {
            return null;
        }
        try {
            $relative = ltrim(preg_replace('~^(https?://[^/]+)?/?storage/~', '', $stored) ?? '', '/');
            $root = realpath(Storage::disk('public')->path(''));
            $real = realpath(Storage::disk('public')->path($relative));
        } catch (Throwable) {
            return null;
        }
        if ($root === false || $real === false || ! str_starts_with($real, $root . DIRECTORY_SEPARATOR) || ! is_file($real)) {
            return null;
        }

        return filesize($real) <= 10 * 1024 * 1024 && preg_match('/\.(jpe?g|png|webp)$/i', $real) ? $real : null;
    }

    /**
     * Build and send after the response, from the committed state — but judged by who made the
     * change NOW, while the admin who pressed the button (or the web session) is still known.
     */
    private static function later(callable $fn): void
    {
        try {
            if (! AdminAlerts::enabled()) {
                return;
            }
        } catch (Throwable) {
            return;
        }
        $actor = self::resolvedBy();
        $human = self::human();
        AdminAlerts::afterResponse(function () use ($fn, $actor, $human) {
            [$prevActor, $prevHuman] = [self::$actor, self::$humanOverride];
            [self::$actor, self::$humanOverride] = [$actor, $human];
            try {
                self::guard($fn);
            } finally {
                [self::$actor, self::$humanOverride] = [$prevActor, $prevHuman];
            }
        }, 'business-alert');
    }

    /**
     * Run an alert builder only when alerts are on, and never let it throw. Failures are logged
     * directly — not re-thrown into the exception handler, which would try to alert about them.
     */
    private static function guard(callable $fn): void
    {
        try {
            if (AdminAlerts::enabled()) {
                $fn();
            }
        } catch (Throwable $e) {
            try {
                Log::warning('admin-alert: building the alert failed', ['error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
            } catch (Throwable) {
            }
        }
    }
}
