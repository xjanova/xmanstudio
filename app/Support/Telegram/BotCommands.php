<?php

namespace App\Support\Telegram;

use App\Support\AdminAlerts;
use App\Support\Alerts\Alert;
use App\Support\Alerts\Reports;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Text commands to the admin bot — the "/" menu: reports drawn on demand, the work queue, quiet
 * hours, and "/start link_<code>", which is how an admin's Telegram account gets linked.
 *
 * Only linked admins ([TelegramAdmins]) get answers beyond /id and the link step. Everyone else
 * hears "not linked" at most once per 10 minutes — a bot that answers every stranger is a bot that
 * can be used to spam.
 */
final class BotCommands
{
    /** command => description, as registered with setMyCommands. */
    public const MENU = [
        'today' => 'ยอดขายวันนี้ พร้อมกราฟ 7 วัน',
        'week' => 'ยอดขาย 7 วันล่าสุด',
        'month' => 'ยอดขายรายเดือน ย้อนหลัง 6 เดือน',
        'pending' => 'งานที่รอคุณ: สลิป เติมเงิน ค่าเช่า ตั๋ว',
        'security' => 'เหตุการณ์ความปลอดภัยวันนี้',
        'status' => 'สถานะเซิร์ฟเวอร์และระบบ',
        'quiet' => 'โหมดเงียบ เช่น /quiet 8 หรือ /quiet off',
        'help' => 'เมนูทั้งหมด',
        'id' => 'ดู Chat ID และ User ID',
    ];

    public static function handle(array $message): void
    {
        $chat = (array) ($message['chat'] ?? []);
        $from = (array) ($message['from'] ?? []);
        if (! isset($chat['id'])) {
            return;
        }
        TelegramBot::rememberChat($chat, $from);

        $chatId = (string) $chat['id'];
        $thread = ! empty($message['is_topic_message']) && isset($message['message_thread_id']) ? (int) $message['message_thread_id'] : null;
        $private = ($chat['type'] ?? '') === 'private';
        $text = trim((string) ($message['text'] ?? ''));

        if ($text === '' || ! str_starts_with($text, '/')) {
            if ($private && Cache::add('tg:hint:' . $chatId, 1, now()->addMinutes(10))) {
                TelegramBot::sendText($chatId, 'พิมพ์ /help เพื่อดูคำสั่งทั้งหมดของบอทแจ้งเตือน XMAN Studio');
            }

            return;
        }

        // "/today@XmanAlertBot 8" → today, "8". A command addressed to another bot in a group is not ours.
        if (! preg_match('~^/([A-Za-z0-9_]{1,32})(?:@([A-Za-z0-9_]{3,64}))?(?:\s+(.*))?$~s', $text, $m)) {
            return;
        }
        $command = strtolower($m[1]);
        $addressedTo = $m[2] ?? '';
        $arg = trim($m[3] ?? '');
        if ($addressedTo !== '' && TelegramBot::username() !== '' && strcasecmp($addressedTo, TelegramBot::username()) !== 0) {
            return;
        }

        if ($command === 'start' && str_starts_with($arg, 'link_')) {
            self::link(substr($arg, 5), $from, $chatId, $private);

            return;
        }
        if ($command === 'id') {
            if (Cache::add('tg:id:' . $chatId, 1, now()->addSeconds(10))) {
                TelegramBot::sendText($chatId, 'Chat ID: <code>' . e($chatId) . '</code>' . "\n" . 'User ID ของคุณ: <code>' . e((string) ($from['id'] ?? '—')) . '</code>', null, $thread);
            }

            return;
        }

        $admin = TelegramAdmins::user($from['id'] ?? null);
        if ($admin === null) {
            if (Cache::add('tg:denied:' . ($from['id'] ?? $chatId), 1, now()->addMinutes(10))) {
                TelegramBot::sendText($chatId, '⛔ คำสั่งนี้สำหรับแอดมินที่ผูกบัญชี Telegram แล้วเท่านั้น' . "\n" . 'ผูกได้ที่หน้าเว็บ: แอดมิน → แจ้งเตือน Telegram → "ผูกบัญชี Telegram ของฉัน"', null, $thread);
            }

            return;
        }

        match ($command) {
            'start', 'help', 'menu' => self::help($chatId, $thread, (string) $admin->name),
            'today', 'yesterday', 'week', 'month', 'months', 'pending', 'status', 'security' => self::report($command, $chatId, $thread),
            'quiet' => self::quiet($arg, $chatId, $thread),
            default => TelegramBot::sendText($chatId, 'ไม่รู้จักคำสั่ง /' . e($command) . ' — พิมพ์ /help เพื่อดูเมนู', null, $thread),
        };
    }

