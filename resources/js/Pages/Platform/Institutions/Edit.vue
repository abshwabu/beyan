<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({
    institution: Object,
    plans: Array,
});

const form = useForm({
    name: props.institution.name,
    slug: props.institution.slug,
    mode: props.institution.mode,
    timezone: props.institution.timezone,
    currency: props.institution.currency,
    locale: props.institution.locale,
    country: props.institution.country || 'ET',
});

const submit = () => {
    form.put(`/platform/institutions/${props.institution.id}`);
};
</script>

<template>
    <PlatformLayout :title="`Edit ${institution.name}`">
        <div class="max-w-2xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Edit Madrasa Settings</h1>
                    <p class="text-xs text-slate-500">Update institution profile and regional localization.</p>
                </div>
                <Link :href="`/platform/institutions/${institution.id}`" class="text-xs font-bold text-slate-600 hover:text-slate-900">
                    &larr; Back to Detail
                </Link>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Institution Name</label>
                        <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Slug Identifier</label>
                        <input v-model="form.slug" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Campus Mode</label>
                            <select v-model="form.mode" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                                <option value="physical">Physical</option>
                                <option value="online">Online</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Timezone</label>
                            <input v-model="form.timezone" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Currency</label>
                            <input v-model="form.currency" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-xl uppercase font-mono" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Locale</label>
                            <select v-model="form.locale" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                                <option value="en">English (en)</option>
                                <option value="am">Amharic (am)</option>
                                <option value="om">Afaan Oromoo (om)</option>
                                <option value="ar">Arabic (ar)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Country</label>
                            <input v-model="form.country" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl uppercase font-mono" />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                        <Link :href="`/platform/institutions/${institution.id}`" class="px-5 py-2.5 bg-slate-100 rounded-xl font-bold text-slate-700 hover:bg-slate-200">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-md transition disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </PlatformLayout>
</template>
