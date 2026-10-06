<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AppAiBillingException;
use App\Http\Controllers\Controller;
use App\Models\AppAiUsage;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Services\AiChatService;
use App\Services\AppAiBilling;
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
    public function __construct(
        protected AiChatService $chat,
        protected AppAiBilling $billing,
    ) {}

    /**
     * GET /api/ai/v1/account
     *
     * Everything the app's settings screen shows about paying for AI: is this
     * device linked to an account, the wallet balance, today's spend against the
     * daily cap, where to top up, and the models we offer with their prices.
     *
     * The model list is returned even to an unlinked device so it can show the
     * prices before the user decides to link and top up.
     */
    public function account(Request $request): JsonResponse
    {
        $license = $this->resolveLicense($request);
        if (! $license) {
            return $this->fail('Invalid or expired license.', 401);
        }

        $userId = $license->user_id;
        $models = $this->billing->models();

        return response()->json([
            'enabled' => $this->billing->enabled(),
            'linked' => $userId !== null,
            'link_url' => url('/giggok/link'),
            'topup_url' => $this->billing->topupUrl(),
            'currency' => AppAiBilling::CURRENCY,
            'balance' => $userId ? $this->billing->balance($userId) : 0,
            'daily_cap' => $this->billing->dailyCap(),
            'today' => [
                'spent' => $userId ? $this->billing->spentToday($userId) : 0,
                'messages' => $userId ? $this->billing->messagesToday($userId) : 0,
            ],
            'models' => $models,
            'default_model' => $models[0]['id'] ?? null,
        ]);
    }

    /**
     * POST /api/ai/v1/chat/completions
     */
    public function chatCompletions(Request $request): JsonResponse
    {
        if (! $this->billing->enabled()) {
            return $this->fail('The assistant service is turned off right now.', 503);
        }

        $license = $this->resolveLicense($request);

        // 401 is what the app turns into "your key is not right", which is the
        // correct thing to say to a device whose license we cannot resolve.
        if (! $license) {
            return $this->fail('Invalid or expired license.', 401);
        }

        // Every message is paid from the wallet of the account this device is
        // linked to - free key or paid. No account = nobody to charge.
        if ($license->user_id === null) {
            return $this->fail('Link this device to your account first: xman4289.com/giggok/link', 403, 'not_linked');
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

        // Only models the admin offers, at the admin's price. Nothing offered =
        // the service is not open, whatever the master switch says.
        $offer = $this->billing->pick($data['model'] ?? null);
        if ($offer === null || ! $this->chat->isConfigured()) {
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

        // Pay first (under a wallet lock), answer second, refund on failure -
        // see AppAiBilling for why it is this way round.
        try {
            $payment = $this->billing->charge($license->user_id, $offer['price'], $offer['id']);
        } catch (AppAiBillingException $e) {
            return match ($e->reason) {
                // 402 is what the app turns into "top up your credit"
                AppAiBillingException::INSUFFICIENT => $this->fail(
                    'Not enough credit. Top up at xman4289.com/wallet/topup', 402, $e->reason),
                AppAiBillingException::DAILY_CAP => $this->fail(
                    'Daily spending limit reached. It resets at midnight.', 429, $e->reason),
                default => $this->fail('Your wallet is suspended. Please contact us.', 403, $e->reason),
            };
        }

        // Written before the call, so a second message sent while this one is
        // still thinking already counts against today's cap.
        $usage = AppAiUsage::create([
            'user_id' => $license->user_id,
            'license_key_id' => $license->id,
            'license_key' => $key,
            'model' => $offer['id'],
            'message_count' => count($turns),
            'chars_in' => $chars,
            'ok' => false,
            'price' => $offer['price'],
            'wallet_transaction_id' => $payment?->id,
            'ip_address' => $request->ip(),
        ]);
        $payment?->update(['reference_id' => $usage->id]);

        $result = ['success' => false, 'message' => null];
        try {
            $result = $this->chat->chat($turns, $system, $offer['id']);
        } catch (\Throwable $e) {
            // Never hand an upstream error text to the app: it can carry the
            // provider name, our model choice, and sometimes fragments of the
            // request. The app shows whatever we put in error.message.
            Log::warning('app ai proxy failed', ['error' => $e->getMessage()]);
        }

        $answer = is_string($result['message'] ?? null) ? trim($result['message']) : '';
        $ok = ($result['success'] ?? false) && $answer !== '';

        $usage->update([
            'provider' => $result['provider'] ?? null,
            'model' => $result['model'] ?? $offer['id'],
            'chars_out' => mb_strlen($answer),
            'ok' => $ok,
        ]);

        if (! $ok) {
            // No answer = no charge
            if ($payment) {
                try {
                    $this->billing->refund($payment, $usage->id);
                    $usage->update(['refunded' => true]);
                } catch (\Throwable $e) {
                    Log::error('app ai refund failed', ['usage' => $usage->id, 'error' => $e->getMessage()]);
                }
            }

            return $this->fail('The assistant could not answer just now. You were not charged.', 502);
        }

        $userId = $license->user_id;

        // OpenAI's shape, because that is what the app already parses.
        return response()->json([
            'id' => 'giggok-' . uniqid(),
            'object' => 'chat.completion',
            'created' => now()->timestamp,
            'model' => $offer['id'],
            'choices' => [[
                'index' => 0,
                'message' => ['role' => 'assistant', 'content' => $answer],
                'finish_reason' => 'stop',
            ]],
            // What this message cost and what is left, so the app's credit bar
            // moves without asking again. Named so it cannot be mistaken for
            // OpenAI's own usage block.
            'giggok_billing' => [
                'charged' => $offer['price'],
                'currency' => AppAiBilling::CURRENCY,
                'balance' => $this->billing->balance($userId),
                'spent_today' => $this->billing->spentToday($userId),
                'daily_cap' => $this->billing->dailyCap(),
            ],
        ]);
    }

    /**
     * Errors in OpenAI's shape too, so the app reads error.message - plus a
     * stable code the app can branch on without parsing English.
     */
    protected function fail(string $message, int $status, ?string $code = null): JsonResponse
    {
        $error = ['message' => $message, 'type' => 'giggok_proxy'];
        if ($code !== null) {
            $error['code'] = $code;
            if ($code === AppAiBillingException::INSUFFICIENT) {
                $error['topup_url'] = $this->billing->topupUrl();
            }
            if ($code === 'not_linked') {
                $error['link_url'] = url('/giggok/link');
            }
        }

        return response()->json(['error' => $error], $status);
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
