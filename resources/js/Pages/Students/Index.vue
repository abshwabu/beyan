<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

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

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const className = ref(props.filters.class || '');

let searchDebounceTimer = null;

const applyFilters = () => {
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
    }
  );
};

watch(search, () => {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(applyFilters, 300);
});

watch([status, className], applyFilters);

const getStatusBadgeClass = (s) => {
  switch (s) {
    case 'active':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'inactive':
      return 'bg-gray-50 text-gray-700 ring-gray-600/20';
    case 'suspended':
      return 'bg-amber-50 text-amber-700 ring-amber-600/20';
    case 'withdrawn':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    case 'graduated':
      return 'bg-blue-50 text-blue-700 ring-blue-600/20';
    default:
      return 'bg-gray-50 text-gray-700 ring-gray-600/20';
  }
};
</script>

<template>
  <Head title="Students Directory - Beyan" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">Students</h1>
          <p class="text-sm text-slate-500">Manage student admissions, profiles, and enrollments.</p>
        </div>
        <div>
          <Link
            :href="route('students.create')"
            class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 transition"
          >
            + Register Student
          </Link>
        </div>
      </div>

      <!-- Filters & Search Bar -->
      <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Search Name / ID</label>
          <input
            v-model="search"
            type="text"
            placeholder="e.g. BYN-2026-0001 or Bilal"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Status</label>
          <select
            v-model="status"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
            <option value="withdrawn">Withdrawn</option>
            <option value="graduated">Graduated</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Class</label>
          <input
            v-model="className"
            type="text"
            placeholder="e.g. Hifz Level 1"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
          />
        </div>
      </div>

      <!-- Table / Card List -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
              <tr>
                <th class="px-6 py-3.5">Student</th>
                <th class="px-6 py-3.5">ID Code</th>
                <th class="px-6 py-3.5">Status</th>
                <th class="px-6 py-3.5">Current Class</th>
                <th class="px-6 py-3.5">Primary Guardian</th>
                <th class="px-6 py-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr
                v-for="student in students.data"
                :key="student.id"
                class="hover:bg-slate-50/75 transition"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-medium text-slate-900">{{ student.full_name || student.name }}</div>
                  <div class="text-xs text-slate-500 capitalize">{{ student.gender }} • {{ student.date_of_birth || 'No DOB' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs font-semibold text-slate-700">
                  {{ student.student_code || student.admission_number }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                      getStatusBadgeClass(student.status)
                    ]"
                  >
                    {{ student.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                  {{ student.current_enrollment?.class_name || 'Unassigned' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                  <div v-if="student.guardians && student.guardians.length > 0">
                    <span class="font-medium text-slate-900">{{ student.guardians[0].full_name }}</span>
                    <span class="text-xs text-slate-400 block">{{ student.guardians[0].phone || student.guardians[0].email }}</span>
                  </div>
                  <span v-else class="text-slate-400 italic text-xs">None linked</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                  <Link
                    :href="route('students.show', student.id)"
                    class="text-emerald-600 hover:text-emerald-800 font-medium mr-3"
                  >
                    View
                  </Link>
                  <Link
                    :href="route('students.edit', student.id)"
                    class="text-slate-600 hover:text-slate-900 font-medium"
                  >
                    Edit
                  </Link>
                </td>
              </tr>
              <tr v-if="!students.data || students.data.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                  No students found matching your criteria.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="students.links && students.links.length > 3"
          class="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-3 sm:px-6"
        >
          <div class="text-xs text-slate-500">
            Showing {{ students.from || 0 }} to {{ students.to || 0 }} of {{ students.total }} students
          </div>
          <div class="flex space-x-1">
            <template v-for="(link, key) in students.links" :key="key">
              <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                :class="[
                  'px-3 py-1.5 text-xs rounded-md border font-medium',
                  link.active
                    ? 'bg-emerald-600 text-white border-emerald-600'
                    : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                ]"
              />
              <span
                v-else
                v-html="link.label"
                class="px-3 py-1.5 text-xs rounded-md border border-slate-200 text-slate-400 cursor-not-allowed"
              />
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
