<?php

namespace App\Support\Telegram;

use App\Models\Setting;
use App\Support\AdminAlerts;
use App\Support\Alerts\Alert;
use App\Support\Alerts\AlertCard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The admin Telegram bot: sends alert cards, edits them once someone has acted on them, and answers
 * the commands and buttons that come back through [App\Http\Controllers\TelegramWebhookController].
 *
 * Every alert goes out as a photo — the drawn [AlertCard], an HTML caption with the same facts in
 * words, and buttons. Low-priority alerts (INFO) arrive silently. If the card can't be drawn, or
 * Telegram refuses the photo, it goes again as text: the alert matters more than the picture.
 *
 * THE TOKEN IS IN EVERY URL ("/bot<token>/sendPhoto"). Guzzle's connection errors quote the full
 * URL, and error strings from here reach laravel.log and the admin page's history — so everything
 * that leaves this class goes through [self::redact] first.
 *
 * Settings: `telegram_alerts_enabled`, `telegram_bot_token` (encrypted), `telegram_chat_id` (a user
 * id, a -100… group id, or @channel), `telegram_bot_username`, `telegram_topics` (category =>
 * forum thread id), `telegram_protect_content`.
 *
 * Ported from NetWix's TelegramNotifier, plus editing, topics, webhooks and the command menu.
 */
final class TelegramBot
{
    private const API = 'https://api.telegram.org';

    /** Telegram's limits are 1024 (caption) and 4096 (message) — stay clear, emoji count double. */
    public const CAPTION_MAX = 1000;

    public const TEXT_MAX = 3800;

    public static function enabled(): bool
    {
        return (bool) Setting::getValue('telegram_alerts_enabled', false) && self::configured();
    }

    public static function configured(): bool
    {
        return self::token() !== '' && self::chat() !== '';
    }

    public static function token(): string
    {
        return trim((string) Setting::getValue('telegram_bot_token', ''));
    }

    /** The chat alerts go to. */
    public static function chat(): string
    {
        return trim((string) Setting::getValue('telegram_chat_id', ''));
    }

    public static function username(): string
    {
        return trim((string) Setting::getValue('telegram_bot_username', ''));
    }

    /** Forum thread for a category in a topics-enabled group, or null for the main chat. */
    public static function topic(string $category): ?int
    {
        $topics = Setting::getValue('telegram_topics', []);
        $topics = is_array($topics) ? $topics : (json_decode((string) $topics, true) ?: []);
        $id = $topics[$category] ?? null;

        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }

    // ---------------------------------------------------------------------- alerts

    /**
     * Send one alert as a card. Never throws: an alerting failure must not take down whatever was
     * reporting it.
     *
     * @return array{error:?string,message_id:?int,chat:string}
     */
    public static function sendAlert(Alert $alert, ?string $chat = null, ?int $thread = null, ?int $replyTo = null): array
    {
        $chat ??= self::chat();
        try {
            $sent = self::sendAlertOnce($alert, $chat, $thread, $replyTo, retryMigrated: true);
            if ($sent['error'] === null && $alert->photo !== null) {
                self::sendPhotoFile($sent['chat'], $alert->photo, $thread, $sent['message_id'], self::protects($alert));
            }

            return $sent;
        } catch (Throwable $e) {
            $reason = self::redact($e->getMessage());
            Log::warning('telegram: send threw', ['error' => $reason]);

            return ['error' => mb_substr($reason, 0, 200), 'message_id' => null, 'chat' => $chat];
        }
    }

