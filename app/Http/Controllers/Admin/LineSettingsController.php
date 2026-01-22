<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LineSettingsController extends Controller
{
    /**
     * Display Line settings page.
     */
    public function index()
    {
        $settings = [
            // Line Messaging API
            'line_channel_access_token' => Setting::getValue('line_channel_access_token', ''),
            'line_channel_secret' => Setting::getValue('line_channel_secret', ''),
            'line_messaging_enabled' => Setting::getValue('line_messaging_enabled', false),

            // Line Login
            'line_login_channel_id' => Setting::getValue('line_login_channel_id', ''),
            'line_login_channel_secret' => Setting::getValue('line_login_channel_secret', ''),
            'line_login_enabled' => Setting::getValue('line_login_enabled', false),

            // Line Notify
            'line_notify_token' => Setting::getValue('line_notify_token', ''),
            'line_notify_enabled' => Setting::getValue('line_notify_enabled', false),

            // Line LIFF
            'line_liff_id' => Setting::getValue('line_liff_id', ''),
            'line_liff_enabled' => Setting::getValue('line_liff_enabled', false),

            // Notification Settings
            'line_notify_new_order' => Setting::getValue('line_notify_new_order', true),
            'line_notify_new_quotation' => Setting::getValue('line_notify_new_quotation', true),
            'line_notify_payment_received' => Setting::getValue('line_notify_payment_received', true),
            'line_notify_new_support_ticket' => Setting::getValue('line_notify_new_support_ticket', true),
            'line_notify_new_user' => Setting::getValue('line_notify_new_user', false),

            // Admin User ID
            'line_admin_user_id' => Setting::getValue('line_admin_user_id', ''),
        ];

        return view('admin.line-settings.index', compact('settings'));
    }

    /**
     * Update Line settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'line_channel_access_token' => 'nullable|string|max:500',
            'line_channel_secret' => 'nullable|string|max:100',
            'line_login_channel_id' => 'nullable|string|max:50',
            'line_login_channel_secret' => 'nullable|string|max:100',
            'line_notify_token' => 'nullable|string|max:100',
            'line_liff_id' => 'nullable|string|max:50',
            'line_admin_user_id' => 'nullable|string|max:100',
        ]);

        // Line Messaging API
        if ($request->filled('line_channel_access_token')) {
            Setting::setValue('line_channel_access_token', $request->line_channel_access_token, 'line');
        }
        if ($request->filled('line_channel_secret')) {
            Setting::setValue('line_channel_secret', $request->line_channel_secret, 'line');
        }
        Setting::setValue('line_messaging_enabled', $request->boolean('line_messaging_enabled'), 'line', 'boolean');

        // Line Login
        if ($request->filled('line_login_channel_id')) {
            Setting::setValue('line_login_channel_id', $request->line_login_channel_id, 'line');
        }
        if ($request->filled('line_login_channel_secret')) {
            Setting::setValue('line_login_channel_secret', $request->line_login_channel_secret, 'line');
        }
        Setting::setValue('line_login_enabled', $request->boolean('line_login_enabled'), 'line', 'boolean');

        // Line Notify
        if ($request->filled('line_notify_token')) {
            Setting::setValue('line_notify_token', $request->line_notify_token, 'line');
        }
        Setting::setValue('line_notify_enabled', $request->boolean('line_notify_enabled'), 'line', 'boolean');

        // Line LIFF
        if ($request->filled('line_liff_id')) {
            Setting::setValue('line_liff_id', $request->line_liff_id, 'line');
        }
        Setting::setValue('line_liff_enabled', $request->boolean('line_liff_enabled'), 'line', 'boolean');

        // Notification Settings
        Setting::setValue('line_notify_new_order', $request->boolean('line_notify_new_order'), 'line', 'boolean');
        Setting::setValue('line_notify_new_quotation', $request->boolean('line_notify_new_quotation'), 'line', 'boolean');
        Setting::setValue('line_notify_payment_received', $request->boolean('line_notify_payment_received'), 'line', 'boolean');
        Setting::setValue('line_notify_new_support_ticket', $request->boolean('line_notify_new_support_ticket'), 'line', 'boolean');
        Setting::setValue('line_notify_new_user', $request->boolean('line_notify_new_user'), 'line', 'boolean');

        // Admin User ID
        if ($request->filled('line_admin_user_id')) {
            Setting::setValue('line_admin_user_id', $request->line_admin_user_id, 'line');
        }

        return redirect()->route('admin.line-settings.index')
            ->with('success', 'บันทึกการตั้งค่า Line เรียบร้อยแล้ว');
    }

    /**
     * Test Line Messaging API connection.
     */
    public function testMessaging()
    {
        $token = Setting::getValue('line_channel_access_token');

        if (empty($token)) {
            return response()->json(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า Channel Access Token']);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get('https://api.line.me/v2/bot/info');

            if ($response->successful()) {
                $botInfo = $response->json();

                return response()->json([
                    'success' => true,
                    'message' => 'เชื่อมต่อสำเร็จ! Bot: '.$botInfo['displayName'],
                    'data' => $botInfo,
                ]);
            }

            return response()->json(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อได้: '.$response->status()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage()]);
        }
    }

    /**
     * Test Line Notify connection.
     */
    public function testNotify()
    {
        $token = Setting::getValue('line_notify_token');

        if (empty($token)) {
            return response()->json(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า Line Notify Token']);
        }

        try {
            $response = Http::asForm()->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->post('https://notify-api.line.me/api/notify', [
                'message' => "\n[XMAN Studio] ทดสอบการเชื่อมต่อ Line Notify สำเร็จ!\nเวลา: ".now()->format('d/m/Y H:i:s'),
            ]);

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'ส่งข้อความทดสอบสำเร็จ!']);
            }

            return response()->json(['success' => false, 'message' => 'ไม่สามารถส่งข้อความได้: '.$response->status()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage()]);
        }
    }
}
