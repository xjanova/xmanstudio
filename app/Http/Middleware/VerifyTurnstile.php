<?php

namespace App\Http\Middleware;

use App\Support\Turnstile;
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

        // Turnstile::enabledFor covers the master switch, the key pair and the
        // per-section toggle together. The <x-turnstile> component asks the same
        // question — if these two ever disagree the form becomes unsubmittable.
        if (! Turnstile::enabledFor($section)) {
            return $next($request);
        }

        $secretKey = Turnstile::secretKey();

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
