<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use App\Support\UniverseHome;
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
        $customerTheme = ThemeService::getCustomerTheme();
        $customerThemes = ThemeService::CUSTOMER_THEMES;
        $universeEnabled = UniverseHome::enabled();

        return view('admin.theme.index', compact('currentTheme', 'themes', 'customerTheme', 'customerThemes', 'universeEnabled'));
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

    /**
     * Update the member area's theme — separate from the site theme above
     */
    public function updateCustomer(Request $request)
    {
        $request->validate([
            'customer_theme' => ['required', 'string', 'in:' . implode(',', array_keys(ThemeService::CUSTOMER_THEMES))],
        ]);

        $theme = $request->input('customer_theme');

        if (ThemeService::setCustomerTheme($theme)) {
            return redirect()->route('admin.theme.index')
                ->with('success', 'หลังบ้านสมาชิกใช้ธีม "' . ThemeService::CUSTOMER_THEMES[$theme]['name'] . '" แล้ว');
        }

        return redirect()->route('admin.theme.index')
            ->with('error', 'ไม่สามารถเปลี่ยนธีมหลังบ้านสมาชิกได้ กรุณาลองใหม่อีกครั้ง');
    }

    /**
     * Switch the 3D XMAN Universe home page on or off. Off, every visitor gets the site theme's
     * home page, exactly what a browser without WebGL gets while it is on.
     */
    public function updateUniverse(Request $request)
    {
        $request->validate([
            'home_universe' => ['required', 'boolean'],
        ]);

        $enabled = $request->boolean('home_universe');
        UniverseHome::setEnabled($enabled);

        return redirect()->route('admin.theme.index')
            ->with('success', $enabled
                ? 'เปิดหน้าแรกจักรวาล 3D แล้ว — เครื่องที่ไม่รองรับจะเห็นหน้าแรกของธีมตามเดิม'
                : 'ปิดหน้าแรกจักรวาล 3D แล้ว — ทุกคนเห็นหน้าแรกของธีมเว็บไซต์');
    }
}
