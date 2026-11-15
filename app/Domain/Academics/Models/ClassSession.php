<?php

namespace App\Domain\Academics\Models;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Teachers\Models\Teacher;
use Carbon\Carbon;
use Database\Factories\ClassSessionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'class_sessions';

    protected $fillable = [
        'institution_id',
        'class_group_id',
        'teacher_id',
        'scheduled_date',
        'start_time_utc',
        'end_time_utc',
        'type',
        'meeting_link',
        'room',
        'status',
        'notes',
        'teacher_attendance_status',
        'teacher_checked_in_at',
        'teacher_check_in_notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'start_time_utc' => 'datetime',
            'end_time_utc' => 'datetime',
            'teacher_checked_in_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ClassSessionFactory
    {
        return ClassSessionFactory::new();
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class, 'class_group_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'class_session_id');
    }

    /**
     * Get start time converted to a specific timezone (or institution/viewer timezone).
     */
    public function startTimeForTimezone(?string $tz = null): Carbon
    {
        $timezone = $tz ?: ($this->institution->timezone ?? 'UTC');

        return $this->start_time_utc->copy()->setTimezone($timezone);
    }

    /**
     * Get end time converted to a specific timezone (or institution/viewer timezone).
     */
    public function endTimeForTimezone(?string $tz = null): Carbon
    {
        $timezone = $tz ?: ($this->institution->timezone ?? 'UTC');

        return $this->end_time_utc->copy()->setTimezone($timezone);
    }

    /**
     * Format session details with viewer timezone converted times.
     */
    public function toTimezoneArray(?string $tz = null): array
    {
        $start = $this->startTimeForTimezone($tz);
        $end = $this->endTimeForTimezone($tz);

        return array_merge($this->toArray(), [
            'viewer_timezone' => $tz ?: ($this->institution->timezone ?? 'UTC'),
            'local_date' => $start->format('Y-m-d'),
            'local_start_time' => $start->format('H:i'),
            'local_end_time' => $end->format('H:i'),
            'formatted_time_range' => "{$start->format('g:i A')} - {$end->format('g:i A')}",
        ]);
    }
}
