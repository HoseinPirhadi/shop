<?php

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
});
