<?php

namespace App\Domain\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    /**
     * Handle an incoming request.
     * Allows Platform Super Admin and Platform Support.
     * Blocks non-platform users (403).
     * Enforces read-only access for Platform Support users on write methods.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isPlatformStaff()) {
            abort(403, 'Unauthorized. Platform Staff access required.');
        }

        // Support role is strictly read-only across all /platform write endpoints
        if ($user->isPlatformSupport() && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            abort(403, 'Unauthorized. Platform Support role is read-only and cannot perform write actions.');
        }

        return $next($request);
    }
}
