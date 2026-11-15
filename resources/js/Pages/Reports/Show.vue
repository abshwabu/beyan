<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  type: {
    type: String,
    required: true,
  },
  report: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const filterForm = ref({
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  status: props.filters.status || '',
});

const applyFilters = () => {
  router.get(route('reports.show', props.type), filterForm.value, {
    preserveState: true,
  });
};
</script>

<template>
  <Head :title="`${report.title} - Reports`" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
          <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <Link :href="route('reports.index')" class="hover:underline">Reports</Link>
            <span>/</span>
            <span class="text-slate-800 font-bold capitalize">{{ type }}</span>
          </div>
          <h1 class="text-2xl font-black text-slate-900">{{ report.title }}</h1>
          <p class="text-xs text-slate-500">
            Total Records: <strong class="text-slate-800">{{ report.count }}</strong>
            <span v-if="report.period"> · Period: {{ report.period }}</span>
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <a
            :href="route('reports.export', { type, format: 'csv', ...filters })"
            class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-xl shadow-sm transition flex items-center gap-1.5"
          >
            <span>📥</span> Export CSV (Excel)
          </a>
          <a
            :href="route('reports.export', { type, format: 'pdf', ...filters })"
            target="_blank"
            class="px-3.5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition flex items-center gap-1.5"
          >
            <span>🖨️</span> Print / Export PDF
          </a>
        </div>
      </div>

      <!-- Filters Bar -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap gap-4 items-center text-xs">
        <span class="font-bold uppercase text-slate-500">Filters:</span>
        <div class="flex items-center gap-2">
          <label class="text-slate-600 font-medium">Date From:</label>
          <input type="date" v-model="filterForm.date_from" class="rounded-lg border-slate-300 text-xs py-1" />
        </div>
        <div class="flex items-center gap-2">
          <label class="text-slate-600 font-medium">Date To:</label>
          <input type="date" v-model="filterForm.date_to" class="rounded-lg border-slate-300 text-xs py-1" />
        </div>
        <button
          @click="applyFilters"
          class="px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg transition"
        >
          Filter
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                <th v-for="header in report.headers" :key="header" class="p-3.5">
                  {{ header }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(row, idx) in report.rows" :key="idx" class="hover:bg-slate-50">
                <td v-for="(cell, cellIdx) in Object.values(row)" :key="cellIdx" class="p-3.5 font-medium text-slate-800">
                  {{ cell }}
                </td>
              </tr>
              <tr v-if="!report.rows || report.rows.length === 0">
                <td :colspan="report.headers?.length || 1" class="p-8 text-center text-slate-400 italic">
                  No records match the current filters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
