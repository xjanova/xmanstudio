<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class ThemeService
{
    /**
     * Available themes
     */
    public const THEMES = [
        'classic' => [
            'name' => 'Classic',
            'description' => 'ธีมคลาสสิก เรียบง่าย',
            'preview' => '/images/themes/classic-preview.png',
        ],
        'premium' => [
            'name' => 'Premium',
            'description' => 'ธีมพรีเมียม ทันสมัย พร้อม animation',
            'preview' => '/images/themes/premium-preview.png',
        ],
    ];

    /**
     * Default theme
     */
    public const DEFAULT_THEME = 'classic';

    /**
     * Get current theme
     */
    public static function getCurrentTheme(): string
    {
        return Cache::remember('site_theme', 3600, function () {
            return Setting::getValue('site_theme', self::DEFAULT_THEME);
        });
    }

    /**
     * Set theme
     */
    public static function setTheme(string $theme): bool
    {
        if (!array_key_exists($theme, self::THEMES)) {
            return false;
        }

        Setting::setValue('site_theme', $theme, 'string', 'appearance', 'Site theme');
        Cache::forget('site_theme');

        return true;
    }

    /**
     * Get all available themes
     */
    public static function getAvailableThemes(): array
    {
        return self::THEMES;
    }

    /**
     * Get theme info
     */
    public static function getThemeInfo(string $theme): ?array
    {
        return self::THEMES[$theme] ?? null;
    }

    /**
     * Check if theme is valid
     */
    public static function isValidTheme(string $theme): bool
    {
        return array_key_exists($theme, self::THEMES);
    }

    /**
     * Get admin layout path based on current theme
     */
    public static function getAdminLayout(): string
    {
        $theme = self::getCurrentTheme();

        return match ($theme) {
            'premium' => 'layouts.admin-premium',
            default => 'layouts.admin',
        };
    }

    /**
     * Get customer layout path based on current theme
     */
    public static function getCustomerLayout(): string
    {
        $theme = self::getCurrentTheme();

        return match ($theme) {
            'premium' => 'layouts.customer-premium',
            default => 'layouts.customer',
        };
    }

    /**
     * Get public layout path based on current theme
     */
    public static function getPublicLayout(): string
    {
        $theme = self::getCurrentTheme();

        return match ($theme) {
            'premium' => 'layouts.app-premium',
            default => 'layouts.app',
        };
    }
}
