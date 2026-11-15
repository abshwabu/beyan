<?php

namespace App\Domain\Students\Models;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Auth\Models\User;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Hifz\Models\HifzCertification;
use App\Domain\Hifz\Models\HifzEntry;
use App\Domain\Hifz\Models\HifzTarget;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Services\StudentIdGenerator;
use Carbon\Carbon;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Student extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'students';

    protected $fillable = [
        'institution_id',
        'user_id',
        'guardian_id',
        'full_name',
        'name',
        'student_code',
        'admission_number',
        'gender',
        'date_of_birth',
        'contact',
        'admission_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'admission_date' => 'date',
            'contact' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            // Keep name and full_name in sync
            if (empty($student->full_name) && ! empty($student->name)) {
                $student->full_name = $student->name;
            } elseif (empty($student->name) && ! empty($student->full_name)) {
                $student->name = $student->full_name;
            }

            // Auto-generate race-safe student ID if not provided
            if (empty($student->student_code) && empty($student->admission_number)) {
                $institution = $student->institution ?? Institution::find($student->institution_id);
                if ($institution) {
                    $generator = app(StudentIdGenerator::class);
                    $code = $generator->generate($institution, $student->admission_date ? Carbon::parse($student->admission_date) : null);
                    $student->student_code = $code;
                    $student->admission_number = $code;
                }
            } elseif (empty($student->student_code) && ! empty($student->admission_number)) {
                $student->student_code = $student->admission_number;
            } elseif (empty($student->admission_number) && ! empty($student->student_code)) {
                $student->admission_number = $student->student_code;
            }

            if (empty($student->status)) {
                $student->status = 'active';
            }
        });

        static::updating(function (Student $student) {
            if ($student->isDirty('full_name') && ! $student->isDirty('name')) {
                $student->name = $student->full_name;
            } elseif ($student->isDirty('name') && ! $student->isDirty('full_name')) {
                $student->full_name = $student->name;
            }
        });
    }

    protected static function newFactory(): StudentFactory
    {
        return StudentFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student', 'student_id', 'guardian_id')
            ->withPivot(['relationship_type', 'is_primary', 'institution_id'])
            ->withTimestamps();
    }

    public function primaryGuardian(): ?Guardian
    {
        return $this->guardians()->wherePivot('is_primary', true)->first()
            ?? $this->guardians()->first();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id')->orderBy('start_date', 'desc');
    }

    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class, 'student_id')
            ->whereNull('end_date')
            ->where('status', 'current');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class, 'student_id')->orderBy('effective_date', 'desc');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'student_id')->orderBy('created_at', 'desc');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_teacher', 'student_id', 'teacher_id')
            ->withTimestamps();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function hifzEntries(): HasMany
    {
        return $this->hasMany(HifzEntry::class, 'student_id');
    }

    public function hifzTargets(): HasMany
    {
        return $this->hasMany(HifzTarget::class, 'student_id');
    }

    public function hifzCertifications(): HasMany
    {
        return $this->hasMany(HifzCertification::class, 'student_id');
    }

    public function feeInvoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class, 'student_id');
    }

    /**
     * Transfer student to a new class with effective-dated history retention.
     */
    public function transferClass(string $newClassName, ?string $transferDate = null, ?string $notes = null): StudentEnrollment
    {
        return DB::transaction(function () use ($newClassName, $transferDate, $notes) {
            $effectiveDate = $transferDate ? Carbon::parse($transferDate) : Carbon::today();

            // End active enrollment
            $current = $this->currentEnrollment()->first();
            if ($current) {
                $current->update([
                    'end_date' => $effectiveDate,
                    'status' => 'transferred',
                ]);
            }

            // Create new effective-dated enrollment
            return $this->enrollments()->create([
                'institution_id' => $this->institution_id,
                'class_name' => $newClassName,
                'start_date' => $effectiveDate,
                'end_date' => null,
                'status' => 'current',
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Record a status change with reason and effective date without deleting history.
     */
    public function changeStatus(string $newStatus, string $reason, ?string $effectiveDate = null, ?string $changedById = null): StudentStatusHistory
    {
        return DB::transaction(function () use ($newStatus, $reason, $effectiveDate, $changedById) {
            $prevStatus = $this->status;
            $date = $effectiveDate ? Carbon::parse($effectiveDate) : Carbon::today();

            $history = $this->statusHistories()->create([
                'institution_id' => $this->institution_id,
                'changed_by_id' => $changedById ?? auth()->id(),
                'previous_status' => $prevStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'effective_date' => $date,
            ]);

            $this->update(['status' => $newStatus]);

            return $history;
        });
    }

    /**
     * Determine if this student is assigned to a specific teacher.
     */
    public function isAssignedToTeacher(User $teacher): bool
    {
        if ($this->relationLoaded('teachers')) {
            return $this->teachers->contains('id', $teacher->id);
        }

        return $this->teachers()->where('users.id', $teacher->id)->exists();
    }
}
