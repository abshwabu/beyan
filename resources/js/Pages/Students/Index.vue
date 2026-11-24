<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  students: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const page = usePage();
const isRtl = computed(() => page.props.i18n?.direction === 'rtl');

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const className = ref(props.filters.class || '');
const isSearching = ref(false);

let searchDebounceTimer = null;

const applyFilters = () => {
  isSearching.value = true;
  router.get(
    route('students.index'),
    {
      search: search.value || undefined,
      status: status.value || undefined,
      class: className.value || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onFinish: () => {
        isSearching.value = false;
      },
    }
  );
};

const clearFilters = () => {
  search.value = '';
  status.value = '';
  className.value = '';
  applyFilters();
};

const hasActiveFilters = computed(() => {
  return Boolean(search.value || status.value || className.value);
});

watch(search, () => {
  clearTimeout(searchDebounceTimer);
  isSearching.value = true;
  searchDebounceTimer = setTimeout(applyFilters, 300);
});

watch([status, className], applyFilters);

const getStatusBadge = (s) => {
  switch (s) {
    case 'active':
      return {
        label: 'Active',
        classes: 'bg-emerald-50 text-emerald-800 border-emerald-200/90 ring-emerald-600/10',
        dot: 'bg-emerald-500',
      };
    case 'inactive':
      return {
        label: 'Inactive',
        classes: 'bg-slate-50 text-slate-700 border-slate-200 ring-slate-600/10',
        dot: 'bg-slate-400',
      };
    case 'suspended':
      return {
        label: 'Suspended',
        classes: 'bg-amber-50 text-amber-800 border-amber-200 ring-amber-600/10',
        dot: 'bg-amber-500',
      };
    case 'withdrawn':
      return {
        label: 'Withdrawn',
        classes: 'bg-rose-50 text-rose-800 border-rose-200 ring-rose-600/10',
        dot: 'bg-rose-500',
      };
    case 'graduated':
      return {
        label: 'Graduated',
        classes: 'bg-blue-50 text-blue-800 border-blue-200 ring-blue-600/10',
        dot: 'bg-blue-500',
      };
    default:
      return {
        label: s || 'Unknown',
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
</script>

<template>
  <Head title="Students Directory - Beyan" />

  <div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8" :dir="isRtl ? 'rtl' : 'ltr'">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Breadcrumbs & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1.5">
            <Link :href="route('dashboard')" class="hover:text-emerald-700 transition-colors">
              Dashboard
            </Link>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800 font-semibold">Students Directory</span>
          </nav>

          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
              Students
            </h1>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
              {{ students.total }} Registered
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
            Manage madrasa student admissions, class placements, parent contacts, and progress.
          </p>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex items-center gap-2.5 self-start sm:self-auto flex-wrap">
          <Link
            :href="route('hifz.fast-entry')"
            class="inline-flex items-center gap-1.5 rounded-xl bg-white border border-slate-200/90 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition-all cursor-pointer"
          >
            <span class="text-emerald-600">⚡</span>
            <span>Fast Qur'an Log</span>
          </Link>

          <Link
            :href="route('students.create')"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-xs hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 transition-all cursor-pointer transform active:scale-[0.98]"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Register Student</span>
          </Link>
        </div>
      </div>

      <!-- Search & Filters Card -->
      <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">

          <!-- Search by Name or ID -->
          <div class="sm:col-span-5">
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
              Search Student
            </label>
            <div class="relative rounded-lg shadow-xs">
              <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg v-if="!isSearching" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <svg v-else class="h-4 w-4 text-emerald-600 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
              </div>
              <input
                v-model="search"
                type="text"
                placeholder="Search by student name or code (e.g. BYN-2026-0001)..."
                class="block w-full rounded-lg border border-slate-300 text-sm ps-9 pe-9 py-2.5 outline-none transition-all focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-slate-50/40"
              />
              <button
                v-if="search"
                type="button"
                @click="search = ''; applyFilters();"
                class="absolute inset-y-0 end-0 flex items-center pe-3 text-slate-400 hover:text-slate-600 cursor-pointer"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Filter by Status -->
          <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
              Enrollment Status
            </label>
            <div class="relative rounded-lg shadow-xs">
              <select
                v-model="status"
                class="block w-full rounded-lg border border-slate-300 text-sm px-3.5 py-2.5 outline-none transition-all focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-slate-50/40 font-medium text-slate-800"
              >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
                <option value="withdrawn">Withdrawn</option>
                <option value="graduated">Graduated</option>
              </select>
            </div>
          </div>

          <!-- Filter by Class -->
          <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
              Class / Level
            </label>
            <div class="flex items-center gap-2">
              <div class="relative flex-1 rounded-lg shadow-xs">
                <input
                  v-model="className"
                  type="text"
                  placeholder="Filter by class (e.g. Hifz Level 1)..."
                  class="block w-full rounded-lg border border-slate-300 text-sm px-3.5 py-2.5 outline-none transition-all focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-slate-50/40"
                />
              </div>

              <button
                v-if="hasActiveFilters"
                type="button"
                @click="clearFilters"
                class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-rose-600 px-3 py-2.5 rounded-lg border border-slate-200 hover:border-rose-200 hover:bg-rose-50/40 transition cursor-pointer shrink-0"
                title="Reset all active filters"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Reset</span>
              </button>
            </div>
          </div>

        </div>

        <!-- Filter context summary pill -->
        <div v-if="hasActiveFilters" class="flex items-center gap-2 pt-2 border-t border-slate-100 text-xs text-slate-500">
          <span>Active filter applied:</span>
          <span v-if="search" class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium">"{{ search }}"</span>
          <span v-if="status" class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium capitalize">{{ status }}</span>
          <span v-if="className" class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium">{{ className }}</span>
          <span class="text-slate-400 ms-auto">Found {{ students.total }} students</span>
        </div>
      </div>

      <!-- Students Table Card -->
      <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-start text-sm">
            <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              <tr>
                <th class="px-6 py-3.5 text-start">Student Name</th>
                <th class="px-6 py-3.5 text-start">ID Code</th>
                <th class="px-6 py-3.5 text-start">Status</th>
                <th class="px-6 py-3.5 text-start">Current Class</th>
                <th class="px-6 py-3.5 text-start">Primary Guardian</th>
                <th class="px-6 py-3.5 text-end">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
              <tr
                v-for="student in students.data"
                :key="student.id"
                class="hover:bg-slate-50/70 transition-colors group"
              >
                <!-- Student Avatar & Name -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 border"
                      :class="student.gender === 'female'
                        ? 'bg-rose-50 text-rose-700 border-rose-200'
                        : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                    >
                      {{ getInitials(student.full_name || student.name) }}
                    </div>

                    <div>
                      <Link
                        :href="route('students.show', student.id)"
                        class="font-semibold text-slate-900 group-hover:text-emerald-700 transition-colors block text-sm"
                      >
                        {{ student.full_name || student.name }}
                      </Link>
                      <div class="text-xs text-slate-400 flex items-center gap-1.5 capitalize mt-0.5">
                        <span>{{ student.gender || 'student' }}</span>
                        <span v-if="student.date_of_birth" class="text-slate-300">•</span>
                        <span v-if="student.date_of_birth">{{ student.date_of_birth }}</span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- ID Code -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center font-mono text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                    {{ student.student_code || student.admission_number || 'BYN-PENDING' }}
                  </span>
                </td>

                <!-- Status Badge -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border',
                      getStatusBadge(student.status).classes
                    ]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="getStatusBadge(student.status).dot" />
                    {{ getStatusBadge(student.status).label }}
                  </span>
                </td>

                <!-- Current Class -->
                <td class="px-6 py-4 whitespace-nowrap text-slate-700 text-xs sm:text-sm">
                  <span
                    v-if="student.current_enrollment?.class_name"
                    class="font-medium text-slate-800"
                  >
                    {{ student.current_enrollment.class_name }}
                  </span>
                  <span v-else class="text-slate-400 italic text-xs">
                    Unassigned
                  </span>
                </td>

                <!-- Primary Guardian -->
                <td class="px-6 py-4 whitespace-nowrap text-xs sm:text-sm">
                  <div v-if="student.guardians && student.guardians.length > 0">
                    <div class="font-medium text-slate-900 flex items-center gap-1.5">
                      <span>{{ student.guardians[0].full_name }}</span>
                      <span
                        v-if="student.guardians[0].relationship_type"
                        class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-semibold border border-slate-200/60"
                      >
                        {{ student.guardians[0].relationship_type }}
                      </span>
                    </div>
                    <span v-if="student.guardians[0].phone" class="text-xs text-slate-500 font-mono block mt-0.5">
                      {{ student.guardians[0].phone }}
                    </span>
                    <span v-else-if="student.guardians[0].email" class="text-xs text-slate-500 block mt-0.5">
                      {{ student.guardians[0].email }}
                    </span>
                  </div>
                  <span v-else class="text-slate-400 italic text-xs">No guardian linked</span>
                </td>

                <!-- Actions -->
                <td class="px-6 py-4 whitespace-nowrap text-end text-sm">
                  <div class="flex items-center justify-end gap-1.5">
                    <Link
                      :href="route('hifz.fast-entry', { student_id: student.id })"
                      class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100/80 px-2.5 py-1.5 rounded-lg border border-emerald-200 transition-colors"
                      title="Quick Qur'an entry for this student"
                    >
                      <span>⚡</span>
                      <span class="hidden sm:inline">Hifz</span>
                    </Link>

                    <Link
                      :href="route('students.show', student.id)"
                      class="inline-flex items-center text-xs font-semibold text-slate-700 hover:text-slate-900 bg-white hover:bg-slate-100 px-2.5 py-1.5 rounded-lg border border-slate-200 transition-colors"
                    >
                      View
                    </Link>

                    <Link
                      :href="route('students.edit', student.id)"
                      class="inline-flex items-center text-xs font-semibold text-slate-600 hover:text-slate-900 px-2 py-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                      title="Edit student profile"
                    >
                      Edit
                    </Link>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="!students.data || students.data.length === 0">
                <td colspan="6" class="px-6 py-16 text-center">
                  <div class="max-w-sm mx-auto space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                      🔍
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-slate-900">No students found</h3>
                      <p class="text-xs text-slate-500 mt-1">
                        We couldn't find any students matching your search criteria or active filters.
                      </p>
                    </div>
                    <div class="pt-2 flex items-center justify-center gap-2">
                      <button
                        v-if="hasActiveFilters"
                        type="button"
                        @click="clearFilters"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                      >
                        Clear Filters
                      </button>
                      <Link
                        :href="route('students.create')"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-500 shadow-2xs transition"
                      >
                        + Register New Student
                      </Link>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div
          v-if="students.links && students.links.length > 3"
          class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-t border-slate-200 bg-white px-5 py-3.5 gap-3"
        >
          <div class="text-xs font-medium text-slate-500">
            Showing <strong class="text-slate-800">{{ students.from || 0 }}</strong> to <strong class="text-slate-800">{{ students.to || 0 }}</strong> of <strong class="text-slate-800">{{ students.total }}</strong> students
          </div>

          <div class="flex items-center gap-1 flex-wrap">
            <template v-for="(link, key) in students.links" :key="key">
              <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                :class="[
                  'px-3 py-1.5 text-xs rounded-lg border font-semibold transition-all',
                  link.active
                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:text-slate-900'
                ]"
              />
              <span
                v-else
                v-html="link.label"
                class="px-3 py-1.5 text-xs rounded-lg border border-slate-200/60 text-slate-300 cursor-not-allowed select-none"
              />
            </template>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
