<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Auth\Totp;
use App\Support\Auth\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * แอดมิน → ความปลอดภัย → ยืนยันตัวตน 2 ขั้น: enrol an authenticator app, keep
 * recovery codes, move to a new phone.
 *
 * Reachable before enrolment on purpose — the admin gate lets these routes
 * through for an admin who has not set up yet, and sends everything else here.
 */
class TwoFactorController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        if (TwoFactor::enabled($user)) {
            return view('admin.security.two-factor', [
                'enabled' => true,
                'remaining' => TwoFactor::remainingRecoveryCodes($user),
                'freshCodes' => session('two_factor_fresh_codes'),
            ]);
        }

        // One secret per setup attempt, kept in the session until the first code
        // from the app proves it was scanned. Nothing is saved on the account yet.
        $secret = $request->session()->get(TwoFactor::PENDING_KEY);
        if (! is_string($secret) || $secret === '') {
            $secret = Totp::generateSecret();
            $request->session()->put(TwoFactor::PENDING_KEY, $secret);
        }

        $uri = Totp::provisioningUri($secret, $user->email, (string) config('security.two_factor.issuer', 'XMAN Studio'));

        return view('admin.security.two-factor', [
            'enabled' => false,
            'secret' => $secret,
            'qr' => (string) QrCode::format('svg')->size(210)->margin(1)->generate($uri),
            'required' => TwoFactor::required(),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['code' => ['required', 'string', 'max:16']]);

        $secret = $request->session()->get(TwoFactor::PENDING_KEY);

        if (TwoFactor::enabled($user) || ! is_string($secret) || $secret === '') {
            return redirect()->route('admin.security.two-factor.show');
        }

        if (! TwoFactor::acceptStep($user, Totp::verify($secret, (string) $request->input('code')))) {
            return back()->withErrors(['code' => 'รหัสไม่ตรง — ตรวจว่าสแกน QR นี้แล้ว และเวลาในมือถือตรง']);
        }

        [$plain, $hashes] = TwoFactor::newRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $hashes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget(TwoFactor::PENDING_KEY);
        TwoFactor::markVerified($request, $user);

        return redirect()
            ->route('admin.security.two-factor.show')
            ->with('two_factor_fresh_codes', $plain)
            ->with('success', 'เปิดการยืนยันตัวตน 2 ขั้นแล้ว — เก็บรหัสสำรองด้านล่างไว้ในที่ปลอดภัย');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! TwoFactor::enabled($user)) {
            return redirect()->route('admin.security.two-factor.show');
        }

        [$plain, $hashes] = TwoFactor::newRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $hashes])->save();

        return redirect()
            ->route('admin.security.two-factor.show')
            ->with('two_factor_fresh_codes', $plain)
            ->with('success', 'สร้างรหัสสำรองชุดใหม่แล้ว — ชุดเก่าใช้ไม่ได้อีกต่อไป');
    }

    /**
     * Start over with a new phone. Asks for a current code, so a session left
     * open on someone else's screen cannot quietly swap the authenticator.
     */
    public function reset(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['code' => ['required', 'string', 'max:64']]);

        if (! TwoFactor::enabled($user)) {
            return redirect()->route('admin.security.two-factor.show');
        }

        if (! TwoFactor::attempt($user, (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'รหัสไม่ถูกต้อง']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->forget(TwoFactor::PENDING_KEY);

        return redirect()
            ->route('admin.security.two-factor.show')
            ->with('success', 'ยกเลิกเครื่องเดิมแล้ว — สแกน QR ใหม่ด้วยเครื่องที่จะใช้ต่อ');
    }
}
