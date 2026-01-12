<?php

namespace App\Multitenancy\TenantFinder;

use App\Models\Landlord\Company;
use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

/**
 * Company Tenant Finder
 * Resolves tenant by Company subdomain (not old Tenant model)
 */
class CompanyTenantFinder extends TenantFinder
{
    /**
     * Find company (tenant) by subdomain from request host
     *
     * @param Request $request
     * @return Company|null
     */
    public function findForRequest(Request $request): ?Company
    {
        $host = $request->getHost();

        // Remove port if present (e.g., acme.thruoo.local:8000 -> acme.thruoo.local)
        $host = preg_replace('/:\d+$/', '', $host);

        // Extract subdomain from host
        $tenantDomain = config('app.tenant_domain', 'thruoo.local');

        // Check if it's a subdomain request
        if (str_ends_with($host, '.' . $tenantDomain)) {
            // Extract subdomain (e.g., ahmed-tech.thruoo.local -> ahmed-tech)
            $subdomain = str_replace('.' . $tenantDomain, '', $host);
        } elseif ($host === $tenantDomain) {
            // Main domain - no tenant/company
            return null;
        } else {
            // Check for custom domain (use landlord connection)
            $company = Company::on('mysql')
                ->where('domain', $host)
                ->where('status', Company::STATUS_ACTIVE)
                ->first();

            if ($company) {
                return $company;
            }

            // Try to extract subdomain from any domain
            $parts = explode('.', $host);
            if (count($parts) >= 2) {
                $subdomain = $parts[0];
            } else {
                return null;
            }
        }

        // Find company by subdomain (use landlord connection)
        return Company::on('mysql')
            ->where('subdomain', $subdomain)
            ->where('status', Company::STATUS_ACTIVE)
            ->first();
    }
}
