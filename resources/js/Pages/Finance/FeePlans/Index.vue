<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  plans: {
    type: Array,
    required: true,
  },
});

const showCreateModal = ref(false);
const form = useForm({
  name: '',
  code: '',
  period_type: 'monthly',
  amount: 1500, // in ETB
  currency: 'ETB',
  description: '',
});

const submit = () => {
  // Convert ETB to minor units
  const payload = {
    ...form.data(),
    amount: Math.round(form.amount * 100),
  };

  form.transform(() => payload).post(route('finance.fee-plans.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false;
      form.reset();
    },
  });
};

const formatCurrency = (minorUnits) => {
  if (minorUnits === null || minorUnits === undefined) return '0.00 ETB';
  return (minorUnits / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ETB';
};
</script>

<template>
  <Head title="Fee Plans - Finance" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
          <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <Link :href="route('finance.invoices.index')" class="hover:underline">Finance</Link>
            <span>/</span>
            <span class="text-slate-800 font-bold">Fee Plans</span>
          </div>
          <h1 class="text-2xl font-black text-slate-900">Tuition Fee Plans</h1>
          <p class="text-xs text-slate-500">Configure monthly, term, or one-time tuition structures per course</p>
        </div>

        <button
          @click="showCreateModal = true"
          class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition"
        >
          + Create Fee Plan
        </button>
      </div>

      <!-- Plans Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="plan in plans"
          :key="plan.id"
          class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between"
        >
          <div>
            <div class="flex justify-between items-center mb-3">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                {{ plan.period_type }}
              </span>
              <span v-if="plan.code" class="text-xs font-mono text-slate-400 font-semibold">{{ plan.code }}</span>
            </div>
            <h2 class="text-lg font-black text-slate-900">{{ plan.name }}</h2>
            <p v-if="plan.description" class="text-xs text-slate-500 mt-2 line-clamp-2">{{ plan.description }}</p>
            <div v-if="plan.course" class="text-xs text-emerald-700 font-semibold mt-2">
              Course: {{ plan.course.name }}
            </div>
          </div>

          <div class="pt-4 border-t border-slate-100 mt-6 flex justify-between items-baseline">
            <span class="text-xs text-slate-500">Standard Rate:</span>
            <span class="text-xl font-black text-emerald-800">{{ formatCurrency(plan.amount) }}</span>
          </div>
        </div>

        <div v-if="!plans || plans.length === 0" class="col-span-3 bg-white p-12 text-center text-slate-400 italic rounded-2xl border border-slate-200">
          No fee plans defined yet. Click "+ Create Fee Plan" to set up tuition structures.
        </div>
      </div>

      <!-- Create Plan Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
          <h3 class="text-base font-bold text-slate-900">Create Tuition Fee Plan</h3>

          <form @submit.prevent="submit" class="space-y-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Plan Name</label>
              <input type="text" v-model="form.name" placeholder="e.g. Hifz Program Monthly Tuition" class="w-full rounded-xl border-slate-300 text-xs" required />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Plan Code</label>
                <input type="text" v-model="form.code" placeholder="e.g. FP-HIFZ" class="w-full rounded-xl border-slate-300 text-xs" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Period Type</label>
                <select v-model="form.period_type" class="w-full rounded-xl border-slate-300 text-xs">
                  <option value="monthly">Monthly</option>
                  <option value="term">Per Term</option>
                  <option value="one-time">One-Time</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (ETB)</label>
              <input type="number" step="1" min="1" v-model="form.amount" class="w-full rounded-xl border-slate-300 text-sm font-bold" required />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Description (Optional)</label>
              <textarea v-model="form.description" rows="2" class="w-full rounded-xl border-slate-300 text-xs"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow"
              >
                Create Plan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
