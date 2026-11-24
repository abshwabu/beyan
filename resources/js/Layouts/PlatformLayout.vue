<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash || {});

defineProps({
    title: {
        type: String,
        default: 'Platform Control Panel',
    },
});

const isCurrent = (path) => {
    const currentUrl = page.url || '';
    if (path === '/platform') {
        return currentUrl === '/platform' || currentUrl === '/platform/' || currentUrl === '/platform/dashboard';
    }
    return currentUrl.startsWith(path);
};
</script>

<template>
    <Head :title="`${title} - Beyan Platform`" />

    <div class="min-h-screen bg-slate-100 text-slate-800 font-sans selection:bg-rose-500 selection:text-white flex flex-col">
        <!-- Top App Bar -->
        <header class="bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-50 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    
                    <!-- Left: Platform Branding -->
                    <div class="flex items-center gap-3">
                        <Link href="/platform" class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center font-bold text-base shadow-sm">
                                <span>ب</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-black text-lg text-white leading-none tracking-tight">Beyan</span>
                                <span class="text-[10px] text-slate-400 uppercase font-mono font-bold tracking-wider">Platform Hub</span>
                            </div>
                        </Link>

                        <span
                            class="ms-2 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                            :class="user?.role === 'platform_super_admin' ? 'bg-rose-600 text-white' : 'bg-amber-500 text-slate-900'"
                        >
                            {{ user?.role === 'platform_super_admin' ? 'Super Admin' : 'Support' }}
                        </span>
                    </div>

                    <!-- Right: User & Exit -->
                    <div class="flex items-center gap-4 text-xs">
                        <span class="text-slate-300 font-medium hidden sm:inline">{{ user?.name }} ({{ user?.email }})</span>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-semibold transition"
                        >
                            Log Out
                        </Link>
                    </div>

                </div>

                <!-- Navigation Tabs -->
                <nav class="flex space-x-1 sm:space-x-2 overflow-x-auto text-xs font-semibold py-2 border-t border-slate-800 scrollbar-none">
                    <Link
                        href="/platform"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        Overview
                    </Link>
                    <Link
                        href="/platform/institutions"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform/institutions') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        Institutions
                    </Link>
                    <Link
                        href="/platform/plans"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform/plans') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        Plans
                    </Link>
                    <Link
                        href="/platform/subscriptions"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform/subscriptions') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        Subscriptions
                    </Link>
                    <Link
                        href="/platform/payments"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform/payments') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        Payments
                    </Link>
                    <Link
                        href="/platform/demo-requests"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform/demo-requests') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        Demo Leads
                    </Link>
                    <Link
                        href="/platform/announcements"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform/announcements') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        Announcements
                    </Link>
                    <Link
                        href="/platform/health"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform/health') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        System Health
                    </Link>
                    <Link
                        href="/platform/settings"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform/settings') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        Settings
                    </Link>
                    <Link
                        href="/platform/staff"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform/staff') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        Staff
                    </Link>
                    <Link
                        href="/platform/audit-logs"
                        class="px-3 py-1.5 rounded-md whitespace-nowrap transition"
                        :class="isCurrent('/platform/audit-logs') ? 'bg-rose-600 text-white font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800'"
                    >
                        Audit Logs
                    </Link>
                </nav>

            </div>
        </header>

        <!-- Flash Messages -->
        <div v-if="flash.success" class="bg-emerald-600 text-white px-4 py-2.5 text-xs font-bold text-center shadow-inner">
            {{ flash.success }}
        </div>
        <div v-if="flash.error" class="bg-rose-600 text-white px-4 py-2.5 text-xs font-bold text-center shadow-inner">
            {{ flash.error }}
        </div>

        <!-- Main Body -->
        <main class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-500">
            Beyan SaaS Platform Control Center &copy; {{ new Date().getFullYear() }} · Built with care for East Africa
        </footer>
    </div>
</template>
