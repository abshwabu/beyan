<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  profile: {
    type: Object,
    required: true,
  },
});

const activeTab = ref('overview'); // overview, timeline, compare, certifications

// Form for adding target
const targetForm = useForm({
  period: 'daily',
  target_type: 'sabaq_ayahs',
  target_value: 5,
  notes: '',
});

const showTargetModal = ref(false);
const saveTarget = () => {
  targetForm.post(route('hifz.student.targets.store', props.profile.student.id), {
    preserveScroll: true,
    onSuccess: () => {
      showTargetModal.value = false;
      targetForm.reset();
    },
  });
};

// Form for certification
const certForm = useForm({
  type: 'hifz_completion',
  title: 'Full Qur\'an Memorization Completion',
  riwayah: 'Hafs an Asim',
  sanad_chain: '',
  completion_date: new Date().toISOString().split('T')[0],
  grade: 'Mumtaz',
  notes: '',
});

const showCertModal = ref(false);
const saveCertification = () => {
  certForm.post(route('hifz.student.certifications.store', props.profile.student.id), {
    preserveScroll: true,
    onSuccess: () => {
      showCertModal.value = false;
      certForm.reset();
    },
  });
};

// Progress comparison
const compareDates = ref({
  start_date_a: new Date(Date.now() - 30 * 86400000).toISOString().split('T')[0],
  end_date_a: new Date(Date.now() - 15 * 86400000).toISOString().split('T')[0],
  start_date_b: new Date(Date.now() - 14 * 86400000).toISOString().split('T')[0],
  end_date_b: new Date().toISOString().split('T')[0],
});

const comparisonResult = ref(null);
const isComparing = ref(false);

const runComparison = async () => {
  isComparing.value = true;
  try {
    const res = await fetch(
      route('hifz.student.compare', {
        student: props.profile.student.id,
        ...compareDates.value,
      }),
      {
        headers: { Accept: 'application/json' },
      }
    );
    comparisonResult.value = await res.json();
  } catch (e) {
    console.error('Failed to compare progress', e);
  } finally {
    isComparing.value = false;
  }
};
</script>

