<?php

namespace App\Domain\Institution\Http\Controllers;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Term;
use App\Domain\Auth\Models\User;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TenantManager $tenantManager): Response
    {
        $institution = $tenantManager->getTenant();

        // Due to BelongsToInstitution global scope, these queries only return records
        // belonging to the current tenant institution!
        $academicYears = AcademicYear::with('terms')->latest()->get();
        $currentYear = AcademicYear::where('is_current', true)->first();
        $currentTerm = Term::where('is_current', true)->first();
        $usersCount = User::count();

        return Inertia::render('Dashboard', [
            'institution' => $institution,
            'academicYears' => $academicYears,
            'currentYear' => $currentYear,
            'currentTerm' => $currentTerm,
            'stats' => [
                'usersCount' => $usersCount,
                'academicYearsCount' => $academicYears->count(),
            ],
        ]);
    }
}
