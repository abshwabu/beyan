<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  courses: {
    type: Array,
    required: true,
  },
});

const page = usePage();

// Filter & Search states
const searchQuery = ref('');
const selectedLevel = ref('all');
const selectedStatus = ref('all');

// Modals
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingCourse = ref(null);
const courseToDelete = ref(null);

// Forms
const createForm = useForm({
  name: '',
  code: '',
  level: 'all_levels',
  duration: '',
  learning_objectives: '',
  description: '',
  status: 'active',
});

const editForm = useForm({
  name: '',
  code: '',
  level: 'all_levels',
  duration: '',
  learning_objectives: '',
  description: '',
  status: 'active',
});

// Computed filtering
const filteredCourses = computed(() => {
  return props.courses.filter((c) => {
    const matchesSearch =
      searchQuery.value === '' ||
      c.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      c.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (c.description && c.description.toLowerCase().includes(searchQuery.value.toLowerCase()));

    const matchesLevel =
      selectedLevel.value === 'all' || c.level === selectedLevel.value;

    const matchesStatus =
      selectedStatus.value === 'all' || c.status === selectedStatus.value;

    return matchesSearch && matchesLevel && matchesStatus;
  });
});

// Summary metrics
const totalCourses = computed(() => props.courses.length);
const activeCourses = computed(() => props.courses.filter((c) => c.status === 'active').length);
const totalLinkedClasses = computed(() =>
  props.courses.reduce((sum, c) => sum + (c.class_groups_count || 0), 0)
);

// Actions
const openCreateModal = () => {
  createForm.reset();
  createForm.clearErrors();
  showCreateModal.value = true;
};

const submitCreateCourse = () => {
  createForm.post(route('courses.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false;
      createForm.reset();
    },
  });
};

const openEditModal = (course) => {
  editingCourse.value = course;
  editForm.name = course.name;
  editForm.code = course.code;
  editForm.level = course.level;
  editForm.duration = course.duration || '';
  editForm.learning_objectives = Array.isArray(course.learning_objectives)
    ? course.learning_objectives.join('\n')
    : (course.learning_objectives || '');
  editForm.description = course.description || '';
  editForm.status = course.status;
  editForm.clearErrors();
  showEditModal.value = true;
};

const submitEditCourse = () => {
  if (!editingCourse.value) return;

  editForm.put(route('courses.update', editingCourse.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
      editingCourse.value = null;
    },
  });
};

const confirmDeleteCourse = (course) => {
  courseToDelete.value = course;
};

const executeDeleteCourse = () => {
  if (!courseToDelete.value) return;

  router.delete(route('courses.destroy', courseToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      courseToDelete.value = null;
    },
  });
};

