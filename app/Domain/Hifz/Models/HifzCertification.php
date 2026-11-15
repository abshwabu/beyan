<?php

namespace App\Domain\Hifz\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Database\Factories\HifzCertificationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HifzCertification extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'hifz_certifications';

    protected $fillable = [
        'institution_id',
        'student_id',
        'teacher_id',
        'type', // hifz_completion|juz_completion|ijazah|tajweed_certificate
        'title',
        'riwayah',
        'sanad_chain',
        'completion_date',
        'certificate_number',
        'grade',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
        ];
    }

    protected static function newFactory(): HifzCertificationFactory
    {
        return HifzCertificationFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
