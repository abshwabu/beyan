<script setup>
import { useForm } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    trial_days: props.settings.trial_days || 14,
    default_grace_days: props.settings.default_grace_days || 7,
    default_currency: props.settings.default_currency || 'ETB',
    support_phone: props.settings.support_phone || '+251 91 123 4567',
    support_email: props.settings.support_email || 'salam@beyan.io',
    support_telegram: props.settings.support_telegram || '@beyan_academy',
    maintenance_mode: Boolean(props.settings.maintenance_mode),
    maintenance_message: props.settings.maintenance_message || '',
});

const submit = () => {
    form.put('/platform/settings');
};
</script>

<template>
    <PlatformLayout title="Platform Settings">
        <div class="max-w-3xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">System Configuration & Policies</h1>
                <p class="text-xs text-slate-500 mt-0.5">Global defaults for newly registered madrasas, support contacts, and maintenance mode.</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6 text-xs">
                    
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 uppercase tracking-wider mb-4">
                            Subscription & Grace Defaults
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">Default Trial Length (days)</label>
                                <input v-model.number="form.trial_days" type="number" min="1" max="90" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">Overdue Grace Period (days)</label>
                                <input v-model.number="form.default_grace_days" type="number" min="0" max="60" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">Platform Currency</label>
                                <input v-model="form.default_currency" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono uppercase" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 uppercase tracking-wider mb-4">
                            Direct Support Contacts (Ethiopia)
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">Support Phone</label>
                                <input v-model="form.support_phone" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">Support Email</label>
                                <input v-model="form.support_email" type="email" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">Telegram Handle</label>
                                <input v-model="form.support_telegram" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 uppercase tracking-wider mb-4">
                            Maintenance Mode
                        </h2>

                        <div class="space-y-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.maintenance_mode" type="checkbox" class="rounded text-rose-600 focus:ring-rose-500" />
                                <span class="font-bold text-slate-800">Enable Platform Maintenance Mode</span>
                            </label>

                            <div v-if="form.maintenance_mode">
                                <label class="block font-bold text-slate-700 uppercase mb-1">Maintenance Banner Message</label>
                                <textarea v-model="form.maintenance_message" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-md transition"
                        >
                            Save Settings
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </PlatformLayout>
</template>
