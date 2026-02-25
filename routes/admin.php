<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:admin', 'admin.permission'])->group(function () {
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
});
