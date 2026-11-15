<?php

namespace App\Domain\Attendance\Models;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Models\Student;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'attendance_records';

    protected $fillable = [
        'institution_id',
        'student_id',
        'academic_year_id',
        'date',
        'status',
        'remarks',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    protected static function newFactory(): AttendanceRecordFactory
    {
        return AttendanceRecordFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
