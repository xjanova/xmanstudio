<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\AppAuth\DeviceAuthorizations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * /device — where a signed-in person confirms the code a desktop app shows (installer contract 1.3).
 *
 * The app polls app-auth/device/poll; once the person here presses confirm, that poll returns the
 * account's licence for the app's machine. Guests are sent to the normal sign-in page (Google, LINE,
 * password) by the `auth` middleware and come back here with the code through intended().
 *
 * Admins may use it like anyone: a confirmation only links an app to the account's own licences —
 * no session, token or admin power is handed to the app.
 */
class AppDeviceController extends Controller
{
    public function __construct(private readonly DeviceAuthorizations $devices) {}

    /**
     * GET /device?code=XXXX-XXXX
     */
    public function show(Request $request): View
    {
        $result = $request->session()->get('device_result');
        $input = $request->query('code');
        $input = is_string($input) ? mb_substr(trim($input), 0, 32) : '';

        $pending = null;
        $state = 'enter';

        if (in_array($result, ['approved', 'denied', 'invalid'], true)) {
            $state = $result;
        } elseif ($input !== '') {
            $found = $this->devices->findByUserCode($input);

            if ($found && $found['status'] === DeviceAuthorizations::STATUS_PENDING) {
                $state = 'confirm';
                $pending = [
                    'user_code' => DeviceAuthorizations::formatUserCode($found['user_code']),
                    'product' => Product::find($found['product_id'])?->name ?? '—',
                    'machine_name' => $found['machine_name'] ?: 'คอมพิวเตอร์ไม่ระบุชื่อ',
                    'app_version' => $found['app_version'],
                    'seconds_left' => DeviceAuthorizations::secondsLeft($found),
                ];
            } else {
                $state = 'invalid';
            }
        }

        return view('app-device.show', [
            'state' => $state,
            'code' => $pending['user_code'] ?? $input,
            'pending' => $pending,
        ]);
    }

    /**
     * POST /device {user_code, action: approve|deny}
     */
    public function confirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_code' => 'required|string|max:32',
            'action' => 'required|in:approve,deny',
        ], [
            'user_code.required' => 'กรุณากรอกโค้ดที่แสดงในโปรแกรม',
            'action.*' => 'คำสั่งไม่ถูกต้อง กรุณาลองใหม่',
        ]);

        $outcome = $this->devices->decide($validated['user_code'], $request->user(), $validated['action'] === 'approve');

        Log::info('app-auth: device code answered', [
            'user_id' => $request->user()->id,
            'action' => $validated['action'],
            'outcome' => $outcome,
        ]);

        return redirect()->route('app-device.show')->with('device_result', $outcome);
    }
}
