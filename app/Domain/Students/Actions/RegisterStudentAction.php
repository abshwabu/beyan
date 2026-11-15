<?php

namespace App\Domain\Students\Actions;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Guardian;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\StudentIdGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

class RegisterStudentAction
{
    public function __construct(
        protected StudentIdGenerator $idGenerator
    ) {}

    /**
     * Register a new student with race-safe ID generation, optional initial class enrollment,
     * and optional guardian accounts.
     *
     * @param  array{
     *     institution_id?: ?string,
     *     full_name: string,
     *     name?: ?string,
     *     gender?: ?string,
     *     date_of_birth?: ?string,
     *     contact?: ?array,
     *     admission_date?: ?string,
     *     initial_class?: ?string,
     *     guardians?: ?array<array{
     *         full_name: string,
     *         phone?: ?string,
     *         email?: ?string,
     *         address?: ?string,
     *         occupation?: ?string,
     *         relationship_type?: ?string,
     *         is_primary?: ?bool,
     *         create_account?: ?bool
     *     }>
     * }  $data
     */
    public function execute(array $data): Student
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $institutionId = $data['institution_id'] ?? $tenantManager->getTenantId();

        if (empty($institutionId)) {
            throw new InvalidArgumentException('An institution ID is required to register a student.');
        }

        $institution = Institution::findOrFail($institutionId);

        return DB::transaction(function () use ($data, $institution) {
            $admissionDate = ! empty($data['admission_date'])
                ? Carbon::parse($data['admission_date'])
                : Carbon::today();

            $studentCode = $this->idGenerator->generate($institution, $admissionDate);

            $fullName = $data['full_name'] ?? $data['name'];

            $student = Student::withoutInstitutionScope()->create([
                'institution_id' => $institution->id,
                'full_name' => $fullName,
                'name' => $fullName,
                'student_code' => $studentCode,
                'admission_number' => $studentCode,
                'gender' => $data['gender'] ?? 'male',
                'date_of_birth' => ! empty($data['date_of_birth']) ? Carbon::parse($data['date_of_birth']) : null,
                'contact' => $data['contact'] ?? null,
                'admission_date' => $admissionDate,
                'status' => 'active',
            ]);

            // Initial Class Enrollment
            if (! empty($data['initial_class'])) {
                $student->enrollments()->create([
                    'institution_id' => $institution->id,
                    'class_name' => $data['initial_class'],
                    'start_date' => $admissionDate,
                    'end_date' => null,
                    'status' => 'current',
                    'notes' => 'Initial admission enrollment',
                ]);
            }

            // Guardians association
            if (! empty($data['guardians']) && is_array($data['guardians'])) {
                foreach ($data['guardians'] as $index => $gData) {
                    if (empty($gData['full_name'])) {
                        continue;
                    }

                    $guardian = null;
                    if (! empty($gData['email'])) {
                        $guardian = Guardian::withoutInstitutionScope()
                            ->where('institution_id', $institution->id)
                            ->where('email', strtolower(trim($gData['email'])))
                            ->first();
                    }

                    if (! $guardian) {
                        $guardian = Guardian::withoutInstitutionScope()->create([
                            'institution_id' => $institution->id,
                            'full_name' => $gData['full_name'],
                            'phone' => $gData['phone'] ?? null,
                            'email' => ! empty($gData['email']) ? strtolower(trim($gData['email'])) : null,
                            'address' => $gData['address'] ?? null,
                            'occupation' => $gData['occupation'] ?? null,
                        ]);
                    }

                    // Optional User account creation for guardian
                    if (! empty($gData['create_account']) && ! empty($gData['email'])) {
                        $email = strtolower(trim($gData['email']));
                        $guardianUser = User::withoutInstitutionScope()->where('email', $email)->first();

                        if (! $guardianUser) {
                            $guardianUser = User::withoutInstitutionScope()->create([
                                'institution_id' => $institution->id,
                                'name' => $gData['full_name'],
                                'email' => $email,
                                'password' => Hash::make(Str::random(16)),
                                'role' => 'guardian',
                                'email_verified_at' => Carbon::now(),
                            ]);
                        }

                        app(PermissionRegistrar::class)->setPermissionsTeamId($institution->id);
                        if (! $guardianUser->hasRole('Guardian')) {
                            $guardianUser->assignRolesWithAudit('Guardian');
                        }

                        $guardian->update(['user_id' => $guardianUser->id]);
                    }

                    $isPrimary = $gData['is_primary'] ?? ($index === 0);

                    $student->guardians()->attach($guardian->id, [
                        'institution_id' => $institution->id,
                        'relationship_type' => $gData['relationship_type'] ?? 'guardian',
                        'is_primary' => (bool) $isPrimary,
                    ]);

                    if ($isPrimary && $guardian->user_id) {
                        $student->update(['guardian_id' => $guardian->user_id]);
                    }
                }
            }

            return $student->load(['guardians', 'currentEnrollment', 'enrollments']);
        });
    }
}
