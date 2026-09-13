<?php

namespace App\Support\Telegram;

use App\Models\Order;
use App\Models\RentalPayment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WalletTopup;
use App\Services\OrderPaymentService;
use App\Services\RentalService;
use App\Support\AdminAlerts;
use App\Support\Alerts\Alert;
use App\Support\Alerts\BusinessAlerts;
use App\Support\Alerts\Reports;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The buttons under alert cards, and what happens when an admin presses one.
 *
 * A button carries "<action>:<arg>:<signature>" — Telegram allows 64 bytes, so the thing itself
 * (the order, the top-up) is looked up fresh from the database, never trusted from the button.
 * The signature is an HMAC under the app key: a modified Telegram client CAN send any callback
 * data it likes, so the data alone proves nothing. And the presser must be a linked admin
 * ([TelegramAdmins]) — being in the chat is not enough.
 *
 * Money actions take two presses ("อนุมัติ" → "ยืนยันอนุมัติ") and run under a lock with the row
 * re-read inside it: a double-tap, or two admins pressing at once, approves exactly once. They
 * call the same services the admin pages do, so an approval from a phone issues the same licenses
 * and e-mails as one from the website.
 *
 * Actions:
 *   oy/on  → ask to confirm approve/reject an order     oY/oN → do it     ob → back to the buttons
 *   ty/tn, tY/tN, tb → the same for a wallet top-up
 *   ry/rn, rY/rN, rb → the same for a rental payment
 *   oc/tc/rc → send that item's card (from /pending)     tk → take a support ticket
 *   ak → "received"   mu → mute this alert 24 h   rp → a report   qt → quiet hours   nop → nothing
 */
final class BotActions
{
    /** "oy:42" + ":" + 10 signature chars — well inside Telegram's 64 bytes. */
    public static function sign(string $action, string $arg = ''): string
    {
        $data = $action . ':' . $arg . ':' . self::signature($action, $arg);

        return strlen($data) <= 64 ? $data : 'nop::' . self::signature('nop', '');
    }

    /** @return array{0:string,1:string}|null [action, arg] when the signature holds */
    public static function verify(string $data): ?array
    {
        $parts = explode(':', $data);
        if (count($parts) !== 3) {
            return null;
        }
        [$action, $arg, $sig] = $parts;

        return hash_equals(self::signature($action, $arg), $sig) ? [$action, $arg] : null;
    }

    private static function signature(string $action, string $arg): string
    {
        $mac = hash_hmac('sha256', 'telegram-action|' . $action . '|' . $arg, (string) config('app.key'), true);

        return substr(rtrim(strtr(base64_encode($mac), '+/', '-_'), '='), 0, 10);
    }

    // ================================================================================ buttons

    /** Approve / reject for an order ('o'), a top-up ('t') or a rental payment ('r'). */
    public static function decisionButtons(string $kind, int $id): array
    {
        $approve = match ($kind) {
            't' => '✅ อนุมัติเติมเงิน',
            default => '✅ ยืนยันชำระแล้ว',
        };

        return [[
            ['text' => $approve, 'data' => self::sign($kind . 'y', (string) $id)],
            ['text' => '❌ ปฏิเสธ', 'data' => self::sign($kind . 'n', (string) $id)],
        ]];
    }

    /** "Received" — for alerts with nothing to approve (a contact message, a login from a new network). */
    public static function ackButton(string $tag, string $text = '✅ รับเรื่องแล้ว'): array
    {
        return ['text' => $text, 'data' => self::sign('ak', preg_replace('/[^A-Za-z0-9]/', '', $tag) ?? '')];
    }

    public static function takeButton(int $ticketId): array
    {
        return ['text' => '✋ รับเรื่องนี้', 'data' => self::sign('tk', (string) $ticketId)];
    }

    public static function muteButton(string $alertKey): array
    {
        return ['text' => '🔕 ปิดเสียงเรื่องนี้ 24 ชม.', 'data' => self::sign('mu', AdminAlerts::keyHash($alertKey))];
    }