    private static function sendAlertOnce(Alert $alert, string $chat, ?int $thread, ?int $replyTo, bool $retryMigrated): array
    {
        $keyboard = self::keyboard($alert);
        $png = AlertCard::png($alert);
        $common = [
            'chat_id' => $chat,
            'message_thread_id' => $thread,
            'parse_mode' => 'HTML',
            'disable_notification' => self::silent($alert) ? 'true' : 'false',
            'protect_content' => self::protects($alert) ? 'true' : null,
            'reply_parameters' => $replyTo ? json_encode(['message_id' => $replyTo, 'allow_sending_without_reply' => true]) : null,
            'reply_markup' => $keyboard !== null ? json_encode($keyboard) : null,
        ];

        if ($png !== null) {
            $res = self::call('sendPhoto', $common + [
                'caption' => self::caption($alert, $keyboard === null, self::CAPTION_MAX),
            ], $png);

            if ($res['ok']) {
                return ['error' => null, 'message_id' => (int) ($res['result']['message_id'] ?? 0) ?: null, 'chat' => $chat];
            }
            if ($retryMigrated && ($to = self::followMigration($res, $chat)) !== null) {
                return self::sendAlertOnce($alert, $to, $thread, $replyTo, retryMigrated: false);
            }
            if (self::isFatal($res)) {
                return ['error' => self::explain($res), 'message_id' => null, 'chat' => $chat];
            }
            // Something about the photo itself was refused — say it in words instead.
            Log::info('telegram: photo refused, falling back to text', ['desc' => $res['desc']]);
        }

        $text = $common + [
            'text' => self::caption($alert, true, self::TEXT_MAX),
            'link_preview_options' => json_encode(['is_disabled' => true]),
        ];
        $res = self::call('sendMessage', $text);

        if (! $res['ok'] && $retryMigrated && ($to = self::followMigration($res, $chat)) !== null) {
            return self::sendAlertOnce($alert, $to, $thread, $replyTo, retryMigrated: false);
        }
        // Last resort: if Telegram could not parse our HTML, the words still matter — send them bare.
        if (! $res['ok'] && str_contains(strtolower($res['desc']), 'entities')) {
            $res = self::call('sendMessage', ['text' => mb_substr($alert->toText(), 0, self::TEXT_MAX), 'parse_mode' => null] + $text);
        }

        return $res['ok']
            ? ['error' => null, 'message_id' => (int) ($res['result']['message_id'] ?? 0) ?: null, 'chat' => $chat]
            : ['error' => self::explain($res), 'message_id' => null, 'chat' => $chat];
    }

    /**
     * Replace a card we sent earlier with a fresh one — the order is paid now, a teammate handled the
     * message — so the chat shows the current state and nobody acts on it twice. Falls back to
     * editing only the caption when the message was sent as text or the photo can't be swapped.
     */
    public static function editAlert(string $chat, int $messageId, Alert $alert): ?string
    {
        try {
            $keyboard = self::keyboard($alert);
            $markup = json_encode($keyboard ?? ['inline_keyboard' => []]);
            $png = AlertCard::png($alert);
            if ($png !== null) {
                $res = self::call('editMessageMedia', [
                    'chat_id' => $chat,
                    'message_id' => $messageId,
                    'media' => json_encode([
                        'type' => 'photo',
                        'media' => 'attach://card',
                        'caption' => self::caption($alert, $keyboard === null, self::CAPTION_MAX),
                        'parse_mode' => 'HTML',
                    ]),
                    'reply_markup' => $markup,
                ], $png, attachAs: 'card');
                if ($res['ok'] || str_contains($res['desc'], 'not modified')) {
                    return null;
                }
            }
            foreach (['editMessageCaption' => 'caption', 'editMessageText' => 'text'] as $method => $field) {
                $res = self::call($method, [
                    'chat_id' => $chat,
                    'message_id' => $messageId,
                    $field => self::caption($alert, $keyboard === null, $field === 'caption' ? self::CAPTION_MAX : self::TEXT_MAX),
                    'parse_mode' => 'HTML',
                    'reply_markup' => $markup,
                ]);
                if ($res['ok'] || str_contains($res['desc'], 'not modified')) {
                    return null;
                }
            }

            return self::explain($res);
        } catch (Throwable $e) {
            return mb_substr(self::redact($e->getMessage()), 0, 200);
        }
    }

