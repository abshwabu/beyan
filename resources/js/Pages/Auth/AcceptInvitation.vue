<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    token: String,
    email: String,
    institution: Object,
    roles: Array,
});

const page = usePage();
const isRtl = computed(() => page.props.i18n?.direction === 'rtl');

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(`/invitations/${props.token}/accept`);
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" dir="ltr">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <span class="inline-flex items-center justify-center p-3 bg-emerald-600 rounded-2xl shadow-lg">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
                <h2 class="mt-4 text-2xl font-bold text-slate-900">
                    Join {{ institution?.name }}
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    Accept your invitation as:
                    <span v-for="role in roles" :key="role" class="ms-1 font-semibold text-emerald-700">
                        [{{ role }}]
                    </span>
                </p>
            </div>

            <div class="mt-8 bg-white py-8 px-6 shadow-sm border border-slate-200 rounded-2xl sm:px-10">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email Address</label>
                        <input type="email" :value="email" disabled class="mt-1 block w-full rounded-lg bg-slate-100 border-slate-300 text-slate-500 sm:text-sm px-3 py-2 border cursor-not-allowed" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Your Full Name</label>
                        <input v-model="form.name" type="text" required autofocus placeholder="Ustadh / Ustadha..." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                        <span v-if="form.errors.name" class="text-xs text-rose-500 mt-1 block">{{ form.errors.name }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Set Password</label>
                        <input v-model="form.password" type="password" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                        <span v-if="form.errors.password" class="text-xs text-rose-500 mt-1 block">{{ form.errors.password }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Confirm Password</label>
                        <input v-model="form.password_confirmation" type="password" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm px-3 py-2 border" />
                    </div>

                    <div>
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                            {{ form.processing ? 'Creating Account...' : 'Complete Account Setup' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
