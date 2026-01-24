<?php

use App\Http\Controllers\Api\AutoTradeXLicenseController;
use App\Http\Controllers\Api\LicenseApiController;
use App\Http\Controllers\Api\VersionController;
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

// ==================== AutoTradeX License API Routes ====================
// These routes are specifically for AutoTradeX desktop application
// Rate limited to 60 requests per minute per IP

Route::prefix('v1/autotradex')->middleware(['throttle:60,1'])->group(function () {
    // Register device automatically when app starts
    Route::post('/register-device', [AutoTradeXLicenseController::class, 'registerDevice']);

    // Activate license on a machine
    Route::post('/activate', [AutoTradeXLicenseController::class, 'activate']);

    // Validate existing license
    Route::post('/validate', [AutoTradeXLicenseController::class, 'validate']);

    // Deactivate license
    Route::post('/deactivate', [AutoTradeXLicenseController::class, 'deactivate']);

    // Check license status
    Route::get('/status/{licenseKey}', [AutoTradeXLicenseController::class, 'status']);

    // Get pricing info (public)
    Route::get('/pricing', [AutoTradeXLicenseController::class, 'pricing']);

    // Get purchase URL for app to open browser
    Route::get('/purchase-url', [AutoTradeXLicenseController::class, 'purchaseUrl']);

    // Verify server authenticity (anti-fake server)
    Route::post('/verify-server', [AutoTradeXLicenseController::class, 'verifyServer']);

    // Demo endpoints (rate limited more strictly)
    Route::middleware(['throttle:10,1'])->group(function () {
        Route::post('/demo', [AutoTradeXLicenseController::class, 'startDemo']);
        Route::post('/demo/check', [AutoTradeXLicenseController::class, 'checkDemo']);
    });
});

// ==================== Version & Download API Routes ====================
// These routes are used by desktop applications for version checking and updates
// Rate limited to 60 requests per minute per IP

Route::prefix('v1/products')->middleware(['throttle:60,1'])->group(function () {
    // Get latest version for a product (public)
    Route::get('/{slug}/version', [VersionController::class, 'latest']);

    // Get all versions for a product (public)
    Route::get('/{slug}/versions', [VersionController::class, 'all']);

    // Check if update is available (public, but enhanced with license)
    Route::post('/{slug}/check-update', [VersionController::class, 'check']);

    // Validate license key
    Route::post('/validate-license', [VersionController::class, 'validateLicense']);
});
