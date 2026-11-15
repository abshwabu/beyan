<?php

namespace App\Domain\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformSuperAdmin
{
    /**
     * Handle an incoming request.
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
