<?php

namespace App\Domain\Institution\Services;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Term;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Attendance\Services\AttendanceAnalyticsService;
use App\Domain\Auth\Models\User;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Hifz\Models\HifzEntry;
use App\Domain\Institution\Audit\Models\AuditLog;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardMetricsService
{
    public function __construct(
        protected AttendanceAnalyticsService $attendanceAnalytics
    ) {}

    /**
     * Get aggregate dashboard metrics for Admin / Owner / Academic Coordinator.
     * Caches heavy aggregate queries for 60 seconds.
     *
     * @return array<string, mixed>
     */
    public function getAdminDashboardMetrics(Institution $institution): array
    {
        $cacheKey = "dash_admin_metrics_{$institution->id}";

        return Cache::remember($cacheKey, 60, function () use ($institution) {
            $today = Carbon::today()->toDateString();

            // 1. Core Entity Counts
            $activeStudentsCount = Student::where('institution_id', $institution->id)
                ->where('status', 'active')
                ->count();

            $activeTeachersCount = Teacher::where('institution_id', $institution->id)
                ->where('status', 'active')
                ->count();

            $activeClassesCount = ClassGroup::where('institution_id', $institution->id)
                ->where('status', 'active')
                ->count();

            // 2. Today's Attendance Metrics
            $todaySessionsCount = ClassSession::where('institution_id', $institution->id)
                ->whereDate('scheduled_date', $today)
                ->count();

            $todayMarkedSessionsCount = ClassSession::where('institution_id', $institution->id)
                ->whereDate('scheduled_date', $today)
                ->where(function ($q) {
                    $q->whereNotNull('teacher_checked_in_at')
                        ->orWhereHas('attendanceRecords');
                })
                ->count();

            $todayAttendanceRecords = AttendanceRecord::where('institution_id', $institution->id)
                ->whereDate('date', $today)
                ->get(['status']);

            $todayPresentCount = $todayAttendanceRecords->where('status', 'present')->count();
            $todayTotalRecords = $todayAttendanceRecords->count();
            $todayAttendancePct = $todayTotalRecords > 0
                ? (int) round(($todayPresentCount / $todayTotalRecords) * 100)
                : 100;

            // 3. Finance & Pending/Overdue Fees
            $unpaidInvoices = FeeInvoice::where('institution_id', $institution->id)
                ->whereIn('status', ['unpaid', 'partial'])
                ->get(['balance_due', 'due_date']);

            $pendingFeesTotal = (int) $unpaidInvoices->sum('balance_due');
            $overdueInvoicesCount = $unpaidInvoices->filter(fn ($inv) => $inv->due_date && $inv->due_date->isPast())->count();
            $overdueFeesTotal = (int) $unpaidInvoices
                ->filter(fn ($inv) => $inv->due_date && $inv->due_date->isPast())
                ->sum('balance_due');

            // 4. Upcoming sessions today (Eager-loaded to eliminate N+1)
            $upcomingSessions = ClassSession::where('institution_id', $institution->id)
                ->whereDate('scheduled_date', $today)
                ->with(['classGroup.course', 'teacher.user'])
                ->withExists('attendanceRecords')
                ->orderBy('start_time_utc')
                ->limit(6)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'class_name' => $s->classGroup?->name,
                    'course_name' => $s->classGroup?->course?->name,
                    'teacher_name' => $s->teacher?->user?->name ?: $s->teacher?->full_name,
                    'start_time' => $s->start_time_utc,
                    'end_time' => $s->end_time_utc,
                    'status' => $s->status,
                    'attendance_marked' => ! empty($s->teacher_checked_in_at) || $s->attendance_records_exists,
                ]);

            // 5. Recent Activity (Audit Log)
            $recentActivity = AuditLog::where('institution_id', $institution->id)
                ->with('user')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'user_name' => $log->user?->name ?: 'System',
                    'action' => $log->action,
                    'auditable_type' => class_basename($log->auditable_type),
                    'created_at' => $log->created_at->diffForHumans(),
                ]);

            // Academic Year and Term Context
            $currentYear = AcademicYear::where('institution_id', $institution->id)->where('is_current', true)->first();
            $currentTerm = Term::where('institution_id', $institution->id)->where('is_current', true)->first();

            return [
                'active_students_count' => $activeStudentsCount,
                'active_teachers_count' => $activeTeachersCount,
                'active_classes_count' => $activeClassesCount,
                'today_attendance' => [
                    'percentage' => $todayAttendancePct,
                    'total_records' => $todayTotalRecords,
                    'present_count' => $todayPresentCount,
                    'sessions_count' => $todaySessionsCount,
                    'marked_sessions_count' => $todayMarkedSessionsCount,
                ],
                'fees' => [
                    'pending_total' => $pendingFeesTotal,
                    'overdue_count' => $overdueInvoicesCount,
                    'overdue_total' => $overdueFeesTotal,
                    'currency' => $institution->currency ?: 'ETB',
                ],
                'upcoming_sessions' => $upcomingSessions,
                'recent_activity' => $recentActivity,
                'current_year' => $currentYear ? ['id' => $currentYear->id, 'name' => $currentYear->name] : null,
                'current_term' => $currentTerm ? ['id' => $currentTerm->id, 'name' => $currentTerm->name] : null,
            ];
        });
    }

    /**
     * Get tailored dashboard metrics for a Teacher.
     * Caches query results for 60 seconds per teacher.
     *
     * @return array<string, mixed>
     */
    public function getTeacherDashboardMetrics(Institution $institution, User $teacherUser): array
    {
        $cacheKey = "dash_teacher_metrics_{$institution->id}_{$teacherUser->id}";

        return Cache::remember($cacheKey, 60, function () use ($institution, $teacherUser) {
            $today = Carbon::today()->toDateString();
            $teacherProfile = $teacherUser->teacherProfile ?? Teacher::where('user_id', $teacherUser->id)->first();

            if (! $teacherProfile) {
                return [
                    'today_sessions' => [],
                    'students_needing_attention' => [],
                ];
            }

            // 1. Teacher's sessions today
            $todaySessions = ClassSession::where('institution_id', $institution->id)
                ->where('teacher_id', $teacherProfile->id)
                ->whereDate('scheduled_date', $today)
                ->with(['classGroup.course', 'classGroup.activeEnrollments.student'])
                ->withExists('attendanceRecords')
                ->orderBy('start_time_utc')
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'class_name' => $s->classGroup?->name,
                    'course_name' => $s->classGroup?->course?->name,
                    'start_time' => $s->start_time_utc,
                    'end_time' => $s->end_time_utc,
                    'attendance_marked' => ! empty($s->teacher_checked_in_at) || $s->attendance_records_exists,
                    'enrolled_count' => $s->classGroup?->activeEnrollments?->count() ?? 0,
                ]);

            // 2. Students assigned to this teacher who need attention
            $assignedStudentIds = $teacherUser->assignedStudents()->pluck('students.id');

            // Find students with repeated absences (>= 3 absences in the last 30 days)
            $absentStudentIds = AttendanceRecord::where('institution_id', $institution->id)
                ->whereIn('student_id', $assignedStudentIds)
                ->where('status', 'absent')
                ->whereDate('date', '>=', now()->subDays(30)->toDateString())
                ->groupBy('student_id')
                ->havingRaw('COUNT(*) >= 3')
                ->pluck('student_id');

            // Find students who haven't logged Sabaq in over 7 days
            $recentSabaqStudentIds = HifzEntry::where('institution_id', $institution->id)
                ->whereIn('student_id', $assignedStudentIds)
                ->where('type', 'sabaq')
                ->whereDate('date', '>=', now()->subDays(7)->toDateString())
                ->pluck('student_id')
                ->unique();

            $attentionStudentIds = $assignedStudentIds->filter(function ($id) use ($absentStudentIds, $recentSabaqStudentIds) {
                return $absentStudentIds->contains($id) || ! $recentSabaqStudentIds->contains($id);
            })->take(10);

            $studentsNeedingAttention = Student::whereIn('id', $attentionStudentIds)
                ->get(['id', 'full_name', 'student_code'])
                ->map(function ($student) use ($absentStudentIds, $recentSabaqStudentIds) {
                    $reasons = [];
                    if ($absentStudentIds->contains($student->id)) {
                        $reasons[] = 'Repeated Absences (≥3 in 30 days)';
                    }
                    if (! $recentSabaqStudentIds->contains($student->id)) {
                        $reasons[] = 'No Sabaq recorded this week';
                    }

                    return [
                        'id' => $student->id,
                        'name' => $student->full_name,
                        'student_code' => $student->student_code,
                        'reasons' => $reasons,
                    ];
                });

            return [
                'today_sessions' => $todaySessions,
                'students_needing_attention' => $studentsNeedingAttention,
            ];
        });
    }
}
