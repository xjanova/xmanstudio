<?php

use App\Http\Controllers\Admin\AdPlacementController;
use App\Http\Controllers\Admin\AdsTxtController;
use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BrandingSettingsController;
use App\Http\Controllers\Admin\CustomCodeController;
use App\Http\Controllers\Admin\LicenseController as AdminLicenseController;
use App\Http\Controllers\Admin\LineMessagingController;
use App\Http\Controllers\Admin\LineSettingsController;
use App\Http\Controllers\Admin\MetalXAiController;
use App\Http\Controllers\Admin\MetalXAnalyticsController;
use App\Http\Controllers\Admin\MetalXEngagementController;
use App\Http\Controllers\Admin\MetalXPlaylistController;
use App\Http\Controllers\Admin\MetalXSettingsController;
use App\Http\Controllers\Admin\MetalXTeamController;
use App\Http\Controllers\Admin\MetalXVideoController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductVersionController;
use App\Http\Controllers\Admin\ProjectOrderController;
use App\Http\Controllers\Admin\QuotationCategoryController;
use App\Http\Controllers\Admin\QuotationOptionController;
use App\Http\Controllers\Admin\RentalController as AdminRentalController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MetalXController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\SupportTicketController;
use App\Models\AdsTxtSetting;
use App\Models\SeoSetting;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Initial Setup Routes (before any admin exists)
|--------------------------------------------------------------------------
*/

Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Home (with setup check)
Route::get('/', function () {
    if (SetupController::isSetupRequired()) {
        return redirect()->route('setup.index');
    }

    return app(HomeController::class)->index();
})->name('home');

// Products
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// Downloads (product downloads with license check)
Route::get('/download/{slug}', [DownloadController::class, 'downloadPage'])->name('download.page');
Route::get('/download/{slug}/{version?}', [DownloadController::class, 'download'])->name('download.product')->middleware('auth');
Route::post('/api/download/{slug}/{version?}', [DownloadController::class, 'apiDownload'])->name('download.api');

// AutoTradeX - Direct purchase from app
Route::prefix('autotradex')->name('autotradex.')->group(function () {
    Route::get('/pricing', [\App\Http\Controllers\AutoTradeXController::class, 'pricing'])->name('pricing');
    Route::get('/buy', [\App\Http\Controllers\AutoTradeXController::class, 'buyRedirect'])->name('buy');

    // Require authentication for checkout
    Route::middleware('auth')->group(function () {
        Route::get('/checkout/{plan}', [\App\Http\Controllers\AutoTradeXController::class, 'checkout'])->name('checkout');
        Route::post('/checkout/{plan}', [\App\Http\Controllers\AutoTradeXController::class, 'processCheckout'])->name('process');
        Route::get('/payment/{order}', [\App\Http\Controllers\AutoTradeXController::class, 'payment'])->name('payment');
        Route::post('/payment/{order}/confirm', [\App\Http\Controllers\AutoTradeXController::class, 'confirmPayment'])->name('confirm-payment');
        Route::get('/payment/{order}/success', [\App\Http\Controllers\AutoTradeXController::class, 'paymentSuccess'])->name('payment-success');
    });
});

// Services
Route::get('/services', [ProductController::class, 'services'])->name('services.index');
Route::get('/services/{slug}', [ProductController::class, 'serviceDetail'])->name('services.show');

// Rental packages (public view)
Route::get('/rental', [RentalController::class, 'index'])->name('rental.index');

// Cart (session-based, works without login)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{item}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Support & Quotation
Route::get('/support', [QuotationController::class, 'index'])->name('support.index');
Route::get('/services/{categoryKey}/{optionKey}', [QuotationController::class, 'serviceDetail'])->name('service.detail');
Route::post('/quotation/preview', [QuotationController::class, 'preview'])->name('quotation.preview');
Route::post('/quotation/pdf', [QuotationController::class, 'generatePdf'])->name('quotation.pdf');
Route::post('/quotation/submit', [QuotationController::class, 'submitOrder'])->name('quotation.submit');
Route::get('/quotation/services', [QuotationController::class, 'getServices'])->name('quotation.services');

// About page
Route::view('/about', 'about')->name('about');

// Portfolio page
Route::view('/portfolio', 'portfolio')->name('portfolio');

