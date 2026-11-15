<?php

namespace App\Domain\Attendance\Services;

use App\Domain\Students\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceAnalyticsService
{
    /**
     * Compute attendance percentages for an individual student using SQL aggregates.
     *
     * @return array{
     *     student_id: string,
     *     total_records: int,
     *     present_count: int,
     *     late_count: int,
     *     absent_count: int,
     *     excused_count: int,
     *     attendance_percentage: float,
     *     present_percentage: float
     * }
     */
    public function computeStudentPercentage(
        string $studentId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $query = DB::table('attendance_records')
            ->where('student_id', $studentId);

        if ($startDate) {
            $query->where('date', '>=', $startDate->toDateString());
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate->toDateString());
        }

        $stats = $query->selectRaw("
            COUNT(*) as total_records,
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
            COUNT(CASE WHEN status = 'late' THEN 1 END) as late_count,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
            COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused_count
        ")->first();

        $total = (int) ($stats->total_records ?? 0);
        $present = (int) ($stats->present_count ?? 0);
        $late = (int) ($stats->late_count ?? 0);
        $absent = (int) ($stats->absent_count ?? 0);
        $excused = (int) ($stats->excused_count ?? 0);

        $attended = $present + $late;
        $attendanceRate = $total > 0 ? round(($attended / $total) * 100, 2) : 0.0;
        $presentRate = $total > 0 ? round(($present / $total) * 100, 2) : 0.0;

        return [
            'student_id' => $studentId,
            'total_records' => $total,
            'present_count' => $present,
            'late_count' => $late,
            'absent_count' => $absent,
            'excused_count' => $excused,
            'attendance_percentage' => $attendanceRate,
            'present_percentage' => $presentRate,
        ];
    }

    /**
     * Compute attendance percentages for a class group using SQL aggregates.
     *
     * @return array{
     *     class_group_id: string,
     *     total_records: int,
     *     present_count: int,
     *     late_count: int,
     *     absent_count: int,
     *     excused_count: int,
     *     attendance_percentage: float,
     *     present_percentage: float
     * }
     */
    public function computeClassPercentage(
        string $classGroupId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $query = DB::table('attendance_records')
            ->join('class_sessions', 'class_sessions.id', '=', 'attendance_records.class_session_id')
            ->where('class_sessions.class_group_id', $classGroupId);

        if ($startDate) {
            $query->where('attendance_records.date', '>=', $startDate->toDateString());
        }

        if ($endDate) {
            $query->where('attendance_records.date', '<=', $endDate->toDateString());
        }

        $stats = $query->selectRaw("
            COUNT(*) as total_records,
            COUNT(CASE WHEN attendance_records.status = 'present' THEN 1 END) as present_count,
            COUNT(CASE WHEN attendance_records.status = 'late' THEN 1 END) as late_count,
            COUNT(CASE WHEN attendance_records.status = 'absent' THEN 1 END) as absent_count,
            COUNT(CASE WHEN attendance_records.status = 'excused' THEN 1 END) as excused_count
        ")->first();

        $total = (int) ($stats->total_records ?? 0);
        $present = (int) ($stats->present_count ?? 0);
        $late = (int) ($stats->late_count ?? 0);
        $absent = (int) ($stats->absent_count ?? 0);
        $excused = (int) ($stats->excused_count ?? 0);

        $attended = $present + $late;
        $attendanceRate = $total > 0 ? round(($attended / $total) * 100, 2) : 0.0;
        $presentRate = $total > 0 ? round(($present / $total) * 100, 2) : 0.0;

        return [
            'class_group_id' => $classGroupId,
            'total_records' => $total,
            'present_count' => $present,
            'late_count' => $late,
            'absent_count' => $absent,
            'excused_count' => $excused,
            'attendance_percentage' => $attendanceRate,
            'present_percentage' => $presentRate,
        ];
    }

    /**
     * Compute institutional attendance percentage for a date period using SQL aggregates.
     *
     * @return array{
     *     total_records: int,
     *     present_count: int,
     *     late_count: int,
     *     absent_count: int,
     *     excused_count: int,
     *     attendance_percentage: float
     * }
     */
    public function computePeriodPercentage(
        string $institutionId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $stats = DB::table('attendance_records')
            ->where('institution_id', $institutionId)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw("
                COUNT(*) as total_records,
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as late_count,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
                COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused_count
            ")->first();

        $total = (int) ($stats->total_records ?? 0);
        $present = (int) ($stats->present_count ?? 0);
        $late = (int) ($stats->late_count ?? 0);
        $absent = (int) ($stats->absent_count ?? 0);
        $excused = (int) ($stats->excused_count ?? 0);

        $attended = $present + $late;
        $rate = $total > 0 ? round(($attended / $total) * 100, 2) : 0.0;

        return [
            'total_records' => $total,
            'present_count' => $present,
            'late_count' => $late,
            'absent_count' => $absent,
            'excused_count' => $excused,
            'attendance_percentage' => $rate,
        ];
    }

    /**
     * Repeated-absence detection with configurable threshold.
     * Flags students with >= $threshold absences for proactive dashboard alert.
     *
     * @return Collection<int, array>
     */
    public function detectRepeatedAbsences(
        string $institutionId,
        int $threshold = 3,
        int $lookbackDays = 30
    ): Collection {
        $cutoffDate = Carbon::today()->subDays($lookbackDays)->toDateString();

        $flagged = DB::table('attendance_records')
            ->join('students', 'students.id', '=', 'attendance_records.student_id')
            ->where('attendance_records.institution_id', $institutionId)
            ->where('attendance_records.status', 'absent')
            ->where('attendance_records.date', '>=', $cutoffDate)
            ->select(
                'students.id as student_id',
                'students.full_name',
                'students.student_code',
                DB::raw('COUNT(attendance_records.id) as absence_count'),
                DB::raw('MAX(attendance_records.date) as last_absence_date')
            )
            ->groupBy('students.id', 'students.full_name', 'students.student_code')
            ->havingRaw('COUNT(attendance_records.id) >= ?', [$threshold])
            ->orderByDesc('absence_count')
            ->get();

        return $flagged->map(function ($item) {
            $student = Student::withoutInstitutionScope()->with('guardians')->find($item->student_id);
            $guardian = $student?->primaryGuardian();

            return [
                'student_id' => $item->student_id,
                'full_name' => $item->full_name,
                'student_code' => $item->student_code,
                'absence_count' => (int) $item->absence_count,
                'last_absence_date' => $item->last_absence_date,
                'guardian_name' => $guardian?->full_name,
                'guardian_phone' => $guardian?->phone,
                'guardian_email' => $guardian?->email,
            ];
        });
    }
}
