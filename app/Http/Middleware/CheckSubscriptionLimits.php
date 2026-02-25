<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Billing\BillingService;
use Spatie\Multitenancy\Models\Tenant;

class CheckSubscriptionLimits
{
    protected BillingService $billing;

    public function __construct(BillingService $billing)
    {
        $this->billing = $billing;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limitType = 'projects'): Response
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return response()->json(['error' => 'No active tenant context.'], 403);
        }

        if ($limitType === 'projects') {
            if (! $this->billing->canAddProject($tenant)) {
                return response()->json([
                    'error' => 'Project limit reached for your current plan.',
                    'requires_upgrade' => true
                ], 402); // 402 Payment Required
            }
        }

        return $next($request);
    }
}
