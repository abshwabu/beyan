<script setup>
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  teacher: {
    type: Object,
    default: null,
  },
  teachers: {
    type: Array,
    default: () => [],
  },
  today: {
    type: String,
    required: true,
  },
  timezone: {
    type: String,
    required: true,
  },
  sessions: {
    type: Array,
    required: true,
  },
});

const onTeacherSelect = (teacherId) => {
  router.visit(route('timetable.today', { teacher_id: teacherId }));
};

const getTypeBadgeClass = (type) => {
  switch (type) {
    case 'online':
      return 'bg-sky-50 text-sky-700 ring-sky-600/20';
    case 'hybrid':
      return 'bg-purple-50 text-purple-700 ring-purple-600/20';
    default:
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
  }
};
</script>

<template>
  <Head title="Today's Timetable - Beyan" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Daily Schedule</span>
            <!-- Teacher Selector for Admins -->
            <div v-if="teachers && teachers.length > 1" class="flex items-center gap-1.5">
              <span class="text-xs text-slate-400">• Teacher:</span>
              <select
                :value="teacher?.id"
                @change="onTeacherSelect($event.target.value)"
                class="text-xs font-bold text-slate-800 bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1 focus:border-emerald-500 focus:ring-emerald-500"
              >
                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.full_name }}</option>
              </select>
            </div>
          </div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-1">
            {{ teacher ? teacher.full_name : 'All Scheduled Classes' }}
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Today: <span class="font-medium text-slate-700">{{ today }}</span> • Displaying times in <span class="font-medium text-slate-700">{{ timezone }}</span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('dashboard')"
            class="inline-flex items-center rounded-lg bg-slate-100 hover:bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition"
          >
            &larr; Dashboard
          </Link>
          <span class="inline-flex items-center rounded-lg bg-emerald-50 text-emerald-800 px-3 py-1.5 text-xs font-bold">
            {{ sessions.length }} {{ sessions.length === 1 ? 'Session' : 'Sessions' }} Today
          </span>
        </div>
      </div>

      <!-- Sessions List -->
      <div v-if="sessions.length > 0" class="space-y-4">
        <div
          v-for="session in sessions"
          :key="session.id"
          class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-emerald-300 transition flex flex-col md:flex-row md:items-center md:justify-between gap-6"
        >
          <!-- Session Left Details -->
          <div class="space-y-2">
            <div class="flex items-center space-x-2">
              <span
                :class="[
                  'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize',
                  getTypeBadgeClass(session.type)
                ]"
              >
                {{ session.type }}
              </span>
              <span class="text-xs font-mono font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded">
                {{ session.formatted_time_range }}
              </span>
            </div>

            <h2 class="text-lg font-bold text-slate-900">
              {{ session.course_name }}
              <span class="text-sm font-medium text-slate-500">— {{ session.class_name }}</span>
            </h2>

            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
              <span v-if="session.room" class="flex items-center gap-1">
                📍 Room: <strong class="text-slate-700">{{ session.room }}</strong>
              </span>
              <span class="flex items-center gap-1">
                👥 Enrolled: <strong class="text-slate-700">{{ session.enrolled_count }} students</strong>
              </span>
              <a
                v-if="session.meeting_link"
                :href="session.meeting_link"
                target="_blank"
                class="inline-flex items-center font-semibold text-sky-600 hover:text-sky-800 underline"
              >
                🔗 Join Live Class
              </a>
            </div>
          </div>

          <!-- One-Tap Action: Attendance -->
          <div class="flex items-center gap-3">
            <Link
              :href="session.attendance_url"
              class="w-full md:w-auto inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 transition"
            >
              ✓ Take Attendance
            </Link>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="bg-white p-12 text-center rounded-2xl shadow-sm border border-slate-200 space-y-3"
      >
        <div class="text-4xl">📅</div>
        <h3 class="text-base font-bold text-slate-900">
          {{ teacher ? 'No Sessions Scheduled For Today' : 'No Active Teachers or Sessions Found' }}
        </h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          {{ teacher ? 'There are no active teaching sessions scheduled for today.' : 'No active teacher profiles or scheduled sessions were found for today.' }}
        </p>
        <div class="pt-2">
          <Link
            :href="route('dashboard')"
            class="inline-flex items-center px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-sm"
          >
            Return to Dashboard
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