    /** An image from disk (a payment slip) as a reply to the card about it. Best-effort. */
    private static function sendPhotoFile(string $chat, string $path, ?int $thread, ?int $replyTo, bool $protect): void
    {
        try {
            $bytes = @file_get_contents($path);
            if ($bytes === false || $bytes === '') {
                return;
            }
            $res = self::call('sendPhoto', [
                'chat_id' => $chat,
                'message_thread_id' => $thread,
                'caption' => 'สลิปที่ลูกค้าแนบมา',
                'disable_notification' => 'true',
                'protect_content' => $protect ? 'true' : null,
                'reply_parameters' => $replyTo ? json_encode(['message_id' => $replyTo, 'allow_sending_without_reply' => true]) : null,
            ], $bytes, attachAs: 'photo', filename: basename($path));
            if (! $res['ok']) {
                Log::info('telegram: slip photo refused', ['desc' => $res['desc']]);
            }
        } catch (Throwable $e) {
            Log::info('telegram: slip photo failed', ['error' => self::redact($e->getMessage())]);
        }
    }

    /** Plain HTML message — command replies, confirmations. */
    public static function sendText(string $chat, string $html, ?array $keyboard = null, ?int $thread = null, ?int $replyTo = null): ?string
    {
        try {
            $res = self::call('sendMessage', [
                'chat_id' => $chat,
                'message_thread_id' => $thread,
                'text' => mb_substr($html, 0, self::TEXT_MAX),
                'parse_mode' => 'HTML',
                'link_preview_options' => json_encode(['is_disabled' => true]),
                'reply_parameters' => $replyTo ? json_encode(['message_id' => $replyTo, 'allow_sending_without_reply' => true]) : null,
                'reply_markup' => $keyboard !== null ? json_encode($keyboard) : null,
            ]);

            return $res['ok'] ? null : self::explain($res);
        } catch (Throwable $e) {
            return mb_substr(self::redact($e->getMessage()), 0, 200);
        }
    }

    /** Swap only the buttons under a message (the "are you sure?" step). */
    public static function editKeyboard(string $chat, int $messageId, ?array $keyboard): void
    {
        try {
            self::call('editMessageReplyMarkup', [
                'chat_id' => $chat,
                'message_id' => $messageId,
                'reply_markup' => json_encode($keyboard ?? ['inline_keyboard' => []]),
            ]);
        } catch (Throwable) {
        }
    }

    /** Stop the button's spinner; $alert pops a dialog instead of a toast. */
    public static function answerCallback(string $id, string $text = '', bool $alert = false): void
    {
        try {
            self::call('answerCallbackQuery', [
                'callback_query_id' => $id,
                'text' => $text !== '' ? mb_substr($text, 0, 190) : null,
                'show_alert' => $alert ? 'true' : null,
            ]);
        } catch (Throwable) {
        }
    }

    /** Show "typing…"/"sending photo…" while a report card is drawn. */
    public static function chatAction(string $chat, string $action = 'upload_photo', ?int $thread = null): void
    {
        try {
            self::call('sendChatAction', ['chat_id' => $chat, 'action' => $action, 'message_thread_id' => $thread]);
        } catch (Throwable) {
        }
    }

    // ---------------------------------------------------------------------- setup helpers

    /**
     * Ask Telegram who a token belongs to (getMe) — used when the admin saves a token, so a typo is
     * caught on the spot instead of at the first real alert.
     *
     * @return array{ok:bool,reachable:bool,username:?string,name:?string,error:?string}
     */
    public static function identify(string $token): array
    {
        try {
            $res = self::call('getMe', [], null, $token);
        } catch (Throwable $e) {
            return ['ok' => false, 'reachable' => false, 'username' => null, 'name' => null,
                'error' => 'ต่อ Telegram ไม่ได้ในตอนนี้ (' . mb_substr(self::redact($e->getMessage(), $token), 0, 120) . ')'];
        }

        return $res['ok']
            ? ['ok' => true, 'reachable' => true, 'username' => $res['result']['username'] ?? null, 'name' => $res['result']['first_name'] ?? null, 'error' => null]
            : ['ok' => false, 'reachable' => true, 'username' => null, 'name' => null, 'error' => self::explain($res)];
    }

