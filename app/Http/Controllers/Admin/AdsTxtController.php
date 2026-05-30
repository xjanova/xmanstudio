<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdsTxtSetting;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdsTxtController extends Controller
{
    /**
     * Display the ads.txt settings form.
     */
    public function index()
    {
        $setting = AdsTxtSetting::getInstance();

        $adsenseClientId = (string) Setting::getValue('adsense_client_id', '');
        $adsenseEnabled = (bool) Setting::getValue('adsense_enabled', false);

        return view('admin.ads-txt.index', compact('setting', 'adsenseClientId', 'adsenseEnabled'));
    }

    /**
     * Update the ads.txt settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'adsense_client_id' => 'nullable|string|max:50',
            'adsense_enabled' => 'nullable|boolean',
        ]);

        // Normalise the AdSense publisher/client ID. Admins may paste either
        // "pub-1234..." or the full "ca-pub-1234..." form.
        $clientId = trim((string) $request->input('adsense_client_id', ''));
        if ($clientId !== '' && str_starts_with($clientId, 'pub-')) {
            $clientId = 'ca-' . $clientId;
        }

        // Strict validation before it is ever written to settings (it later
        // reaches an HTML attribute + meta tag). Reject anything malformed.
        if ($clientId !== '' && ! preg_match('/^ca-pub-\d{10,20}$/', $clientId)) {
            return redirect()
                ->route('admin.ads-txt.index')
                ->withErrors(['adsense_client_id' => 'รหัส AdSense Publisher ID ต้องอยู่ในรูปแบบ ca-pub-XXXXXXXXXXXXXXXX (ตัวเลข 10-20 หลัก)'])
                ->withInput();
        }

        // Ads.txt file content + on/off
        $setting = AdsTxtSetting::getInstance();
        $setting->update([
            'content' => $request->input('content', ''),
            'enabled' => $request->has('enabled'),
        ]);

        // AdSense verification script (publisher ID is public, not a secret)
        Setting::setValue('adsense_client_id', $clientId, 'string', 'adsense', 'Google AdSense client ID (ca-pub-...)', true);
        Setting::setValue('adsense_enabled', $request->has('adsense_enabled'), 'boolean', 'adsense', 'Inject the AdSense verification/ads script on all public pages', true);

        return redirect()
            ->route('admin.ads-txt.index')
            ->with('success', 'บันทึกการตั้งค่า Ads.txt และ AdSense เรียบร้อยแล้ว');
    }
}
