<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function impersonate(Request $request, $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        
        // Find owner or top user to jump into their context using Many-to-Many pivot
        $user = $tenant->users()->first();
        if (!$user) {
            return response()->json(['message' => 'No active user found for this tenant'], 404);
        }

        // Issue a Sanctum token acting as that user
        $token = $user->createToken('admin_impersonation', ['*'])->plainTextToken;

        return response()->json([
            'message' => 'Impersonation successful',
            'impersonation_token' => $token,
            'tenant' => $tenant->name
        ]);
    }
}