// Metal-X Project Music Channel
Route::get('/metal-x', [MetalXController::class, 'index'])->name('metal-x.index');

// Legal pages
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');

// Sitemap.xml (SEO)
Route::get('/sitemap.xml', function () {
    $sitemapPath = public_path('sitemap.xml');

    if (! file_exists($sitemapPath)) {
        abort(404);
    }

    return response()->file($sitemapPath, [
        'Content-Type' => 'application/xml',
    ]);
})->name('sitemap');

// Robots.txt (SEO)
Route::get('/robots.txt', function () {
    $setting = SeoSetting::getInstance();

    if (! $setting->robots_txt_enabled || empty($setting->robots_txt_content)) {
        return response('User-agent: *'."\n".'Allow: /', 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    return response($setting->robots_txt_content, 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('robots');

// Ads.txt (Google Ads)
Route::get('/ads.txt', function () {
    $setting = AdsTxtSetting::getInstance();

    if (! $setting->enabled || empty($setting->content)) {
        abort(404);
    }

    return response($setting->content, 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('ads-txt');

// Banner tracking (public routes)
Route::post('/banners/{banner}/track-view', [BannerController::class, 'trackView'])->name('banners.track-view');
Route::post('/banners/{banner}/track-click', [BannerController::class, 'trackClick'])->name('banners.track-click');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware('verified')->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::post('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rental (requires login)
    Route::get('/rental/checkout/{package}', [RentalController::class, 'checkout'])->name('rental.checkout');
    Route::post('/rental/checkout', [RentalController::class, 'processCheckout'])->name('rental.process');
    Route::get('/rental/payment/{uuid}', [RentalController::class, 'payment'])->name('rental.payment');
    Route::post('/rental/payment/{uuid}/upload-slip', [RentalController::class, 'uploadSlip'])->name('rental.upload-slip');
    Route::get('/rental/payment/{uuid}/status', [RentalController::class, 'paymentStatus'])->name('rental.payment.status');
    Route::get('/rental/status', [RentalController::class, 'status'])->name('rental.status');
    Route::post('/rental/validate-promo', [RentalController::class, 'validatePromo'])->name('rental.validate-promo');
    Route::post('/rental/{rental}/cancel', [RentalController::class, 'cancel'])->name('rental.cancel');
    Route::get('/rental/invoices', [RentalController::class, 'invoices'])->name('rental.invoices');

    // Checkout & Orders
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
    Route::get('/orders/{order}/download', [OrderController::class, 'download'])->name('orders.download');

    // Customer Portal
    Route::prefix('my-account')->name('customer.')->group(function () {
        Route::get('/', [CustomerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/licenses', [CustomerPortalController::class, 'licenses'])->name('licenses');
        Route::get('/licenses/{license}', [CustomerPortalController::class, 'licenseShow'])->name('licenses.show');
        Route::get('/subscriptions', [CustomerPortalController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/subscriptions/{rental}', [CustomerPortalController::class, 'subscriptionShow'])->name('subscriptions.show');
        Route::get('/orders', [CustomerPortalController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [CustomerPortalController::class, 'orderShow'])->name('orders.show');
        Route::get('/invoices', [CustomerPortalController::class, 'invoices'])->name('invoices');
        Route::get('/downloads', [CustomerPortalController::class, 'downloads'])->name('downloads');

        // Projects (Order progress tracking)
        Route::get('/projects', [CustomerPortalController::class, 'projects'])->name('projects');
        Route::get('/projects/{project}', [CustomerPortalController::class, 'projectShow'])->name('projects.show');

        // Support Tickets
        Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
        Route::get('/support/create', [SupportTicketController::class, 'create'])->name('support.create');
        Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
        Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');
        Route::post('/support/{ticket}/close', [SupportTicketController::class, 'close'])->name('support.close');
        Route::post('/support/{ticket}/reopen', [SupportTicketController::class, 'reopen'])->name('support.reopen');
    });
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard - Redirect to Analytics
    Route::get('/', function () {
        return redirect()->route('admin.analytics.index');
    })->name('dashboard');

    // Analytics Dashboard
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Rental Management
    Route::get('/rentals', [AdminRentalController::class, 'index'])->name('rentals.index');
    Route::get('/rentals/payments', [AdminRentalController::class, 'payments'])->name('rentals.payments');
    Route::get('/rentals/packages', [AdminRentalController::class, 'packages'])->name('rentals.packages');
    Route::get('/rentals/packages/create', [AdminRentalController::class, 'createPackage'])->name('rentals.packages.create');
    Route::post('/rentals/packages', [AdminRentalController::class, 'storePackage'])->name('rentals.packages.store');
    Route::get('/rentals/packages/{package}/edit', [AdminRentalController::class, 'editPackage'])->name('rentals.packages.edit');
    Route::put('/rentals/packages/{package}', [AdminRentalController::class, 'updatePackage'])->name('rentals.packages.update');
    Route::delete('/rentals/packages/{package}', [AdminRentalController::class, 'destroyPackage'])->name('rentals.packages.destroy');
    Route::post('/rentals/packages/{package}/toggle', [AdminRentalController::class, 'togglePackage'])->name('rentals.packages.toggle');
    Route::get('/rentals/reports', [AdminRentalController::class, 'reports'])->name('rentals.reports');
    Route::get('/rentals/{rental}', [AdminRentalController::class, 'show'])->name('rentals.show');
    Route::post('/rentals/{rental}/extend', [AdminRentalController::class, 'extend'])->name('rentals.extend');
    Route::post('/rentals/{rental}/suspend', [AdminRentalController::class, 'suspend'])->name('rentals.suspend');
    Route::post('/rentals/{rental}/activate', [AdminRentalController::class, 'activate'])->name('rentals.activate');
    Route::post('/payments/{payment}/approve', [AdminRentalController::class, 'approvePayment'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [AdminRentalController::class, 'rejectPayment'])->name('payments.reject');

    // Service Management
    Route::resource('services', AdminServiceController::class);
    Route::post('/services/{service}/toggle', [AdminServiceController::class, 'toggle'])->name('services.toggle');
    Route::post('/services/{service}/toggle-coming-soon', [AdminServiceController::class, 'toggleComingSoon'])->name('services.toggle-coming-soon');

    // Product Categories
    Route::prefix('products/categories')->name('products.categories.')->group(function () {
        Route::get('/', [ProductCategoryController::class, 'index'])->name('index');
        Route::get('/create', [ProductCategoryController::class, 'create'])->name('create');
        Route::post('/', [ProductCategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [ProductCategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [ProductCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [ProductCategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{category}/toggle', [ProductCategoryController::class, 'toggle'])->name('toggle');
    });

    // Product Management
    Route::resource('products', AdminProductController::class);
    Route::post('/products/{product}/toggle', [AdminProductController::class, 'toggle'])->name('products.toggle');
    Route::post('/products/{product}/toggle-coming-soon', [AdminProductController::class, 'toggleComingSoon'])->name('products.toggle-coming-soon');
    Route::get('/products/{product}/preview', [AdminProductController::class, 'preview'])->name('products.preview');

    // Product Versions & GitHub Settings
    Route::prefix('products/{product}/versions')->name('products.versions.')->group(function () {
        Route::get('/', [ProductVersionController::class, 'index'])->name('index');
        Route::post('/github', [ProductVersionController::class, 'saveGithubSettings'])->name('github');
        Route::get('/test', [ProductVersionController::class, 'testConnection'])->name('test');
        Route::post('/sync', [ProductVersionController::class, 'syncRelease'])->name('sync');
        Route::post('/create', [ProductVersionController::class, 'createVersion'])->name('create');
        Route::get('/logs', [ProductVersionController::class, 'downloadLogs'])->name('logs');
        Route::post('/{version}/toggle', [ProductVersionController::class, 'toggleVersion'])->name('toggle');
        Route::delete('/{version}', [ProductVersionController::class, 'destroyVersion'])->name('destroy');
    });

    // License Management
    Route::get('/licenses', [AdminLicenseController::class, 'index'])->name('licenses.index');
    Route::get('/licenses/create', [AdminLicenseController::class, 'create'])->name('licenses.create');
    Route::post('/licenses', [AdminLicenseController::class, 'store'])->name('licenses.store');
    Route::get('/licenses/{license}', [AdminLicenseController::class, 'show'])->name('licenses.show');
    Route::post('/licenses/{license}/revoke', [AdminLicenseController::class, 'revoke'])->name('licenses.revoke');
    Route::post('/licenses/{license}/reactivate', [AdminLicenseController::class, 'reactivate'])->name('licenses.reactivate');
    Route::post('/licenses/{license}/reset-machine', [AdminLicenseController::class, 'resetMachine'])->name('licenses.reset-machine');
    Route::post('/licenses/{license}/extend', [AdminLicenseController::class, 'extend'])->name('licenses.extend');
    Route::delete('/licenses/{license}', [AdminLicenseController::class, 'destroy'])->name('licenses.destroy');

    // Payment Settings
    Route::get('/payment-settings', [PaymentSettingController::class, 'index'])->name('payment-settings.index');
    Route::put('/payment-settings', [PaymentSettingController::class, 'update'])->name('payment-settings.update');
    Route::post('/payment-settings/bank', [PaymentSettingController::class, 'storeBank'])->name('payment-settings.bank.store');
    Route::put('/payment-settings/bank/{bankAccount}', [PaymentSettingController::class, 'updateBank'])->name('payment-settings.bank.update');
    Route::post('/payment-settings/bank/{bankAccount}/toggle', [PaymentSettingController::class, 'toggleBank'])->name('payment-settings.bank.toggle');
    Route::delete('/payment-settings/bank/{bankAccount}', [PaymentSettingController::class, 'destroyBank'])->name('payment-settings.bank.destroy');

    // Branding Settings (Logo & Favicon)
    Route::get('/branding', [BrandingSettingsController::class, 'index'])->name('branding.index');
    Route::post('/branding', [BrandingSettingsController::class, 'update'])->name('branding.update');
    Route::delete('/branding/logo', [BrandingSettingsController::class, 'deleteLogo'])->name('branding.logo.delete');
    Route::delete('/branding/favicon', [BrandingSettingsController::class, 'deleteFavicon'])->name('branding.favicon.delete');

    // AI Settings
    Route::get('/ai-settings', [AiSettingsController::class, 'index'])->name('ai-settings.index');
    Route::put('/ai-settings', [AiSettingsController::class, 'update'])->name('ai-settings.update');
    Route::post('/ai-settings/test', [AiSettingsController::class, 'test'])->name('ai-settings.test');
    Route::get('/ai-settings/ollama-models', [AiSettingsController::class, 'getOllamaModels'])->name('ai-settings.ollama-models');

    // Line Settings
    Route::get('/line-settings', [LineSettingsController::class, 'index'])->name('line-settings.index');
    Route::put('/line-settings', [LineSettingsController::class, 'update'])->name('line-settings.update');
    Route::post('/line-settings/test-messaging', [LineSettingsController::class, 'testMessaging'])->name('line-settings.test-messaging');
    Route::post('/line-settings/test-notify', [LineSettingsController::class, 'testNotify'])->name('line-settings.test-notify');

    // Custom Code Settings (Tracking & Verification)
    Route::get('/custom-code', [CustomCodeController::class, 'index'])->name('custom-code.index');
    Route::put('/custom-code', [CustomCodeController::class, 'update'])->name('custom-code.update');
    Route::post('/custom-code/clear', [CustomCodeController::class, 'clear'])->name('custom-code.clear');

    // Line Messaging
    Route::get('/line-messaging', [LineMessagingController::class, 'index'])->name('line-messaging.index');
    Route::get('/line-messaging/search', [LineMessagingController::class, 'search'])->name('line-messaging.search');
    Route::post('/line-messaging/send', [LineMessagingController::class, 'send'])->name('line-messaging.send');
    Route::get('/line-messaging/users', [LineMessagingController::class, 'users'])->name('line-messaging.users');
    Route::post('/line-messaging/update-uid', [LineMessagingController::class, 'updateUid'])->name('line-messaging.update-uid');

    // Support Tickets Management
    Route::get('/support', [AdminSupportTicketController::class, 'index'])->name('support.index');
    Route::get('/support/{ticket}', [AdminSupportTicketController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [AdminSupportTicketController::class, 'reply'])->name('support.reply');
    Route::post('/support/{ticket}/status', [AdminSupportTicketController::class, 'updateStatus'])->name('support.update-status');
    Route::post('/support/{ticket}/priority', [AdminSupportTicketController::class, 'updatePriority'])->name('support.update-priority');
    Route::post('/support/{ticket}/assign', [AdminSupportTicketController::class, 'assign'])->name('support.assign');
    Route::post('/support/bulk', [AdminSupportTicketController::class, 'bulkAction'])->name('support.bulk');

    // Metal-X Project Management
    Route::prefix('metal-x')->name('metal-x.')->group(function () {
        // Team Members
        Route::get('/team', [MetalXTeamController::class, 'index'])->name('index');
        Route::get('/team/create', [MetalXTeamController::class, 'create'])->name('create');
        Route::post('/team', [MetalXTeamController::class, 'store'])->name('store');
        Route::get('/team/{metalX}/edit', [MetalXTeamController::class, 'edit'])->name('edit');
        Route::put('/team/{metalX}', [MetalXTeamController::class, 'update'])->name('update');
        Route::delete('/team/{metalX}', [MetalXTeamController::class, 'destroy'])->name('destroy');

        // Analytics Dashboard
        Route::get('/', [MetalXAnalyticsController::class, 'index'])->name('analytics');
        Route::post('/analytics/refresh', [MetalXAnalyticsController::class, 'refresh'])->name('analytics.refresh');
        Route::get('/analytics/export', [MetalXAnalyticsController::class, 'export'])->name('analytics.export');

        // Videos
        Route::prefix('videos')->name('videos.')->group(function () {
            Route::get('/', [MetalXVideoController::class, 'index'])->name('index');
            Route::get('/create', [MetalXVideoController::class, 'create'])->name('create');
            Route::post('/', [MetalXVideoController::class, 'store'])->name('store');
            Route::get('/{video}/edit', [MetalXVideoController::class, 'edit'])->name('edit');
            Route::put('/{video}', [MetalXVideoController::class, 'update'])->name('update');
            Route::delete('/{video}', [MetalXVideoController::class, 'destroy'])->name('destroy');
            Route::post('/{video}/toggle', [MetalXVideoController::class, 'toggle'])->name('toggle');
            Route::post('/{video}/toggle-featured', [MetalXVideoController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::post('/{video}/sync', [MetalXVideoController::class, 'sync'])->name('sync');
            Route::post('/sync-all', [MetalXVideoController::class, 'syncAll'])->name('sync-all');
            Route::post('/update-stats', [MetalXVideoController::class, 'updateStats'])->name('update-stats');
            Route::post('/import', [MetalXVideoController::class, 'import'])->name('import');
        });

        // Playlists
        Route::prefix('playlists')->name('playlists.')->group(function () {
            Route::get('/', [MetalXPlaylistController::class, 'index'])->name('index');
            Route::get('/create', [MetalXPlaylistController::class, 'create'])->name('create');
            Route::post('/', [MetalXPlaylistController::class, 'store'])->name('store');
            Route::get('/{playlist}', [MetalXPlaylistController::class, 'show'])->name('show');
            Route::get('/{playlist}/edit', [MetalXPlaylistController::class, 'edit'])->name('edit');
            Route::put('/{playlist}', [MetalXPlaylistController::class, 'update'])->name('update');
            Route::delete('/{playlist}', [MetalXPlaylistController::class, 'destroy'])->name('destroy');
            Route::post('/{playlist}/toggle', [MetalXPlaylistController::class, 'toggle'])->name('toggle');
            Route::post('/{playlist}/sync', [MetalXPlaylistController::class, 'sync'])->name('sync');
            Route::post('/{playlist}/reorder', [MetalXPlaylistController::class, 'reorder'])->name('reorder');
            Route::post('/{playlist}/add-video', [MetalXPlaylistController::class, 'addVideo'])->name('add-video');
            Route::post('/{playlist}/remove-video', [MetalXPlaylistController::class, 'removeVideo'])->name('remove-video');
        });

        // AI Tools (Rate Limited: 10 requests/minute)
        Route::prefix('ai')->name('ai.')->middleware('throttle:ai-operations')->group(function () {
            Route::get('/', [MetalXAiController::class, 'index'])->name('index')->withoutMiddleware('throttle:ai-operations');
            Route::post('/{video}/generate', [MetalXAiController::class, 'generateSingle'])->name('generate-single');
            Route::post('/generate-batch', [MetalXAiController::class, 'generateBatch'])->name('generate-batch');
            Route::post('/generate-all', [MetalXAiController::class, 'generateAll'])->name('generate-all');
            Route::post('/{video}/approve', [MetalXAiController::class, 'approve'])->name('approve')->withoutMiddleware('throttle:ai-operations');
            Route::post('/{video}/reject', [MetalXAiController::class, 'reject'])->name('reject')->withoutMiddleware('throttle:ai-operations');
            Route::post('/approve-batch', [MetalXAiController::class, 'approveBatch'])->name('approve-batch')->withoutMiddleware('throttle:ai-operations');
            Route::get('/{video}/preview', [MetalXAiController::class, 'preview'])->name('preview')->withoutMiddleware('throttle:ai-operations');
            Route::get('/status', [MetalXAiController::class, 'status'])->name('status')->withoutMiddleware('throttle:ai-operations');
        });

        // Engagement & Comments (Rate Limited)
        Route::prefix('engagement')->name('engagement.')->group(function () {
            // View routes (no rate limit)
            Route::get('/', [MetalXEngagementController::class, 'index'])->name('index');
            Route::get('/{video}/stats', [MetalXEngagementController::class, 'videoStats'])->name('video-stats');
            Route::get('/blacklist', [MetalXEngagementController::class, 'blacklist'])->name('blacklist');

            // YouTube API operations (20 requests/minute)
            Route::middleware('throttle:youtube-operations')->group(function () {
                Route::post('/{video}/sync-comments', [MetalXEngagementController::class, 'syncComments'])->name('sync-comments');
                Route::post('/sync-all-comments', [MetalXEngagementController::class, 'syncAllComments'])->name('sync-all-comments');
                Route::post('/comment/{comment}/post-reply', [MetalXEngagementController::class, 'postReply'])->name('post-reply');
                Route::post('/comment/{comment}/like', [MetalXEngagementController::class, 'likeComment'])->name('like-comment');
                Route::delete('/comment/{comment}/delete', [MetalXEngagementController::class, 'deleteComment'])->name('delete-comment');
            });

            // AI operations (10 requests/minute)
            Route::middleware('throttle:ai-operations')->group(function () {
                Route::post('/comment/{comment}/process', [MetalXEngagementController::class, 'processComment'])->name('process-comment');
                Route::post('/comment/{comment}/generate-reply', [MetalXEngagementController::class, 'generateReply'])->name('generate-reply');
                Route::post('/{video}/improve-content', [MetalXEngagementController::class, 'improveContent'])->name('improve-content');
                Route::post('/comment/{comment}/detect-violation', [MetalXEngagementController::class, 'detectViolation'])->name('detect-violation');
            });

            // Moderation operations (30 requests/minute)
            Route::middleware('throttle:comment-moderation')->group(function () {
                Route::post('/comment/{comment}/mark-spam', [MetalXEngagementController::class, 'markSpam'])->name('mark-spam');
                Route::post('/comment/{comment}/toggle-attention', [MetalXEngagementController::class, 'toggleAttention'])->name('toggle-attention');
                Route::post('/{video}/apply-improvements', [MetalXEngagementController::class, 'applyContentImprovements'])->name('apply-improvements');
                Route::post('/batch-process', [MetalXEngagementController::class, 'batchProcess'])->name('batch-process');
                Route::post('/comment/{comment}/block-channel', [MetalXEngagementController::class, 'blockChannel'])->name('block-channel');
                Route::post('/comment/{comment}/auto-moderate', [MetalXEngagementController::class, 'autoModerate'])->name('auto-moderate');
                Route::post('/blacklist/{id}/unblock', [MetalXEngagementController::class, 'unblockChannel'])->name('unblock-channel');
            });
        });

        // Settings
        Route::get('/settings', [MetalXSettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [MetalXSettingsController::class, 'update'])->name('settings.update');
    });

    // Ads.txt Management
    Route::get('/ads-txt', [AdsTxtController::class, 'index'])->name('ads-txt.index');
    Route::put('/ads-txt', [AdsTxtController::class, 'update'])->name('ads-txt.update');

    // SEO Management
    Route::get('/seo', [SeoController::class, 'index'])->name('seo.index');
    Route::put('/seo', [SeoController::class, 'update'])->name('seo.update');
    Route::get('/seo/generate-sitemap', [SeoController::class, 'generateSitemap'])->name('seo.generate-sitemap');

    // Google Ads Placements Management
    Route::get('/ads', [AdPlacementController::class, 'index'])->name('ads.index');
    Route::get('/ads/create', [AdPlacementController::class, 'create'])->name('ads.create');
    Route::post('/ads', [AdPlacementController::class, 'store'])->name('ads.store');
    Route::get('/ads/{ad}/edit', [AdPlacementController::class, 'edit'])->name('ads.edit');
    Route::put('/ads/{ad}', [AdPlacementController::class, 'update'])->name('ads.update');
    Route::patch('/ads/{ad}/toggle', [AdPlacementController::class, 'toggle'])->name('ads.toggle');
    Route::delete('/ads/{ad}', [AdPlacementController::class, 'destroy'])->name('ads.destroy');

    // Banner Management
    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
    Route::get('/banners/create', [BannerController::class, 'create'])->name('banners.create');
    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
    Route::get('/banners/{banner}/edit', [BannerController::class, 'edit'])->name('banners.edit');
    Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
    Route::patch('/banners/{banner}/toggle', [BannerController::class, 'toggle'])->name('banners.toggle');
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');

    // Quotation Management
    Route::prefix('quotations')->name('quotations.')->group(function () {
        // Categories
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [QuotationCategoryController::class, 'index'])->name('index');
            Route::get('/create', [QuotationCategoryController::class, 'create'])->name('create');
            Route::post('/', [QuotationCategoryController::class, 'store'])->name('store');
            Route::get('/{category}', [QuotationCategoryController::class, 'show'])->name('show');
            Route::get('/{category}/edit', [QuotationCategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [QuotationCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [QuotationCategoryController::class, 'destroy'])->name('destroy');
        });

        // Options
        Route::prefix('options')->name('options.')->group(function () {
            Route::get('/', [QuotationOptionController::class, 'index'])->name('index');
            Route::get('/create', [QuotationOptionController::class, 'create'])->name('create');
            Route::post('/', [QuotationOptionController::class, 'store'])->name('store');
            Route::get('/{option}', [QuotationOptionController::class, 'show'])->name('show');
            Route::get('/{option}/edit', [QuotationOptionController::class, 'edit'])->name('edit');
            Route::put('/{option}', [QuotationOptionController::class, 'update'])->name('update');
            Route::delete('/{option}', [QuotationOptionController::class, 'destroy'])->name('destroy');
        });
    });

    // Project Order Management
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectOrderController::class, 'index'])->name('index');
        Route::get('/create', [ProjectOrderController::class, 'create'])->name('create');
        Route::post('/', [ProjectOrderController::class, 'store'])->name('store');
        Route::get('/{project}', [ProjectOrderController::class, 'show'])->name('show');
        Route::get('/{project}/edit', [ProjectOrderController::class, 'edit'])->name('edit');
        Route::put('/{project}', [ProjectOrderController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectOrderController::class, 'destroy'])->name('destroy');

        // Create from quotation
        Route::post('/from-quotation/{quotation}', [ProjectOrderController::class, 'createFromQuotation'])->name('from-quotation');

        // Progress Updates
        Route::post('/{project}/progress', [ProjectOrderController::class, 'addProgress'])->name('progress.store');

        // Features
        Route::post('/{project}/features', [ProjectOrderController::class, 'addFeature'])->name('features.store');
        Route::put('/{project}/features/{feature}', [ProjectOrderController::class, 'updateFeature'])->name('features.update');
        Route::delete('/{project}/features/{feature}', [ProjectOrderController::class, 'deleteFeature'])->name('features.destroy');

        // Members
        Route::post('/{project}/members', [ProjectOrderController::class, 'addMember'])->name('members.store');
        Route::delete('/{project}/members/{member}', [ProjectOrderController::class, 'deleteMember'])->name('members.destroy');

        // Timeline
        Route::post('/{project}/timeline', [ProjectOrderController::class, 'addTimeline'])->name('timeline.store');
        Route::post('/{project}/timeline/{timeline}/toggle', [ProjectOrderController::class, 'toggleTimeline'])->name('timeline.toggle');
    });
});
