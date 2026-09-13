<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AdminAlerts;
use App\Support\Alerts\Alert;
use App\Support\Alerts\AlertCard;
use App\Support\Telegram\BotCommands;
use App\Support\Telegram\TelegramAdmins;
use App\Support\Telegram\TelegramBot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

/**
 * "แจ้งเตือน Telegram" — where the owner points the admin alerts at a Telegram bot, chooses what is
 * worth a buzz, turns on the bot's commands and buttons, and links the admin accounts that may use
 * them.
 *
 * The bot token is WRITE-ONLY here: the form shows whether one is set, never the value (a token that
 * can be read back off an admin page leaks through a screenshot, a shoulder, a stale cache). It is
 * checked against Telegram (getMe) before it is stored, so a typo is found now and not at the first
 * real order.
 */
class TelegramAlertController extends Controller
{
    private const CHAT_ID_RULE = 'regex:/^(-?\d{1,20}|@[A-Za-z][A-Za-z0-9_]{3,31})$/';

    /** Telegram's six allowed topic colours, one per category. */
    private const TOPIC_COLORS = [
        'orders' => 9367192,      // green
        'contact' => 7322096,     // blue
        'security' => 16478047,   // red
        'system' => 16766590,     // yellow
        'members' => 13338331,    // purple
        'daily' => 16749490,      // pink
    ];

    public function index(): View
    {
        $hasToken = TelegramBot::token() !== '';
        $webhookOn = (string) Setting::getValue('telegram_webhook_secret', '') !== '';

        $link = session('tg_link');
        $qr = null;
        if (is_string($link) && $link !== '') {
            try {
                $qr = (string) QrCode::format('svg')->size(190)->margin(1)->generate($link);
            } catch (Throwable) {
                $qr = null;
            }
        }

        return view('admin.alerts.index', [
            'tg' => [
                'enabled' => (bool) Setting::getValue('telegram_alerts_enabled', false),
                'hasToken' => $hasToken,
                'chat' => TelegramBot::chat(),
                'bot' => TelegramBot::username(),
                'ready' => TelegramBot::enabled(),
                'protect' => (bool) Setting::getValue('telegram_protect_content', false),
            ],
            'webhook' => [
                'on' => $webhookOn,
                // One API call, only when there is something to ask about.
                'info' => $hasToken && $webhookOn ? TelegramBot::webhookInfo() : null,
                'url' => route('telegram.webhook'),
                'https' => str_starts_with(route('telegram.webhook'), 'https://'),
            ],
            'categories' => AdminAlerts::CATEGORIES,
            'enabledCategories' => AdminAlerts::categories(),
            'topics' => (array) (Setting::getValue('telegram_topics', []) ?: []),
            'admins' => TelegramAdmins::all(),
            'link' => $link,
            'qr' => $qr,
            'canDraw' => AlertCard::available(),
            'recent' => AdminAlerts::recent(30),
            'chats' => (array) session('tg_chats', []),
            'quietUntil' => AdminAlerts::quietUntil(),
        ]);
    }

