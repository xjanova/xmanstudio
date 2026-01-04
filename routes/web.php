<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');

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

// Support (contact page)
Route::view('/support', 'support.index')->name('support.index');

// About page
Route::view('/about', 'about')->name('about');

// Portfolio page
Route::view('/portfolio', 'portfolio')->name('portfolio');

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
});

require __DIR__.'/auth.php';
