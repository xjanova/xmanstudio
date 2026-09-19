<?php

namespace App\Support\Quotation;

use App\Models\Setting;

/**
 * The selling numbers: volume discounts, the rush surcharge, how long a
 * quotation stands, and how the money is split into instalments.
 *
 * These were constants in the controller and in the document template, which
 * meant a promotion — or an accountant asking for 40/30/30 — needed a deploy,
 * and the page, the PDF and the invoices could each drift out of step. All of
 * them now read the same settings rows, with the old hardcoded values as the
 * defaults so a site with no rows behaves exactly as before.
 *
 * Every read is sanitised. A row can be hand-edited or left half-saved, and a
 * bad value must not reach the arithmetic on a public page: an unusable
 * setting falls back to the default rather than producing a document with a
 * negative total.
 */
class Pricing
{
    /** @var array<int, array{from: int, percent: float}> */
    public const DEFAULT_TIERS = [
        ['from' => 200000, 'percent' => 5],
        ['from' => 500000, 'percent' => 10],
        ['from' => 1000000, 'percent' => 15],
    ];

    /** @var array<int, array{percent: float, label: string}> */
    public const DEFAULT_SPLIT = [
        ['percent' => 50, 'label' => 'เริ่มงาน'],
        ['percent' => 25, 'label' => 'ส่งมอบงานออกแบบ'],
        ['percent' => 25, 'label' => 'ส่งมอบระบบ'],
    ];

    public const DEFAULT_RUSH_PERCENT = 25.0;

    public const DEFAULT_VALID_DAYS = 30;

    public const DEFAULT_DUE_DAYS = 7;

    /**
     * Volume discount bands, lowest threshold first.
     *
     * The builder shows these to the visitor ("อีก 300,000 จะได้ 10%") and the
     * totals apply them, so both must read this one list or the page can
     * promise a discount the maths never gives.
     *
     * @return array<int, array{from: int, percent: float}>
     */
    public static function discountTiers(): array
    {
        $raw = Setting::getValue('quote_discount_tiers');

        if (! is_array($raw) || $raw === []) {
            return self::DEFAULT_TIERS;
        }

        $tiers = [];
        foreach ($raw as $tier) {
            if (! is_array($tier)) {
                continue;
            }
            $from = (int) ($tier['from'] ?? 0);
            $percent = (float) ($tier['percent'] ?? 0);

            // A 0% band is noise and a 100% band gives the work away: both are
            // almost certainly a typo, and neither belongs in a live document.
            if ($from < 0 || $percent <= 0 || $percent >= 100) {
                continue;
            }

            $tiers[] = ['from' => $from, 'percent' => round($percent, 2)];
        }

        if ($tiers === []) {
            return self::DEFAULT_TIERS;
        }

        usort($tiers, fn (array $a, array $b) => $a['from'] <=> $b['from']);

        return $tiers;
    }

    /**
     * The discount percent a subtotal earns — the highest band it reaches.
     */
    public static function discountPercentFor(float $subtotal): float
    {
        $percent = 0.0;
        foreach (self::discountTiers() as $tier) {
            if ($subtotal >= $tier['from']) {
                $percent = (float) $tier['percent'];
            }
        }

        return $percent;
    }

    /**
     * Surcharge for an urgent timeline, as a percent of the discounted
     * subtotal. Zero is a legitimate setting: it turns rush pricing off
     * without touching the timeline options.
     */
    public static function rushPercent(): float
    {
        $raw = Setting::getValue('quote_rush_percent');

        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return self::DEFAULT_RUSH_PERCENT;
        }

        $percent = (float) $raw;

        return ($percent < 0 || $percent > 200) ? self::DEFAULT_RUSH_PERCENT : round($percent, 2);
    }

    /**
     * How many days a quotation stands before it expires.
     */
    public static function validDays(): int
    {
        $days = (int) Setting::getValue('quote_valid_days', self::DEFAULT_VALID_DAYS);

        return ($days >= 1 && $days <= 365) ? $days : self::DEFAULT_VALID_DAYS;
    }

    /**
     * How many days the customer gets to pay the first instalment.
     */
    public static function dueDays(): int
    {
        $days = (int) Setting::getValue('quote_due_days', self::DEFAULT_DUE_DAYS);

        return ($days >= 0 && $days <= 180) ? $days : self::DEFAULT_DUE_DAYS;
    }

    /**
     * The instalment plan, as percentages that add up to 100.
     *
     * A plan that does not total 100 would either short-invoice the job or
     * over-charge it, so a saved plan that misses is rejected in favour of the
     * default rather than quietly scaled.
     *
     * @return array<int, array{percent: float, label: string}>
     */
    public static function paymentSplit(): array
    {
        $raw = Setting::getValue('quote_payment_split');

        if (! is_array($raw) || $raw === []) {
            return self::normaliseSplit(self::DEFAULT_SPLIT);
        }

        $split = [];
        foreach ($raw as $stage) {
            if (! is_array($stage)) {
                continue;
            }
            $percent = (float) ($stage['percent'] ?? 0);
            if ($percent <= 0 || $percent > 100) {
                continue;
            }
            $label = trim((string) ($stage['label'] ?? ''));
            $split[] = ['percent' => round($percent, 2), 'label' => $label !== '' ? $label : 'งวดชำระ'];
        }

        $sum = array_sum(array_column($split, 'percent'));

        // A little slack, because 33.33 × 3 is how anyone writes thirds.
        if ($split === [] || abs($sum - 100) > 0.05) {
            return self::normaliseSplit(self::DEFAULT_SPLIT);
        }

        return $split;
    }

    /**
     * @param  array<int, array{percent: int|float, label: string}>  $split
     * @return array<int, array{percent: float, label: string}>
     */
    protected static function normaliseSplit(array $split): array
    {
        return array_map(
            fn (array $stage) => ['percent' => (float) $stage['percent'], 'label' => $stage['label']],
            $split
        );
    }

    /**
     * The instalments for a total, in baht.
     *
     * The LAST instalment absorbs the rounding, not the first. Three rounded
     * 33.33% shares of 100,000 leave a satang unaccounted for, and a first
     * instalment that reads 33,334.00 against a document that says 33.33% is
     * the one the customer queries — a remainder is expected to land on the
     * final balance.
     *
     * @return array<int, array{no: int, percent: float, label: string, amount: float}>
     */
    public static function instalments(float $total): array
    {
        $split = self::paymentSplit();
        $total = round($total, 2);
        $rows = [];
        $running = 0.0;
        $last = count($split) - 1;

        foreach ($split as $i => $stage) {
            $amount = $i === $last
                ? round($total - $running, 2)
                : round($total * ($stage['percent'] / 100), 2);
            $running = round($running + $amount, 2);

            $rows[] = [
                'no' => $i + 1,
                'percent' => $stage['percent'],
                'label' => $stage['label'],
                'amount' => $amount,
            ];
        }

        return $rows;
    }
}
