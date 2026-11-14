<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/categories/{slug}', [StorefrontController::class, 'category'])->name('store.categories.show');
Route::get('/products/{slug}', [StorefrontController::class, 'product'])->name('store.products.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::middleware('can:access-admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::inertia('categories', 'Admin/Categories/Index')->name('categories.index');
            Route::inertia('products', 'Admin/Products/Index')->name('products.index');
        });
});

require __DIR__.'/settings.php';
