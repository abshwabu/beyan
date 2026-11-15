<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  report: {
    type: Object,
    required: true,
  },
});

const startDate = ref(props.report.period.start_date);
const endDate = ref(props.report.period.end_date);

const filterPeriod = () => {
  router.get(
    route('finance.reports.income-expense'),
    { start_date: startDate.value, end_date: endDate.value },
    { preserveState: true }
  );
};

const formatCurrency = (minorUnits) => {
  if (minorUnits === null || minorUnits === undefined) return '0.00 ETB';
  return (minorUnits / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ETB';
};
</script>

<template>
  <Head title="Income vs Expense Report - Finance" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
          <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <Link :href="route('finance.invoices.index')" class="hover:underline">Finance</Link>
            <span>/</span>
            <span class="text-slate-800 font-bold">Reports</span>
          </div>
          <h1 class="text-2xl font-black text-slate-900">Income vs Expense Report</h1>
          <p class="text-xs text-slate-500">Overview of student fee collections, refunds, operational expenses, and net surplus/deficit</p>
        </div>

        <!-- Date Range Filter -->
        <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-xl border border-slate-200">
          <input type="date" v-model="startDate" class="text-xs rounded-lg border-slate-300 py-1" />
          <span class="text-xs text-slate-400">to</span>
          <input type="date" v-model="endDate" class="text-xs rounded-lg border-slate-300 py-1" />
          <button
            @click="filterPeriod"
            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition"
          >
            Apply
          </button>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-xs text-slate-500 font-bold uppercase tracking-wider">Gross Income</div>
          <div class="text-2xl font-black text-slate-900 mt-2">{{ formatCurrency(report.income.gross_amount) }}</div>
          <div class="text-[11px] text-slate-400 mt-1">Refunds: {{ formatCurrency(report.income.refunds_amount) }}</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-xs text-slate-500 font-bold uppercase tracking-wider">Net Income</div>
          <div class="text-2xl font-black text-emerald-700 mt-2">{{ formatCurrency(report.income.net_amount) }}</div>
          <div class="text-[11px] text-slate-400 mt-1">From tuition & fees</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-xs text-slate-500 font-bold uppercase tracking-wider">Total Expenses</div>
          <div class="text-2xl font-black text-red-600 mt-2">{{ formatCurrency(report.expenses.total_amount) }}</div>
          <div class="text-[11px] text-slate-400 mt-1">Operational costs</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-xs text-slate-500 font-bold uppercase tracking-wider">Net Financial Position</div>
          <div
            class="text-2xl font-black mt-2"
            :class="report.is_surplus ? 'text-emerald-700' : 'text-red-700'"
          >
            {{ formatCurrency(report.net_surplus_deficit) }}
          </div>
          <div class="text-[11px] font-bold mt-1" :class="report.is_surplus ? 'text-emerald-600' : 'text-red-600'">
            {{ report.is_surplus ? 'Net Operating Surplus' : 'Net Operating Deficit' }}
          </div>
        </div>
      </div>

      <!-- Detailed Breakdown Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Expenses By Category -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
          <h2 class="text-sm font-bold text-slate-900 uppercase">Expenses by Category</h2>
          <div class="space-y-3">
            <div
              v-for="(amount, category) in report.expenses.by_category"
              :key="category"
              class="flex justify-between items-center py-2 border-b border-slate-100 text-xs"
            >
              <span class="font-medium capitalize text-slate-700">{{ category }}</span>
              <span class="font-bold text-slate-900">{{ formatCurrency(amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Income by Payment Method -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
          <h2 class="text-sm font-bold text-slate-900 uppercase">Collections by Payment Method</h2>
          <div class="space-y-3">
            <div
              v-for="(amount, method) in report.income.by_method"
              :key="method"
              class="flex justify-between items-center py-2 border-b border-slate-100 text-xs"
            >
              <span class="font-medium capitalize text-slate-700">{{ method.replace('_', ' ') }}</span>
              <span class="font-bold text-emerald-700">{{ formatCurrency(amount) }}</span>
            </div>
            <div v-if="!report.income.by_method || Object.keys(report.income.by_method).length === 0" class="text-xs text-slate-400 italic py-4 text-center">
              No payments collected during this period.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
