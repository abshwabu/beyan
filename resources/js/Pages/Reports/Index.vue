<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  reports: {
    type: Array,
    required: true,
  },
});
</script>

<template>
  <Head title="Institution Reports - Beyan SaaS" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
          <h1 class="text-2xl font-black text-slate-900">Reports & Analytics Center</h1>
          <p class="text-xs text-slate-500">Generate, view, and export official reports to Excel (CSV) or PDF</p>
        </div>

        <Link
          :href="route('dashboard')"
          class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
        >
          ← Back to Dashboard
        </Link>
      </div>

      <!-- Report Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="rep in reports"
          :key="rep.id"
          class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col justify-between hover:shadow-md transition"
        >
          <div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center text-xl mb-3">
              {{ rep.icon }}
            </div>
            <h2 class="text-base font-bold text-slate-900">{{ rep.title }}</h2>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ rep.description }}</p>
          </div>

          <div class="pt-4 border-t border-slate-100 mt-6 flex justify-between items-center">
            <Link
              :href="route('reports.show', rep.id)"
              class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition"
            >
              Open Report →
            </Link>
            <a
              :href="route('reports.export', { type: rep.id, format: 'csv' })"
              class="text-xs font-semibold text-slate-600 hover:text-slate-900"
            >
              Export CSV 📥
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
