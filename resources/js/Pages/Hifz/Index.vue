<script setup>
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  entries: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const filterByType = (type) => {
  router.get(route('hifz.index'), { ...props.filters, type }, { preserveState: true });
};
</script>

<template>
  <Head title="Qur'an & Hifz Entries - Beyan" />

  <div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8" dir="ltr">
    <div class="max-w-6xl mx-auto space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200/90">
        <div>
          <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1.5">
            <Link :href="route('dashboard')" class="hover:text-emerald-700 transition-colors">
              Dashboard
            </Link>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800 font-semibold">Qur'an & Hifz Progress</span>
          </nav>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">Qur'an & Hifz Progress</h1>
          <p class="text-xs text-slate-500 mt-0.5">Track Sabaq, Sabqi, and Manzil records across all enrolled students</p>
        </div>

        <Link
          :href="route('hifz.fast-entry')"
          class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white font-bold text-xs rounded-xl shadow-sm transition-all"
        >
          + Fast Qur'an Entry
        </Link>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap gap-2 text-xs font-bold">
        <button
          @click="filterByType(null)"
          :class="['px-3.5 py-2 rounded-xl border transition-all active:scale-[0.98]', !filters.type ? 'bg-slate-900 text-white border-slate-900 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50']"
        >
          All Entries
        </button>
        <button
          @click="filterByType('sabaq')"
          :class="['px-3.5 py-2 rounded-xl border transition-all active:scale-[0.98]', filters.type === 'sabaq' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50']"
        >
          Sabaq (New Memorization)
        </button>
        <button
          @click="filterByType('sabqi')"
          :class="['px-3.5 py-2 rounded-xl border transition-all active:scale-[0.98]', filters.type === 'sabqi' ? 'bg-amber-600 text-white border-amber-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50']"
        >
          Sabqi (Recent Revision)
        </button>
        <button
          @click="filterByType('manzil')"
          :class="['px-3.5 py-2 rounded-xl border transition-all active:scale-[0.98]', filters.type === 'manzil' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50']"
        >
          Manzil (Cycle Revision)
        </button>
      </div>

      <!-- Entries Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200/90 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider">
                <th class="p-3.5">Date</th>
                <th class="p-3.5">Student</th>
                <th class="p-3.5">Type</th>
                <th class="p-3.5">Surah & Range</th>
                <th class="p-3.5">Quality / Tajweed</th>
                <th class="p-3.5">Teacher</th>
                <th class="p-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="entry in entries.data" :key="entry.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="p-3.5 text-slate-600 font-medium whitespace-nowrap">{{ entry.date }}</td>
                <td class="p-3.5 font-bold text-slate-900">
                  <Link :href="route('hifz.student.profile', entry.student_id)" class="text-emerald-700 hover:text-emerald-800 hover:underline">
                    {{ entry.student?.full_name || entry.student?.name }}
                  </Link>
                </td>
                <td class="p-3.5">
                  <span
                    :class="[
                      'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ring-1 ring-inset',
                      entry.type === 'sabaq' ? 'bg-emerald-50 text-emerald-800 ring-emerald-600/20' :
                      entry.type === 'sabqi' ? 'bg-amber-50 text-amber-800 ring-amber-600/20' :
                      entry.type === 'manzil' ? 'bg-indigo-50 text-indigo-800 ring-indigo-600/20' : 'bg-sky-50 text-sky-800 ring-sky-600/20'
                    ]"
                  >
                    {{ entry.type }}
                  </span>
                </td>
                <td class="p-3.5 font-bold text-slate-900">
                  {{ entry.surah?.english_name || ('Surah ' + entry.surah_number) }} · Ayahs {{ entry.start_ayah }}-{{ entry.end_ayah }}
                </td>
                <td class="p-3.5 capitalize">
                  <span class="font-bold text-slate-800">{{ entry.quality_rating }}</span>
                  <span class="text-slate-500 block text-[10px]">Tajweed: {{ entry.tajweed_rating }}</span>
                </td>
                <td class="p-3.5 text-slate-600 font-medium">{{ entry.teacher?.user?.name || 'N/A' }}</td>
                <td class="p-3.5 text-right">
                  <Link
                    :href="route('hifz.student.profile', entry.student_id)"
                    class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-900 transition-colors"
                  >
                    Profile →
                  </Link>
                </td>
              </tr>
              <tr v-if="!entries.data || entries.data.length === 0">
                <td colspan="7" class="p-10 text-center text-slate-400 italic">
                  No Qur'an progress entries recorded yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
