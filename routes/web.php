<?php

use App\Http\Controllers\Admin\RentalController as AdminRentalController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

// Debug route - check this first on production: /debug-routes
Route::get('/debug-routes', function () {
    $allRoutes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
        ];
    });

    $rootRoutes = $allRoutes->filter(fn ($r) => $r['uri'] === '/');

    return response()->json([
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'total_routes' => $allRoutes->count(),
        'root_route_count' => $rootRoutes->count(),
        'root_routes' => $rootRoutes->values(),
        'cached' => app()->routesAreCached(),
        'cache_file_exists' => file_exists(base_path('bootstrap/cache/routes-v7.php')),
        'bootstrap_app_exists' => file_exists(base_path('bootstrap/app.php')),
        'web_routes_exists' => file_exists(base_path('routes/web.php')),
        'request_method' => request()->method(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    ], 200, [], JSON_PRETTY_PRINT);
});

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
Route::get('/rental', [RentalController::class, 'packages'])->name('rental.packages');

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
});
