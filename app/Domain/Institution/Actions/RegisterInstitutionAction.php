<?php

namespace App\Domain\Institution\Actions;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Term;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Institution\Events\InstitutionRegistered;
use App\Domain\Institution\Models\Institution;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RegisterInstitutionAction
{
    /**
     * Register a new institution and its owner user within one short transaction.
     *
     * @param  array{
     *     institution_name: string,
     *     institution_slug?: ?string,
     *     mode?: ?string,
     *     timezone?: ?string,
     *     currency?: ?string,
     *     locale?: ?string,
     *     phone?: ?string,
     *     email?: ?string,
     *     address?: ?string,
     *     owner_name: string,
     *     owner_email: string,
     *     owner_password: string
     * }  $data
     * @return array{institution: Institution, user: User}
     */
    public function execute(array $data): array
    {
        $slug = ! empty($data['institution_slug'])
            ? Str::slug($data['institution_slug'])
            : Str::slug($data['institution_name']);

        if (Institution::where('slug', $slug)->exists()) {
            $slug = $slug.'-'.strtolower(Str::random(5));
        }

        // Short write transaction strictly for database persistence.
        // No external services (HTTP, email) are called inside this block.
        $trialDays = (int) config('app.trial_days', 14);

        $result = DB::transaction(function () use ($data, $slug, $trialDays) {
            $institution = Institution::create([
                'name' => $data['institution_name'],
                'slug' => $slug,
                'mode' => $data['mode'] ?? 'hybrid',
                'timezone' => $data['timezone'] ?? 'Africa/Addis_Ababa',
                'currency' => $data['currency'] ?? 'ETB',
                'locale' => $data['locale'] ?? 'en',
                'status' => 'active',
                'subscription_plan' => $data['subscription_plan'] ?? $data['plan'] ?? 'trial',
                'subscription_ends_at' => Carbon::now()->addDays($trialDays),
                'contact' => [
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? $data['owner_email'],
                    'address' => $data['address'] ?? null,
                ],
            ]);

            $user = User::withoutInstitutionScope()->create([
                'institution_id' => $institution->id,
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'role' => 'owner',
                'password' => Hash::make($data['owner_password']),
                'email_verified_at' => Carbon::now(),
            ]);

            $now = Carbon::now();
            $yearName = $now->year.'-'.($now->year + 1);

            $academicYear = AcademicYear::withoutInstitutionScope()->create([
                'institution_id' => $institution->id,
                'name' => $yearName,
                'start_date' => $now->copy()->startOfYear()->toDateString(),
                'end_date' => $now->copy()->endOfYear()->toDateString(),
                'is_current' => true,
            ]);

            Term::withoutInstitutionScope()->create([
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'Term 1',
                'start_date' => $now->copy()->startOfYear()->toDateString(),
                'end_date' => $now->copy()->startOfYear()->addMonths(4)->toDateString(),
                'is_current' => true,
            ]);

            // Seed institution roles and assign Owner role to the new user
            $matrix = new RolePermissionMatrix;
            $matrix->seedPermissions();
            $matrix->seedInstitutionRoles($institution->id);

            app(PermissionRegistrar::class)->setPermissionsTeamId($institution->id);
            $user->assignRole('Owner');

            return [
                'institution' => $institution,
                'user' => $user,
            ];
        });

        // Any notifications or async events are dispatched outside the transaction
        event(new InstitutionRegistered($result['institution'], $result['user']));

        return $result;
    }
}
