<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  invoice: {
    type: Object,
    required: true,
  },
});

const generateUuid = () => {
  return 'idem-' + Math.random().toString(36).substring(2, 15) + '-' + Date.now();
};

// Payment Modal State
const showPaymentModal = ref(false);
const paymentForm = useForm({
  amount: props.invoice.balance_due ? (props.invoice.balance_due / 100) : 0,
  payment_method: 'cash',
  reference_number: '',
  date: new Date().toISOString().split('T')[0],
  idempotency_key: generateUuid(),
  notes: '',
});

const submitPayment = () => {
  paymentForm.post(route('finance.invoices.payments.store', props.invoice.id), {
    preserveScroll: true,
    onSuccess: () => {
      showPaymentModal.value = false;
      paymentForm.reset();
      paymentForm.idempotency_key = generateUuid();
    },
  });
};

// Reversal Modal State
const showReversalModal = ref(false);
const selectedPayment = ref(null);
const reversalForm = useForm({
  amount: 0,
  type: 'refund',
  reason: '',
  reference_number: '',
  date: new Date().toISOString().split('T')[0],
  idempotency_key: generateUuid(),
});

const openReversalModal = (payment) => {
  selectedPayment.value = payment;
  reversalForm.amount = payment.amount / 100;
  reversalForm.idempotency_key = generateUuid();
  showReversalModal.value = true;
};