<template>
  <Head :title="profile.student.name + ' - Qur\'an & Hifz Profile'" />

  <div class="min-h-screen bg-slate-100 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <div class="flex items-center gap-3">
            <span class="p-2.5 bg-emerald-600 rounded-xl text-white font-bold text-xl">📖</span>
            <div>
              <h1 class="text-2xl font-black text-slate-900">{{ profile.student.name }}</h1>
              <div class="flex items-center gap-3 text-xs text-slate-500 mt-0.5">
                <span>ID: <strong class="text-slate-800">{{ profile.student.student_code }}</strong></span>
                <span>•</span>
                <span class="capitalize px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-semibold">{{ profile.student.status }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="showTargetModal = true"
            class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
          >
            + Set Target
          </button>
          <button
            @click="showCertModal = true"
            class="px-3.5 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition"
          >
            + Issue Ijazah / Cert
          </button>
          <Link
            :href="route('hifz.fast-entry', { student_id: profile.student.id })"
            class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow transition"
          >
            Log Entry (Fast Entry)
          </Link>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex border-b border-slate-200 space-x-6 text-sm font-semibold">
        <button
          @click="activeTab = 'overview'"
          :class="['pb-3 transition border-b-2', activeTab === 'overview' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800']"
        >
          Progress Overview (3 Pillars)
        </button>
        <button
          @click="activeTab = 'timeline'"
          :class="['pb-3 transition border-b-2', activeTab === 'timeline' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800']"
        >
          Hifz History Timeline
        </button>
        <button
          @click="activeTab = 'compare'"
          :class="['pb-3 transition border-b-2', activeTab === 'compare' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800']"
        >
          Period Comparison
        </button>
        <button
          @click="activeTab = 'certifications'"
          :class="['pb-3 transition border-b-2', activeTab === 'certifications' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800']"
        >
          Completions & Ijazahs ({{ profile.certifications?.length || 0 }})
        </button>
      </div>

      <!-- TAB 1: OVERVIEW (THREE DISTINCT PILLARS) -->
      <div v-if="activeTab === 'overview'" class="space-y-6">
        <!-- 3 DISTINCT PILLARS - NEVER ONE COLLAPSED PERCENTAGE -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Pillar 1: Sabaq -->
          <div class="bg-white rounded-2xl shadow-sm border border-emerald-200 p-6 flex flex-col justify-between">
            <div>
              <div class="flex justify-between items-center mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Pillar 1</span>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">New Memorization</span>
              </div>
              <h2 class="text-lg font-bold text-slate-900 mb-2">Sabaq Position</h2>
              <div v-if="profile.sabaq?.current_position" class="space-y-2 mt-3">
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                  <div class="text-xs text-emerald-800 font-medium">Current Surah</div>
                  <div class="text-base font-black text-emerald-950">
                    {{ profile.sabaq.current_position.surah_name }} ({{ profile.sabaq.current_position.surah_arabic }})
                  </div>
                  <div class="text-xs text-emerald-700 mt-1 font-semibold">
                    Ayah: {{ profile.sabaq.current_position.start_ayah }} - {{ profile.sabaq.current_position.end_ayah }}
                  </div>
                </div>
                <div class="text-xs text-slate-500 flex justify-between pt-1">
                  <span>Last Logged: {{ profile.sabaq.current_position.date }}</span>
                  <span class="capitalize font-medium text-emerald-700">{{ profile.sabaq.current_position.quality_rating }}</span>
                </div>
              </div>
              <div v-else class="text-sm text-slate-400 py-6 text-center italic">
                No Sabaq entries recorded yet.
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100 mt-4 text-xs text-slate-600 flex justify-between items-center">
              <span>Total Sabaq Ayahs:</span>
              <strong class="text-emerald-700 font-bold text-sm">{{ profile.sabaq?.total_ayahs_memorized || 0 }}</strong>
            </div>
          </div>

          <!-- Pillar 2: Sabqi -->
          <div class="bg-white rounded-2xl shadow-sm border border-amber-200 p-6 flex flex-col justify-between">
            <div>
              <div class="flex justify-between items-center mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Pillar 2</span>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">Recent Revision</span>
              </div>
              <h2 class="text-lg font-bold text-slate-900 mb-2">Sabqi Coverage</h2>
              <div class="space-y-3 mt-3">
                <div class="p-3 bg-amber-50 rounded-xl border border-amber-100">
                  <div class="text-xs text-amber-800 font-medium">Past 30 Days Activity</div>
                  <div class="text-2xl font-black text-amber-950 mt-1">
                    {{ profile.sabqi?.last_30_days_ayahs || 0 }} <span class="text-xs font-normal text-amber-700">Ayahs reviewed</span>
                  </div>
                  <div class="text-xs text-amber-700 mt-1">
                    {{ profile.sabqi?.last_30_days_revisions || 0 }} revision sessions
                  </div>
                </div>
                <div class="text-xs text-slate-500">
                  Surahs recently refreshed: <strong class="text-slate-700">{{ profile.sabqi?.reviewed_surah_numbers?.length || 0 }} surahs</strong>
                </div>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100 mt-4 text-xs text-slate-600 flex justify-between items-center">
              <span>Total Sabqi Ayahs:</span>
              <strong class="text-amber-700 font-bold text-sm">{{ profile.sabqi?.total_ayahs_reviewed || 0 }}</strong>
            </div>
          </div>

          <!-- Pillar 3: Manzil -->
          <div class="bg-white rounded-2xl shadow-sm border border-indigo-200 p-6 flex flex-col justify-between">
            <div>
              <div class="flex justify-between items-center mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Pillar 3</span>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-100 text-indigo-800">Older Revision</span>
              </div>
              <h2 class="text-lg font-bold text-slate-900 mb-2">Manzil Cycle</h2>
              <div class="space-y-3 mt-3">
                <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-100">
                  <div class="text-xs text-indigo-800 font-medium">Juz Coverage</div>
                  <div class="text-2xl font-black text-indigo-950 mt-1">
                    {{ profile.manzil?.covered_juz_numbers?.length || 0 }} <span class="text-xs font-normal text-indigo-700">/ 30 Juzs touched</span>
                  </div>
                  <div class="text-xs text-indigo-700 mt-1">
                    {{ profile.manzil?.total_revisions_count || 0 }} complete manzil sessions
                  </div>
                </div>
                <div class="text-xs text-slate-500">
                  Total older revision volume: <strong class="text-slate-700">{{ profile.manzil?.total_ayahs_reviewed || 0 }} Ayahs</strong>
                </div>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100 mt-4 text-xs text-slate-600 flex justify-between items-center">
              <span>Active Cycle Juzs:</span>
              <strong class="text-indigo-700 font-bold text-sm">{{ (profile.manzil?.covered_juz_numbers || []).join(', ') || 'None' }}</strong>
            </div>
          </div>
        </div>

        <!-- Targets: Achieved vs Target Widget -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <div>
              <h3 class="text-base font-bold text-slate-900">Active Targets (Achieved vs Target)</h3>
              <p class="text-xs text-slate-500">Daily and weekly goals monitored per student</p>
            </div>
            <button
              @click="showTargetModal = true"
              class="text-xs text-emerald-700 font-bold hover:underline"
            >
              + Configure Target
            </button>
          </div>

          <div v-if="profile.targets && profile.targets.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="t in profile.targets"
              :key="t.id"
              class="p-4 rounded-xl border border-slate-100 bg-slate-50 space-y-2"
            >
              <div class="flex justify-between items-center text-xs">
                <span class="font-bold uppercase tracking-wider text-slate-600">{{ t.period }} Target · {{ t.target_type }}</span>
                <span
                  :class="[
                    'px-2 py-0.5 rounded-full text-[10px] font-bold',
                    t.is_completed ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'
                  ]"
                >
                  {{ t.is_completed ? 'Goal Achieved' : 'In Progress' }}
                </span>
              </div>

              <div class="flex justify-between items-baseline">
                <div class="text-xl font-black text-slate-900">
                  {{ t.achieved_value }} <span class="text-xs font-normal text-slate-500">/ {{ t.target_value }}</span>
                </div>
                <span class="text-xs font-bold text-emerald-700">{{ t.progress_percentage }}%</span>
              </div>

              <!-- Progress bar -->
              <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                <div
                  class="bg-emerald-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: t.progress_percentage + '%' }"
                ></div>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-slate-400 py-6 text-center italic">
            No active targets configured for this student. Click "+ Set Target" to add daily or weekly goals.
          </div>
        </div>
      </div>

      <!-- TAB 2: TIMELINE -->
      <div v-if="activeTab === 'timeline'" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <h3 class="text-base font-bold text-slate-900">Recent Qur'an Entries</h3>
        <div v-if="profile.timeline && profile.timeline.length > 0" class="divide-y divide-slate-100">
          <div
            v-for="entry in profile.timeline"
            :key="entry.id"
            class="py-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2"
          >
            <div class="flex items-center gap-3">
              <span
                :class="[
                  'px-2.5 py-1 rounded-lg text-xs font-bold uppercase',
                  entry.type === 'sabaq' ? 'bg-emerald-100 text-emerald-800' :
                  entry.type === 'sabqi' ? 'bg-amber-100 text-amber-800' :
                  entry.type === 'manzil' ? 'bg-indigo-100 text-indigo-800' : 'bg-sky-100 text-sky-800'
                ]"
              >
                {{ entry.type }}
              </span>
              <div>
                <div class="text-sm font-bold text-slate-900">
                  {{ entry.surah_name }} ({{ entry.surah_arabic }}) · Ayahs {{ entry.start_ayah }}-{{ entry.end_ayah }}
                </div>
                <div class="text-xs text-slate-500">
                  {{ entry.date }} · Teacher: {{ entry.teacher_name || 'N/A' }}
                  <span v-if="entry.teacher_comment"> · Note: "{{ entry.teacher_comment }}"</span>
                </div>
              </div>
            </div>

            <div class="text-right text-xs">
              <span class="font-semibold text-slate-700 capitalize">{{ entry.quality_rating }}</span>
              <span v-if="entry.mistakes_count > 0" class="text-red-600 block text-[11px] font-semibold">
                {{ entry.mistakes_count }} mistake(s)
              </span>
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-slate-400 py-8 text-center italic">
          No entries recorded yet.
        </div>
      </div>

      <!-- TAB 3: COMPARE PERIODS -->
      <div v-if="activeTab === 'compare'" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
        <div>
          <h3 class="text-base font-bold text-slate-900">Compare Progress Across Periods</h3>
          <p class="text-xs text-slate-500">Measure growth, revision volume, and mistake trends between two timeframes</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
          <div>
            <span class="block text-xs font-bold uppercase text-slate-700 mb-2">Period A</span>
            <div class="grid grid-cols-2 gap-2">
              <input type="date" v-model="compareDates.start_date_a" class="rounded-lg text-xs border-slate-300" />
              <input type="date" v-model="compareDates.end_date_a" class="rounded-lg text-xs border-slate-300" />
            </div>
          </div>

          <div>
            <span class="block text-xs font-bold uppercase text-slate-700 mb-2">Period B</span>
            <div class="grid grid-cols-2 gap-2">
              <input type="date" v-model="compareDates.start_date_b" class="rounded-lg text-xs border-slate-300" />
              <input type="date" v-model="compareDates.end_date_b" class="rounded-lg text-xs border-slate-300" />
            </div>
          </div>
        </div>

        <button
          @click="runComparison"
          :disabled="isComparing"
          class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition"
        >
          <span v-if="isComparing">Comparing...</span>
          <span v-else>Generate Comparison Report</span>
        </button>

        <!-- Results Table -->
        <div v-if="comparisonResult" class="overflow-x-auto mt-4">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 font-bold">
                <th class="p-3">Metric</th>
                <th class="p-3">Period A</th>
                <th class="p-3">Period B</th>
                <th class="p-3">Difference</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr>
                <td class="p-3 font-semibold">Sabaq (New Ayahs)</td>
                <td class="p-3">{{ comparisonResult.period_a.metrics.sabaq_ayahs }}</td>
                <td class="p-3">{{ comparisonResult.period_b.metrics.sabaq_ayahs }}</td>
                <td class="p-3 font-bold" :class="comparisonResult.comparison.sabaq_ayahs_diff >= 0 ? 'text-emerald-700' : 'text-red-600'">
                  {{ comparisonResult.comparison.sabaq_ayahs_diff >= 0 ? '+' : '' }}{{ comparisonResult.comparison.sabaq_ayahs_diff }}
                </td>
              </tr>
              <tr>
                <td class="p-3 font-semibold">Sabqi (Recent Revision Ayahs)</td>
                <td class="p-3">{{ comparisonResult.period_a.metrics.sabqi_ayahs }}</td>
                <td class="p-3">{{ comparisonResult.period_b.metrics.sabqi_ayahs }}</td>
                <td class="p-3 font-bold" :class="comparisonResult.comparison.sabqi_ayahs_diff >= 0 ? 'text-amber-700' : 'text-red-600'">
                  {{ comparisonResult.comparison.sabqi_ayahs_diff >= 0 ? '+' : '' }}{{ comparisonResult.comparison.sabqi_ayahs_diff }}
                </td>
              </tr>
              <tr>
                <td class="p-3 font-semibold">Manzil (Cycle Revision Ayahs)</td>
                <td class="p-3">{{ comparisonResult.period_a.metrics.manzil_ayahs }}</td>
                <td class="p-3">{{ comparisonResult.period_b.metrics.manzil_ayahs }}</td>
                <td class="p-3 font-bold" :class="comparisonResult.comparison.manzil_ayahs_diff >= 0 ? 'text-indigo-700' : 'text-red-600'">
                  {{ comparisonResult.comparison.manzil_ayahs_diff >= 0 ? '+' : '' }}{{ comparisonResult.comparison.manzil_ayahs_diff }}
                </td>
              </tr>
              <tr>
                <td class="p-3 font-semibold">Total Mistakes</td>
                <td class="p-3">{{ comparisonResult.period_a.metrics.total_mistakes }}</td>
                <td class="p-3">{{ comparisonResult.period_b.metrics.total_mistakes }}</td>
                <td class="p-3 font-bold" :class="comparisonResult.comparison.total_mistakes_diff <= 0 ? 'text-emerald-700' : 'text-red-600'">
                  {{ comparisonResult.comparison.total_mistakes_diff >= 0 ? '+' : '' }}{{ comparisonResult.comparison.total_mistakes_diff }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 4: CERTIFICATIONS & IJAZAHS -->
      <div v-if="activeTab === 'certifications'" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-base font-bold text-slate-900">Qur'an Completions & Ijazahs</h3>
            <p class="text-xs text-slate-500">Official certifications, sanad chains, and milestone awards</p>
          </div>
          <button
            @click="showCertModal = true"
            class="px-3.5 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow transition"
          >
            + Issue Ijazah
          </button>
        </div>

        <div v-if="profile.certifications && profile.certifications.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="cert in profile.certifications"
            :key="cert.id"
            class="p-5 rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/50 to-white shadow-sm space-y-3"
          >
            <div class="flex justify-between items-start">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full">
                  {{ cert.type }}
                </span>
                <h4 class="text-base font-black text-slate-900 mt-1">{{ cert.title }}</h4>
                <div class="text-xs text-slate-500">Riwayah: <strong class="text-slate-800">{{ cert.riwayah }}</strong></div>
              </div>
              <div class="text-right">
                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">{{ cert.grade }}</span>
                <span class="block text-[10px] text-slate-400 mt-1">{{ cert.certificate_number }}</span>
              </div>
            </div>

            <div v-if="cert.sanad_chain" class="p-3 bg-white/80 rounded-xl border border-slate-200 text-xs text-slate-600 italic">
              "{{ cert.sanad_chain }}"
            </div>

            <div class="text-[11px] text-slate-500 pt-2 border-t border-indigo-100 flex justify-between">
              <span>Date: {{ cert.completion_date }}</span>
              <span v-if="cert.teacher?.user?.name">Teacher: {{ cert.teacher.user.name }}</span>
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-slate-400 py-8 text-center italic">
          No certifications or Ijazahs issued yet.
        </div>
      </div>
    </div>
  </div>
</template>
