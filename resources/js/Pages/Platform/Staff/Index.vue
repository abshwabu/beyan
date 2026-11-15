<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineProps({
    staff: Object,
});

const modalOpen = ref(false);
const form = useForm({
    name: '',
    email: '',
    phone: '',
    role: 'platform_support',
    password: 'Password123!',
});

const submit = () => {
    form.post('/platform/staff', {
        onSuccess: () => {
            modalOpen.value = false;
            form.reset();
        },
    });
};

const deleteStaff = (member) => {
    if (confirm(`Remove administrator '${member.name}'?`)) {
        router.delete(`/platform/staff/${member.id}`);
    }
};
</script>

<template>
    <PlatformLayout title="Platform Staff">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Platform Administrators & Support</h1>
                <p class="text-xs text-slate-500 mt-0.5">Manage operator accounts. Support users have strictly read-only visibility.</p>
            </div>
            <button
                v-if="$page.props.auth?.user?.role === 'platform_super_admin'"
                @click="modalOpen = true"
                type="button"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition"
            >
                + Add Staff Member
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden text-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-start">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-start">Name</th>
                            <th class="px-4 py-3 text-start">Email Address</th>
                            <th class="px-4 py-3 text-start">Phone</th>
                            <th class="px-4 py-3 text-start">Role Privilege</th>
                            <th class="px-4 py-3 text-start">Created</th>
                            <th class="px-5 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="member in staff.data" :key="member.id" class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-900">
                                {{ member.name }}
                                <span v-if="member.id === $page.props.auth?.user?.id" class="ms-1.5 text-[10px] text-slate-400 font-normal">(You)</span>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-[11px] text-slate-600">{{ member.email }}</td>
                            <td class="px-4 py-3.5 font-mono text-[11px] text-slate-500">{{ member.phone || '—' }}</td>
                            <td class="px-4 py-3.5">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                                    :class="member.role === 'platform_super_admin' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800'"
                                >
                                    {{ member.role === 'platform_super_admin' ? 'Super Admin' : 'Support' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-slate-500 font-mono text-[11px]">
                                {{ new Date(member.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-5 py-3.5 text-end">
                                <button
                                    v-if="$page.props.auth?.user?.role === 'platform_super_admin' && member.id !== $page.props.auth?.user?.id"
                                    @click="deleteStaff(member)"
                                    type="button"
                                    class="text-rose-600 hover:text-rose-800 font-bold"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Staff Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
                <h3 class="text-base font-bold text-slate-900 mb-2">Add Platform Staff</h3>
                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Full Name *</label>
                        <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Email Address *</label>
                        <input v-model="form.email" type="email" required class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Phone</label>
                        <input v-model="form.phone" type="text" placeholder="+251..." class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Role Privilege *</label>
                        <select v-model="form.role" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                            <option value="platform_support">Support (Read-only)</option>
                            <option value="platform_super_admin">Super Admin (Full write access)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Initial Password *</label>
                        <input v-model="form.password" type="password" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button @click="modalOpen = false" type="button" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-md">Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </PlatformLayout>
</template>
