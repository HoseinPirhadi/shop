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
