<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
  session: {
    type: Object,
    required: true,
  },
  students: {
    type: Array,
    required: true,
  },
  teacherCheckInStatus: {
    type: String,
    default: 'present',
  },
  teacherCheckInNotes: {
    type: String,
    default: '',
  },
});

const teacherStatus = ref(props.teacherCheckInStatus || 'present');
const teacherNotes = ref(props.teacherCheckInNotes || '');

// Local reactive attendance records for students
const studentRecords = ref(
  props.students.map((s) => ({
    student_id: s.student_id,
    full_name: s.full_name,
    student_code: s.student_code,
    status: s.status, // null if unmarked
    note: s.note || '',
    showNote: Boolean(s.note),
  }))
);

const isSaving = ref(false);
const saveSuccess = ref(false);

const setStatus = (student, status) => {
  student.status = status;
};

// "Mark All Present" shortcut
const markAllPresent = () => {
  studentRecords.value.forEach((s) => {
    if (!s.status) {
      s.status = 'present';
    }
  });
};

// Summary metrics
const markedCount = computed(() => studentRecords.value.filter((s) => s.status !== null).length);
const totalCount = computed(() => studentRecords.value.length);
const presentCount = computed(() => studentRecords.value.filter((s) => s.status === 'present').length);
const absentCount = computed(() => studentRecords.value.filter((s) => s.status === 'absent').length);
const lateCount = computed(() => studentRecords.value.filter((s) => s.status === 'late').length);

const submitAttendance = () => {
  isSaving.value = true;
  saveSuccess.value = false;

  const payload = {
    records: studentRecords.value
      .filter((s) => s.status !== null)
      .map((s) => ({
        student_id: s.student_id,
        status: s.status,
        note: s.note || undefined,
      })),
    teacher_check_in_status: teacherStatus.value,
    teacher_check_in_notes: teacherNotes.value || undefined,
  };

  router.post(route('attendance.sessions.save', props.session.id), payload, {
    preserveScroll: true,
    onSuccess: () => {
      isSaving.value = false;
      saveSuccess.value = true;
      setTimeout(() => (saveSuccess.value = false), 3000);
    },
    onError: () => {
      isSaving.value = false;
    },
  });
};
</script>

