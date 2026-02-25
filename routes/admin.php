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
    
    // Impersonate Tenant
    Route::post('/tenants/{id}/impersonate', [\App\Http\Controllers\Api\Admin\ImpersonationController::class, 'impersonate']);
});
