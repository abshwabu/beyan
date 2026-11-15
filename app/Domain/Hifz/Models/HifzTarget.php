<?php

namespace App\Domain\Hifz\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Models\Student;
use Database\Factories\HifzTargetFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HifzTarget extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'hifz_targets';

    protected $fillable = [
        'institution_id',
        'student_id',
        'period', // daily|weekly
        'target_type', // sabaq_ayahs|sabaq_pages|sabqi_pages|manzil_pages
        'target_value',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): HifzTargetFactory
    {
        return HifzTargetFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
