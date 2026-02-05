<?php

namespace App\Http\Controllers;

use App\Services\ThemeService;
use Illuminate\Http\Request;

class UserThemeController extends Controller
{
    /**
     * Display user theme settings
     */
    public function index()
    {
        $themes = ThemeService::THEMES;
        $currentTheme = ThemeService::getCurrentTheme();
        $userTheme = auth()->user()->getPreferredTheme();
        $siteDefaultTheme = ThemeService::getSiteDefaultTheme();

        return view('customer.settings.theme', compact(
            'themes',
            'currentTheme',
            'userTheme',
            'siteDefaultTheme'
        ));
    }

    /**
     * Update user theme preference
     */
    public function update(Request $request)
    {
        $request->validate([
            'theme' => ['nullable', 'string', 'in:' . implode(',', array_keys(ThemeService::THEMES)) . ',default'],
        ]);

        $theme = $request->theme;

        // If 'default' is selected, remove user preference (use site default)
        if ($theme === 'default') {
            $theme = null;
        }

        auth()->user()->setPreferredTheme($theme);

        return redirect()->route('customer.settings.theme')
            ->with('success', 'บันทึกการตั้งค่าธีมเรียบร้อยแล้ว');
    }
}