    /**
     * The chats that have talked to the bot recently — so the owner never has to dig a numeric chat
     * id out of Telegram by hand: press Start with the bot, then pick it from a list.
     *
     * With a webhook set Telegram refuses getUpdates (409), so the webhook remembers the chats it
     * sees ([self::rememberChat]) and this reads that list instead.
     *
     * @return array{chats:array<int,array{id:string,type:string,title:string,user_id:?string}>,error:?string}
     */
    public static function discoverChats(): array
    {
        if (self::token() === '') {
            return ['chats' => [], 'error' => 'ยังไม่ได้ใส่ Bot Token'];
        }

        $seen = Cache::get('telegram:seen-chats', []);
        $seen = is_array($seen) ? $seen : [];
        try {
            $res = self::call('getUpdates', ['limit' => 100, 'timeout' => 0]);
        } catch (Throwable $e) {
            return ['chats' => array_values($seen), 'error' => $seen === [] ? 'ต่อ Telegram ไม่ได้ในตอนนี้ (' . mb_substr(self::redact($e->getMessage()), 0, 120) . ')' : null];
        }

        $chats = $seen;
        if ($res['ok']) {
            foreach ((array) $res['result'] as $update) {
                foreach (['message', 'edited_message', 'channel_post', 'my_chat_member', 'chat_member', 'callback_query'] as $kind) {
                    $node = $update[$kind] ?? null;
                    $chat = $kind === 'callback_query' ? ($node['message']['chat'] ?? null) : ($node['chat'] ?? null);
                    if (is_array($chat) && isset($chat['id'])) {
                        $chats = self::chatEntry($chats, $chat, $node['from'] ?? null);
                    }
                }
            }
        } elseif ($res['status'] !== 409 && $seen === []) {
            return ['chats' => [], 'error' => self::explain($res)];
        }

        if ($chats === []) {
            return ['chats' => [], 'error' => 'ยังไม่พบแชทที่คุยกับบอท — เปิดแชทกับบอทแล้วกด Start (หรือเพิ่มบอทเข้ากลุ่ม) แล้วกดค้นหาอีกครั้ง'];
        }

        return ['chats' => array_reverse(array_values($chats)), 'error' => null];
    }

    /** Called by the webhook for every update, so [self::discoverChats] still works in webhook mode. */
    public static function rememberChat(array $chat, ?array $from): void
    {
        try {
            $seen = Cache::get('telegram:seen-chats', []);
            $seen = self::chatEntry(is_array($seen) ? $seen : [], $chat, $from);
            Cache::put('telegram:seen-chats', array_slice($seen, -20, null, true), now()->addDays(7));
        } catch (Throwable) {
        }
    }

