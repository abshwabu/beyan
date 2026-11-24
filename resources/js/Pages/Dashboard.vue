<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
  institution: Object,
  is_teacher_only: Boolean,
  admin_metrics: Object,
  teacher_metrics: Object,
  academicYears: Array,
  currentYear: Object,
  currentTerm: Object,
  stats: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const formatCurrency = (minorUnits) => {
  if (!minorUnits) return '0.00 ETB';
  return (minorUnits / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + (props.institution?.currency || 'ETB');
};
</script>

<template>
  <div class="min-h-screen bg-slate-100" dir="ltr">
    <!-- Impersonation Banner -->
    <div v-if="$page.props.impersonation?.active" class="bg-amber-400 text-slate-900 px-4 py-2.5 font-bold text-xs sm:text-sm shadow-md flex flex-wrap items-center justify-between gap-2 border-b border-amber-500">
      <div class="flex items-center gap-2">
        <span class="px-2 py-0.5 bg-slate-900 text-white rounded text-[11px] uppercase tracking-wider font-extrabold">Support Session</span>
        <span>You are viewing <strong>{{ $page.props.impersonation.institution_name }}</strong>. Reason: <em>"{{ $page.props.impersonation.reason }}"</em>. (Auto-expires in ~{{ $page.props.impersonation.minutes_remaining }} min)</span>
      </div>
      <Link href="/platform/impersonate/stop" method="post" as="button" class="px-3 py-1 bg-slate-900 text-white rounded-md text-xs hover:bg-slate-800 font-bold transition">
        Leave Support Impersonation &rarr;
      </Link>
    </div>

    <!-- Platform Announcement Banner -->
    <div v-if="$page.props.announcement" class="bg-indigo-700 text-white px-4 py-2 text-xs sm:text-sm shadow-sm flex items-center justify-between gap-4 border-b border-indigo-800">
      <div class="flex items-center gap-2 truncate">
        <span class="font-extrabold uppercase tracking-wider bg-white/20 px-2 py-0.5 rounded text-[10px]">Announcement</span>
        <span class="truncate"><strong>{{ $page.props.announcement.title }}:</strong> {{ $page.props.announcement.body }}</span>
      </div>
      <Link :href="`/announcements/${$page.props.announcement.id}/dismiss`" method="post" as="button" class="text-white/80 hover:text-white font-black text-sm ms-2 flex-shrink-0" title="Dismiss announcement">
        ✕
      </Link>
    </div>

    <!-- Top Navigation Header -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center gap-3">
            <span class="p-2 bg-emerald-600 rounded-lg text-white font-bold text-lg">BM</span>
            <div>
              <span class="text-base sm:text-lg font-bold text-slate-800">{{ institution?.name || 'Madrasa Workspace' }}</span>
              <span class="ms-2 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-emerald-100 text-emerald-800 uppercase">
                {{ institution?.mode || 'hybrid' }}
              </span>
            </div>
          </div>

          <!-- Main Nav Links -->
          <div class="hidden md:flex items-center space-x-4 text-xs font-bold text-slate-600">
            <Link :href="route('students.index')" class="hover:text-emerald-700 transition">Students</Link>
            <Link :href="route('timetable.today')" class="hover:text-emerald-700 transition">Timetable</Link>
            <Link :href="route('attendance.index')" class="hover:text-emerald-700 transition">Attendance</Link>
            <Link :href="route('hifz.index')" class="hover:text-emerald-700 transition">Qur'an & Hifz</Link>
            <Link v-if="!is_teacher_only" :href="route('finance.invoices.index')" class="hover:text-emerald-700 transition">Finance</Link>
            <Link v-if="!is_teacher_only" :href="route('reports.index')" class="hover:text-emerald-700 transition">Reports</Link>
          </div>

          <div class="flex items-center gap-3">
            <div class="text-end hidden sm:block">
              <div class="text-xs font-bold text-slate-900">{{ user?.name }}</div>
              <div class="text-[10px] text-slate-500 uppercase">{{ user?.role }}</div>
            </div>
            <Link
              href="/logout"
              method="post"
              as="button"
              class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-md transition"
            >
              Logout
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Workspace Content -->
    <main class="max-w-7xl mx-auto py-6 px-3 sm:px-6 lg:px-8 space-y-6">
      <!-- Flash Notification -->
      <div v-if="page.props.flash?.success" class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium">
        ✓ {{ page.props.flash.success }}
      </div>

      <!-- TEACHER VIEW (If teacher or teacher-only) -->
      <div v-if="teacher_metrics" class="space-y-6">
        <div class="bg-gradient-to-r from-emerald-800 to-emerald-700 rounded-2xl p-6 text-white shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-black">As-salamu alaykum, {{ user?.name }}</h1>
            <p class="text-xs text-emerald-100 mt-1">Today's assigned teaching sessions and student memorization logs</p>
          </div>

          <div class="flex gap-2">
            <Link
              :href="route('hifz.fast-entry')"
              class="px-4 py-2 bg-white text-emerald-900 font-bold text-xs rounded-xl shadow hover:bg-emerald-50 transition"
            >
              📖 Fast Qur'an Entry
            </Link>
            <Link
              :href="route('timetable.today')"
              class="px-4 py-2 bg-emerald-900 text-white font-bold text-xs rounded-xl shadow hover:bg-emerald-950 transition border border-emerald-600"
            >
              📅 Today's Schedule
            </Link>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Today's Sessions -->
          <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="flex justify-between items-center">
              <h2 class="text-base font-bold text-slate-900">Today's Assigned Sessions</h2>
              <span class="text-xs text-slate-500 font-medium">{{ teacher_metrics.today_sessions?.length || 0 }} sessions today</span>
            </div>

            <div v-if="teacher_metrics.today_sessions && teacher_metrics.today_sessions.length > 0" class="divide-y divide-slate-100">
              <div
                v-for="session in teacher_metrics.today_sessions"
                :key="session.id"
                class="py-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2"
              >
                <div>
                  <div class="text-sm font-bold text-slate-900">{{ session.class_name }}</div>
                  <div class="text-xs text-slate-500">
                    {{ session.course_name }} · {{ session.start_time?.slice(11, 16) }} - {{ session.end_time?.slice(11, 16) }} UTC
                  </div>
                </div>

                <div class="flex items-center gap-3">
                  <span
                    :class="[
                      'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                      session.attendance_marked ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                    ]"
                  >
                    {{ session.attendance_marked ? 'Attendance Marked' : 'Pending Attendance' }}
                  </span>
                  <Link
                    :href="route('attendance.index', { session_id: session.id })"
                    class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition"
                  >
                    Mark
                  </Link>
                </div>
              </div>
            </div>
            <div v-else class="text-xs text-slate-400 py-8 text-center italic">
              No sessions scheduled for you today.
            </div>
          </div>

          <!-- Students Needing Attention -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="flex justify-between items-center">
              <h2 class="text-base font-bold text-slate-900">Needs Attention</h2>
              <span class="text-[10px] uppercase font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded">Absences & Sabaq</span>
            </div>

            <div v-if="teacher_metrics.students_needing_attention && teacher_metrics.students_needing_attention.length > 0" class="space-y-3">
              <div
                v-for="st in teacher_metrics.students_needing_attention"
                :key="st.id"
                class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs flex justify-between items-center"
              >
                <div>
                  <div class="font-bold text-slate-900">{{ st.name }} ({{ st.student_code }})</div>
                  <div class="text-[10px] text-amber-700 font-medium mt-0.5">
                    {{ st.reasons?.join(' · ') }}
                  </div>
                </div>
                <Link
                  :href="route('hifz.student.profile', st.id)"
                  class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold rounded-lg text-[11px]"
                >
                  Log Sabaq
                </Link>
              </div>
            </div>
            <div v-else class="text-xs text-slate-400 py-6 text-center italic">
              All assigned students are in good standing!
            </div>
          </div>
        </div>
      </div>

      <!-- ADMIN / OWNER / ACADEMIC COORDINATOR VIEW -->
      <div v-if="admin_metrics" class="space-y-6">
        <!-- Quick Action Bar -->
        <div class="flex flex-wrap gap-2 text-xs">
          <Link :href="route('students.create')" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow transition">
            + Register Student
          </Link>
          <Link :href="route('hifz.fast-entry')" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-800 font-bold border border-slate-300 rounded-xl shadow-sm transition">
            📖 Fast Qur'an Entry
          </Link>
          <Link :href="route('finance.invoices.index')" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-800 font-bold border border-slate-300 rounded-xl shadow-sm transition">
            💳 Invoices & Fees
          </Link>
          <Link :href="route('reports.index')" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-800 font-bold border border-slate-300 rounded-xl shadow-sm transition">
            📊 Reports Center
          </Link>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Students</div>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ admin_metrics.active_students_count }}</div>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">Enrolled & Active</div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Teachers & Classes</div>
            <div class="text-2xl font-black text-slate-900 mt-2">
              {{ admin_metrics.active_teachers_count }} <span class="text-sm font-normal text-slate-400">/ {{ admin_metrics.active_classes_count }} classes</span>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Teaching staff</div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Attendance</div>
            <div class="text-2xl font-black text-emerald-700 mt-2">
              {{ admin_metrics.today_attendance.percentage }}%
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
              {{ admin_metrics.today_attendance.marked_sessions_count }} / {{ admin_metrics.today_attendance.sessions_count }} sessions marked
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Tuition Fees</div>
            <div class="text-xl font-black text-slate-900 mt-2">{{ formatCurrency(admin_metrics.fees.pending_total) }}</div>
            <div class="text-[11px] font-bold mt-1" :class="admin_metrics.fees.overdue_count > 0 ? 'text-red-600' : 'text-slate-400'">
              {{ admin_metrics.fees.overdue_count }} Overdue Invoices ({{ formatCurrency(admin_metrics.fees.overdue_total) }})
            </div>
          </div>
        </div>

        <!-- Split Grid: Upcoming Sessions & Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Upcoming Sessions -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="flex justify-between items-center">
              <h2 class="text-base font-bold text-slate-900">Today's Timetable Sessions</h2>
              <Link :href="route('timetable.today')" class="text-xs text-emerald-700 font-bold hover:underline">View All →</Link>
            </div>

            <div v-if="admin_metrics.upcoming_sessions && admin_metrics.upcoming_sessions.length > 0" class="divide-y divide-slate-100">
              <div
                v-for="session in admin_metrics.upcoming_sessions"
                :key="session.id"
                class="py-3 flex justify-between items-center text-xs"
              >
                <div>
                  <div class="font-bold text-slate-900">{{ session.class_name }} ({{ session.course_name }})</div>
                  <div class="text-slate-500 mt-0.5">Teacher: {{ session.teacher_name }}</div>
                </div>
                <div class="text-right">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                      session.attendance_marked ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'
                    ]"
                  >
                    {{ session.attendance_marked ? 'Marked' : 'Unmarked' }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="text-xs text-slate-400 py-6 text-center italic">
              No sessions scheduled for today.
            </div>
          </div>

          <!-- Recent Activity Feed -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="flex justify-between items-center">
              <h2 class="text-base font-bold text-slate-900">Recent Audit Activity</h2>
              <span class="text-xs text-slate-400">Live Audit Trail</span>
            </div>

            <div v-if="admin_metrics.recent_activity && admin_metrics.recent_activity.length > 0" class="divide-y divide-slate-100">
              <div
                v-for="act in admin_metrics.recent_activity"
                :key="act.id"
                class="py-2.5 flex justify-between items-center text-xs"
              >
                <div>
                  <span class="font-bold text-slate-800">{{ act.user_name }}</span>
                  <span class="text-slate-500"> {{ act.action }} {{ act.auditable_type }}</span>
                </div>
                <div class="text-[11px] text-slate-400">{{ act.created_at }}</div>
              </div>
            </div>
            <div v-else class="text-xs text-slate-400 py-6 text-center italic">
              No recent activity logged.
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
