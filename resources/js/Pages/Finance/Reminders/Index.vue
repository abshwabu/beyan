<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  overdueInvoices: {
    type: Array,
    required: true,
  },
});

const reminderForm = useForm({
  channel: 'sms',
});

const sendReminder = (invoiceId) => {
  reminderForm.post(route('finance.invoices.remind', invoiceId), {
    preserveScroll: true,
  });
};

const formatCurrency = (minorUnits) => {
  if (minorUnits === null || minorUnits === undefined) return '0.00 ETB';
  return (minorUnits / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ETB';
};
</script>

<template>
  <Head title="Overdue Invoices & Payment Reminders" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
          <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <Link :href="route('finance.invoices.index')" class="hover:underline">Finance</Link>
            <span>/</span>
            <span class="text-slate-800 font-bold">Reminders</span>
          </div>
          <h1 class="text-2xl font-black text-slate-900">Overdue Invoices & Reminders</h1>
          <p class="text-xs text-slate-500">Track unpaid tuition invoices past their due dates and trigger reminders</p>
        </div>

        <Link
          :href="route('finance.invoices.index')"
          class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
        >
          ← Back to Invoices
        </Link>
      </div>

      <!-- Overdue Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-red-50/50 border-b border-red-100 text-red-900 font-bold uppercase tracking-wider">
                <th class="p-3.5">Invoice #</th>
                <th class="p-3.5">Student</th>
                <th class="p-3.5">Due Date</th>
                <th class="p-3.5">Days Overdue</th>
                <th class="p-3.5">Balance Due</th>
                <th class="p-3.5">Last Reminder</th>
                <th class="p-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="inv in overdueInvoices" :key="inv.id" class="hover:bg-slate-50">
                <td class="p-3.5 font-bold font-mono text-slate-900">
                  <Link :href="route('finance.invoices.show', inv.id)" class="text-emerald-700 hover:underline">
                    {{ inv.invoice_number }}
                  </Link>
                </td>
                <td class="p-3.5 font-bold text-slate-900">
                  {{ inv.student?.full_name }}
                  <span class="block text-[10px] text-slate-400 font-normal">{{ inv.student?.student_code }}</span>
                </td>
                <td class="p-3.5 text-red-700 font-semibold">{{ inv.due_date }}</td>
                <td class="p-3.5">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                    {{ inv.days_overdue }} days late
                  </span>
                </td>
                <td class="p-3.5 font-black text-red-700 text-sm">
                  {{ formatCurrency(inv.balance_due) }}
                </td>
                <td class="p-3.5 text-slate-500">
                  {{ inv.last_reminder_sent_at ? inv.last_reminder_sent_at.slice(0, 10) : 'Never' }}
                </td>
                <td class="p-3.5 text-right space-x-2">
                  <button
                    @click="sendReminder(inv.id)"
                    :disabled="reminderForm.processing"
                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm transition"
                  >
                    Send Reminder 📲
                  </button>
                  <Link
                    :href="route('finance.invoices.show', inv.id)"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-lg transition"
                  >
                    View
                  </Link>
                </td>
              </tr>
              <tr v-if="!overdueInvoices || overdueInvoices.length === 0">
                <td colspan="7" class="p-8 text-center text-slate-400 italic">
                  🎉 No overdue invoices! All tuition is up to date.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
