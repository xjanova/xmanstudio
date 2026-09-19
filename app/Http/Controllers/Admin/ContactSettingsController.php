<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ContactSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'contact_phone' => Setting::getValue('contact_phone', ''),
            'contact_phone_name' => Setting::getValue('contact_phone_name', ''),
            'contact_email' => Setting::getValue('contact_email', ''),
            'contact_facebook_name' => Setting::getValue('contact_facebook_name', ''),
            'contact_facebook_url' => Setting::getValue('contact_facebook_url', ''),
            'contact_line_id' => Setting::getValue('contact_line_id', ''),
            'contact_line_url' => Setting::getValue('contact_line_url', ''),
            'contact_youtube_name' => Setting::getValue('contact_youtube_name', ''),
            'contact_youtube_url' => Setting::getValue('contact_youtube_url', ''),
            'contact_address' => Setting::getValue('contact_address', ''),
            // พิมพ์ลงหัวใบเสนอราคา/ใบแจ้งหนี้ ไม่ได้โชว์ในหน้าติดต่อเรา
            'company_name' => Setting::getValue('company_name', ''),
            'company_tax_id' => Setting::getValue('company_tax_id', ''),
        ];

        return view('admin.contact.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'contact_phone' => 'nullable|string|max:50',
            'contact_phone_name' => 'nullable|string|max:100',
            'contact_email' => 'nullable|email|max:255',
            'contact_facebook_name' => 'nullable|string|max:255',
            'contact_facebook_url' => 'nullable|url|max:500',
            'contact_line_id' => 'nullable|string|max:100',
            'contact_line_url' => 'nullable|url|max:500',
            'contact_youtube_name' => 'nullable|string|max:255',
            'contact_youtube_url' => 'nullable|url|max:500',
            'contact_address' => 'nullable|string|max:500',
            'company_name' => 'nullable|string|max:255',
            // 13 หลักตามบัตรผู้เสียภาษี ยอมให้พิมพ์ขีดคั่นได้เพราะคนพิมพ์ตามที่เห็นบนเอกสาร
            // แต่ต้องนับเป็น 13 หลักจริง — เลขผิดบนเอกสารภาษีแย่กว่าไม่ใส่เลย
            'company_tax_id' => ['nullable', 'string', 'max:25', function (string $attribute, mixed $value, callable $fail) {
                if (strlen(preg_replace('/\D/', '', (string) $value)) !== 13) {
                    $fail('เลขประจำตัวผู้เสียภาษีต้องเป็นตัวเลข 13 หลัก (ใส่ขีดคั่นได้)');
                }
            }],
        ]);

        $fields = [
            'contact_phone',
            'contact_phone_name',
            'contact_email',
            'contact_facebook_name',
            'contact_facebook_url',
            'contact_line_id',
            'contact_line_url',
            'contact_youtube_name',
            'contact_youtube_url',
            'contact_address',
            'company_name',
            'company_tax_id',
        ];

        foreach ($fields as $field) {
            Setting::setValue($field, $request->input($field, ''), 'string', 'contact', null, true);
        }

        return redirect()
            ->route('admin.contact-settings.index')
            ->with('success', 'อัปเดตข้อมูลติดต่อเรียบร้อยแล้ว');
    }
}
