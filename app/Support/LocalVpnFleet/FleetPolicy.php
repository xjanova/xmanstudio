<?php

namespace App\Support\LocalVpnFleet;

use App\Models\Setting;

/**
 * Tier limits and budget thresholds for the relay fleet, stored as JSON in the
 * `localvpn_fleet_policy` setting and edited at LocalVPN → Fleet → Policy.
 *
 * Unset (or a damaged value) falls back to the defaults from the spec, key by
 * key, so a half-written policy can never switch the limits off.
 */
final class FleetPolicy
{
    public const SETTING_KEY = 'localvpn_fleet_policy';

    public const DEFAULTS = [
        'tiers' => [
            'free' => ['relay_kbps' => 1500, 'relay_daily_mb' => 300, 'game' => false, 'exit_daily_mb' => 0],
            'trial' => ['relay_kbps' => 8000, 'relay_daily_mb' => 3000, 'game' => true, 'exit_daily_mb' => 1500],
            'pro' => ['relay_kbps' => 20000, 'relay_daily_mb' => 10000, 'game' => true, 'exit_daily_mb' => 5000],
        ],
        'budget' => ['monthly_thb' => 3000, 'free_cut_pct' => 80, 'trial_cut_pct' => 90, 'pro_cut_pct' => 98],
    ];

    private const TIER_INTS = ['relay_kbps', 'relay_daily_mb', 'exit_daily_mb'];

    private const PCT_KEYS = ['free_cut_pct', 'trial_cut_pct', 'pro_cut_pct'];

    /** @return array{tiers: array<string, array>, budget: array} */
    public static function current(): array
    {
        return self::normalize(Setting::getValue(self::SETTING_KEY));
    }

    /** @return array{relay_kbps: int, relay_daily_mb: int, game: bool, exit_daily_mb: int} */
    public static function tier(array $policy, string $tier): array
    {
        return $policy['tiers'][$tier] ?? $policy['tiers'][Tier::FREE];
    }

    /** @return array{tiers: array<string, array>, budget: array} */
    public static function normalize(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        $raw = is_array($raw) ? $raw : [];
        $policy = self::DEFAULTS;

        foreach (Tier::ALL as $tier) {
            $given = $raw['tiers'][$tier] ?? [];
            if (! is_array($given)) {
                continue;
            }
            foreach (self::TIER_INTS as $key) {
                if (isset($given[$key]) && is_numeric($given[$key]) && $given[$key] >= 0) {
                    $policy['tiers'][$tier][$key] = (int) $given[$key];
                }
            }
            if (array_key_exists('game', $given)) {
                $policy['tiers'][$tier]['game'] = filter_var($given['game'], FILTER_VALIDATE_BOOLEAN);
            }
        }

        $budget = is_array($raw['budget'] ?? null) ? $raw['budget'] : [];
        if (isset($budget['monthly_thb']) && is_numeric($budget['monthly_thb']) && $budget['monthly_thb'] >= 0) {
            $policy['budget']['monthly_thb'] = round((float) $budget['monthly_thb'], 2);
        }
        foreach (self::PCT_KEYS as $key) {
            if (isset($budget[$key]) && is_numeric($budget[$key]) && $budget[$key] > 0 && $budget[$key] <= 100) {
                $policy['budget'][$key] = (int) $budget[$key];
            }
        }

        return $policy;
    }

    /** Validation rules for the admin form (tiers[free][relay_kbps] …). */
    public static function rules(): array
    {
        $rules = [
            'budget.monthly_thb' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'budget.free_cut_pct' => ['required', 'integer', 'min:1', 'max:100'],
            'budget.trial_cut_pct' => ['required', 'integer', 'min:1', 'max:100', 'gte:budget.free_cut_pct'],
            'budget.pro_cut_pct' => ['required', 'integer', 'min:1', 'max:100', 'gte:budget.trial_cut_pct'],
        ];

        foreach (Tier::ALL as $tier) {
            foreach (self::TIER_INTS as $key) {
                $rules["tiers.{$tier}.{$key}"] = ['required', 'integer', 'min:0', 'max:10000000'];
            }
            $rules["tiers.{$tier}.game"] = ['required', 'boolean'];
        }

        return $rules;
    }

    /** Thai messages for the ordering rule; the rest use the site's defaults. */
    public static function messages(): array
    {
        return [
            'budget.trial_cut_pct.gte' => 'เกณฑ์ตัด Trial ต้องไม่น้อยกว่าเกณฑ์ตัด Free',
            'budget.pro_cut_pct.gte' => 'เกณฑ์ตัด Pro ต้องไม่น้อยกว่าเกณฑ์ตัด Trial',
        ];
    }

    /** Save validated form input. */
    public static function save(array $input): array
    {
        $policy = self::normalize($input);
        Setting::setValue(self::SETTING_KEY, $policy, 'json', 'localvpn', 'LocalVPN relay fleet: tier limits and budget');

        return $policy;
    }
}
