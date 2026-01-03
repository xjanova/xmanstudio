<?php

use App\Http\Controllers\Api\LicenseApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Health check for API
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'api_version' => 'v1',
        'timestamp' => now()->toISOString(),
    ]);
});

// ==================== License API Routes ====================
// These routes are used by desktop applications for license validation
// Rate limited to 60 requests per minute per IP

Route::prefix('v1/license')->middleware(['throttle:60,1'])->group(function () {
    // Activate license on a machine
    Route::post('/activate', [LicenseApiController::class, 'activate']);

    // Validate existing license
    Route::post('/validate', [LicenseApiController::class, 'validate']);

    // Deactivate license
    Route::post('/deactivate', [LicenseApiController::class, 'deactivate']);

    // Check license status
    Route::get('/status/{licenseKey}', [LicenseApiController::class, 'status']);

    // Demo endpoints (rate limited more strictly)
    Route::middleware(['throttle:10,1'])->group(function () {
        Route::post('/demo', [LicenseApiController::class, 'startDemo']);
        Route::post('/demo/check', [LicenseApiController::class, 'checkDemo']);
    });
});
