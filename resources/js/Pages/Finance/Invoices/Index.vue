<script setup>
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  invoices: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

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

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">
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
    </div>
  </div>
</template>
