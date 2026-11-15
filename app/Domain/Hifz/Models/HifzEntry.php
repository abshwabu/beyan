<?php

namespace App\Domain\Hifz\Models;

use App\Domain\Academics\Models\ClassSession;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Database\Factories\HifzEntryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HifzEntry extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'hifz_entries';

    protected $fillable = [
        'institution_id',
        'student_id',
        'teacher_id',
        'class_session_id',
        'date',
        'type', // sabaq|sabqi|manzil|reading
        'surah_number',
        'start_ayah',
        'end_ayah',
        'quality_rating',
        'tajweed_rating',
        'mistakes_count',
        'teacher_comment',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'surah_number' => 'integer',
            'start_ayah' => 'integer',
            'end_ayah' => 'integer',
            'mistakes_count' => 'integer',
        ];
    }

    protected static function newFactory(): HifzEntryFactory
    {
        return HifzEntryFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'surah_number', 'number');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    /**
     * Total number of ayahs completed in this entry.
     */
    public function getAyahsCountAttribute(): int
    {
        return max(0, $this->end_ayah - $this->start_ayah + 1);
    }
}
