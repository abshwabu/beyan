<?php

namespace App\Domain\Institution\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantInstitution
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Platform Staff (Super Admin or Support) operates outside any institution
            if (empty($user->institution_id) && $user->isPlatformStaff()) {
                $this->tenantManager->clear();
                app(PermissionRegistrar::class)->setPermissionsTeamId(null);
                $request->attributes->set('is_platform_admin', true);

                return $next($request);
            }

            $institution = $user->institution;

            if (! $institution) {
                abort(403, 'User does not belong to any institution.');
            }

            if ($institution->status === 'suspended' || $institution->fresh()?->status === 'suspended') {
                abort(403, 'Institution subscription is suspended. Please contact support.');
            }

            if ($institution->status === 'inactive') {
                abort(403, 'Institution account is inactive.');
            }

            $this->tenantManager->setTenant($institution);
            $this->tenantManager->setStrict(true);

            // Sync Spatie team context with current tenant
            app(PermissionRegistrar::class)->setPermissionsTeamId($institution->id);

            if ($institution->locale && in_array($institution->locale, ['en', 'am', 'om', 'ar'], true)) {
                app()->setLocale($institution->locale);
            }

            $request->attributes->set('tenant', $institution);
        }

        return $next($request);
    }
}
