<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
    payments: Object,
    filters: Object,
    institutions: Array,
    totalCompleted: Number,
});

const method = ref(props.filters?.method || '');
const status = ref(props.filters?.status || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

const applyFilters = () => {
    router.get('/platform/payments', {
        method: method.value,
        status: status.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Record Payment Modal
const paymentModalOpen = ref(false);
const form = useForm({
    institution_id: props.institutions?.[0]?.id || '',
    amount_major: 1400,
    currency: 'ETB',
    method: 'bank_transfer',
    billing_cycle: 'monthly',
    reference: '',
    note: '',
    idempotency_key: '',
});

const openRecordModal = () => {
    form.reset();
    form.institution_id = props.institutions?.[0]?.id || '';
    form.amount_major = 1400;
    form.currency = 'ETB';
    form.method = 'bank_transfer';
    form.billing_cycle = 'monthly';
    form.idempotency_key = 'PAY-' + Date.now();
    paymentModalOpen.value = true;
};

const submitPayment = () => {
    form.post('/platform/payments', {
        onSuccess: () => {
            paymentModalOpen.value = false;
        },
    });
};

// Reverse Payment Modal
const reverseModalOpen = ref(false);
const activePayment = ref(null);
const reverseReason = ref('');

const openReverseModal = (p) => {
    activePayment.value = p;
    reverseReason.value = '';
    reverseModalOpen.value = true;
};

const confirmReverse = () => {
    if (!activePayment.value) return;
    router.post(`/platform/payments/${activePayment.value.id}/reverse`, {
        reason: reverseReason.value,
    }, {
        onSuccess: () => {
            reverseModalOpen.value = false;
        },
    });
};
</script>

<template>
    <PlatformLayout title="Subscription Payments">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Subscription Payments Ledger</h1>
                <p class="text-xs text-slate-500 mt-0.5">Record and reconcile manual cash, CBE bank transfer, and Telebirr receipts.</p>
            </div>
            <div class="flex items-center gap-3">
                <a
                    href="/platform/payments/export"
                    class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold shadow-sm transition"
                >
                    📥 Export Excel (CSV)
                </a>
                <button
                    @click="openRecordModal"
                    type="button"
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition"
                >
                    + Record Payment
                </button>
            </div>
        </div>

        <!-- Total Revenue Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Platform Revenue Settled</span>
                <span class="text-3xl font-black text-slate-900">{{ totalCompleted?.toLocaleString() }} <span class="text-sm font-bold text-slate-400">ETB</span></span>
            </div>
            <div class="text-xs text-slate-500 font-medium">
                Append-only ledger · Double-entry protected via unique idempotency keys
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 shadow-sm flex flex-wrap items-center gap-3 text-xs">
            <div>
                <select v-model="method" @change="applyFilters" class="px-3 py-2 rounded-xl border border-slate-300">
                    <option value="">All Payment Methods</option>
                    <option value="bank_transfer">Bank Transfer (CBE)</option>
                    <option value="mobile_money">Mobile Money (Telebirr)</option>
                    <option value="cash">Cash</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div>
                <select v-model="status" @change="applyFilters" class="px-3 py-2 rounded-xl border border-slate-300">
                    <option value="">All Statuses</option>
                    <option value="completed">Completed</option>
                    <option value="reversed">Reversed</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input v-model="dateFrom" type="date" class="px-3 py-2 rounded-xl border border-slate-300" />
                <span class="text-slate-400">to</span>
                <input v-model="dateTo" type="date" class="px-3 py-2 rounded-xl border border-slate-300" />
                <button @click="applyFilters" type="button" class="px-3 py-2 bg-slate-900 text-white rounded-xl font-bold">
                    Filter
                </button>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden text-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-start">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-start">Institution</th>
                            <th class="px-4 py-3 text-start">Amount</th>
                            <th class="px-4 py-3 text-start">Method</th>
                            <th class="px-4 py-3 text-start">Reference</th>
                            <th class="px-4 py-3 text-start">Date</th>
                            <th class="px-4 py-3 text-start">Status</th>
                            <th class="px-4 py-3 text-start">Recorded By</th>
                            <th class="px-5 py-3 text-end">Receipt / Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in payments.data" :key="p.id" class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-900">
                                <Link :href="`/platform/institutions/${p.institution_id}`" class="hover:text-rose-600">
                                    {{ p.institution?.name || 'Unknown' }}
                                </Link>
                            </td>
                            <td class="px-4 py-3.5 font-black text-slate-900">
                                {{ (p.amount / 100).toLocaleString() }} {{ p.currency }}
                            </td>
                            <td class="px-4 py-3.5 capitalize font-medium text-slate-700">
                                {{ p.method.replace('_', ' ') }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-[11px] text-slate-500">
                                {{ p.reference || '—' }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-[11px] text-slate-500">
                                {{ new Date(p.paid_at).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                                    :class="p.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                >
                                    {{ p.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-slate-500">{{ p.recorded_by }}</td>
                            <td class="px-5 py-3.5 text-end space-x-2">
                                <a
                                    :href="`/platform/payments/${p.id}/receipt`"
                                    target="_blank"
                                    class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg"
                                >
                                    Receipt 🖨️
                                </a>
                                <button
                                    v-if="p.status === 'completed'"
                                    @click="openReverseModal(p)"
                                    type="button"
                                    class="px-2 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold rounded-lg"
                                >
                                    Reverse
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Record Payment Modal -->
        <div v-if="paymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
                <h3 class="text-base font-bold text-slate-900 mb-2">Record Subscription Payment</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Extends the selected madrasa's subscription period automatically.
                </p>

                <form @submit.prevent="submitPayment" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Target Institution *</label>
                        <select v-model="form.institution_id" required class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                            <option v-for="inst in institutions" :key="inst.id" :value="inst.id">{{ inst.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Amount Paid (ETB) *</label>
                            <input v-model.number="form.amount_major" type="number" required min="1" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Billing Cycle *</label>
                            <select v-model="form.billing_cycle" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                                <option value="monthly">Monthly (+30 days)</option>
                                <option value="yearly">Yearly (+365 days)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Payment Method *</label>
                            <select v-model="form.method" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                                <option value="bank_transfer">CBE / Bank Transfer</option>
                                <option value="mobile_money">Telebirr</option>
                                <option value="cash">Cash</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Reference / Slip Number</label>
                            <input v-model="form.reference" type="text" placeholder="e.g. FT26012498" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Idempotency Key (Race Protection)</label>
                        <input v-model="form.idempotency_key" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-[11px] text-slate-500" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Admin Note (optional)</label>
                        <input v-model="form.note" type="text" placeholder="Deposit slip confirmed by accounting" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button @click="paymentModalOpen = false" type="button" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-md">
                            Record Payment & Extend
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reverse Modal -->
        <div v-if="reverseModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-xl border border-slate-200">
                <h3 class="text-base font-bold text-slate-900 mb-2">Reverse Payment Record</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Reversals are append-only. Please provide an audit reason for cancelling this payment.
                </p>
                <div class="mb-4 text-xs">
                    <label class="block font-bold text-slate-700 uppercase mb-1">Reversal Reason *</label>
                    <textarea v-model="reverseReason" rows="3" required placeholder="e.g. Bank chargeback / incorrect slip..." class="w-full px-3 py-2 border border-slate-300 rounded-xl"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="reverseModalOpen = false" type="button" class="px-3 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl">Cancel</button>
                    <button @click="confirmReverse" type="button" class="px-4 py-2 bg-rose-600 text-white text-xs font-bold rounded-xl hover:bg-rose-700">Confirm Reversal</button>
                </div>
            </div>
        </div>
    </PlatformLayout>
</template>
