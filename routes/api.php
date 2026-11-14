<?php

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

    Route::get('/categories', [\\App\\Http\\Controllers\\Api\\V1\\CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{slug}', [\\App\\Http\\Controllers\\Api\\V1\\CategoryController::class, 'show'])->name('categories.show');

    Route::middleware(['auth:sanctum', 'can:access-admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/categories', [\\App\\Http\\Controllers\\Api\\V1\\CategoryController::class, 'adminIndex'])->name('categories.index');
            Route::post('/categories', [\\App\\Http\\Controllers\\Api\\V1\\CategoryController::class, 'store'])->name('categories.store');
            Route::patch('/categories/{category}', [\\App\\Http\\Controllers\\Api\\V1\\CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [\\App\\Http\\Controllers\\Api\\V1\\CategoryController::class, 'destroy'])->name('categories.destroy');
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
