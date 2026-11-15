<?php

namespace App\Domain\Academics\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Teachers\Models\Teacher;
use Database\Factories\ClassScheduleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSchedule extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'class_schedules';

    protected $fillable = [
        'institution_id',
        'class_group_id',
        'teacher_id',
        'weekday',
        'start_time',
        'end_time',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }

    protected static function newFactory(): ClassScheduleFactory
    {
        return ClassScheduleFactory::new();
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class, 'class_group_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
