<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  classes: {
    type: Array,
    default: () => [],
  },
  courses: {
    type: Array,
    default: () => [],
  },
  teachers: {
    type: Array,
    default: () => [],
  },
  terms: {
    type: Array,
    default: () => [],
  },
});

// Modals
const showCreateModal = ref(false);
const editingClass = ref(null);

// Forms
const createForm = useForm({
  name: '',
  course_id: props.courses[0]?.id || '',
  teacher_id: props.teachers[0]?.id || '',
  academic_term_id: props.terms.find((t) => t.is_current)?.id || props.terms[0]?.id || '',
  type: 'physical',
  capacity: 20,
  room: '',
  meeting_link: '',
});

const editForm = useForm({
  id: '',
  name: '',
  course_id: '',
  teacher_id: '',
  academic_term_id: '',
  type: 'physical',
  capacity: 20,
  room: '',
  meeting_link: '',
  status: 'active',
});

const openEditModal = (c) => {
  editingClass.value = c;
  editForm.id = c.id;
  editForm.name = c.name;
  editForm.course_id = c.course?.id || '';
  editForm.teacher_id = c.primary_teacher?.id || '';
  editForm.academic_term_id = props.terms.find((t) => t.name === c.term)?.id || '';
  editForm.type = c.type;
  editForm.capacity = c.capacity;
  editForm.room = c.room || '';
  editForm.meeting_link = c.meeting_link || '';
  editForm.status = c.status || 'active';
};

const submitCreate = () => {
  createForm.post(route('classes.store'), {
    onSuccess: () => {
      showCreateModal.value = false;
      createForm.reset();
    },
  });
};

const submitEdit = () => {
  editForm.put(route('classes.update', editForm.id), {
    onSuccess: () => {
      editingClass.value = null;
    },
  });
};

const deleteClass = (id, name) => {
  if (confirm(`Are you sure you want to delete class "${name}"?`)) {
    useForm({}).delete(route('classes.destroy', id));
  }
};

// Filters
const searchQuery = ref('');
const typeFilter = ref('all');
const courseFilter = ref('all');

