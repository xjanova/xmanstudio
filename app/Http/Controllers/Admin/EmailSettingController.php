<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailSettingController extends Controller
{
    public function index()
    {
        $settings = [
            'resend_api_key' => PaymentSetting::get('resend_api_key'),
            'mail_from_address' => PaymentSetting::get('mail_from_address', config('mail.from.address')),
            'mail_from_name' => PaymentSetting::get('mail_from_name', config('app.name')),
            'mail_enabled' => PaymentSetting::get('mail_enabled', true),
        ];

        return view('admin.email-settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'mail_enabled' => 'boolean',
            'resend_api_key' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        PaymentSetting::set('mail_enabled', $request->boolean('mail_enabled'), [
            'group' => 'email',
            'type' => 'boolean',
            'label' => 'เปิดใช้งานระบบอีเมล',
        ]);

        if (! empty($validated['resend_api_key'])) {
            PaymentSetting::set('resend_api_key', $validated['resend_api_key'], [
                'group' => 'email',
                'type' => 'string',
                'label' => 'Resend API Key',
                'is_encrypted' => true,
            ]);

            // Update runtime config
            config(['services.resend.key' => $validated['resend_api_key']]);
        }

        if (! empty($validated['mail_from_address'])) {
            PaymentSetting::set('mail_from_address', $validated['mail_from_address'], [
                'group' => 'email',
                'type' => 'string',
                'label' => 'อีเมลผู้ส่ง',
            ]);
        }

        if (! empty($validated['mail_from_name'])) {
            PaymentSetting::set('mail_from_name', $validated['mail_from_name'], [
                'group' => 'email',
                'type' => 'string',
                'label' => 'ชื่อผู้ส่ง',
            ]);
        }

        return redirect()->back()->with('success', 'บันทึกการตั้งค่าอีเมลเรียบร้อยแล้ว');
    }

    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        // Apply dynamic config
        $this->applyDynamicMailConfig();

        try {
            Mail::to($validated['test_email'])->send(new TestMail);

            return redirect()->back()->with('success', "ส่งอีเมลทดสอบไปที่ {$validated['test_email']} เรียบร้อยแล้ว");
        } catch (\Exception $e) {
            Log::error('Test email failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'ส่งอีเมลไม่สำเร็จ กรุณาตรวจสอบ API Key และการตั้งค่า');
        }
    }

    private function applyDynamicMailConfig(): void
    {
        $apiKey = PaymentSetting::get('resend_api_key');
        if ($apiKey) {
            config(['services.resend.key' => $apiKey]);
        }

        $fromAddress = PaymentSetting::get('mail_from_address');
        if ($fromAddress) {
            config(['mail.from.address' => $fromAddress]);
        }

        $fromName = PaymentSetting::get('mail_from_name');
        if ($fromName) {
            config(['mail.from.name' => $fromName]);
        }
    }
}
