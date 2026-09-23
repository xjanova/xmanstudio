<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * The way back in when the phone and the recovery codes are both gone.
 *
 * Runs from the server, which already takes an SSH key to reach — the one
 * place a lost authenticator cannot lock the owner out of. The account is
 * asked to enrol again at its next admin page.
 */
class SecurityTwoFactorResetCommand extends Command
{
    protected $signature = 'security:2fa-reset
                            {email : The admin account to reset}
                            {--force : Skip the confirmation question}';

    protected $description = 'Turn off two-step sign-in for one account so it can enrol a new authenticator';

    public function handle(): int
    {
        $user = User::where('email', (string) $this->argument('email'))->first();

        if (! $user) {
            $this->error('No account with that e-mail.');

            return self::FAILURE;
        }

        if ($user->two_factor_confirmed_at === null) {
            $this->info('Two-step sign-in is not on for this account; nothing to reset.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Reset two-step sign-in for {$user->email}?", false)) {
            $this->comment('Cancelled.');

            return self::SUCCESS;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->info("Reset. {$user->email} will be asked to scan a new QR code at the next admin page.");

        return self::SUCCESS;
    }
}
