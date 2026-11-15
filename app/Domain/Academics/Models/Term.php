<?php

namespace App\Domain\Academics\Models;

use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Database\Factories\TermFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends Model
{
    use BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'terms';

    protected $fillable = [
        'institution_id',
        'academic_year_id',
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

    protected static function newFactory(): TermFactory
    {
        return TermFactory::new();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
