<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const form = useForm({
    name: '',
    slug: '',
    monthly_price_major: 600,
    yearly_price_major: 6000,
    currency: 'ETB',
    student_limit: 50,
    user_limit: null,
    features_raw: "Hifz Progress Tracking\nSession Attendance Marking\nFee Receipts",
    badge: '',
    is_active: true,
    sort_order: 1,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        features: data.features_raw ? data.features_raw.split('\n').map(s => s.trim()).filter(Boolean) : [],
    })).post('/platform/plans');
};
</script>

<template>
    <PlatformLayout title="Create Plan">
        <div class="max-w-2xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create Subscription Plan</h1>
                <Link href="/platform/plans" class="text-xs font-bold text-slate-600 hover:text-slate-900">&larr; Back to Plans</Link>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Plan Name *</label>
                            <input v-model="form.name" type="text" required placeholder="e.g. Starter (Nour)" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Slug (optional)</label>
                            <input v-model="form.slug" type="text" placeholder="starter" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                        </div>
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
                            <label class="block font-bold text-slate-700 uppercase mb-1">Student Limit (blank = unlimited)</label>
                            <input v-model.number="form.student_limit" type="number" placeholder="50" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Badge (e.g. Most Popular)</label>
                            <input v-model="form.badge" type="text" placeholder="Most Popular" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Features (one per line)</label>
                        <textarea v-model="form.features_raw" rows="4" class="w-full px-3 py-2 border border-slate-300 rounded-xl"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                        <Link href="/platform/plans" class="px-5 py-2.5 bg-slate-100 rounded-xl font-bold text-slate-700 hover:bg-slate-200">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-md transition">
                            Create Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </PlatformLayout>
</template>
