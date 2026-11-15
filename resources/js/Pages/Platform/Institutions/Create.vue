<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
    plans: Array,
    prefill: Object,
});

const form = useForm({
    name: props.prefill?.name || '',
    slug: '',
    mode: props.prefill?.mode || 'hybrid',
    timezone: 'Africa/Addis_Ababa',
    currency: 'ETB',
    locale: 'am',
    country: 'ET',
    subscription_plan: 'starter',
    trial_days: 14,
    owner_name: 'Ustadh ' + (props.prefill?.name ? props.prefill.name.split(' ')[0] : 'Admin'),
    owner_email: props.prefill?.email || '',
    owner_phone: props.prefill?.phone || '',
    owner_password: 'Password123!',
});

const submit = () => {
    form.post('/platform/institutions');
};
</script>

<template>
    <PlatformLayout title="Register Madrasa">
        <div class="max-w-3xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Manual Madrasa Registration</h1>
                    <p class="text-xs text-slate-500">Create an isolated tenant workspace and initial owner credentials.</p>
                </div>
                <Link href="/platform/institutions" class="text-xs font-bold text-slate-600 hover:text-slate-900">
                    &larr; Back to List
                </Link>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6 text-xs">
                    
                    <h2 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 uppercase tracking-wider">
                        1. Institution Workspace Details
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Institution Name *</label>
                            <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                            <p v-if="form.errors.name" class="text-rose-600 mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Workspace Slug *</label>
                            <input v-model="form.slug" type="text" required placeholder="e.g. darul-huda" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                            <p v-if="form.errors.slug" class="text-rose-600 mt-1">{{ form.errors.slug }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Campus Mode *</label>
                            <select v-model="form.mode" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                                <option value="physical">Physical (Campus)</option>
                                <option value="online">Online Academy</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Timezone *</label>
                            <input v-model="form.timezone" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Currency *</label>
                            <input v-model="form.currency" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono uppercase" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Default Locale *</label>
                            <select v-model="form.locale" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                                <option value="en">English (en)</option>
                                <option value="am">Amharic (am)</option>
                                <option value="om">Afaan Oromoo (om)</option>
                                <option value="ar">Arabic (ar - RTL)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Subscription Plan *</label>
                            <select v-model="form.subscription_plan" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                                <option v-for="p in plans" :key="p.id" :value="p.slug">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Trial / Active Days *</label>
                            <input v-model.number="form.trial_days" type="number" min="0" max="365" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                        </div>
                    </div>

                    <h2 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 uppercase tracking-wider pt-4">
                        2. Primary Owner Credentials
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Owner Name *</label>
                            <input v-model="form.owner_name" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                            <p v-if="form.errors.owner_name" class="text-rose-600 mt-1">{{ form.errors.owner_name }}</p>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Owner Email *</label>
                            <input v-model="form.owner_email" type="email" required class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                            <p v-if="form.errors.owner_email" class="text-rose-600 mt-1">{{ form.errors.owner_email }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Owner Phone</label>
                            <input v-model="form.owner_phone" type="text" placeholder="+251..." class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Initial Password *</label>
                            <input v-model="form.owner_password" type="password" required class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                            <p v-if="form.errors.owner_password" class="text-rose-600 mt-1">{{ form.errors.owner_password }}</p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                        <Link href="/platform/institutions" class="px-5 py-2.5 bg-slate-100 rounded-xl font-bold text-slate-700 hover:bg-slate-200">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-md transition disabled:opacity-50"
                        >
                            {{ form.processing ? 'Creating...' : 'Create Madrasa Workspace' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </PlatformLayout>
</template>
