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
        ];

        foreach ($fields as $field) {
            Setting::setValue($field, $request->input($field, ''), 'string', 'contact', null, true);
        }

        return redirect()
            ->route('admin.contact-settings.index')
            ->with('success', 'อัปเดตข้อมูลติดต่อเรียบร้อยแล้ว');
    }
}
