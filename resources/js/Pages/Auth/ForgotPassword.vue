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
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" dir="ltr">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-slate-900">Reset your password</h2>
                <p class="mt-2 text-sm text-slate-600">Enter your email and we'll dispatch a password reset link.</p>
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
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                            {{ form.processing ? 'Dispatching...' : 'Email Reset Link' }}
                        </button>
                    </div>

                    <div class="text-center pt-2">
                        <Link href="/login" class="text-xs text-slate-600 hover:text-slate-900">
                            &larr; Back to login
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