    /** The /help menu. */
    public static function menu(): array
    {
        return ['inline_keyboard' => [
            [['text' => '📊 วันนี้', 'callback_data' => self::sign('rp', 'today')], ['text' => '📈 7 วัน', 'callback_data' => self::sign('rp', 'week')], ['text' => '🗓 6 เดือน', 'callback_data' => self::sign('rp', 'months')]],
            [['text' => '⏳ งานค้าง', 'callback_data' => self::sign('rp', 'pending')], ['text' => '🛡 ความปลอดภัย', 'callback_data' => self::sign('rp', 'security')], ['text' => '🩺 สถานะระบบ', 'callback_data' => self::sign('rp', 'status')]],
            [['text' => '🌙 เงียบ 8 ชม.', 'callback_data' => self::sign('qt', '480')], ['text' => '🔔 เปิดเสียง', 'callback_data' => self::sign('qt', '0')]],
        ]];
    }

    // ================================================================================ dispatch

    /** Handle one callback_query from the webhook. Never throws. */
    public static function handle(array $cb): void
    {
        $id = (string) ($cb['id'] ?? '');
        try {
            self::dispatch($cb, $id);
        } catch (Throwable $e) {
            Log::warning('telegram: action failed', ['error' => TelegramBot::redact($e->getMessage()), 'at' => $e->getFile() . ':' . $e->getLine()]);
            TelegramBot::answerCallback($id, 'ทำรายการไม่สำเร็จ — ลองใหม่ หรือทำในหน้าแอดมิน', true);
        } finally {
            BusinessAlerts::$actor = null;
        }
    }

    private static function dispatch(array $cb, string $id): void
    {
        $parsed = self::verify((string) ($cb['data'] ?? ''));
        if ($parsed === null) {
            TelegramBot::answerCallback($id, 'ปุ่มนี้ใช้ไม่ได้แล้ว', true);

            return;
        }
        [$action, $arg] = $parsed;
        if ($action === 'nop') {
            TelegramBot::answerCallback($id);

            return;
        }

        $from = (array) ($cb['from'] ?? []);
        $admin = TelegramAdmins::user($from['id'] ?? null);
        if ($admin === null) {
            TelegramBot::answerCallback($id, '⛔ บัญชี Telegram นี้ยังไม่ได้ผูกกับแอดมิน — ผูกได้ที่หน้าเว็บ: แอดมิน → แจ้งเตือน Telegram', true);

            return;
        }
        // Thirty presses a minute is far beyond a person; anything past it is a script.
        $slot = 'tg:actions:' . $admin->id . ':' . now()->format('YmdHi');
        Cache::add($slot, 0, now()->addMinutes(2));
        if (Cache::increment($slot) > 30) {
            TelegramBot::answerCallback($id, 'กดถี่เกินไป รอสักครู่', true);

            return;
        }

        // Everything downstream (observers, services, card text) sees who is acting.
        BusinessAlerts::$actor = (string) $admin->name;
        Auth::setUser($admin);

        $msg = (array) ($cb['message'] ?? []);
        $chat = (string) ($msg['chat']['id'] ?? '');
        $messageId = (int) ($msg['message_id'] ?? 0);
        $thread = isset($msg['message_thread_id']) ? (int) $msg['message_thread_id'] : null;
        $keep = self::urlRows((array) ($msg['reply_markup']['inline_keyboard'] ?? []));

        $kind = $action[0];
        $step = substr($action, 1);

        if (in_array($kind, ['o', 't', 'r'], true) && in_array($step, ['y', 'n', 'Y', 'N', 'b', 'c'], true)) {
            self::money($kind, $step, (int) $arg, $admin, $id, $chat, $messageId, $thread, $keep);

            return;
        }

        match ($action) {
            'ak' => self::stamp($id, $chat, $messageId, $keep, '✅ รับเรื่องแล้ว — ' . $admin->name . ' · ' . now('Asia/Bangkok')->format('H:i'), 'บันทึกแล้วว่าคุณรับเรื่องนี้'),
            'tk' => self::takeTicket((int) $arg, $admin, $id, $chat, $messageId, $keep),
            'mu' => self::mute($arg, $admin, $id, $chat, $messageId, $keep),
            'rp' => self::report($arg, $id, $chat, $thread),
            'qt' => self::quiet((int) $arg, $id, $chat, $thread),
            default => TelegramBot::answerCallback($id, 'ไม่รู้จักคำสั่งนี้', true),
        };
    }

