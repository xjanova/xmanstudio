<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SupportTicketController;

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');

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
