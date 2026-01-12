<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Multitenancy\Models\Tenant;

class EnsureModuleEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $module
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = Tenant::current();

        if (!$tenant || !$tenant->hasModule($module)) {
            return response()->json([
                'success' => false,
                'message' => "Module '{$module}' is not enabled for this tenant",
            ], 403);
        }

        return $next($request);
    }
}

