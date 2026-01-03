<?php

use App\Http\Controllers\Admin\LicenseController as AdminLicenseController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\RentalController as AdminRentalController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

// Health check endpoint for monitoring
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
    ]);
})->name('health');

// Home route - explicitly support both GET and HEAD methods
Route::match(['GET', 'HEAD'], '/', [HomeController::class, 'index'])->name('home');

// Products & Services
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/services', [ProductController::class, 'services'])->name('services');
Route::get('/services/{slug}', [ProductController::class, 'serviceDetail'])->name('services.detail');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{item}', [CartController::class, 'remove'])->name('cart.remove');

// Orders
Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/orders/{order}/download', [OrderController::class, 'download'])->name('orders.download');

// Support
Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');

// Portfolio
Route::get('/portfolio', function () {
    return view('portfolio');
})->name('portfolio');

// ==================== Rental System Routes ====================

// Public rental pages
Route::get('/rental', [RentalController::class, 'index'])->name('rental.index');

// Authenticated rental routes
Route::middleware(['auth'])->group(function () {
    Route::get('/rental/status', [RentalController::class, 'status'])->name('rental.status');
    Route::get('/rental/checkout/{package}', [RentalController::class, 'checkout'])->name('rental.checkout');
    Route::post('/rental/subscribe/{package}', [RentalController::class, 'subscribe'])->name('rental.subscribe');
    Route::post('/rental/validate-promo', [RentalController::class, 'validatePromo'])->name('rental.validate-promo');
    Route::get('/rental/payment/{payment}', [RentalController::class, 'payment'])->name('rental.payment');
    Route::post('/rental/payment/{payment}/confirm', [RentalController::class, 'confirmPayment'])->name('rental.confirm-payment');
    Route::get('/rental/invoice/{invoice}', [RentalController::class, 'invoice'])->name('rental.invoice');
});

// ==================== Admin Routes ====================

Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    // Rental Management
    Route::get('/rentals', [AdminRentalController::class, 'index'])->name('rentals.index');
    Route::get('/rentals/{rental}', [AdminRentalController::class, 'show'])->name('rentals.show');
    Route::post('/rentals/{rental}/extend', [AdminRentalController::class, 'extend'])->name('rentals.extend');
    Route::post('/rentals/{rental}/suspend', [AdminRentalController::class, 'suspend'])->name('rentals.suspend');
    Route::post('/rentals/{rental}/reactivate', [AdminRentalController::class, 'reactivate'])->name('rentals.reactivate');

    // Payment Management
    Route::get('/rentals/payments', [AdminRentalController::class, 'payments'])->name('rentals.payments');
    Route::post('/rentals/payments/{payment}/verify', [AdminRentalController::class, 'verifyPayment'])->name('rentals.payments.verify');
    Route::post('/rentals/payments/{payment}/reject', [AdminRentalController::class, 'rejectPayment'])->name('rentals.payments.reject');

    // Package Management
    Route::get('/rentals/packages', [AdminRentalController::class, 'packages'])->name('rentals.packages');
    Route::get('/rentals/packages/create', [AdminRentalController::class, 'createPackage'])->name('rentals.packages.create');
    Route::post('/rentals/packages', [AdminRentalController::class, 'storePackage'])->name('rentals.packages.store');
    Route::get('/rentals/packages/{package}/edit', [AdminRentalController::class, 'editPackage'])->name('rentals.packages.edit');
    Route::put('/rentals/packages/{package}', [AdminRentalController::class, 'updatePackage'])->name('rentals.packages.update');
    Route::post('/rentals/packages/{package}/toggle', [AdminRentalController::class, 'togglePackage'])->name('rentals.packages.toggle');

    // Reports
    Route::get('/rentals/reports', [AdminRentalController::class, 'reports'])->name('rentals.reports');

    // Service Management
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::post('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    Route::post('/services/order', [ServiceController::class, 'updateOrder'])->name('services.order');

    // Payment Settings Management
    Route::get('/payment-settings', [PaymentSettingController::class, 'index'])->name('payment-settings.index');
    Route::put('/payment-settings', [PaymentSettingController::class, 'update'])->name('payment-settings.update');
    Route::post('/payment-settings/bank', [PaymentSettingController::class, 'storeBank'])->name('payment-settings.bank.store');
    Route::put('/payment-settings/bank/{bankAccount}', [PaymentSettingController::class, 'updateBank'])->name('payment-settings.bank.update');
    Route::post('/payment-settings/bank/{bankAccount}/toggle', [PaymentSettingController::class, 'toggleBank'])->name('payment-settings.bank.toggle');
    Route::delete('/payment-settings/bank/{bankAccount}', [PaymentSettingController::class, 'destroyBank'])->name('payment-settings.bank.destroy');

    // License Management
    Route::get('/licenses', [AdminLicenseController::class, 'index'])->name('licenses.index');
    Route::get('/licenses/create', [AdminLicenseController::class, 'create'])->name('licenses.create');
    Route::post('/licenses', [AdminLicenseController::class, 'store'])->name('licenses.store');
    Route::get('/licenses/{license}', [AdminLicenseController::class, 'show'])->name('licenses.show');
    Route::post('/licenses/{license}/revoke', [AdminLicenseController::class, 'revoke'])->name('licenses.revoke');
    Route::post('/licenses/{license}/reactivate', [AdminLicenseController::class, 'reactivate'])->name('licenses.reactivate');
    Route::post('/licenses/{license}/reset-machine', [AdminLicenseController::class, 'resetMachine'])->name('licenses.reset-machine');
    Route::post('/licenses/{license}/extend', [AdminLicenseController::class, 'extend'])->name('licenses.extend');

    // Product Management
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::post('/products/{product}/toggle', [AdminProductController::class, 'toggle'])->name('products.toggle');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
});
