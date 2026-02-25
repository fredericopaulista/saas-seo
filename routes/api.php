<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register'])->name('auth.register');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me'])->name('auth.me');
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->name('auth.logout');
    });

    // Google OAuth
    Route::get('/google', [\App\Http\Controllers\Api\GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/google/callback', [\App\Http\Controllers\Api\GoogleAuthController::class, 'callback'])->name('google.callback');
});

// Using a prefix and middleware (ideally auth:sanctum & tenant context)
// Note: You would normally enforce tenancy and project ownership here.
Route::prefix('dashboard/projects/{project}')->group(function () {
    Route::get('/overview', [\App\Http\Controllers\Api\DashboardController::class, 'overview'])->name('dashboard.overview');
    Route::get('/performance', [\App\Http\Controllers\Api\DashboardController::class, 'performance'])->name('dashboard.performance');
    Route::get('/insights', [\App\Http\Controllers\Api\DashboardController::class, 'insights'])->name('dashboard.insights');
});