    private static function chatEntry(array $chats, array $chat, ?array $from): array
    {
        $name = trim((string) ($chat['title'] ?? trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? ''))));
        $id = (string) $chat['id'];
        unset($chats[$id]);     // re-insert so the newest activity ends up last
        $chats[$id] = [
            'id' => $id,
            'type' => (string) ($chat['type'] ?? ''),
            'title' => $name !== '' ? $name : '@' . ($chat['username'] ?? $id),
            'is_forum' => (bool) ($chat['is_forum'] ?? false),
            'user_id' => isset($from['id']) ? (string) $from['id'] : null,
        ];

        return $chats;
    }

    /**
     * Point Telegram at our webhook. The secret comes back on every update as a header, which is how
     * the webhook knows a request is really from Telegram and not someone who guessed the URL.
     *
     * @return ?string null on success, otherwise a reason
     */
    public static function setWebhook(string $url, string $secret): ?string
    {
        try {
            $res = self::call('setWebhook', [
                'url' => $url,
                'secret_token' => $secret,
                'allowed_updates' => json_encode(['message', 'callback_query', 'my_chat_member']),
                'drop_pending_updates' => 'true',
                'max_connections' => 10,
            ]);

            return $res['ok'] ? null : self::explain($res);
        } catch (Throwable $e) {
            return mb_substr(self::redact($e->getMessage()), 0, 200);
        }
    }

    public static function deleteWebhook(): ?string
    {
        try {
            $res = self::call('deleteWebhook', ['drop_pending_updates' => 'true']);

            return $res['ok'] ? null : self::explain($res);
        } catch (Throwable $e) {
            return mb_substr(self::redact($e->getMessage()), 0, 200);
        }
    }

    /** @return array{url:string,pending:int,last_error:?string,last_error_at:?int}|null */
    public static function webhookInfo(): ?array
    {
        if (self::token() === '') {
            return null;
        }
        try {
            $res = self::call('getWebhookInfo', []);
        } catch (Throwable) {
            return null;
        }
        if (! $res['ok']) {
            return null;
        }
        $r = (array) $res['result'];

        return [
            'url' => (string) ($r['url'] ?? ''),
            'pending' => (int) ($r['pending_update_count'] ?? 0),
            'last_error' => isset($r['last_error_message']) ? self::redact((string) $r['last_error_message']) : null,
            'last_error_at' => isset($r['last_error_date']) ? (int) $r['last_error_date'] : null,
        ];
    }

    /** The "/" menu the admin sees when typing in the chat. @param array<string,string> $commands */
    public static function setCommands(array $commands): ?string
    {
        $list = [];
        foreach ($commands as $command => $description) {
            $list[] = ['command' => $command, 'description' => mb_substr($description, 0, 250)];
        }
        try {
            $res = self::call('setMyCommands', ['commands' => json_encode($list)]);

            return $res['ok'] ? null : self::explain($res);
        } catch (Throwable $e) {
            return mb_substr(self::redact($e->getMessage()), 0, 200);
        }
    }

    /**
     * Create a forum topic ("ห้องย่อย") in a topics-enabled supergroup.
     *
     * @return array{id:?int,error:?string}
     */
    public static function createTopic(string $chat, string $name, int $color): array
    {
        try {
            $res = self::call('createForumTopic', ['chat_id' => $chat, 'name' => mb_substr($name, 0, 128), 'icon_color' => $color]);
        } catch (Throwable $e) {
            return ['id' => null, 'error' => mb_substr(self::redact($e->getMessage()), 0, 200)];
        }
        if ($res['ok']) {
            return ['id' => (int) ($res['result']['message_thread_id'] ?? 0) ?: null, 'error' => null];
        }
        $d = strtolower($res['desc']);

        return ['id' => null, 'error' => match (true) {
            str_contains($d, 'not a forum') || str_contains($d, 'forum') && str_contains($d, 'not') => 'กลุ่มนี้ยังไม่ได้เปิดโหมด "หัวข้อ (Topics)" — เปิดใน ตั้งค่ากลุ่ม → Topics ก่อน',
            str_contains($d, 'not enough rights') || str_contains($d, 'rights') => 'บอทต้องเป็นแอดมินของกลุ่ม และมีสิทธิ์ "จัดการหัวข้อ (Manage Topics)"',
            default => self::explain($res),
        }];
    }

    // ---------------------------------------------------------------------- transport

    /**
     * One Bot API call. Null params are dropped; everything else is sent as a string field, which
     * is what a multipart request needs and what Telegram accepts either way.
     *
     * @return array{ok:bool,status:int,desc:string,result:mixed,params:array}
     */
    private static function call(string $method, array $params, ?string $photo = null, ?string $token = null, string $attachAs = 'photo', string $filename = 'xman-alert.png'): array
    {
        $params = array_map('strval', array_filter($params, fn ($v) => $v !== null));
        $request = Http::connectTimeout(5)->timeout($photo !== null ? 25 : 12);
        if ($photo !== null) {
            $request = $request->attach($attachAs, $photo, $filename);
        } else {
            $request = $request->asForm();
        }

        $resp = $request->post(self::API . '/bot' . ($token ?? self::token()) . '/' . $method, $params);
        $json = $resp->json();
        $ok = $resp->successful() && is_array($json) && ($json['ok'] ?? false) === true;

        return [
            'ok' => $ok,
            'status' => $resp->status(),
            'desc' => $ok ? '' : (string) (is_array($json) ? ($json['description'] ?? '') : mb_substr($resp->body(), 0, 200)),
            'result' => $ok ? ($json['result'] ?? []) : [],
            'params' => is_array($json) ? (array) ($json['parameters'] ?? []) : [],
        ];
    }

    /**
     * A group that gets upgraded to a supergroup changes its chat id, and Telegram answers the old
     * one with the new id attached. Follow it once and remember it, instead of going silent.
     */
    private static function followMigration(array $res, string $chat): ?string
    {
        $to = $res['params']['migrate_to_chat_id'] ?? null;
        if (! is_numeric($to)) {
            return null;
        }
        if ($chat === self::chat()) {
            Setting::setValue('telegram_chat_id', (string) $to, 'string', 'telegram');
            Log::info('telegram: chat migrated to a supergroup, chat id updated', ['to' => (string) $to]);
        }

        return (string) $to;
    }

    /** Failures a text retry would only repeat: the token, the chat, or rate limiting. */
    private static function isFatal(array $res): bool
    {
        $d = strtolower($res['desc']);

        return in_array($res['status'], [401, 403, 404, 429], true)
            || str_contains($d, 'chat not found') || str_contains($d, 'upgraded') || str_contains($d, 'thread not found');
    }

    /** A short Thai reason for the admin page, never the raw API text alone. */
    public static function explain(array $res): string
    {
        $d = strtolower($res['desc']);

        return match (true) {
            in_array($res['status'], [401, 404], true) => 'Token ไม่ถูกต้อง หรือบอทถูกลบไปแล้ว — ขอ Token ใหม่จาก @BotFather',
            str_contains($d, 'chat not found') => 'ไม่พบแชทปลายทาง — ต้องกด Start ในแชทกับบอทก่อน (หรือเพิ่มบอทเข้ากลุ่ม) แล้วตรวจ Chat ID อีกครั้ง',
            str_contains($d, 'thread not found') => 'ไม่พบห้องย่อย (Topic) ที่ตั้งไว้ — อาจถูกลบไปแล้ว กด "สร้างห้องย่อยอัตโนมัติ" ใหม่',
            str_contains($d, 'blocked by the user') => 'บอทถูกบล็อกอยู่ — เปิดแชทกับบอทแล้วกด Restart',
            str_contains($d, 'not a member'), str_contains($d, 'kicked'), str_contains($d, 'not enough rights') => 'บอทไม่ได้อยู่ในกลุ่ม/ช่องนี้ หรือไม่มีสิทธิ์ส่งข้อความ — เพิ่มบอทเข้าไป (ช่องต้องตั้งบอทเป็นแอดมิน)',
            str_contains($d, 'upgraded') => 'กลุ่มถูกอัปเกรดเป็น supergroup และ Chat ID เปลี่ยนแล้ว — กดค้นหา Chat ID ใหม่',
            str_contains($d, 'https url must be provided') || str_contains($d, 'bad webhook') => 'Telegram รับ webhook ได้เฉพาะ HTTPS ที่เป็นโดเมนจริง (ใช้กับ localhost ไม่ได้)',
            $res['status'] === 429 => 'Telegram ให้รอสักครู่ (ส่งถี่เกินไป)',
            default => mb_substr(self::redact('HTTP ' . $res['status'] . ' ' . $res['desc']), 0, 200),
        };
    }

    /** Strip the bot token out of anything that is about to be logged, stored or shown. */
    public static function redact(string $text, ?string $token = null): string
    {
        foreach (array_filter([$token, self::token()]) as $secret) {
            $text = str_replace($secret, '***', $text);
        }

        return preg_replace('~bot\d{5,}:[A-Za-z0-9_-]{20,}~', 'bot***', $text) ?? $text;
    }

    // ---------------------------------------------------------------------- message body

    /** INFO never buzzes; during quiet hours nothing but CRITICAL does. */
    private static function silent(Alert $a): bool
    {
        return $a->level === Alert::INFO
            || ($a->level !== Alert::CRITICAL && AdminAlerts::quietUntil() !== null);
    }

    /** Customer data stays inside the chat: no forwarding or saving, when the admin asked for that. */
    private static function protects(Alert $a): bool
    {
        return in_array($a->category, ['orders', 'contact', 'members'], true)
            && (bool) Setting::getValue('telegram_protect_content', false);
    }

    /** HTML caption: headline, the facts as a list, then the explanation — trimmed to fit $max. */
    public static function caption(Alert $a, bool $withUrl, int $max): string
    {
        $e = fn (string $s): string => htmlspecialchars($s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $head = $a->emoji() . ' <b>' . $e($a->title) . '</b>';
        $headPlain = $a->emoji() . ' ' . $a->title;

        $facts = [];
        $factsPlain = '';
        foreach ($a->facts as $label => $value) {
            $facts[] = '• ' . $e((string) $label) . ': <b>' . $e((string) $value) . '</b>';
            $factsPlain .= '• ' . $label . ': ' . $value . "\n";
        }

        $url = $withUrl && $a->url ? $a->url : '';

        // Budget the body on VISIBLE characters — that is what Telegram counts, after tags.
        $room = $max - mb_strlen($headPlain . $factsPlain . $url) - 8;
        $body = trim($a->body);
        if (mb_strlen($body) > $room) {
            $body = rtrim(mb_substr($body, 0, max(0, $room - 1))) . '…';
        }
        if ($body !== '') {
            // A long report collapses; a sentence or two stays open.
            $body = substr_count($body, "\n") >= 3
                ? '<blockquote expandable>' . $e($body) . '</blockquote>'
                : $e($body);
        }

        return implode("\n\n", array_filter([
            $head,
            implode("\n", $facts),
            $body,
            $url !== '' ? $e($url) : '',
        ], fn ($part) => $part !== ''));
    }

    /** An alert's full keyboard — to put its buttons back after an "are you sure?" was cancelled. */
    public static function keyboardFor(Alert $a): array
    {
        return self::keyboard($a) ?? ['inline_keyboard' => []];
    }

    /**
     * The buttons: the alert's own action rows, then a link to the admin page — a URL button only
     * for an address Telegram will accept (not localhost or a bare IP).
     */
    private static function keyboard(Alert $a): ?array
    {
        $rows = [];
        foreach ($a->buttons as $row) {
            $out = [];
            foreach ((array) $row as $b) {
                if (isset($b['data']) && strlen((string) $b['data']) <= 64) {
                    $out[] = ['text' => (string) $b['text'], 'callback_data' => (string) $b['data']];
                } elseif (isset($b['url']) && self::publicUrl((string) $b['url'])) {
                    $out[] = ['text' => (string) $b['text'], 'url' => (string) $b['url']];
                }
            }
            if ($out !== []) {
                $rows[] = $out;
            }
        }
        if ($a->url && self::publicUrl($a->url)) {
            $rows[] = [['text' => $a->urlLabel, 'url' => $a->url]];
        }

        return $rows !== [] ? ['inline_keyboard' => $rows] : null;
    }

    private static function publicUrl(string $url): bool
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $scheme = (string) parse_url($url, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true) && str_contains($host, '.')
            && filter_var($host, FILTER_VALIDATE_IP) === false
            && ! preg_match('/\.(test|local|localhost)$/', $host);
    }
}
