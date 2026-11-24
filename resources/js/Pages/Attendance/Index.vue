<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  records: {
    type: Object,
    required: true,
  },
  todaySessions: {
    type: Array,
    default: () => [],
  },
  todayCounts: {
    type: Object,
    default: () => ({
      total: 0,
      present: 0,
      absent: 0,
      late: 0,
      excused: 0,
    }),
  },
  flagged: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  today: {
    type: String,
    default: '',
  },
});

const page = usePage();
const isRtl = computed(() => page.props.i18n?.direction === 'rtl');

const selectedDate = ref(props.filters.date || '');
const selectedStatus = ref(props.filters.status || '');

const hasActiveFilters = computed(() => {
  return Boolean(selectedDate.value || selectedStatus.value);
});

const applyFilters = () => {
  router.get(
    route('attendance.index'),
    {
      date: selectedDate.value || undefined,
      status: selectedStatus.value || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

const clearFilters = () => {
  selectedDate.value = '';
  selectedStatus.value = '';
  applyFilters();
};

watch([selectedDate, selectedStatus], applyFilters);

const getStatusBadge = (s) => {
  switch (s) {
    case 'present':
      return {
        label: 'Present',
        classes: 'bg-emerald-50 text-emerald-800 border-emerald-200/90 ring-emerald-600/10',
        dot: 'bg-emerald-500',
      };
    case 'absent':
      return {
        label: 'Absent',
        classes: 'bg-rose-50 text-rose-800 border-rose-200/90 ring-rose-600/10',
        dot: 'bg-rose-500',
      };
    case 'late':
      return {
        label: 'Late',
        classes: 'bg-amber-50 text-amber-800 border-amber-200/90 ring-amber-600/10',
        dot: 'bg-amber-500',
      };
    case 'excused':
      return {
        label: 'Excused',
        classes: 'bg-blue-50 text-blue-800 border-blue-200/90 ring-blue-600/10',
        dot: 'bg-blue-500',
      };
    default:
      return {
        label: s || 'Unmarked',
        classes: 'bg-slate-50 text-slate-700 border-slate-200 ring-slate-600/10',
        dot: 'bg-slate-400',
      };
  }
};

const getInitials = (name) => {
  if (!name) return 'ST';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase();
};

const attendanceRate = computed(() => {
  const total = props.todayCounts.present + props.todayCounts.absent + props.todayCounts.late + props.todayCounts.excused;
  if (total === 0) return 0;
  return Math.round(((props.todayCounts.present + props.todayCounts.late) / total) * 100);
});
</script>

<template>
  <Head title="Attendance Management - Beyan" />

  <div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8" dir="ltr">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Breadcrumbs & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1.5">
            <Link :href="route('dashboard')" class="hover:text-emerald-700 transition-colors">
              Dashboard
            </Link>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800 font-semibold">Attendance Management</span>
          </nav>

          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
              Attendance
            </h1>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
              Today: {{ today }}
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
            Real-time daily roll call, timetable session check-ins, and absence monitoring.
          </p>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto flex-wrap">
          <Link
            :href="route('timetable.today')"
            class="inline-flex items-center gap-1.5 rounded-xl bg-white border border-slate-200/90 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition-all cursor-pointer"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Today's Timetable</span>
          </Link>

          <Link
            :href="route('hifz.fast-entry')"
            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-xs hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 transition-all cursor-pointer transform active:scale-[0.98]"
          >
            <span>⚡</span>
            <span>Fast Qur'an Log</span>
          </Link>
        </div>
      </div>

      <!-- Quick Metrics Bento Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <!-- Present Rate -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-slate-200/90 space-y-1">
          <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-500">
            <span>Present Today</span>
            <span class="w-2 h-2 rounded-full bg-emerald-500" />
          </div>
          <div class="text-2xl font-bold text-slate-900">
            {{ todayCounts.present }}
          </div>
          <div class="text-[11px] text-slate-500">
            {{ attendanceRate }}% attendance rate
          </div>
        </div>

        <!-- Absent -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-slate-200/90 space-y-1">
          <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-500">
            <span>Absent Today</span>
            <span class="w-2 h-2 rounded-full bg-rose-500" />
          </div>
          <div class="text-2xl font-bold text-slate-900">
            {{ todayCounts.absent }}
          </div>
          <div class="text-[11px] text-slate-500">
            Unexcused absences
          </div>
        </div>

        <!-- Late Arrivals -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-slate-200/90 space-y-1">
          <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-500">
            <span>Late Arrivals</span>
            <span class="w-2 h-2 rounded-full bg-amber-500" />
          </div>
          <div class="text-2xl font-bold text-slate-900">
            {{ todayCounts.late }}
          </div>
          <div class="text-[11px] text-slate-500">
            Recorded as tardy
          </div>
        </div>

        <!-- Excused -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-slate-200/90 space-y-1">
          <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-500">
            <span>Excused</span>
            <span class="w-2 h-2 rounded-full bg-blue-500" />
          </div>
          <div class="text-2xl font-bold text-slate-900">
            {{ todayCounts.excused }}
          </div>
          <div class="text-[11px] text-slate-500">
            Notified & approved
          </div>
        </div>
      </div>

      <!-- Repeated Absences Warning Banner -->
      <div
        v-if="flagged && flagged.length > 0"
        class="bg-amber-50 border border-amber-200 rounded-2xl p-5 space-y-3"
      >
        <div class="flex items-center gap-2.5 text-amber-900 font-semibold text-sm">
          <span class="flex items-center justify-center w-6 h-6 rounded-full bg-amber-200 text-amber-900 text-xs font-bold">⚠️</span>
          <span>Repeated Absence Alert: {{ flagged.length }} students have 3+ consecutive unexcused absences</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
          <div
            v-for="item in flagged"
            :key="item.student_id"
            class="bg-white/90 p-3 rounded-xl border border-amber-200/80 flex items-center justify-between text-xs shadow-2xs"
          >
            <div>
              <strong class="text-slate-900 block">{{ item.full_name }}</strong>
              <span class="text-slate-500 font-mono text-[11px]">{{ item.student_code }}</span>
            </div>
            <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[11px]">
              {{ item.consecutive_absences }} Absences
            </span>
          </div>
        </div>
      </div>

      <!-- Today's Scheduled Class Sessions Section -->
      <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/90 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-100">
              📅
            </span>
            <div>
              <h2 class="text-base font-semibold text-slate-900">Today's Class Sessions</h2>
              <p class="text-xs text-slate-500">Select any scheduled session to take or update roll call.</p>
            </div>
          </div>

          <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">
            {{ todaySessions.length }} Sessions Scheduled
          </span>
        </div>

        <!-- Sessions Grid -->
        <div v-if="todaySessions.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
          <div
            v-for="s in todaySessions"
            :key="s.id"
            class="p-4 rounded-xl border transition-all space-y-3 relative group"
            :class="s.is_marked
              ? 'bg-slate-50/50 border-slate-200 hover:border-slate-300'
              : 'bg-emerald-50/30 border-emerald-200 hover:border-emerald-300'"
          >
            <div class="flex items-start justify-between gap-2">
              <div>
                <h3 class="font-bold text-slate-900 text-sm group-hover:text-emerald-700 transition-colors">
                  {{ s.class_name }}
                </h3>
                <span class="text-xs text-slate-500 font-medium block">
                  {{ s.course_name }}
                </span>
              </div>

              <span
                class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase shrink-0 border"
                :class="s.is_marked
                  ? 'bg-emerald-100 text-emerald-800 border-emerald-200'
                  : 'bg-amber-100 text-amber-800 border-amber-200'"
              >
                {{ s.is_marked ? 'Marked' : 'Pending' }}
              </span>
            </div>

            <div class="space-y-1 text-xs text-slate-600">
              <div class="flex items-center gap-1.5">
                <span class="text-slate-400">⏰</span>
                <span class="font-semibold text-slate-800">{{ s.start_time }} - {{ s.end_time }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="text-slate-400">👨‍🏫</span>
                <span>{{ s.teacher_name }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="text-slate-400">👥</span>
                <span>{{ s.enrolled_count }} enrolled students</span>
              </div>
            </div>

            <div class="pt-2 border-t border-slate-200/70">
              <Link
                :href="s.attendance_url"
                class="w-full py-2 px-3 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all cursor-pointer shadow-2xs"
                :class="s.is_marked
                  ? 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-300'
                  : 'bg-emerald-600 hover:bg-emerald-500 text-white'"
              >
                <span>{{ s.is_marked ? 'Update Roll Call' : 'Take Attendance' }}</span>
                <span>→</span>
              </Link>
            </div>
          </div>
        </div>

        <div v-else class="p-8 text-center bg-slate-50/50 rounded-xl border border-dashed border-slate-200 space-y-2">
          <p class="text-sm font-semibold text-slate-800">No class sessions scheduled for today</p>
          <p class="text-xs text-slate-500">Sessions are configured through academic timetables and class groups.</p>
        </div>
      </div>

      <!-- Attendance Records Log -->
      <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden space-y-4 p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Attendance Log & History</h2>
            <p class="text-xs text-slate-500">Browse and filter historical roll-call records.</p>
          </div>

          <!-- Filters -->
          <div class="flex items-center gap-2.5 flex-wrap">
            <div>
              <input
                v-model="selectedDate"
                type="date"
                class="block rounded-lg border border-slate-300 text-xs px-3 py-2 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-slate-50/50"
              />
            </div>

            <div>
              <select
                v-model="selectedStatus"
                class="block rounded-lg border border-slate-300 text-xs px-3 py-2 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-slate-50/50 font-medium text-slate-800"
              >
                <option value="">All Statuses</option>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="excused">Excused</option>
              </select>
            </div>

            <button
              v-if="hasActiveFilters"
              type="button"
              @click="clearFilters"
              class="px-2.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
            >
              Reset
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto -mx-5 sm:-mx-6">
          <table class="min-w-full divide-y divide-slate-200 text-start text-sm">
            <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              <tr>
                <th class="px-6 py-3.5 text-start">Student</th>
                <th class="px-6 py-3.5 text-start">Session / Class</th>
                <th class="px-6 py-3.5 text-start">Date</th>
                <th class="px-6 py-3.5 text-start">Status</th>
                <th class="px-6 py-3.5 text-start">Recorded By</th>
                <th class="px-6 py-3.5 text-start">Notes</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
              <tr
                v-for="record in records.data"
                :key="record.id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <!-- Student -->
                <td class="px-6 py-3.5 whitespace-nowrap">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-xs text-slate-700 shrink-0">
                      {{ getInitials(record.student?.full_name || record.student?.name) }}
                    </div>
                    <div>
                      <Link
                        v-if="record.student"
                        :href="route('students.show', record.student.id)"
                        class="font-semibold text-slate-900 hover:text-emerald-700 transition-colors text-xs sm:text-sm block"
                      >
                        {{ record.student.full_name || record.student.name }}
                      </Link>
                      <span class="text-[11px] font-mono text-slate-500 block">
                        {{ record.student?.student_code || record.student?.admission_number || 'N/A' }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Session / Class -->
                <td class="px-6 py-3.5 whitespace-nowrap text-xs text-slate-700">
                  <span v-if="record.class_session?.class_group" class="font-medium">
                    {{ record.class_session.class_group.name }}
                  </span>
                  <span v-else class="text-slate-400 italic">Direct Entry</span>
                </td>

                <!-- Date -->
                <td class="px-6 py-3.5 whitespace-nowrap text-xs text-slate-600 font-medium">
                  {{ record.date }}
                </td>

                <!-- Status Badge -->
                <td class="px-6 py-3.5 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border',
                      getStatusBadge(record.status).classes
                    ]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="getStatusBadge(record.status).dot" />
                    {{ getStatusBadge(record.status).label }}
                  </span>
                </td>

                <!-- Recorded By -->
                <td class="px-6 py-3.5 whitespace-nowrap text-xs text-slate-500">
                  {{ record.recorder?.name || 'Staff' }}
                </td>

                <!-- Notes -->
                <td class="px-6 py-3.5 text-xs text-slate-600 max-w-xs truncate">
                  {{ record.note || record.remarks || '—' }}
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="!records.data || records.data.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs sm:text-sm">
                  No attendance records found for this criteria.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="records.links && records.links.length > 3"
          class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-t border-slate-200 pt-4 gap-3 text-xs"
        >
          <div class="text-slate-500">
            Showing {{ records.from || 0 }} to {{ records.to || 0 }} of {{ records.total }} records
          </div>

          <div class="flex items-center gap-1 flex-wrap">
            <template v-for="(link, key) in records.links" :key="key">
              <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                :class="[
                  'px-3 py-1.5 rounded-lg border font-semibold transition-all',
                  link.active
                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:text-slate-900'
                ]"
              />
              <span
                v-else
                v-html="link.label"
                class="px-3 py-1.5 rounded-lg border border-slate-200/60 text-slate-300 cursor-not-allowed select-none"
              />
            </template>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