const filteredClasses = computed(() => {
  return props.classes.filter((c) => {
    const matchesSearch =
      c.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (c.course?.name && c.course.name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (c.primary_teacher?.name && c.primary_teacher.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
    const matchesType = typeFilter.value === 'all' || c.type === typeFilter.value;
    const matchesCourse = courseFilter.value === 'all' || c.course?.id === courseFilter.value;

    return matchesSearch && matchesType && matchesCourse;
  });
});

const getTypeBadge = (type) => {
  switch (type) {
    case 'online':
      return { label: 'Online', bg: 'bg-sky-50 text-sky-700 ring-1 ring-sky-600/20' };
    case 'hybrid':
      return { label: 'Hybrid', bg: 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20' };
    default:
      return { label: 'Physical', bg: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' };
  }
};
</script>

<template>
  <Head title="Class Groups & Management - Beyan" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8" dir="ltr">
    <div class="max-w-6xl mx-auto space-y-6">

      <!-- Breadcrumbs & Nav -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
          <Link :href="route('dashboard')" class="hover:text-emerald-700 transition">Dashboard</Link>
          <span>/</span>
          <span class="text-slate-800">Academics</span>
          <span>/</span>
          <span class="text-slate-800 font-bold">Classes</span>
        </div>
        <Link
          :href="route('dashboard')"
          class="text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-1"
        >
          &larr; Back to Dashboard
        </Link>
      </div>

      <!-- Header Card -->
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-sm">🏫</span>
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Academic Structure</span>
          </div>
          <h1 class="text-2xl font-black text-slate-900 mt-1">Class Groups & Halaqas</h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Configure learning groups, assign primary teachers, track classroom capacity, and manage schedules.
          </p>
        </div>

        <div class="flex items-center gap-2 self-start md:self-auto">
          <Link
            :href="route('courses.index')"
            class="px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-1.5"
          >
            <span>📚</span>
            <span>Manage Courses</span>
          </Link>
          <button
            type="button"
            @click="showCreateModal = true"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center gap-1.5"
          >
            <span>+</span>
            <span>Create New Class</span>
          </button>
        </div>
      </div>

      <!-- Bento Metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Classes</div>
          <div class="text-2xl font-black text-slate-900 mt-1">{{ classes.length }}</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Configured learning groups</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Physical Campus</div>
          <div class="text-2xl font-black text-emerald-700 mt-1">
            {{ classes.filter((c) => c.type === 'physical').length }}
          </div>
          <div class="text-[11px] text-emerald-600 mt-0.5">On-site classrooms</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Online & Hybrid</div>
          <div class="text-2xl font-black text-sky-700 mt-1">
            {{ classes.filter((c) => c.type === 'online' || c.type === 'hybrid').length }}
          </div>
          <div class="text-[11px] text-sky-600 mt-0.5">Remote live halaqas</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Enrolled</div>
          <div class="text-2xl font-black text-slate-900 mt-1">
            {{ classes.reduce((sum, c) => sum + (c.enrolled_count || 0), 0) }}
          </div>
          <div class="text-[11px] text-slate-500 mt-0.5">Active student placements</div>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="relative flex-1 max-w-sm">
          <span class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-slate-400 text-xs">
            🔍
          </span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search class, course, or teacher..."
            class="w-full ps-8 pe-3 py-1.5 text-xs rounded-xl border border-slate-300 bg-white focus:ring-emerald-500 focus:border-emerald-500"
          />
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
          <div class="flex items-center gap-1.5">
            <label class="font-semibold text-slate-500">Course:</label>
            <select
              v-model="courseFilter"
              class="font-bold text-slate-700 bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-1.5 focus:ring-emerald-500 focus:border-emerald-500"
            >
              <option value="all">All Courses</option>
              <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div class="flex items-center gap-1.5">
            <label class="font-semibold text-slate-500">Type:</label>
            <select
              v-model="typeFilter"
              class="font-bold text-slate-700 bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-1.5 focus:ring-emerald-500 focus:border-emerald-500"
            >
              <option value="all">All Types</option>
              <option value="physical">Physical</option>
              <option value="online">Online</option>
              <option value="hybrid">Hybrid</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Classes Grid -->
      <div v-if="filteredClasses.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="c in filteredClasses"
          :key="c.id"
          class="bg-white rounded-2xl shadow-sm border border-slate-200 hover:border-emerald-300 transition p-5 flex flex-col justify-between space-y-4"
        >
          <!-- Top Row: Course Code & Type Badge -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-mono font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                {{ c.course?.code || 'ACAD' }}
              </span>
              <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold uppercase', getTypeBadge(c.type).bg]">
                {{ getTypeBadge(c.type).label }}
              </span>
            </div>

            <!-- Class Name & Course -->
            <div>
              <h2 class="text-base font-bold text-slate-900 leading-snug">{{ c.name }}</h2>
              <div class="text-xs text-emerald-800 font-semibold mt-0.5">{{ c.course?.name || 'General Course' }}</div>
            </div>
          </div>

          <!-- Middle Details: Teacher, Location / Link, Capacity -->
          <div class="space-y-2.5 text-xs text-slate-600 border-t border-slate-100 pt-3">
            <div class="flex items-center gap-2">
              <span class="text-slate-400">👤 Teacher:</span>
              <span class="font-bold text-slate-800">{{ c.primary_teacher?.name || 'Unassigned' }}</span>
            </div>

            <div v-if="c.room" class="flex items-center gap-2">
              <span class="text-slate-400">📍 Room:</span>
              <span class="font-medium text-slate-700">{{ c.room }}</span>
            </div>

            <div v-if="c.meeting_link" class="flex items-center gap-2">
              <span class="text-slate-400">🔗 Link:</span>
              <a :href="c.meeting_link" target="_blank" class="font-bold text-sky-600 hover:underline truncate max-w-[200px]">
                {{ c.meeting_link }}
              </a>
            </div>

            <!-- Capacity Progress Bar -->
            <div class="space-y-1 pt-1">
              <div class="flex justify-between items-center text-[11px]">
                <span class="text-slate-500 font-medium">Enrollment Capacity</span>
                <span class="font-bold text-slate-800">{{ c.enrolled_count }} / {{ c.capacity }} students</span>
              </div>
              <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div
                  class="h-2 rounded-full transition-all duration-300"
                  :class="c.enrolled_count >= c.capacity ? 'bg-amber-500' : 'bg-emerald-600'"
                  :style="{ width: `${Math.min(100, (c.enrolled_count / (c.capacity || 1)) * 100)}%` }"
                ></div>
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
            <Link
              :href="route('students.index', { class: c.name })"
              class="font-bold text-emerald-700 hover:text-emerald-900 transition"
            >
              View Roster &rarr;
            </Link>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="openEditModal(c)"
                class="px-2.5 py-1 text-slate-600 hover:text-slate-900 font-semibold bg-slate-100 hover:bg-slate-200 rounded-lg transition"
              >
                Edit
              </button>
              <button
                type="button"
                @click="deleteClass(c.id, c.name)"
                class="px-2.5 py-1 text-red-600 hover:text-red-800 font-semibold hover:bg-red-50 rounded-lg transition"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="bg-white p-12 text-center rounded-2xl shadow-sm border border-slate-200 space-y-3"
      >
        <div class="text-4xl">🏫</div>
        <h3 class="text-base font-bold text-slate-900">No Class Groups Found</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          {{ searchQuery || typeFilter !== 'all' || courseFilter !== 'all' ? 'No classes matched your filter criteria.' : 'Create your first class group to assign teachers and enroll students.' }}
        </p>
        <div class="pt-2">
          <button
            type="button"
            @click="showCreateModal = true"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition"
          >
            + Create New Class
          </button>
        </div>
      </div>

    </div>

    <!-- MODAL: Create Class -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4 border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center pb-2 border-b border-slate-100">
          <div>
            <h3 class="text-base font-bold text-slate-900">Create New Class Group</h3>
            <p class="text-xs text-slate-500">Add a course halaqah and assign a teacher</p>
          </div>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form @submit.prevent="submitCreate" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Class / Halaqah Name *</label>
            <input
              v-model="createForm.name"
              type="text"
              required
              placeholder="e.g. Hifz Morning Halaqah A"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
            />
            <div v-if="createForm.errors.name" class="text-red-600 text-[11px] mt-1">{{ createForm.errors.name }}</div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block font-bold text-slate-700">Associated Course *</label>
                <Link :href="route('courses.index')" class="text-[11px] font-bold text-emerald-700 hover:underline">+ New Course</Link>
              </div>
              <select
                v-model="createForm.course_id"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white font-semibold focus:ring-emerald-500 focus:border-emerald-500"
              >
                <option value="" disabled>Select a course</option>
                <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }} ({{ c.code }})</option>
              </select>
              <p v-if="courses.length === 0" class="text-amber-700 text-[11px] mt-1 font-medium">
                No active courses found. <Link :href="route('courses.index')" class="underline font-bold">Add a course first</Link>.
              </p>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Primary Teacher</label>
              <select
                v-model="createForm.teacher_id"
                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white font-semibold focus:ring-emerald-500 focus:border-emerald-500"
              >
                <option value="">Unassigned</option>
                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.full_name }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Class Type *</label>
              <select
                v-model="createForm.type"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white font-semibold focus:ring-emerald-500 focus:border-emerald-500"
              >
                <option value="physical">Physical (Campus Room)</option>
                <option value="online">Online (Virtual Meeting)</option>
                <option value="hybrid">Hybrid (Both)</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Student Capacity *</label>
              <input
                v-model.number="createForm.capacity"
                type="number"
                min="1"
                max="500"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500 font-mono font-bold"
              />
            </div>
          </div>

          <div v-if="createForm.type === 'physical' || createForm.type === 'hybrid'">
            <label class="block font-bold text-slate-700 mb-1">Room / Location</label>
            <input
              v-model="createForm.room"
              type="text"
              placeholder="e.g. Room 102, Ground Floor"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>

          <div v-if="createForm.type === 'online' || createForm.type === 'hybrid'">
            <label class="block font-bold text-slate-700 mb-1">Meeting Link (Google Meet / Zoom)</label>
            <input
              v-model="createForm.meeting_link"
              type="url"
              placeholder="https://meet.google.com/xyz-abc-def"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 border border-slate-300 rounded-xl text-slate-700 font-bold hover:bg-slate-50 transition"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="createForm.processing"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition shadow"
            >
              {{ createForm.processing ? 'Saving...' : 'Create Class' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: Edit Class -->
    <div
      v-if="editingClass"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4 border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center pb-2 border-b border-slate-100">
          <div>
            <h3 class="text-base font-bold text-slate-900">Edit Class Group</h3>
            <p class="text-xs text-slate-500">Update class settings and teacher assignments</p>
          </div>
          <button @click="editingClass = null" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form @submit.prevent="submitEdit" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Class / Halaqah Name *</label>
            <input
              v-model="editForm.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-emerald-500 focus:border-emerald-500 font-bold"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Course *</label>
              <select
                v-model="editForm.course_id"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white font-semibold focus:ring-emerald-500 focus:border-emerald-500"
              >
                <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Primary Teacher</label>
              <select
                v-model="editForm.teacher_id"
                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white font-semibold focus:ring-emerald-500 focus:border-emerald-500"
              >
                <option value="">Unassigned</option>
                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.full_name }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Class Type *</label>
              <select
                v-model="editForm.type"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white font-semibold focus:ring-emerald-500 focus:border-emerald-500"
              >
                <option value="physical">Physical</option>
                <option value="online">Online</option>
                <option value="hybrid">Hybrid</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Capacity *</label>
              <input
                v-model.number="editForm.capacity"
                type="number"
                min="1"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono font-bold"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Status *</label>
              <select
                v-model="editForm.status"
                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white font-semibold focus:ring-emerald-500 focus:border-emerald-500"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>

          <div v-if="editForm.type === 'physical' || editForm.type === 'hybrid'">
            <label class="block font-bold text-slate-700 mb-1">Room / Location</label>
            <input
              v-model="editForm.room"
              type="text"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl"
            />
          </div>

          <div v-if="editForm.type === 'online' || editForm.type === 'hybrid'">
            <label class="block font-bold text-slate-700 mb-1">Meeting Link</label>
            <input
              v-model="editForm.meeting_link"
              type="url"
              class="w-full px-3 py-2 border border-slate-300 rounded-xl"
            />
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button
              type="button"
              @click="editingClass = null"
              class="px-4 py-2 border border-slate-300 rounded-xl text-slate-700 font-bold hover:bg-slate-50 transition"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="editForm.processing"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition shadow"
            >
              {{ editForm.processing ? 'Saving...' : 'Update Class' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>
