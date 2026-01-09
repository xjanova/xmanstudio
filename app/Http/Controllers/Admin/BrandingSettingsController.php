<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BrandingSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'site_logo' => Setting::getValue('site_logo'),
            'site_favicon' => Setting::getValue('site_favicon'),
        ];

        return view('admin.branding.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:png,jpg,jpeg,ico,svg|max:512',
        ], [
            'logo.image' => 'ไฟล์โลโก้ต้องเป็นรูปภาพเท่านั้น',
            'logo.mimes' => 'โลโก้ต้องเป็นไฟล์ประเภท: png, jpg, jpeg, svg, webp',
            'logo.max' => 'ขนาดไฟล์โลโก้ต้องไม่เกิน 2MB',
            'favicon.image' => 'ไฟล์ favicon ต้องเป็นรูปภาพเท่านั้น',
            'favicon.mimes' => 'Favicon ต้องเป็นไฟล์ประเภท: png, jpg, jpeg, ico, svg',
            'favicon.max' => 'ขนาดไฟล์ favicon ต้องไม่เกิน 512KB',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('admin.branding.index')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Handle logo upload
            if ($request->hasFile('logo')) {
                $oldLogo = Setting::getValue('site_logo');
                if ($oldLogo) {
                    Storage::disk('public')->delete($oldLogo);
                }

                $logoPath = $request->file('logo')->store('branding', 'public');
                Setting::setValue('site_logo', $logoPath, 'string', 'branding', 'เส้นทางของโลโก้เว็บไซต์', true);
            }

            // Handle favicon upload
            if ($request->hasFile('favicon')) {
                $oldFavicon = Setting::getValue('site_favicon');
                if ($oldFavicon) {
                    Storage::disk('public')->delete($oldFavicon);
                }

                $faviconPath = $request->file('favicon')->store('branding', 'public');
                Setting::setValue('site_favicon', $faviconPath, 'string', 'branding', 'เส้นทางของ favicon', true);
            }

            return redirect()
                ->route('admin.branding.index')
                ->with('success', 'อัปเดตการตั้งค่าแบรนด์เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            return redirect()
                ->route('admin.branding.index')
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    public function deleteLogo()
    {
        try {
            $oldLogo = Setting::getValue('site_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
                Setting::setValue('site_logo', null, 'string', 'branding');
            }

            return redirect()
                ->route('admin.branding.index')
                ->with('success', 'ลบโลโก้เรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.branding.index')
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    public function deleteFavicon()
    {
        try {
            $oldFavicon = Setting::getValue('site_favicon');
            if ($oldFavicon) {
                Storage::disk('public')->delete($oldFavicon);
                Setting::setValue('site_favicon', null, 'string', 'branding');
            }

            return redirect()
                ->route('admin.branding.index')
                ->with('success', 'ลบ favicon เรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.branding.index')
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }
}
