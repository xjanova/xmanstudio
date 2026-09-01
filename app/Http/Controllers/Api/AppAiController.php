<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppAiUsage;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The AI proxy the GigGok app talks to.
 *
 * Why a proxy at all: the alternative was shipping our OpenAI key to the app,
 * encrypted. That protects nothing. Whatever key decrypts it has to ship in the
 * APK too, so anyone who unpacks the app gets both halves - and even if they
 * did not, the key is plain text in the Authorization header the moment the app
 * calls OpenAI. A secret that reaches the user's device is not a secret.
 *
 * Here the key never leaves the server. The app proves who it is with its own
 * license key, we do the talking, and only the answer goes back.
 *
 * Deliberately shaped exactly like OpenAI's /v1/chat/completions, because the
 * app already has a client that speaks it (the same one it uses for a home
 * Ollama box). Matching the shape meant no new client code at all.
 *
 * WHICH key gets used is not decided here - AiChatService reads the provider
 * and key from Settings, so the admin picks it at /admin/ai-settings and this
 * endpoint follows along.
 */
class AppAiController extends Controller
{
    public function __construct(protected AiChatService $chat) {}

    /**
     * POST /api/ai/v1/chat/completions
     */
    public function chatCompletions(Request $request): JsonResponse
    {
        if (! config('appai.enabled', true)) {
            return $this->fail('The assistant service is turned off right now.', 503);
        }

        $license = $this->resolveLicense($request);

        // 401 is what the app turns into "your key is not right", which is the
        // correct thing to say to a device whose license we cannot resolve.
        if (! $license) {
            return $this->fail('Invalid or expired license.', 401);
        }

        $data = $request->validate([
            'messages' => 'required|array|min:1|max:' . config('appai.max_messages', 40),
            'messages.*.role' => 'required|string|in:system,user,assistant',
            'messages.*.content' => 'required|string',
            'model' => 'nullable|string|max:128',
        ]);

        $messages = $data['messages'];
        $chars = array_sum(array_map(fn ($m) => mb_strlen($m['content']), $messages));

        if ($chars > config('appai.max_chars', 24000)) {
            return $this->fail('That conversation is too long to send.', 422);
        }

        $key = (string) $request->bearerToken();
        $used = AppAiUsage::todayFor($key);
        $limit = (int) config('appai.daily_limit', 200);

        // 429 is what the app turns into "rate limited", which is exactly what
        // a spent quota is from the user's side.
        if ($limit > 0 && $used >= $limit) {
            return $this->fail("Daily limit reached ($limit messages). It resets at midnight.", 429);
        }

        if (! $this->chat->isConfigured()) {
            return $this->fail('The assistant is not configured yet.', 503);
        }

        // The app sends the persona as a system message. AiChatService takes the
        // system prompt separately and, given an override, uses it verbatim -
        // so Mind keeps her own personality instead of inheriting the website
        // assistant's "I am here to help you with XMAN Studio services".
        $system = null;
        $turns = [];
        foreach ($messages as $m) {
            if ($m['role'] === 'system') {
                $system = $system === null ? $m['content'] : $system . "\n\n" . $m['content'];

                continue;
            }
            $turns[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        if ($turns === []) {
            return $this->fail('There is nothing to answer.', 422);
        }

        $result = ['success' => false, 'message' => null];
        try {
            $result = $this->chat->chat($turns, $system);
        } catch (\Throwable $e) {
            // Never hand an upstream error text to the app: it can carry the
            // provider name, our model choice, and sometimes fragments of the
            // request. The app shows whatever we put in error.message.
            Log::warning('app ai proxy failed', ['error' => $e->getMessage()]);
        }

        $answer = is_string($result['message'] ?? null) ? trim($result['message']) : '';
        $ok = ($result['success'] ?? false) && $answer !== '';

        // Logged whether it worked or not - see AppAiUsage::todayFor().
        AppAiUsage::create([
            'user_id' => $license->user_id,
            'license_key_id' => $license->id,
            'license_key' => $key,
            'provider' => $result['provider'] ?? null,
            'model' => $result['model'] ?? null,
            'message_count' => count($turns),
            'chars_in' => $chars,
            'chars_out' => mb_strlen($answer),
            'ok' => $ok,
            'ip_address' => $request->ip(),
        ]);

        if (! $ok) {
            return $this->fail('The assistant could not answer just now.', 502);
        }

        // OpenAI's shape, because that is what the app already parses.
        return response()->json([
            'id' => 'giggok-' . uniqid(),
            'object' => 'chat.completion',
            'created' => now()->timestamp,
            'model' => $result['model'] ?? ($data['model'] ?? 'unknown'),
            'choices' => [[
                'index' => 0,
                'message' => ['role' => 'assistant', 'content' => $answer],
                'finish_reason' => 'stop',
            ]],
            // Not token counts - we do not get them from every provider. This
            // is the quota the app may want to show, named so it cannot be
            // mistaken for OpenAI billing units.
            'giggok_quota' => ['used' => $used + 1, 'limit' => $limit],
        ]);
    }

    /**
     * Errors in OpenAI's shape too, so the app reads error.message.
     */
    protected function fail(string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => ['message' => $message, 'type' => 'giggok_proxy'],
        ], $status);
    }

    /**
     * Same rule as the pack store: the bearer is the app's own license key,
     * and it only counts if it was issued for THIS app.
     *
     * Accepting a license for any product would let a key bought for something
     * else spend our AI budget.
     */
    protected function resolveLicense(Request $request): ?LicenseKey
    {
        $key = trim((string) $request->bearerToken());

        if ($key === '') {
            return null;
        }

        $appProduct = Product::where('slug', config('packs.app_product_slug'))->first();

        if (! $appProduct) {
            return null;
        }

        return LicenseKey::where('license_key', $key)
            ->where('product_id', $appProduct->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
