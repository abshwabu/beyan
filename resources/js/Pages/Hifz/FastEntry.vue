<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
  students: {
    type: Array,
    default: () => [],
  },
  surahs: {
    type: Array,
    default: () => [],
  },
  last_sabaq: {
    type: Object,
    default: null,
  },
  selected_student_id: {
    type: String,
    default: '',
  },
});

const page = usePage();
const isRtl = computed(() => page.props.i18n?.direction === 'rtl');

const form = useForm({
  student_id: props.selected_student_id || (props.students[0]?.id || ''),
  type: 'sabaq', // sabaq, sabqi, manzil, reading
  surah_number: props.last_sabaq?.surah_number || 1,
  start_ayah: props.last_sabaq?.suggested_start_ayah || 1,
  end_ayah: props.last_sabaq?.suggested_start_ayah ? props.last_sabaq.suggested_start_ayah + 4 : 5,
  date: new Date().toISOString().split('T')[0],
  quality_rating: 'good',
  tajweed_rating: 'good',
  mistakes_count: 0,
  teacher_comment: '',
});

const surahSearch = ref('');
const selectedSurah = computed(() => {
  return props.surahs.find((s) => s.number === Number(form.surah_number));
});

// Filter surahs for quick search
const filteredSurahs = computed(() => {
  if (!surahSearch.value) return props.surahs;
  const q = surahSearch.value.toLowerCase().trim();
  return props.surahs.filter(
    (s) =>
      s.english_name.toLowerCase().includes(q) ||
      (s.arabic_name && s.arabic_name.includes(q)) ||
      String(s.number) === q ||
      String(s.number).startsWith(q)
  );
});

// When student changes, reload with pre-filled last Sabaq
const onStudentChange = () => {
  if (form.student_id) {
    router.get(
      route('hifz.fast-entry'),
      { student_id: form.student_id },
      { preserveState: true, preserveScroll: true }
    );
  }
};

// When selected_student_id or last_sabaq prop updates, update form
watch(
  () => props.last_sabaq,
  (newSabaq) => {
    if (newSabaq && form.type === 'sabaq') {
      form.surah_number = newSabaq.surah_number;
      form.start_ayah = newSabaq.suggested_start_ayah;
      form.end_ayah = Math.min(
        newSabaq.suggested_start_ayah + 4,
        selectedSurah.value?.total_ayahs || 999
      );
    }
  }
);

// Quick ayah adjustments
const addAyahs = (count) => {
  const max = selectedSurah.value?.total_ayahs || 286;
  form.end_ayah = Math.min(form.end_ayah + count, max);
};

const setWholeSurah = () => {
  if (selectedSurah.value) {
    form.start_ayah = 1;
    form.end_ayah = selectedSurah.value.total_ayahs;
  }
};

const applyContinueFromLast = () => {
  if (props.last_sabaq) {
    form.surah_number = props.last_sabaq.surah_number;
    form.start_ayah = props.last_sabaq.suggested_start_ayah;
    const max = selectedSurah.value?.total_ayahs || 286;
    form.end_ayah = Math.min(form.start_ayah + 4, max);
  }
};

// Quick teacher comment tags
const commentTags = [
  'Madd Rules',
  'Ghunnah Focus',
  'Qalqalah Revision',
  'Fluency Needed',
  'Excellent Recitation',
  'Review Waqf',
];

const appendTag = (tag) => {
  if (!form.teacher_comment) {
    form.teacher_comment = tag;
  } else if (!form.teacher_comment.includes(tag)) {
    form.teacher_comment += `, ${tag}`;
  }
};

const incrementMistakes = () => {
  form.mistakes_count++;
};

const decrementMistakes = () => {
  if (form.mistakes_count > 0) form.mistakes_count--;
};

const saveSuccess = ref(false);

const submit = () => {
  form.post(route('hifz.entries.store'), {
    preserveScroll: true,
    onSuccess: () => {
      saveSuccess.value = true;
      setTimeout(() => {
        saveSuccess.value = false;
      }, 3500);

      // Auto-advance start_ayah for next entry if was sabaq
      if (form.type === 'sabaq' && selectedSurah.value) {
        if (form.end_ayah < selectedSurah.value.total_ayahs) {
          form.start_ayah = form.end_ayah + 1;
          form.end_ayah = Math.min(form.start_ayah + 4, selectedSurah.value.total_ayahs);
        }
      }
      form.mistakes_count = 0;
      form.teacher_comment = '';
    },
  });
};

