<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MetalXSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'channel_name' => Setting::getValue('metalx_channel_name', 'Metal-X Project'),
            'channel_description' => Setting::getValue('metalx_channel_description'),
            'channel_url' => Setting::getValue('metalx_channel_url', 'https://www.youtube.com/@Metal-XProject'),
            'channel_logo' => Setting::getValue('metalx_channel_logo'),
            'channel_banner' => Setting::getValue('metalx_channel_banner'),
            'youtube_api_key' => Setting::getValue('youtube_api_key'),
        ];

        return view('admin.metal-x.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'channel_name' => 'required|string|max:255',
            'channel_description' => 'nullable|string',
            'channel_url' => 'required|url',
            'channel_logo' => 'nullable|image|max:2048',
            'channel_banner' => 'nullable|image|max:2048',
            'youtube_api_key' => 'nullable|string',
        ]);

        // Handle logo upload
        if ($request->hasFile('channel_logo')) {
            $oldLogo = Setting::getValue('metalx_channel_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $logoPath = $request->file('channel_logo')->store('metal-x/channel', 'public');
            Setting::setValue('metalx_channel_logo', $logoPath, 'string', 'metalx');
        }

        // Handle banner upload
        if ($request->hasFile('channel_banner')) {
            $oldBanner = Setting::getValue('metalx_channel_banner');
            if ($oldBanner) {
                Storage::disk('public')->delete($oldBanner);
            }
            $bannerPath = $request->file('channel_banner')->store('metal-x/channel', 'public');
            Setting::setValue('metalx_channel_banner', $bannerPath, 'string', 'metalx');
        }

        // Update text settings
        Setting::setValue('metalx_channel_name', $validated['channel_name'], 'string', 'metalx');
        Setting::setValue('metalx_channel_description', $validated['channel_description'] ?? '', 'string', 'metalx');
        Setting::setValue('metalx_channel_url', $validated['channel_url'], 'string', 'metalx');
        Setting::setValue('youtube_api_key', $validated['youtube_api_key'] ?? '', 'string', 'metalx');

        return redirect()->route('admin.metal-x.settings')
            ->with('success', 'Settings updated successfully!');
    }
}
