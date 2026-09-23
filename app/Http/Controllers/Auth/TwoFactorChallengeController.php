<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Alerts\SecurityAlerts;
use App\Support\Auth\LoginLog;
use App\Support\Auth\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * The second step for an admin who has signed in: six digits from the
 * authenticator app, or one of the recovery codes. The admin gate sends every
 * admin session here once (App\Support\Auth\TwoFactor::gate).
 */
class TwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Nothing to ask a customer — and /admin would only answer them with a 403.
        if (! $user->isAdmin()) {
            return redirect()->route('home');
        }

        if (! TwoFactor::enabled($user) || TwoFactor::verified($request, $user)) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() || ! TwoFactor::enabled($user)) {
            return redirect()->route('home');
        }

        $request->validate(['code' => ['required', 'string', 'max:64']]);

        $throttleKey = 'two-factor:' . $user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return back()->withErrors([
                'code' => 'ลองผิดหลายครั้งเกินไป กรุณารอ ' . RateLimiter::availableIn($throttleKey) . ' วินาที',
            ]);
        }

        if (! TwoFactor::attempt($user, (string) $request->input('code'))) {
            RateLimiter::hit($throttleKey, 300);
            LoginLog::failed($user->email, $user, '2fa');
            SecurityAlerts::twoFactorFailed($user, $request->ip());

            return back()->withErrors(['code' => 'รหัสไม่ถูกต้อง หรือถูกใช้ไปแล้ว']);
        }

        RateLimiter::clear($throttleKey);
        TwoFactor::markVerified($request, $user);
        LoginLog::success($user, '2fa');

        return redirect()->intended(route('admin.dashboard'));
    }
}
