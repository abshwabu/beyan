<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
    plan: Object,
});

const form = useForm({
    name: props.plan.name,
    monthly_price_major: props.plan.monthly_price / 100,
    yearly_price_major: props.plan.yearly_price / 100,
    currency: props.plan.currency,
    student_limit: props.plan.student_limit,
    user_limit: props.plan.user_limit,
    features_raw: props.plan.features?.join('\n') || '',
    badge: props.plan.badge || '',
    is_active: Boolean(props.plan.is_active),
    sort_order: props.plan.sort_order || 0,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        features: data.features_raw ? data.features_raw.split('\n').map(s => s.trim()).filter(Boolean) : [],
    })).put(`/platform/plans/${props.plan.id}`);
};
</script>

<template>
    <PlatformLayout :title="`Edit ${plan.name}`">
        <div class="max-w-2xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Edit Plan: {{ plan.name }}</h1>
                <Link href="/platform/plans" class="text-xs font-bold text-slate-600 hover:text-slate-900">&larr; Back to Plans</Link>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Plan Name *</label>
                        <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Monthly Price *</label>
                            <input v-model.number="form.monthly_price_major" type="number" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Yearly Price *</label>
                            <input v-model.number="form.yearly_price_major" type="number" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Currency *</label>
                            <input v-model="form.currency" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl uppercase font-mono" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Student Limit</label>
                            <input v-model.number="form.student_limit" type="number" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Badge</label>
                            <input v-model="form.badge" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Features (one per line)</label>
                        <textarea v-model="form.features_raw" rows="4" class="w-full px-3 py-2 border border-slate-300 rounded-xl"></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.is_active" type="checkbox" class="rounded text-rose-600 focus:ring-rose-500" />
                            <span class="font-bold text-slate-700">Active (Visible on public landing page)</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                        <Link href="/platform/plans" class="px-5 py-2.5 bg-slate-100 rounded-xl font-bold text-slate-700 hover:bg-slate-200">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-md transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </PlatformLayout>
</template>
