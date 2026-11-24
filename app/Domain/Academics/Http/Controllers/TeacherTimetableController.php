<?php

namespace App\Domain\Academics\Http\Controllers;

use App\Domain\Academics\Models\ClassSession;
use App\Domain\Teachers\Models\Teacher;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherTimetableController extends Controller
{
    /**
     * Display the teacher's "Today" schedule with one tap into attendance.
     */
    public function today(Request $request): Response|JsonResponse
    {
        $user = auth()->user();

        // Resolve teacher record
        $teacher = Teacher::where('user_id', $user->id)->first();

        $isStaff = $user->isOwner()
            || $user->hasRole('Admin')
            || $user->hasRole('Owner')
            || $user->isPlatformSuperAdmin()
            || $user->isPlatformStaff();

        $teachers = [];
        if ($isStaff) {
            $teachers = Teacher::where('status', 'active')
                ->orderBy('full_name')
                ->get();

            if (! $teacher) {
                if ($request->filled('teacher_id')) {
                    $teacher = Teacher::find($request->input('teacher_id'));
                } else {
                    // Default to first active teacher in the institution so admins can view schedule immediately
                    $teacher = $teachers->first();
                }
            }
        }

        $viewerTimezone = $request->input('timezone')
            ?: ($user->institution->timezone ?? 'UTC');

        $todayLocal = Carbon::now($viewerTimezone)->format('Y-m-d');

        if (! $teacher) {
            if ($isStaff) {
                // If the institution has no teachers yet, show empty state gracefully instead of 403
                if ($request->wantsJson()) {
                    return response()->json([
                        'teacher' => null,
                        'teachers' => $teachers,
                        'today' => $todayLocal,
                        'timezone' => $viewerTimezone,
                        'sessions' => [],
                    ]);
                }

                return Inertia::render('Timetable/Today', [
                    'teacher' => null,
                    'teachers' => $teachers,
                    'today' => $todayLocal,
                    'timezone' => $viewerTimezone,
                    'sessions' => [],
                ]);
            }

            abort(403, 'No teacher profile linked to your user account.');
        }

        $sessions = ClassSession::with(['classGroup.course', 'classGroup.activeEnrollments'])
            ->where('teacher_id', $teacher->id)
            ->whereDate('scheduled_date', $todayLocal)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time_utc', 'asc')
            ->get()
            ->map(function (ClassSession $s) use ($viewerTimezone) {
                $arr = $s->toTimezoneArray($viewerTimezone);
                $arr['course_name'] = $s->classGroup?->course?->name ?? 'Course';
                $arr['class_name'] = $s->classGroup?->name ?? 'Class Group';
                $arr['enrolled_count'] = $s->classGroup?->activeEnrollments?->count() ?? 0;
                $arr['attendance_url'] = route('attendance.index', ['session_id' => $s->id]);

                return $arr;
            });

        if ($request->wantsJson()) {
            return response()->json([
                'teacher' => $teacher,
                'teachers' => $teachers,
                'today' => $todayLocal,
                'timezone' => $viewerTimezone,
                'sessions' => $sessions,
            ]);
        }

        return Inertia::render('Timetable/Today', [
            'teacher' => $teacher,
            'teachers' => $teachers,
            'today' => $todayLocal,
            'timezone' => $viewerTimezone,
            'sessions' => $sessions,
        ]);
    }
}
