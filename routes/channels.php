<?php

use App\Models\SmsCheckerDevice;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Private channel for SMS Checker device
 * Format: sms-checker.device.{device_id}
 */
Broadcast::channel('sms-checker.device.{deviceId}', function ($user, $deviceId) {
    // If authenticated via web, check if user owns this device
    if ($user) {
        return SmsCheckerDevice::where('device_id', $deviceId)
            ->where('user_id', $user->id)
            ->exists();
    }

    return false;
});

/**
 * Private channel for order updates
 * Format: orders.{order_id}
 */
Broadcast::channel('orders.{orderId}', function ($user, $orderId) {
    // User must be authenticated
    return $user !== null;
});

/**
 * Public channel for all SMS Checker devices (no auth required)
 * Used for broadcast messages like server status, maintenance notices
 */
Broadcast::channel('sms-checker.broadcast', function () {
    return true;
});

/**
 * Presence channel for tracking online devices
 * Format: sms-checker.presence
 */
Broadcast::channel('sms-checker.presence', function ($user) {
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    return false;
});
