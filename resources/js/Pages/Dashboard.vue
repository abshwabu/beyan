<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
    institution: Object,
    academicYears: Array,
    currentYear: Object,
    currentTerm: Object,
    stats: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isRtl = computed(() => page.props.i18n?.direction === 'rtl');
</script>

<template>
    <div class="min-h-screen bg-slate-100" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Navigation Header -->
        <nav class="bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-3">
                        <span class="p-2 bg-emerald-600 rounded-lg text-white font-bold text-lg">
                            BM
                        </span>
                        <div>
                            <span class="text-xl font-bold text-slate-800">{{ institution?.name || 'Madrasa Workspace' }}</span>
                            <span class="ms-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 uppercase">
                                {{ institution?.mode || 'hybrid' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="text-end">
                            <div class="text-sm font-medium text-slate-900">{{ user?.name }}</div>
                            <div class="text-xs text-slate-500">{{ user?.email }} ({{ user?.role }})</div>
                        </div>
                        <Link href="/logout" method="post" as="button" class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-md transition-colors">
                            Logout
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Workspace Content -->
        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Flash Notification -->
            <div v-if="page.props.flash?.success" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
                <span>{{ page.props.flash.success }}</span>
            </div>

            <!-- Workspace Quick Stats -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5 border border-slate-200">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Institution Workspace ID</div>
                    <div class="mt-2 text-sm font-mono font-bold text-slate-800 truncate">{{ institution?.id }}</div>
                    <div class="mt-1 text-xs text-slate-400">Strict Tenant Scoped</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5 border border-slate-200">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Academic Year</div>
                    <div class="mt-2 text-lg font-bold text-slate-800">{{ currentYear?.name || 'None Set' }}</div>
                    <div class="mt-1 text-xs text-emerald-600 font-medium">{{ currentTerm?.name || 'No Active Term' }}</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5 border border-slate-200">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Institutional Timezone</div>
                    <div class="mt-2 text-lg font-bold text-slate-800">{{ institution?.timezone }}</div>
                    <div class="mt-1 text-xs text-slate-400">UTC in database, converted in UI</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-xl p-5 border border-slate-200">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tenant Users</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-600">{{ stats?.usersCount || 1 }}</div>
                    <div class="mt-1 text-xs text-slate-400">Isolated to this madrasa</div>
                </div>
            </div>

            <!-- Academic Calendar Overview -->
            <div class="bg-white shadow-sm rounded-xl border border-slate-200 p-6">
                <div class="flex items-center justify-between border-b pb-4 mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Academic Years & Terms</h2>
                        <p class="text-sm text-slate-500">Configured academic cycles for {{ institution?.name }}</p>
                    </div>
                </div>

                <div v-if="academicYears?.length" class="space-y-4">
                    <div v-for="year in academicYears" :key="year.id" class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-slate-900">{{ year.name }}</span>
                            <span v-if="year.is_current" class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">
                                Current Academic Year
                            </span>
                        </div>
                        <div class="text-xs text-slate-600 mb-3">
                            {{ year.start_date }} &rarr; {{ year.end_date }}
                        </div>

                        <div v-if="year.terms?.length" class="flex flex-wrap gap-2">
                            <span v-for="term in year.terms" :key="term.id" class="px-3 py-1 bg-white border border-slate-300 rounded-md text-xs font-medium text-slate-700">
                                {{ term.name }} ({{ term.start_date }} - {{ term.end_date }})
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-6 text-slate-500 text-sm">
                    No academic years configured yet.
                </div>
            </div>
        </main>
    </div>
</template>
