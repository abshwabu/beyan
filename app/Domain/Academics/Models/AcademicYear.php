<?php

namespace App\Domain\Academics\Models;

use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'academic_years';

    protected $fillable = [
        'institution_id',
        'name',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    protected static function newFactory(): AcademicYearFactory
    {
        return AcademicYearFactory::new();
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class, 'academic_year_id');
    }
}
