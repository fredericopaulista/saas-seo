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

// Projects Management (General Auth logic)
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/projects', \App\Http\Controllers\Api\ProjectController::class);
});

// Using a prefix and middleware (ideally auth:sanctum & tenant context)
// Note: You would normally enforce tenancy and project ownership here.
// Adjusted back to match what the frontend dashboard was hitting recently.
Route::prefix('dashboard/projects/{project}')->group(function () {
    Route::get('/overview', [\App\Http\Controllers\Api\DashboardController::class, 'overview'])->name('dashboard.overview');
    Route::get('/performance', [\App\Http\Controllers\Api\DashboardController::class, 'performance'])->name('dashboard.performance');
    Route::get('/insights', [\App\Http\Controllers\Api\DashboardController::class, 'insights'])->name('dashboard.insights');
    Route::post('/insights/{insight}/resolve', [\App\Http\Controllers\Api\DashboardController::class, 'resolveInsight'])->name('dashboard.insights.resolve');
    Route::get('/urls', [\App\Http\Controllers\Api\DashboardController::class, 'urls'])->name('dashboard.urls');
    Route::post('/sync', [\App\Http\Controllers\Api\DashboardController::class, 'triggerSync'])->name('dashboard.sync');
    Route::post('/inspect-urls', [\App\Http\Controllers\Api\DashboardController::class, 'triggerUrlInspection'])->name('dashboard.inspect.urls');
});

// Asaas Webhooks (Public, validated via Header Token)
Route::post('/webhooks/asaas', [\App\Http\Controllers\Api\Webhook\AsaasWebhookController::class, 'handle']);

Route::get('billing/plans', [\App\Http\Controllers\Api\BillingController::class, 'getPlans']);

Route::prefix('billing')->middleware('auth:sanctum')->group(function () {
    Route::get('/my-subscription', [\App\Http\Controllers\Api\BillingController::class, 'mySubscription']);
    Route::post('/subscribe', [\App\Http\Controllers\Api\BillingController::class, 'subscribe']);
    Route::post('/cancel', [\App\Http\Controllers\Api\BillingController::class, 'cancelSubscription']);
});
