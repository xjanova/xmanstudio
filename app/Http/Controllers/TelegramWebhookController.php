<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\AdminAlerts;
use App\Support\Telegram\BotActions;
use App\Support\Telegram\BotCommands;
use App\Support\Telegram\TelegramBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Where Telegram delivers what happens in the admin bot's chats: commands typed, buttons pressed.
 *
 * The URL is public, so the only thing that makes a request "from Telegram" is the secret Telegram
 * echoes back in X-Telegram-Bot-Api-Secret-Token — set by us in setWebhook, stored encrypted,
 * compared in constant time. No secret configured means the webhook is off: every request is refused.
 *
 * The answer is always an immediate 200 and the work happens after the response. Telegram re-sends
 * an update it thinks timed out, so each update_id is processed once; drawing a report card takes
 * about a second, and a slow webhook makes Telegram queue everything behind it.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) Setting::getValue('telegram_webhook_secret', '');
        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response()->json(['ok' => false], 403);
        }

        $update = $request->json()->all();
        $updateId = $update['update_id'] ?? null;
        if (! is_int($updateId) || ! Cache::add('telegram:update:' . $updateId, 1, now()->addDay())) {
            return response()->json(['ok' => true]);   // malformed, or a re-delivery we already handled
        }

        // Per-sender budget, instead of a per-IP one (all updates come from Telegram's own IPs).
        // Past it, updates are acknowledged and dropped — a flood from one account costs us nothing.
        $sender = $update['callback_query']['from']['id'] ?? $update['message']['from']['id'] ?? 'unknown';
        $slot = 'telegram:sender:' . $sender . ':' . now()->format('YmdHi');
        Cache::add($slot, 0, now()->addMinutes(2));
        if (Cache::increment($slot) > 40) {
            return response()->json(['ok' => true]);
        }

        // afterResponse, not a bare defer(): alerts raised while handling this (an approval re-draws
        // the order's cards) must go out from inside the deferred work, not be deferred again.
        AdminAlerts::afterResponse(function () use ($update) {
            try {
                if (isset($update['callback_query']) && is_array($update['callback_query'])) {
                    BotActions::handle($update['callback_query']);
                } elseif (isset($update['message']) && is_array($update['message'])) {
                    BotCommands::handle($update['message']);
                } elseif (isset($update['my_chat_member']['chat']) && is_array($update['my_chat_member']['chat'])) {
                    // The bot was added to (or removed from) a group: remember it for "find my chat".
                    TelegramBot::rememberChat($update['my_chat_member']['chat'], $update['my_chat_member']['from'] ?? null);
                }
            } catch (Throwable $e) {
                Log::warning('telegram: webhook update failed', ['error' => TelegramBot::redact($e->getMessage())]);
            }
        }, 'telegram-update:' . $updateId);

        return response()->json(['ok' => true]);
    }
}
