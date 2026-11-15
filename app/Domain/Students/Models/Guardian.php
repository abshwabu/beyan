<?php

namespace App\Domain\Students\Models;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'guardians';

    protected $fillable = [
        'institution_id',
        'user_id',
        'full_name',
        'phone',
        'email',
        'address',
        'occupation',
    ];

    protected static function newFactory(): GuardianFactory
    {
        return GuardianFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student', 'guardian_id', 'student_id')
            ->withPivot(['relationship_type', 'is_primary', 'institution_id'])
            ->withTimestamps();
    }
}
