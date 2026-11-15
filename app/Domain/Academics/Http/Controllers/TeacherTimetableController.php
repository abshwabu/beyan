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

        // If user is admin/owner, allow viewing any teacher's today schedule via ?teacher_id=
        if (! $teacher && $request->filled('teacher_id') && ($user->isOwner() || $user->hasRole('Admin') || $user->isPlatformSuperAdmin())) {
            $teacher = Teacher::findOrFail($request->input('teacher_id'));
        }

        if (! $teacher) {
            abort(403, 'No teacher profile linked to your user account.');
        }

        $viewerTimezone = $request->input('timezone')
            ?: ($user->institution->timezone ?? 'UTC');

        $todayLocal = Carbon::now($viewerTimezone)->format('Y-m-d');

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
                'today' => $todayLocal,
                'timezone' => $viewerTimezone,
                'sessions' => $sessions,
            ]);
        }

        return Inertia::render('Timetable/Today', [
            'teacher' => $teacher,
            'today' => $todayLocal,
            'timezone' => $viewerTimezone,
            'sessions' => $sessions,
        ]);
    }
}
