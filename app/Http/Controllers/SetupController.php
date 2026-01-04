<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SetupController extends Controller
{
    /**
     * Check if setup is required
     */
    public static function isSetupRequired(): bool
    {
        try {
            // Check if any admin/super_admin user exists
            return ! User::whereIn('role', ['admin', 'super_admin'])->exists();
        } catch (\Exception $e) {
            // Database might not be set up yet
            return true;
        }
    }

    /**
     * Show setup form
     */
    public function index()
    {
        // Redirect if setup already completed
        if (! self::isSetupRequired()) {
            return redirect()->route('home')->with('info', 'ระบบได้รับการตั้งค่าแล้ว');
        }

        return view('setup.index');
    }

    /**
     * Process setup form
     */
    public function store(Request $request)
    {
        // Prevent if setup already completed
        if (! self::isSetupRequired()) {
            return redirect()->route('home')->with('error', 'ระบบได้รับการตั้งค่าแล้ว');
        }

        $validated = $request->validate([
            // Admin info
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],

            // Company info
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_line' => ['nullable', 'string', 'max:100'],

            // Line Messaging API (optional)
            'line_channel_access_token' => ['nullable', 'string'],
            'line_admin_user_id' => ['nullable', 'string'],
        ]);

        // Create super admin user
        $admin = User::create([
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        // Save settings
        $settings = [
            ['group' => 'company', 'key' => 'company_name', 'value' => $validated['company_name']],
            ['group' => 'company', 'key' => 'company_email', 'value' => $validated['company_email']],
            ['group' => 'company', 'key' => 'company_phone', 'value' => $validated['company_phone'] ?? ''],
            ['group' => 'company', 'key' => 'company_line', 'value' => $validated['company_line'] ?? ''],
            ['group' => 'notification', 'key' => 'line_channel_access_token', 'value' => $validated['line_channel_access_token'] ?? ''],
            ['group' => 'notification', 'key' => 'line_admin_user_id', 'value' => $validated['line_admin_user_id'] ?? ''],
            ['group' => 'notification', 'key' => 'notification_enabled', 'value' => 'true'],
            ['group' => 'quotation', 'key' => 'quotation_validity_days', 'value' => '30'],
            ['group' => 'quotation', 'key' => 'vat_rate', 'value' => '7'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Auto login the admin
        auth()->login($admin);

        return redirect()->route('dashboard')->with('success', 'ตั้งค่าระบบสำเร็จ! ยินดีต้อนรับ '.$admin->name);
    }
}
