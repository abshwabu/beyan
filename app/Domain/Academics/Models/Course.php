<?php

namespace App\Domain\Academics\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'courses';

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'level',
        'duration',
        'learning_objectives',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'learning_objectives' => 'array',
        ];
    }

    protected static function newFactory(): CourseFactory
    {
        return CourseFactory::new();
    }

    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class, 'course_id');
    }
}
