<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\LicenseController as AdminLicenseController;
use App\Http\Controllers\Admin\LineMessagingController;
use App\Http\Controllers\Admin\MetalXSettingsController;
use App\Http\Controllers\Admin\MetalXTeamController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\QuotationCategoryController;
use App\Http\Controllers\Admin\QuotationOptionController;
use App\Http\Controllers\Admin\RentalController as AdminRentalController;
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

    // Orders
    if (class_exists(OrderController::class)) {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    }

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