<template>
  <Head :title="`Mark Attendance - ${session.class_group?.name || 'Class'}`" />

  <div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8" dir="ltr">
    <div class="max-w-3xl mx-auto space-y-6">
      <!-- Top Navigation & Session Header -->
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/90 space-y-5">
        <div class="flex items-center justify-between">
          <Link
            :href="route('timetable.today')"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:text-emerald-800 transition-colors"
          >
            <span>←</span> Back to Today's Schedule
          </Link>
          <span class="text-xs font-mono bg-slate-100 text-slate-700 px-3 py-1 rounded-lg font-semibold border border-slate-200">
            {{ session.scheduled_date }}
          </span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
          <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
              {{ session.class_group?.course?.name || 'Course Session' }}
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5">
              {{ session.class_group?.name }} • <span class="uppercase tracking-wider font-semibold text-slate-700">{{ session.type }}</span>
            </p>
          </div>
          <div class="flex items-center">
            <button
              type="button"
              @click="markAllPresent"
              class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200/90 hover:bg-emerald-100 active:scale-[0.98] transition-all shadow-sm"
            >
              <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
              </svg>
              Mark All Present
            </button>
          </div>
        </div>

        <!-- Teacher Check-in bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-slate-50 rounded-xl border border-slate-200/90 text-xs gap-3">
          <div class="font-bold text-slate-800">
            Teacher Check-In:
          </div>
          <div class="flex items-center space-x-2">
            <button
              type="button"
              @click="teacherStatus = 'present'"
              :class="[
                'px-3.5 py-1.5 rounded-lg font-bold transition-all active:scale-[0.98] text-xs shadow-sm',
                teacherStatus === 'present' ? 'bg-emerald-600 text-white shadow ring-2 ring-emerald-600/30' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
              ]"
            >
              ✓ Present
            </button>
            <button
              type="button"
              @click="teacherStatus = 'late'"
              :class="[
                'px-3.5 py-1.5 rounded-lg font-bold transition-all active:scale-[0.98] text-xs shadow-sm',
                teacherStatus === 'late' ? 'bg-amber-600 text-white shadow ring-2 ring-amber-600/30' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
              ]"
            >
              ⏱ Late
            </button>
            <button
              type="button"
              @click="teacherStatus = 'absent'"
              :class="[
                'px-3.5 py-1.5 rounded-lg font-bold transition-all active:scale-[0.98] text-xs shadow-sm',
                teacherStatus === 'absent' ? 'bg-rose-600 text-white shadow ring-2 ring-rose-600/30' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
              ]"
            >
              ✕ Absent
            </button>
          </div>
        </div>

        <!-- Progress Metrics -->
        <div class="grid grid-cols-4 gap-3 text-center text-xs">
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
            <div class="text-sm font-black text-slate-900">{{ markedCount }} / {{ totalCount }}</div>
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Marked</div>
          </div>
          <div class="p-3 bg-emerald-50 text-emerald-900 rounded-xl border border-emerald-200/80">
            <div class="text-sm font-black text-emerald-800">{{ presentCount }}</div>
            <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Present</div>
          </div>
          <div class="p-3 bg-rose-50 text-rose-900 rounded-xl border border-rose-200/80">
            <div class="text-sm font-black text-rose-800">{{ absentCount }}</div>
            <div class="text-[11px] font-semibold text-rose-700 uppercase tracking-wide">Absent</div>
          </div>
          <div class="p-3 bg-amber-50 text-amber-900 rounded-xl border border-amber-200/80">
            <div class="text-sm font-black text-amber-800">{{ lateCount }}</div>
            <div class="text-[11px] font-semibold text-amber-700 uppercase tracking-wide">Late</div>
          </div>
        </div>
      </div>

      <!-- Student Roster (Mobile-First Cards) -->
      <div class="space-y-3">
        <div
          v-for="student in studentRecords"
          :key="student.student_id"
          class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-slate-200/90 space-y-3.5 hover:border-slate-300 transition-colors"
        >
          <div class="flex items-center justify-between">
            <div>
              <span class="font-bold text-sm text-slate-900">{{ student.full_name }}</span>
              <span class="text-xs font-mono text-slate-500 block">{{ student.student_code }}</span>
            </div>
            <div>
              <button
                type="button"
                @click="student.showNote = !student.showNote"
                class="text-xs font-bold text-slate-600 hover:text-emerald-700 underline transition-colors"
              >
                {{ student.note ? 'Edit Note' : '+ Add Note' }}
              </button>
            </div>
          </div>

          <!-- One-Tap Status Toggles -->
          <div class="grid grid-cols-4 gap-2">
            <button
              type="button"
              @click="setStatus(student, 'present')"
              :class="[
                'py-2.5 px-2 text-xs font-bold rounded-xl border transition-all active:scale-[0.98]',
                student.status === 'present'
                  ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm ring-2 ring-emerald-600/30'
                  : 'bg-slate-50 text-slate-700 border-slate-200/90 hover:bg-slate-100'
              ]"
            >
              ✓ Present
            </button>
            <button
              type="button"
              @click="setStatus(student, 'absent')"
              :class="[
                'py-2.5 px-2 text-xs font-bold rounded-xl border transition-all active:scale-[0.98]',
                student.status === 'absent'
                  ? 'bg-rose-600 text-white border-rose-600 shadow-sm ring-2 ring-rose-600/30'
                  : 'bg-slate-50 text-slate-700 border-slate-200/90 hover:bg-slate-100'
              ]"
            >
              ✕ Absent
            </button>
            <button
              type="button"
              @click="setStatus(student, 'late')"
              :class="[
                'py-2.5 px-2 text-xs font-bold rounded-xl border transition-all active:scale-[0.98]',
                student.status === 'late'
                  ? 'bg-amber-600 text-white border-amber-600 shadow-sm ring-2 ring-amber-600/30'
                  : 'bg-slate-50 text-slate-700 border-slate-200/90 hover:bg-slate-100'
              ]"
            >
              ⏱ Late
            </button>
            <button
              type="button"
              @click="setStatus(student, 'excused')"
              :class="[
                'py-2.5 px-2 text-xs font-bold rounded-xl border transition-all active:scale-[0.98]',
                student.status === 'excused'
                  ? 'bg-sky-600 text-white border-sky-600 shadow-sm ring-2 ring-sky-600/30'
                  : 'bg-slate-50 text-slate-700 border-slate-200/90 hover:bg-slate-100'
              ]"
            >
              ℹ Excused
            </button>
          </div>

          <!-- Note Input (Collapsible) -->
          <div v-if="student.showNote" class="pt-1">
            <input
              v-model="student.note"
              type="text"
              placeholder="e.g. Arrived 15 mins late due to transport"
              class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-2.5"
            />
          </div>
        </div>

        <div v-if="studentRecords.length === 0" class="p-8 text-center bg-white rounded-2xl border border-slate-200 text-slate-400 italic text-xs">
          No students are currently enrolled in this class group.
        </div>
      </div>

      <!-- Sticky Bottom Action Bar -->
      <div class="sticky bottom-4 bg-white/95 backdrop-blur border border-slate-200/90 p-4 rounded-2xl shadow-xl flex items-center justify-between">
        <div class="text-xs">
          <span v-if="saveSuccess" class="font-bold text-emerald-700 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Attendance Saved Successfully!
          </span>
          <span v-else class="text-slate-600 font-medium">
            <strong class="text-slate-900">{{ markedCount }}</strong> of {{ totalCount }} students marked
          </span>
        </div>
        <button
          type="button"
          @click="submitAttendance"
          :disabled="isSaving"
          class="px-6 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-sm hover:bg-emerald-700 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 transition-all disabled:opacity-50"
        >
          {{ isSaving ? 'Saving...' : 'Save Attendance' }}
        </button>
      </div>
    </div>
  </div>
</template>
