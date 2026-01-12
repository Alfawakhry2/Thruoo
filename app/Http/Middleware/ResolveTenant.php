<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Multitenancy\Models\Tenant as TenantModel;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Spatie's tenant finder automatically resolves tenant from request
        // and makes it current. We just need to verify it exists and is active.
        $tenant = TenantModel::current();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found',
            ], 404);
        }

        // Check if tenant is active
        if ($tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Tenant is not active',
            ], 403);
        }

        // Explicitly make tenant current to ensure database connection is switched
        // This is important for Sanctum to find tokens in the correct database
        $tenant->makeCurrent();

        $currentConnection = Config::get('database.default');
        $tenantDatabase = Config::get('database.connections.tenant.database');

        // Log for debugging (optional - remove in production)
        Log::info('Tenant resolved', [
            'tenant_id' => $tenant->id,
            'tenant_subdomain' => $tenant->subdomain,
            'tenant_database' => $tenant->database,
            'current_connection' => $currentConnection,
            'tenant_connection_database' => $tenantDatabase,
        ]);

        return $next($request);
    }
}

