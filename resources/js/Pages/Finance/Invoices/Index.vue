<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
  invoices: {
    type: Object,
    required: true,
  },
  linkedStudents: {
    type: Array,
    default: () => [],
  },
  currentPeriod: {
    type: String,
    default: () => new Date().toISOString().slice(0, 7),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const page = usePage();

const showGenerateModal = ref(false);
const selectedStudent = ref(null);

const generateForm = useForm({
  student_id: '',
  fee_plan_id: '',
  period: props.currentPeriod,
  due_date: new Date(Date.now() + 15 * 86400000).toISOString().slice(0, 10),
});

const openGenerateModal = (student) => {
  selectedStudent.value = student;
  generateForm.student_id = student.id;
  generateForm.fee_plan_id = student.fee_plan?.id || '';
  generateForm.period = props.currentPeriod;
  generateForm.clearErrors();
  showGenerateModal.value = true;
};

const submitGenerateInvoice = () => {
  generateForm.post(route('finance.invoices.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showGenerateModal.value = false;
      selectedStudent.value = null;
    },
  });
};

const filterByStatus = (status) => {
  router.get(route('finance.invoices.index'), { ...props.filters, status }, { preserveState: true });
};

const formatCurrency = (minorUnits) => {
  if (minorUnits === null || minorUnits === undefined) return '0.00 ETB';
  return (minorUnits / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ETB';
};

const getStatusBadge = (status) => {
  switch (status) {
    case 'paid':
      return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    case 'partial':
      return 'bg-amber-100 text-amber-800 border-amber-200';
    case 'void':
      return 'bg-slate-200 text-slate-700 border-slate-300';
    default:
      return 'bg-red-100 text-red-800 border-red-200';
  }
};
</script>

<template>
  <Head title="Fee Invoices - Finance" />

  <div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8" dir="ltr">
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Flash Messages -->
      <div v-if="page.props.flash?.success" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold">
        ✓ {{ page.props.flash.success }}
      </div>
      <div v-if="page.props.flash?.error" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold">
        ✕ {{ page.props.flash.error }}
      </div>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
          <h1 class="text-2xl font-black text-slate-900">Student Fee Invoices</h1>
          <p class="text-xs text-slate-500">Manage term & monthly tuition fees, discounts, and payments</p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Link
            :href="route('finance.fee-plans.index')"
            class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
          >
            Fee Plans
          </Link>
          <Link
            :href="route('finance.reminders.index')"
            class="px-3.5 py-2 text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 rounded-xl transition"
          >
            Overdue Reminders
          </Link>
          <Link
            :href="route('finance.reports.income-expense')"
            class="px-3.5 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition"
          >
            Income vs Expense
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex gap-2 text-xs font-semibold">
        <button
          @click="filterByStatus(null)"
          :class="['px-3 py-1.5 rounded-lg border transition', !filters.status ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200']"
        >
          All
        </button>
        <button
          @click="filterByStatus('unpaid')"
          :class="['px-3 py-1.5 rounded-lg border transition', filters.status === 'unpaid' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-slate-700 border-slate-200']"
        >
          Unpaid
        </button>
        <button
          @click="filterByStatus('partial')"
          :class="['px-3 py-1.5 rounded-lg border transition', filters.status === 'partial' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-200']"
        >
          Partial
        </button>
        <button
          @click="filterByStatus('paid')"
          :class="['px-3 py-1.5 rounded-lg border transition', filters.status === 'paid' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-700 border-slate-200']"
        >
          Paid
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                <th class="p-3.5">Invoice #</th>
                <th class="p-3.5">Student</th>
                <th class="p-3.5">Fee Plan / Period</th>
                <th class="p-3.5">Total Amount</th>
                <th class="p-3.5">Balance Due</th>
                <th class="p-3.5">Status</th>
                <th class="p-3.5">Due Date</th>
                <th class="p-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-slate-50">
                <td class="p-3.5 font-bold text-slate-900 font-mono">
                  <Link :href="route('finance.invoices.show', inv.id)" class="text-emerald-700 hover:underline">
                    {{ inv.invoice_number }}
                  </Link>
                </td>
                <td class="p-3.5 font-bold text-slate-900">
                  {{ inv.student?.full_name || inv.student?.name }}
                  <span class="block text-[10px] text-slate-400 font-normal">{{ inv.student?.student_code }}</span>
                </td>
                <td class="p-3.5 text-slate-700">
                  {{ inv.title }}
                  <span v-if="inv.period" class="block text-[10px] text-slate-400 font-semibold">{{ inv.period }}</span>
                </td>
                <td class="p-3.5 font-semibold text-slate-900">
                  {{ formatCurrency(inv.total_amount || Math.round(inv.amount * 100)) }}
                </td>
                <td class="p-3.5 font-black text-slate-900">
                  <span :class="inv.balance_due > 0 ? 'text-red-600' : 'text-emerald-600'">
                    {{ formatCurrency(inv.balance_due) }}
                  </span>
                </td>
                <td class="p-3.5">
                  <span :class="['px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border', getStatusBadge(inv.status)]">
                    {{ inv.status }}
                  </span>
                </td>
                <td class="p-3.5 text-slate-500 whitespace-nowrap">{{ inv.due_date }}</td>
                <td class="p-3.5 text-right">
                  <Link
                    :href="route('finance.invoices.show', inv.id)"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-lg transition"
                  >
                    View / Pay →
                  </Link>
                </td>
              </tr>
              <tr v-if="!invoices.data || invoices.data.length === 0">
                <td colspan="8" class="p-8 text-center text-slate-400 italic">
                  No invoices found matching current criteria.
                </td>
              </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Students Linked to Fee Plans -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h2 class="text-base font-bold text-slate-900">Students Linked to Fee Plans</h2>
          <p class="text-xs text-slate-500">All students assigned a tuition fee plan and their billing status for {{ currentPeriod }}</p>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 self-start sm:self-auto">
          {{ linkedStudents.length }} Linked Student(s)
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
              <th class="p-3.5">Student</th>
              <th class="p-3.5">Linked Fee Plan</th>
              <th class="p-3.5">Standard Rate</th>
              <th class="p-3.5">{{ currentPeriod }} Status</th>
              <th class="p-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="st in linkedStudents" :key="st.id" class="hover:bg-slate-50">
              <td class="p-3.5 font-bold text-slate-900">
                <Link :href="route('students.show', st.id)" class="text-emerald-700 hover:underline">
                  {{ st.full_name }}
                </Link>
                <span class="block text-[10px] font-mono text-slate-400 font-normal">{{ st.student_code }}</span>
              </td>
              <td class="p-3.5 font-semibold text-slate-800">
                {{ st.fee_plan?.name || 'N/A' }}
                <span class="block text-[10px] uppercase text-slate-400">{{ st.fee_plan?.period_type }}</span>
              </td>
              <td class="p-3.5 font-bold text-slate-900">
                {{ formatCurrency(st.fee_plan?.amount) }}
              </td>
              <td class="p-3.5">
                <span
                  v-if="st.current_invoice"
                  :class="['px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border', getStatusBadge(st.current_invoice.status)]"
                >
                  {{ st.current_invoice.status }} ({{ st.current_invoice.invoice_number }})
                </span>
                <span v-else class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                  Not Billed Yet
                </span>
              </td>
              <td class="p-3.5 text-right">
                <Link
                  v-if="st.current_invoice"
                  :href="route('finance.invoices.show', st.current_invoice.id)"
                  class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-lg transition mr-2"
                >
                  View Invoice &rarr;
                </Link>
                <button
                  type="button"
                  @click="openGenerateModal(st)"
                  class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition"
                >
                  + Generate Invoice
                </button>
              </td>
            </tr>
            <tr v-if="!linkedStudents || linkedStudents.length === 0">
              <td colspan="5" class="p-8 text-center text-slate-400 italic">
                No students are linked to a fee plan yet. Assign a fee plan during student registration or on a student's profile.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Generate Invoice Modal -->
  <div v-if="showGenerateModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h3 class="text-base font-bold text-slate-900">Generate Fee Invoice</h3>
        <button type="button" @click="showGenerateModal = false" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
      </div>

      <div v-if="selectedStudent" class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-0.5">
        <div class="font-bold text-slate-900">{{ selectedStudent.full_name }} ({{ selectedStudent.student_code }})</div>
        <div class="text-slate-600">Plan: <strong>{{ selectedStudent.fee_plan?.name }}</strong> — {{ formatCurrency(selectedStudent.fee_plan?.amount) }}</div>
      </div>

      <form @submit.prevent="submitGenerateInvoice" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700">Billing Period * (e.g. 2026-11 or TERM-1)</label>
          <input
            v-model="generateForm.period"
            type="text"
            required
            class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
          />
          <p v-if="generateForm.errors.period" class="text-xs text-rose-600 mt-1">{{ generateForm.errors.period }}</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700">Due Date *</label>
          <input
            v-model="generateForm.due_date"
            type="date"
            required
            class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
          />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            type="button"
            @click="showGenerateModal = false"
            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="generateForm.processing"
            class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm disabled:opacity-50"
          >
            {{ generateForm.processing ? 'Generating...' : 'Generate Invoice' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
</template>
