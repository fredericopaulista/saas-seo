<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GoogleAuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Using simple group without full auth to simplify boilerplate for MVP demonstration
// Replace with middleware('auth:sanctum') in secure production wrapper

// Projects Management
// Using apiResource creates GET /projects, POST /projects, DELETE /projects/{project} etc.
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/projects', \App\Http\Controllers\Api\ProjectController::class);
});

Route::prefix('dashboard')->group(function () {
    Route::get('/projects/{project}/overview', [DashboardController::class, 'overview']);
    Route::get('/projects/{project}/performance', [DashboardController::class, 'performance']);
    Route::get('/projects/{project}/insights', [DashboardController::class, 'insights']);
    Route::get('/projects/{project}/urls', [DashboardController::class, 'urls']);
    Route::post('/projects/{project}/sync', [DashboardController::class, 'triggerSync']);
    Route::post('/projects/{project}/inspect-urls', [DashboardController::class, 'triggerUrlInspection']);
});

// Admin global settings routes
Route::prefix('admin')->group(function () {
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index']);
    Route::post('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update']);
});

// Google OAuth Flow
Route::get('/auth/google', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
