<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Platform\Models\PlatformAnnouncement;
use App\Support\DirectionHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $currentTenant = $tenantManager->getTenant();
        $user = $request->user();
        $locale = app()->getLocale();

        // 1. Support Impersonation Check and Auto-Expiration (30-min window)
        $impersonation = null;
        if ($impersonatorId = $request->session()->get('impersonator_id')) {
            $expiresAt = (int) $request->session()->get('impersonation_expires_at');
            if (Carbon::now()->timestamp >= $expiresAt) {
                // Impersonation auto-expired
                $instId = $request->session()->get('impersonated_institution_id');
                if ($instId && ($inst = Institution::find($instId))) {
                    $inst->recordAudit('impersonation_expired', null, [
                        'admin_id' => $impersonatorId,
                        'expired_at' => Carbon::now()->toIso8601String(),
                    ]);
                }
                $request->session()->forget([
                    'impersonator_id',
                    'impersonator_name',
                    'impersonated_institution_id',
                    'impersonated_user_id',
                    'impersonation_reason',
                    'impersonation_expires_at',
                ]);
                if ($origAdmin = User::withoutInstitutionScope()->find($impersonatorId)) {
                    Auth::login($origAdmin);
                    $user = $origAdmin;
                }
            } else {
                $impersonation = [
                    'active' => true,
                    'institution_id' => $request->session()->get('impersonated_institution_id'),
                    'institution_name' => $currentTenant?->name,
                    'reason' => $request->session()->get('impersonation_reason'),
                    'impersonator_name' => $request->session()->get('impersonator_name'),
                    'expires_at' => $expiresAt,
                    'minutes_remaining' => max(0, (int) ceil(($expiresAt - Carbon::now()->timestamp) / 60)),
                ];
            }
        }

        // 2. Active Platform Announcement Check
        $announcement = null;
        if ($currentTenant && $user) {
            $activeAnnouncement = PlatformAnnouncement::active()
                ->whereDoesntHave('dismissals', fn ($q) => $q->where('user_id', $user->id))
                ->latest()
                ->first();

            if ($activeAnnouncement && $activeAnnouncement->matchesTarget($currentTenant->mode, $currentTenant->subscription_plan)) {
                $announcement = [
                    'id' => $activeAnnouncement->id,
                    'title' => $activeAnnouncement->title,
                    'body' => $activeAnnouncement->body,
                ];
            }
        }

        return [
            ...parent::share($request),
            'impersonation' => $impersonation,
            'announcement' => $announcement,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ] : null,
            ],
            'tenant' => $currentTenant ? [
                'id' => $currentTenant->id,
                'name' => $currentTenant->name,
                'slug' => $currentTenant->slug,
                'mode' => $currentTenant->mode,
                'timezone' => $currentTenant->timezone,
                'currency' => $currentTenant->currency,
                'locale' => $currentTenant->locale,
                'status' => $currentTenant->status,
            ] : null,
            'i18n' => [
                'locale' => $locale,
                'direction' => DirectionHelper::forLocale($locale),
                'translations' => trans('app') ?: [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
