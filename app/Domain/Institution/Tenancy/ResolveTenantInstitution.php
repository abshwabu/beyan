<?php

namespace App\Domain\Institution\Tenancy;

use Closure;
use Illuminate\Http\Request;
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
            $institution = $user->institution;

            if (! $institution) {
                abort(403, 'User does not belong to any institution.');
            }

            if ($institution->status === 'suspended') {
                abort(403, 'Institution subscription is suspended. Please contact support.');
            }

            if ($institution->status === 'inactive') {
                abort(403, 'Institution account is inactive.');
            }

            $this->tenantManager->setTenant($institution);
            $this->tenantManager->setStrict(true);

            if ($institution->locale && in_array($institution->locale, ['en', 'am', 'om', 'ar'], true)) {
                app()->setLocale($institution->locale);
            }

            $request->attributes->set('tenant', $institution);
        }

        return $next($request);
    }
}
