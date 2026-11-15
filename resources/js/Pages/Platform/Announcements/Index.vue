<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineProps({
    announcements: Object,
});

const modalOpen = ref(false);
const form = useForm({
    title: '',
    body: '',
    target_mode: 'all',
    target_plan: 'all',
    starts_at: new Date().toISOString().slice(0, 10),
    ends_at: new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10),
    is_active: true,
});

const submit = () => {
    form.post('/platform/announcements', {
        onSuccess: () => {
            modalOpen.value = false;
            form.reset();
        },
    });
};

const deleteAnnouncement = (id) => {
    if (confirm('Delete this announcement?')) {
        router.delete(`/platform/announcements/${id}`);
    }
};
</script>

<template>
    <PlatformLayout title="Announcements">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Platform Announcements</h1>
                <p class="text-xs text-slate-500 mt-0.5">Publish system-wide notifications and banners visible in tenant workspaces.</p>
            </div>
            <button
                @click="modalOpen = true"
                type="button"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition"
            >
                + New Announcement
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden text-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-start">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-start">Title & Message</th>
                            <th class="px-4 py-3 text-start">Targeting</th>
                            <th class="px-4 py-3 text-start">Active Window</th>
                            <th class="px-4 py-3 text-start">Dismissals</th>
                            <th class="px-4 py-3 text-start">Status</th>
                            <th class="px-5 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="a in announcements.data" :key="a.id" class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900 text-sm">{{ a.title }}</div>
                                <div class="text-slate-500 text-xs mt-0.5 max-w-md line-clamp-2">{{ a.body }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-slate-600">
                                <div>Mode: <strong class="capitalize">{{ a.target_mode || 'All' }}</strong></div>
                                <div>Plan: <strong class="capitalize">{{ a.target_plan || 'All' }}</strong></div>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-[11px] text-slate-600">
                                {{ new Date(a.starts_at).toLocaleDateString() }} to {{ new Date(a.ends_at).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-3.5 text-slate-500 font-mono">{{ a.dismissals_count || 0 }} dismissed</td>
                            <td class="px-4 py-3.5">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="a.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'">
                                    {{ a.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-end">
                                <button @click="deleteAnnouncement(a.id)" type="button" class="text-rose-600 font-bold hover:underline">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
                <h3 class="text-base font-bold text-slate-900 mb-2">Create Announcement Banner</h3>
                
                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Title *</label>
                        <input v-model="form.title" type="text" required placeholder="e.g. Scheduled Network Maintenance" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Message Body *</label>
                        <textarea v-model="form.body" rows="3" required placeholder="Details displayed to institution ustadhs..." class="w-full px-3 py-2 border border-slate-300 rounded-xl"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Target Mode</label>
                            <select v-model="form.target_mode" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                                <option value="all">All Madrasas</option>
                                <option value="physical">Physical only</option>
                                <option value="online">Online only</option>
                                <option value="hybrid">Hybrid only</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Target Plan</label>
                            <select v-model="form.target_plan" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                                <option value="all">All Plans</option>
                                <option value="starter">Starter only</option>
                                <option value="growth">Growth only</option>
                                <option value="institute">Institute only</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Start Date</label>
                            <input v-model="form.starts_at" type="date" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">End Date</label>
                            <input v-model="form.ends_at" type="date" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button @click="modalOpen = false" type="button" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-md">Publish Banner</button>
                    </div>
                </form>
            </div>
        </div>
    </PlatformLayout>
</template>
