<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Turnstile;
use Illuminate\Http\Request;

class TurnstileSettingsController extends Controller
{
    public function index()
    {
        // The per-section boxes read through Turnstile::sectionEnabled, not the
        // raw setting, so a section whose row does not exist yet shows ticked —
        // which is what it actually does. Showing it unticked would be a lie the
        // next Save would then make true.
        $settings = [
            'turnstile_enabled' => Setting::getValue('turnstile_enabled', false),
            'turnstile_site_key' => Setting::getValue('turnstile_site_key', ''),
            'turnstile_secret_key' => Setting::getValue('turnstile_secret_key', ''),
        ];

        foreach (Turnstile::SECTIONS as $section) {
            $settings["turnstile_{$section}"] = Turnstile::sectionEnabled($section);
        }

        return view('admin.turnstile.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'turnstile_site_key' => 'nullable|string|max:255',
            'turnstile_secret_key' => 'nullable|string|max:255',
        ]);

        // Boolean toggles
        $booleanFields = array_merge(
            ['turnstile_enabled'],
            array_map(fn (string $s) => "turnstile_{$s}", Turnstile::SECTIONS),
        );

        foreach ($booleanFields as $field) {
            Setting::setValue($field, $request->boolean($field) ? '1' : '0', 'boolean', 'turnstile');
        }

        // String fields
        Setting::setValue('turnstile_site_key', $request->input('turnstile_site_key', ''), 'string', 'turnstile');

        // Only update secret key if provided (don't clear existing)
        $secretKey = $request->input('turnstile_secret_key');
        if ($secretKey) {
            Setting::setValue('turnstile_secret_key', $secretKey, 'string', 'turnstile');
        }

        return redirect()
            ->route('admin.turnstile.index')
            ->with('success', 'อัปเดตการตั้งค่า Turnstile เรียบร้อยแล้ว');
    }
}
