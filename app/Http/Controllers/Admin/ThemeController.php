<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /**
     * Display theme settings page
     */
    public function index()
    {
        // Use getSiteDefaultTheme() to show actual site default, not user's personal preference
        $currentTheme = ThemeService::getSiteDefaultTheme();
        $themes = ThemeService::getAvailableThemes();

        // Get admin's personal theme for info display
        $adminPersonalTheme = ThemeService::getUserTheme();

        return view('admin.theme.index', compact('currentTheme', 'themes', 'adminPersonalTheme'));
    }

    /**
     * Update theme
     */
    public function update(Request $request)
    {
        $request->validate([
            'theme' => 'required|string|in:classic,premium',
        ]);

        $theme = $request->input('theme');

        if (ThemeService::setTheme($theme)) {
            return redirect()->route('admin.theme.index')
                ->with('success', 'เปลี่ยนธีมเป็น "'.ThemeService::getThemeInfo($theme)['name'].'" เรียบร้อยแล้ว');
        }

        return redirect()->route('admin.theme.index')
            ->with('error', 'ไม่สามารถเปลี่ยนธีมได้ กรุณาลองใหม่อีกครั้ง');
    }
}
