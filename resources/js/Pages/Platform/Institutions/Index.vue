<script setup>
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    institutions: Array,
});

const toggleStatus = (institution) => {
    const action = institution.status === 'active' ? 'suspend' : 'activate';
    router.post(`/platform/institutions/${institution.id}/${action}`);
};

const changePlan = (institution) => {
    const newPlan = prompt('Enter new plan (free, pilot, standard, premium, enterprise):', institution.subscription_plan || 'standard');
    if (newPlan) {
        router.put(`/platform/institutions/${institution.id}/plan`, {
            subscription_plan: newPlan,
        });
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <!-- Header -->
        <header class="bg-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-rose-600 rounded-md font-bold text-xs uppercase tracking-wider">
                        Platform Super Admin
                    </span>
                    <h1 class="text-xl font-bold">Beyan Platform Control Panel</h1>
                </div>

                <div class="flex items-center gap-4">
                    <Link href="/logout" method="post" as="button" class="text-xs text-slate-300 hover:text-white">
                        Logout
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main -->
        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Registered Institutions</h2>
                        <p class="text-xs text-slate-500">Manage tenant workspaces, lifecycle states, and subscription tiers.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-700">
                            <tr>
                                <th class="py-3.5 px-4 text-start font-semibold">Madrasa Name</th>
                                <th class="py-3.5 px-4 text-start font-semibold">Slug</th>
                                <th class="py-3.5 px-4 text-start font-semibold">Mode</th>
                                <th class="py-3.5 px-4 text-start font-semibold">Status</th>
                                <th class="py-3.5 px-4 text-start font-semibold">Plan</th>
                                <th class="py-3.5 px-4 text-start font-semibold">Users</th>
                                <th class="py-3.5 px-4 text-end font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-700">
                            <tr v-for="inst in institutions" :key="inst.id" class="hover:bg-slate-50">
                                <td class="py-3.5 px-4 font-medium text-slate-900">{{ inst.name }}</td>
                                <td class="py-3.5 px-4 font-mono text-xs text-slate-500">{{ inst.slug }}</td>
                                <td class="py-3.5 px-4 uppercase text-xs">{{ inst.mode }}</td>
                                <td class="py-3.5 px-4">
                                    <span :class="inst.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'" class="px-2 py-0.5 rounded-full text-xs font-semibold uppercase">
                                        {{ inst.status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-800">{{ inst.subscription_plan || 'none' }}</td>
                                <td class="py-3.5 px-4">{{ inst.users_count || 0 }}</td>
                                <td class="py-3.5 px-4 text-end space-x-2">
                                    <button @click="changePlan(inst)" class="px-2.5 py-1 text-xs border rounded-md hover:bg-slate-100 text-slate-700">
                                        Edit Plan
                                    </button>
                                    <button @click="toggleStatus(inst)" :class="inst.status === 'active' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700'" class="px-2.5 py-1 text-xs text-white rounded-md transition-colors">
                                        {{ inst.status === 'active' ? 'Suspend' : 'Activate' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</template>
