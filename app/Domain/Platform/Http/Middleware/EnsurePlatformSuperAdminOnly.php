<?php

namespace App\Domain\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformSuperAdminOnly
{
    /**
     * Handle an incoming request.
     * Strictly requires Platform Super Admin role (Support users forbidden).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isPlatformSuperAdmin()) {
            abort(403, 'Unauthorized. Platform Super Admin privilege required.');
        }

        return $next($request);
    }
}
