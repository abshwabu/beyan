<?php

namespace App\Domain\Attendance\Models;

use App\Domain\Academics\Models\ClassSession;
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
        'class_session_id',
        'session_id',
        'academic_year_id',
        'date',
        'status',
        'note',
        'remarks',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'recorded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AttendanceRecord $record) {
            if (! empty($record->class_session_id) && empty($record->session_id)) {
                $record->session_id = $record->class_session_id;
            } elseif (! empty($record->session_id) && empty($record->class_session_id)) {
                $record->class_session_id = $record->session_id;
            }

            if (! empty($record->note) && empty($record->remarks)) {
                $record->remarks = $record->note;
            } elseif (! empty($record->remarks) && empty($record->note)) {
                $record->note = $record->remarks;
            }

            if (empty($record->date) && ! empty($record->class_session_id)) {
                $session = ClassSession::withoutInstitutionScope()->find($record->class_session_id);
                if ($session) {
                    $record->date = $session->scheduled_date;
                }
            }

            if (empty($record->recorded_at)) {
                $record->recorded_at = now();
            }
        });
    }

    protected static function newFactory(): AttendanceRecordFactory
    {
        return AttendanceRecordFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
