<?php

namespace App\Domain\Hifz\Services;

use App\Domain\Hifz\Models\HifzEntry;
use App\Domain\Hifz\Models\HifzTarget;
use App\Domain\Students\Models\Student;
use Carbon\Carbon;

class HifzTargetService
{
    /**
     * Get active targets for a student with computed achieved vs target metrics.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStudentTargetsWithProgress(Student $student, ?Carbon $date = null): array
    {
        $currentDate = $date ?? Carbon::today();
        $startOfWeek = $currentDate->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $currentDate->copy()->endOfWeek(Carbon::SUNDAY);

        $targets = HifzTarget::query()
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->get();

        $results = [];

        foreach ($targets as $target) {
            $achieved = $this->calculateAchieved($student, $target, $currentDate, $startOfWeek, $endOfWeek);
            $targetVal = max(1, $target->target_value);
            $percentage = min(100, (int) round(($achieved / $targetVal) * 100));

            $results[] = [
                'id' => $target->id,
                'period' => $target->period,
                'target_type' => $target->target_type,
                'target_value' => $target->target_value,
                'achieved_value' => $achieved,
                'progress_percentage' => $percentage,
                'is_completed' => $achieved >= $target->target_value,
                'notes' => $target->notes,
            ];
        }

        return $results;
    }

    /**
     * Calculate achieved value for a specific target.
     */
    protected function calculateAchieved(
        Student $student,
        HifzTarget $target,
        Carbon $currentDate,
        Carbon $startOfWeek,
        Carbon $endOfWeek
    ): int {
        $query = HifzEntry::query()->where('student_id', $student->id);

        if ($target->period === 'daily') {
            $query->whereDate('date', $currentDate->toDateString());
        } else {
            $query->whereDate('date', '>=', $startOfWeek->toDateString())
                ->whereDate('date', '<=', $endOfWeek->toDateString());
        }

        // Map target types to entry types
        return match ($target->target_type) {
            'sabaq_ayahs' => (int) $query->where('type', 'sabaq')->get()->sum(fn (HifzEntry $e) => $e->ayahs_count),
            'sabqi_ayahs' => (int) $query->where('type', 'sabqi')->get()->sum(fn (HifzEntry $e) => $e->ayahs_count),
            'manzil_ayahs' => (int) $query->where('type', 'manzil')->get()->sum(fn (HifzEntry $e) => $e->ayahs_count),
            'reading_ayahs' => (int) $query->where('type', 'reading')->get()->sum(fn (HifzEntry $e) => $e->ayahs_count),
            // For page-based targets, 15 ayahs approximately equals 1 standard Madani mushaf page
            'sabaq_pages' => (int) round($query->where('type', 'sabaq')->get()->sum(fn (HifzEntry $e) => $e->ayahs_count) / 15),
            'sabqi_pages' => (int) round($query->where('type', 'sabqi')->get()->sum(fn (HifzEntry $e) => $e->ayahs_count) / 15),
            'manzil_pages' => (int) round($query->where('type', 'manzil')->get()->sum(fn (HifzEntry $e) => $e->ayahs_count) / 15),
            default => (int) $query->get()->sum(fn (HifzEntry $e) => $e->ayahs_count),
        };
    }

    /**
     * Set or update a target for a student.
     *
     * @param  array<string, mixed>  $data
     */
    public function setTarget(Student $student, array $data): HifzTarget
    {
        return HifzTarget::updateOrCreate(
            [
                'institution_id' => $student->institution_id,
                'student_id' => $student->id,
                'period' => $data['period'],
                'target_type' => $data['target_type'],
            ],
            [
                'target_value' => (int) $data['target_value'],
                'notes' => $data['notes'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]
        );
    }
}
