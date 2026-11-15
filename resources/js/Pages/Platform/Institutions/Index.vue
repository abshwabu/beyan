<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
    institutions: Object,
    filters: Object,
    plans: Array,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const plan = ref(props.filters?.plan || '');
const mode = ref(props.filters?.mode || '');

const applyFilters = () => {
    router.get('/platform/institutions', {
        search: search.value,
        status: status.value,
        plan: plan.value,
        mode: mode.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    plan.value = '';
    mode.value = '';
    router.get('/platform/institutions');
};

const suspendModalOpen = ref(false);
const activeInstitution = ref(null);
const suspendReason = ref('');

const openSuspendModal = (inst) => {
    activeInstitution.value = inst;
    suspendReason.value = '';
    suspendModalOpen.value = true;
};

const confirmSuspend = () => {
    if (!activeInstitution.value) return;
    router.post(`/platform/institutions/${activeInstitution.value.id}/suspend`, {
        reason: suspendReason.value,
    }, {
        onSuccess: () => {
            suspendModalOpen.value = false;
        },
    });
};

const activateInstitution = (inst) => {
    if (confirm(`Activate institution '${inst.name}'?`)) {
        router.post(`/platform/institutions/${inst.id}/activate`);
    }
};

// Impersonation Modal
const impersonateModalOpen = ref(false);
const impersonateReason = ref('');

const openImpersonateModal = (inst) => {
    activeInstitution.value = inst;
    impersonateReason.value = 'Investigating user-reported issue with halaqah timetable';
    impersonateModalOpen.value = true;
};

const confirmImpersonate = () => {
    if (!activeInstitution.value) return;
    router.post(`/platform/institutions/${activeInstitution.value.id}/impersonate`, {
        reason: impersonateReason.value,
    });
};
</script>

<template>
    <PlatformLayout title="Institutions Directory">
        
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Institutions Directory</h1>
                <p class="text-xs text-slate-500 mt-0.5">Manage registered madrasas, lifecycle states, and subscription tiers.</p>
            </div>
            <Link
                href="/platform/institutions/create"
                class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition"
            >
                + Create Madrasa
            </Link>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 shadow-sm flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Search name or slug..."
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500"
                />
            </div>

            <div>
                <select
                    v-model="status"
                    @change="applyFilters"
                    class="px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500"
                >
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="trial">Trial</option>
                    <option value="suspended">Suspended</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div>
                <select
                    v-model="plan"
                    @change="applyFilters"
                    class="px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500"
                >
                    <option value="">All Plans</option>
                    <option v-for="p in plans" :key="p.id" :value="p.slug">{{ p.name }}</option>
                </select>
            </div>

            <div>
                <select
                    v-model="mode"
                    @change="applyFilters"
                    class="px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500"
                >
                    <option value="">All Modes</option>
                    <option value="physical">Physical</option>
                    <option value="online">Online</option>
                    <option value="hybrid">Hybrid</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="applyFilters"
                    type="button"
                    class="px-3 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800"
                >
                    Filter
                </button>
                <button
                    @click="resetFilters"
                    type="button"
                    class="px-3 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-200"
                >
                    Reset
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-start">Institution</th>
                            <th class="px-4 py-3 text-start">Owner</th>
                            <th class="px-4 py-3 text-start">Status</th>
                            <th class="px-4 py-3 text-start">Plan</th>
                            <th class="px-4 py-3 text-start">Mode</th>
                            <th class="px-4 py-3 text-start">Expires</th>
                            <th class="px-5 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="inst in institutions.data" :key="inst.id" class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-4">
                                <Link :href="`/platform/institutions/${inst.id}`" class="font-bold text-slate-900 hover:text-rose-600 text-sm block">
                                    {{ inst.name }}
                                </Link>
                                <span class="font-mono text-[11px] text-slate-400">/{{ inst.slug }} · {{ inst.country || 'ET' }}</span>
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <div class="font-semibold">{{ inst.users?.[0]?.name || 'No Owner' }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ inst.users?.[0]?.email || '' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': inst.status === 'active',
                                        'bg-rose-100 text-rose-800': inst.status === 'suspended',
                                        'bg-amber-100 text-amber-800': inst.status === 'trial',
                                        'bg-slate-100 text-slate-600': inst.status === 'inactive'
                                    }"
                                >
                                    {{ inst.status }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-semibold capitalize text-slate-800">{{ inst.subscription_plan || 'Pilot' }}</span>
                            </td>
                            <td class="px-4 py-4 uppercase font-bold text-[10px] text-slate-500">
                                {{ inst.mode }}
                            </td>
                            <td class="px-4 py-4 font-mono text-[11px] text-slate-600">
                                {{ inst.subscription_ends_at ? new Date(inst.subscription_ends_at).toLocaleDateString() : 'N/A' }}
                            </td>
                            <td class="px-5 py-4 text-end space-x-1">
                                <Link
                                    :href="`/platform/institutions/${inst.id}`"
                                    class="px-2.5 py-1 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-lg text-xs font-bold"
                                >
                                    View
                                </Link>

                                <button
                                    v-if="$page.props.auth?.user?.role === 'platform_super_admin'"
                                    @click="openImpersonateModal(inst)"
                                    type="button"
                                    class="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 rounded-lg text-xs font-bold"
                                    title="Impersonate owner for support"
                                >
                                    Impersonate
                                </button>

                                <button
                                    v-if="inst.status === 'active'"
                                    @click="openSuspendModal(inst)"
                                    type="button"
                                    class="px-2.5 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-xs font-bold"
                                >
                                    Suspend
                                </button>
                                <button
                                    v-else
                                    @click="activateInstitution(inst)"
                                    type="button"
                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-bold"
                                >
                                    Activate
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="institutions.links?.length > 3" class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-xs">
                <span class="text-slate-500">Showing {{ institutions.from || 0 }} - {{ institutions.to || 0 }} of {{ institutions.total }} madrasas</span>
                <div class="flex gap-1">
                    <Link
                        v-for="(link, i) in institutions.links"
                        :key="i"
                        :href="link.url || '#'"
                        class="px-2.5 py-1 rounded-md text-xs font-semibold"
                        :class="link.active ? 'bg-rose-600 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-100'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Suspend Modal -->
        <div v-if="suspendModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Suspend Institution</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Suspending <strong>{{ activeInstitution?.name }}</strong> will immediately block all its administrators, teachers, and students from accessing the platform.
                </p>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Reason for suspension:</label>
                    <textarea
                        v-model="suspendReason"
                        rows="3"
                        placeholder="e.g. Non-payment of subscription fees after grace period..."
                        class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="suspendModalOpen = false" type="button" class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-200">
                        Cancel
                    </button>
                    <button @click="confirmSuspend" type="button" class="px-4 py-2 bg-rose-600 text-white text-xs font-bold rounded-xl hover:bg-rose-700">
                        Confirm Suspension
                    </button>
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
                    You are accessing <strong>{{ activeInstitution?.name }}</strong> as an administrator. This session is strictly limited to <strong>30 minutes</strong> and is fully recorded in the platform audit trail.
                </p>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mandatory Justification / Ticket #:</label>
                    <textarea
                        v-model="impersonateReason"
                        rows="3"
                        required
                        placeholder="Please state why you need to log in as the owner..."
                        class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="impersonateModalOpen = false" type="button" class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-200">
                        Cancel
                    </button>
                    <button @click="confirmImpersonate" type="button" class="px-4 py-2 bg-amber-500 text-slate-900 text-xs font-extrabold rounded-xl hover:bg-amber-600">
                        Start Session (30m) &rarr;
                    </button>
                </div>
            </div>
        </div>

    </PlatformLayout>
</template>
