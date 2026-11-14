<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
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

    Route::middleware(['auth:sanctum', 'can:access-admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/categories', [CategoryController::class, 'adminIndex'])->name('categories.index');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('/products', [ProductController::class, 'adminIndex'])->name('products.index');
            Route::post('/products', [ProductController::class, 'store'])->name('products.store');
            Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
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
