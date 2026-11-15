<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    token: String,
    email: String,
});

const page = usePage();
const isRtl = computed(() => page.props.i18n?.direction === 'rtl');

const form = useForm({
    token: props.token,
    email: props.email || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-slate-900">Set a new password</h2>
                <p class="mt-2 text-sm text-slate-600">Please choose a strong password with at least 8 characters.</p>
            </div>

            <div class="mt-8 bg-white py-8 px-6 shadow-sm border border-slate-200 rounded-2xl sm:px-10">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email Address</label>
                        <input v-model="form.email" type="email" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                        <span v-if="form.errors.email" class="text-xs text-rose-500 mt-1 block">{{ form.errors.email }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">New Password</label>
                        <input v-model="form.password" type="password" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                        <span v-if="form.errors.password" class="text-xs text-rose-500 mt-1 block">{{ form.errors.password }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Confirm New Password</label>
                        <input v-model="form.password_confirmation" type="password" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                    </div>

                    <div>
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                            {{ form.processing ? 'Resetting...' : 'Reset Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
