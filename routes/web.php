<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::middleware('can:access-admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::inertia('categories', 'Admin/Categories/Index')->name('categories.index');
        });
});

require __DIR__.'/settings.php';