    /** Draw and send a report card; /pending also lists the items as buttons. */
    public static function report(string $which, string $chat, ?int $thread): void
    {
        $now = CarbonImmutable::now('Asia/Bangkok');
        $alert = match ($which) {
            'today' => Reports::day($now, soFar: true),
            'yesterday' => Reports::day($now->subDay()),
            'week' => Reports::week(),
            'month', 'months' => Reports::months(),
            'pending' => Reports::pending(),
            'status' => Reports::status(),
            'security' => Reports::security(),
            default => null,
        };
        if (! $alert instanceof Alert) {
            return;
        }
        $error = Reports::deliver($alert, $chat, $thread);

        if ($which === 'pending' && $error === null && ($items = Reports::pendingItems()) !== []) {
            $rows = array_map(fn ($item) => [['text' => $item[0], 'callback_data' => BotActions::sign($item[1] . 'c', (string) $item[2])]], $items);
            TelegramBot::sendText($chat, '<b>แตะรายการเพื่อเปิดการ์ด</b> (พร้อมปุ่มยืนยัน/ปฏิเสธ)', ['inline_keyboard' => $rows], $thread);
        }
    }

    private static function help(string $chat, ?int $thread, string $name): void
    {
        $lines = ['🤖 <b>บอทแจ้งเตือน XMAN Studio</b> — สวัสดีคุณ ' . e($name), ''];
        foreach (self::MENU as $command => $description) {
            $lines[] = '/' . $command . ' — ' . e($description);
        }
        $quiet = AdminAlerts::quietUntil();
        if ($quiet !== null) {
            $lines[] = '';
            $lines[] = '🌙 ตอนนี้อยู่ในโหมดเงียบถึง ' . CarbonImmutable::createFromTimestamp($quiet, 'Asia/Bangkok')->format('H:i') . ' น.';
        }
        TelegramBot::sendText($chat, implode("\n", $lines), BotActions::menu(), $thread);
    }

    private static function quiet(string $arg, string $chat, ?int $thread): void
    {
        $arg = strtolower(trim($arg));
        if (in_array($arg, ['off', 'ปิด', '0', 'stop'], true)) {
            AdminAlerts::setQuiet(null);
            TelegramBot::sendText($chat, '🔔 <b>เปิดเสียงแจ้งเตือนแล้ว</b>', null, $thread);

            return;
        }
        $hours = is_numeric($arg) ? max(1, min(24, (int) $arg)) : 8;
        AdminAlerts::setQuiet($hours * 60);
        TelegramBot::sendText($chat, '🌙 <b>โหมดเงียบ ' . $hours . ' ชม.</b> — แจ้งเตือนยังเข้าครบทุกเรื่อง แต่ไม่มีเสียง ยกเว้นเรื่องด่วน (🚨)' . "\n" . 'เปิดเสียงคืน: /quiet off', null, $thread);
    }

    /** "/start link_<code>" from the admin page's link: bind this Telegram account to that admin. */
    private static function link(string $code, array $from, string $chat, bool $private): void
    {
        $telegramId = (string) ($from['id'] ?? '');
        if ($telegramId === '' || ! $private) {
            TelegramBot::sendText($chat, 'ผูกบัญชีได้เฉพาะในแชทส่วนตัวกับบอท — เปิดลิงก์จากหน้าแอดมินอีกครั้ง');

            return;
        }
        // Codes are 24 random characters and live 10 minutes; this only stops a script from trying.
        $tries = 'tg:link-tries:' . $telegramId;
        Cache::add($tries, 0, now()->addHour());
        if (Cache::increment($tries) > 5) {
            TelegramBot::sendText($chat, 'ลองผูกบัญชีหลายครั้งเกินไป — รอ 1 ชั่วโมงแล้วสร้างลิงก์ใหม่');

            return;
        }

        $user = TelegramAdmins::redeem($code);
        if ($user === null) {
            TelegramBot::sendText($chat, 'ลิงก์นี้หมดอายุหรือถูกใช้ไปแล้ว — กด "ผูกบัญชี Telegram ของฉัน" ที่หน้าแอดมินเพื่อสร้างลิงก์ใหม่ (ใช้ได้ 10 นาที)');

            return;
        }

        $telegramName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: '@' . ($from['username'] ?? $telegramId);
        TelegramAdmins::link($telegramId, $user, $telegramName);
        TelegramBot::sendText($chat, '✅ <b>ผูกบัญชีเรียบร้อย</b>' . "\n" . 'Telegram นี้ทำงานในนามแอดมิน <b>' . e((string) $user->name) . '</b> แล้ว — กดปุ่มในการ์ดแจ้งเตือน หรือใช้คำสั่งด้านล่างได้เลย', BotActions::menu());

        // A new hand on the controls is itself a security event — tell the alert chat.
        AdminAlerts::send(new Alert(
            key: 'telegram-linked:' . $telegramId,
            level: Alert::WARNING,
            title: 'ผูกบัญชี Telegram ใหม่กับแอดมิน ' . Str::limit((string) $user->name, 30),
            body: 'บัญชี Telegram "' . Str::limit($telegramName, 40) . '" สั่งงานบอทและกดอนุมัติเงินได้แล้ว'
                . "\nถ้าไม่ได้ทำเอง ให้ถอดบัญชีนี้ที่หน้าแอดมิน → แจ้งเตือน Telegram ทันที",
            facts: ['Telegram ID' => $telegramId, 'แอดมิน' => Str::limit((string) $user->name, 30)],
            url: route('admin.alerts.index'),
            urlLabel: 'จัดการบัญชีที่ผูก',
            category: 'security',
        ), 1);
    }
}
