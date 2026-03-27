<?php

use App\Http\Controllers\Api\AiprayApiController;
use App\Http\Controllers\Api\AutoTradeXLicenseController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\LicenseApiController;
use App\Http\Controllers\Api\GlobalTorrentController;
use App\Http\Controllers\Api\LocalVpnFileController;
use App\Http\Controllers\Api\LocalVpnRelayController;
use App\Http\Controllers\Api\ProductLicenseController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BugReportController;
use App\Http\Controllers\Api\V1\DataProfileController;
use App\Http\Controllers\Api\V1\PuzzleDebugController;
use App\Http\Controllers\Api\V1\SmsPaymentController;
use App\Http\Controllers\Api\V1\WorkflowController;
use App\Http\Controllers\Api\VersionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// ==================== Metal-X Freepik Image Upload ====================
// Receives images from Freepik browser automation and saves to Laravel storage
Route::post('/metal-x/upload-image', function (Request $request) {
    // Auth: require admin token
    $token = $request->header('X-Admin-Token');
    $expectedToken = config('metalx.admin_token', config('app.metal_x_admin_token'));
    if (! $expectedToken || ! hash_equals($expectedToken, (string) $token)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $request->validate([
        'image' => 'required|string',
        'filename' => 'required|string|regex:/^[a-zA-Z0-9_\-\.]+$/',
        'project_dir' => 'required|string|regex:/^[a-zA-Z0-9_\-]+$/',
    ]);

    $imageData = base64_decode($request->input('image'));
    if (! $imageData) {
        return response()->json(['error' => 'Invalid base64 image'], 400);
    }

    // Sanitize path components to prevent traversal
    $projectDir = basename($request->input('project_dir'));
    $filename = basename($request->input('filename'));

    $dir = 'metal-x/projects/' . $projectDir;
    $path = $dir . '/' . $filename;

    Storage::disk('local')->put($path, $imageData);

    return response()->json([
        'success' => true,
        'path' => $path,
        'size' => strlen($imageData),
    ]);
})->middleware('throttle:60,1');

// Metal-X Import from URL — server downloads file directly from external URL
// Protected by X-Admin-Token header (must match METAL_X_ADMIN_TOKEN env var)
Route::post('/metal-x/import-url', function (Request $request) {
    $token = $request->header('X-Admin-Token');
    $expectedToken = config('metalx.admin_token');
    if (! $expectedToken || ! hash_equals($expectedToken, (string) $token)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $request->validate([
        'url' => 'required|url',
        'type' => 'required|string|in:music,image,video,auto',
        'title' => 'nullable|string|max:255',
        'tags' => 'nullable|string|max:500',
        'style' => 'nullable|string|max:50',
        'source' => 'required|string|in:freepik,suno,custom,ai_generated',
    ]);

    $exitCode = Artisan::call('metalx:import-url', [
        'url' => $request->input('url'),
        '--type' => $request->input('type', 'auto'),
        '--title' => $request->input('title'),
        '--tags' => $request->input('tags'),
        '--style' => $request->input('style', 'metal'),
        '--source' => $request->input('source'),
    ]);

    $output = Artisan::output();

    return response()->json([
        'success' => $exitCode === 0,
        'output' => trim($output),
    ]);
})->middleware('throttle:30,1');

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

// ==================== Authentication API Routes ====================
// Token-based auth for mobile apps via Laravel Sanctum
// Rate limited to 10 requests per minute per IP

Route::prefix('v1/auth')->middleware(['throttle:10,1'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/device', [AuthController::class, 'deviceAuth']);  // License-based device auth (no login required)

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/web-login-token', [AuthController::class, 'webLoginToken']);
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

    // Check if machine already has an active license (HWID auto-check)
    Route::post('/check-machine', [ProductLicenseController::class, 'checkMachine']);

    // Deactivate license
    Route::post('/deactivate', [ProductLicenseController::class, 'deactivate']);

    // Check license status
    Route::get('/status/{licenseKey}', [ProductLicenseController::class, 'status']);

    // Get pricing info (public)
    Route::get('/pricing', [ProductLicenseController::class, 'pricing']);

    // Diagnostic reports from app (rate limited)
    Route::middleware(['throttle:10,1'])->group(function () {
        Route::post('/diagnostics', [ProductLicenseController::class, 'storeDiagnostics']);
    });

    // Puzzle debug images for AI learning (rate limited)
    Route::middleware(['throttle:20,1'])->group(function () {
        Route::post('/debug-images', [PuzzleDebugController::class, 'store']);
        Route::post('/debug-images/feedback', [PuzzleDebugController::class, 'feedback']);
        Route::post('/debug-images/infer', [PuzzleDebugController::class, 'infer']);
        Route::get('/debug-images', [PuzzleDebugController::class, 'index']);
        Route::get('/debug-images/stats', [PuzzleDebugController::class, 'stats']);
        Route::get('/debug-images/correction', [PuzzleDebugController::class, 'correction']);
        Route::get('/debug-images/export', [PuzzleDebugController::class, 'export']);
        Route::put('/debug-images/{id}/label', [PuzzleDebugController::class, 'updateLabel']);
    });

    // Demo endpoints (rate limited more strictly)
    Route::middleware(['throttle:10,1'])->group(function () {
        Route::post('/demo', [ProductLicenseController::class, 'startDemo']);
        Route::post('/demo/check', [ProductLicenseController::class, 'checkDemo']);
    });

    // ===== Workflow & Data Profile Cloud Sync (requires auth) =====
    Route::middleware('auth:sanctum')->group(function () {
        // Workflows CRUD
        Route::get('/workflows', [WorkflowController::class, 'index']);
        Route::post('/workflows', [WorkflowController::class, 'store']);
        Route::get('/workflows/{id}', [WorkflowController::class, 'show']);
        Route::put('/workflows/{id}', [WorkflowController::class, 'update']);
        Route::delete('/workflows/{id}', [WorkflowController::class, 'destroy']);
        Route::post('/workflows/{id}/share', [WorkflowController::class, 'share']);
        Route::post('/workflows/import', [WorkflowController::class, 'bulkImport']);

        // Data Profiles CRUD
        Route::get('/data-profiles', [DataProfileController::class, 'index']);
        Route::post('/data-profiles', [DataProfileController::class, 'store']);
        Route::put('/data-profiles/{id}', [DataProfileController::class, 'update']);
        Route::delete('/data-profiles/{id}', [DataProfileController::class, 'destroy']);
        Route::post('/data-profiles/import', [DataProfileController::class, 'bulkImport']);
    });

    // Public shared workflow access (no auth)
    Route::get('/workflows/shared/{token}', [WorkflowController::class, 'getShared']);

    // In-app update check (GET for mobile apps)
    Route::get('/update/check', [VersionController::class, 'checkUpdate']);
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

// ==================== Stripe API Routes ====================
// These routes handle Stripe PaymentIntent creation for checkout flows
// Requires authentication via Sanctum

Route::prefix('v1/stripe')->middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('/payment-intent/order/{order}', [StripeController::class, 'createOrderPaymentIntent'])
        ->name('api.stripe.intent.order');
    Route::post('/payment-intent/topup/{topup}', [StripeController::class, 'createTopupPaymentIntent'])
        ->name('api.stripe.intent.topup');
    Route::post('/payment-intent/rental/{payment}', [StripeController::class, 'createRentalPaymentIntent'])
        ->name('api.stripe.intent.rental');
});

// ==================== SMS Payment API Routes ====================
// These routes handle SMS-based bank transfer verification
// Used by SmsChecker Android app for automatic payment confirmation

Route::prefix('v1/sms-payment')->group(function () {
    // Critical device endpoints - higher rate limit to ensure always works
    // These must succeed even when device is polling aggressively
    Route::middleware(['smschecker.device', 'throttle:60,1'])->group(function () {
        // Register/update device information (includes FCM token)
        Route::post('/register-device', [SmsPaymentController::class, 'registerDevice']);

        // FCM Token registration
        Route::post('/register-fcm-token', [SmsPaymentController::class, 'registerFcmToken']);

        // Receive SMS payment notification from Android device
        Route::post('/notify', [SmsPaymentController::class, 'notify']);

        // Receive encrypted action (approve/reject) from Android device
        Route::post('/notify-action', [SmsPaymentController::class, 'notifyAction']);

        // Debug report from Android app (temporary)
        Route::post('/debug-report', [SmsPaymentController::class, 'debugReport']);

        // Debug: ตรวจสอบปัญหา topup approve
        Route::get('/debug-topup', [SmsPaymentController::class, 'debugTopup']);
        Route::post('/debug-topup-approve', [SmsPaymentController::class, 'debugTopupApprove']);
    });

    // Standard device endpoints - normal rate limit
    Route::middleware(['smschecker.device', 'throttle:120,1'])->group(function () {
        // Check device status and pending count
        Route::get('/status', [SmsPaymentController::class, 'status']);

        // Order approval endpoints (for Android app)
        Route::get('/orders', [SmsPaymentController::class, 'getOrders']);
        Route::get('/orders/match', [SmsPaymentController::class, 'matchOrderByAmount']); // Match-only mode: find order by SMS amount
        Route::get('/orders/sync', [SmsPaymentController::class, 'syncOrders']);
        Route::post('/orders/bulk-approve', [SmsPaymentController::class, 'bulkApproveOrders']);
        Route::post('/orders/{id}/approve', [SmsPaymentController::class, 'approveOrder']);
        Route::post('/orders/{id}/reject', [SmsPaymentController::class, 'rejectOrder']);

        // Device settings
        Route::get('/device-settings', [SmsPaymentController::class, 'getDeviceSettings']);
        Route::put('/device-settings', [SmsPaymentController::class, 'updateDeviceSettings']);

        // Dashboard statistics
        Route::get('/dashboard-stats', [SmsPaymentController::class, 'getDashboardStats']);

        // WebSocket/Pusher authentication for private channels
        Route::post('/pusher/auth', [SmsPaymentController::class, 'pusherAuth']);

        // Server sync - get changes since last sync
        Route::get('/sync', [SmsPaymentController::class, 'sync']);

        // Version check for sync
        Route::get('/sync-version', [SmsPaymentController::class, 'getSyncVersion']);
    });

    // Web-authenticated endpoints (for checkout flow)
    Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
        // Generate unique payment amount for bank transfer
        Route::post('/generate-amount', [SmsPaymentController::class, 'generateAmount']);

        // Get notification history (admin)
        Route::get('/notifications', [SmsPaymentController::class, 'notifications']);
    });
});

