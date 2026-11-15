<?php

namespace App\Domain\Hifz\Services;

use App\Domain\Hifz\Models\HifzEntry;
use App\Domain\Hifz\Models\QuranJuz;
use App\Domain\Students\Models\Student;

class HifzProgressService
{
    public function __construct(
        protected HifzTargetService $targetService
    ) {}

    /**
     * Build the comprehensive Qur'an progress profile for a student.
     *
     * DESIGN RULE: Do NOT reduce progress to one percentage.
     * Keep three distinct entry types:
     * - Sabaq (new memorization)
     * - Sabqi (recent revision)
     * - Manzil (older revision)
     *
     * @return array<string, mixed>
     */
    public function getStudentProfile(Student $student): array
    {
        // 1. Sabaq (New Memorization) Progress
        $sabaqEntries = HifzEntry::query()
            ->where('student_id', $student->id)
            ->where('type', 'sabaq')
            ->with('surah')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $latestSabaq = $sabaqEntries->first();
        $sabaqTotalAyahs = $sabaqEntries->sum(fn (HifzEntry $e) => $e->ayahs_count);

        // Completed surahs based on Sabaq reaching or surpassing total ayahs
        $completedSurahNumbers = $sabaqEntries
            ->filter(fn (HifzEntry $e) => $e->surah && $e->end_ayah >= $e->surah->total_ayahs)
            ->pluck('surah_number')
            ->unique()
            ->values()
            ->all();

        $sabaqData = [
            'type_label' => 'Sabaq (New Memorization)',
            'current_position' => $latestSabaq ? [
                'surah_number' => $latestSabaq->surah_number,
                'surah_name' => $latestSabaq->surah?->english_name,
                'surah_arabic' => $latestSabaq->surah?->arabic_name,
                'start_ayah' => $latestSabaq->start_ayah,
                'end_ayah' => $latestSabaq->end_ayah,
                'date' => $latestSabaq->date->toDateString(),
                'quality_rating' => $latestSabaq->quality_rating,
            ] : null,
            'total_ayahs_memorized' => $sabaqTotalAyahs,
            'completed_surahs_count' => count($completedSurahNumbers),
            'completed_surah_numbers' => $completedSurahNumbers,
            'recent_entries' => $sabaqEntries->take(5)->map(fn ($e) => $this->formatEntry($e)),
        ];

        // 2. Sabqi (Recent Revision) Progress
        $sabqiEntries = HifzEntry::query()
            ->where('student_id', $student->id)
            ->where('type', 'sabqi')
            ->with('surah')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $sabqiLast30Days = $sabqiEntries->filter(fn (HifzEntry $e) => $e->date >= now()->subDays(30));
        $sabqiData = [
            'type_label' => 'Sabqi (Recent Revision)',
            'total_revisions_count' => $sabqiEntries->count(),
            'total_ayahs_reviewed' => $sabqiEntries->sum(fn (HifzEntry $e) => $e->ayahs_count),
            'last_30_days_revisions' => $sabqiLast30Days->count(),
            'last_30_days_ayahs' => $sabqiLast30Days->sum(fn (HifzEntry $e) => $e->ayahs_count),
            'reviewed_surah_numbers' => $sabqiEntries->pluck('surah_number')->unique()->values()->all(),
            'recent_entries' => $sabqiEntries->take(5)->map(fn ($e) => $this->formatEntry($e)),
        ];

        // 3. Manzil (Older Revision) Progress
        $manzilEntries = HifzEntry::query()
            ->where('student_id', $student->id)
            ->where('type', 'manzil')
            ->with('surah')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        // Calculate juz coverage from manzil entries
        $manzilSurahs = $manzilEntries->pluck('surah_number')->unique()->all();
        $coveredJuzNumbers = QuranJuz::query()
            ->get()
            ->filter(function (QuranJuz $juz) use ($manzilSurahs) {
                return in_array($juz->start_surah_number, $manzilSurahs, true) || in_array($juz->end_surah_number, $manzilSurahs, true);
            })
            ->pluck('number')
            ->values()
            ->all();

        $manzilData = [
            'type_label' => 'Manzil (Older Revision)',
            'total_revisions_count' => $manzilEntries->count(),
            'total_ayahs_reviewed' => $manzilEntries->sum(fn (HifzEntry $e) => $e->ayahs_count),
            'covered_juz_numbers' => $coveredJuzNumbers,
            'recent_entries' => $manzilEntries->take(5)->map(fn ($e) => $this->formatEntry($e)),
        ];

        // 4. Targets: Achieved vs Target
        $targets = $this->targetService->getStudentTargetsWithProgress($student);

        // 5. Certifications & Ijazahs
        $certifications = $student->hifzCertifications()
            ->with('teacher.user')
            ->orderByDesc('completion_date')
            ->get();

        // 6. Complete History Timeline
        $timeline = HifzEntry::query()
            ->where('student_id', $student->id)
            ->with(['surah', 'teacher.user'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($e) => $this->formatEntry($e));

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'student_code' => $student->student_code,
                'status' => $student->status,
            ],
            // THREE DISTINCT PILLARS - NEVER MERGED INTO ONE PERCENTAGE
            'sabaq' => $sabaqData,
            'sabqi' => $sabqiData,
            'manzil' => $manzilData,
            'targets' => $targets,
            'certifications' => $certifications,
            'timeline' => $timeline,
        ];
    }

    /**
     * Compare student progress across two distinct time periods.
     *
     * @return array<string, mixed>
     */
    public function compareProgress(
        Student $student,
        string $startDateA,
        string $endDateA,
        string $startDateB,
        string $endDateB
    ): array {
        $metricsA = $this->calculatePeriodMetrics($student, $startDateA, $endDateA);
        $metricsB = $this->calculatePeriodMetrics($student, $startDateB, $endDateB);

        return [
            'period_a' => [
                'start_date' => $startDateA,
                'end_date' => $endDateA,
                'metrics' => $metricsA,
            ],
            'period_b' => [
                'start_date' => $startDateB,
                'end_date' => $endDateB,
                'metrics' => $metricsB,
            ],
            'comparison' => [
                'sabaq_ayahs_diff' => $metricsB['sabaq_ayahs'] - $metricsA['sabaq_ayahs'],
                'sabqi_ayahs_diff' => $metricsB['sabqi_ayahs'] - $metricsA['sabqi_ayahs'],
                'manzil_ayahs_diff' => $metricsB['manzil_ayahs'] - $metricsA['manzil_ayahs'],
                'total_mistakes_diff' => $metricsB['total_mistakes'] - $metricsA['total_mistakes'],
                'active_days_diff' => $metricsB['active_days'] - $metricsA['active_days'],
            ],
        ];
    }

    /**
     * Aggregate metrics for a specific date range.
     *
     * @return array<string, int|float>
     */
    protected function calculatePeriodMetrics(Student $student, string $startDate, string $endDate): array
    {
        $entries = HifzEntry::query()
            ->where('student_id', $student->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();

        $sabaq = $entries->where('type', 'sabaq');
        $sabqi = $entries->where('type', 'sabqi');
        $manzil = $entries->where('type', 'manzil');

        $activeDays = $entries->pluck('date')->unique()->count();
        $totalMistakes = (int) $entries->sum('mistakes_count');
        $entriesCount = $entries->count();

        return [
            'entries_count' => $entriesCount,
            'active_days' => $activeDays,
            'sabaq_ayahs' => (int) $sabaq->sum(fn (HifzEntry $e) => $e->ayahs_count),
            'sabaq_entries' => $sabaq->count(),
            'sabqi_ayahs' => (int) $sabqi->sum(fn (HifzEntry $e) => $e->ayahs_count),
            'sabqi_entries' => $sabqi->count(),
            'manzil_ayahs' => (int) $manzil->sum(fn (HifzEntry $e) => $e->ayahs_count),
            'manzil_entries' => $manzil->count(),
            'total_mistakes' => $totalMistakes,
            'avg_mistakes_per_entry' => $entriesCount > 0 ? round($totalMistakes / $entriesCount, 2) : 0.0,
        ];
    }

    /**
     * Format a single Hifz entry for display.
     *
     * @return array<string, mixed>
     */
    public function formatEntry(HifzEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'date' => $entry->date->toDateString(),
            'type' => $entry->type,
            'surah_number' => $entry->surah_number,
            'surah_name' => $entry->surah?->english_name,
            'surah_arabic' => $entry->surah?->arabic_name,
            'start_ayah' => $entry->start_ayah,
            'end_ayah' => $entry->end_ayah,
            'ayahs_count' => $entry->ayahs_count,
            'quality_rating' => $entry->quality_rating,
            'tajweed_rating' => $entry->tajweed_rating,
            'mistakes_count' => $entry->mistakes_count,
            'teacher_comment' => $entry->teacher_comment,
            'teacher_name' => $entry->teacher?->user?->name,
        ];
    }
}
