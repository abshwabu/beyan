<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
    subscriptions: Object,
    filters: Object,
    plans: Array,
});

const status = ref(props.filters?.status || '');
const overdue = ref(Boolean(props.filters?.overdue));

const applyFilters = () => {
    router.get('/platform/subscriptions', {
        status: status.value,
        overdue: overdue.value ? '1' : '',
    }, {
        preserveState: true,
        replace: true,
    });
};

const extendModalOpen = ref(false);
const activeSubscription = ref(null);
const extendDays = ref(30);

const openExtendModal = (sub) => {
    activeSubscription.value = sub;
    extendDays.value = 30;
    extendModalOpen.value = true;
};

const confirmExtend = () => {
    if (!activeSubscription.value) return;
    router.post(`/platform/subscriptions/${activeSubscription.value.id}/extend`, {
        days: extendDays.value,
    }, {
        onSuccess: () => {
            extendModalOpen.value = false;
        },
    });
};
</script>

<template>
    <PlatformLayout title="Subscriptions">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Active Subscriptions</h1>
                <p class="text-xs text-slate-500 mt-0.5">Track billing periods, grace days, and renewals.</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 shadow-sm flex items-center gap-4 text-xs">
            <div>
                <select v-model="status" @change="applyFilters" class="px-3 py-2 rounded-xl border border-slate-300">
                    <option value="">All Statuses</option>
                    <option value="trial">Trial</option>
                    <option value="active">Active</option>
                    <option value="grace_period">Grace Period</option>
                    <option value="expired">Expired</option>
                </select>
            </div>

            <label class="flex items-center gap-2 font-bold cursor-pointer text-slate-700">
                <input v-model="overdue" @change="applyFilters" type="checkbox" class="rounded text-rose-600" />
                <span>Show Overdue / Grace Only</span>
            </label>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden text-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-start">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-start">Institution</th>
                            <th class="px-4 py-3 text-start">Plan</th>
                            <th class="px-4 py-3 text-start">Status</th>
                            <th class="px-4 py-3 text-start">Cycle</th>
                            <th class="px-4 py-3 text-start">Starts</th>
                            <th class="px-4 py-3 text-start">Ends</th>
                            <th class="px-4 py-3 text-start">Grace</th>
                            <th class="px-5 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="sub in subscriptions.data" :key="sub.id" class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-900">
                                <Link :href="`/platform/institutions/${sub.institution_id}`" class="hover:text-rose-600">
                                    {{ sub.institution?.name || 'Unknown' }}
                                </Link>
                            </td>
                            <td class="px-4 py-3.5 capitalize font-semibold text-slate-700">
                                {{ sub.plan?.name || 'Standard' }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': sub.status === 'active',
                                        'bg-amber-100 text-amber-800': sub.status === 'grace_period' || sub.status === 'trial',
                                        'bg-rose-100 text-rose-800': sub.status === 'expired'
                                    }"
                                >
                                    {{ sub.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 capitalize text-slate-600">{{ sub.billing_cycle }}</td>
                            <td class="px-4 py-3.5 font-mono text-[11px] text-slate-500">
                                {{ new Date(sub.starts_at).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-[11px]" :class="new Date(sub.ends_at) < new Date() ? 'text-rose-600 font-bold' : 'text-slate-800 font-semibold'">
                                {{ sub.ends_at ? new Date(sub.ends_at).toLocaleDateString() : 'N/A' }}
                            </td>
                            <td class="px-4 py-3.5 text-slate-500">{{ sub.grace_days }} days</td>
                            <td class="px-5 py-3.5 text-end">
                                <button
                                    @click="openExtendModal(sub)"
                                    type="button"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-lg"
                                >
                                    Extend &rarr;
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Extend Modal -->
        <div v-if="extendModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-xl border border-slate-200">
                <h3 class="text-base font-bold text-slate-900 mb-2">Extend Subscription</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Extending for <strong>{{ activeSubscription?.institution?.name }}</strong>.
                </p>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Days to add:</label>
                    <input v-model.number="extendDays" type="number" min="1" max="365" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="extendModalOpen = false" type="button" class="px-3 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl">Cancel</button>
                    <button @click="confirmExtend" type="button" class="px-4 py-2 bg-rose-600 text-white text-xs font-bold rounded-xl hover:bg-rose-700">Apply</button>
                </div>
            </div>
        </div>
    </PlatformLayout>
</template>
