<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;

class BillingService
{
    /**
     * Checks if the tenant can add another project based on their current active plan.
     */
    public function canAddProject(Tenant $tenant): bool
    {
        $subscription = $this->getActiveSubscription($tenant);

        // Without an active subscription, no specific limit strategy applies, default to max 1.
        if (! $subscription || ! $subscription->plan) {
            return $tenant->projects()->count() < 1;
        }

        $features = $subscription->plan->features_json;
        $maxProjects = $features['max_projects'] ?? 1;

        // E.g., setting max_projects = -1 for 'unlimited' Agency plan
        if ($maxProjects === -1) {
            return true;
        }

        return $tenant->projects()->count() < $maxProjects;
    }

    /**
     * Retrieve the current active subscription for a tenant.
     */
    public function getActiveSubscription(Tenant $tenant): ?Subscription
    {
        return $tenant->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', now());
            })
            ->latest()
            ->first();
    }

    /**
     * Returns the retention days historical limit for GSC syncing based on Plan.
     */
    public function getGscRetentionDays(Tenant $tenant): int
    {
        $subscription = $this->getActiveSubscription($tenant);
        if (! $subscription || ! $subscription->plan) {
            return 90; // Default Starter: 3 months
        }

        $features = $subscription->plan->features_json;
        return $features['gsc_retENTION_days'] ?? 90; // Pro/Agency might return 480 (~16 months)
    }
}