// ==================== Bug Report API Routes ====================
// These routes are used by mobile apps to submit bug reports and misclassification reports
// Rate limited to 30 requests per minute per IP

Route::prefix('v1/bug-reports')->middleware(['throttle:30,1'])->group(function () {
    // Public endpoints (no authentication required)

    // Submit a single bug report
    Route::post('/', [BugReportController::class, 'store']);

    // Submit multiple bug reports in batch
    Route::post('/batch', [BugReportController::class, 'storeBatch']);

    // List bug reports with filters
    Route::get('/', [BugReportController::class, 'index']);

    // Get statistics (must be before /{id} to avoid matching "stats" as an ID)
    Route::get('/stats', [BugReportController::class, 'stats']);

    // Get bug report by ID
    Route::get('/{id}', [BugReportController::class, 'show']);

    // Admin-only endpoints (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        // Post unposted reports to GitHub
        Route::post('/post-to-github', [BugReportController::class, 'postToGitHub']);
    });
});

// ==================== Aipray Flutter App API ====================
Route::prefix('aipray')->middleware(['throttle:60,1'])->group(function () {
    Route::post('/sessions', [AiprayApiController::class, 'storeSession']);
    Route::post('/audio/upload', [AiprayApiController::class, 'uploadAudio'])->middleware('throttle:20,1');
    Route::post('/chants/sync', [AiprayApiController::class, 'syncChants']);
    Route::get('/models/latest', [AiprayApiController::class, 'latestModel']);
    Route::get('/chants/community', [AiprayApiController::class, 'communityChants']);
    Route::get('/stats', [AiprayApiController::class, 'stats']);
    // Signed URL model download
    Route::get('/models/{model}/download', [AiprayApiController::class, 'downloadModel'])
        ->name('aipray.model.download')
        ->middleware('signed');
    // ML service internal callback
    Route::post('/ml/training-callback', [AiprayApiController::class, 'mlCallback']);
});

