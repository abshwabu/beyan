<?php

namespace App\Domain\Teachers\Models;

use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Auth\Models\User;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'teachers';

    protected $fillable = [
        'institution_id',
        'user_id',
        'full_name',
        'email',
        'phone',
        'qualifications',
        'specialization',
        'status',
        'availability_slots',
        'bio',
    ];

    protected function casts(): array
    {
        return [
            'qualifications' => 'array',
            'availability_slots' => 'array',
        ];
    }

    protected static function newFactory(): TeacherFactory
    {
        return TeacherFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function classGroups(): BelongsToMany
    {
        return $this->belongsToMany(ClassGroup::class, 'class_group_teacher', 'teacher_id', 'class_group_id')
            ->withPivot(['is_primary', 'institution_id'])
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'teacher_id');
    }
}
