<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        // Deliberately identical wording whatever the broker says.
        //
        // Laravel's default reports INVALID_USER ("We can't find a user with
        // that email address") separately from RESET_LINK_SENT, which turns
        // this public form into a membership oracle: anyone can walk a list of
        // addresses and learn which ones hold accounts here. The route is rate
        // limited, but a limit slows enumeration down — it does not stop it.
        //
        // THROTTLED is folded in for the same reason: "you asked too recently"
        // only makes sense about an address that exists, so surfacing it would
        // give the answer back a second way.
        if (in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            return back()->with('status', __('passwords.sent'));
        }

        // Anything else is a genuine failure on our side (mail transport down,
        // broker misconfigured) — the user needs to know it did not work.
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
