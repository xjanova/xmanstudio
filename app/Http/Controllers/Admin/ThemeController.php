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
        // The theme is site-wide and admin-controlled; there is no per-user
        // override to reconcile against.
        $currentTheme = ThemeService::getSiteDefaultTheme();
        $themes = ThemeService::getAvailableThemes();

        return view('admin.theme.index', compact('currentTheme', 'themes'));
    }

    /**
     * Update theme
     */
    public function update(Request $request)
    {
        $request->validate([
            'theme' => ['required', 'string', 'in:' . implode(',', array_keys(ThemeService::THEMES))],
        ]);

        $theme = $request->input('theme');

        if (ThemeService::setTheme($theme)) {
            return redirect()->route('admin.theme.index')
                ->with('success', 'เปลี่ยนธีมเป็น "' . ThemeService::getThemeInfo($theme)['name'] . '" เรียบร้อยแล้ว');
        }

        return redirect()->route('admin.theme.index')
            ->with('error', 'ไม่สามารถเปลี่ยนธีมได้ กรุณาลองใหม่อีกครั้ง');
    }
}
