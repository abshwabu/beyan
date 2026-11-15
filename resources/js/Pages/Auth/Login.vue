<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: String,
});

const page = usePage();
const isRtl = computed(() => page.props.i18n?.direction === 'rtl');

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <span class="inline-flex items-center justify-center p-3 bg-emerald-600 rounded-2xl shadow-lg">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </span>
                <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900">
                    Sign in to Beyan
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    Madrasa Management & Institutional Workspace
                </p>
            </div>

            <div class="mt-8 bg-white py-8 px-6 shadow-sm border border-slate-200 rounded-2xl sm:px-10">
                <div v-if="status" class="mb-4 text-sm font-medium text-emerald-600 bg-emerald-50 p-3 rounded-lg border border-emerald-200">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email Address</label>
                        <input v-model="form.email" type="email" required autofocus class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                        <span v-if="form.errors.email" class="text-xs text-rose-500 mt-1 block">{{ form.errors.email }}</span>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-slate-700">Password</label>
                            <Link href="/forgot-password" class="text-xs text-emerald-600 hover:text-emerald-500 font-medium">
                                Forgot password?
                            </Link>
                        </div>
                        <input v-model="form.password" type="password" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                        <span v-if="form.errors.password" class="text-xs text-rose-500 mt-1 block">{{ form.errors.password }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                            <span>Remember me</span>
                        </label>
                    </div>

                    <div>
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors disabled:opacity-50">
                            {{ form.processing ? 'Signing in...' : 'Sign In' }}
                        </button>
                    </div>

                    <div class="text-center pt-2 border-t">
                        <Link href="/register-institution" class="text-xs text-slate-600 hover:text-slate-900">
                            Need to register a new madrasa? <span class="text-emerald-600 font-semibold">Register here</span>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
