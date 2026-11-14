<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerAddressController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductImageController;
use App\Http\Controllers\Api\V1\ProductVariantController;
use App\Http\Controllers\Api\V1\StoreSettingController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'data' => [
                'service' => 'api',
                'version' => 'v1',
            ],
        ]);
    })->name('health');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/store-settings', [StoreSettingController::class, 'publicSettings'])->name('store-settings.public');

    Route::middleware(['auth:sanctum', 'can:access-admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/settings/store', [StoreSettingController::class, 'show'])->name('settings.store.show');
            Route::patch('/settings/store', [StoreSettingController::class, 'update'])->name('settings.store.update');

            Route::get('/categories', [CategoryController::class, 'adminIndex'])->name('categories.index');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('/products', [ProductController::class, 'adminIndex'])->name('products.index');
            Route::post('/products', [ProductController::class, 'store'])->name('products.store');
            Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

            Route::post('/products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
            Route::patch('/products/{product}/images/{image}', [ProductImageController::class, 'update'])->name('products.images.update');
            Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');

            Route::get('/products/{product}/variants', [ProductVariantController::class, 'index'])->name('products.variants.index');
            Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
            Route::patch('/products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('products.variants.update');
            Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('products.variants.destroy');
            Route::post('/products/{product}/variants/{variant}/inventory-adjustments', [ProductVariantController::class, 'adjustInventory'])->name('products.variants.inventory-adjustments.store');
            Route::get('/products/{product}/variants/{variant}/inventory-movements', [ProductVariantController::class, 'movements'])->name('products.variants.inventory-movements.index');
        });

    Route::middleware('auth:sanctum')
        ->prefix('me')
        ->name('me.')
        ->group(function () {
            Route::get('/addresses', [CustomerAddressController::class, 'index'])->name('addresses.index');
            Route::post('/addresses', [CustomerAddressController::class, 'store'])->name('addresses.store');
            Route::patch('/addresses/{address}', [CustomerAddressController::class, 'update'])->name('addresses.update');
            Route::delete('/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('addresses.destroy');
        });

    Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'is_admin' => $user->is_admin,
            ],
        ]);
    })->name('me');
});
