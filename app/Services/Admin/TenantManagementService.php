<?php

namespace App\Services\Admin;

use App\Models\Tenant;

class TenantManagementService
{
    public function suspendTenant(Tenant $tenant): void
    {
        // TODO: Implement suspension logic (e.g. status flag)
    }

    public function reactivateTenant(Tenant $tenant): void
    {
        // TODO: Implement reactivation logic
    }
    
    public function resetGoogleTokens(Tenant $tenant): void
    {
        // TODO: Revoke Google APIs OAuth tokens for tenant
    }
}
