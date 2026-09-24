<?php

namespace App\Support;

/**
 * The price of every license term a product sells, from config('licenses.plans').
 *
 * There used to be nine tables, and they had drifted apart. The pricing API
 * made up 399/2,500/5,000 for any product without an entry of its own —
 * Aipray, free forever, included. AutoTradeX's app and its web checkout quoted
 * different monthly prices, and CluadeX's landing page offered plans its
 * product page did not sell. Everything that quotes or charges a term price
 * asks here now, so the numbers cannot disagree again.
 */
class LicensePlans
{
    /** The money every plan is priced in. */
    public const CURRENCY = 'THB';

    /**
     * The terms a product sells and their prices, in the order it offers them.
     * Empty for a product that sells none.
     *
     * @return array<string, int>
     */
    public static function for(string $slug): array
    {
        return array_map('intval', config('licenses.plans')[$slug] ?? []);
    }

    /** What one term costs, or null when the product does not sell it. */
    public static function price(string $slug, string $term): ?int
    {
        return self::for($slug)[$term] ?? null;
    }

    /**
     * A page's own description of its plans (names, features, durations) with
     * each price filled in from the table. A plan the table does not price is
     * dropped, so no page can offer a term the store does not sell.
     *
     * @param  array<string, array<string, mixed>>  $plans
     * @return array<string, array<string, mixed>>
     */
    public static function priced(string $slug, array $plans): array
    {
        $prices = self::for($slug);
        $priced = [];

        foreach ($plans as $term => $plan) {
            if (isset($prices[$term])) {
                $priced[$term] = ['price' => $prices[$term]] + $plan;
            }
        }

        return $priced;
    }

    /**
     * How much a year saves against twelve months, in whole percent, for the
     * "save N%" badge. Rounded down, so the badge never claims more than the
     * customer actually saves. Null when the product lacks either term.
     */
    public static function yearlySaving(string $slug): ?int
    {
        $monthly = self::price($slug, 'monthly');
        $yearly = self::price($slug, 'yearly');

        if (! $monthly || $yearly === null) {
            return null;
        }

        return (int) floor((1 - $yearly / ($monthly * 12)) * 100);
    }

    /** Whether the site cart sells this product's terms (config('licenses.cart_products')). */
    public static function soldInCart(string $slug): bool
    {
        return in_array($slug, config('licenses.cart_products', []), true);
    }
}
