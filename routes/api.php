<?php

use App\Http\Controllers\Api\AutoTradeXLicenseController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\LicenseApiController;
use App\Http\Controllers\Api\ProductLicenseController;
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

    // Reset device for Lifetime license holders (rate limited - 5 requests per day)
    Route::middleware(['throttle:5,1440'])->group(function () {
        Route::post('/reset-device', [AutoTradeXLicenseController::class, 'resetDevice']);
    });

    // Admin routes (protected by X-Admin-Token header)
    Route::prefix('admin')->group(function () {
        Route::post('/reset-device', [AutoTradeXLicenseController::class, 'adminResetDevice']);
        Route::get('/license/{licenseKey}', [AutoTradeXLicenseController::class, 'adminGetLicense']);
    });
});

// ==================== Generic Product License API Routes ====================
// These routes support all products that require license
// Use /{productSlug}/ to specify the product
// Rate limited to 60 requests per minute per IP

Route::prefix('v1/product/{productSlug}')->middleware(['throttle:60,1'])->group(function () {
    // Register device when app starts
    Route::post('/register-device', [ProductLicenseController::class, 'registerDevice']);

    // Activate license on a machine
    Route::post('/activate', [ProductLicenseController::class, 'activate']);

    // Validate existing license
    Route::post('/validate', [ProductLicenseController::class, 'validate']);

    // Deactivate license
    Route::post('/deactivate', [ProductLicenseController::class, 'deactivate']);

    // Check license status
    Route::get('/status/{licenseKey}', [ProductLicenseController::class, 'status']);

    // Get pricing info (public)
    Route::get('/pricing', [ProductLicenseController::class, 'pricing']);

    // Demo endpoints (rate limited more strictly)
    Route::middleware(['throttle:10,1'])->group(function () {
        Route::post('/demo', [ProductLicenseController::class, 'startDemo']);
        Route::post('/demo/check', [ProductLicenseController::class, 'checkDemo']);
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

// ==================== Coupon API Routes ====================
// These routes are used during checkout to validate and apply coupons
// Requires authentication (web session or Sanctum token)

Route::prefix('v1/coupons')->middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    // Validate coupon code and get discount amount
    Route::post('/validate', [CouponController::class, 'validate']);

    // Apply coupon to current session
    Route::post('/apply', [CouponController::class, 'apply']);

    // Remove coupon from session
    Route::delete('/remove', [CouponController::class, 'remove']);
});