const submitReversal = () => {
  if (!selectedPayment.value) return;

  reversalForm.post(route('finance.payments.reversal', selectedPayment.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showReversalModal.value = false;
      reversalForm.reset();
    },
  });
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
  <Head :title="`Invoice ${invoice.invoice_number}`" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-6">
      <!-- Breadcrumb & Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
          <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <Link :href="route('finance.invoices.index')" class="hover:underline">Invoices</Link>
            <span>/</span>
            <span class="text-slate-800 font-mono font-bold">{{ invoice.invoice_number }}</span>
          </div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-black text-slate-900">{{ invoice.title }}</h1>
            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold uppercase border', getStatusBadge(invoice.status)]">
              {{ invoice.status }}
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-1">
            Student: <strong class="text-slate-800">{{ invoice.student?.full_name }}</strong> ({{ invoice.student?.student_code }})
            <span v-if="invoice.period"> · Period: {{ invoice.period }}</span>
          </p>
        </div>

        <div class="flex gap-2">
          <button
            v-if="invoice.status !== 'paid' && invoice.status !== 'void'"
            @click="showPaymentModal = true"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition"
          >
            + Record Payment
          </button>
        </div>
      </div>

      <!-- Financial Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-xs text-slate-500 font-semibold uppercase">Subtotal</div>
          <div class="text-lg font-bold text-slate-900 mt-1">{{ formatCurrency(invoice.subtotal) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-xs text-slate-500 font-semibold uppercase">Discounts</div>
          <div class="text-lg font-bold text-emerald-700 mt-1">-{{ formatCurrency(invoice.discount_total) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-xs text-slate-500 font-semibold uppercase">Total Invoiced</div>
          <div class="text-lg font-black text-slate-900 mt-1">{{ formatCurrency(invoice.total_amount) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <div class="text-xs text-slate-500 font-semibold uppercase">Outstanding Due</div>
          <div class="text-lg font-black mt-1" :class="invoice.balance_due > 0 ? 'text-red-600' : 'text-emerald-600'">
            {{ formatCurrency(invoice.balance_due) }}
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 font-bold text-xs text-slate-700 uppercase">
          Invoice Line Items
        </div>
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="text-slate-500 border-b border-slate-100">
              <th class="p-3">Description</th>
              <th class="p-3 text-right">Base Amount</th>
              <th class="p-3 text-right">Discount</th>
              <th class="p-3 text-right">Final Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in invoice.items" :key="item.id">
              <td class="p-3 font-semibold text-slate-800">{{ item.description }}</td>
              <td class="p-3 text-right">{{ formatCurrency(item.amount) }}</td>
              <td class="p-3 text-right text-emerald-700">-{{ formatCurrency(item.discount_amount) }}</td>
              <td class="p-3 text-right font-bold text-slate-900">{{ formatCurrency(item.final_amount) }}</td>
            </tr>
            <tr v-if="!invoice.items || invoice.items.length === 0">
              <td class="p-3 font-semibold text-slate-800">{{ invoice.title }}</td>
              <td class="p-3 text-right">{{ formatCurrency(invoice.subtotal) }}</td>
              <td class="p-3 text-right text-emerald-700">-{{ formatCurrency(invoice.discount_total) }}</td>
              <td class="p-3 text-right font-bold text-slate-900">{{ formatCurrency(invoice.total_amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Payments Ledger (Append-Only) -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
          <span class="font-bold text-xs text-slate-700 uppercase">Payments & Adjustments Ledger (Append-Only)</span>
          <span class="text-[10px] text-slate-500 italic">Immutable records · Corrections via refund/reversal</span>
        </div>

        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="text-slate-500 border-b border-slate-100 font-bold">
              <th class="p-3">Receipt #</th>
              <th class="p-3">Date</th>
              <th class="p-3">Type</th>
              <th class="p-3">Method</th>
              <th class="p-3">Reference #</th>
              <th class="p-3 text-right">Amount</th>
              <th class="p-3">Recorded By</th>
              <th class="p-3 text-right">Receipt / Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="pmt in invoice.payments" :key="pmt.id" class="hover:bg-slate-50">
              <td class="p-3 font-mono font-bold text-slate-900">{{ pmt.payment_number }}</td>
              <td class="p-3 text-slate-600">{{ pmt.date }}</td>
              <td class="p-3">
                <span
                  :class="[
                    'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                    pmt.type === 'payment' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ pmt.type }}
                </span>
              </td>
              <td class="p-3 capitalize">{{ pmt.payment_method?.replace('_', ' ') }}</td>
              <td class="p-3 text-slate-500 font-mono">{{ pmt.reference_number || '-' }}</td>
              <td class="p-3 text-right font-bold" :class="pmt.type === 'payment' ? 'text-emerald-700' : 'text-red-600'">
                {{ pmt.type === 'payment' ? '+' : '-' }}{{ formatCurrency(pmt.amount) }}
              </td>
              <td class="p-3 text-slate-600">{{ pmt.recorder?.name || 'Staff' }}</td>
              <td class="p-3 text-right space-x-2">
                <a
                  :href="route('finance.payments.receipt', pmt.id)"
                  target="_blank"
                  class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded text-[11px] transition"
                >
                  Receipt 🖨️
                </a>
                <button
                  v-if="pmt.type === 'payment'"
                  @click="openReversalModal(pmt)"
                  class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-700 font-bold rounded text-[11px] transition"
                >
                  Refund
                </button>
              </td>
            </tr>
            <tr v-if="!invoice.payments || invoice.payments.length === 0">
              <td colspan="8" class="p-6 text-center text-slate-400 italic">
                No payments recorded yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Record Payment Modal -->
      <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
          <h3 class="text-base font-bold text-slate-900">Record Payment</h3>
          <p class="text-xs text-slate-500">Invoice: {{ invoice.invoice_number }} · Balance: {{ formatCurrency(invoice.balance_due) }}</p>

          <form @submit.prevent="submitPayment" class="space-y-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (ETB)</label>
              <input
                type="number"
                step="0.01"
                min="0.01"
                v-model="paymentForm.amount"
                class="w-full rounded-xl border-slate-300 text-sm font-bold"
                required
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method</label>
              <select v-model="paymentForm.payment_method" class="w-full rounded-xl border-slate-300 text-xs">
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer (CBE / Awash / Dashen)</option>
                <option value="telebirr">Telebirr</option>
                <option value="cbe_birr">CBE Birr</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Reference # / Bank Slip (Optional)</label>
              <input
                type="text"
                v-model="paymentForm.reference_number"
                placeholder="e.g. CBE-TXN-984321"
                class="w-full rounded-xl border-slate-300 text-xs"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Date</label>
              <input type="date" v-model="paymentForm.date" class="w-full rounded-xl border-slate-300 text-xs" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="showPaymentModal = false"
                class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="paymentForm.processing"
                class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow"
              >
                Save Payment
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Reversal / Refund Modal -->
      <div v-if="showReversalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl">
          <h3 class="text-base font-bold text-red-900">Issue Payment Refund / Reversal</h3>
          <p class="text-xs text-slate-500">Original Payment: {{ selectedPayment?.payment_number }} ({{ formatCurrency(selectedPayment?.amount) }})</p>

          <form @submit.prevent="submitReversal" class="space-y-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Refund Amount (ETB)</label>
              <input
                type="number"
                step="0.01"
                min="0.01"
                v-model="reversalForm.amount"
                class="w-full rounded-xl border-slate-300 text-sm font-bold"
                required
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Type</label>
              <select v-model="reversalForm.type" class="w-full rounded-xl border-slate-300 text-xs">
                <option value="refund">Refund (Money returned to payer)</option>
                <option value="reversal">Reversal (Entry correction / bank bounce)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Reason (Required)</label>
              <input
                type="text"
                v-model="reversalForm.reason"
                placeholder="e.g. Overpayment adjustment / student withdrawal"
                class="w-full rounded-xl border-slate-300 text-xs"
                required
              />
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="showReversalModal = false"
                class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="reversalForm.processing"
                class="px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow"
              >
                Confirm Refund
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
