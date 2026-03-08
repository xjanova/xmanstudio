<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class TurnstileSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'turnstile_enabled' => Setting::getValue('turnstile_enabled', false),
            'turnstile_site_key' => Setting::getValue('turnstile_site_key', ''),
            'turnstile_secret_key' => Setting::getValue('turnstile_secret_key', ''),
            'turnstile_login' => Setting::getValue('turnstile_login', false),
            'turnstile_register' => Setting::getValue('turnstile_register', false),
            'turnstile_checkout' => Setting::getValue('turnstile_checkout', false),
            'turnstile_support' => Setting::getValue('turnstile_support', false),
        ];

        return view('admin.turnstile.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'turnstile_site_key' => 'nullable|string|max:255',
            'turnstile_secret_key' => 'nullable|string|max:255',
        ]);

        // Boolean toggles
        $booleanFields = [
            'turnstile_enabled',
            'turnstile_login',
            'turnstile_register',
            'turnstile_checkout',
            'turnstile_support',
        ];

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
