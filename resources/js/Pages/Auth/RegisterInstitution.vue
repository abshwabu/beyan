<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    modes: { type: Array, default: () => ['online', 'physical', 'hybrid'] },
    locales: { type: Object, default: () => ({ en: 'English', am: 'አማርኛ', om: 'Afaan Oromoo', ar: 'العربية' }) },
    timezones: { type: Object, default: () => ({ 'Africa/Addis_Ababa': 'Addis Ababa' }) },
    currencies: { type: Array, default: () => ['ETB', 'USD', 'SAR'] },
});

const page = usePage();
const isRtl = computed(() => page.props.i18n?.direction === 'rtl');

const form = useForm({
    institution_name: '',
    institution_slug: '',
    mode: 'hybrid',
    timezone: 'Africa/Addis_Ababa',
    currency: 'ETB',
    locale: 'en',
    phone: '',
    email: '',
    address: '',
    owner_name: '',
    owner_email: '',
    owner_password: '',
    owner_password_confirmation: '',
});

const submit = () => {
    form.post('/register-institution');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="sm:mx-auto sm:w-full sm:max-w-2xl">
            <div class="text-center">
                <span class="inline-flex items-center justify-center p-3 bg-emerald-600 rounded-2xl shadow-lg">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </span>
                <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900">
                    Beyan Madrasa Management
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    Register your madrasa to initialize an isolated institutional workspace.
                </p>
            </div>

            <div class="mt-8 bg-white py-8 px-6 shadow-sm border border-slate-200 rounded-2xl sm:px-10">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Madrasa Details Section -->
                    <div>
                        <h3 class="text-base font-semibold leading-7 text-slate-900 border-b pb-2 mb-4">
                            1. Madrasa Profile
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">Madrasa Name *</label>
                                <input v-model="form.institution_name" type="text" required placeholder="e.g. Beyan Academy" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                                <span v-if="form.errors.institution_name" class="text-xs text-rose-500 mt-1">{{ form.errors.institution_name }}</span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Slug (Workspace URL)</label>
                                <input v-model="form.institution_slug" type="text" placeholder="e.g. beyan-academy" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                                <span v-if="form.errors.institution_slug" class="text-xs text-rose-500 mt-1">{{ form.errors.institution_slug }}</span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Operating Mode</label>
                                <select v-model="form.mode" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border">
                                    <option v-for="mode in modes" :key="mode" :value="mode">{{ mode }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Timezone</label>
                                <select v-model="form.timezone" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border">
                                    <option v-for="(label, tz) in timezones" :key="tz" :value="tz">{{ label }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Primary Currency</label>
                                <select v-model="form.currency" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border">
                                    <option v-for="curr in currencies" :key="curr" :value="curr">{{ curr }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Primary Language / Locale</label>
                                <select v-model="form.locale" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border">
                                    <option v-for="(name, code) in locales" :key="code" :value="code">{{ name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Phone</label>
                                <input v-model="form.phone" type="text" placeholder="+251 91 123 4567" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                            </div>
                        </div>
                    </div>

                    <!-- Owner Account Details Section -->
                    <div class="pt-4">
                        <h3 class="text-base font-semibold leading-7 text-slate-900 border-b pb-2 mb-4">
                            2. Administrator Account
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">Admin Full Name *</label>
                                <input v-model="form.owner_name" type="text" required placeholder="Sheikh / Ustadh..." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                                <span v-if="form.errors.owner_name" class="text-xs text-rose-500 mt-1">{{ form.errors.owner_name }}</span>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">Email Address *</label>
                                <input v-model="form.owner_email" type="email" required placeholder="admin@beyan.edu.et" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                                <span v-if="form.errors.owner_email" class="text-xs text-rose-500 mt-1">{{ form.errors.owner_email }}</span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Password *</label>
                                <input v-model="form.owner_password" type="password" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                                <span v-if="form.errors.owner_password" class="text-xs text-rose-500 mt-1">{{ form.errors.owner_password }}</span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Confirm Password *</label>
                                <input v-model="form.owner_password_confirmation" type="password" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors disabled:opacity-50">
                            {{ form.processing ? 'Initializing Workspace...' : 'Register Madrasa & Create Workspace' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
