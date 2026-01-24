<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ThemeService
{
    /**
     * Available themes
     */
    public const THEMES = [
        'classic' => [
            'name' => 'Classic',
            'description' => 'ธีมคลาสสิก เรียบง่าย สะอาดตา',
            'preview' => '/images/themes/classic-preview.png',
            'features' => ['เรียบง่าย', 'โหลดเร็ว', 'Light Mode'],
            'icon' => 'sun',
            'gradient' => 'from-gray-200 to-gray-400',
        ],
        'premium' => [
            'name' => 'Premium',
            'description' => 'ธีมพรีเมียม ทันสมัย พร้อม animation สวยงาม',
            'preview' => '/images/themes/premium-preview.png',
            'features' => ['Animations', 'Gradients', 'Dark Mode'],
            'icon' => 'sparkles',
            'gradient' => 'from-indigo-500 to-purple-600',
        ],
    ];

    /**
     * Default theme
     */
    public const DEFAULT_THEME = 'classic';

    /**
     * Get site default theme
     */
    public static function getSiteDefaultTheme(): string
    {
        return Cache::remember('site_theme', 3600, function () {
            return Setting::getValue('site_theme', self::DEFAULT_THEME);
        });
    }

    /**
     * Get current theme (respecting user preference)
     */
    public static function getCurrentTheme(): string
    {
        // Check if user is logged in and has a preferred theme
        if (Auth::check()) {
            $userTheme = Auth::user()->getPreferredTheme();
            if ($userTheme && self::isValidTheme($userTheme)) {
                return $userTheme;
            }
        }

        // Fall back to site default theme
        return self::getSiteDefaultTheme();
    }

    /**
     * Get user's preferred theme or null if using site default
     */
    public static function getUserTheme(): ?string
    {
        if (Auth::check()) {
            return Auth::user()->getPreferredTheme();
        }

        return null;
    }

    /**
     * Set theme
     */
    public static function setTheme(string $theme): bool
    {
        if (! array_key_exists($theme, self::THEMES)) {
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
