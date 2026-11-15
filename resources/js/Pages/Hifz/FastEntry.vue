<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

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

// Filter surahs for quick selection
const filteredSurahs = computed(() => {
  if (!surahSearch.value) return props.surahs;
  const q = surahSearch.value.toLowerCase();
  return props.surahs.filter(
    (s) =>
      s.english_name.toLowerCase().includes(q) ||
      s.arabic_name.includes(q) ||
      String(s.number).includes(q)
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
      }, 3000);
      // Advance start_ayah for next entry if was sabaq
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
</script>

<template>
  <Head title="Fast Qur'an / Hifz Entry" />

  <div class="min-h-screen bg-slate-50 py-6 px-3 sm:px-6">
    <div class="max-w-xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <!-- Header -->
      <div class="bg-emerald-700 px-6 py-4 text-white flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold">Fast Qur'an Entry</h1>
          <p class="text-xs text-emerald-100">Log Sabaq, Sabqi, or Manzil in under 20 seconds</p>
        </div>
        <Link
          v-if="form.student_id"
          :href="route('hifz.student.profile', form.student_id)"
          class="text-xs bg-emerald-800 hover:bg-emerald-900 text-white px-3 py-1.5 rounded-lg border border-emerald-600 transition"
        >
          View Profile
        </Link>
      </div>

      <!-- Notification -->
      <div v-if="saveSuccess" class="bg-emerald-50 border-b border-emerald-200 p-3 text-sm text-emerald-800 text-center font-medium animate-pulse">
        ✓ Entry saved successfully!
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-5">
        <!-- 1. Select Student -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Student</label>
          <select
            v-model="form.student_id"
            @change="onStudentChange"
            class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-medium"
          >
            <option v-for="s in students" :key="s.id" :value="s.id">
              {{ s.full_name }} ({{ s.student_code }})
            </option>
          </select>
          <div v-if="form.errors.student_id" class="text-red-500 text-xs mt-1">{{ form.errors.student_id }}</div>

          <!-- Last Sabaq Context Pill -->
          <div v-if="last_sabaq" class="mt-2 text-xs bg-slate-100 text-slate-700 p-2 rounded-lg flex items-center justify-between">
            <span>Last Sabaq: <strong class="text-emerald-700">{{ last_sabaq.surah_name }} (Ayah {{ last_sabaq.end_ayah }})</strong></span>
            <span class="text-slate-500">{{ last_sabaq.date }}</span>
          </div>
        </div>

        <!-- 2. Entry Type (Sabaq, Sabqi, Manzil, Reading) -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Entry Type</label>
          <div class="grid grid-cols-4 gap-2">
            <button
              type="button"
              @click="form.type = 'sabaq'"
              :class="[
                'py-2 px-2 rounded-xl text-xs font-bold text-center border transition',
                form.type === 'sabaq'
                  ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                  : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
              ]"
            >
              Sabaq
              <span class="block text-[10px] font-normal opacity-80">New</span>
            </button>
            <button
              type="button"
              @click="form.type = 'sabqi'"
              :class="[
                'py-2 px-2 rounded-xl text-xs font-bold text-center border transition',
                form.type === 'sabqi'
                  ? 'bg-amber-600 text-white border-amber-600 shadow-sm'
                  : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
              ]"
            >
              Sabqi
              <span class="block text-[10px] font-normal opacity-80">Recent</span>
            </button>
            <button
              type="button"
              @click="form.type = 'manzil'"
              :class="[
                'py-2 px-2 rounded-xl text-xs font-bold text-center border transition',
                form.type === 'manzil'
                  ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                  : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
              ]"
            >
              Manzil
              <span class="block text-[10px] font-normal opacity-80">Cycle</span>
            </button>
            <button
              type="button"
              @click="form.type = 'reading'"
              :class="[
                'py-2 px-2 rounded-xl text-xs font-bold text-center border transition',
                form.type === 'reading'
                  ? 'bg-sky-600 text-white border-sky-600 shadow-sm'
                  : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
              ]"
            >
              Reading
              <span class="block text-[10px] font-normal opacity-80">Tilawah</span>
            </button>
          </div>
          <div v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</div>
        </div>

        <!-- 3. Surah and Ayah Range -->
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="block text-xs font-semibold text-slate-700 uppercase">Surah</label>
              <span v-if="selectedSurah" class="text-xs text-slate-500 font-arabic">
                {{ selectedSurah.arabic_name }} · {{ selectedSurah.total_ayahs }} Ayahs
              </span>
            </div>
            <select
              v-model="form.surah_number"
              class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-medium"
            >
              <option v-for="s in surahs" :key="s.number" :value="s.number">
                {{ s.number }}. {{ s.english_name }} ({{ s.arabic_name }})
              </option>
            </select>
            <div v-if="form.errors.surah_number" class="text-red-500 text-xs mt-1">{{ form.errors.surah_number }}</div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">From Ayah</label>
              <input
                type="number"
                v-model.number="form.start_ayah"
                min="1"
                :max="selectedSurah ? selectedSurah.total_ayahs : 286"
                class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-semibold text-center"
              />
              <div v-if="form.errors.start_ayah" class="text-red-500 text-xs mt-1">{{ form.errors.start_ayah }}</div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">To Ayah</label>
              <input
                type="number"
                v-model.number="form.end_ayah"
                :min="form.start_ayah || 1"
                :max="selectedSurah ? selectedSurah.total_ayahs : 286"
                class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-semibold text-center"
              />
              <div v-if="form.errors.end_ayah" class="text-red-500 text-xs mt-1">{{ form.errors.end_ayah }}</div>
            </div>
          </div>
        </div>

        <!-- 4. Quality & Tajweed Rating -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Memorization</label>
            <select
              v-model="form.quality_rating"
              class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"
            >
              <option value="excellent">Excellent (Mumtaz)</option>
              <option value="very_good">Very Good (Jayyid Jiddan)</option>
              <option value="good">Good (Jayyid)</option>
              <option value="acceptable">Acceptable (Maqbool)</option>
              <option value="poor">Needs Revision (Da'eef)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tajweed</label>
            <select
              v-model="form.tajweed_rating"
              class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"
            >
              <option value="excellent">Excellent</option>
              <option value="good">Good</option>
              <option value="needs_work">Needs Work</option>
            </select>
          </div>
        </div>

        <!-- 5. Mistakes & Comment -->
        <div class="grid grid-cols-3 gap-3 items-center">
          <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Mistakes</label>
            <div class="flex items-center space-x-1">
              <button
                type="button"
                @click="decrementMistakes"
                class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 font-bold hover:bg-slate-300 flex items-center justify-center"
              >
                -
              </button>
              <input
                type="number"
                v-model.number="form.mistakes_count"
                min="0"
                class="w-12 text-center rounded-lg border-slate-300 text-sm py-1 font-bold"
              />
              <button
                type="button"
                @click="incrementMistakes"
                class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 font-bold hover:bg-slate-300 flex items-center justify-center"
              >
                +
              </button>
            </div>
          </div>

          <div class="col-span-2">
            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teacher Comment (Optional)</label>
            <input
              type="text"
              v-model="form.teacher_comment"
              placeholder="e.g. Needs focus on madd munfasil"
              class="w-full rounded-xl border-slate-300 text-xs py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            />
          </div>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-xl shadow-md transition flex items-center justify-center space-x-2 disabled:opacity-50"
        >
          <span v-if="form.processing">Saving...</span>
          <span v-else>Record Entry (Save)</span>
        </button>
      </form>
    </div>
  </div>
</template>
