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
        'retro' => [
            'name' => 'Retro Tron',
            'description' => 'ธีม Tron/Retro-Futurism — เขียว-ฟ้าอิเล็กทริก ทองโฟยล์ พื้นหลังนาวี กริดสไตล์ยุค 80s',
            'preview' => '/images/themes/retro-preview.png',
            'features' => ['Electric Cyan', 'Gold Foil', 'Parallax Grid'],
            'icon' => 'bolt',
            'gradient' => 'from-cyan-400 via-yellow-400 to-amber-500',
        ],
    ];

    /**
     * Default theme
     */
    public const DEFAULT_THEME = 'premium';

    /**
     * Get site default theme
     */
    public static function getSiteDefaultTheme(): string
    {
        $theme = Cache::remember('site_theme', 3600, function () {
            return Setting::getValue('site_theme', self::DEFAULT_THEME);
        });

        // Self-heal if the stored theme key is no longer in THEMES
        // (e.g. a theme was retired between deploys).
        if (! self::isValidTheme($theme)) {
            return self::DEFAULT_THEME;
        }

        return $theme;
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
        if (! Auth::check()) {
            return null;
        }

        $theme = Auth::user()->getPreferredTheme();

        // Self-heal if the stored preference is no longer a registered theme.
        return ($theme && self::isValidTheme($theme)) ? $theme : null;
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

        // Retro falls through to layouts.app for non-home pages.
        // home.blade.php extends layouts.retro directly regardless of
        // the current theme — retro is the permanent home surface now.
        return match ($theme) {
            'premium' => 'layouts.app-premium',
            default => 'layouts.app',
        };
    }
}
