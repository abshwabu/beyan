<?php

namespace App\Domain\Academics\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Database\Factories\ClassGroupFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassGroup extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'class_groups';

    protected $fillable = [
        'institution_id',
        'course_id',
        'academic_term_id',
        'name',
        'capacity',
        'type',
        'meeting_link',
        'room',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    protected static function newFactory(): ClassGroupFactory
    {
        return ClassGroupFactory::new();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'academic_term_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'class_group_teacher', 'class_group_id', 'teacher_id')
            ->withPivot(['is_primary', 'institution_id'])
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'class_group_id');
    }

    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'class_group_id')
            ->where('status', 'enrolled')
            ->whereNull('end_date');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_enrollments', 'class_group_id', 'student_id')
            ->withPivot(['start_date', 'end_date', 'status', 'notes', 'institution_id'])
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'class_group_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'class_group_id')->orderBy('start_time_utc', 'asc');
    }
}
