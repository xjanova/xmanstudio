<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdsTxtSetting;
use Illuminate\Http\Request;

class AdsTxtController extends Controller
{
    /**
     * Display the ads.txt settings form.
     */
    public function index()
    {
        $setting = AdsTxtSetting::getInstance();

        return view('admin.ads-txt.index', compact('setting'));
    }

    /**
     * Update the ads.txt settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
            'enabled' => 'nullable|boolean',
        ]);

        $setting = AdsTxtSetting::getInstance();

        $setting->update([
            'content' => $request->input('content', ''),
            'enabled' => $request->has('enabled'),
        ]);

        return redirect()
            ->route('admin.ads-txt.index')
            ->with('success', 'Ads.txt settings updated successfully!');
    }
}
