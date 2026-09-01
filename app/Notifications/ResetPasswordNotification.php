<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

/**
 * The password reset email, in the site's own dark NOVA shell and in both
 * languages.
 *
 * Laravel's stock notification is English-only Markdown on a white card. Every
 * other transactional mail this site sends (orders, payments, commissions) uses
 * `emails.partials.email-base`, so a reset mail that looks nothing like them
 * reads as a phishing attempt to the very customer who is already locked out.
 *
 * Subclassing the framework notification rather than replacing it keeps the
 * token plumbing — and, more importantly, `createUrlUsing()`/`toMailUsing()`
 * remain honoured, so tests and any future override still work.
 */
class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        // Respect an explicit override if one is ever registered, exactly as the
        // parent does, instead of silently winning over it.
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        return (new MailMessage)
            ->subject('ตั้งรหัสผ่านใหม่ / Reset your password — ' . config('app.name'))
            ->view('emails.reset-password', [
                'userName' => $notifiable->name ?: $notifiable->email,
                'resetUrl' => $this->resetUrl($notifiable),
                'expiresInMinutes' => Config::get('auth.passwords.' . Config::get('auth.defaults.passwords') . '.expire', 60),
            ]);
    }
}
