<?php

namespace App\Domain\Hifz\Actions;

use App\Domain\Hifz\Models\HifzEntry;
use App\Domain\Hifz\Models\QuranSurah;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordHifzEntryAction
{
    /**
     * Record a new Hifz entry with strict Quranic boundaries validation.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): HifzEntry
    {
        $surahNumber = (int) ($data['surah_number'] ?? 0);
        $startAyah = (int) ($data['start_ayah'] ?? 0);
        $endAyah = (int) ($data['end_ayah'] ?? 0);

        /** @var QuranSurah|null $surah */
        $surah = QuranSurah::query()->find($surahNumber);

        if (! $surah) {
            throw ValidationException::withMessages([
                'surah_number' => ["Surah number {$surahNumber} is invalid."],
            ]);
        }

        if ($startAyah < 1) {
            throw ValidationException::withMessages([
                'start_ayah' => ['The start ayah must be at least 1.'],
            ]);
        }

        if ($startAyah > $endAyah) {
            throw ValidationException::withMessages([
                'start_ayah' => ['The start ayah cannot be greater than the end ayah.'],
                'end_ayah' => ['The end ayah must be greater than or equal to the start ayah.'],
            ]);
        }

        if ($endAyah > $surah->total_ayahs) {
            throw ValidationException::withMessages([
                'end_ayah' => ["The end ayah ({$endAyah}) exceeds the total ayahs ({$surah->total_ayahs}) in Surah {$surah->english_name}."],
            ]);
        }

        // Validate student exists
        $student = Student::query()->findOrFail($data['student_id']);

        // Validate teacher exists
        $teacher = Teacher::query()->findOrFail($data['teacher_id']);

        return DB::transaction(function () use ($data, $student, $teacher, $surah) {
            return HifzEntry::create([
                'institution_id' => $student->institution_id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'class_session_id' => $data['class_session_id'] ?? null,
                'date' => $data['date'] ?? now()->toDateString(),
                'type' => $data['type'],
                'surah_number' => $surah->number,
                'start_ayah' => (int) $data['start_ayah'],
                'end_ayah' => (int) $data['end_ayah'],
                'quality_rating' => $data['quality_rating'] ?? 'good',
                'tajweed_rating' => $data['tajweed_rating'] ?? 'good',
                'mistakes_count' => (int) ($data['mistakes_count'] ?? 0),
                'teacher_comment' => $data['teacher_comment'] ?? null,
            ]);
        });
    }
}