// ==================== LocalVPN Relay API ====================
Route::prefix('v1/localvpn')->middleware(['throttle:120,1'])->group(function () {
    Route::post('/networks', [LocalVpnRelayController::class, 'createNetwork']);
    Route::get('/networks', [LocalVpnRelayController::class, 'listNetworks']);
    Route::post('/networks/join', [LocalVpnRelayController::class, 'joinNetwork']);
    Route::post('/networks/leave', [LocalVpnRelayController::class, 'leaveNetwork']);
    Route::post('/heartbeat', [LocalVpnRelayController::class, 'heartbeat']);
    Route::get('/networks/{slug}/members', [LocalVpnRelayController::class, 'getMembers']);
    Route::post('/relay', [LocalVpnRelayController::class, 'relayData']);
    Route::delete('/networks/{slug}', [LocalVpnRelayController::class, 'deleteNetwork']);

    // P2P signaling
    Route::get('/stun', [LocalVpnRelayController::class, 'stun']);
    Route::post('/signal', [LocalVpnRelayController::class, 'signal']);
    Route::post('/signal/poll', [LocalVpnRelayController::class, 'pollSignals']);

    // File sharing (network-wide registry)
    Route::post('/files/share', [LocalVpnFileController::class, 'share']);
    Route::get('/files/{slug}', [LocalVpnFileController::class, 'index']);
    Route::delete('/files/{fileId}', [LocalVpnFileController::class, 'destroy']);
    Route::post('/files/seed', [LocalVpnFileController::class, 'registerSeeder']);
    Route::get('/files/{fileId}/seeders', [LocalVpnFileController::class, 'seeders']);

    // Global Torrent
    Route::get('/torrent/categories', [GlobalTorrentController::class, 'categories']);
    Route::get('/torrent/files/{categorySlug}', [GlobalTorrentController::class, 'listFiles']);
    Route::get('/torrent/file/{fileId}', [GlobalTorrentController::class, 'fileDetail']);
    Route::post('/torrent/upload', [GlobalTorrentController::class, 'uploadFile']);
    Route::post('/torrent/seed', [GlobalTorrentController::class, 'registerSeeder']);
    Route::post('/torrent/heartbeat', [GlobalTorrentController::class, 'heartbeat']);
    Route::get('/torrent/file/{fileId}/seeders', [GlobalTorrentController::class, 'getSeeders']);
    Route::get('/torrent/leaderboard', [GlobalTorrentController::class, 'leaderboard']);
    Route::match(['get', 'post'], '/torrent/profile', [GlobalTorrentController::class, 'userProfile']);
    Route::get('/torrent/trophies', [GlobalTorrentController::class, 'trophies']);
    Route::get('/torrent/user-trophies', [GlobalTorrentController::class, 'userTrophies']);
    Route::post('/torrent/kyc/submit', [GlobalTorrentController::class, 'submitKyc']);
    Route::get('/torrent/kyc/status', [GlobalTorrentController::class, 'kycStatus']);
});
