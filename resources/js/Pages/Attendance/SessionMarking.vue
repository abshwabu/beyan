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

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto space-y-6">
      <!-- Top Navigation & Session Header -->
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-4">
        <div class="flex items-center justify-between">
          <Link
            :href="route('timetable.today')"
            class="text-xs font-semibold text-emerald-600 hover:text-emerald-700"
          >
            ← Back to Today's Schedule
          </Link>
          <span class="text-xs font-mono bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md font-semibold">
            {{ session.scheduled_date }}
          </span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4">
          <div>
            <h1 class="text-xl font-bold text-slate-900">
              {{ session.class_group?.course?.name || 'Course' }}
            </h1>
            <p class="text-xs text-slate-500 font-medium">
              {{ session.class_group?.name }} • {{ session.type?.toUpperCase() }}
            </p>
          </div>
          <div class="flex items-center space-x-2">
            <button
              type="button"
              @click="markAllPresent"
              class="px-3.5 py-2 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition shadow-sm"
            >
              ✓ Mark All Present
            </button>
          </div>
        </div>

        <!-- Teacher Check-in bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs gap-3">
          <div class="font-semibold text-slate-700">
            Teacher Check-In:
          </div>
          <div class="flex items-center space-x-1.5">
            <button
              type="button"
              @click="teacherStatus = 'present'"
              :class="[
                'px-3 py-1.5 rounded-lg font-bold transition text-xs',
                teacherStatus === 'present' ? 'bg-emerald-600 text-white shadow' : 'bg-white border text-slate-600 hover:bg-slate-50'
              ]"
            >
              Present
            </button>
            <button
              type="button"
              @click="teacherStatus = 'late'"
              :class="[
                'px-3 py-1.5 rounded-lg font-bold transition text-xs',
                teacherStatus === 'late' ? 'bg-amber-600 text-white shadow' : 'bg-white border text-slate-600 hover:bg-slate-50'
              ]"
            >
              Late
            </button>
            <button
              type="button"
              @click="teacherStatus = 'absent'"
              :class="[
                'px-3 py-1.5 rounded-lg font-bold transition text-xs',
                teacherStatus === 'absent' ? 'bg-rose-600 text-white shadow' : 'bg-white border text-slate-600 hover:bg-slate-50'
              ]"
            >
              Absent
            </button>
          </div>
        </div>

        <!-- Progress Metrics -->
        <div class="grid grid-cols-4 gap-2 text-center text-xs">
          <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
            <div class="font-bold text-slate-800">{{ markedCount }} / {{ totalCount }}</div>
            <div class="text-[10px] text-slate-400">Marked</div>
          </div>
          <div class="p-2 bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-100">
            <div class="font-bold">{{ presentCount }}</div>
            <div class="text-[10px]">Present</div>
          </div>
          <div class="p-2 bg-rose-50 text-rose-800 rounded-lg border border-rose-100">
            <div class="font-bold">{{ absentCount }}</div>
            <div class="text-[10px]">Absent</div>
          </div>
          <div class="p-2 bg-amber-50 text-amber-800 rounded-lg border border-amber-100">
            <div class="font-bold">{{ lateCount }}</div>
            <div class="text-[10px]">Late</div>
          </div>
        </div>
      </div>

      <!-- Student Roster (Mobile-First Cards) -->
      <div class="space-y-3">
        <div
          v-for="student in studentRecords"
          :key="student.student_id"
          class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 space-y-3 transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <span class="font-bold text-sm text-slate-900">{{ student.full_name }}</span>
              <span class="text-xs font-mono text-slate-400 block">{{ student.student_code }}</span>
            </div>
            <div>
              <button
                type="button"
                @click="student.showNote = !student.showNote"
                class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 underline"
              >
                {{ student.note ? 'Edit Note' : '+ Note' }}
              </button>
            </div>
          </div>

          <!-- One-Tap Status Toggles -->
          <div class="grid grid-cols-4 gap-2">
            <button
              type="button"
              @click="setStatus(student, 'present')"
              :class="[
                'py-2 px-1 text-xs font-bold rounded-lg border transition',
                student.status === 'present'
                  ? 'bg-emerald-600 text-white border-emerald-600 shadow'
                  : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
              ]"
            >
              Present
            </button>
            <button
              type="button"
              @click="setStatus(student, 'absent')"
              :class="[
                'py-2 px-1 text-xs font-bold rounded-lg border transition',
                student.status === 'absent'
                  ? 'bg-rose-600 text-white border-rose-600 shadow'
                  : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
              ]"
            >
              Absent
            </button>
            <button
              type="button"
              @click="setStatus(student, 'late')"
              :class="[
                'py-2 px-1 text-xs font-bold rounded-lg border transition',
                student.status === 'late'
                  ? 'bg-amber-600 text-white border-amber-600 shadow'
                  : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
              ]"
            >
              Late
            </button>
            <button
              type="button"
              @click="setStatus(student, 'excused')"
              :class="[
                'py-2 px-1 text-xs font-bold rounded-lg border transition',
                student.status === 'excused'
                  ? 'bg-sky-600 text-white border-sky-600 shadow'
                  : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
              ]"
            >
              Excused
            </button>
          </div>

          <!-- Note Input (Collapsible) -->
          <div v-if="student.showNote" class="pt-1">
            <input
              v-model="student.note"
              type="text"
              placeholder="e.g. Arrived 15 mins late due to transport"
              class="w-full text-xs rounded-md border-slate-300 focus:border-emerald-500 focus:ring-emerald-500"
            />
          </div>
        </div>

        <div v-if="studentRecords.length === 0" class="p-8 text-center bg-white rounded-xl border border-slate-200 text-slate-400 italic text-xs">
          No students are currently enrolled in this class group.
        </div>
      </div>

      <!-- Sticky Bottom Action Bar -->
      <div class="sticky bottom-4 bg-white/95 backdrop-blur border border-slate-300 p-4 rounded-2xl shadow-xl flex items-center justify-between">
        <div class="text-xs">
          <span v-if="saveSuccess" class="font-bold text-emerald-600">✓ Attendance Saved Successfully!</span>
          <span v-else class="text-slate-500">{{ markedCount }} of {{ totalCount }} students marked</span>
        </div>
        <button
          type="button"
          @click="submitAttendance"
          :disabled="isSaving"
          class="px-6 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-sm shadow hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 transition disabled:opacity-50"
        >
          {{ isSaving ? 'Saving...' : 'Save Attendance' }}
        </button>
      </div>
    </div>
  </div>
</template>
