<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
    demoRequests: Object,
    filters: Object,
});

const status = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get('/platform/demo-requests', {
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const updateStatus = (lead, newStatus) => {
    router.put(`/platform/demo-requests/${lead.id}`, {
        status: newStatus,
        notes: lead.notes,
    });
};

const saveNotes = (lead) => {
    router.put(`/platform/demo-requests/${lead.id}`, {
        status: lead.status,
        notes: lead.notes,
    });
};
</script>

<template>
    <PlatformLayout title="Demo Leads">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Demo & Sales Leads</h1>
                <p class="text-xs text-slate-500 mt-0.5">Manage inbound demo requests from madrasa ustadhs and administrators.</p>
            </div>
            <div>
                <select v-model="status" @change="applyFilters" class="px-3 py-2 text-xs rounded-xl border border-slate-300">
                    <option value="">All Leads</option>
                    <option value="new">New Inquiries</option>
                    <option value="contacted">Contacted</option>
                    <option value="closed">Closed / Converted</option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden text-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-start">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-start">Madrasa & Contact</th>
                            <th class="px-4 py-3 text-start">Type & Scale</th>
                            <th class="px-4 py-3 text-start">Inquiry Message</th>
                            <th class="px-4 py-3 text-start">Status</th>
                            <th class="px-4 py-3 text-start">Admin Notes</th>
                            <th class="px-5 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="lead in demoRequests.data" :key="lead.id" class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900 text-sm">{{ lead.madrasa_name }}</div>
                                <div class="text-slate-600 font-medium">{{ lead.name }}</div>
                                <div class="font-mono text-[11px] text-slate-400">{{ lead.phone }} <span v-if="lead.email">· {{ lead.email }}</span></div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="capitalize font-semibold text-slate-700">{{ lead.mode }}</span>
                                <div class="text-[11px] text-slate-400 capitalize">{{ lead.student_count_range?.replace('_', ' ') }} students</div>
                            </td>
                            <td class="px-4 py-3.5 max-w-xs text-slate-600 truncate" :title="lead.message">
                                {{ lead.message || 'No additional note' }}
                            </td>
                            <td class="px-4 py-3.5">
                                <select
                                    v-model="lead.status"
                                    @change="updateStatus(lead, lead.status)"
                                    class="text-[11px] font-bold px-2 py-1 rounded-lg border border-slate-300"
                                    :class="{
                                        'bg-amber-50 text-amber-800': lead.status === 'new',
                                        'bg-blue-50 text-blue-800': lead.status === 'contacted',
                                        'bg-emerald-50 text-emerald-800': lead.status === 'closed'
                                    }"
                                >
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </td>
                            <td class="px-4 py-3.5">
                                <input
                                    v-model="lead.notes"
                                    @blur="saveNotes(lead)"
                                    @keyup.enter="saveNotes(lead)"
                                    type="text"
                                    placeholder="Add notes..."
                                    class="w-full px-2 py-1 text-xs border border-slate-200 rounded-lg"
                                />
                            </td>
                            <td class="px-5 py-3.5 text-end">
                                <Link
                                    :href="`/platform/demo-requests/${lead.id}/convert`"
                                    class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg whitespace-nowrap shadow-sm"
                                >
                                    Convert to Madrasa &rarr;
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlatformLayout>
</template>
