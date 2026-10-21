<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Push for the Anti X phone app (Firebase project "anti-x-mobile", separate from SmsChecker's).
 *
 * The message is fixed here: an Anti X PC only says how serious and how many — no address, no server name, nothing
 * from the event. The phone opens the app and reads the details from its PC over the signed, encrypted channel.
 * Credentials: Admin → Anti X Mobile push (a service account JSON of the Firebase project).
 */
class AntiXPushService
{
    public const DEFAULT_PROJECT = 'anti-x-mobile';

    public function projectId(): string
    {
        return (string) (Setting::getValue('antix_fcm_project_id') ?: self::DEFAULT_PROJECT);
    }

    /** The service account's e-mail when the credentials are in place, null otherwise. */
    public function serviceAccount(): ?string
    {
        $creds = $this->credentials();

        return $creds['client_email'] ?? null;
    }

    public function isConfigured(): bool
    {
        return $this->serviceAccount() !== null;
    }

    /**
     * @param  list<string>  $tokens
     * @return array{sent: int, invalid: list<string>}
     *
     * @throws RuntimeException when no credentials are configured or Google refuses them
     */
    public function send(array $tokens, string $level, int $count): array
    {
        $accessToken = $this->accessToken();
        [$title, $body] = $this->text($level, $count);
        $sent = 0;
        $invalid = [];

        foreach ($tokens as $token) {
            $response = Http::withToken($accessToken)->timeout(10)->post(
                'https://fcm.googleapis.com/v1/projects/' . $this->projectId() . '/messages:send',
                ['message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => ['type' => 'antix_alert', 'level' => $level],
                    'android' => [
                        'priority' => 'high',
                        'ttl' => '3600s',
                        // ข้อความใหม่แทนอันเดิมบนจอ ไม่ซ้อนเป็นกอง
                        'notification' => ['channel_id' => 'antix_alerts', 'tag' => 'antix-alert'],
                    ],
                ]]
            );

            if ($response->successful()) {
                $sent++;

                continue;
            }

            $code = $response->json('error.details.0.errorCode') ?? $response->json('error.status');
            if ($response->status() === 404 || in_array($code, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                $invalid[] = $token;
            } else {
                Log::warning('Anti X push: FCM refused', ['status' => $response->status(), 'code' => $code]);
            }
        }

        return ['sent' => $sent, 'invalid' => $invalid];
    }

    /** @return array{0: string, 1: string} */
    public function text(string $level, int $count): array
    {
        $many = $count > 1 ? ' (' . number_format($count) . ' รายการ)' : '';

        return match ($level) {
            'critical' => ['Anti X', 'พบเหตุการณ์ร้ายแรงบนเครื่องที่ดูแล' . $many . ' — เปิดแอปเพื่อดู'],
            'warning' => ['Anti X', 'มีเหตุการณ์ที่ควรดูบนเครื่องที่ดูแล' . $many],
            default => ['Anti X', 'ทดสอบแจ้งเตือน — มือถือเครื่องนี้รับแจ้งเตือนจาก Anti X ได้แล้ว'],
        };
    }

    /** @return array<string, mixed>|null */
    private function credentials(): ?array
    {
        $path = Setting::getValue('antix_fcm_credentials_path');
        if (! $path || ! is_file($path)) {
            return null;
        }
        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) && ! empty($json['client_email']) && ! empty($json['private_key']) ? $json : null;
    }

    private function accessToken(): string
    {
        $creds = $this->credentials() ?? throw new RuntimeException('not configured');

        return Cache::remember('antix-push:token:' . md5($creds['client_email']), 3000, function () use ($creds) {
            $now = time();
            $segments = [
                $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
                $this->b64(json_encode([
                    'iss' => $creds['client_email'],
                    'sub' => $creds['client_email'],
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'iat' => $now,
                    'exp' => $now + 3600,
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                ])),
            ];
            if (! openssl_sign(implode('.', $segments), $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('cannot sign with the service account key');
            }
            $segments[] = $this->b64($signature);

            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => implode('.', $segments),
            ]);
            if (! $response->successful() || ! $response->json('access_token')) {
                throw new RuntimeException('Google refused the service account (HTTP ' . $response->status() . ')');
            }

            return (string) $response->json('access_token');
        });
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