    // ================================================================================ money

    private static function money(string $kind, string $step, int $id, User $admin, string $cbId, string $chat, int $messageId, ?int $thread, array $keep): void
    {
        $label = ['o' => 'ใบสั่งซื้อ', 't' => 'รายการเติมเงิน', 'r' => 'ค่าเช่า'][$kind];

        // First press: ask. Nothing changes until the second.
        if ($step === 'y' || $step === 'n') {
            $yes = $step === 'y';
            TelegramBot::editKeyboard($chat, $messageId, ['inline_keyboard' => array_merge([
                [['text' => $yes ? '✅ ยืนยัน — อนุมัติเลย' : '❌ ยืนยัน — ปฏิเสธ', 'callback_data' => self::sign($kind . ($yes ? 'Y' : 'N'), (string) $id)]],
                [['text' => '↩️ ยกเลิก', 'callback_data' => self::sign($kind . 'b', (string) $id)]],
            ], $keep)]);
            TelegramBot::answerCallback($cbId, $yes ? 'กดยืนยันอีกครั้งเพื่ออนุมัติ' : 'กดยืนยันอีกครั้งเพื่อปฏิเสธ');

            return;
        }

        if ($step === 'c') {
            // From /pending: send this item's full card, with its own buttons.
            $alert = self::cardFor($kind, $id);
            TelegramBot::answerCallback($cbId, $alert ? '' : 'ไม่พบ' . $label . 'นี้แล้ว');
            if ($alert !== null) {
                Reports::deliver($alert, $chat, $thread);
            }

            return;
        }

        $lock = Cache::lock('tg-action:' . $kind . ':' . $id, 30);
        if (! $lock->get()) {
            TelegramBot::answerCallback($cbId, 'มีคนกำลังทำรายการนี้อยู่ รอสักครู่', true);

            return;
        }
        try {
            if ($step === 'b') {
                $alert = self::cardFor($kind, $id);
                TelegramBot::editKeyboard($chat, $messageId, $alert ? TelegramBot::keyboardFor($alert) : ['inline_keyboard' => $keep]);
                TelegramBot::answerCallback($cbId, 'ยกเลิกแล้ว');

                return;
            }

            $approve = $step === 'Y';
            [$done, $message] = match ($kind) {
                'o' => self::decideOrder($id, $approve),
                't' => self::decideTopup($id, $approve, $admin),
                'r' => self::decideRental($id, $approve, $admin),
            };
            if ($done) {
                // Who decided goes to the log; what the customer may see stays neutral.
                Log::info('telegram: payment decision', ['kind' => $kind, 'id' => $id, 'approve' => $approve, 'admin_id' => $admin->id]);
            }

            // The card itself is re-drawn by the model observer (and so is every other card about
            // this item); the buttons go now, so nobody presses them again in the meantime.
            if ($done) {
                self::stampRows($chat, $messageId, $keep, ($approve ? '✅ อนุมัติแล้ว — ' : '❌ ปฏิเสธแล้ว — ') . $admin->name . ' · ' . now('Asia/Bangkok')->format('H:i'));
            } else {
                $alert = self::cardFor($kind, $id);
                TelegramBot::editKeyboard($chat, $messageId, $alert ? TelegramBot::keyboardFor($alert) : ['inline_keyboard' => $keep]);
            }
            TelegramBot::answerCallback($cbId, $message, ! $done);
        } finally {
            $lock->release();
        }
    }

    /** What the customer sees as the reason when a payment is refused from the chat. */
    private const REJECT_REASON = 'ตรวจไม่พบยอดโอนที่ตรงกับรายการนี้ — กรุณาติดต่อทีมงาน';

