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
  <Head title="Qur'an & Hifz Entries" />

  <div class="min-h-screen bg-slate-100 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
          <h1 class="text-2xl font-black text-slate-900">Qur'an & Hifz Progress</h1>
          <p class="text-xs text-slate-500">Track Sabaq, Sabqi, and Manzil records across all students</p>
        </div>

        <Link
          :href="route('hifz.fast-entry')"
          class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition"
        >
          + Fast Qur'an Entry
        </Link>
      </div>

      <!-- Filters -->
      <div class="flex gap-2 text-xs font-semibold">
        <button
          @click="filterByType(null)"
          :class="['px-3 py-1.5 rounded-lg border transition', !filters.type ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200']"
        >
          All
        </button>
        <button
          @click="filterByType('sabaq')"
          :class="['px-3 py-1.5 rounded-lg border transition', filters.type === 'sabaq' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-700 border-slate-200']"
        >
          Sabaq (New)
        </button>
        <button
          @click="filterByType('sabqi')"
          :class="['px-3 py-1.5 rounded-lg border transition', filters.type === 'sabqi' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-200']"
        >
          Sabqi (Recent)
        </button>
        <button
          @click="filterByType('manzil')"
          :class="['px-3 py-1.5 rounded-lg border transition', filters.type === 'manzil' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-200']"
        >
          Manzil (Cycle)
        </button>
      </div>

      <!-- Entries Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
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
              <tr v-for="entry in entries.data" :key="entry.id" class="hover:bg-slate-50">
                <td class="p-3.5 text-slate-500 whitespace-nowrap">{{ entry.date }}</td>
                <td class="p-3.5 font-bold text-slate-900">
                  <Link :href="route('hifz.student.profile', entry.student_id)" class="text-emerald-700 hover:underline">
                    {{ entry.student?.full_name || entry.student?.name }}
                  </Link>
                </td>
                <td class="p-3.5">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                      entry.type === 'sabaq' ? 'bg-emerald-100 text-emerald-800' :
                      entry.type === 'sabqi' ? 'bg-amber-100 text-amber-800' :
                      entry.type === 'manzil' ? 'bg-indigo-100 text-indigo-800' : 'bg-sky-100 text-sky-800'
                    ]"
                  >
                    {{ entry.type }}
                  </span>
                </td>
                <td class="p-3.5 font-semibold text-slate-800">
                  {{ entry.surah?.english_name || ('Surah ' + entry.surah_number) }} · {{ entry.start_ayah }}-{{ entry.end_ayah }}
                </td>
                <td class="p-3.5 capitalize">
                  <span class="font-medium text-slate-700">{{ entry.quality_rating }}</span>
                  <span class="text-slate-400 block text-[10px]">Tajweed: {{ entry.tajweed_rating }}</span>
                </td>
                <td class="p-3.5 text-slate-600">{{ entry.teacher?.user?.name || 'N/A' }}</td>
                <td class="p-3.5 text-right">
                  <Link
                    :href="route('hifz.student.profile', entry.student_id)"
                    class="text-xs font-bold text-emerald-700 hover:text-emerald-900"
                  >
                    Profile →
                  </Link>
                </td>
              </tr>
              <tr v-if="!entries.data || entries.data.length === 0">
                <td colspan="7" class="p-8 text-center text-slate-400 italic">
                  No Hifz entries recorded.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
