<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    institutions: Array,
});

const event = ref(props.filters?.event || '');
const institutionId = ref(props.filters?.institution_id || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

const applyFilters = () => {
    router.get('/platform/audit-logs', {
        event: event.value,
        institution_id: institutionId.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <PlatformLayout title="Platform Audit Trail">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">System Audit Trail</h1>
                <p class="text-xs text-slate-500 mt-0.5">Immutable record of administrator actions, lifecycle transitions, and support sessions.</p>
            </div>
            <div>
                <a
                    href="/platform/audit-logs/export"
                    class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold shadow-sm transition"
                >
                    📥 Export CSV
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 shadow-sm flex flex-wrap items-center gap-3 text-xs">
            <div>
                <input
                    v-model="event"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Search action event..."
                    class="px-3 py-2 rounded-xl border border-slate-300"
                />
            </div>

            <div>
                <select v-model="institutionId" @change="applyFilters" class="px-3 py-2 rounded-xl border border-slate-300">
                    <option value="">All Institutions</option>
                    <option v-for="inst in institutions" :key="inst.id" :value="inst.id">{{ inst.name }}</option>
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

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden text-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-start">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-start">Event</th>
                            <th class="px-4 py-3 text-start">Operator / User</th>
                            <th class="px-4 py-3 text-start">Institution</th>
                            <th class="px-4 py-3 text-start">IP Address</th>
                            <th class="px-4 py-3 text-start">Timestamp</th>
                            <th class="px-5 py-3 text-start">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 font-bold font-mono text-slate-900">{{ log.event }}</td>
                            <td class="px-4 py-3.5 font-medium text-slate-700">{{ log.user?.name || 'System' }}</td>
                            <td class="px-4 py-3.5 font-mono text-[11px] text-slate-500">{{ log.institution_id ? log.institution_id.slice(0, 10) + '...' : 'Global Platform' }}</td>
                            <td class="px-4 py-3.5 font-mono text-[11px] text-slate-400">{{ log.ip_address || '127.0.0.1' }}</td>
                            <td class="px-4 py-3.5 font-mono text-[11px] text-slate-500">
                                {{ new Date(log.created_at).toLocaleString() }}
                            </td>
                            <td class="px-5 py-3.5 font-mono text-[11px] text-slate-600 max-w-xs truncate" :title="JSON.stringify(log.new_values)">
                                {{ JSON.stringify(log.new_values) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlatformLayout>
</template>