    /** @return array{0:bool,1:string} */
    private static function decideOrder(int $id, bool $approve): array
    {
        $result = [false, 'ไม่พบใบสั่งซื้อนี้แล้ว'];
        DB::transaction(function () use ($id, $approve, &$result) {
            // Re-read under the row lock: the page, the SMS matcher or another admin may have
            // decided this order a moment ago.
            $order = Order::whereKey($id)->lockForUpdate()->first();
            if (! $order) {
                return;
            }
            if (! BusinessAlerts::isPending($order)) {
                $result = [false, 'ใบสั่งซื้อ ' . $order->order_number . ' ถูกดำเนินการไปแล้ว (สถานะ: ' . $order->payment_status . ')'];

                return;
            }

            // An SMS-payment order is decided the way the SMS Payment page decides it (the SMS record
            // and the SmsChecker phone app follow); any other order the way the Orders page does.
            $payments = app(OrderPaymentService::class);
            match (true) {
                $order->usesSmsPayment() && $approve => $payments->confirmSmsOrder($order),
                $order->usesSmsPayment() => $payments->rejectSmsOrder($order, self::REJECT_REASON),
                $approve => $payments->approve($order, 'ยืนยันการชำระผ่าน Telegram'),
                default => $payments->reject($order, 'ปฏิเสธการชำระผ่าน Telegram'),
            };
            $result = [true, ($approve ? '✅ ยืนยันชำระ ' : '❌ ปฏิเสธ ') . $order->order_number . ' แล้ว'];
        });

        return $result;
    }

    /** @return array{0:bool,1:string} */
    private static function decideTopup(int $id, bool $approve, User $admin): array
    {
        $done = false;
        $number = '';
        DB::transaction(function () use ($id, $approve, $admin, &$done, &$number) {
            $topup = WalletTopup::find($id);
            if (! $topup) {
                return;
            }
            $number = (string) $topup->topup_id;
            // approve()/reject() claim the row under its lock — a second press gets false.
            $done = $approve ? $topup->approve((int) $admin->id) : $topup->reject((int) $admin->id, self::REJECT_REASON);
        });

        return match (true) {
            $number === '' => [false, 'ไม่พบรายการเติมเงินนี้แล้ว'],
            ! $done => [false, 'รายการ ' . $number . ' ถูกดำเนินการไปแล้ว'],
            default => [true, ($approve ? '✅ อนุมัติเติมเงิน ' : '❌ ปฏิเสธ ') . $number . ' แล้ว'],
        };
    }

    /** @return array{0:bool,1:string} */
    private static function decideRental(int $id, bool $approve, User $admin): array
    {
        $result = [false, 'ไม่พบรายการค่าเช่านี้แล้ว'];
        DB::transaction(function () use ($id, $approve, $admin, &$result) {
            $payment = RentalPayment::whereKey($id)->lockForUpdate()->first();
            if (! $payment) {
                return;
            }
            if (! BusinessAlerts::rentalOpen($payment)) {
                $result = [false, 'รายการ ' . $payment->payment_reference . ' ถูกดำเนินการไปแล้ว'];

                return;
            }
            $service = app(RentalService::class);
            if ($approve) {
                $verified = $service->verifyBankTransfer($payment, (int) $admin->id, 'ยืนยันผ่าน Telegram');
                if (! ($verified['success'] ?? false)) {
                    $result = [false, (string) ($verified['error'] ?? 'ยืนยันไม่สำเร็จ')];

                    return;
                }
            } else {
                $service->rejectPayment($payment, self::REJECT_REASON);
            }
            $result = [true, ($approve ? '✅ ยืนยันค่าเช่า ' : '❌ ปฏิเสธ ') . $payment->payment_reference . ' แล้ว'];
        });

        return $result;
    }

    private static function cardFor(string $kind, int $id): ?Alert
    {
        return match ($kind) {
            'o' => ($o = Order::find($id)) ? BusinessAlerts::orderCard($o) : null,
            't' => ($t = WalletTopup::find($id)) ? BusinessAlerts::topupCard($t) : null,
            'r' => ($p = RentalPayment::find($id)) ? BusinessAlerts::rentalCard($p) : null,
            default => null,
        };
    }

