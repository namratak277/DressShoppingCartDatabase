<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OrderController;

Route::get('/', [OrderController::class, 'dresses'])->name('shop.dresses');
Route::get('/product/{id}', [OrderController::class, 'showProduct'])->name('shop.product');

Route::post('/cart/add', [OrderController::class, 'addToCart'])->name('cart.add');
Route::get('/cart', [OrderController::class, 'cart'])->name('cart.view');
Route::post('/cart/update', [OrderController::class, 'updateCart'])->name('cart.update');
Route::post('/cart/remove', [OrderController::class, 'removeFromCart'])->name('cart.remove');

Route::get('/checkout', [OrderController::class, 'checkoutForm'])->name('checkout.form');
Route::post('/checkout', [OrderController::class, 'checkout'])->name('checkout');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/orders', [OrderController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'showOrder'])->name('orders.show');
    Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
});