    public function updateTelegram(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'telegram_alerts_enabled' => ['sometimes', 'boolean'],
            'telegram_protect_content' => ['sometimes', 'boolean'],
            // Blank = keep the stored token, so the chat can be changed without pasting it again.
            'telegram_bot_token' => ['nullable', 'string', 'max:100', 'regex:/^\d{5,16}:[A-Za-z0-9_-]{30,64}$/'],
            'telegram_chat_id' => ['nullable', 'string', 'max:64', self::CHAT_ID_RULE],
        ], [
            'telegram_bot_token.regex' => 'Bot Token ไม่ถูกรูปแบบ — ต้องหน้าตาแบบ 123456789:AAH… (คัดลอกจาก @BotFather มาทั้งบรรทัด)',
            'telegram_chat_id.regex' => 'Chat ID ต้องเป็นตัวเลข (กลุ่มจะขึ้นต้นด้วย -100) หรือ @ชื่อช่อง',
        ]);

        $status = 'บันทึกการตั้งค่า Telegram แล้ว';
        $token = trim((string) ($data['telegram_bot_token'] ?? ''));
        if ($token !== '') {
            // Ask Telegram before storing it: a typo found now is not a silent outage later.
            $who = TelegramBot::identify($token);
            if (! $who['ok'] && $who['reachable']) {
                return back()->withErrors(['telegram_bot_token' => $who['error']]);
            }
            if ($token !== TelegramBot::token()) {
                // A different bot: the old webhook and linked accounts belong to the old one.
                Setting::setValue('telegram_webhook_secret', '', 'string', 'telegram');
            }
            Setting::setValue('telegram_bot_token', $token, 'string', 'telegram');
            Setting::setValue('telegram_bot_username', (string) ($who['username'] ?? ''), 'string', 'telegram');
            $status = $who['ok']
                ? "เชื่อมบอท @{$who['username']} แล้ว — ต่อไปเปิดแชทกับบอทแล้วกด Start จากนั้นกด \"ค้นหา Chat ID อัตโนมัติ\""
                : $status . ' (ตอนนี้ยังตรวจ Token กับ Telegram ไม่ได้ — ลองกดทดสอบส่งภายหลัง)';
        }
        Setting::setValue('telegram_chat_id', trim((string) ($data['telegram_chat_id'] ?? '')), 'string', 'telegram');
        Setting::setValue('telegram_alerts_enabled', $request->boolean('telegram_alerts_enabled') ? '1' : '0', 'boolean', 'telegram');
        Setting::setValue('telegram_protect_content', $request->boolean('telegram_protect_content') ? '1' : '0', 'boolean', 'telegram');

        return back()->with('success', $status);
    }

    /** List the chats that have talked to the bot, so the owner picks one instead of hunting an id. */
    public function detectChats(): RedirectResponse
    {
        $found = TelegramBot::discoverChats();
        if ($found['error'] !== null) {
            return back()->withErrors(['telegram' => $found['error']]);
        }

        return back()
            ->with('tg_chats', $found['chats'])
            ->with('success', 'พบ ' . count($found['chats']) . ' แชท — เลือกแชทที่จะรับแจ้งเตือนด้านล่าง');
    }

    public function useChat(Request $request): RedirectResponse
    {
        $data = $request->validate(['chat_id' => ['required', 'string', 'max:64', self::CHAT_ID_RULE]]);

        if ($data['chat_id'] !== TelegramBot::chat()) {
            // Topic ids belong to the old group.
            Setting::setValue('telegram_topics', [], 'json', 'telegram');
        }
        Setting::setValue('telegram_chat_id', $data['chat_id'], 'string', 'telegram');
        // Picking the chat to receive alerts in IS choosing to receive them.
        Setting::setValue('telegram_alerts_enabled', '1', 'boolean', 'telegram');

        return back()->with('success', 'ตั้งแชทปลายทางและเปิดแจ้งเตือนแล้ว — กด "ทดสอบส่ง" เพื่อดูการ์ดจริงได้เลย');
    }

    public function test(): RedirectResponse
    {
        [$ok, $error] = AdminAlerts::test();

        return $ok
            ? back()->with('success', 'ส่งการ์ดทดสอบแล้ว — ลองเช็คใน Telegram')
            : back()->withErrors(['telegram' => $error ?? 'ส่งไม่สำเร็จ']);
    }

    /** Clear the stored token (e.g. after revoking it in @BotFather), and everything tied to that bot. */
    public function forgetToken(): RedirectResponse
    {
        if (TelegramBot::token() !== '' && (string) Setting::getValue('telegram_webhook_secret', '') !== '') {
            TelegramBot::deleteWebhook();
        }
        foreach (['telegram_bot_token', 'telegram_bot_username', 'telegram_webhook_secret'] as $key) {
            Setting::setValue($key, '', 'string', 'telegram');
        }
        Setting::setValue('telegram_alerts_enabled', '0', 'boolean', 'telegram');

        return back()->with('success', 'ลบ Bot Token แล้ว ปิดการแจ้งเตือนและปิดรับคำสั่งบอท');
    }

    public function updateCategories(Request $request): RedirectResponse
    {
        $request->validate([
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', Rule::in(array_keys(AdminAlerts::CATEGORIES))],
        ]);
        AdminAlerts::saveCategories((array) $request->input('categories', []));

        return back()->with('success', 'บันทึกหมวดที่จะแจ้งเตือนแล้ว');
    }

    // ------------------------------------------------------------------------------ bot commands

    /**
     * Point Telegram at our webhook with a fresh secret, and register the "/" menu. From here on
     * getUpdates stops working (Telegram allows one or the other), so "find my chat" reads the chats
     * the webhook has seen instead.
     */
    public function enableWebhook(): RedirectResponse
    {
        if (TelegramBot::token() === '') {
            return back()->withErrors(['webhook' => 'ใส่ Bot Token ก่อน']);
        }
        $url = route('telegram.webhook');
        if (! str_starts_with($url, 'https://')) {
            return back()->withErrors(['webhook' => 'Telegram ส่งคำสั่งได้เฉพาะเว็บที่เป็น HTTPS — ตอนนี้ APP_URL คือ ' . config('app.url')]);
        }

        $secret = Str::random(48);
        $error = TelegramBot::setWebhook($url, $secret);
        if ($error !== null) {
            return back()->withErrors(['webhook' => $error]);
        }
        Setting::setValue('telegram_webhook_secret', $secret, 'string', 'telegram');
        TelegramBot::setCommands(BotCommands::MENU);

        return back()->with('success', 'เปิดรับคำสั่งบอทแล้ว — ต่อไปกด "ผูกบัญชี Telegram ของฉัน" เพื่อให้กดปุ่มอนุมัติ/สั่งรายงานได้');
    }

    public function disableWebhook(): RedirectResponse
    {
        $error = TelegramBot::deleteWebhook();
        Setting::setValue('telegram_webhook_secret', '', 'string', 'telegram');

        return $error === null
            ? back()->with('success', 'ปิดรับคำสั่งบอทแล้ว — ยังส่งแจ้งเตือนได้ตามปกติ แต่ปุ่มในการ์ดจะกดไม่ได้')
            : back()->with('success', 'ปิดฝั่งเว็บแล้ว (แจ้ง Telegram ไม่สำเร็จ: ' . $error . ') — คำขอจาก Telegram จะถูกปฏิเสธทั้งหมด');
    }

    /** A one-time link (and QR) that binds the Telegram account which opens it to the signed-in admin. */
    public function linkMe(Request $request): RedirectResponse
    {
        if ((string) Setting::getValue('telegram_webhook_secret', '') === '') {
            return back()->withErrors(['link' => 'ต้องเปิดรับคำสั่งบอทก่อน บอทจึงจะรับลิงก์ผูกบัญชีได้']);
        }
        $link = TelegramAdmins::deepLink(TelegramAdmins::issueCode($request->user()));
        if ($link === null) {
            return back()->withErrors(['link' => 'ยังไม่รู้ชื่อบอท — บันทึก Bot Token ใหม่อีกครั้ง']);
        }

        return back()->with('tg_link', $link)->with('success', 'สร้างลิงก์ผูกบัญชีแล้ว (ใช้ได้ครั้งเดียว ภายใน 10 นาที)');
    }

    public function unlink(string $telegramId): RedirectResponse
    {
        TelegramAdmins::unlink($telegramId);

        return back()->with('success', 'ถอดบัญชี Telegram นี้แล้ว — กดปุ่มหรือสั่งบอทไม่ได้อีก');
    }

    // ------------------------------------------------------------------------------ forum topics

    /** One "ห้องย่อย" per switched-on category, in a topics-enabled group. */
    public function createTopics(): RedirectResponse
    {
        $chat = TelegramBot::chat();
        if ($chat === '' || ! str_starts_with($chat, '-100')) {
            return back()->withErrors(['topics' => 'ห้องย่อยใช้ได้กับกลุ่ม (supergroup) ที่เปิดโหมด Topics เท่านั้น — Chat ID ต้องขึ้นต้นด้วย -100']);
        }

        $topics = (array) (Setting::getValue('telegram_topics', []) ?: []);
        $made = 0;
        foreach (AdminAlerts::categories() as $category) {
            if (! empty($topics[$category])) {
                continue;
            }
            $result = TelegramBot::createTopic($chat, AdminAlerts::CATEGORIES[$category][0], self::TOPIC_COLORS[$category] ?? 7322096);
            if ($result['id'] === null) {
                Setting::setValue('telegram_topics', $topics, 'json', 'telegram');

                return back()->withErrors(['topics' => $result['error'] ?? 'สร้างห้องย่อยไม่สำเร็จ']);
            }
            $topics[$category] = $result['id'];
            $made++;
        }
        Setting::setValue('telegram_topics', $topics, 'json', 'telegram');

        return back()->with('success', $made > 0 ? "สร้างห้องย่อยแล้ว {$made} ห้อง — แจ้งเตือนแต่ละหมวดจะเข้าห้องของมันเอง" : 'ทุกหมวดมีห้องย่อยอยู่แล้ว');
    }

    public function clearTopics(): RedirectResponse
    {
        Setting::setValue('telegram_topics', [], 'json', 'telegram');

        return back()->with('success', 'เลิกใช้ห้องย่อยแล้ว — แจ้งเตือนทั้งหมดจะเข้าแชทหลัก (ห้องเดิมใน Telegram ไม่ถูกลบ)');
    }

    // ------------------------------------------------------------------------------ preview

    /** A card drawn from sample data — what arrives on the phone, before anything is set up. */
    public function preview(string $level): Response
    {
        $png = AlertCard::png($this->sample($level));
        abort_if($png === null, 404);

        return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'private, max-age=600']);
    }

    private function sample(string $kind): Alert
    {
        return match ($kind) {
            'contact' => new Alert(
                key: 'ai-lead:ตัวอย่าง',
                level: Alert::MONEY,
                title: 'ลูกค้าในแชท AI ทิ้งช่องทางติดต่อไว้',
                body: "ลูกค้า: สนใจทำระบบจองคิวร้านค่ะ ราคาประมาณเท่าไหร่\nAI: เริ่มต้นที่ ฿15,000 ค่ะ สนใจให้ทีมงานติดต่อกลับไหมคะ\nลูกค้า: ได้ค่ะ 081-234-5678",
                facts: ['ติดต่อกลับที่' => '0812345678', 'หน้า' => '/services'],
                url: url('/admin'),
                category: 'contact',
            ),
            'security' => new Alert(
                key: 'admin-brute:ตัวอย่าง',
                level: Alert::CRITICAL,
                title: 'มีคนพยายามล็อกอินบัญชีแอดมิน',
                body: "บัญชี: ad***@xman4289.com\nรหัสผ่านผิด 3 ครั้งใน 15 นาที",
                facts: ['ครั้งที่ผิด' => 3, 'IP ล่าสุด' => '203.0.113.7', 'ช่วงเวลา' => '15 นาที'],
                url: url('/admin/users'),
                category: 'security',
            ),
            'daily' => new Alert(
                key: 'daily-report:ตัวอย่าง',
                level: Alert::OK,
                title: 'สรุปประจำวัน (ตัวอย่าง)',
                body: "• ชำระแล้ว 14 รายการ\n• รอดำเนินการ: สลิปรอตรวจ 2",
                facts: ['ยอดขาย' => '฿38,450 (+24%)', 'รายการ' => '14', 'สมาชิกใหม่' => '9 คน', 'ติดต่อ/ขอราคา' => '5'],
                bars: ['Metal-X License' => 19960, 'AI Credits 1,000' => 9990, 'เช่า VPS รายเดือน' => 8500],
                url: url('/admin'),
                category: 'daily',
                barsLabel: 'สินค้าขายดี (บาท)',
                columns: ['ศ. 6' => 21500, 'ส. 7' => 18200, 'อา. 8' => 9400, 'จ. 9' => 26800, 'อ. 10' => 31000, 'พ. 11' => 22950, 'พฤ. 12' => 38450],
                columnsLabel: 'ยอดขาย 7 วัน (บาท)',
            ),
            default => new Alert(
                key: 'order:ตัวอย่าง',
                level: Alert::MONEY,
                title: 'ลูกค้าแนบสลิปแล้ว ฿4,990',
                body: "ลูกค้า: สมชาย ใจดี · 081-234-5678\n• Metal-X License (1 ปี) ×1 — ฿4,990\nชำระผ่าน: โอนธนาคาร",
                facts: ['ยอดชำระ' => '฿4,990', 'เลขที่' => 'XM20260913-0042', 'สถานะ' => 'รอตรวจสลิป'],
                url: url('/admin/orders'),
                category: 'orders',
            ),
        };
    }
}