const getLevelBadge = (level) => {
  switch (level) {
    case 'beginner':
      return { label: 'Beginner', classes: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-600/20' };
    case 'intermediate':
      return { label: 'Intermediate', classes: 'bg-amber-50 text-amber-800 ring-1 ring-amber-600/20' };
    case 'advanced':
      return { label: 'Advanced', classes: 'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-600/20' };
    default:
      return { label: 'All Levels', classes: 'bg-slate-50 text-slate-700 ring-1 ring-slate-600/20' };
  }
};
</script>

<template>
  <Head title="Courses & Curriculum - Beyan" />

  <div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8" dir="ltr">
    <div class="max-w-6xl mx-auto space-y-6">

      <!-- Breadcrumbs & Nav -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
          <Link :href="route('dashboard')" class="hover:text-emerald-700 transition">Dashboard</Link>
          <span class="text-slate-300">/</span>
          <span class="text-slate-600">Academics</span>
          <span class="text-slate-300">/</span>
          <span class="text-slate-900 font-bold">Courses</span>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('classes.index')"
            class="text-xs font-bold text-emerald-700 hover:text-emerald-800 transition flex items-center gap-1"
          >
            🏫 Manage Classes &rarr;
          </Link>
          <span class="text-slate-300">|</span>
          <Link
            :href="route('dashboard')"
            class="text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-1"
          >
            &larr; Back to Dashboard
          </Link>
        </div>
      </div>

      <!-- Flash Notification -->
      <div
        v-if="page.props.flash?.success"
        class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200/90 text-emerald-800 text-xs font-bold flex items-center justify-between"
      >
        <span>✓ {{ page.props.flash.success }}</span>
      </div>
      <div
        v-if="page.props.flash?.error"
        class="p-4 rounded-2xl bg-rose-50 border border-rose-200/90 text-rose-800 text-xs font-bold flex items-center justify-between"
      >
        <span>✕ {{ page.props.flash.error }}</span>
      </div>

      <!-- Header Card -->
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/90 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-sm">📚</span>
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Curriculum & Subjects</span>
          </div>
          <h1 class="text-2xl font-black text-slate-900 mt-1">Academic Courses</h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Define curriculum subjects, proficiency levels, duration, and learning objectives for your madrasa halaqas.
          </p>
        </div>

        <button
          type="button"
          @click="openCreateModal"
          class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5 self-start md:self-auto"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
          </svg>
          + Add New Course
        </button>
      </div>

      <!-- Summary Metrics Bar -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex items-center justify-between">
          <div>
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Courses</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ totalCourses }}</div>
          </div>
          <span class="p-3 bg-slate-50 rounded-xl text-xl">📖</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex items-center justify-between">
          <div>
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Curriculum</div>
            <div class="text-2xl font-black text-emerald-700 mt-1">{{ activeCourses }}</div>
          </div>
          <span class="p-3 bg-emerald-50 rounded-xl text-xl">✓</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex items-center justify-between">
          <div>
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Classes</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ totalLinkedClasses }}</div>
          </div>
          <span class="p-3 bg-indigo-50 rounded-xl text-xl">🏫</span>
        </div>
      </div>

      <!-- Filter & Search Toolbar -->
      <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200/90 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 text-xs">
        <div class="relative flex-1">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by course name, code, or description..."
            class="w-full pl-9 pr-4 py-2 rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500"
          />
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <select
            v-model="selectedLevel"
            class="py-2 px-3 rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 font-medium"
          >
            <option value="all">All Levels</option>
            <option value="beginner">Beginner</option>
            <option value="intermediate">Intermediate</option>
            <option value="advanced">Advanced</option>
            <option value="all_levels">All Levels Tier</option>
          </select>

          <select
            v-model="selectedStatus"
            class="py-2 px-3 rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 font-medium"
          >
            <option value="all">All Statuses</option>
            <option value="active">Active Only</option>
            <option value="inactive">Inactive</option>
            <option value="archived">Archived</option>
          </select>
        </div>
      </div>

      <!-- Courses Grid -->
      <div v-if="filteredCourses.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="course in filteredCourses"
          :key="course.id"
          class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-sm hover:shadow transition-shadow flex flex-col justify-between space-y-4"
        >
          <!-- Top Row: Code, Name, Status -->
          <div class="space-y-2">
            <div class="flex items-center justify-between gap-2">
              <span class="font-mono text-xs font-black bg-slate-100 text-slate-800 px-2.5 py-1 rounded-lg border border-slate-200">
                {{ course.code }}
              </span>
              <div class="flex items-center gap-2">
                <span
                  :class="[
                    'px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase',
                    getLevelBadge(course.level).classes
                  ]"
                >
                  {{ getLevelBadge(course.level).label }}
                </span>
                <span
                  :class="[
                    'px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase',
                    course.status === 'active' ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-600/20' : 'bg-slate-100 text-slate-600'
                  ]"
                >
                  {{ course.status }}
                </span>
              </div>
            </div>

            <div>
              <h2 class="text-base font-bold text-slate-900">{{ course.name }}</h2>
              <p v-if="course.description" class="text-xs text-slate-600 mt-1 line-clamp-2">
                {{ course.description }}
              </p>
            </div>

            <!-- Meta: Duration & Classes Count -->
            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 pt-1">
              <span v-if="course.duration" class="flex items-center gap-1 font-medium text-slate-700">
                <span>⏱</span> {{ course.duration }}
              </span>
              <span class="flex items-center gap-1 font-semibold text-emerald-700">
                <span>🏫</span> {{ course.class_groups_count }} {{ course.class_groups_count === 1 ? 'Class' : 'Classes' }}
              </span>
            </div>

            <!-- Learning Objectives Tags -->
            <div v-if="course.learning_objectives && course.learning_objectives.length > 0" class="pt-2">
              <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Objectives:</div>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="(obj, idx) in course.learning_objectives.slice(0, 3)"
                  :key="idx"
                  class="px-2 py-0.5 bg-slate-50 text-slate-700 rounded-md text-[11px] border border-slate-200/80 font-medium"
                >
                  • {{ obj }}
                </span>
                <span
                  v-if="course.learning_objectives.length > 3"
                  class="text-[10px] text-slate-400 font-semibold self-center"
                >
                  +{{ course.learning_objectives.length - 3 }} more
                </span>
              </div>
            </div>
          </div>

          <!-- Bottom Actions -->
          <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-[11px] text-slate-400">Added {{ course.created_at }}</span>
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="openEditModal(course)"
                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 active:scale-[0.98] text-slate-700 font-bold rounded-lg transition-all"
              >
                Edit
              </button>
              <button
                type="button"
                @click="confirmDeleteCourse(course)"
                class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 active:scale-[0.98] text-rose-700 font-bold rounded-lg transition-all"
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
        class="bg-white rounded-2xl p-12 text-center border border-slate-200/90 shadow-sm space-y-4"
      >
        <span class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl text-3xl inline-block">📚</span>
        <div class="max-w-md mx-auto">
          <h3 class="text-base font-bold text-slate-900">No courses found</h3>
          <p class="text-xs text-slate-500 mt-1">
            {{ searchQuery || selectedLevel !== 'all' || selectedStatus !== 'all' ? 'Try adjusting your search criteria or filters.' : 'Get started by creating your first academic course (e.g. Hifz Memorization, Tajweed, Arabic Grammar).' }}
          </p>
        </div>
        <button
          type="button"
          @click="openCreateModal"
          class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white font-bold text-xs rounded-xl shadow-sm transition-all"
        >
          + Add Your First Course
        </button>
      </div>

    </div>

    <!-- Create Course Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-bold text-slate-900">Add New Academic Course</h3>
          <button
            type="button"
            @click="showCreateModal = false"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold"
          >
            ✕
          </button>
        </div>

        <form @submit.prevent="submitCreateCourse" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
              <label class="block text-xs font-bold text-slate-700">Course Name *</label>
              <input
                v-model="createForm.name"
                required
                type="text"
                placeholder="e.g. Full Qur'an Memorization (Hifz)"
                class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
              />
              <p v-if="createForm.errors.name" class="text-rose-600 text-[11px] mt-1">{{ createForm.errors.name }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700">Course Code *</label>
              <input
                v-model="createForm.code"
                required
                type="text"
                placeholder="e.g. HIFZ-01"
                class="mt-1 block w-full rounded-xl border-slate-300 text-sm uppercase focus:border-emerald-500 focus:ring-emerald-500"
              />
              <p v-if="createForm.errors.code" class="text-rose-600 text-[11px] mt-1">{{ createForm.errors.code }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700">Level *</label>
              <select
                v-model="createForm.level"
                class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 font-medium"
              >
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
                <option value="all_levels">All Levels</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700">Duration</label>
              <input
                v-model="createForm.duration"
                type="text"
                placeholder="e.g. 1 Year, 6 Months"
                class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700">Status *</label>
              <select
                v-model="createForm.status"
                class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 font-medium"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700">Learning Objectives (One per line)</label>
            <textarea
              v-model="createForm.learning_objectives"
              rows="3"
              placeholder="e.g.
Complete memorization of 30 Juz
Master foundational tajweed rules
Daily revision of Sabqi and Manzil"
              class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700">Course Description</label>
            <textarea
              v-model="createForm.description"
              rows="2"
              placeholder="Comprehensive curriculum syllabus details, prerequisites, and materials..."
              class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            ></textarea>
          </div>

          <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="createForm.processing"
              class="px-5 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 active:scale-[0.98] transition-all shadow-sm disabled:opacity-50"
            >
              {{ createForm.processing ? 'Saving...' : 'Save Course' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Course Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-bold text-slate-900">Edit Academic Course</h3>
          <button
            type="button"
            @click="showEditModal = false"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold"
          >
            ✕
          </button>
        </div>

        <form @submit.prevent="submitEditCourse" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
              <label class="block text-xs font-bold text-slate-700">Course Name *</label>
              <input
                v-model="editForm.name"
                required
                type="text"
                class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
              />
              <p v-if="editForm.errors.name" class="text-rose-600 text-[11px] mt-1">{{ editForm.errors.name }}</p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700">Course Code *</label>
              <input
                v-model="editForm.code"
                required
                type="text"
                class="mt-1 block w-full rounded-xl border-slate-300 text-sm uppercase focus:border-emerald-500 focus:ring-emerald-500"
              />
              <p v-if="editForm.errors.code" class="text-rose-600 text-[11px] mt-1">{{ editForm.errors.code }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700">Level *</label>
              <select
                v-model="editForm.level"
                class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 font-medium"
              >
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
                <option value="all_levels">All Levels</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700">Duration</label>
              <input
                v-model="editForm.duration"
                type="text"
                class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700">Status *</label>
              <select
                v-model="editForm.status"
                class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 font-medium"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700">Learning Objectives (One per line)</label>
            <textarea
              v-model="editForm.learning_objectives"
              rows="3"
              class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700">Course Description</label>
            <textarea
              v-model="editForm.description"
              rows="2"
              class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            ></textarea>
          </div>

          <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showEditModal = false"
              class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="editForm.processing"
              class="px-5 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 active:scale-[0.98] transition-all shadow-sm disabled:opacity-50"
            >
              {{ editForm.processing ? 'Saving...' : 'Update Course' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="courseToDelete"
      class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center gap-3">
          <span class="p-2.5 bg-rose-50 text-rose-700 rounded-xl text-lg font-bold">⚠️</span>
          <div>
            <h3 class="text-base font-bold text-slate-900">Delete Course</h3>
            <p class="text-xs text-slate-500 mt-0.5">Are you sure you want to delete this course?</p>
          </div>
        </div>

        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1">
          <div class="font-bold text-slate-900">{{ courseToDelete.name }} ({{ courseToDelete.code }})</div>
          <div class="text-slate-600">
            Linked classes: <strong class="text-slate-800">{{ courseToDelete.class_groups_count }}</strong>
          </div>
        </div>

        <p v-if="courseToDelete.class_groups_count > 0" class="text-xs text-rose-600 font-medium">
          Note: This course cannot be deleted because it is assigned to {{ courseToDelete.class_groups_count }} class group(s). You must remove or reassign the classes first.
        </p>

        <div class="flex justify-end space-x-2 pt-2">
          <button
            type="button"
            @click="courseToDelete = null"
            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors"
          >
            Cancel
          </button>
          <button
            type="button"
            :disabled="courseToDelete.class_groups_count > 0"
            @click="executeDeleteCourse"
            class="px-4 py-2 text-xs font-bold rounded-xl bg-rose-600 text-white hover:bg-rose-700 active:scale-[0.98] transition-all shadow-sm disabled:opacity-50"
          >
            Confirm Delete
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