    // ================================================================================ the rest

    private static function takeTicket(int $id, User $admin, string $cbId, string $chat, int $messageId, array $keep): void
    {
        $ticket = SupportTicket::find($id);
        if (! $ticket) {
            TelegramBot::answerCallback($cbId, 'ไม่พบตั๋วนี้แล้ว', true);

            return;
        }
        if ($ticket->assigned_to && (int) $ticket->assigned_to !== (int) $admin->id) {
            $who = $ticket->assignedTo?->name ?? 'คนอื่น';
            self::stampRows($chat, $messageId, $keep, '✋ ' . $who . ' รับเรื่องนี้แล้ว');
            TelegramBot::answerCallback($cbId, $who . ' รับเรื่องนี้ไปแล้ว', true);

            return;
        }
        $ticket->assignTo($admin);
        self::stamp($cbId, $chat, $messageId, $keep, '✋ รับเรื่องโดย ' . $admin->name . ' · ' . now('Asia/Bangkok')->format('H:i'), 'ตั๋ว ' . $ticket->ticket_number . ' เป็นของคุณแล้ว');
    }

    private static function mute(string $hash, User $admin, string $cbId, string $chat, int $messageId, array $keep): void
    {
        if (! preg_match('/^[0-9a-f]{12}$/', $hash)) {
            TelegramBot::answerCallback($cbId, 'ปุ่มนี้ใช้ไม่ได้แล้ว', true);

            return;
        }
        AdminAlerts::muteHash($hash, 24);
        self::stamp($cbId, $chat, $messageId, $keep, '🔕 ปิดเสียงเรื่องนี้ 24 ชม. — ' . $admin->name, 'เรื่องนี้จะไม่แจ้งอีก 24 ชม.');
    }

    private static function report(string $which, string $cbId, string $chat, ?int $thread): void
    {
        TelegramBot::answerCallback($cbId, 'กำลังวาดรายงาน…');
        BotCommands::report($which, $chat, $thread);
    }

    private static function quiet(int $minutes, string $cbId, string $chat, ?int $thread): void
    {
        AdminAlerts::setQuiet($minutes > 0 ? min($minutes, 24 * 60) : null);
        TelegramBot::answerCallback($cbId, $minutes > 0 ? 'เงียบ ' . intdiv($minutes, 60) . ' ชม. (เรื่องด่วนยังดังอยู่)' : 'เปิดเสียงแจ้งเตือนแล้ว');
        TelegramBot::sendText($chat, $minutes > 0
            ? '🌙 <b>โหมดเงียบ ' . intdiv($minutes, 60) . ' ชม.</b> — แจ้งเตือนยังเข้าครบ แต่ไม่มีเสียง ยกเว้นเรื่องด่วน (🚨)'
            : '🔔 <b>เปิดเสียงแจ้งเตือนแล้ว</b>', null, $thread);
    }

    /** Replace the action buttons with a one-line record of who did what, keeping the link rows. */
    private static function stamp(string $cbId, string $chat, int $messageId, array $keep, string $line, string $toast): void
    {
        self::stampRows($chat, $messageId, $keep, $line);
        TelegramBot::answerCallback($cbId, $toast);
    }

    private static function stampRows(string $chat, int $messageId, array $keep, string $line): void
    {
        TelegramBot::editKeyboard($chat, $messageId, ['inline_keyboard' => array_merge(
            [[['text' => $line, 'callback_data' => self::sign('nop')]]],
            $keep,
        )]);
    }

    /** The link buttons of a keyboard — the part of it an action never changes. */
    private static function urlRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $links = array_values(array_filter((array) $row, fn ($b) => is_array($b) && isset($b['url'])));
            if ($links !== []) {
                $out[] = array_map(fn ($b) => ['text' => (string) $b['text'], 'url' => (string) $b['url']], $links);
            }
        }

        return $out;
    }
}
