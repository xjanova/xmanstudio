<?php

namespace App\Support;

use App\Models\Setting;

/**
 * When the shop warns a customer that a domain is running out.
 *
 * These used to be command-line defaults (--notice-days=37 --charge-days=30),
 * and since the scheduler calls `domains:renew` with no options, they were
 * effectively hard-coded. Changing when a customer hears from us needed a
 * deploy, which is the wrong shape for a number an operator will want to tune
 * against real complaints.
 *
 * Two separate things live here, and confusing them is how a customer gets
 * charged without warning:
 *
 *   noticeDays  — the auto-renew warning: "we will take X baht on this date".
 *                 One per period, and the charge is blocked until it has been
 *                 out for leadDays.
 *   reminderDays — for domains WITHOUT auto-renew. Nobody is going to charge
 *                 them, so nothing was ever sent, and the domain simply
 *                 expired. These are the "renew it yourself" nudges, one per
 *                 milestone.
 */
class DomainReminders
{
    public const DEFAULT_NOTICE_DAYS = 37;

    public const DEFAULT_CHARGE_DAYS = 30;

    public const DEFAULT_LEAD_DAYS = 3;

    /** Milestones for domains that will not renew themselves. */
    public const DEFAULT_REMINDER_DAYS = [60, 30, 14, 7, 1];

    /** Nothing may be scheduled further out than this. */
    public const MAX_DAYS = 180;

    public static function enabled(): bool
    {
        return (bool) Setting::getValue('domain_reminders_enabled', true);
    }

    /** How many days before expiry the auto-renew warning goes out. */
    public static function noticeDays(): int
    {
        return self::clamp(
            (int) Setting::getValue('domain_notice_days', self::DEFAULT_NOTICE_DAYS),
            self::DEFAULT_NOTICE_DAYS
        );
    }

    /** How many days before expiry the wallet is charged. */
    public static function chargeDays(): int
    {
        return self::clamp(
            (int) Setting::getValue('domain_charge_days', self::DEFAULT_CHARGE_DAYS),
            self::DEFAULT_CHARGE_DAYS
        );
    }

    /**
     * How long the warning must have been sitting in the inbox before the
     * money moves. A warning that arrives with the receipt is not a warning.
     */
    public static function leadDays(): int
    {
        return max(0, min(30, (int) Setting::getValue('domain_notice_lead_days', self::DEFAULT_LEAD_DAYS)));
    }

    /**
     * The manual-renewal milestones, largest first.
     *
     * Descending because the sender walks them and takes the first one the
     * domain has passed — ascending order would fire the 1-day reminder for a
     * domain with 60 days left.
     *
     * @return list<int>
     */
    public static function reminderDays(): array
    {
        $raw = Setting::getValue('domain_reminder_days', null);

        if ($raw === null || trim((string) $raw) === '') {
            return self::DEFAULT_REMINDER_DAYS;
        }

        $days = self::parseDays((string) $raw);

        return $days === [] ? self::DEFAULT_REMINDER_DAYS : $days;
    }

    /**
     * Turn "60, 30, 7" into [60, 30, 7] — sorted, unique, and inside range.
     *
     * Shared with the settings form so what an operator types is validated by
     * exactly the code that will later read it back.
     *
     * @return list<int>
     */
    public static function parseDays(string $raw): array
    {
        $days = [];

        foreach (preg_split('/[,\s]+/', trim($raw)) ?: [] as $piece) {
            if ($piece === '' || ! ctype_digit($piece)) {
                continue;
            }

            $day = (int) $piece;

            if ($day >= 1 && $day <= self::MAX_DAYS) {
                $days[] = $day;
            }
        }

        $days = array_values(array_unique($days));

        // Largest first: see reminderDays().
        rsort($days);

        return array_slice($days, 0, 8);
    }

    /** @param list<int> $days */
    public static function formatDays(array $days): string
    {
        return implode(', ', $days);
    }

    /**
     * The warning has to come before the charge, or the customer is told
     * about money that has already gone.
     */
    public static function scheduleIsCoherent(): bool
    {
        return self::noticeDays() >= self::chargeDays() + self::leadDays();
    }

    private static function clamp(int $value, int $fallback): int
    {
        if ($value < 1 || $value > self::MAX_DAYS) {
            return $fallback;
        }

        return $value;
    }
}
