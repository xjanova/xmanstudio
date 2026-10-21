<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AntiXPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Admin → Anti X Mobile push: the Firebase service account that sends the Anti X phone alerts (project anti-x-mobile).
 * The key stays in storage (0600) and is never shown again — only its account e-mail.
 */
class AntiXPushSettingsController extends Controller
{
    public function edit(AntiXPushService $push): View
    {
        return view('admin.anti-x-push', [
            'projectId' => $push->projectId(),
            'serviceAccount' => $push->serviceAccount(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'project_id' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'credentials' => 'nullable|file|max:512',
        ]);
        Setting::setValue('antix_fcm_project_id', $request->string('project_id')->toString(), 'string', 'antix_push', 'Anti X push: Firebase project');

        if ($request->hasFile('credentials')) {
            $content = (string) file_get_contents($request->file('credentials')->getRealPath());
            $json = json_decode($content, true);
            if (! is_array($json) || ($json['type'] ?? '') !== 'service_account' || empty($json['private_key']) || empty($json['client_email'])) {
                return back()->with('error', 'ไฟล์นี้ไม่ใช่ Service Account JSON ของ Firebase');
            }
            if (($json['project_id'] ?? '') !== $request->string('project_id')->toString()) {
                return back()->with('error', 'ไฟล์นี้เป็นของโปรเจกต์ ' . ($json['project_id'] ?? '?') . ' ไม่ใช่ ' . $request->string('project_id'));
            }
            $dir = storage_path('app/firebase');
            if (! is_dir($dir)) {
                mkdir($dir, 0700, true);
            }
            $path = $dir . '/anti-x-mobile.json';
            file_put_contents($path, $content);
            @chmod($path, 0600);
            Setting::setValue('antix_fcm_credentials_path', $path, 'string', 'antix_push', 'Anti X push: service account file');
            Log::info('Anti X push: service account uploaded', ['admin_id' => auth()->id(), 'account' => $json['client_email']]);
        }

        return redirect()->route('admin.anti-x-push.edit')->with('success', 'บันทึกแล้ว');
    }
}
