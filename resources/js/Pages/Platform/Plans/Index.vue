<script setup>
import { Link, router } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineProps({
    plans: Array,
});

const deletePlan = (plan) => {
    if (confirm(`Delete plan '${plan.name}'?`)) {
        router.delete(`/platform/plans/${plan.id}`);
    }
};
</script>

<template>
    <PlatformLayout title="Plans & Packaging">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Subscription Plans</h1>
                <p class="text-xs text-slate-500 mt-0.5">Manage pricing tiers, limits, and feature packaging feeding the public landing page.</p>
            </div>
            <Link
                href="/platform/plans/create"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition"
            >
                + Create New Plan
            </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div
                v-for="plan in plans"
                :key="plan.id"
                class="bg-white rounded-2xl border p-6 flex flex-col justify-between shadow-sm relative"
                :class="plan.badge ? 'border-rose-400 ring-2 ring-rose-500/10' : 'border-slate-200'"
            >
                <span v-if="plan.badge" class="absolute -top-3 start-4 px-2.5 py-0.5 bg-rose-600 text-white rounded-full text-[10px] font-black uppercase tracking-wider">
                    {{ plan.badge }}
                </span>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-lg font-black text-slate-900">{{ plan.name }}</h2>
                        <span class="text-[10px] font-mono text-slate-400">/{{ plan.slug }}</span>
                    </div>

                    <div class="mb-4">
                        <span class="text-3xl font-black text-slate-900">{{ (plan.monthly_price / 100).toLocaleString() }}</span>
                        <span class="text-xs font-bold text-slate-500 ms-1">{{ plan.currency }} / mo</span>
                        <div class="text-[11px] text-slate-400 font-medium">
                            Yearly: {{ (plan.yearly_price / 100).toLocaleString() }} {{ plan.currency }}
                        </div>
                    </div>

                    <div class="space-y-1.5 mb-6 text-xs text-slate-600 font-medium">
                        <div>Students: <strong>{{ plan.student_limit ? plan.student_limit : 'Unlimited' }}</strong></div>
                        <div>Staff/Users: <strong>{{ plan.user_limit ? plan.user_limit : 'Unlimited' }}</strong></div>
                        <div v-if="plan.features?.length" class="pt-2 border-t border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Features:</span>
                            <ul class="space-y-1 text-[11px]">
                                <li v-for="(f, fi) in plan.features" :key="fi" class="flex items-center gap-1.5 text-slate-700">
                                    <span class="text-emerald-600 font-bold">✓</span>
                                    <span>{{ f }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="plan.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'">
                        {{ plan.is_active ? 'Active' : 'Disabled' }}
                    </span>
                    <div class="space-x-2">
                        <Link :href="`/platform/plans/${plan.id}/edit`" class="text-slate-700 hover:text-slate-900 font-bold">
                            Edit
                        </Link>
                        <button @click="deletePlan(plan)" type="button" class="text-rose-600 hover:text-rose-800 font-bold">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </PlatformLayout>
</template>
