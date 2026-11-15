<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
    institution: Object,
    owner: Object,
    usage: Object,
    auditLogs: Array,
});

const trialDays = ref(14);
const extendTrial = () => {
    router.post(`/platform/institutions/${props.institution.id}/extend-trial`, {
        days: trialDays.value,
    });
};

const impersonateModalOpen = ref(false);
const impersonateReason = ref('Assisting administrator with student class enrollments');

const confirmImpersonate = () => {
    router.post(`/platform/institutions/${props.institution.id}/impersonate`, {
        reason: impersonateReason.value,
    });
};

const toggleStatus = () => {
    const action = props.institution.status === 'active' ? 'suspend' : 'activate';
    if (confirm(`Are you sure you want to ${action} this institution?`)) {
        router.post(`/platform/institutions/${props.institution.id}/${action}`);
    }
};

const deleteInstitution = () => {
    if (confirm(`Permanently soft-delete institution '${props.institution.name}'?`)) {
        router.delete(`/platform/institutions/${props.institution.id}`);
    }
};
</script>

<template>
    <PlatformLayout :title="`Institution: ${institution.name}`">
        
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ institution.name }}</h1>
                    <span
                        class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                        :class="institution.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                    >
                        {{ institution.status }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-mono mt-1">ID: {{ institution.id }} · /{{ institution.slug }} · {{ institution.timezone }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-if="$page.props.auth?.user?.role === 'platform_super_admin'"
                    @click="impersonateModalOpen = true"
                    type="button"
                    class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-900 font-extrabold rounded-xl text-xs shadow-sm transition"
                >
                    🔑 Impersonate Owner
                </button>
                <Link
                    :href="`/platform/institutions/${institution.id}/edit`"
                    class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold rounded-xl text-xs shadow-sm"
                >
                    Edit Profile
                </Link>
                <button
                    @click="toggleStatus"
                    type="button"
                    class="px-3 py-2 rounded-xl text-xs font-bold transition"
                    :class="institution.status === 'active' ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                >
                    {{ institution.status === 'active' ? 'Suspend Madrasa' : 'Activate Madrasa' }}
                </button>
                <button
                    v-if="$page.props.auth?.user?.role === 'platform_super_admin'"
                    @click="deleteInstitution"
                    type="button"
                    class="px-3 py-2 bg-slate-200 hover:bg-rose-100 text-slate-600 hover:text-rose-700 rounded-xl text-xs font-bold"
                >
                    Delete
                </button>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Enrolled Students</span>
                <span class="text-3xl font-black text-slate-900">{{ usage.student_count }}</span>
                <p class="text-[11px] text-slate-400 mt-2">Aggregate total (No bulk records shown)</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Teachers Onboarded</span>
                <span class="text-3xl font-black text-slate-900">{{ usage.teacher_count }}</span>
                <p class="text-[11px] text-slate-400 mt-2">Active halaqah instructors</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Subscription Plan</span>
                <span class="text-2xl font-black text-slate-900 capitalize">{{ institution.subscription_plan || 'Trial' }}</span>
                <p class="text-[11px] text-slate-500 mt-2 font-mono">
                    Ends: {{ institution.subscription_ends_at ? new Date(institution.subscription_ends_at).toLocaleDateString() : 'None' }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            
            <!-- Left 2 Cols: Details & Owner -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Profile Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Madrasa Profile & Owner Contact</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 font-bold uppercase text-[10px] block">Campus Mode</span>
                            <span class="font-semibold text-slate-800 capitalize">{{ institution.mode }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold uppercase text-[10px] block">Currency & Locale</span>
                            <span class="font-semibold text-slate-800">{{ institution.currency }} · {{ institution.locale?.toUpperCase() }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold uppercase text-[10px] block">Primary Owner Name</span>
                            <span class="font-semibold text-slate-800">{{ owner?.name || 'Unassigned' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold uppercase text-[10px] block">Owner Contact Email</span>
                            <span class="font-semibold text-slate-800 font-mono">{{ owner?.email || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold uppercase text-[10px] block">Owner Phone</span>
                            <span class="font-semibold text-slate-800 font-mono">{{ owner?.phone || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold uppercase text-[10px] block">Created Date</span>
                            <span class="font-semibold text-slate-800">{{ new Date(institution.created_at).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Audit Events for Institution -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Institutional Audit History</h2>
                    <div v-if="auditLogs?.length" class="space-y-3">
                        <div v-for="log in auditLogs" :key="log.id" class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 text-xs flex justify-between items-center">
                            <div>
                                <span class="font-bold text-slate-900">{{ log.event }}</span>
                                <span class="text-[11px] text-slate-500 block">By {{ log.user?.name || 'System' }} · {{ new Date(log.created_at).toLocaleString() }}</span>
                            </div>
                            <span class="font-mono text-[10px] text-slate-400">{{ log.ip_address || '127.0.0.1' }}</span>
                        </div>
                    </div>
                    <div v-else class="text-xs text-slate-400 py-4 text-center">
                        No audit events recorded for this madrasa.
                    </div>
                </div>

            </div>

            <!-- Right Col: Subscription Controls -->
            <div class="space-y-6">
                
                <!-- Extend Trial Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 mb-2">Extend Subscription / Trial</h3>
                    <p class="text-xs text-slate-500 mb-4">Add additional grace or trial days without recording a manual payment.</p>
                    
                    <div class="flex items-center gap-2 mb-3">
                        <input
                            v-model.number="trialDays"
                            type="number"
                            min="1"
                            max="90"
                            class="w-24 px-3 py-2 text-xs border border-slate-300 rounded-xl"
                        />
                        <span class="text-xs text-slate-600 font-medium">days</span>
                        <button
                            @click="extendTrial"
                            type="button"
                            class="flex-1 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-sm"
                        >
                            + Extend
                        </button>
                    </div>
                </div>

                <!-- Record Payment Quick Link -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white shadow-sm">
                    <h3 class="text-sm font-bold mb-1">Manual Subscription Payment</h3>
                    <p class="text-xs text-slate-300 mb-4">Record Ethiopian Bank Transfer (CBE), Telebirr, or cash receipt.</p>
                    <Link
                        :href="`/platform/payments`"
                        class="block text-center py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-sm"
                    >
                        Go to Payments Ledger &rarr;
                    </Link>
                </div>

            </div>

        </div>

        <!-- Impersonate Modal -->
        <div v-if="impersonateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold">⚠️</span>
                    <h3 class="text-lg font-bold text-slate-900">Initiate Support Impersonation</h3>
                </div>
                <p class="text-xs text-slate-500 mb-4">
                    Access <strong>{{ institution.name }}</strong> as an administrator. Session is strictly limited to 30 minutes and written to the platform audit log.
                </p>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mandatory Justification / Reason:</label>
                    <textarea
                        v-model="impersonateReason"
                        rows="3"
                        required
                        class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="impersonateModalOpen = false" type="button" class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-200">
                        Cancel
                    </button>
                    <button @click="confirmImpersonate" type="button" class="px-4 py-2 bg-amber-500 text-slate-900 text-xs font-extrabold rounded-xl hover:bg-amber-600">
                        Confirm & Log In (30m) &rarr;
                    </button>
                </div>
            </div>
        </div>

    </PlatformLayout>
</template>
