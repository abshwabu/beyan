<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales for Beyan Academy SaaS.
     */
    public const SUPPORTED_LOCALES = ['en', 'am', 'om', 'ar'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestedLocale = $request->query('lang');

        if ($requestedLocale && in_array($requestedLocale, self::SUPPORTED_LOCALES, true)) {
            $locale = $requestedLocale;
            $request->session()->put('locale', $locale);
        } else {
            $locale = $request->session()->get('locale');

            if (! $locale || ! in_array($locale, self::SUPPORTED_LOCALES, true)) {
                $locale = $request->user()?->institution?->locale;

                if (! $locale || ! in_array($locale, self::SUPPORTED_LOCALES, true)) {
                    $locale = config('app.locale', 'en');
                }
            }
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
