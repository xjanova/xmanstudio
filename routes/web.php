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
use App\Http\Controllers\Admin\MetalXSettingsController;
use App\Http\Controllers\Admin\MetalXTeamController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\QuotationCategoryController;
use App\Http\Controllers\Admin\QuotationOptionController;
use App\Http\Controllers\Admin\RentalController as AdminRentalController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerPortalController;
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

    // Product Management
    Route::resource('products', AdminProductController::class);

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
        Route::get('/', [MetalXTeamController::class, 'index'])->name('index');
        Route::get('/create', [MetalXTeamController::class, 'create'])->name('create');
        Route::post('/', [MetalXTeamController::class, 'store'])->name('store');
        Route::get('/{metalX}/edit', [MetalXTeamController::class, 'edit'])->name('edit');
        Route::put('/{metalX}', [MetalXTeamController::class, 'update'])->name('update');
        Route::delete('/{metalX}', [MetalXTeamController::class, 'destroy'])->name('destroy');

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
});
