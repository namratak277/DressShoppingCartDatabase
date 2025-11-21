<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OrderController;

/**
 * Shop routes (class-based controller) — these map the store endpoints
 * to the `OrderController` implemented under `app/Http/Controllers/Admin`.
 * They are optional: the procedural public files still exist under `public/`.
 */
Route::get('/', [OrderController::class, 'dresses'])->name('shop.dresses');
Route::post('/cart/add', [OrderController::class, 'addToCart'])->name('cart.add');
Route::get('/cart', [OrderController::class, 'cart'])->name('cart.view');
Route::post('/checkout', [OrderController::class, 'checkout'])->name('checkout');

// Admin order management (class controller)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/orders', [OrderController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'showOrder'])->name('orders.show');
    Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
});

// Simple redirects so the legacy public PHP entrypoints are reachable
// (useful when serving with the built-in PHP server against `public/`).
Route::get('/products.php', function () { return redirect('/'); });
Route::get('/cart.php', function () { return redirect('/cart'); });
Route::get('/checkout.php', function () { return redirect('/checkout'); });


Route::get('/', function () {
    return view('welcome');
});