const selectedStudent = computed(() => {
  return props.students.find((s) => s.id === form.student_id);
});
</script>

<template>
  <Head title="Fast Qur'an Entry - Beyan" />

  <div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8" :dir="isRtl ? 'rtl' : 'ltr'">
    <div class="max-w-3xl mx-auto space-y-6">

      <!-- Breadcrumbs & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1.5">
            <Link :href="route('hifz.index')" class="hover:text-emerald-700 transition-colors">
              Qur'an & Hifz
            </Link>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800 font-semibold">Fast Log</span>
          </nav>

          <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2.5">
            Fast Qur'an Entry
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-1">
              ⚡ &lt; 20s Entry
            </span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
            Quick recitation recorder for daily Sabaq, recent Sabqi, or cycle Manzil.
          </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
          <Link
            v-if="form.student_id"
            :href="route('hifz.student.profile', form.student_id)"
            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-xs hover:bg-slate-50 transition-all"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Student Profile
          </Link>
          <Link
            :href="route('hifz.index')"
            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-xs hover:bg-slate-50 transition-all"
          >
            <svg class="w-4 h-4 text-slate-400" :class="isRtl ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Overview
          </Link>
        </div>
      </div>

      <!-- Success Toast Banner -->
      <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-2 opacity-0"
      >
        <div
          v-if="saveSuccess"
          class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-900 text-sm flex items-center justify-between gap-3 shadow-xs"
        >
          <div class="flex items-center gap-2.5">
            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold">✓</span>
            <div>
              <p class="font-semibold">Recitation entry recorded successfully!</p>
              <p class="text-xs text-emerald-700">Next Ayah range has been auto-advanced for the student.</p>
            </div>
          </div>
          <button
            type="button"
            @click="saveSuccess = false"
            class="text-emerald-700 hover:text-emerald-900 text-xs font-semibold px-2 py-1 rounded"
          >
            Dismiss
          </button>
        </div>
      </transition>

      <!-- Form Card -->
      <form @submit.prevent="submit" class="space-y-6">

        <!-- 1. Student Selection & Last Sabaq Pill -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/90 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-100">
                01
              </span>
              <div>
                <h2 class="text-base font-semibold text-slate-900">Select Student & Date</h2>
                <p class="text-xs text-slate-500">Pick the active student to log today's recitation progress.</p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                Student <span class="text-rose-500">*</span>
              </label>
              <div class="relative rounded-lg shadow-xs">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                  <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <select
                  v-model="form.student_id"
                  @change="onStudentChange"
                  class="block w-full rounded-lg border border-slate-300 text-sm ps-9 pe-8 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-white font-medium text-slate-800"
                >
                  <option v-for="s in students" :key="s.id" :value="s.id">
                    {{ s.full_name }} ({{ s.student_code }})
                  </option>
                </select>
              </div>
              <span v-if="form.errors.student_id" class="text-xs text-rose-600 mt-1 block">{{ form.errors.student_id }}</span>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                Recitation Date <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.date"
                type="date"
                required
                class="block w-full rounded-lg border border-slate-300 text-sm px-3.5 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-white"
              />
              <span v-if="form.errors.date" class="text-xs text-rose-600 mt-1 block">{{ form.errors.date }}</span>
            </div>
          </div>

          <!-- Last Sabaq Context Banner -->
          <div
            v-if="last_sabaq"
            class="rounded-xl bg-emerald-50/70 border border-emerald-200/80 p-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
          >
            <div class="flex items-center gap-2.5">
              <span class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800 text-xs">📖</span>
              <div class="text-xs">
                <span class="text-slate-600">Previous Sabaq: </span>
                <strong class="text-emerald-900 font-semibold">{{ last_sabaq.surah_name }}</strong>
                <span class="text-slate-600"> · Finished at Ayah {{ last_sabaq.end_ayah }}</span>
                <span class="text-slate-400 ms-1.5 font-normal">({{ last_sabaq.date }})</span>
              </div>
            </div>

            <button
              type="button"
              @click="applyContinueFromLast"
              class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-800 hover:text-emerald-900 bg-white px-2.5 py-1.5 rounded-lg border border-emerald-300 shadow-2xs hover:bg-emerald-50/50 transition-all cursor-pointer self-start sm:self-auto"
            >
              <span>Continue from Ayah {{ last_sabaq.suggested_start_ayah }}</span>
              <span>→</span>
            </button>
          </div>
        </div>

        <!-- 2. Entry Type Selection -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/90 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-100">
                02
              </span>
              <div>
                <h2 class="text-base font-semibold text-slate-900">Recitation Category</h2>
                <p class="text-xs text-slate-500">Select what the student is presenting today.</p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <!-- Sabaq -->
            <button
              type="button"
              @click="form.type = 'sabaq'"
              class="p-3.5 rounded-xl border text-start transition-all cursor-pointer relative"
              :class="form.type === 'sabaq'
                ? 'bg-emerald-50/90 border-emerald-400 ring-2 ring-emerald-600/20 shadow-xs'
                : 'bg-slate-50/60 border-slate-200 hover:bg-slate-100/70 text-slate-700'"
            >
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold uppercase tracking-wider" :class="form.type === 'sabaq' ? 'text-emerald-900' : 'text-slate-800'">
                  Sabaq
                </span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold" :class="form.type === 'sabaq' ? 'bg-emerald-200/70 text-emerald-900' : 'bg-slate-200/60 text-slate-600'">
                  حفظ جديد
                </span>
              </div>
              <p class="text-[11px] leading-tight" :class="form.type === 'sabaq' ? 'text-emerald-700 font-medium' : 'text-slate-500'">
                New lesson memorization
              </p>
            </button>

            <!-- Sabqi -->
            <button
              type="button"
              @click="form.type = 'sabqi'"
              class="p-3.5 rounded-xl border text-start transition-all cursor-pointer relative"
              :class="form.type === 'sabqi'
                ? 'bg-amber-50/90 border-amber-400 ring-2 ring-amber-600/20 shadow-xs'
                : 'bg-slate-50/60 border-slate-200 hover:bg-slate-100/70 text-slate-700'"
            >
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold uppercase tracking-wider" :class="form.type === 'sabqi' ? 'text-amber-900' : 'text-slate-800'">
                  Sabqi
                </span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold" :class="form.type === 'sabqi' ? 'bg-amber-200/70 text-amber-900' : 'bg-slate-200/60 text-slate-600'">
                  مراجعة قريبة
                </span>
              </div>
              <p class="text-[11px] leading-tight" :class="form.type === 'sabqi' ? 'text-amber-800 font-medium' : 'text-slate-500'">
                Recent days revision
              </p>
            </button>

            <!-- Manzil -->
            <button
              type="button"
              @click="form.type = 'manzil'"
              class="p-3.5 rounded-xl border text-start transition-all cursor-pointer relative"
              :class="form.type === 'manzil'
                ? 'bg-indigo-50/90 border-indigo-400 ring-2 ring-indigo-600/20 shadow-xs'
                : 'bg-slate-50/60 border-slate-200 hover:bg-slate-100/70 text-slate-700'"
            >
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold uppercase tracking-wider" :class="form.type === 'manzil' ? 'text-indigo-900' : 'text-slate-800'">
                  Manzil
                </span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold" :class="form.type === 'manzil' ? 'bg-indigo-200/70 text-indigo-900' : 'bg-slate-200/60 text-slate-600'">
                  مراجعة عامة
                </span>
              </div>
              <p class="text-[11px] leading-tight" :class="form.type === 'manzil' ? 'text-indigo-800 font-medium' : 'text-slate-500'">
                Old cycle revision
              </p>
            </button>

            <!-- Reading -->
            <button
              type="button"
              @click="form.type = 'reading'"
              class="p-3.5 rounded-xl border text-start transition-all cursor-pointer relative"
              :class="form.type === 'reading'
                ? 'bg-sky-50/90 border-sky-400 ring-2 ring-sky-600/20 shadow-xs'
                : 'bg-slate-50/60 border-slate-200 hover:bg-slate-100/70 text-slate-700'"
            >
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold uppercase tracking-wider" :class="form.type === 'reading' ? 'text-sky-900' : 'text-slate-800'">
                  Reading
                </span>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold" :class="form.type === 'reading' ? 'bg-sky-200/70 text-sky-900' : 'bg-slate-200/60 text-slate-600'">
                  تلاوة ونظر
                </span>
              </div>
              <p class="text-[11px] leading-tight" :class="form.type === 'reading' ? 'text-sky-800 font-medium' : 'text-slate-500'">
                Tilawah / Nazar reading
              </p>
            </button>
          </div>
          <span v-if="form.errors.type" class="text-xs text-rose-600 mt-1 block">{{ form.errors.type }}</span>
        </div>

        <!-- 3. Surah & Ayah Range -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/90 space-y-5">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-100">
                03
              </span>
              <div>
                <h2 class="text-base font-semibold text-slate-900">Surah & Ayah Range</h2>
                <p class="text-xs text-slate-500">Designate the exact portion recited by the student.</p>
              </div>
            </div>

            <div v-if="selectedSurah" class="text-end">
              <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                {{ selectedSurah.total_ayahs }} Ayahs Total
              </span>
            </div>
          </div>

          <!-- Surah Picker -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Select Surah <span class="text-rose-500">*</span>
              </label>
              <span v-if="selectedSurah" class="text-xs font-arabic text-slate-600 font-medium" dir="rtl">
                سورة {{ selectedSurah.arabic_name }}
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <div class="sm:col-span-1">
                <input
                  v-model="surahSearch"
                  type="text"
                  placeholder="Filter name or #..."
                  class="block w-full rounded-lg border border-slate-300 text-xs px-3 py-2.5 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-slate-50/50"
                />
              </div>

              <div class="sm:col-span-2">
                <select
                  v-model="form.surah_number"
                  class="block w-full rounded-lg border border-slate-300 text-sm px-3 py-2.5 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-white font-medium text-slate-800"
                >
                  <option v-for="s in filteredSurahs" :key="s.number" :value="s.number">
                    {{ s.number }}. {{ s.english_name }} ({{ s.arabic_name }}) · {{ s.total_ayahs }} Ayahs
                  </option>
                </select>
              </div>
            </div>
            <span v-if="form.errors.surah_number" class="text-xs text-rose-600 mt-1 block">{{ form.errors.surah_number }}</span>
          </div>

          <!-- Ayah Range Inputs & Quick Controls -->
          <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/90 space-y-3">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                  Start Ayah <span class="text-rose-500">*</span>
                </label>
                <div class="relative rounded-lg shadow-xs">
                  <input
                    type="number"
                    v-model.number="form.start_ayah"
                    min="1"
                    :max="selectedSurah ? selectedSurah.total_ayahs : 286"
                    required
                    class="block w-full rounded-lg border border-slate-300 text-sm py-2 px-3 text-center font-bold text-slate-800 bg-white outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                  />
                </div>
                <span v-if="form.errors.start_ayah" class="text-xs text-rose-600 mt-1 block">{{ form.errors.start_ayah }}</span>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                  End Ayah <span class="text-rose-500">*</span>
                </label>
                <div class="relative rounded-lg shadow-xs">
                  <input
                    type="number"
                    v-model.number="form.end_ayah"
                    :min="form.start_ayah || 1"
                    :max="selectedSurah ? selectedSurah.total_ayahs : 286"
                    required
                    class="block w-full rounded-lg border border-slate-300 text-sm py-2 px-3 text-center font-bold text-slate-800 bg-white outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                  />
                </div>
                <span v-if="form.errors.end_ayah" class="text-xs text-rose-600 mt-1 block">{{ form.errors.end_ayah }}</span>
              </div>
            </div>

            <!-- Quick Ayah Adjust Buttons -->
            <div class="flex items-center justify-between pt-1 flex-wrap gap-2">
              <span class="text-xs text-slate-500 font-medium">Quick Ayah Presets:</span>
              <div class="flex items-center gap-1.5">
                <button
                  type="button"
                  @click="addAyahs(3)"
                  class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white border border-slate-300 hover:border-emerald-500 hover:bg-emerald-50/50 text-slate-700 hover:text-emerald-800 transition cursor-pointer"
                >
                  +3 Ayahs
                </button>
                <button
                  type="button"
                  @click="addAyahs(5)"
                  class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white border border-slate-300 hover:border-emerald-500 hover:bg-emerald-50/50 text-slate-700 hover:text-emerald-800 transition cursor-pointer"
                >
                  +5 Ayahs
                </button>
                <button
                  type="button"
                  @click="addAyahs(10)"
                  class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white border border-slate-300 hover:border-emerald-500 hover:bg-emerald-50/50 text-slate-700 hover:text-emerald-800 transition cursor-pointer"
                >
                  +10 Ayahs
                </button>
                <button
                  type="button"
                  @click="setWholeSurah"
                  class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white border border-slate-300 hover:border-emerald-500 hover:bg-emerald-50/50 text-slate-700 hover:text-emerald-800 transition cursor-pointer"
                >
                  Full Surah
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Quality & Tajweed Rating Evaluation -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/90 space-y-5">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs border border-emerald-100">
                04
              </span>
              <div>
                <h2 class="text-base font-semibold text-slate-900">Performance Evaluation</h2>
                <p class="text-xs text-slate-500">Tap to rate memorization strength and tajweed precision.</p>
              </div>
            </div>
          </div>

          <!-- Memorization Quality -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
              Memorization Quality (Hifz)
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
              <button
                type="button"
                @click="form.quality_rating = 'excellent'"
                class="py-2.5 px-2 rounded-xl border text-center transition-all cursor-pointer"
                :class="form.quality_rating === 'excellent'
                  ? 'bg-emerald-50 text-emerald-900 border-emerald-400 ring-2 ring-emerald-500/20 font-bold shadow-xs'
                  : 'bg-slate-50/60 text-slate-700 border-slate-200 hover:bg-slate-100'"
              >
                <span class="block text-xs font-bold">Excellent</span>
                <span class="block text-[10px] text-slate-500 font-arabic font-normal">ممتاز</span>
              </button>

              <button
                type="button"
                @click="form.quality_rating = 'very_good'"
                class="py-2.5 px-2 rounded-xl border text-center transition-all cursor-pointer"
                :class="form.quality_rating === 'very_good'
                  ? 'bg-emerald-50 text-emerald-900 border-emerald-400 ring-2 ring-emerald-500/20 font-bold shadow-xs'
                  : 'bg-slate-50/60 text-slate-700 border-slate-200 hover:bg-slate-100'"
              >
                <span class="block text-xs font-bold">Very Good</span>
                <span class="block text-[10px] text-slate-500 font-arabic font-normal">جيد جداً</span>
              </button>

              <button
                type="button"
                @click="form.quality_rating = 'good'"
                class="py-2.5 px-2 rounded-xl border text-center transition-all cursor-pointer"
                :class="form.quality_rating === 'good'
                  ? 'bg-emerald-50 text-emerald-900 border-emerald-400 ring-2 ring-emerald-500/20 font-bold shadow-xs'
                  : 'bg-slate-50/60 text-slate-700 border-slate-200 hover:bg-slate-100'"
              >
                <span class="block text-xs font-bold">Good</span>
                <span class="block text-[10px] text-slate-500 font-arabic font-normal">جيد</span>
              </button>

              <button
                type="button"
                @click="form.quality_rating = 'acceptable'"
                class="py-2.5 px-2 rounded-xl border text-center transition-all cursor-pointer"
                :class="form.quality_rating === 'acceptable'
                  ? 'bg-amber-50 text-amber-900 border-amber-400 ring-2 ring-amber-500/20 font-bold shadow-xs'
                  : 'bg-slate-50/60 text-slate-700 border-slate-200 hover:bg-slate-100'"
              >
                <span class="block text-xs font-bold">Acceptable</span>
                <span class="block text-[10px] text-slate-500 font-arabic font-normal">مقبول</span>
              </button>

              <button
                type="button"
                @click="form.quality_rating = 'poor'"
                class="py-2.5 px-2 rounded-xl border text-center transition-all cursor-pointer"
                :class="form.quality_rating === 'poor'
                  ? 'bg-rose-50 text-rose-900 border-rose-400 ring-2 ring-rose-500/20 font-bold shadow-xs'
                  : 'bg-slate-50/60 text-slate-700 border-slate-200 hover:bg-slate-100'"
              >
                <span class="block text-xs font-bold">Needs Revision</span>
                <span class="block text-[10px] text-slate-500 font-arabic font-normal">ضعيف / إعادة</span>
              </button>
            </div>
            <span v-if="form.errors.quality_rating" class="text-xs text-rose-600 mt-1 block">{{ form.errors.quality_rating }}</span>
          </div>

          <!-- Tajweed Rating -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
              Tajweed Precision
            </label>
            <div class="grid grid-cols-3 gap-2.5">
              <button
                type="button"
                @click="form.tajweed_rating = 'excellent'"
                class="py-2.5 px-3 rounded-xl border text-center transition-all cursor-pointer"
                :class="form.tajweed_rating === 'excellent'
                  ? 'bg-emerald-50 text-emerald-900 border-emerald-400 ring-2 ring-emerald-500/20 font-bold shadow-xs'
                  : 'bg-slate-50/60 text-slate-700 border-slate-200 hover:bg-slate-100'"
              >
                <span class="block text-xs font-bold">Excellent</span>
                <span class="block text-[10px] text-slate-500 font-normal">Makharij & Rules Clear</span>
              </button>

              <button
                type="button"
                @click="form.tajweed_rating = 'good'"
                class="py-2.5 px-3 rounded-xl border text-center transition-all cursor-pointer"
                :class="form.tajweed_rating === 'good'
                  ? 'bg-emerald-50 text-emerald-900 border-emerald-400 ring-2 ring-emerald-500/20 font-bold shadow-xs'
                  : 'bg-slate-50/60 text-slate-700 border-slate-200 hover:bg-slate-100'"
              >
                <span class="block text-xs font-bold">Good</span>
                <span class="block text-[10px] text-slate-500 font-normal">Minor Hesitations</span>
              </button>

              <button
                type="button"
                @click="form.tajweed_rating = 'needs_work'"
                class="py-2.5 px-3 rounded-xl border text-center transition-all cursor-pointer"
                :class="form.tajweed_rating === 'needs_work'
                  ? 'bg-amber-50 text-amber-900 border-amber-400 ring-2 ring-amber-500/20 font-bold shadow-xs'
                  : 'bg-slate-50/60 text-slate-700 border-slate-200 hover:bg-slate-100'"
              >
                <span class="block text-xs font-bold">Needs Work</span>
                <span class="block text-[10px] text-slate-500 font-normal">Frequent Rule Errors</span>
              </button>
            </div>
            <span v-if="form.errors.tajweed_rating" class="text-xs text-rose-600 mt-1 block">{{ form.errors.tajweed_rating }}</span>
          </div>

          <!-- Mistakes Counter & Teacher Notes -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-100">
            <!-- Mistakes Counter -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Mistakes Count
              </label>
              <div class="flex items-center gap-1.5">
                <button
                  type="button"
                  @click="decrementMistakes"
                  class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold text-base flex items-center justify-center transition cursor-pointer"
                >
                  −
                </button>
                <input
                  type="number"
                  v-model.number="form.mistakes_count"
                  min="0"
                  class="w-16 h-10 text-center rounded-xl border border-slate-300 text-sm font-bold text-slate-800 bg-white outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                />
                <button
                  type="button"
                  @click="incrementMistakes"
                  class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold text-base flex items-center justify-center transition cursor-pointer"
                >
                  +
                </button>
              </div>
              <span v-if="form.errors.mistakes_count" class="text-xs text-rose-600 mt-1 block">{{ form.errors.mistakes_count }}</span>
            </div>

            <!-- Teacher Comment -->
            <div class="sm:col-span-2 space-y-1.5">
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Teacher Notes / Feedback
              </label>
              <input
                type="text"
                v-model="form.teacher_comment"
                placeholder="e.g. Focus on madd munfasil and ghunnah on Noon mushaddadah..."
                class="block w-full rounded-xl border border-slate-300 text-xs sm:text-sm px-3.5 py-2.5 transition-all outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 bg-white"
              />
              <!-- Quick Comment Chips -->
              <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                <button
                  v-for="tag in commentTags"
                  :key="tag"
                  type="button"
                  @click="appendTag(tag)"
                  class="px-2 py-0.5 rounded-md bg-slate-100 hover:bg-emerald-50 hover:text-emerald-800 text-[11px] font-medium text-slate-600 border border-slate-200/80 transition cursor-pointer"
                >
                  + {{ tag }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Bar -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-slate-200/90 flex items-center justify-between gap-4">
          <Link
            :href="route('hifz.index')"
            class="px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all"
          >
            Cancel
          </Link>

          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-6 sm:px-8 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-xs hover:shadow transition-all disabled:opacity-50 cursor-pointer transform active:scale-[0.98]"
          >
            <svg
              v-if="form.processing"
              class="animate-spin -ms-1 me-2 h-4 w-4 text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <span v-if="!form.processing">Record Recitation Entry</span>
            <span v-else>Saving Entry...</span>
          </button>
        </div>

      </form>
    </div>
  </div>
</template>
