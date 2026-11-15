<?php

namespace App\Domain\Institution\Http\Controllers;

use App\Domain\Finance\Models\Plan;
use App\Domain\Institution\Actions\SubmitDemoRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Support\DirectionHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LandingPageController extends Controller
{
    /**
     * Display the public marketing landing page.
     */
    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->isPlatformSuperAdmin()) {
                return redirect()->route('platform.institutions.index');
            }

            return redirect()->route('dashboard');
        }

        $locale = app()->getLocale();

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (Plan $plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'monthly_price_major' => $plan->monthly_price_major,
                    'yearly_price_major' => $plan->yearly_price_major,
                    'yearly_savings_percent' => $plan->yearly_savings_percent,
                    'currency' => $plan->currency,
                    'student_limit' => $plan->student_limit,
                    'features' => $plan->features ?? [],
                    'badge' => $plan->badge,
                    'sort_order' => $plan->sort_order,
                ];
            });

        return Inertia::render('Landing', [
            'plans' => $plans,
            'content' => trans('landing') ?: [],
            'locale' => $locale,
            'direction' => DirectionHelper::forLocale($locale),
            'supportedLocales' => [
                ['code' => 'en', 'name' => 'English', 'native' => 'English', 'dir' => 'ltr'],
                ['code' => 'am', 'name' => 'Amharic', 'native' => 'አማርኛ', 'dir' => 'ltr'],
                ['code' => 'om', 'name' => 'Afaan Oromoo', 'native' => 'Afaan Oromoo', 'dir' => 'ltr'],
                ['code' => 'ar', 'name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl'],
            ],
        ]);
    }

    /**
     * Switch active interface language.
     */
    public function setLocale(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, SetLocale::SUPPORTED_LOCALES, true)) {
            $request->session()->put('locale', $locale);
            app()->setLocale($locale);
        }

        return redirect()->back();
    }

    /**
     * Process a public demo request.
     */
    public function submitDemo(Request $request, SubmitDemoRequestAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'madrasa_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'mode' => ['required', 'in:physical,online,hybrid'],
            'student_count_range' => ['required', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
            'hp_website' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['ip_address'] = $request->ip();
        $validated['user_agent'] = $request->userAgent();

        $action->execute($validated);

        return redirect()->back()->with('success', trans('landing.demo.success_message') ?? 'Thank you! We have received your demo request and will reach out shortly via phone/WhatsApp.');
    }

    /**
     * Render XML Sitemap for search engines.
     */
    public function sitemap(Request $request): Response
    {
        $baseUrl = config('app.url', url('/'));

        $routes = [
            ['loc' => $baseUrl.'/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => $baseUrl.'/register-institution', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => $baseUrl.'/login', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => $baseUrl.'/privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => $baseUrl.'/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        $locales = ['en', 'am', 'om', 'ar'];

        foreach ($routes as $route) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($route['loc']).'</loc>';
            if ($route['loc'] === $baseUrl.'/') {
                foreach ($locales as $loc) {
                    $xml .= '<xhtml:link rel="alternate" hreflang="'.$loc.'" href="'.htmlspecialchars($baseUrl.'/?lang='.$loc).'"/>';
                }
            }
            $xml .= '<lastmod>'.date('Y-m-d').'</lastmod>';
            $xml .= '<changefreq>'.$route['changefreq'].'</changefreq>';
            $xml .= '<priority>'.$route['priority'].'</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Render Privacy Policy page.
     */
    public function privacy(Request $request): InertiaResponse
    {
        return Inertia::render('Legal/Privacy');
    }

    /**
     * Render Terms of Service page.
     */
    public function terms(Request $request): InertiaResponse
    {
        return Inertia::render('Legal/Terms');
    }
}
