<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class VerifyTurnstile
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $section  The section identifier (login, register, checkout, support)
     */
    public function handle(Request $request, Closure $next, string $section = ''): Response
    {
        // Only verify POST requests
        if (! $request->isMethod('post')) {
            return $next($request);
        }

        // Check if Turnstile is globally enabled
        if (! Setting::getValue('turnstile_enabled', false)) {
            return $next($request);
        }

        // Check if Turnstile is enabled for this specific section
        if ($section && ! Setting::getValue("turnstile_{$section}", false)) {
            return $next($request);
        }

        $secretKey = Setting::getValue('turnstile_secret_key');
        if (! $secretKey) {
            return $next($request);
        }

        $token = $request->input('cf-turnstile-response');

        if (! $token) {
            return $this->failResponse($request, 'กรุณายืนยันว่าคุณไม่ใช่บอท');
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            if ($response->successful() && $response->json('success')) {
                return $next($request);
            }
        } catch (\Throwable $e) {
            // If Cloudflare API is unreachable, allow the request through
            return $next($request);
        }

        return $this->failResponse($request, 'การยืนยันล้มเหลว กรุณาลองใหม่อีกครั้ง');
    }

    private function failResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => ['cf-turnstile-response' => [$message]],
            ], 422);
        }

        return redirect()->back()
            ->withInput($request->except('password', 'password_confirmation'))
            ->withErrors(['cf-turnstile-response' => $message]);
    }
}
