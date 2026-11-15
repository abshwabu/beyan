<?php

namespace App\Domain\Students\Models;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Auth\Models\User;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'students';

    protected $fillable = [
        'institution_id',
        'user_id',
        'guardian_id',
        'name',
        'admission_number',
        'gender',
        'date_of_birth',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
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

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_teacher', 'student_id', 'teacher_id')
            ->withTimestamps();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function feeInvoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class, 'student_id');
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
