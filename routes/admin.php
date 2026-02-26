<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:sanctum', 'admin.permission'])->group(function () {
    Route::get('/dashboard', function (\App\Services\Admin\AdminDashboardService $service) {
        return response()->json($service->getExecutiveSummary());
    });

    // Sub-resources mapping
    Route::get('/tenants', function () {
        return response()->json([
            'tenants' => \App\Models\Tenant::latest()->get()
        ]);
    });

    Route::get('/billing-overview', function () {
        return response()->json([
            'plans' => \App\Models\Plan::withCount('subscriptions')->get(),
            'subscriptions' => \App\Models\Subscription::with(['tenant', 'plan'])->latest()->take(50)->get()
        ]);
    });
    
    // Impersonate Tenant
    Route::post('/tenants/{id}/impersonate', [\App\Http\Controllers\Api\Admin\ImpersonationController::class, 'impersonate']);

    // Toggle Tenant status
    Route::put('/tenants/{id}/toggle-status', function ($id) {
        $tenant = \App\Models\Tenant::findOrFail($id);
        $tenant->update(['is_active' => !$tenant->is_active]);
        return response()->json(['message' => 'Status updated successfully', 'is_active' => $tenant->is_active]);
    });

    // Plans CRUD
    Route::apiResource('/plans', \App\Http\Controllers\Api\Admin\PlanController::class);

    // System Monitoring
    Route::get('/system/health', function (\App\Services\Admin\SystemMonitoringService $service) {
        return response()->json($service->getSystemHealth());
    });

    // Global Settings
    Route::get('/settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'index']);
    Route::post('/settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'store']);

    // Webhook History
    Route::get('/webhooks', [\App\Http\Controllers\Api\Admin\WebhookEventController::class, 'index']);
    Route::get('/webhooks/{id}', [\App\Http\Controllers\Api\Admin\WebhookEventController::class, 'show']);
    Route::delete('/webhooks/{id}', [\App\Http\Controllers\Api\Admin\WebhookEventController::class, 'destroy']);
});
