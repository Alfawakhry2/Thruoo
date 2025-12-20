<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Multitenancy\Models\Tenant;

class EnsureSubscriptionActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::current();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found',
            ], 404);
        }

        // Check if tenant is active (trial, grace period, or subscription)
        if (!$tenant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription has expired. Please renew your subscription.',
                'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
                'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
            ], 403);
        }

        return $next($request);
    }
}

