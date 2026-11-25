<?php

namespace App\Domain\Reports\Services;

use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Finance\Models\Payment;
use App\Domain\Hifz\Models\HifzEntry;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;

class ReportGeneratorService
{
    /**
     * 1. Student List Report (Read-only query).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generateStudentListReport(Institution $institution, array $filters): array
    {
        $query = Student::query()
            ->where('institution_id', $institution->id)
            ->with(['currentEnrollment', 'classGroups', 'guardians'])
            ->orderBy('full_name');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (! empty($filters['class_group_id'])) {
            $query->whereHas('classGroups', function ($q) use ($filters) {
                $q->where('class_groups.id', $filters['class_group_id']);
            });
        }

        $rows = $query->get()->map(fn (Student $s) => [
            'student_code' => $s->student_code ?: $s->admission_number,
            'full_name' => $s->full_name,
            'gender' => ucfirst($s->gender ?: 'N/A'),
            'date_of_birth' => $s->date_of_birth?->toDateString() ?: 'N/A',
            'admission_date' => $s->admission_date?->toDateString() ?: 'N/A',
            'class_group' => $s->classGroups->first()?->name ?: ($s->currentEnrollment?->class_name ?: 'Unassigned'),
            'guardian_contact' => $s->primaryGuardian()?->phone ?: 'N/A',
            'status' => ucfirst($s->status),
        ])->all();

        return [
            'title' => 'Student Directory Report',
            'headers' => ['Student ID', 'Full Name', 'Gender', 'Date of Birth', 'Admission Date', 'Class Group', 'Guardian Phone', 'Status'],
            'rows' => $rows,
            'count' => count($rows),
        ];
    }

    /**
     * 2. Teacher List Report (Read-only query).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generateTeacherListReport(Institution $institution, array $filters): array
    {
        $query = Teacher::query()
            ->where('institution_id', $institution->id)
            ->with(['user', 'classGroups'])
            ->orderBy('full_name');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $rows = $query->get()->map(fn (Teacher $t) => [
            'name' => $t->full_name ?: $t->user?->name,
            'email' => $t->email ?: $t->user?->email,
            'phone' => $t->phone ?: 'N/A',
            'specialization' => $t->specialization ?: 'Qur\'an & Islamic Studies',
            'assigned_classes_count' => $t->classGroups->count(),
            'status' => ucfirst($t->status),
        ])->all();

        return [
            'title' => 'Teacher Roster Report',
            'headers' => ['Teacher Name', 'Email', 'Phone', 'Specialization', 'Assigned Classes', 'Status'],
            'rows' => $rows,
            'count' => count($rows),
        ];
    }

    /**
     * 3. Attendance Report (Read-only query).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generateAttendanceReport(Institution $institution, array $filters): array
    {
        $startDate = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['date_to'] ?? now()->toDateString();

        $query = AttendanceRecord::query()
            ->where('attendance_records.institution_id', $institution->id)
            ->whereDate('attendance_records.date', '>=', $startDate)
            ->whereDate('attendance_records.date', '<=', $endDate)
            ->with(['student']);

        if (! empty($filters['class_session_id'])) {
            $query->where('class_session_id', $filters['class_session_id']);
        }

        $records = $query->get();

        // Group by student
        $studentsData = $records->groupBy('student_id')->map(function ($studentRecords) {
            $student = $studentRecords->first()?->student;
            $total = $studentRecords->count();
            $present = $studentRecords->where('status', 'present')->count();
            $absent = $studentRecords->where('status', 'absent')->count();
            $late = $studentRecords->where('status', 'late')->count();
            $excused = $studentRecords->where('status', 'excused')->count();
            $pct = $total > 0 ? (int) round(($present / $total) * 100) : 0;

            return [
                'student_code' => $student?->student_code ?: 'N/A',
                'student_name' => $student?->full_name ?: 'Unknown',
                'total_sessions' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'excused' => $excused,
                'percentage' => "{$pct}%",
            ];
        })->values()->all();

        return [
            'title' => 'Attendance Performance Report',
            'period' => "{$startDate} to {$endDate}",
            'headers' => ['Student ID', 'Student Name', 'Total Sessions', 'Present', 'Absent', 'Late', 'Excused', 'Attendance Rate'],
            'rows' => $studentsData,
            'count' => count($studentsData),
        ];
    }

    /**
     * 4. Hifz Progress Report (Read-only query).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generateHifzProgressReport(Institution $institution, array $filters): array
    {
        $startDate = $filters['date_from'] ?? now()->subDays(30)->toDateString();
        $endDate = $filters['date_to'] ?? now()->toDateString();

        $query = HifzEntry::query()
            ->where('institution_id', $institution->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->with(['student', 'surah', 'teacher.user'])
            ->orderByDesc('date');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        $entries = $query->get()->map(fn (HifzEntry $e) => [
            'date' => $e->date->toDateString(),
            'student_name' => $e->student?->full_name,
            'type' => ucfirst($e->type),
            'surah' => $e->surah?->english_name ?: "Surah {$e->surah_number}",
            'ayah_range' => "{$e->start_ayah} - {$e->end_ayah}",
            'ayahs_count' => $e->ayahs_count,
            'quality' => ucfirst($e->quality_rating),
            'mistakes' => $e->mistakes_count,
            'teacher' => $e->teacher?->user?->name ?: $e->teacher?->full_name ?: 'N/A',
        ])->all();

        return [
            'title' => 'Qur\'an & Hifz Progress Report',
            'period' => "{$startDate} to {$endDate}",
            'headers' => ['Date', 'Student Name', 'Category', 'Surah', 'Ayah Range', 'Ayahs Count', 'Quality', 'Mistakes', 'Teacher'],
            'rows' => $entries,
            'count' => count($entries),
        ];
    }

    /**
     * 5. Payments Ledger Report (Read-only query).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generatePaymentsReport(Institution $institution, array $filters): array
    {
        $startDate = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['date_to'] ?? now()->toDateString();

        $query = Payment::query()
            ->where('institution_id', $institution->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->with(['invoice.student', 'recorder'])
            ->orderByDesc('date');

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $payments = $query->get()->map(fn (Payment $p) => [
            'payment_number' => $p->payment_number,
            'date' => $p->date->toDateString(),
            'student_name' => $p->invoice?->student?->full_name ?: 'N/A',
            'invoice_number' => $p->invoice?->invoice_number ?: 'N/A',
            'type' => ucfirst($p->type),
            'payment_method' => ucwords(str_replace('_', ' ', $p->payment_method)),
            'reference_number' => $p->reference_number ?: 'N/A',
            'amount' => number_format($p->amount / 100, 2).' '.$institution->currency,
            'recorded_by' => $p->recorder?->name ?: 'Staff',
        ])->all();

        return [
            'title' => 'Tuition Payments Report',
            'period' => "{$startDate} to {$endDate}",
            'headers' => ['Receipt #', 'Date', 'Student Name', 'Invoice #', 'Type', 'Payment Method', 'Reference #', 'Amount', 'Recorded By'],
            'rows' => $payments,
            'count' => count($payments),
        ];
    }

    /**
     * 6. Class Group Report (Read-only query).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generateClassReport(Institution $institution, array $filters): array
    {
        $query = ClassGroup::query()
            ->where('institution_id', $institution->id)
            ->with(['course', 'teachers.user', 'activeEnrollments', 'term'])
            ->orderBy('name');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $classes = $query->get()->map(fn (ClassGroup $c) => [
            'class_name' => $c->name,
            'course' => $c->course?->name ?: 'General',
            'type' => ucfirst($c->type),
            'teachers' => $c->teachers->map(fn ($t) => $t->user?->name ?: $t->full_name)->implode(', ') ?: 'Unassigned',
            'term' => $c->term?->name ?: 'All Terms',
            'enrolled_students' => $c->activeEnrollments->count(),
            'capacity' => $c->capacity ?: 'Unlimited',
            'status' => ucfirst($c->status),
        ])->all();

        return [
            'title' => 'Class Groups & Timetable Report',
            'headers' => ['Class Name', 'Course', 'Type', 'Teachers', 'Term', 'Enrolled Students', 'Capacity', 'Status'],
            'rows' => $classes,
            'count' => count($classes),
        ];
    }
}
