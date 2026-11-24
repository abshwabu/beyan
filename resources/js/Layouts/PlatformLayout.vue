<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash || {});
const langMenuOpen = ref(false);
const currentLocale = computed(() => page.props.i18n?.locale || 'en');
const supportedLocales = computed(() => page.props.i18n?.supportedLocales || [
    { code: 'en', name: 'English', native: 'English', dir: 'ltr' },
    { code: 'am', name: 'Amharic', native: 'አማርኛ', dir: 'ltr' },
    { code: 'om', name: 'Afaan Oromoo', native: 'Afaan Oromoo', dir: 'ltr' },
    { code: 'ar', name: 'Arabic', native: 'العربية', dir: 'rtl' },
]);

const switchLocale = (code) => {
    langMenuOpen.value = false;
    router.visit(`/locale/${code}`, {
        preserveScroll: true,
    });
};

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
                        <!-- Language Switcher -->
                        <div class="relative">
                            <button
                                type="button"
                                @click="langMenuOpen = !langMenuOpen"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold transition"
                                title="Change Interface Language"
                            >
                                <span>🌐</span>
                                <span class="uppercase tracking-wider text-[11px]">{{ currentLocale }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                v-if="langMenuOpen"
                                class="absolute end-0 mt-1.5 w-40 bg-slate-900 border border-slate-700 rounded-xl shadow-xl py-1 z-50 text-xs"
                            >
                                <button
                                    v-for="l in supportedLocales"
                                    :key="l.code"
                                    type="button"
                                    @click="switchLocale(l.code)"
                                    class="w-full flex items-center justify-between px-3 py-2 text-start hover:bg-slate-800 transition"
                                    :class="currentLocale === l.code ? 'font-bold text-rose-400 bg-slate-800/80' : 'text-slate-300'"
                                >
                                    <span>{{ l.native }}</span>
                                    <span class="text-[10px] text-slate-500 uppercase font-mono">{{ l.code }}</span>
                                </button>
                            </div>
                        </div>

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
